<?php

require_once 'bootstrap/app.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Debug Sanctum Configuration ===\n\n";
echo "env('SANCTUM_STATEFUL_DOMAINS'): " . env('SANCTUM_STATEFUL_DOMAINS') . "\n\n";
echo "config('sanctum.stateful'):\n";
var_dump(config('sanctum.stateful'));
echo "\n";