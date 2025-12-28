<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Current Roles ===\n";
$roles = DB::table('roles')->get();
foreach ($roles as $role) {
    echo "ID: {$role->id}, Name: {$role->name}\n";
}

echo "\n=== User 1 Current Roles ===\n";
$userRoles = DB::table('role_user')->where('user_id', 1)->get();
foreach ($userRoles as $ur) {
    $roleName = DB::table('roles')->where('id', $ur->role_id)->value('name');
    echo "Role ID: {$ur->role_id}, Name: {$roleName}\n";
}

// If super admin role doesn't exist, create it
if (!DB::table('roles')->where('name', 'super admin')->exists()) {
    echo "\n=== Creating super admin role ===\n";
    $roleId = DB::table('roles')->insertGetId([
        'name' => 'super admin',
        'display_name' => 'Super Admin',
        'description' => 'Super Administrator with full access',
        'created_at' => now(),
        'updated_at' => now()
    ]);
    echo "✓ Created super admin role with ID: {$roleId}\n";
    
    // Attach to user 1
    DB::table('role_user')->insert([
        'role_id' => $roleId,
        'user_id' => 1,
        'user_type' => 'App\\Models\\User'
    ]);
    echo "✓ Attached super admin role to user 1\n";
} else {
    $role = DB::table('roles')->where('name', 'super admin')->first();
    // Make sure it's attached to user 1
    if (!DB::table('role_user')->where('user_id', 1)->where('role_id', $role->id)->exists()) {
        DB::table('role_user')->insert([
            'role_id' => $role->id,
            'user_id' => 1,
            'user_type' => 'App\\Models\\User'
        ]);
        echo "✓ Attached super admin role to user 1\n";
    }
}

// Clear cache
Artisan::call('cache:forget', ['key' => 'sidebar_menu_1']);
Artisan::call('cache:clear');
Artisan::call('optimize:clear');
echo "\n✓ ALL CACHES CLEARED - PLEASE REFRESH YOUR BROWSER NOW!\n";
?>
