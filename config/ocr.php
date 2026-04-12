<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Tesseract Binary Path
    |--------------------------------------------------------------------------
    | Path to the tesseract executable. Defaults to 'tesseract' (assumes it
    | is in the system PATH). Override with the full absolute path if needed.
    |
    | Example Windows: 'C:\Program Files\Tesseract-OCR\tesseract.exe'
    | Example Linux:   '/usr/bin/tesseract'
    |
    */
    'tesseract_binary' => env('TESSERACT_BINARY', 'tesseract'),

    /*
    |--------------------------------------------------------------------------
    | OCR Engine Driver
    |--------------------------------------------------------------------------
    | Which OCR driver to use. Currently only 'tesseract' is supported.
    | Setting this allows future swapping to cloud providers (e.g. Google Vision).
    |
    */
    'driver' => env('OCR_DRIVER', 'tesseract'),

    /*
    |--------------------------------------------------------------------------
    | Max Image Size (bytes)
    |--------------------------------------------------------------------------
    | Maximum allowed upload size for receipt images. Default: 5 MB.
    | This mirrors the FormRequest validation rule.
    |
    */
    'max_image_size' => env('OCR_MAX_IMAGE_SIZE', 5 * 1024 * 1024),

];
