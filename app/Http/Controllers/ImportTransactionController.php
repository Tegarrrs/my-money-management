<?php

namespace App\Http\Controllers;

use App\Actions\Transaction\CreateTransaction;
use App\Models\Category;
use App\Models\Wallet;
use App\Services\TextImportParser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ImportTransactionController extends Controller
{
    public function __construct(
        private readonly TextImportParser $parser,
        private readonly CreateTransaction $creator,
    ) {}

    /**
     * Show the import form page.
     */
    public function show(): View
    {
        $userId = auth()->id();
        $wallets = Wallet::forUser($userId)->orderBy('name')->get();
        $categories = Category::forUser($userId)->orderBy('type')->orderBy('name')->get();

        return view('pages.import.index', compact('wallets', 'categories'));
    }

    /**
     * Parse the raw text and return a JSON preview (no DB writes).
     */
    public function preview(Request $request): JsonResponse
    {
        $request->validate([
            'raw_text' => ['required', 'string', 'max:50000'],
        ]);

        $items = $this->parser->parse($request->input('raw_text'));

        return response()->json([
            'count' => count($items),
            'items' => $items,
        ]);
    }

    /**
     * Persist the confirmed import items to the database.
     */
    public function store(Request $request): RedirectResponse
    {
        $userId = auth()->id();

        $request->validate([
            'wallet_id' => ['required', 'integer', Rule::exists('wallets', 'id')->where('user_id', $userId)],
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->where('user_id', $userId)],
            'type' => ['required', 'in:expense,income'],
            'items' => ['required', 'json'],
        ]);

        $user = auth()->user();
        $items = json_decode($request->input('items'), true);

        if (empty($items) || ! is_array($items)) {
            return back()->withErrors(['items' => 'Data import kosong atau tidak valid.']);
        }

        $wallet = $user->wallets()->findOrFail($request->input('wallet_id'));

        $savedCount = 0;

        foreach ($items as $item) {
            // Basic sanity checks on each row
            if (
                empty($item['date']) ||
                empty($item['description']) ||
                ! isset($item['amount']) ||
                (int) $item['amount'] <= 0
            ) {
                continue;
            }

            $this->creator->execute($user, [
                'wallet_id' => $wallet->id,
                'category_id' => $request->input('category_id') ?: null,
                'type' => $request->input('type'),
                'description' => $item['description'],
                'amount' => (int) $item['amount'],
                'transaction_date' => $item['date'],
            ]);

            $savedCount++;
        }

        return redirect()
            ->route('transaction.index')
            ->with('success', "{$savedCount} transaksi berhasil diimpor.");
    }
}
