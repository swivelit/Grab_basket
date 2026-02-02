@extends('delivery-partner.layouts.app')

@section('title', 'Login Diagnostics')

@section('content')
<div class="delivery-card">
    <div class="delivery-header">
        <h1 class="delivery-title">🔧 Login Diagnostics</h1>
        <p class="delivery-subtitle">Real-time performance monitoring</p>
    </div>

    <div class="delivery-body">
        <div id="diagnostics-results">
            <div class="alert alert-info">
                <i class="fas fa-cog fa-spin me-2"></i>Running diagnostics...
            </div>
        </div>
        
        <button onclick="runDiagnostics()" class="btn btn-primary mb-3">
            <i class="fas fa-play me-2"></i>Run Diagnostics Again
        </button>
        
        <a href="{{ route('delivery-partner.login') }}" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left me-2"></i>Back to Login
        </a>
    </div>
</div>

<script>
// Auto-run diagnostics on page load
document.addEventListener('DOMContentLoaded', function() {
    runDiagnostics();
});

async function runDiagnostics() {
    const resultsDiv = document.getElementById('diagnostics-results');
    resultsDiv.innerHTML = '<div class="alert alert-info"><i class="fas fa-cog fa-spin me-2"></i>Running diagnostics...</div>';
    
    const tests = [];
    
    // Test 1: Network connectivity
    const networkStart = performance.now();
    try {
        const response = await fetch('/api/health-check', { method: 'GET' });
        const networkTime = performance.now() - networkStart;
        tests.push({
            name: 'Network Connectivity',
            time: networkTime,
            status: response.ok ? 'success' : 'warning',
            details: `HTTP ${response.status} - ${networkTime.toFixed(2)}ms`
        });
    } catch (error) {
        tests.push({
            name: 'Network Connectivity',
            time: 0,
            status: 'danger',
            details: 'Network error: ' + error.message
        });
    }
    
    // Test 2: Form submission simulation
    const formStart = performance.now();
    try {
        const formData = new FormData();
        formData.append('_token', document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '');
        formData.append('login', 'test@example.com');
        formData.append('password', 'testpassword');
        
        const response = await fetch('{{ route("delivery-partner.login.post") }}', {
            method: 'POST',
            body: formData,
            redirect: 'manual' // Don't follow redirects
        });
        
        const formTime = performance.now() - formStart;
        tests.push({
            name: 'Login Form Response',
            time: formTime,
            status: response.status < 400 ? 'success' : 'warning',
            details: `HTTP ${response.status} - ${formTime.toFixed(2)}ms`
        });
    } catch (error) {
        tests.push({
            name: 'Login Form Response',
            time: 0,
            status: 'danger',
            details: 'Form error: ' + error.message
        });
    }
    
    // Test 3: JavaScript performance
    const jsStart = performance.now();
    for (let i = 0; i < 1000; i++) {
        const div = document.createElement('div');
        div.innerHTML = 'test';
    }
    const jsTime = performance.now() - jsStart;
    tests.push({
        name: 'Client Performance',
        time: jsTime,
        status: jsTime < 10 ? 'success' : 'warning',
        details: `DOM operations: ${jsTime.toFixed(2)}ms`
    });
    
    // Test 4: Browser capabilities
    const capabilities = {
        'Local Storage': typeof Storage !== 'undefined',
        'Fetch API': typeof fetch !== 'undefined',
        'WebP Support': await supportsWebP(),
        'Service Worker': 'serviceWorker' in navigator
    };
    
    tests.push({
        name: 'Browser Capabilities',
        time: 0,
        status: 'info',
        details: Object.entries(capabilities).map(([key, value]) => 
            `${key}: ${value ? '✅' : '❌'}`).join(', ')
    });
    
    // Display results
    displayResults(tests);
}

async function supportsWebP() {
    return new Promise(resolve => {
        const webP = new Image();
        webP.onload = webP.onerror = () => resolve(webP.height === 2);
        webP.src = 'data:image/webp;base64,UklGRjoAAABXRUJQVlA4IC4AAACyAgCdASoCAAIALmk0mk0iIiIiIgBoSygABc6WWgAA/veff/0PP8bA//LwYAAA';
    });
}

function displayResults(tests) {
    const resultsDiv = document.getElementById('diagnostics-results');
    let html = '<h5>🔍 Diagnostic Results</h5>';
    
    tests.forEach(test => {
        const badgeClass = {
            'success': 'bg-success',
            'warning': 'bg-warning',
            'danger': 'bg-danger',
            'info': 'bg-info'
        }[test.status] || 'bg-secondary';
        
        html += `
            <div class="card mb-2">
                <div class="card-body py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <strong>${test.name}</strong>
                        <span class="badge ${badgeClass}">${test.time > 0 ? test.time.toFixed(2) + 'ms' : 'N/A'}</span>
                    </div>
                    <small class="text-muted">${test.details}</small>
                </div>
            </div>
        `;
    });
    
    // Performance summary
    const totalTime = tests.reduce((sum, test) => sum + (test.time || 0), 0);
    const slowTests = tests.filter(test => test.time > 1000).length;
    
    html += `
        <div class="alert ${slowTests > 0 ? 'alert-warning' : 'alert-success'} mt-3">
            <h6>📊 Performance Summary</h6>
            <p class="mb-1"><strong>Total measured time:</strong> ${totalTime.toFixed(2)}ms</p>
            <p class="mb-1"><strong>Slow operations:</strong> ${slowTests}</p>
            <p class="mb-0"><strong>Status:</strong> ${slowTests > 0 ? '⚠️ Some operations are slow' : '✅ Performance looks good'}</p>
        </div>
        
        <div class="alert alert-info mt-3">
            <h6>💡 Troubleshooting Tips</h6>
            <ul class="mb-0">
                <li>If Network/Form tests are slow (>2000ms): Check internet connection</li>
                <li>If Client Performance is slow (>50ms): Try clearing browser cache</li>
                <li>If login still loads forever: Try different browser or device</li>
                <li>Database connection is currently ~228ms due to remote cloud database</li>
            </ul>
        </div>
    `;
    
    resultsDiv.innerHTML = html;
}
</script>
@endsection