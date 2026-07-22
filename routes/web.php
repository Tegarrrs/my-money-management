<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImportTransactionController;
use App\Http\Controllers\OCRController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\WalletController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::middleware('auth')->group(function () {

    Route::prefix('onboarding')->name('onboarding.')->group(function () {
        Route::get('/', [OnboardingController::class, 'show'])->name('show');
        Route::post('/wallet', [OnboardingController::class, 'storeWallet'])->name('wallet');
        Route::post('/categories', [OnboardingController::class, 'confirmCategories'])->name('categories');
        Route::post('/transaction', [OnboardingController::class, 'storeTransaction'])->name('transaction');
        Route::post('/skip', [OnboardingController::class, 'skip'])->name('skip');
    });

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
    // Keep old name as alias for backwards compat in existing links
    // Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Wallet CRUD
    Route::prefix('wallet')->name('wallet.')->group(function () {
        Route::get('/', [WalletController::class, 'index'])->name('index');
        Route::post('/', [WalletController::class, 'store'])->name('store');
        Route::put('/{wallet}', [WalletController::class, 'update'])->name('update');
        Route::delete('/{wallet}', [WalletController::class, 'destroy'])->name('destroy');
    });

    // Category CRUD
    Route::prefix('category')->name('category.')->group(function () {
        Route::get('/', [CategoryController::class, 'index'])->name('index');
        Route::post('/', [CategoryController::class, 'store'])->name('store');
        Route::put('/{category}', [CategoryController::class, 'update'])->name('update');
        Route::delete('/{category}', [CategoryController::class, 'destroy'])->name('destroy');
    });

    // Transaction CRUD
    Route::prefix('transaction')->name('transaction.')->group(function () {
        Route::get('/', [TransactionController::class, 'index'])->name('index');
        Route::get('/suggest-category', [TransactionController::class, 'suggestCategory'])->name('suggest-category');
        Route::get('/export', [TransactionController::class, 'export'])->name('export');
        Route::get('/{transaction}/splits', [TransactionController::class, 'splits'])->name('splits');
        Route::post('/bulk-delete', [TransactionController::class, 'bulkDestroy'])->name('bulk-destroy');
        Route::post('/bulk-update-category', [TransactionController::class, 'bulkUpdateCategory'])->name('bulk-update-category');
        Route::post('/', [TransactionController::class, 'store'])->name('store');
        Route::put('/{transaction}', [TransactionController::class, 'update'])->name('update');
        Route::delete('/{transaction}', [TransactionController::class, 'destroy'])->name('destroy');
    });

    Route::get('/receipts/{receipt}', [ReceiptController::class, 'show'])->name('receipts.show');

    // Import
    Route::prefix('import')->name('import.')->group(function () {
        Route::get('/', [ImportTransactionController::class, 'show'])->name('show');
        Route::post('/preview', [ImportTransactionController::class, 'preview'])->name('preview');
        Route::post('/store', [ImportTransactionController::class, 'store'])->name('store');
    });

    // Report
    Route::get('/report', [ReportController::class, 'index'])->name('report.index');

    // OCR Import
    Route::prefix('ocr')->name('ocr.')->group(function () {
        Route::get('/', [OCRController::class, 'upload'])->name('upload');
        Route::post('/preview', [OCRController::class, 'preview'])->name('preview');
        Route::post('/store', [OCRController::class, 'store'])->name('store');
    });

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
