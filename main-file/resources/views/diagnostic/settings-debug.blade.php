<?php
// DIAGNOSTIC PAGE - Access via: /settings-diagnostic (add route manually if needed)

use App\Models\User;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

if (!Auth::check()) {
    die('Please login first');
}

if (Auth::user()->type !== 'super admin') {
    die('Super admin only');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Settings Diagnostic</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .section { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; border: 1px solid #ddd; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        h2 { border-bottom: 2px solid #333; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
        th { background: #333; color: white; }
        .btn { padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; margin: 5px; }
        .btn-danger { background: #dc3545; }
        .btn-success { background: #28a745; }
    </style>
</head>
<body>

<h1>🔍 Super Admin Settings Diagnostic</h1>

<?php
$currentUser = Auth::user();
$superAdmin = User::where('type', 'super admin')->first();
$creatorIdValue = creatorId();
$activeBusinessValue = getActiveBusiness();

?>

<div class="section">
    <h2>1. Current User Info</h2>
    <table>
        <tr><th>Property</th><th>Value</th></tr>
        <tr><td>ID</td><td><strong><?php echo $currentUser->id; ?></strong></td></tr>
        <tr><td>Name</td><td><?php echo $currentUser->name; ?></td></tr>
        <tr><td>Email</td><td><?php echo $currentUser->email; ?></td></tr>
        <tr><td>Type</td><td><strong><?php echo $currentUser->type; ?></strong></td></tr>
        <tr><td>Active Business</td><td><strong><?php echo $currentUser->active_business ?? 'NULL'; ?></strong></td></tr>
        <tr><td>creatorId()</td><td class="<?php echo $creatorIdValue == $currentUser->id ? 'success' : 'error'; ?>"><strong><?php echo $creatorIdValue; ?></strong></td></tr>
        <tr><td>getActiveBusiness()</td><td class="<?php echo $activeBusinessValue === 0 ? 'success' : 'error'; ?>"><strong><?php echo $activeBusinessValue; ?></strong> <?php echo $activeBusinessValue === 0 ? '✓' : '✗ Should be 0!'; ?></td></tr>
    </table>
</div>

<div class="section">
    <h2>2. First Super Admin in DB</h2>
    <?php if ($superAdmin): ?>
    <table>
        <tr><th>Property</th><th>Value</th></tr>
        <tr><td>ID</td><td><strong><?php echo $superAdmin->id; ?></strong></td></tr>
        <tr><td>Name</td><td><?php echo $superAdmin->name; ?></td></tr>
        <tr><td>Email</td><td><?php echo $superAdmin->email; ?></td></tr>
        <tr><td>Match Current?</td><td class="<?php echo $superAdmin->id == $currentUser->id ? 'success' : 'error'; ?>"><strong><?php echo $superAdmin->id == $currentUser->id ? 'YES ✓' : 'NO ✗ MISMATCH!'; ?></strong></td></tr>
    </table>
    <?php if ($superAdmin->id != $currentUser->id): ?>
    <p class="error"><strong>⚠️ CRITICAL ISSUE: You are logged in as super admin ID <?php echo $currentUser->id; ?>, but getAdminAllSetting() retrieves settings for super admin ID <?php echo $superAdmin->id; ?>!</strong></p>
    <?php endif; ?>
    <?php else: ?>
    <p class="error">No super admin found!</p>
    <?php endif; ?>
</div>

<div class="section">
    <h2>3. Settings in Database</h2>
    <?php
    $yourSettings = Setting::where('created_by', $currentUser->id)->where('business', 0)->count();
    $firstAdminSettings = $superAdmin ? Setting::where('created_by', $superAdmin->id)->where('business', 0)->count() : 0;
    $totalSuperAdminSettings = Setting::whereIn('created_by', User::where('type', 'super admin')->pluck('id'))
        ->where('business', 0)->count();
    $incorrectBusinessSettings = Setting::where('created_by', $currentUser->id)->where('business', '!=', 0)->count();
    ?>
    <table>
        <tr><th>Description</th><th>Count</th><th>Query</th></tr>
        <tr>
            <td>Your settings (user_id=<?php echo $currentUser->id; ?>, business=0)</td>
            <td class="<?php echo $yourSettings > 0 ? 'success' : 'error'; ?>"><strong><?php echo $yourSettings; ?></strong></td>
            <td><code>created_by=<?php echo $currentUser->id; ?>, business=0</code></td>
        </tr>
        <?php if ($superAdmin && $superAdmin->id != $currentUser->id): ?>
        <tr>
            <td>First super admin settings (user_id=<?php echo $superAdmin->id; ?>, business=0)</td>
            <td class="<?php echo $firstAdminSettings > 0 ? 'warning' : 'error'; ?>"><strong><?php echo $firstAdminSettings; ?></strong></td>
            <td><code>created_by=<?php echo $superAdmin->id; ?>, business=0</code></td>
        </tr>
        <?php endif; ?>
        <tr>
            <td>All super admin settings (business=0)</td>
            <td><strong><?php echo $totalSuperAdminSettings; ?></strong></td>
            <td><code>All super admins, business=0</code></td>
        </tr>
        <tr>
            <td class="<?php echo $incorrectBusinessSettings > 0 ? 'error' : 'success'; ?>">Your settings with WRONG business ID (business!=0)</td>
            <td class="<?php echo $incorrectBusinessSettings > 0 ? 'error' : 'success'; ?>"><strong><?php echo $incorrectBusinessSettings; ?></strong> <?php echo $incorrectBusinessSettings > 0 ? '✗ PROBLEM!' : '✓'; ?></td>
            <td><code>created_by=<?php echo $currentUser->id; ?>, business!=0</code></td>
        </tr>
    </table>
</div>

<div class="section">
    <h2>4. getAdminAllSetting() Test</h2>
    <?php
    Cache::forget('admin_settings');
    $adminSettings = getAdminAllSetting();
    $settingsCount = count($adminSettings);
    ?>
    <p>getAdminAllSetting() returns <strong class="<?php echo $settingsCount > 0 ? 'success' : 'error'; ?>"><?php echo $settingsCount; ?> settings</strong></p>
    
    <?php if ($settingsCount > 0): ?>
    <p>Sample settings:</p>
    <table>
        <tr><th>Key</th><th>Value</th></tr>
        <?php 
        $sample = array_slice($adminSettings, 0, 10, true);
        foreach ($sample as $key => $value): 
        ?>
        <tr><td><?php echo htmlspecialchars($key); ?></td><td><?php echo htmlspecialchars(substr($value, 0, 100)); ?></td></tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>
</div>

<div class="section">
    <h2>5. Recent Logs</h2>
    <?php
    $logFile = storage_path('logs/laravel.log');
    if (file_exists($logFile)) {
        $logs = file($logFile);
        $recentLogs = array_slice($logs, -30);
        $settingsLogs = array_filter($recentLogs, function($line) {
            return strpos($line, 'SettingsController') !== false || 
                   strpos($line, 'getAdminAllSetting') !== false ||
                   strpos($line, 'SETTINGS SAVE') !== false;
        });
        
        if (count($settingsLogs) > 0) {
            echo "<pre style='background:#f8f8f8;padding:10px;overflow-x:auto;'>";
            foreach ($settingsLogs as $log) {
                echo htmlspecialchars($log);
            }
            echo "</pre>";
        } else {
            echo "<p class='warning'>No recent settings-related logs found. Try making a change and refresh this page.</p>";
        }
    } else {
        echo "<p class='error'>Log file not found</p>";
    }
    ?>
</div>

<div class="section">
    <h2>6. Actions</h2>
    
    <p><strong>Quick Fixes Available:</strong></p>
    
    <form method="POST" action="{{ route('settings.diagnostic.action') }}" style="margin: 10px 0;">
        @csrf
        <input type="hidden" name="action" value="clear_cache">
        <button type="submit" class="btn" style="font-size: 16px; padding: 15px 30px;">
            🔄 Clear All Caches
        </button>
    </form>
    
    <?php if ($incorrectBusinessSettings > 0): ?>
    <form method="POST" action="{{ route('settings.diagnostic.action') }}" style="margin: 10px 0;">
        @csrf
        <input type="hidden" name="action" value="fix_business_ids">
        <button type="submit" class="btn btn-danger" style="font-size: 16px; padding: 15px 30px;" onclick="return confirm('This will update <?php echo $incorrectBusinessSettings; ?> settings to business=0. Continue?');">
            🔧 Fix Business IDs (<?php echo $incorrectBusinessSettings; ?> settings) - CLICK THIS!
        </button>
    </form>
    <p class="error" style="font-weight: bold;">👆 IMPORTANT: Click the button above to fix your <?php echo $incorrectBusinessSettings; ?> settings!</p>
    <?php endif; ?>
    
    <?php if ($superAdmin && $superAdmin->id != $currentUser->id && $firstAdminSettings > 0): ?>
    <form method="POST" action="{{ route('settings.diagnostic.action') }}" style="margin: 10px 0;">
        @csrf
        <input type="hidden" name="action" value="migrate_settings">
        <input type="hidden" name="from_user" value="<?php echo $superAdmin->id; ?>">
        <input type="hidden" name="to_user" value="<?php echo $currentUser->id; ?>">
        <button type="submit" class="btn btn-danger" style="font-size: 16px; padding: 15px 30px;" onclick="return confirm('This will migrate <?php echo $firstAdminSettings; ?> settings from user <?php echo $superAdmin->id; ?> to user <?php echo $currentUser->id; ?>. Continue?');">
            📦 Migrate Settings to Current User
        </button>
    </form>
    <?php endif; ?>
    
    <hr style="margin: 20px 0;">
    
    <a href="/settings" class="btn btn-success" style="font-size: 16px; padding: 15px 30px;">
        ⚙️ Go to Settings Page
    </a>
</div>

<div class="section">
    <h2>7. Diagnosis Summary</h2>
    <?php
    $issues = [];
    
    if ($activeBusinessValue !== 0) {
        $issues[] = "getActiveBusiness() returns $activeBusinessValue instead of 0";
    }
    
    if ($superAdmin && $superAdmin->id != $currentUser->id) {
        $issues[] = "Logged in as super admin ID {$currentUser->id}, but getAdminAllSetting() retrieves for ID {$superAdmin->id}";
    }
    
    if ($yourSettings == 0 && $settingsCount == 0) {
        $issues[] = "No settings found for your user";
    }
    
    if ($incorrectBusinessSettings > 0) {
        $issues[] = "$incorrectBusinessSettings settings have wrong business ID";
    }
    
    if (count($issues) > 0) {
        echo "<div class='error'><strong>❌ ISSUES FOUND:</strong><ul>";
        foreach ($issues as $issue) {
            echo "<li>$issue</li>";
        }
        echo "</ul></div>";
    } else {
        echo "<div class='success'><strong>✓ No obvious issues detected</strong></div>";
        echo "<p>If settings still don't save, check:<br>";
        echo "1. Browser console for JavaScript errors<br>";
        echo "2. Network tab to see if POST request is successful<br>";
        echo "3. Try making a change and check 'Recent Logs' section above</p>";
    }
    ?>
</div>

<p style="text-align:center;color:#999;margin-top:30px;">Diagnostic Page | <?php echo date('Y-m-d H:i:s'); ?></p>

</body>
</html>
