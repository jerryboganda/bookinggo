<?php
use App\Models\Business;
use App\Models\User;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Use the company user (ID 3)
$admin = User::find(3) ?? User::first();

echo "Admin found: " . ($admin ? "yes, ID=" . $admin->id : "no") . "\n";

if ($admin) {
    // Create a test business with Formlayout11
    $business = Business::create([
        'name' => 'Test Form Layout 11',
        'slug' => 'test-form-layout-11',
        'form_type' => 'form-layout',
        'layouts' => 'Formlayout11',
        'theme_color' => 'color1-Formlayout11',
        'status' => 'active',
        'is_disable' => 0,
        'created_by' => $admin->id,
    ]);
    
    echo "Created business: ID=" . $business->id . ", Slug=" . $business->slug . ", Layout=" . $business->layouts . "\n";
} else {
    echo "User not found\n";
}
