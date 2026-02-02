@extends('warehouse.layouts.app')

@section('title', 'Barcode Scanner')

@section('breadcrumb')
<li class="breadcrumb-item active">Barcode Scanner</li>
@endsection

@push('styles')
<style>
    #scanner-container {
        position: relative;
        width: 100%;
        max-width: 500px;
        margin: 0 auto;
    }
    
    #scanner {
        width: 100%;
        height: 300px;
        border: 3px solid #667eea;
        border-radius: 12px;
        overflow: hidden;
        position: relative;
    }
    
    .scanner-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10;
    }
    
    .scanner-viewfinder {
        width: 200px;
        height: 200px;
        border: 2px solid #fff;
        border-radius: 8px;
        position: relative;
        box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.5);
    }
    
    .scanner-line {
        position: absolute;
        width: 100%;
        height: 2px;
        background: #667eea;
        animation: scan 2s linear infinite;
    }
    
    @keyframes scan {
        0% { top: 0; }
        100% { top: 196px; }
    }
    
    .manual-input {
        background: #f8f9fa;
        border-radius: 12px;
        padding: 1.5rem;
        margin-top: 1rem;
    }
    
    .product-result {
        background: white;
        border: 2px solid #28a745;
        border-radius: 12px;
        padding: 1.5rem;
        margin-top: 1rem;
        animation: fadeIn 0.5s ease;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .scan-result-success {
        background: #d4edda;
        border-color: #28a745;
        color: #155724;
    }
    
    .scan-result-error {
        background: #f8d7da;
        border-color: #dc3545;
        color: #721c24;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <!-- Scanner Header -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-gradient-primary text-white">
                    <h4 class="mb-0">
                        <i class="bi bi-qr-code-scan me-2"></i>
                        Barcode Scanner
                    </h4>
                    <p class="mb-0 opacity-75">Scan product barcodes for quick inventory lookup</p>
                </div>
                <div class="card-body text-center">
                    <!-- Camera Scanner -->
                    <div id="scanner-container">
                        <div id="scanner">
                            <video id="scanner-video" style="width: 100%; height: 100%; object-fit: cover;"></video>
                            <div class="scanner-overlay" id="scanner-overlay">
                                <div class="scanner-viewfinder">
                                    <div class="scanner-line"></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <button id="start-scanner" class="btn btn-success me-2">
                                <i class="bi bi-camera me-2"></i>Start Camera
                            </button>
                            <button id="stop-scanner" class="btn btn-danger me-2" disabled>
                                <i class="bi bi-camera-video-off me-2"></i>Stop Camera
                            </button>
                            <button id="switch-camera" class="btn btn-outline-primary" disabled>
                                <i class="bi bi-arrow-repeat me-2"></i>Switch Camera
                            </button>
                        </div>
                    </div>

                    <!-- Manual Input Alternative -->
                    <div class="manual-input mt-4">
                        <h6 class="mb-3">
                            <i class="bi bi-keyboard me-2"></i>
                            Manual Barcode Entry
                        </h6>
                        <div class="row">
                            <div class="col-md-8">
                                <input type="text" 
                                       id="manual-barcode" 
                                       class="form-control" 
                                       placeholder="Enter barcode manually" 
                                       maxlength="50">
                            </div>
                            <div class="col-md-4">
                                <button id="manual-search" class="btn btn-primary w-100">
                                    <i class="bi bi-search me-2"></i>Search
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Scan Results -->
            <div id="scan-results" style="display: none;"></div>

            <!-- Quick Actions -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0">
                        <i class="bi bi-lightning me-2"></i>
                        Quick Actions
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <button class="btn btn-outline-primary w-100 mb-2" onclick="scanMode='lookup'">
                                <i class="bi bi-search me-2"></i>
                                Product Lookup
                            </button>
                        </div>
                        <div class="col-md-4">
                            <button class="btn btn-outline-success w-100 mb-2" onclick="scanMode='add-stock'">
                                <i class="bi bi-plus-circle me-2"></i>
                                Add Stock
                            </button>
                        </div>
                        <div class="col-md-4">
                            <button class="btn btn-outline-warning w-100 mb-2" onclick="scanMode='adjust-stock'">
                                <i class="bi bi-arrow-repeat me-2"></i>
                                Adjust Stock
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Scans -->
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">
                        <i class="bi bi-clock-history me-2"></i>
                        Recent Scans
                    </h6>
                    <button id="clear-history" class="btn btn-sm btn-outline-danger">
                        <i class="bi bi-trash me-1"></i>Clear
                    </button>
                </div>
                <div class="card-body">
                    <div id="scan-history">
                        <p class="text-muted text-center">No scans yet</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Stock Action Modal -->
<div class="modal fade" id="stockActionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-boxes me-2"></i>
                    Stock Action
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="modal-product-info"></div>
                <div id="modal-stock-form"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/quagga@0.12.1/dist/quagga.min.js"></script>
<script>
let scannerActive = false;
let scanMode = 'lookup'; // lookup, add-stock, adjust-stock
let currentStream = null;
let scanHistory = JSON.parse(localStorage.getItem('warehouseScanHistory') || '[]');

document.addEventListener('DOMContentLoaded', function() {
    updateScanHistory();
    
    // Scanner controls
    document.getElementById('start-scanner').addEventListener('click', startScanner);
    document.getElementById('stop-scanner').addEventListener('click', stopScanner);
    document.getElementById('switch-camera').addEventListener('click', switchCamera);
    document.getElementById('manual-search').addEventListener('click', manualSearch);
    document.getElementById('clear-history').addEventListener('click', clearHistory);
    
    // Manual barcode input
    document.getElementById('manual-barcode').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            manualSearch();
        }
    });
});

async function startScanner() {
    try {
        const video = document.getElementById('scanner-video');
        const constraints = {
            video: {
                facingMode: 'environment', // Use back camera
                width: { ideal: 640 },
                height: { ideal: 480 }
            }
        };

        currentStream = await navigator.mediaDevices.getUserMedia(constraints);
        video.srcObject = currentStream;
        video.play();

        // Initialize QuaggaJS
        Quagga.init({
            inputStream: {
                name: "Live",
                type: "LiveStream",
                target: video,
                constraints: {
                    width: 640,
                    height: 480,
                    facingMode: "environment"
                }
            },
            decoder: {
                readers: [
                    "code_128_reader",
                    "ean_reader",
                    "ean_8_reader",
                    "code_39_reader",
                    "code_39_vin_reader",
                    "codabar_reader",
                    "upc_reader",
                    "upc_e_reader"
                ]
            },
            locate: true,
            locator: {
                patchSize: "medium",
                halfSample: true
            }
        }, function(err) {
            if (err) {
                console.error('QuaggaJS initialization failed:', err);
                showError('Camera initialization failed: ' + err.message);
                return;
            }
            
            console.log('QuaggaJS initialized successfully');
            Quagga.start();
            scannerActive = true;
            
            document.getElementById('start-scanner').disabled = true;
            document.getElementById('stop-scanner').disabled = false;
            document.getElementById('switch-camera').disabled = false;
            document.getElementById('scanner-overlay').style.display = 'flex';
        });

        // Handle detected barcodes
        Quagga.onDetected(function(result) {
            const barcode = result.codeResult.code;
            console.log('Barcode detected:', barcode);
            
            // Add beep sound (optional)
            playBeep();
            
            // Process the barcode
            processBarcode(barcode);
            
            // Brief pause to avoid multiple scans
            setTimeout(() => {
                if (scannerActive) {
                    Quagga.start();
                }
            }, 1000);
        });

    } catch (error) {
        console.error('Error starting scanner:', error);
        showError('Could not access camera: ' + error.message);
    }
}

function stopScanner() {
    if (scannerActive) {
        Quagga.stop();
        scannerActive = false;
    }
    
    if (currentStream) {
        currentStream.getTracks().forEach(track => track.stop());
        currentStream = null;
    }
    
    document.getElementById('start-scanner').disabled = false;
    document.getElementById('stop-scanner').disabled = true;
    document.getElementById('switch-camera').disabled = true;
    document.getElementById('scanner-overlay').style.display = 'none';
}

async function switchCamera() {
    stopScanner();
    // Switch between front and back camera
    // Implementation would depend on device capabilities
    setTimeout(startScanner, 500);
}

function manualSearch() {
    const barcode = document.getElementById('manual-barcode').value.trim();
    if (barcode) {
        processBarcode(barcode);
        document.getElementById('manual-barcode').value = '';
    }
}

async function processBarcode(barcode) {
    try {
        showLoading('Searching for product...');
        
        // Add to scan history
        addToScanHistory(barcode);
        
        // Search for product
        const response = await fetch(`/warehouse/search?q=${encodeURIComponent(barcode)}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });
        
        const data = await response.json();
        
        if (data.success && data.results && data.results.length > 0) {
            const product = data.results[0];
            showProductResult(product, barcode);
        } else {
            showError(`No product found for barcode: ${barcode}`);
        }
        
    } catch (error) {
        console.error('Error processing barcode:', error);
        showError('Error searching for product. Please try again.');
    }
}

function showProductResult(product, barcode) {
    const resultHtml = `
        <div class="product-result">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h5 class="mb-1">${product.name}</h5>
                    <p class="mb-1"><strong>SKU:</strong> ${product.sku}</p>
                    <p class="mb-1"><strong>Location:</strong> ${product.location || 'Not assigned'}</p>
                    <p class="mb-0">
                        <strong>Current Stock:</strong> 
                        <span class="badge ${product.current_stock > 0 ? 'bg-success' : 'bg-danger'}">
                            ${product.current_stock}
                        </span>
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <button class="btn btn-primary btn-sm mb-1 w-100" onclick="viewProduct(${product.id})">
                        <i class="bi bi-eye me-1"></i>View Details
                    </button>
                    ${scanMode === 'add-stock' ? `
                        <button class="btn btn-success btn-sm mb-1 w-100" onclick="showStockModal(${product.id}, 'add')">
                            <i class="bi bi-plus-circle me-1"></i>Add Stock
                        </button>
                    ` : ''}
                    ${scanMode === 'adjust-stock' ? `
                        <button class="btn btn-warning btn-sm mb-1 w-100" onclick="showStockModal(${product.id}, 'adjust')">
                            <i class="bi bi-arrow-repeat me-1"></i>Adjust Stock
                        </button>
                    ` : ''}
                </div>
            </div>
        </div>
    `;
    
    document.getElementById('scan-results').innerHTML = resultHtml;
    document.getElementById('scan-results').style.display = 'block';
}

function showError(message) {
    const errorHtml = `
        <div class="alert alert-danger scan-result-error" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>
            ${message}
        </div>
    `;
    
    document.getElementById('scan-results').innerHTML = errorHtml;
    document.getElementById('scan-results').style.display = 'block';
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        document.getElementById('scan-results').style.display = 'none';
    }, 5000);
}

function showLoading(message) {
    const loadingHtml = `
        <div class="alert alert-info" role="alert">
            <div class="d-flex align-items-center">
                <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                ${message}
            </div>
        </div>
    `;
    
    document.getElementById('scan-results').innerHTML = loadingHtml;
    document.getElementById('scan-results').style.display = 'block';
}

function addToScanHistory(barcode) {
    const scan = {
        barcode: barcode,
        timestamp: new Date().toISOString(),
        mode: scanMode
    };
    
    scanHistory.unshift(scan);
    if (scanHistory.length > 20) {
        scanHistory = scanHistory.slice(0, 20);
    }
    
    localStorage.setItem('warehouseScanHistory', JSON.stringify(scanHistory));
    updateScanHistory();
}

function updateScanHistory() {
    const historyContainer = document.getElementById('scan-history');
    
    if (scanHistory.length === 0) {
        historyContainer.innerHTML = '<p class="text-muted text-center">No scans yet</p>';
        return;
    }
    
    const historyHtml = scanHistory.map(scan => `
        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
            <div>
                <code>${scan.barcode}</code>
                <small class="text-muted d-block">${new Date(scan.timestamp).toLocaleString()}</small>
            </div>
            <span class="badge bg-secondary">${scan.mode}</span>
        </div>
    `).join('');
    
    historyContainer.innerHTML = historyHtml;
}

function clearHistory() {
    scanHistory = [];
    localStorage.removeItem('warehouseScanHistory');
    updateScanHistory();
}

function playBeep() {
    // Create a simple beep sound
    const audioContext = new (window.AudioContext || window.webkitAudioContext)();
    const oscillator = audioContext.createOscillator();
    const gain = audioContext.createGain();
    
    oscillator.connect(gain);
    gain.connect(audioContext.destination);
    
    oscillator.frequency.setValueAtTime(800, audioContext.currentTime);
    gain.gain.setValueAtTime(0.1, audioContext.currentTime);
    
    oscillator.start();
    oscillator.stop(audioContext.currentTime + 0.1);
}

function viewProduct(productId) {
    window.location.href = `/warehouse/inventory/${productId}`;
}

function showStockModal(productId, action) {
    // Implementation for stock modal
    const modal = new bootstrap.Modal(document.getElementById('stockActionModal'));
    modal.show();
    
    // Load product details and show appropriate form
    // This would be implemented based on your stock management requirements
}
</script>
@endpush
