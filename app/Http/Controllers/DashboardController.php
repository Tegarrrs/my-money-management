<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\RecurringTransaction;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Services\Budget\BudgetOverviewService;
use App\Services\Dashboard\FinancialHealthService;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(
        BudgetOverviewService $budgetOverviewService,
        FinancialHealthService $financialHealthService,
    ): View {
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
            : null;

        $lastMonthSavingRate = $lastMonthIncome > 0
            ? round((($lastMonthIncome - $lastMonthExpense) / $lastMonthIncome) * 100)
            : null;

        $savingRateChange = $savingRate !== null && $lastMonthSavingRate !== null
            ? $savingRate - $lastMonthSavingRate
            : null;

        /* ── Percent changes ─────────────────────────────── */
        $incomeChange = $lastMonthIncome > 0
            ? round((($monthlyIncome - $lastMonthIncome) / $lastMonthIncome) * 100, 1)
            : null;

        $expenseChange = $lastMonthExpense > 0
            ? round((($monthlyExpense - $lastMonthExpense) / $lastMonthExpense) * 100, 1)
            : null;

        /* ── Monthly transaction count ───────────────────── */
        $monthlyTxCount = Transaction::forUser($userId)
            ->where('is_balance_adjustment', false)
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->count();
        $monthlyExpenseTxCount = Transaction::forUser($userId)
            ->expense()
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->count();
        $categorizedExpenseTxCount = Transaction::forUser($userId)
            ->expense()
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->whereNotNull('category_id')
            ->count();
        $categorizedRate = $monthlyExpenseTxCount > 0
            ? round(($categorizedExpenseTxCount / $monthlyExpenseTxCount) * 100)
            : null;

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
        $recordedMonths = collect($cashflowIncome)
            ->zip($cashflowExpense)
            ->filter(fn ($values) => (float) $values[0] > 0 || (float) $values[1] > 0)
            ->count();
        $cashflowHasData = $recordedMonths > 0;

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

        /* ── Financial health with explicit data sufficiency ─────── */
        $cashflowState = match (true) {
            $monthlyIncome <= 0 && $monthlyExpense <= 0 => 'unavailable',
            $monthlyIncome > $monthlyExpense => 'positive',
            $monthlyIncome === $monthlyExpense => 'neutral',
            default => 'negative',
        };
        $financialHealth = $financialHealthService->calculate([
            'transaction_count' => $monthlyTxCount,
            'income' => $monthlyIncome,
            'expense' => $monthlyExpense,
            'saving_rate' => $savingRate,
            'categorized_rate' => $categorizedRate,
            'recorded_months' => $recordedMonths,
            'has_wallet' => $wallets->isNotEmpty(),
            'has_budget' => $budgetSummary['has_budgets'],
            'budget_usage' => $budgetUsage,
            'balance' => $totalBalance,
        ]);
        $healthScore = $financialHealth['score'];

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
            'cashflowState',
            'cashflowHasData',
            'categorizedRate',
            'financialHealth',
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
