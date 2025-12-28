<?php
use App\Models\Business;
use App\Models\Category;
use App\Models\Service;
use App\Models\Location;
use App\Models\Staff;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Get the test business (most recent one)
$business = Business::where('slug', 'like', 'test-form-layout-11%')->orderBy('id', 'desc')->first();

if (!$business) {
    echo "Business not found\n";
    exit(1);
}

echo "Found business: " . $business->name . " (ID=" . $business->id . ")\n";

// Create a category
$category = Category::create([
    'name' => 'Hair Cutting',
    'business_id' => $business->id,
    'created_by' => $business->created_by,
]);
echo "Created category: " . $category->name . " (ID=" . $category->id . ")\n";

// Create a service
$service = Service::create([
    'name' => 'Haircut',
    'category_id' => $category->id,
    'price' => 25.00,
    'duration' => 30,
    'business_id' => $business->id,
    'created_by' => $business->created_by,
]);
echo "Created service: " . $service->name . " (ID=" . $service->id . ")\n";

// Create a location
$location = Location::create([
    'name' => 'Main Shop',
    'address' => '123 Main Street',
    'business_id' => $business->id,
    'created_by' => $business->created_by,
]);
echo "Created location: " . $location->name . " (ID=" . $location->id . ")\n";

// Create a staff user
use Illuminate\Support\Facades\Hash;

$staffUser = \App\Models\User::create([
    'name' => 'John Barber',
    'email' => 'john@example.com',
    'password' => Hash::make('password'),
    'type' => 'staff',
    'business_id' => $business->id,
    'created_by' => $business->created_by,
]);
echo "Created staff user: " . $staffUser->name . " (ID=" . $staffUser->id . ")\n";

// Create a staff
$staff = Staff::create([
    'name' => 'John Barber',
    'user_id' => $staffUser->id,
    'location_id' => $location->id,
    'service_id' => $service->id,
    'business_id' => $business->id,
    'created_by' => $business->created_by,
]);
echo "Created staff: " . $staff->name . " (ID=" . $staff->id . ")\n";

echo "\nTest data created successfully!\n";
