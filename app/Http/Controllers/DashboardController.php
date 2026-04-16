<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $userId = auth()->id();

        $totalBalance = Wallet::forUser($userId)->sum('balance');

        $totalIncome = Transaction::forUser($userId)
            ->income()
            ->sum('amount');

        $totalExpense = Transaction::forUser($userId)
            ->expense()
            ->sum('amount');

        $recentTransactions = Transaction::with(['category', 'wallet'])
            ->forUser($userId)
            ->where(function ($q) {
                $q->whereNull('transfer_group_id')
                  ->orWhere('amount', '<', 0); // only show debit leg of transfers
            })
            ->recent(5)
            ->get();

        $wallets = Wallet::forUser($userId)->orderBy('name')->get();

        return view('dashboard', compact(
            'totalBalance',
            'totalIncome',
            'totalExpense',
            'recentTransactions',
            'wallets',
        ));
    }
}
