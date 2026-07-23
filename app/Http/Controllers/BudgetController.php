<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\Category;
use App\Services\Budget\BudgetOverviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BudgetController extends Controller
{
    public function index(Request $request, BudgetOverviewService $overviewService): View
    {
        $month = $this->monthFromRequest($request);
        $user = $request->user();
        $overview = $overviewService->forMonth($user, $month);
        $categories = Category::forUser($user->id)->expense()->orderBy('name')->get();
        $budgetByCategory = $overview['items']->keyBy(fn ($item) => $item['category']->id);

        return view('pages.budget.index', [
            'month' => $month,
            'overview' => $overview,
            'categories' => $categories,
            'budgetByCategory' => $budgetByCategory,
            'previousMonth' => $month->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $month->copy()->addMonth()->format('Y-m'),
        ]);
    }

    public function sync(Request $request): RedirectResponse
    {
        $user = $request->user();
        $period = $this->validatedPeriod($request);
        $data = $request->validate([
            'budgets' => ['required', 'array'],
            'budgets.*.category_id' => [
                'required',
                Rule::exists('categories', 'id')
                    ->where('user_id', $user->id)
                    ->where('type', 'expense'),
            ],
            'budgets.*.amount' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'budgets.*.warning_threshold' => ['nullable', 'integer', 'min:50', 'max:95'],
        ]);

        DB::transaction(function () use ($data, $period, $user) {
            foreach ($data['budgets'] as $row) {
                $amount = (float) ($row['amount'] ?? 0);
                $budgetQuery = Budget::forUser($user->id)
                    ->where('category_id', (int) $row['category_id'])
                    ->forPeriod($period);

                if ($amount <= 0) {
                    $budgetQuery->delete();

                    continue;
                }

                $budget = $budgetQuery->first() ?? new Budget([
                    'user_id' => $user->id,
                    'category_id' => (int) $row['category_id'],
                    'period' => $period,
                ]);
                $budget->fill([
                    'amount' => $amount,
                    'warning_threshold' => (int) ($row['warning_threshold'] ?? 80),
                ]);
                $budget->save();
            }
        });

        return redirect()->route('budget.index', ['month' => Carbon::parse($period)->format('Y-m')])
            ->with('success', 'Anggaran bulanan berhasil disimpan.');
    }

    public function copyPrevious(Request $request): RedirectResponse
    {
        $user = $request->user();
        $period = Carbon::createFromFormat('Y-m', $request->validate([
            'period' => ['required', 'date_format:Y-m'],
        ])['period'])->startOfMonth();
        $previous = $period->copy()->subMonth();

        $previousBudgets = Budget::forUser($user->id)
            ->forPeriod($previous->toDateString())
            ->whereHas('category', fn ($query) => $query->expense())
            ->get();

        foreach ($previousBudgets as $budget) {
            $alreadyExists = Budget::forUser($user->id)
                ->where('category_id', $budget->category_id)
                ->forPeriod($period->toDateString())
                ->exists();

            if (! $alreadyExists) {
                Budget::create([
                    'user_id' => $user->id,
                    'category_id' => $budget->category_id,
                    'period' => $period->toDateString(),
                    'amount' => $budget->amount,
                    'warning_threshold' => $budget->warning_threshold,
                ]);
            }
        }

        $message = $previousBudgets->isEmpty()
            ? 'Bulan sebelumnya belum memiliki anggaran.'
            : 'Anggaran bulan sebelumnya berhasil disalin.';

        return redirect()->route('budget.index', ['month' => $period->format('Y-m')])
            ->with($previousBudgets->isEmpty() ? 'error' : 'success', $message);
    }

    private function monthFromRequest(Request $request): Carbon
    {
        $validated = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

        return isset($validated['month'])
            ? Carbon::createFromFormat('Y-m', $validated['month'])->startOfMonth()
            : now()->startOfMonth();
    }

    private function validatedPeriod(Request $request): string
    {
        $validated = $request->validate([
            'period' => ['required', 'date_format:Y-m'],
        ]);

        return Carbon::createFromFormat('Y-m', $validated['period'])
            ->startOfMonth()
            ->toDateString();
    }
}
