<?php

namespace App\Http\Controllers;

use App\Actions\Transaction\CreateTransaction;
use App\Actions\Transaction\DeleteTransaction;
use App\Actions\Transaction\UpdateTransaction;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Requests\UpdateTransactionRequest;
use App\Models\Category;
use App\Models\CategorySuggestion;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function index(Request $request): View|string
    {
        $userId = auth()->id();

        $query = $this->getFilteredTransactionsQuery($request, $userId);

        $transactions = $query->paginate(15)->withQueryString();

        $wallets    = Wallet::forUser($userId)->orderBy('name')->get();
        $categories = Category::forUser($userId)->orderBy('type')->orderBy('name')->get();

        if ($request->ajax()) {
            return view('pages.transaction.partials.table', compact('transactions', 'wallets', 'categories'))->render();
        }

        return view('pages.transaction.index', compact('transactions', 'wallets', 'categories'));
    }

    private function getFilteredTransactionsQuery(Request $request, int $userId)
    {
        $query = Transaction::with(['category', 'wallet', 'receipt'])
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

        return $query;
    }

    public function store(StoreTransactionRequest $request, CreateTransaction $action): RedirectResponse
    {
        $data = $request->validated();
        if ($request->hasFile('receipt_image')) {
            $data['receipt_image'] = $request->file('receipt_image');
        }

        $action->execute(auth()->user(), $data);

        return redirect()->route('transaction.index')
            ->with('success', 'Transaksi berhasil ditambahkan.');
    }

    public function update(UpdateTransactionRequest $request, Transaction $transaction, UpdateTransaction $action): RedirectResponse
    {
        $this->authorizeOwner($transaction);

        $data = $request->validated();
        if ($request->hasFile('receipt_image')) {
            $data['receipt_image'] = $request->file('receipt_image');
        }

        $action->execute($transaction, $data);

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

    public function suggestCategory(Request $request): JsonResponse
    {
        $query = $request->query('query');
        if (empty($query)) {
            return response()->json(['category_id' => null]);
        }

        $userId = auth()->id();

        // 1. Search category_suggestions
        $suggestion = CategorySuggestion::where('user_id', $userId)
            ->where('keyword', 'like', '%' . $query . '%')
            ->orderBy('confidence', 'desc')
            ->first();

        if ($suggestion) {
            return response()->json(['category_id' => $suggestion->category_id]);
        }

        // 2. Search recent transactions
        $prevTx = Transaction::where('user_id', $userId)
            ->where('description', 'like', '%' . $query . '%')
            ->whereNotNull('category_id')
            ->orderByDesc('id')
            ->first();

        if ($prevTx) {
            return response()->json(['category_id' => $prevTx->category_id]);
        }

        return response()->json(['category_id' => null]);
    }

    public function export(Request $request)
    {
        $userId = auth()->id();
        $query = $this->getFilteredTransactionsQuery($request, $userId);
        $transactions = $query->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="transaksi_dompetra_' . now()->format('Ymd_His') . '.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];

        $callback = function () use ($transactions) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
            fputcsv($file, ['Tanggal', 'Deskripsi', 'Detail', 'Kategori', 'Dompet', 'Jenis', 'Jumlah']);

            foreach ($transactions as $tx) {
                $isTransfer = !is_null($tx->transfer_group_id);
                $isIncome   = !$isTransfer && $tx->amount >= 0;
                
                $type = $isTransfer ? 'Transfer' : ($isIncome ? 'Pemasukan' : 'Pengeluaran');
                $category = $isTransfer ? 'Transfer' : ($tx->category?->name ?? '—');
                
                fputcsv($file, [
                    $tx->transaction_date->format('Y-m-d'),
                    $tx->description ?? '—',
                    $tx->detail ?? '—',
                    $category,
                    $tx->wallet?->name ?? '—',
                    $type,
                    (float) abs($tx->amount),
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function bulkDestroy(Request $request, DeleteTransaction $action): RedirectResponse
    {
        $ids = $request->input('transaction_ids', []);
        if (empty($ids) || !is_array($ids)) {
            return redirect()->route('transaction.index')->with('error', 'Tidak ada transaksi yang dipilih.');
        }

        $userId = auth()->id();
        $transactions = Transaction::where('user_id', $userId)->whereIn('id', $ids)->get();

        $deletedCount = 0;
        foreach ($transactions as $transaction) {
            $action->execute($transaction);
            $deletedCount++;
        }

        return redirect()->route('transaction.index')
            ->with('success', "{$deletedCount} transaksi berhasil dihapus.");
    }

    public function bulkUpdateCategory(Request $request): RedirectResponse
    {
        $ids = $request->input('transaction_ids', []);
        $categoryId = $request->input('category_id');

        if (empty($ids) || !is_array($ids)) {
            return redirect()->route('transaction.index')->with('error', 'Tidak ada transaksi yang dipilih.');
        }

        $userId = auth()->id();
        
        if ($categoryId) {
            $category = Category::where('user_id', $userId)->where('id', $categoryId)->first();
            if (!$category) {
                return redirect()->route('transaction.index')->with('error', 'Kategori tidak valid.');
            }
        }

        DB::transaction(function () use ($userId, $ids, $categoryId) {
            Transaction::where('user_id', $userId)
                ->whereIn('id', $ids)
                ->whereNull('transfer_group_id')
                ->update(['category_id' => $categoryId ?: null]);
        });

        return redirect()->route('transaction.index')
            ->with('success', 'Kategori transaksi berhasil diperbarui secara massal.');
    }

    public function splits(Transaction $transaction): JsonResponse
    {
        $this->authorizeOwner($transaction);
        if (!$transaction->split_group_id) {
            return response()->json([]);
        }
        $splits = Transaction::where('split_group_id', $transaction->split_group_id)
            ->orderBy('id')
            ->get();
        return response()->json($splits);
    }

    private function authorizeOwner(Transaction $transaction): void
    {
        abort_unless($transaction->user_id === auth()->id(), 403);
    }
}
