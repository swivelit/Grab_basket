<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Internship Details</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body {
    background: linear-gradient(135deg, #1d2671, #c33764);
    min-height: 100vh;
    padding: 30px;
    font-family: 'Segoe UI', sans-serif;
}

.glass-card {
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(14px);
    border-radius: 20px;
    padding: 25px;
    box-shadow: 0 20px 45px rgba(0,0,0,0.25);
    color: #fff;
}

.title {
    font-weight: 800;
    letter-spacing: 1px;
}

.info-row {
    display: flex;
    justify-content: space-between;
    border-bottom: 1px dashed rgba(255,255,255,0.3);
    padding: 10px 0;
}

.info-row:last-child {
    border-bottom: none;
}

.label {
    font-weight: 600;
    opacity: 0.85;
}

.value {
    font-weight: 700;
}

.badge-status {
    padding: 6px 14px;
    border-radius: 30px;
    font-size: 13px;
    font-weight: 700;
}

.status-paid {
    background: #2ecc71;
    color: #fff;
}

.status-pending {
    background: #f39c12;
    color: #fff;
}
</style>
</head>

<body>

<div class="container">
    <div class="row justify-content-center">

        <div class="col-lg-7 col-md-9">

            @if(session('success'))
                <div class="alert alert-success text-center">
                    {{ session('success') }}
                </div>
            @endif

            @foreach($intern as $item)

            <div class="glass-card mb-4">
                <h4 class="text-center title mb-4">🎓 Internship Application</h4>

                <div class="info-row">
                    <span class="label">Student Name</span>
                    <span class="value">{{ $item->name }}</span>
                </div>

                <div class="info-row">
                    <span class="label">Email</span>
                    <span class="value">{{ $item->email }}</span>
                </div>

                <div class="info-row">
                    <span class="label">Phone</span>
                    <span class="value">{{ $item->phone }}</span>
                </div>

                <div class="info-row">
                    <span class="label">Course</span>
                    <span class="value">{{ $item->course_name }}</span>
                </div>

                <div class="info-row">
                    <span class="label">Domain</span>
                    <span class="value">{{ $item->domain }}</span>
                </div>

                <div class="info-row">
                    <span class="label">Duration</span>
                    <span class="value">{{ $item->weeks }} Weeks</span>
                </div>

                <div class="info-row">
                    <span class="label">Fee Paid</span>
                    <span class="value">₹{{ number_format($item->fee,2) }}</span>
                </div>

                <div class="info-row">
                    <span class="label">Payment ID</span>
                    <span class="value">{{ $item->payment_id ?? '—' }}</span>
                </div>

                <div class="info-row align-items-center">
                    <span class="label">Status</span>
                    <span class="badge-status {{ $item->status === 'paid' ? 'status-paid' : 'status-pending' }}">
                        {{ strtoupper($item->status) }}
                    </span>
                </div>
            </div>

            @endforeach

        </div>
    </div>
</div>

</body>
</html>
