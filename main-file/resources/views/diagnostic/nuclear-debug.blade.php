<!DOCTYPE html>
<html>
<head>
    <title>Nuclear Debug - Settings Save Test</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body {
            font-family: monospace;
            padding: 20px;
            background: #1a1a1a;
            color: #0f0;
        }
        .test-section {
            background: #000;
            border: 2px solid #0f0;
            padding: 20px;
            margin: 20px 0;
        }
        h1, h2 {
            color: #0ff;
            text-shadow: 0 0 10px #0ff;
        }
        button {
            background: #0f0;
            color: #000;
            border: none;
            padding: 15px 30px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            margin: 10px;
        }
        button:hover {
            background: #0ff;
        }
        pre {
            background: #111;
            border: 1px solid #0f0;
            padding: 10px;
            overflow-x: auto;
            color: #0f0;
        }
        .error {
            color: #f00;
            text-shadow: 0 0 10px #f00;
        }
        .success {
            color: #0f0;
            text-shadow: 0 0 10px #0f0;
        }
        #live-log {
            max-height: 400px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <h1>🔥 NUCLEAR DEBUG MODE 🔥</h1>
    <p>This page BYPASSES ALL JAVASCRIPT VALIDATION AND DIRECTLY TESTS THE BACKEND</p>

    @php
        $sessionSuccess = Session::get('success');
        $sessionError = Session::get('error');
        $successMessage = session()->get('success_message'); // Our custom session variable
        $allSession = session()->all();
        $flashData = Session::get('_flash');
        \Log::info('Nuclear-debug page loaded');
        \Log::info('Session ID on page load: ' . session()->getId());
        \Log::info('Session success value: ' . ($sessionSuccess ?: 'NULL'));
        \Log::info('Session success_message value: ' . ($successMessage ?: 'NULL'));
        \Log::info('Session error value: ' . ($sessionError ?: 'NULL'));
        \Log::info('All session keys: ' . json_encode(array_keys($allSession)));
        \Log::info('Flash data structure: ' . json_encode($flashData));
        \Log::info('Full session dump: ' . json_encode($allSession));
        
        // Auto-clear the success message after displaying (manual flash behavior)
        if ($successMessage) {
            session()->forget('success_message');
        }
    @endphp

    <div style="background: #333; color: #0ff; padding: 20px; margin: 20px 0; border: 2px solid #0ff;">
        <h3>SESSION DEBUG INFO:</h3>
        <pre>Session ID: {{ session()->getId() }}</pre>
        <pre>Success Flash: {{ $sessionSuccess ?: 'NOT SET' }}</pre>
        <pre>Success Message (custom): {{ $successMessage ?: 'NOT SET' }}</pre>
        <pre>Error Flash: {{ $sessionError ?: 'NOT SET' }}</pre>
        <pre>Session Keys: {{ json_encode(array_keys($allSession)) }}</pre>
        <pre>Flash Data: {{ json_encode($flashData) }}</pre>
        <pre style="font-size: 10px; max-height: 200px; overflow-y: auto;">All Session: {{ json_encode($allSession, JSON_PRETTY_PRINT) }}</pre>
    </div>

    @if ($successMessage)
    <div style="background: #0f0; color: #000; padding: 20px; margin: 20px 0; border: 3px solid #0f0; font-size: 20px; font-weight: bold; text-align: center;">
        ✓ SUCCESS: {{ $successMessage }}
    </div>
    @endif
    
    @if ($sessionSuccess)
    <div style="background: #0f0; color: #000; padding: 20px; margin: 20px 0; border: 3px solid #0f0; font-size: 20px; font-weight: bold; text-align: center;">
        ✓ SUCCESS (FLASH): {{ $sessionSuccess }}
    </div>
    @endif
    
    @if ($sessionError)
    <div style="background: #f00; color: #fff; padding: 20px; margin: 20px 0; border: 3px solid #f00; font-size: 20px; font-weight: bold; text-align: center;">
        ✗ ERROR: {{ $sessionError }}
    </div>
    @endif

    <div class="test-section">
        <h2>Test 1: Plain HTML Form (NO JavaScript)</h2>
        <p>This form has ZERO JavaScript - pure HTML5 submission</p>
        <form action="{{ route('super.admin.settings.save') }}" method="POST" style="border: 2px solid yellow; padding: 10px;">
            @csrf
            <input type="text" name="app_name" value="NuclearTestApp_{{ time() }}" style="font-size: 16px; padding: 5px; width: 300px;">
            <br><br>
            <button type="submit" style="background: yellow; color: black;">SUBMIT WITH ZERO JS</button>
        </form>
    </div>

    <div class="test-section">
        <h2>Test 2: Check What Actually Happens</h2>
        <button onclick="checkEmergencyLog()">Check Emergency Log</button>
        <button onclick="checkLaravelLog()">Check Laravel Log</button>
        <button onclick="checkDatabase()">Check Database Now</button>
        <pre id="check-result">Click a button to check...</pre>
    </div>

    <div class="test-section">
        <h2>Test 3: Direct Fetch POST (Bypass Forms)</h2>
        <button onclick="directPost()">Send Direct POST</button>
        <pre id="fetch-result">Click to test...</pre>
    </div>

    <div class="test-section">
        <h2>Test 4: Raw cURL Command</h2>
        <p>Run this in PowerShell or Command Prompt:</p>
        <pre>curl -X POST "{{ url('/super-admin/settings/save') }}" ^
  -H "Content-Type: application/x-www-form-urlencoded" ^
  -d "_token={{ csrf_token() }}&app_name=CURLTest_{{ time() }}"</pre>
    </div>

    <div class="test-section">
        <h2>Test 5: Current State</h2>
        <button onclick="getCurrentState()">Get Current State</button>
        <pre id="state-result">Click to check...</pre>
    </div>

    <div class="test-section">
        <h2>Live Log</h2>
        <pre id="live-log"></pre>
    </div>

    <script>
        function log(msg) {
            const logEl = document.getElementById('live-log');
            const timestamp = new Date().toLocaleTimeString();
            logEl.textContent += `[${timestamp}] ${msg}\n`;
            logEl.scrollTop = logEl.scrollHeight;
        }

        log('Page loaded');

        async function checkEmergencyLog() {
            try {
                log('Checking emergency log...');
                const response = await fetch('/check-emergency-log');
                const text = await response.text();
                document.getElementById('check-result').textContent = text || 'Log is empty';
            } catch(e) {
                document.getElementById('check-result').textContent = 'ERROR: ' + e.message;
            }
        }

        async function checkLaravelLog() {
            try {
                log('Checking Laravel log...');
                const response = await fetch('/check-laravel-log');
                const text = await response.text();
                document.getElementById('check-result').textContent = text || 'Log is empty';
            } catch(e) {
                document.getElementById('check-result').textContent = 'ERROR: ' + e.message;
            }
        }

        async function checkDatabase() {
            try {
                log('Checking database...');
                const response = await fetch('/check-settings');
                const text = await response.text();
                document.getElementById('check-result').textContent = text;
            } catch(e) {
                document.getElementById('check-result').textContent = 'ERROR: ' + e.message;
            }
        }

        async function directPost() {
            try {
                log('Sending direct POST...');
                const formData = new FormData();
                formData.append('_token', '{{ csrf_token() }}');
                formData.append('app_name', 'DirectPost_' + Date.now());

                const response = await fetch('{{ route("super.admin.settings.save") }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                log('Response status: ' + response.status);
                const text = await response.text();
                log('Response received');
                document.getElementById('fetch-result').textContent = text.substring(0, 500);
            } catch(e) {
                log('ERROR: ' + e.message);
                document.getElementById('fetch-result').textContent = 'ERROR: ' + e.message;
            }
        }

        async function getCurrentState() {
            try {
                log('Getting current state...');
                const response = await fetch('/check-settings');
                const text = await response.text();
                document.getElementById('state-result').textContent = text;
            } catch(e) {
                document.getElementById('state-result').textContent = 'ERROR: ' + e.message;
            }
        }

        // Monitor form submissions
        document.addEventListener('submit', function(e) {
            log('Form submitted! Target: ' + e.target.action);
        });

        log('All functions loaded');
    </script>
</body>
</html>
