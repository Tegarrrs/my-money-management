<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$parser = app(App\Services\OCRParser::class);
$text = "
1. Ocha Tea 9.000
Lemon Tea 9.000
Biaya Pelayanan 6.300
PB1 10% 12.000
Rounding 300
Tota! Bayar yg: 132. 300
Total Bayar 132.300
";

print_r($parser->parse($text)->toArray());
