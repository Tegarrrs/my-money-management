<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\RecurringTransaction;
use App\Models\Wallet;
use App\Services\Recurring\RecurringTransactionProcessor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RecurringTransactionController extends Controller
{
    public function index(Request $request): View
    {
        $userId = $request->user()->id;
        $recurringTransactions = RecurringTransaction::with(['wallet', 'category', 'runs' => fn ($query) => $query->latest()->limit(3)])
            ->forUser($userId)
            ->orderByDesc('is_active')
            ->orderBy('next_run_at')
            ->get();

        return view('pages.recurring.index', [
            'recurringTransactions' => $recurringTransactions,
            'wallets' => Wallet::forUser($userId)->orderBy('name')->get(),
            'categories' => Category::forUser($userId)->orderBy('type')->orderBy('name')->get(),
            'activeCount' => $recurringTransactions->where('is_active', true)->count(),
            'dueCount' => $recurringTransactions->filter(fn ($item) => $item->is_active && $item->next_run_at?->isPast())->count(),
            'monthlyExpenseEstimate' => $recurringTransactions
                ->where('is_active', true)
                ->filter(fn ($item) => (float) $item->amount < 0)
                ->sum(fn ($item) => $this->monthlyEquivalent(abs((float) $item->amount), $item->frequency)),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->user()->recurringTransactions()->create($this->validatedData($request));

        return redirect()->route('recurring.index')->with('success', 'Transaksi berulang berhasil dibuat.');
    }

    public function update(Request $request, RecurringTransaction $recurringTransaction): RedirectResponse
    {
        $this->authorizeOwner($recurringTransaction);
        $recurringTransaction->update($this->validatedData($request));

        return redirect()->route('recurring.index')->with('success', 'Jadwal transaksi berhasil diperbarui.');
    }

    public function toggle(RecurringTransaction $recurringTransaction): RedirectResponse
    {
        $this->authorizeOwner($recurringTransaction);
        $recurringTransaction->update(['is_active' => ! $recurringTransaction->is_active]);

        return back()->with('success', $recurringTransaction->is_active ? 'Jadwal diaktifkan.' : 'Jadwal dijeda.');
    }

    public function runNow(
        RecurringTransaction $recurringTransaction,
        RecurringTransactionProcessor $processor,
    ): RedirectResponse {
        $this->authorizeOwner($recurringTransaction);
        $success = $processor->runNow($recurringTransaction);

        return back()->with(
            $success ? 'success' : 'error',
            $success ? 'Transaksi berhasil dicatat sekarang.' : 'Transaksi gagal diproses. Periksa konfigurasi jadwal.'
        );
    }

    public function destroy(RecurringTransaction $recurringTransaction): RedirectResponse
    {
        $this->authorizeOwner($recurringTransaction);
        $recurringTransaction->delete();

        return redirect()->route('recurring.index')->with('success', 'Jadwal transaksi dihapus. Transaksi yang sudah tercatat tetap tersimpan.');
    }

    private function validatedData(Request $request): array
    {
        $userId = $request->user()->id;
        $data = $request->validate([
            'wallet_id' => ['required', Rule::exists('wallets', 'id')->where('user_id', $userId)],
            'category_id' => [
                'nullable',
                Rule::exists('categories', 'id')->where(fn ($query) => $query
                    ->where('user_id', $userId)
                    ->where('type', $request->input('type'))),
            ],
            'type' => ['required', 'in:income,expense'],
            'amount' => ['required', 'numeric', 'min:1', 'max:999999999999.99'],
            'description' => ['required', 'string', 'max:255'],
            'detail' => ['nullable', 'string', 'max:1000'],
            'frequency' => ['required', 'in:daily,weekly,monthly,yearly'],
            'next_run_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:next_run_at'],
        ]);

        $data['amount'] = $data['type'] === 'expense'
            ? -abs((float) $data['amount'])
            : abs((float) $data['amount']);
        $data['next_run_at'] = Carbon::parse($data['next_run_at'])->startOfDay();
        unset($data['type']);

        return $data;
    }

    private function authorizeOwner(RecurringTransaction $recurringTransaction): void
    {
        abort_unless($recurringTransaction->user_id === auth()->id(), 403);
    }

    private function monthlyEquivalent(float $amount, string $frequency): float
    {
        return match ($frequency) {
            'daily' => $amount * 30,
            'weekly' => $amount * 4.33,
            'yearly' => $amount / 12,
            default => $amount,
        };
    }
}
