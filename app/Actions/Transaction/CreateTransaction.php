<?php

namespace App\Actions\Transaction;

use App\Models\Category;
use App\Models\CategorySuggestion;
use App\Models\Receipt;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\TransactionParser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateTransaction
{
    public function __construct(private readonly TransactionParser $parser) {}

    public function execute(User $user, array $data): Transaction
    {
        return DB::transaction(function () use ($user, $data) {
            $type = $data['type'] ?? 'expense';

            // ── Upload Receipt ──
            $receiptId = null;
            if (!empty($data['receipt_image']) && $data['receipt_image'] instanceof \Illuminate\Http\UploadedFile) {
                $file = $data['receipt_image'];
                $filename = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
                $destinationPath = public_path('uploads/receipts');
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }
                $file->move($destinationPath, $filename);
                $path = 'uploads/receipts/' . $filename;
                
                $receipt = Receipt::create([
                    'user_id' => $user->id,
                    'image_path' => $path,
                    'status' => 'completed',
                ]);
                $receiptId = $receipt->id;
            }

            // ── SPLIT TRANSACTION ──
            if (!empty($data['is_split']) && !empty($data['splits'])) {
                $wallet = Wallet::findOrFail($data['wallet_id']);
                $groupId = (string) Str::uuid();
                $date = $data['transaction_date'];
                $detail = $data['detail'] ?? null;
                $firstTx = null;

                foreach ($data['splits'] as $split) {
                    $splitCategory = !empty($split['category_id']) ? Category::find($split['category_id']) : null;
                    $splitAmount = $this->parser->normaliseAmount((float) $split['amount'], $splitCategory, 'expense');

                    $tx = $user->transactions()->create([
                        'wallet_id'        => $wallet->id,
                        'category_id'      => $splitCategory?->id,
                        'amount'           => $splitAmount,
                        'description'      => $split['description'] ?? $data['description'] ?? null,
                        'detail'           => $detail,
                        'transaction_date' => $date,
                        'split_group_id'   => $groupId,
                        'receipt_id'       => $receiptId,
                    ]);

                    $wallet->increment('balance', $splitAmount);

                    if (!$firstTx) {
                        $firstTx = $tx;
                    }

                    // Save category suggestion for each split
                    if ($splitCategory && !empty($split['description'])) {
                        CategorySuggestion::updateOrCreate(
                            ['user_id' => $user->id, 'keyword' => strtolower(trim($split['description']))],
                            ['category_id' => $splitCategory->id, 'confidence' => 1.0]
                        );
                    }
                }

                return $firstTx;
            }

            // ── TRANSFER ──────────────────────────────────────────────────
            if ($type === 'transfer') {
                $fromWallet = Wallet::findOrFail($data['wallet_id']);
                $toWallet   = Wallet::findOrFail($data['to_wallet_id']);
                $amount     = (float) $data['amount'];
                $groupId    = (string) Str::uuid();
                $desc       = $data['description'] ?? null;
                $detail     = $data['detail'] ?? null;
                $date       = $data['transaction_date'];

                // Debit from source wallet
                $out = $user->transactions()->create([
                    'wallet_id'         => $fromWallet->id,
                    'category_id'       => null,
                    'amount'            => -$amount,
                    'description'       => $desc ?? "Transfer ke {$toWallet->name}",
                    'detail'            => $detail,
                    'transaction_date'  => $date,
                    'transfer_group_id' => $groupId,
                    'receipt_id'        => $receiptId,
                ]);

                // Credit to destination wallet
                $user->transactions()->create([
                    'wallet_id'         => $toWallet->id,
                    'category_id'       => null,
                    'amount'            => $amount,
                    'description'       => $desc ?? "Transfer dari {$fromWallet->name}",
                    'detail'            => $detail,
                    'transaction_date'  => $date,
                    'transfer_group_id' => $groupId,
                    'receipt_id'        => $receiptId,
                ]);

                $fromWallet->decrement('balance', $amount);
                $toWallet->increment('balance', $amount);

                // Handling Biaya Admin
                if (!empty($data['admin_fee']) && $data['admin_fee'] > 0) {
                    $adminFee = (float) $data['admin_fee'];
                    $adminGroup = $groupId;

                    $user->transactions()->create([
                        'wallet_id'         => $fromWallet->id,
                        'category_id'       => null,
                        'amount'            => -$adminFee,
                        'description'       => "Biaya Admin Transfer",
                        'detail'            => "Biaya admin untuk transfer ke {$toWallet->name}",
                        'transaction_date'  => $date,
                        'transfer_group_id' => $adminGroup,
                        'receipt_id'        => $receiptId,
                    ]);

                    $fromWallet->decrement('balance', $adminFee);
                }

                return $out;
            }

            // ── INCOME / EXPENSE ──────────────────────────────────────────
            /** @var ?Category $category */
            $category = isset($data['category_id']) ? Category::find($data['category_id']) : null;

            /** @var Wallet $wallet */
            $wallet = Wallet::findOrFail($data['wallet_id']);

            $amount = $this->parser->normaliseAmount((float) $data['amount'], $category, $type);

            $transaction = $user->transactions()->create([
                'wallet_id'        => $wallet->id,
                'category_id'      => $category?->id,
                'amount'           => $amount,
                'description'      => $data['description'] ?? null,
                'detail'           => $data['detail'] ?? null,
                'transaction_date' => $data['transaction_date'],
                'receipt_id'       => $receiptId,
            ]);

            $wallet->increment('balance', $amount);

            // Save category suggestion dynamically
            if ($category && !empty($data['description'])) {
                CategorySuggestion::updateOrCreate(
                    ['user_id' => $user->id, 'keyword' => strtolower(trim($data['description']))],
                    ['category_id' => $category->id, 'confidence' => 1.0]
                );
            }

            return $transaction;
        });
    }
}
