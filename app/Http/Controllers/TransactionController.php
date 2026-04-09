<?php

namespace App\Http\Controllers;

use App\Actions\Transaction\CreateTransaction;
use App\Actions\Transaction\DeleteTransaction;
use App\Actions\Transaction\UpdateTransaction;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Requests\UpdateTransactionRequest;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function index(Request $request): View
    {
        $userId = auth()->id();

        $query = Transaction::with(['category', 'wallet'])
            ->forUser($userId)
            ->orderByDesc('transaction_date')
            ->orderByDesc('id');

        // Filters
        if ($request->filled('date_from')) {
            $query->where('transaction_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('transaction_date', '<=', $request->date_to);
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        if ($request->filled('wallet_id')) {
            $query->where('wallet_id', $request->wallet_id);
        }
        if ($request->filled('type')) {
            match ($request->type) {
                'income'   => $query->where('amount', '>', 0)->whereNull('transfer_group_id'),
                'expense'  => $query->where('amount', '<', 0)->whereNull('transfer_group_id'),
                'transfer' => $query->whereNotNull('transfer_group_id')->where('amount', '<', 0), // show only debit leg
                default    => null,
            };
        } else {
            // By default exclude the "credit leg" of transfers to avoid duplicates
            $query->where(function ($q) {
                $q->whereNull('transfer_group_id')
                  ->orWhere('amount', '<', 0); // only show debit leg of transfers
            });
        }

        $transactions = $query->paginate(15)->withQueryString();

        $wallets    = Wallet::forUser($userId)->orderBy('name')->get();
        $categories = Category::forUser($userId)->orderBy('type')->orderBy('name')->get();

        return view('pages.transaction.index', compact('transactions', 'wallets', 'categories'));
    }

    public function store(StoreTransactionRequest $request, CreateTransaction $action): RedirectResponse
    {
        $action->execute(auth()->user(), $request->validated());

        return redirect()->route('transaction.index')
            ->with('success', 'Transaksi berhasil ditambahkan.');
    }

    public function update(UpdateTransactionRequest $request, Transaction $transaction, UpdateTransaction $action): RedirectResponse
    {
        $this->authorizeOwner($transaction);

        $action->execute($transaction, $request->validated());

        return redirect()->route('transaction.index')
            ->with('success', 'Transaksi berhasil diperbarui.');
    }

    public function destroy(Transaction $transaction, DeleteTransaction $action): RedirectResponse
    {
        $this->authorizeOwner($transaction);

        $action->execute($transaction);

        return redirect()->route('transaction.index')
            ->with('success', 'Transaksi berhasil dihapus.');
    }

    private function authorizeOwner(Transaction $transaction): void
    {
        abort_unless($transaction->user_id === auth()->id(), 403);
    }
}
