<?php

use App\Models\Receipt;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        Receipt::query()
            ->where('image_path', 'like', 'uploads/receipts/%')
            ->eachById(function (Receipt $receipt) {
                $legacyPath = public_path($receipt->image_path);

                if (! is_file($legacyPath)) {
                    return;
                }

                $privatePath = 'receipts/legacy/'.$receipt->id.'_'.basename($legacyPath);
                $stored = Storage::disk('local')->put($privatePath, File::get($legacyPath));

                if (! $stored || ! Storage::disk('local')->exists($privatePath)) {
                    throw new RuntimeException("Gagal memindahkan struk #{$receipt->id} ke storage privat.");
                }

                $receipt->update(['image_path' => $privatePath]);
                File::delete($legacyPath);
            });
    }

    public function down(): void
    {
        Receipt::query()
            ->where('image_path', 'like', 'receipts/legacy/%')
            ->eachById(function (Receipt $receipt) {
                $disk = Storage::disk('local');

                if (! $disk->exists($receipt->image_path)) {
                    return;
                }

                $privatePath = $receipt->image_path;
                $publicDirectory = public_path('uploads/receipts');
                File::ensureDirectoryExists($publicDirectory);
                $legacyPath = 'uploads/receipts/'.preg_replace('/^\d+_/', '', basename($receipt->image_path));
                File::put(public_path($legacyPath), $disk->get($receipt->image_path));
                $receipt->update(['image_path' => $legacyPath]);
                $disk->delete($privatePath);
            });
    }
};
