<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== ASSIGNING ALL PERMISSIONS TO COMPANY ROLE ===\n\n";

$companyRole = DB::table('roles')->where('name', 'company')->first();

// Get all permissions
$allPermissions = DB::table('permissions')->get();

echo "Total permissions: " . $allPermissions->count() . "\n";

$assigned = 0;
foreach ($allPermissions as $permission) {
    $exists = DB::table('permission_role')
        ->where('permission_id', $permission->id)
        ->where('role_id', $companyRole->id)
        ->exists();
    
    if (!$exists) {
        DB::table('permission_role')->insert([
            'permission_id' => $permission->id,
            'role_id' => $companyRole->id
        ]);
        $assigned++;
        echo "  + {$permission->name}\n";
    }
}

echo "\n✓ Assigned {$assigned} new permissions\n";

$total = DB::table('permission_role')->where('role_id', $companyRole->id)->count();
echo "✓ Company role now has {$total} total permissions\n";

// Clear all caches including Laravel cache
DB::table('cache')->whereRaw('1=1')->delete();
Artisan::call('cache:clear');
Artisan::call('config:clear');
Artisan::call('view:clear');
Artisan::call('route:clear');

echo "\n✓ ALL CACHES CLEARED\n";
echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "NOW:\n";
echo "1. LOGOUT from the dashboard\n";
echo "2. CLOSE your browser completely\n";
echo "3. Open browser and login again\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
?>
