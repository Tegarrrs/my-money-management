<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReceiptController extends Controller
{
    public function show(Receipt $receipt): StreamedResponse|BinaryFileResponse|Response
    {
        abort_unless($receipt->user_id === auth()->id(), 403);

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('local');

        if ($disk->exists($receipt->image_path)) {
            return $disk->response($receipt->image_path, null, [
                'Cache-Control' => 'private, max-age=300',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        }

        // Kompatibilitas untuk struk lama yang dahulu disimpan di public/uploads.
        $legacyPath = public_path(ltrim($receipt->image_path, '/'));
        abort_unless(is_file($legacyPath), 404);

        return response()->file($legacyPath, [
            'Cache-Control' => 'private, max-age=300',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
