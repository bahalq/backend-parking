<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    Illuminate\Support\Facades\Mail::raw('Test mail from Laravel', function ($m) {
        $m->to('bahalqadam@gmail.com')->subject('SMTP test');
    });
    echo "MAIL_SENT\n";
} catch (Throwable $e) {
    echo 'MAIL_ERROR: ' . $e->getMessage() . "\n";
}
