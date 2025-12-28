<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== AMAD DIAGNOSTIC CENTRE LOGIN CREDENTIALS ===\n\n";

$user = DB::table('users')->where('id', 3)->first();

if ($user) {
    echo "Email: " . $user->email . "\n";
    echo "Name: " . $user->name . "\n";
    echo "Type: " . $user->type . "\n";
    echo "\nNOTE: Check INSTALLATION_CREDENTIALS.txt file for the password\n";
} else {
    echo "User not found!\n";
}

// Also check if INSTALLATION_CREDENTIALS.txt exists
$credFile = __DIR__ . '/INSTALLATION_CREDENTIALS.txt';
if (file_exists($credFile)) {
    echo "\n=== FROM INSTALLATION_CREDENTIALS.txt ===\n";
    echo file_get_contents($credFile);
}
?>
