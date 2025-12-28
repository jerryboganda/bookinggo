<?php

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Logo Test Page</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 40px;
            background: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #4CAF50;
            padding-bottom: 10px;
        }
        .logo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin: 30px 0;
        }
        .logo-card {
            border: 2px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            background: #fafafa;
        }
        .logo-card h3 {
            margin-top: 0;
            color: #555;
        }
        .logo-card img {
            max-width: 200px;
            max-height: 100px;
            border: 1px solid #ccc;
            padding: 10px;
            background: white;
            border-radius: 5px;
        }
        .logo-card .info {
            margin-top: 15px;
            text-align: left;
            font-size: 12px;
            background: #fff;
            padding: 10px;
            border-radius: 5px;
        }
        .logo-card .info div {
            margin: 5px 0;
            word-break: break-all;
        }
        .status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 3px;
            font-weight: bold;
            margin-top: 10px;
        }
        .status.success {
            background: #4CAF50;
            color: white;
        }
        .status.error {
            background: #f44336;
            color: white;
        }
        .technical-details {
            background: #f0f0f0;
            padding: 20px;
            border-radius: 5px;
            margin-top: 30px;
        }
        .technical-details pre {
            background: #fff;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Logo Display Test Page</h1>
        <p><strong>Test URL:</strong> https://bookinggo.test/logo-test.php</p>
        <p>This page tests if logos are properly loaded and displayed.</p>

        <div class="logo-grid">
            <?php
            $adminSettings = getAdminAllSetting();
            $logos = [
                'logo_dark' => 'Dark Logo',
                'logo_light' => 'Light Logo',
                'favicon' => 'Favicon'
            ];

            foreach ($logos as $key => $label) {
                $value = $adminSettings[$key] ?? null;
                $exists = false;
                $url = '';
                
                if ($value) {
                    $exists = check_file($value);
                    $url = get_file($value);
                }
                ?>
                <div class="logo-card">
                    <h3><?php echo $label; ?></h3>
                    <?php if ($value && $exists): ?>
                        <img src="<?php echo $url; ?>?<?php echo time(); ?>" alt="<?php echo $label; ?>" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'200\' height=\'100\'%3E%3Crect width=\'200\' height=\'100\' fill=\'%23ff0000\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' dominant-baseline=\'middle\' text-anchor=\'middle\' fill=\'white\' font-family=\'Arial\'%3EERROR%3C/text%3E%3C/svg%3E'">
                        <div class="status success">✅ WORKING</div>
                    <?php else: ?>
                        <div style="width: 200px; height: 100px; background: #ff0000; color: white; display: flex; align-items: center; justify-content: center; margin: 0 auto;">
                            ❌ NOT FOUND
                        </div>
                        <div class="status error">❌ MISSING</div>
                    <?php endif; ?>
                    
                    <div class="info">
                        <div><strong>Key:</strong> <?php echo $key; ?></div>
                        <div><strong>Value:</strong> <?php echo $value ?: 'NULL'; ?></div>
                        <div><strong>Exists:</strong> <?php echo $exists ? '✅ YES' : '❌ NO'; ?></div>
                        <div><strong>URL:</strong> <br><?php echo $url ?: 'N/A'; ?></div>
                        <?php if ($value): ?>
                            <div><strong>Full Path:</strong> <br><?php echo base_path($value); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php
            }
            ?>
        </div>

        <div class="technical-details">
            <h2>📋 Technical Details</h2>
            <pre><?php
                echo "Storage Setting: " . ($adminSettings['storage_setting'] ?? 'not set') . "\n";
                echo "Base Path: " . base_path() . "\n";
                echo "Public Path: " . public_path() . "\n";
                echo "Asset URL: " . asset('') . "\n";
                echo "\n";
                echo "All Admin Settings:\n";
                foreach (['logo_dark', 'logo_light', 'favicon', 'storage_setting'] as $key) {
                    if (isset($adminSettings[$key])) {
                        echo "  {$key} = {$adminSettings[$key]}\n";
                    }
                }
            ?></pre>
        </div>

        <div style="margin-top: 30px; padding: 20px; background: #e8f5e9; border-left: 4px solid #4CAF50; border-radius: 5px;">
            <h3>✅ What to check:</h3>
            <ol>
                <li>All three logos should display correctly above</li>
                <li>All status badges should be green (✅ WORKING)</li>
                <li>URLs should start with https://bookinggo.test/</li>
                <li>If any logo shows red error, the file is missing or path is wrong</li>
            </ol>
        </div>
    </div>
</body>
</html>
