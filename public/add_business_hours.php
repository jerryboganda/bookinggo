<!DOCTYPE html>
<html>
<head>
    <title>Add Business Hours</title>
</head>
<body>
    <h1>Adding Business Hours...</h1>
    <?php
        require_once __DIR__ . '/vendor/autoload.php';
        $app = require_once __DIR__ . '/bootstrap/app.php';
        $kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
        
        // Delete old records
        \App\Models\BusinessHours::where('business_id', 2)->delete();
        
        // Add Monday to Saturday
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        foreach ($days as $day) {
            \App\Models\BusinessHours::create([
                'business_id' => 2,
                'day' => $day,
                'start_time' => '15:00',
                'end_time' => '21:00',
                'is_close' => 0
            ]);
            echo "✓ Added $day: 3:00 PM - 9:00 PM<br>";
        }
        
        // Add Sunday as closed
        \App\Models\BusinessHours::create([
            'business_id' => 2,
            'day' => 'Sunday',
            'start_time' => '00:00',
            'end_time' => '00:00',
            'is_close' => 1
        ]);
        echo "✓ Added Sunday: CLOSED<br>";
        
        echo "<hr>";
        echo "<h2>Business Hours Added Successfully!</h2>";
        echo "<pre>";
        $hours = \App\Models\BusinessHours::where('business_id', 2)->orderBy('id')->get();
        foreach ($hours as $hour) {
            $status = $hour->is_close ? 'CLOSED' : $hour->start_time . ' - ' . $hour->end_time;
            echo $hour->day . ": " . $status . "\n";
        }
        echo "</pre>";
    ?>
</body>
</html>
