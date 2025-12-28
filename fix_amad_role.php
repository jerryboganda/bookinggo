<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== FIXING AMAD USER ROLE ===\n\n";

// Check what roles exist
$roles = DB::table('roles')->get();
echo "Available Roles:\n";
foreach ($roles as $role) {
    echo "  - ID: {$role->id}, Name: {$role->name}\n";
}

// Create company role if it doesn't exist
if (!DB::table('roles')->where('name', 'company')->exists()) {
    echo "\n=== Creating 'company' role ===\n";
    $companyRoleId = DB::table('roles')->insertGetId([
        'name' => 'company',
        'display_name' => 'Company',
        'description' => 'Company/Business Owner',
        'created_at' => now(),
        'updated_at' => now()
    ]);
    echo "✓ Created company role with ID: {$companyRoleId}\n";
} else {
    $companyRole = DB::table('roles')->where('name', 'company')->first();
    $companyRoleId = $companyRole->id;
    echo "\n✓ Company role exists with ID: {$companyRoleId}\n";
}

// Check user 3 current roles
$user3Roles = DB::table('role_user')->where('user_id', 3)->get();
echo "\n=== User 3 (Amad) Current Roles ===\n";
if ($user3Roles->count() > 0) {
    foreach ($user3Roles as $ur) {
        $roleName = DB::table('roles')->where('id', $ur->role_id)->value('name');
        echo "  - Role ID: {$ur->role_id}, Name: {$roleName}\n";
    }
} else {
    echo "  - NO ROLES ASSIGNED!\n";
}

// Attach company role to user 3
if (!DB::table('role_user')->where('user_id', 3)->where('role_id', $companyRoleId)->exists()) {
    DB::table('role_user')->insert([
        'role_id' => $companyRoleId,
        'user_id' => 3,
        'user_type' => 'App\\Models\\User'
    ]);
    echo "\n✓ Attached company role to user 3 (Amad)\n";
} else {
    echo "\n✓ User 3 already has company role\n";
}

// Clear menu cache for user 3
DB::table('cache')->where('key', 'like', '%sidebar_menu_3%')->delete();
Artisan::call('cache:clear');
Artisan::call('optimize:clear');

echo "\n✓ ALL CACHES CLEARED\n";
echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "PLEASE LOGOUT AND LOGIN AGAIN AT:\n";
echo "https://bookinggo.test/login\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
?>
