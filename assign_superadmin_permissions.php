<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== ASSIGNING ALL PERMISSIONS TO SUPER ADMIN ROLE ===\n\n";

$superAdminRole = DB::table('roles')->where('name', 'super admin')->first();

if (!$superAdminRole) {
    echo "✗ Super admin role not found!\n";
    exit;
}

// Get all permissions
$allPermissions = DB::table('permissions')->get();

echo "Total permissions: " . $allPermissions->count() . "\n";
echo "Super Admin Role ID: {$superAdminRole->id}\n\n";

$assigned = 0;
foreach ($allPermissions as $permission) {
    $exists = DB::table('permission_role')
        ->where('permission_id', $permission->id)
        ->where('role_id', $superAdminRole->id)
        ->exists();
    
    if (!$exists) {
        DB::table('permission_role')->insert([
            'permission_id' => $permission->id,
            'role_id' => $superAdminRole->id
        ]);
        $assigned++;
        echo "  + {$permission->name}\n";
    }
}

echo "\n✓ Assigned {$assigned} new permissions\n";

$total = DB::table('permission_role')->where('role_id', $superAdminRole->id)->count();
echo "✓ Super admin role now has {$total} total permissions\n";

// Verify user 1 has super admin role
$user1Role = DB::table('role_user')
    ->where('user_id', 1)
    ->where('role_id', $superAdminRole->id)
    ->exists();

if ($user1Role) {
    echo "✓ User 1 has super admin role attached\n";
} else {
    echo "✗ User 1 does NOT have super admin role - attaching now...\n";
    DB::table('role_user')->insert([
        'role_id' => $superAdminRole->id,
        'user_id' => 1,
        'user_type' => 'App\\Models\\User'
    ]);
    echo "✓ Super admin role attached to user 1\n";
}

// Clear all caches
DB::table('cache')->whereRaw('1=1')->delete();
Artisan::call('cache:clear');
Artisan::call('config:clear');
Artisan::call('view:clear');
Artisan::call('route:clear');

echo "\n✓ ALL CACHES CLEARED\n";
echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "✓ SUPER ADMIN SETUP COMPLETE\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
?>
