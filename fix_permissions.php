<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== CHECKING PERMISSIONS SETUP ===\n\n";

// Check if permissions table exists and has data
$permissionsCount = DB::table('permissions')->count();
echo "Total permissions in database: {$permissionsCount}\n";

if ($permissionsCount == 0) {
    echo "\n✗ NO PERMISSIONS FOUND! Running permission seeder...\n";
    
    // Run the permission seeder
    Artisan::call('db:seed', ['--class' => 'PermissionTableSeeder', '--force' => true]);
    echo Artisan::output();
    
    $permissionsCount = DB::table('permissions')->count();
    echo "\nPermissions after seeding: {$permissionsCount}\n";
}

// Check company role permissions
$companyRole = DB::table('roles')->where('name', 'company')->first();
if ($companyRole) {
    $rolePermissions = DB::table('permission_role')
        ->where('role_id', $companyRole->id)
        ->count();
    
    echo "\nCompany role permissions count: {$rolePermissions}\n";
    
    if ($rolePermissions == 0) {
        echo "\n✗ Company role has NO permissions! Assigning all permissions...\n";
        
        // Get all permissions
        $allPermissions = DB::table('permissions')->pluck('id');
        
        foreach ($allPermissions as $permId) {
            DB::table('permission_role')->insertOrIgnore([
                'permission_id' => $permId,
                'role_id' => $companyRole->id
            ]);
        }
        
        echo "✓ Assigned {$allPermissions->count()} permissions to company role\n";
    }
}

// Verify user 3 has company role
$user3Role = DB::table('role_user')
    ->join('roles', 'role_user.role_id', '=', 'roles.id')
    ->where('role_user.user_id', 3)
    ->select('roles.name', 'roles.id')
    ->first();

if ($user3Role) {
    echo "\nUser 3 role: {$user3Role->name} (ID: {$user3Role->id})\n";
} else {
    echo "\n✗ User 3 has NO ROLE!\n";
}

// Clear all caches
DB::table('cache')->whereRaw('1=1')->delete();
Artisan::call('cache:clear');
Artisan::call('config:clear');
Artisan::call('view:clear');

echo "\n✓ ALL CACHES CLEARED\n";
echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "LOGOUT, CLOSE BROWSER, AND LOGIN AGAIN\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
?>
