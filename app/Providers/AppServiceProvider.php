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
        // Bind OCR engine: swap TesseractOCRService for any cloud driver here
        $this->app->bind(OCRServiceInterface::class, TesseractOCRService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
