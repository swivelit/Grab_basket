<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Your Package - GRAB BASKETS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .tracking-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 2rem;
            margin: 2rem auto;
            max-width: 800px;
        }
        .tracking-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .tracking-header h1 {
            color: #2c3e50;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        .tracking-header p {
            color: #7f8c8d;
            font-size: 1.1rem;
        }
        .tracking-form {
            background: #f8f9fa;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
        }
        .form-label {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }
        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-track {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-track:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
            color: white;
        }
        .courier-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }
        .courier-card {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 1rem;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .courier-card:hover {
            border-color: #667eea;
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .courier-card.selected {
            border-color: #667eea;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .courier-icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        .quick-track {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 2rem;
        }
        .loading {
            display: none;
            text-align: center;
            padding: 2rem;
        }
        .spinner-border {
            color: #667eea;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="tracking-container">
            <!-- Header -->
            <div class="tracking-header">
                <h1><i class="fas fa-shipping-fast"></i> Track Your Package</h1>
                <p>Enter your tracking number to get real-time updates on your shipment</p>
            </div>

            <!-- Quick Track for Orders -->
            @auth
            <div class="quick-track">
                <h5><i class="fas fa-bolt"></i> Quick Track Your Orders</h5>
                <p class="mb-2">Track orders directly from your account:</p>
                <a href="{{ route('orders.track') }}" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-list"></i> View My Orders
                </a>
            </div>
            @endauth

            <!-- Tracking Form -->
            <div class="tracking-form">
                <form id="trackingForm" method="POST" action="{{ route('tracking.track') }}">
                    @csrf
                    <div class="row">
                        <div class="col-md-8">
                            <label for="tracking_number" class="form-label">
                                <i class="fas fa-barcode"></i> Tracking Number
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   id="tracking_number" 
                                   name="tracking_number" 
                                   placeholder="Enter your tracking number (e.g., DH1234567890)" 
                                   required>
                            <small class="form-text text-muted">
                                Enter the tracking number provided by your courier service
                            </small>
                        </div>
                        <div class="col-md-4">
                            <label for="courier" class="form-label">
                                <i class="fas fa-truck"></i> Courier (Optional)
                            </label>
                            <select class="form-control" id="courier" name="courier">
                                <option value="">Auto-detect</option>
                                @foreach($supportedCouriers as $key => $name)
                                    <option value="{{ $key }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    
                    <div class="text-center mt-3">
                        <button type="submit" class="btn btn-track">
                            <i class="fas fa-search"></i> Track Package
                        </button>
                    </div>
                </form>
            </div>

            <!-- Loading -->
            <div class="loading" id="loading">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Tracking your package...</p>
            </div>

            <!-- Supported Couriers -->
            <div class="mt-4">
                <h5 class="text-center mb-3">Supported Couriers</h5>
                <div class="courier-grid">
                    <div class="courier-card">
                        <div class="courier-icon">🚚</div>
                        <h6>Delhivery</h6>
                        <small>DH/DL + 10-15 digits</small>
                    </div>
                    <div class="courier-card">
                        <div class="courier-icon">📦</div>
                        <h6>Blue Dart</h6>
                        <small>BD/A + 8-12 digits</small>
                    </div>
                    <div class="courier-card">
                        <div class="courier-icon">🛻</div>
                        <h6>DTDC</h6>
                        <small>D + 9-12 digits</small>
                    </div>
                    <div class="courier-card">
                        <div class="courier-icon">✈️</div>
                        <h6>FedEx</h6>
                        <small>12-14 digits</small>
                    </div>
                    <div class="courier-card">
                        <div class="courier-icon">📮</div>
                        <h6>India Post</h6>
                        <small>2 letters + 9 digits + IN</small>
                    </div>
                    <div class="courier-card">
                        <div class="courier-icon">🚛</div>
                        <h6>Auto-Detect</h6>
                        <small>We'll identify your courier</small>
                    </div>
                </div>
            </div>

            <!-- Sample Tracking Numbers -->
            <div class="mt-4 text-center">
                <h6>Try Sample Tracking Numbers:</h6>
                <div class="d-flex flex-wrap justify-content-center gap-2">
                    <button class="btn btn-outline-secondary btn-sm sample-tracking" data-number="DH1234567890">
                        DH1234567890 (Delhivery)
                    </button>
                    <button class="btn btn-outline-secondary btn-sm sample-tracking" data-number="BD12345678">
                        BD12345678 (Blue Dart)
                    </button>
                    <button class="btn btn-outline-secondary btn-sm sample-tracking" data-number="D123456789">
                        D123456789 (DTDC)
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-detect courier on input
        document.getElementById('tracking_number').addEventListener('input', function() {
            const trackingNumber = this.value;
            if (trackingNumber.length > 5) {
                // Auto-detect courier
                fetch(`/tracking/detect/${encodeURIComponent(trackingNumber)}`, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    // Update courier selection
                    const courierSelect = document.getElementById('courier');
                    if (data.courier && data.courier !== 'Unknown Courier') {
                        const courierKey = data.courier.toLowerCase().replace(' ', '_');
                        const option = courierSelect.querySelector(`option[value="${courierKey}"]`);
                        if (option) {
                            courierSelect.value = courierKey;
                        }
                    }
                })
                .catch(error => console.log('Courier detection failed'));
            }
        });

        // Sample tracking numbers
        document.querySelectorAll('.sample-tracking').forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('tracking_number').value = this.dataset.number;
                document.getElementById('tracking_number').dispatchEvent(new Event('input'));
            });
        });

        // Form submission
        document.getElementById('trackingForm').addEventListener('submit', function() {
            document.getElementById('loading').style.display = 'block';
        });
    </script>
</body>
</html>