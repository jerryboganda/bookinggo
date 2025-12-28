<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

// Get all users
$users = User::all();
foreach($users as $u) {
    echo "ID=" . $u->id . ", Type=" . $u->type . ", Name=" . $u->name . "\n";
}
