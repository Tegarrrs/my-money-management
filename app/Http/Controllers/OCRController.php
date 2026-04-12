<?php

namespace App\Http\Controllers;

use App\Actions\OCR\ImportFromImage;
use App\Actions\Transaction\CreateTransaction;
use App\Http\Requests\OCRUploadRequest;
use App\Models\Category;
use App\Models\Wallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Thin controller – only delegates to use cases and returns responses.
 * No business logic lives here.
 */
class OCRController extends Controller
{
    public function __construct(
        private readonly ImportFromImage  $importFromImage,
        private readonly CreateTransaction $createTransaction,
    ) {}

    /**
     * Show the OCR import page.
     */
    public function upload(): View
    {
        $userId     = auth()->id();
        $wallets    = Wallet::forUser($userId)->orderBy('name')->get();
        $categories = Category::forUser($userId)->orderBy('type')->orderBy('name')->get();

        return view('pages.ocr.index', compact('wallets', 'categories'));
    }

    /**
     * Receive an image, run OCR + parsing, return JSON preview.
     * Nothing is written to the database here.
     */
    public function preview(OCRUploadRequest $request): JsonResponse
    {
        $items = $this->importFromImage->handle($request->file('image'));

        if (empty($items)) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada item yang bisa dibaca dari gambar. Coba gambar dengan tulisan yang lebih jelas.',
                'items'   => [],
                'count'   => 0,
            ], 422);
        }

        return response()->json([
            'success' => true,
            'count'   => count($items),
            'items'   => array_map(fn ($dto) => $dto->toArray(), $items),
        ]);
    }

    /**
     * Bulk-save confirmed OCR items to the database.
     */
    public function store(Request $request): RedirectResponse
    {
        $userId = auth()->id();

        $request->validate([
            'wallet_id'   => ['required', 'integer', 'exists:wallets,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'type'        => ['required', 'in:income,expense'],
            'date'        => ['required', 'date'],
            'items'       => ['required', 'json'],
        ]);

        $wallet = Wallet::findOrFail($request->input('wallet_id'));
        abort_unless($wallet->user_id === $userId, 403);

        $user       = auth()->user();
        $rawItems   = json_decode($request->input('items'), true);
        $savedCount = 0;

        foreach ($rawItems as $item) {
            $name   = trim($item['name'] ?? '');
            $amount = (int) ($item['amount'] ?? 0);

            if ($name === '' || $amount <= 0) {
                continue;
            }

            $this->createTransaction->execute($user, [
                'wallet_id'        => $wallet->id,
                'category_id'      => $request->input('category_id') ?: null,
                'type'             => $request->input('type'),
                'description'      => $name,
                'amount'           => $amount,
                'transaction_date' => $request->input('date'),
            ]);

            $savedCount++;
        }

        return redirect()
            ->route('transaction.index')
            ->with('success', "{$savedCount} transaksi dari struk berhasil diimpor.");
    }
}
