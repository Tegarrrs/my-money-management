<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\RecurringTransaction;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Services\Budget\BudgetOverviewService;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(BudgetOverviewService $budgetOverviewService): View
    {
        $user = auth()->user();
        $userId = $user->id;
        $now = Carbon::now();

        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();
        $startOfLastMonth = $now->copy()->subMonth()->startOfMonth();
        $endOfLastMonth = $now->copy()->subMonth()->endOfMonth();

        /* ── Total balance ──────────────────────────────── */
        $totalBalance = (float) Wallet::forUser($userId)->sum('balance');

        /* ── Monthly income / expense ───────────────────── */
        $monthlyIncome = (float) Transaction::forUser($userId)
            ->income()
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        $monthlyExpense = abs((float) Transaction::forUser($userId)
            ->expense()
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->sum('amount'));

        /* ── Previous-month comparison ───────────────────── */
        $lastMonthIncome = (float) Transaction::forUser($userId)
            ->income()
            ->whereBetween('transaction_date', [$startOfLastMonth, $endOfLastMonth])
            ->sum('amount');

        $lastMonthExpense = abs((float) Transaction::forUser($userId)
            ->expense()
            ->whereBetween('transaction_date', [$startOfLastMonth, $endOfLastMonth])
            ->sum('amount'));

        /* ── Saving rate ─────────────────────────────────── */
        $savingRate = $monthlyIncome > 0
            ? round((($monthlyIncome - $monthlyExpense) / $monthlyIncome) * 100)
            : 0;

        $lastMonthSavingRate = $lastMonthIncome > 0
            ? round((($lastMonthIncome - $lastMonthExpense) / $lastMonthIncome) * 100)
            : 0;

        $savingRateChange = $savingRate - $lastMonthSavingRate;

        /* ── Percent changes ─────────────────────────────── */
        $incomeChange = $lastMonthIncome > 0
            ? round((($monthlyIncome - $lastMonthIncome) / $lastMonthIncome) * 100, 1)
            : ($monthlyIncome > 0 ? 100 : 0);

        $expenseChange = $lastMonthExpense > 0
            ? round((($monthlyExpense - $lastMonthExpense) / $lastMonthExpense) * 100, 1)
            : ($monthlyExpense > 0 ? 100 : 0);

        /* ── Monthly transaction count ───────────────────── */
        $monthlyTxCount = Transaction::forUser($userId)
            ->where('is_balance_adjustment', false)
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->count();

        /* ── Actual monthly budget usage ─────────────────── */
        $budgetSummary = $budgetOverviewService->forMonth($user, $now);
        $budgetUsage = $budgetSummary['has_budgets']
            ? round($budgetSummary['percentage'])
            : 0;

        /* ── Cashflow trend — 6 months ───────────────────── */
        $cashflowLabels = [];
        $cashflowIncome = [];
        $cashflowExpense = [];

        for ($i = 5; $i >= 0; $i--) {
            $m = $now->copy()->subMonths($i);
            $s = $m->copy()->startOfMonth();
            $e = $m->copy()->endOfMonth();

            $cashflowLabels[] = $m->translatedFormat('M');
            $cashflowIncome[] = (float) Transaction::forUser($userId)->income()
                ->whereBetween('transaction_date', [$s, $e])->sum('amount');
            $cashflowExpense[] = abs((float) Transaction::forUser($userId)->expense()
                ->whereBetween('transaction_date', [$s, $e])->sum('amount'));
        }

        /* ── Expense by category — donut chart ───────────── */
        $expenseCatRows = Transaction::with('category')
            ->forUser($userId)
            ->expense()
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->whereNotNull('category_id')
            ->selectRaw('category_id, SUM(ABS(amount)) as total_amount')
            ->groupBy('category_id')
            ->orderByDesc('total_amount')
            ->limit(6)
            ->get();

        $donutLabels = $expenseCatRows->map(fn ($r) => $r->category?->name ?? 'Lainnya')->toArray();
        $donutValues = $expenseCatRows->map(fn ($r) => (int) round((float) $r->total_amount))->toArray();

        /* ── Top spending categories ─────────────────────── */
        $topCategories = Transaction::with('category')
            ->forUser($userId)
            ->expense()
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->whereNotNull('category_id')
            ->selectRaw('category_id, SUM(ABS(amount)) as total_amount')
            ->groupBy('category_id')
            ->orderByDesc('total_amount')
            ->limit(5)
            ->get();

        /* ── Wallets ─────────────────────────────────────── */
        $wallets = Wallet::forUser($userId)->orderByDesc('balance')->get();

        /* ── Recent transactions ─────────────────────────── */
        $recentTransactions = Transaction::with(['category', 'wallet'])
            ->forUser($userId)
            ->where(function ($q) {
                $q->whereNull('transfer_group_id')
                    ->orWhere('amount', '<', 0);
            })
            ->recent(8)
            ->get();

        /* ── Financial health score ───────────────────────── */
        $cashflowPositive = $monthlyIncome >= $monthlyExpense;
        $healthScore = 0;

        // Saving rate  → 40 pts
        if ($savingRate >= 20) {
            $healthScore += 40;
        } elseif ($savingRate >= 10) {
            $healthScore += 25;
        } elseif ($savingRate > 0) {
            $healthScore += 10;
        }

        // Positive total balance → 30 pts
        if ($totalBalance > 0) {
            $healthScore += 30;
        } elseif ($totalBalance >= 0) {
            $healthScore += 10;
        }

        // Cashflow → 30 pts
        if ($cashflowPositive) {
            $healthScore += 30;
        } elseif ($monthlyExpense > 0 && ($monthlyIncome / $monthlyExpense) >= 0.9) {
            $healthScore += 15;
        }

        $healthScore = min(100, max(0, $healthScore));

        /* ── Insight helpers ─────────────────────────────── */
        $largestCategory = $topCategories->first();
        $negativeWallet = $wallets->first(fn ($w) => $w->balance < 0);

        /* ── Onboarding progress & data-quality reminder ─────────── */
        $hasRegularTransaction = Transaction::forUser($userId)
            ->where('is_balance_adjustment', false)
            ->exists();
        $onboardingChecklist = [
            'wallet' => $wallets->isNotEmpty(),
            'categories' => Category::forUser($userId)->exists(),
            'transaction' => $hasRegularTransaction,
        ];
        $onboardingProgress = collect($onboardingChecklist)->filter()->count();
        $uncategorizedCount = Transaction::forUser($userId)
            ->whereNull('category_id')
            ->whereNull('transfer_group_id')
            ->where('is_balance_adjustment', false)
            ->count();
        $recurringDueCount = RecurringTransaction::forUser($userId)
            ->where('is_active', true)
            ->whereNotNull('next_run_at')
            ->where('next_run_at', '<=', now())
            ->count();
        $upcomingRecurring = RecurringTransaction::with(['wallet', 'category'])
            ->forUser($userId)
            ->where('is_active', true)
            ->whereBetween('next_run_at', [now(), now()->addDays(7)])
            ->orderBy('next_run_at')
            ->limit(3)
            ->get();

        return view('dashboard', compact(
            'totalBalance',
            'monthlyIncome',
            'monthlyExpense',
            'lastMonthIncome',
            'lastMonthExpense',
            'savingRate',
            'lastMonthSavingRate',
            'savingRateChange',
            'incomeChange',
            'expenseChange',
            'monthlyTxCount',
            'budgetUsage',
            'budgetSummary',
            'cashflowLabels',
            'cashflowIncome',
            'cashflowExpense',
            'donutLabels',
            'donutValues',
            'topCategories',
            'wallets',
            'recentTransactions',
            'healthScore',
            'cashflowPositive',
            'largestCategory',
            'negativeWallet',
            'onboardingChecklist',
            'onboardingProgress',
            'uncategorizedCount',
            'recurringDueCount',
            'upcomingRecurring',
        ));
    }
}
