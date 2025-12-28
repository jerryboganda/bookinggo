<!DOCTYPE html>
<html>
<head>
    <title>AGGRESSIVE DEBUG - Settings Save</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body { font-family: monospace; padding: 20px; background: #000; color: #0f0; }
        .section { background: #111; padding: 15px; margin: 10px 0; border: 1px solid #0f0; }
        .error { color: #f00; }
        .success { color: #0f0; }
        .warning { color: #ff0; }
        button { padding: 10px 20px; background: #0f0; color: #000; border: none; cursor: pointer; margin: 5px; font-weight: bold; }
        #log { background: #000; color: #0f0; padding: 10px; height: 300px; overflow-y: auto; border: 1px solid #0f0; }
        .log-entry { margin: 5px 0; }
    </style>
</head>
<body>

<h1>🔥 AGGRESSIVE SETTINGS DEBUG 🔥</h1>

<div class="section">
    <h2>1. TEST DIRECT DATABASE WRITE</h2>
    <button onclick="testDatabaseWrite()">Test DB Write</button>
    <div id="db-result"></div>
</div>

<div class="section">
    <h2>2. TEST AJAX POST TO CONTROLLER</h2>
    <button onclick="testAjaxPost()">Test AJAX</button>
    <div id="ajax-result"></div>
</div>

<div class="section">
    <h2>3. TEST FORM SUBMISSION (Watch Network Tab!)</h2>
    <form id="test-form" method="POST" action="{{ route('super.admin.settings.save') }}">
        @csrf
        <input type="hidden" name="title_text" value="TEST_{{ time() }}">
        <input type="hidden" name="footer_text" value="TEST_FOOTER">
        <input type="hidden" name="color" value="theme-1">
        <button type="submit">Submit Form</button>
    </form>
    <div id="form-result"></div>
</div>

<div class="section">
    <h2>4. CHECK CURRENT SETTINGS</h2>
    <button onclick="checkSettings()">Check Settings</button>
    <div id="settings-result"></div>
</div>

<div class="section">
    <h2>5. LIVE LOG</h2>
    <div id="log"></div>
</div>

<script>
function log(msg, type = 'info') {
    const logDiv = document.getElementById('log');
    const entry = document.createElement('div');
    entry.className = 'log-entry ' + type;
    entry.textContent = new Date().toISOString() + ' - ' + msg;
    logDiv.appendChild(entry);
    logDiv.scrollTop = logDiv.scrollHeight;
}

// Intercept all network requests
const originalFetch = window.fetch;
window.fetch = function(...args) {
    log('FETCH: ' + args[0], 'warning');
    return originalFetch.apply(this, args);
};

const originalXHR = window.XMLHttpRequest;
window.XMLHttpRequest = function() {
    const xhr = new originalXHR();
    const originalOpen = xhr.open;
    xhr.open = function(method, url) {
        log('XHR: ' + method + ' ' + url, 'warning');
        return originalOpen.apply(this, arguments);
    };
    return xhr;
};

// Intercept form submit
document.getElementById('test-form').addEventListener('submit', function(e) {
    log('FORM SUBMIT TRIGGERED!', 'success');
    log('Action: ' + this.action, 'info');
    log('Method: ' + this.method, 'info');
});

async function testDatabaseWrite() {
    log('Testing direct database write...', 'info');
    const result = document.getElementById('db-result');
    
    try {
        const response = await fetch('/test-db-write', {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const data = await response.text();
        result.innerHTML = '<pre class="success">' + data + '</pre>';
        log('DB Write test complete', 'success');
    } catch (error) {
        result.innerHTML = '<pre class="error">' + error + '</pre>';
        log('DB Write test FAILED: ' + error, 'error');
    }
}

async function testAjaxPost() {
    log('Testing AJAX POST to controller...', 'info');
    const result = document.getElementById('ajax-result');
    
    try {
        const formData = new FormData();
        formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
        formData.append('title_text', 'AJAX_TEST_' + Date.now());
        formData.append('footer_text', 'AJAX_FOOTER');
        formData.append('color', 'theme-2');
        
        log('Sending AJAX to {{ route("super.admin.settings.save") }}', 'info');
        
        const response = await fetch('{{ route("super.admin.settings.save") }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        log('Response status: ' + response.status, 'info');
        const text = await response.text();
        log('Response received', 'success');
        
        result.innerHTML = '<pre class="success">Status: ' + response.status + '\n' + text.substring(0, 500) + '</pre>';
    } catch (error) {
        result.innerHTML = '<pre class="error">' + error + '</pre>';
        log('AJAX test FAILED: ' + error, 'error');
    }
}

async function checkSettings() {
    log('Checking current settings from database...', 'info');
    const result = document.getElementById('settings-result');
    
    try {
        const response = await fetch('/check-settings', {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const data = await response.text();
        result.innerHTML = '<pre>' + data + '</pre>';
        log('Settings check complete', 'success');
    } catch (error) {
        result.innerHTML = '<pre class="error">' + error + '</pre>';
        log('Settings check FAILED: ' + error, 'error');
    }
}

log('Debug page loaded', 'success');
log('User Agent: ' + navigator.userAgent, 'info');
log('CSRF Token: ' + document.querySelector('meta[name="csrf-token"]').content, 'info');
</script>

</body>
</html>
