<?php

namespace App\Http\Controllers;

use App\Actions\Wallet\CreateWallet;
use App\Actions\Wallet\DeleteWallet;
use App\Actions\Wallet\UpdateWallet;
use App\Http\Requests\StoreWalletRequest;
use App\Http\Requests\UpdateWalletRequest;
use App\Models\Wallet;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class WalletController extends Controller
{
    public function index(): View
    {
        $wallets = Wallet::forUser(auth()->id())
            ->orderBy('name')
            ->get();

        return view('pages.wallet.index', compact('wallets'));
    }

    public function store(StoreWalletRequest $request, CreateWallet $action): RedirectResponse
    {
        $action->execute(auth()->user(), $request->validated());

        return redirect()->route('wallet.index')
            ->with('success', 'Dompet berhasil ditambahkan.');
    }

    public function update(UpdateWalletRequest $request, Wallet $wallet, UpdateWallet $action): RedirectResponse
    {
        $this->authorizeOwner($wallet);

        $action->execute($wallet, $request->validated());

        return redirect()->route('wallet.index')
            ->with('success', 'Dompet berhasil diperbarui.');
    }

    public function destroy(Wallet $wallet, DeleteWallet $action): RedirectResponse
    {
        $this->authorizeOwner($wallet);

        $action->execute($wallet);

        return redirect()->route('wallet.index')
            ->with('success', 'Dompet berhasil dihapus.');
    }

    private function authorizeOwner(Wallet $wallet): void
    {
        abort_unless($wallet->user_id === auth()->id(), 403);
    }
}
