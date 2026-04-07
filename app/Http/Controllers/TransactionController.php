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
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function index(): View
    {
        $userId = auth()->id();

        $transactions = Transaction::with(['category', 'wallet'])
            ->forUser($userId)
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->paginate(15);

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
