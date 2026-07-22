<?php

namespace App\Http\Controllers;

use App\Actions\Transaction\CreateTransaction;
use App\Actions\Wallet\CreateWallet;
use App\Http\Requests\StoreWalletRequest;
use App\Services\Onboarding\DefaultCategoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OnboardingController extends Controller
{
    public function show(Request $request, DefaultCategoryService $categories): View|RedirectResponse
    {
        $user = $request->user();
        $categories->ensureFor($user);

        if ((int) $user->onboarding_step === 1 && $user->wallets()->exists()) {
            $user->update(['onboarding_step' => 2]);
        }

        if ((int) $user->onboarding_step > 3) {
            return redirect()->route('dashboard.index');
        }

        return view('onboarding.index', [
            'step' => (int) $user->fresh()->onboarding_step,
            'wallets' => $user->wallets()->orderBy('name')->get(),
            'incomeCategories' => $user->categories()->income()->orderBy('name')->get(),
            'expenseCategories' => $user->categories()->expense()->orderBy('name')->get(),
        ]);
    }

    public function storeWallet(
        StoreWalletRequest $request,
        CreateWallet $createWallet,
    ): RedirectResponse {
        if (! $request->user()->wallets()->exists()) {
            $createWallet->execute($request->user(), $request->validated());
        }

        $request->user()->update(['onboarding_step' => 2]);

        return redirect()->route('onboarding.show')
            ->with('success', 'Dompet pertama siap digunakan.');
    }

    public function confirmCategories(Request $request, DefaultCategoryService $categories): RedirectResponse
    {
        $categories->ensureFor($request->user());
        $request->user()->update(['onboarding_step' => 3]);

        return redirect()->route('onboarding.show');
    }

    public function storeTransaction(Request $request, CreateTransaction $createTransaction): RedirectResponse
    {
        $userId = $request->user()->id;
        $data = $request->validate([
            'wallet_id' => ['required', Rule::exists('wallets', 'id')->where('user_id', $userId)],
            'category_id' => [
                'nullable',
                Rule::exists('categories', 'id')->where(
                    fn ($query) => $query
                        ->where('user_id', $userId)
                        ->where('type', $request->input('type'))
                ),
            ],
            'type' => ['required', 'in:income,expense'],
            'amount' => ['required', 'numeric', 'min:1'],
            'description' => ['required', 'string', 'max:255'],
            'transaction_date' => ['required', 'date'],
        ]);

        $createTransaction->execute($request->user(), $data);
        $request->user()->update([
            'onboarding_step' => 4,
            'onboarding_completed_at' => now(),
        ]);

        return redirect()->route('dashboard.index')
            ->with('success', 'Onboarding selesai. Dompetra siap membantu keuanganmu!');
    }

    public function skip(Request $request): RedirectResponse
    {
        $request->user()->update(['onboarding_completed_at' => now()]);

        return redirect()->route('dashboard.index')
            ->with('success', 'Onboarding dilewati. Kamu dapat melengkapinya dari checklist dashboard.');
    }
}
