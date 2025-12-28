<!DOCTYPE html>
<html>
<head>
    <title>Logo Settings Debug</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1a1a1a; color: #00ff00; }
        .section { background: #2a2a2a; padding: 15px; margin: 10px 0; border: 2px solid #00ff00; }
        .error { color: #ff0000; }
        .success { color: #00ff00; }
        .warning { color: #ffaa00; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #00ff00; padding: 8px; text-align: left; }
        th { background: #003300; }
        .duplicate { background: #330000; }
        img { max-width: 200px; margin: 10px; border: 2px solid #00ff00; }
    </style>
</head>
<body>
    <h1>🔍 LOGO SETTINGS DIAGNOSTIC</h1>
    
    <div class="section">
        <h2>1. DATABASE - All Logo Settings (Raw Query)</h2>
        <table>
            <tr>
                <th>ID</th>
                <th>Key</th>
                <th>Value</th>
                <th>Business</th>
                <th>Created By</th>
                <th>Created At</th>
                <th>Updated At</th>
            </tr>
            @php
                $logoKeys = ['logo_dark', 'logo_light', 'favicon'];
                $allLogoSettings = \App\Models\Setting::whereIn('key', $logoKeys)
                    ->where('business', 0)
                    ->where('created_by', Auth::user()->id)
                    ->orderBy('key')
                    ->orderBy('updated_at', 'desc')
                    ->get();
                
                $grouped = $allLogoSettings->groupBy('key');
            @endphp
            
            @forelse($allLogoSettings as $setting)
                @php
                    $isDuplicate = $grouped[$setting->key]->count() > 1 && 
                                   $grouped[$setting->key]->sortByDesc('updated_at')->first()->id != $setting->id;
                @endphp
                <tr class="{{ $isDuplicate ? 'duplicate' : '' }}">
                    <td>{{ $setting->id }}</td>
                    <td><strong>{{ $setting->key }}</strong> @if($isDuplicate) ⚠️ DUPLICATE @endif</td>
                    <td>{{ $setting->value }}</td>
                    <td>{{ $setting->business }}</td>
                    <td>{{ $setting->created_by }}</td>
                    <td>{{ $setting->created_at }}</td>
                    <td>{{ $setting->updated_at }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="warning">No logo settings found in database</td></tr>
            @endforelse
        </table>
        
        @foreach($logoKeys as $key)
            @if(isset($grouped[$key]) && $grouped[$key]->count() > 1)
                <p class="error">⚠️ DUPLICATE DETECTED: {{ $key }} has {{ $grouped[$key]->count() }} records!</p>
            @endif
        @endforeach
    </div>

    <div class="section">
        <h2>2. CACHE - What getAdminAllSetting() Returns</h2>
        @php
            $cachedSettings = getAdminAllSetting();
        @endphp
        <table>
            <tr>
                <th>Key</th>
                <th>Value from Cache</th>
            </tr>
            @foreach($logoKeys as $key)
                <tr>
                    <td><strong>{{ $key }}</strong></td>
                    <td class="{{ isset($cachedSettings[$key]) ? 'success' : 'error' }}">
                        {{ $cachedSettings[$key] ?? 'NOT SET' }}
                    </td>
                </tr>
            @endforeach
        </table>
    </div>

    <div class="section">
        <h2>3. FILE SYSTEM - Logo Files Existence</h2>
        @php
            $storage_path = storage_path('app/public/uploads/logo/');
            $public_path = public_path('uploads/logo/');
        @endphp
        
        <p><strong>Storage Path:</strong> {{ $storage_path }}</p>
        <p><strong>Public Path:</strong> {{ $public_path }}</p>
        
        <table>
            <tr>
                <th>Logo Key</th>
                <th>Cached Path</th>
                <th>File Exists?</th>
                <th>Full Path</th>
            </tr>
            @foreach($logoKeys as $key)
                @php
                    $path = $cachedSettings[$key] ?? null;
                    $exists = false;
                    $fullPath = 'N/A';
                    
                    if ($path) {
                        // Try different path combinations
                        $testPaths = [
                            storage_path($path),
                            public_path($path),
                            base_path($path),
                            $path,
                        ];
                        
                        foreach ($testPaths as $testPath) {
                            if (file_exists($testPath)) {
                                $exists = true;
                                $fullPath = $testPath;
                                break;
                            }
                        }
                    }
                @endphp
                <tr>
                    <td><strong>{{ $key }}</strong></td>
                    <td>{{ $path ?? 'NULL' }}</td>
                    <td class="{{ $exists ? 'success' : 'error' }}">
                        {{ $exists ? '✅ YES' : '❌ NO' }}
                    </td>
                    <td style="font-size: 10px;">{{ $fullPath }}</td>
                </tr>
            @endforeach
        </table>
        
        <h3>Available Logo Files on Disk:</h3>
        @php
            $logoDirs = [
                'uploads/logo' => public_path('uploads/logo'),
                'storage/uploads/logo' => storage_path('uploads/logo'),
            ];
        @endphp
        
        @foreach($logoDirs as $label => $dir)
            <p><strong>{{ $label }}:</strong></p>
            @if(is_dir($dir))
                <ul>
                    @php
                        $files = File::files($dir);
                    @endphp
                    @forelse($files as $file)
                        <li>{{ $file->getFilename() }} ({{ round($file->getSize()/1024, 2) }} KB) - Modified: {{ date('Y-m-d H:i:s', $file->getMTime()) }}</li>
                    @empty
                        <li class="warning">Directory is empty</li>
                    @endforelse
                </ul>
            @else
                <p class="error">Directory does not exist!</p>
            @endif
        @endforeach
    </div>

    <div class="section">
        <h2>4. VISUAL - Current Logo Display</h2>
        @foreach($logoKeys as $key)
            @php
                $path = $cachedSettings[$key] ?? null;
            @endphp
            @if($path)
                <div>
                    <p><strong>{{ $key }}:</strong></p>
                    <img src="{{ get_file($path) }}" alt="{{ $key }}" onerror="this.style.border='2px solid red'; this.alt='Failed to load: {{ $path }}';">
                    <p>URL: {{ get_file($path) }}</p>
                </div>
            @endif
        @endforeach
    </div>

    <div class="section">
        <h2>5. CACHE STATUS</h2>
        @php
            $cacheKey = 'admin_settings';
            $cacheExists = Cache::has($cacheKey);
        @endphp
        <p><strong>Cache Key:</strong> {{ $cacheKey }}</p>
        <p class="{{ $cacheExists ? 'success' : 'warning' }}"><strong>Cache Exists:</strong> {{ $cacheExists ? 'YES' : 'NO' }}</p>
        
        <form method="POST" action="/clear-logo-cache-now" style="margin-top: 20px;">
            @csrf
            <button type="submit" style="background: #ff0000; color: white; padding: 10px 20px; border: none; cursor: pointer; font-size: 16px;">
                🔥 CLEAR CACHE NOW
            </button>
        </form>
    </div>

    <div class="section">
        <h2>6. USER INFO</h2>
        <p><strong>User ID:</strong> {{ Auth::user()->id }}</p>
        <p><strong>User Type:</strong> {{ Auth::user()->type }}</p>
        <p><strong>User Email:</strong> {{ Auth::user()->email }}</p>
    </div>

    <div class="section">
        <h2>7. QUICK ACTIONS</h2>
        <form method="POST" action="/fix-logo-duplicates-now" style="display: inline;">
            @csrf
            <button type="submit" style="background: #ff6600; color: white; padding: 10px 20px; border: none; cursor: pointer; font-size: 16px; margin: 5px;">
                🔧 DELETE DUPLICATE LOGO SETTINGS
            </button>
        </form>
        
        <form method="POST" action="/reset-logo-settings" style="display: inline;">
            @csrf
            <button type="submit" style="background: #cc0000; color: white; padding: 10px 20px; border: none; cursor: pointer; font-size: 16px; margin: 5px;">
                ⚠️ RESET ALL LOGO SETTINGS
            </button>
        </form>
        
        <a href="/super-admin/settings" style="background: #0066cc; color: white; padding: 10px 20px; border: none; cursor: pointer; font-size: 16px; margin: 5px; display: inline-block; text-decoration: none;">
            ← Back to Settings
        </a>
    </div>

    <script>
        // Auto-refresh every 5 seconds
        // setTimeout(() => location.reload(), 5000);
    </script>
</body>
</html>
