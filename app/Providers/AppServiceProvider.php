<?php

namespace App\Providers;

use App\Infrastructure\OCR\Contracts\OCRServiceInterface;
use App\Infrastructure\OCR\TesseractOCRService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind the receipt scanner to the Gemini AI implementation
        $this->app->bind(
            \App\Contracts\ReceiptScannerInterface::class,
            \App\Infrastructure\OCR\GeminiReceiptScanner::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
