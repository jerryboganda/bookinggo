<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== AMAD DIAGNOSTIC CENTRE ADMIN LOGIN ===\n\n";

$user = DB::table('users')->where('id', 3)->first();

if ($user) {
    // Set a new known password
    $newPassword = 'Amad@2025';
    $hashedPassword = Hash::make($newPassword);
    
    DB::table('users')->where('id', 3)->update([
        'password' => $hashedPassword
    ]);
    
    echo "✓ Password has been reset\n\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "LOGIN CREDENTIALS:\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    echo "🌐 Login URL: https://bookinggo.test/login\n\n";
    echo "📧 Email:    " . $user->email . "\n";
    echo "🔑 Password: " . $newPassword . "\n\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "Business:    Amad Diagnostic Centre - Gujranwala\n";
    echo "User Type:   Company Admin\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
} else {
    echo "✗ User not found!\n";
}
?>
