<?php
/**
 * CATEGORY BOOKING FEATURE - TEST SCRIPT
 * Verify implementation works correctly
 */

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

echo "🧪 CATEGORY BOOKING FEATURE - TEST SCRIPT\n";
echo "=========================================\n\n";

$business = \App\Models\Business::first();
if (!$business) {
    die("❌ No business found in database!\n");
}

echo "Testing with business: {$business->name} (slug: {$business->slug})\n\n";

// Test 1: Check categories exist
echo "TEST 1: Checking Categories\n";
echo "----------------------------\n";
$categories = \App\Models\category::where('business_id', $business->id)
    ->where('created_by', $business->created_by)
    ->get();

if ($categories->isEmpty()) {
    echo "⚠️  WARNING: No categories found for this business!\n";
    echo "   Create categories in admin panel first.\n";
} else {
    echo "✅ Found {$categories->count()} categories:\n";
    foreach ($categories as $cat) {
        $serviceCount = \App\Models\Service::where('category_id', $cat->id)->count();
        echo "   - {$cat->name} (ID: {$cat->id}) - {$serviceCount} services\n";
    }
}

// Test 2: Check services with categories
echo "\nTEST 2: Checking Services with Categories\n";
echo "------------------------------------------\n";
$services = \App\Models\Service::where('business_id', $business->id)->get();
$servicesWithCategory = $services->where('category_id', '!=', null)->count();
$servicesWithoutCategory = $services->where('category_id', null)->count();

echo "Total services: {$services->count()}\n";
echo "With category: {$servicesWithCategory}\n";
echo "Without category: {$servicesWithoutCategory}\n";

if ($servicesWithoutCategory > 0) {
    echo "\n⚠️  WARNING: {$servicesWithoutCategory} services have no category assigned!\n";
    echo "   These services won't appear in the booking form.\n";
    echo "   Assign categories in admin panel.\n";
}

// Test 3: Verify AJAX endpoint
echo "\nTEST 3: Testing AJAX Endpoint\n";
echo "------------------------------\n";
if (!$categories->isEmpty()) {
    $testCategory = $categories->first();
    echo "Testing with category: {$testCategory->name} (ID: {$testCategory->id})\n";
    
    // Simulate AJAX request
    $testServices = \App\Models\Service::where('business_id', $business->id)
        ->where('category_id', $testCategory->id)
        ->get(['id', 'name', 'price', 'duration']);
    
    echo "Services in this category: {$testServices->count()}\n";
    if ($testServices->count() > 0) {
        echo "✅ AJAX endpoint should work correctly\n";
        foreach ($testServices as $service) {
            echo "   - {$service->name} (₨{$service->price})\n";
        }
    } else {
        echo "⚠️  No services in this category\n";
    }
}

// Test 4: Check route exists
echo "\nTEST 4: Verifying Route\n";
echo "------------------------\n";
try {
    $route = route('get.services.by.category');
    echo "✅ Route registered: {$route}\n";
} catch (\Exception $e) {
    echo "❌ Route NOT found! Error: {$e->getMessage()}\n";
}

// Test 5: Check view files
echo "\nTEST 5: Checking View Files\n";
echo "----------------------------\n";
$appointmentFormPath = resource_path('views/web_layouts/appointment-form.blade.php');
$appPath = resource_path('views/web_layouts/app.blade.php');

if (file_exists($appointmentFormPath)) {
    $content = file_get_contents($appointmentFormPath);
    if (strpos($content, 'categorySelect') !== false) {
        echo "✅ appointment-form.blade.php has categorySelect\n";
    } else {
        echo "❌ appointment-form.blade.php MISSING categorySelect!\n";
    }
} else {
    echo "❌ appointment-form.blade.php NOT FOUND!\n";
}

if (file_exists($appPath)) {
    $content = file_get_contents($appPath);
    if (strpos($content, 'fetchServicesByCategory') !== false) {
        echo "✅ app.blade.php has fetchServicesByCategory function\n";
    } else {
        echo "❌ app.blade.php MISSING fetchServicesByCategory function!\n";
    }
} else {
    echo "❌ app.blade.php NOT FOUND!\n";
}

// Summary
echo "\n=========================================\n";
echo "TEST SUMMARY\n";
echo "=========================================\n\n";

if ($categories->isEmpty()) {
    echo "⚠️  ACTION REQUIRED:\n";
    echo "   1. Login to admin panel\n";
    echo "   2. Go to Categories section\n";
    echo "   3. Create at least one category\n";
    echo "   4. Assign categories to all services\n";
    echo "   5. Test booking form again\n\n";
} else if ($servicesWithoutCategory > 0) {
    echo "⚠️  ACTION RECOMMENDED:\n";
    echo "   Assign categories to {$servicesWithoutCategory} services\n";
    echo "   These services won't appear in booking form\n\n";
} else {
    echo "✅ READY TO TEST!\n\n";
    echo "📝 TEST THE BOOKING FORM:\n";
    echo "   1. Visit: https://bookinggo.test/appointments/{$business->slug}\n";
    echo "   2. You should see:\n";
    echo "      - Category dropdown (first)\n";
    echo "      - Service dropdown (disabled until category selected)\n";
    echo "   3. Select a category\n";
    echo "   4. Service dropdown should populate with services from that category\n";
    echo "   5. Complete the booking flow normally\n\n";
}

echo "🔄 TO ROLLBACK (if issues):\n";
echo "   Run: php C:\laragon\www\bookinggo\\backups\category-feature-20251220_102822\RESTORE.php\n\n";

echo "=========================================\n";
