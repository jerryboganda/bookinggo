<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Business;

// Get any user
$user = User::first();
echo "First user: ID=" . $user->id . ", Type=" . $user->type . ", Name=" . $user->name . "\n";

// Get existing business
$business = Business::first();
if ($business) {
    echo "Existing business: ID=" . $business->id . ", Name=" . $business->name . ", Created by=" . $business->created_by . "\n";
}
