<?php

namespace App\Actions\Transaction;

use App\Models\Category;
use App\Models\CategorySuggestion;
use App\Models\Receipt;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Services\TransactionParser;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UpdateTransaction
{
    public function __construct(private readonly TransactionParser $parser) {}

    public function execute(Transaction $transaction, array $data): Transaction
    {
        return DB::transaction(function () use ($transaction, $data) {
            $type = $data['type'] ?? 'expense';

            // ── Upload Receipt ──
            $receiptId = $transaction->receipt_id;
            if (! empty($data['receipt_image']) && $data['receipt_image'] instanceof UploadedFile) {
                $file = $data['receipt_image'];
                $path = $file->store('receipts', 'local');

                if ($path === false) {
                    throw new \RuntimeException('Gagal menyimpan gambar struk.');
                }

                $receipt = Receipt::create([
                    'user_id' => $transaction->user_id,
                    'image_path' => $path,
                    'status' => 'completed',
                ]);
                $receiptId = $receipt->id;
            }

            // ── HANDLE OLD SPLIT CLEANUP ──
            if ($transaction->split_group_id) {
                $oldGroupId = $transaction->split_group_id;
                $splits = Transaction::where('user_id', $transaction->user_id)
                    ->where('split_group_id', $oldGroupId)
                    ->get();

                // Revert balances of all splits
                foreach ($splits as $s) {
                    $w = Wallet::find($s->wallet_id);
                    if ($w) {
                        $w->increment('balance', -(float) $s->amount);
                    }
                }

                // Delete all other splits except the main one
                Transaction::where('split_group_id', $oldGroupId)
                    ->where('user_id', $transaction->user_id)
                    ->where('id', '!=', $transaction->id)
                    ->delete();

                // Clear split group ID from main transaction temporarily
                $transaction->update(['split_group_id' => null]);
            }

            // ── UPDATE TO SPLIT TRANSACTION ──
            if (! empty($data['is_split']) && ! empty($data['splits'])) {
                $wallet = $transaction->user->wallets()->findOrFail($data['wallet_id']);
                $groupId = (string) Str::uuid();
                $date = $data['transaction_date'];
                $detail = $data['detail'] ?? null;

                $isFirst = true;

                foreach ($data['splits'] as $split) {
                    $splitCategory = ! empty($split['category_id'])
                        ? $transaction->user->categories()->findOrFail($split['category_id'])
                        : null;
                    $splitAmount = $this->parser->normaliseAmount((float) $split['amount'], $splitCategory, 'expense');

                    if ($isFirst) {
                        // Update the existing transaction
                        $transaction->update([
                            'wallet_id' => $wallet->id,
                            'category_id' => $splitCategory?->id,
                            'amount' => $splitAmount,
                            'description' => $split['description'] ?? $data['description'] ?? null,
                            'detail' => $detail,
                            'transaction_date' => $date,
                            'split_group_id' => $groupId,
                            'receipt_id' => $receiptId,
                        ]);
                        $isFirst = false;
                    } else {
                        // Create a new transaction
                        $transaction->user->transactions()->create([
                            'wallet_id' => $wallet->id,
                            'category_id' => $splitCategory?->id,
                            'amount' => $splitAmount,
                            'description' => $split['description'] ?? $data['description'] ?? null,
                            'detail' => $detail,
                            'transaction_date' => $date,
                            'split_group_id' => $groupId,
                            'receipt_id' => $receiptId,
                        ]);
                    }

                    $wallet->increment('balance', $splitAmount);

                    // Save category suggestion
                    if ($splitCategory && ! empty($split['description'])) {
                        CategorySuggestion::updateOrCreate(
                            ['user_id' => $transaction->user_id, 'keyword' => strtolower(trim($split['description']))],
                            ['category_id' => $splitCategory->id, 'confidence' => 1.0]
                        );
                    }
                }

                return $transaction->fresh();
            }

            // ── TRANSFER (update both legs via transfer_group_id) ─────────
            if ($type === 'transfer' && $transaction->transfer_group_id) {
                $groupId = $transaction->transfer_group_id;
                $newAmount = (float) $data['amount'];
                $desc = $data['description'] ?? null;
                $detail = $data['detail'] ?? null;
                $date = $data['transaction_date'];

                $legs = Transaction::where('user_id', $transaction->user_id)
                    ->where('transfer_group_id', $groupId)
                    ->get();
                $outLeg = $legs->where('amount', '<', 0)->first() ?? $legs->first();
                $inLeg = $legs->where('amount', '>=', 0)->first() ?? $legs->last();

                $fromWallet = $transaction->user->wallets()->findOrFail($data['wallet_id']);
                $toWallet = $transaction->user->wallets()->findOrFail($data['to_wallet_id']);

                // Revert old balances
                $outLeg->wallet->increment('balance', abs((float) $outLeg->amount));
                $inLeg->wallet->increment('balance', -abs((float) $inLeg->amount));

                // Apply new balances
                $fromWallet->decrement('balance', $newAmount);
                $toWallet->increment('balance', $newAmount);

                $outLeg->update([
                    'wallet_id' => $fromWallet->id,
                    'amount' => -$newAmount,
                    'description' => $desc,
                    'detail' => $detail,
                    'transaction_date' => $date,
                    'receipt_id' => $receiptId,
                ]);
                $inLeg->update([
                    'wallet_id' => $toWallet->id,
                    'amount' => $newAmount,
                    'description' => $desc,
                    'detail' => $detail,
                    'transaction_date' => $date,
                    'receipt_id' => $receiptId,
                ]);

                return $transaction->fresh();
            }

            // ── INCOME / EXPENSE ──────────────────────────────────────────
            $oldAmount = (float) $transaction->amount;
            $oldWalletId = $transaction->wallet_id;

            /** @var ?Category $newCategory */
            $newCategory = isset($data['category_id'])
                ? $transaction->user->categories()->findOrFail($data['category_id'])
                : null;

            /** @var Wallet $newWallet */
            $newWallet = $transaction->user->wallets()->findOrFail($data['wallet_id']);

            $newAmount = $this->parser->normaliseAmount((float) $data['amount'], $newCategory, $type);

            if ($oldWalletId === $newWallet->id) {
                $newWallet->increment('balance', $newAmount - $oldAmount);
            } else {
                $oldWallet = Wallet::findOrFail($oldWalletId);
                $oldWallet->increment('balance', -$oldAmount);
                $newWallet->increment('balance', $newAmount);
            }

            $transaction->update([
                'wallet_id' => $newWallet->id,
                'category_id' => $newCategory?->id,
                'amount' => $newAmount,
                'description' => $data['description'] ?? null,
                'detail' => $data['detail'] ?? null,
                'transaction_date' => $data['transaction_date'],
                'transfer_group_id' => null,
                'receipt_id' => $receiptId,
            ]);

            // Save category suggestion dynamically
            if ($newCategory && ! empty($data['description'])) {
                CategorySuggestion::updateOrCreate(
                    ['user_id' => $transaction->user_id, 'keyword' => strtolower(trim($data['description']))],
                    ['category_id' => $newCategory->id, 'confidence' => 1.0]
                );
            }

            return $transaction->fresh();
        });
    }
}
