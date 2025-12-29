<?php

$logFile = __DIR__ . '/storage/logs/laravel.log';

if (file_exists($logFile)) {
    echo "<h2>Last 100 Lines of Laravel Log (Most Recent Errors):</h2>";
    echo "<pre style='background:#1e1e1e;color:#d4d4d4;padding:20px;overflow:auto;max-height:800px;font-size:12px;'>";
    
    $lines = file($logFile);
    $lastLines = array_slice($lines, -100);
    
    foreach ($lastLines as $line) {
        // Highlight errors
        if (stripos($line, 'error') !== false || stripos($line, 'exception') !== false) {
            echo "<span style='color:#f44336;font-weight:bold;'>" . htmlspecialchars($line) . "</span>";
        } else {
            echo htmlspecialchars($line);
        }
    }
    
    echo "</pre>";
} else {
    echo "<h2 style='color:red;'>Log file not found at: " . $logFile . "</h2>";
}
