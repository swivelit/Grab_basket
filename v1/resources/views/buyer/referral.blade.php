<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Refer & Earn - GrabBasket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-orange: #ff6b00;
            --primary-orange-light: #ff8c33;
            --bg-neutral: #f8f9fa;
        }

        body {
            background-color: var(--bg-neutral);
            font-family: 'Outfit', sans-serif;
            color: #2d3436;
            min-height: 100vh;
        }

        .navbar {
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 15px 0;
        }

        .navbar-brand {
            font-weight: 700;
            color: var(--primary-orange);
            font-size: 1.5rem;
            display: flex;
            align-items: center;
        }

        .navbar-brand img {
            height: 35px;
            margin-right: 10px;
        }

        .referral-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .hero-section {
            background: linear-gradient(135deg, var(--primary-orange) 0%, #ff4d00 100%);
            border-radius: 24px;
            padding: 40px;
            color: white;
            text-align: center;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(255, 107, 0, 0.2);
            margin-bottom: 30px;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 150px;
            height: 150px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        .hero-icon {
            font-size: 4rem;
            margin-bottom: 20px;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-10px);
            }
        }

        .referral-code-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            text-align: center;
            margin-top: -60px;
            position: relative;
            z-index: 2;
        }

        .referral-label {
            font-size: 0.9rem;
            color: #636e72;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .code-display {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--primary-orange);
            letter-spacing: 4px;
            background: #fff5eb;
            padding: 15px 30px;
            border-radius: 12px;
            display: inline-block;
            margin-bottom: 20px;
            border: 2px dashed var(--primary-orange);
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
        }

        .btn-action {
            padding: 12px 25px;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-copy {
            background: white;
            color: var(--primary-orange);
            border: 2px solid var(--primary-orange);
        }

        .btn-copy:hover {
            background: var(--primary-orange);
            color: white;
        }

        .btn-share {
            background: var(--primary-orange);
            color: white;
            border: none;
        }

        .btn-share:hover {
            background: #e66000;
            transform: scale(1.05);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
            border: 1px solid #f1f2f6;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-orange);
        }

        .stat-name {
            color: #636e72;
            font-size: 0.9rem;
        }

        .how-it-works {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-top: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
        }

        .section-title {
            font-weight: 700;
            margin-bottom: 20px;
            font-size: 1.25rem;
        }

        .step-item {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }

        .step-number {
            width: 32px;
            height: 32px;
            background: #fff5eb;
            color: var(--primary-orange);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            flex-shrink: 0;
        }

        .redeem-section {
            background: #fff5eb;
            border-radius: 20px;
            padding: 30px;
            margin-top: 30px;
            border: 1px solid #ffe8d1;
        }

        .redeem-form {
            display: flex;
            gap: 10px;
        }

        .redeem-input {
            flex: 1;
            padding: 12px 20px;
            border-radius: 12px;
            border: 2px solid #ffe8d1;
            outline: none;
            font-weight: 600;
            text-transform: uppercase;
        }

        .redeem-input:focus {
            border-color: var(--primary-orange);
        }

        .btn-redeem {
            background: var(--primary-orange);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 12px;
            font-weight: 600;
        }

        .social-share {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 30px;
        }

        .social-icon {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            transition: all 0.3s;
            text-decoration: none;
        }

        .social-icon:hover {
            transform: translateY(-5px);
            color: white;
        }

        .whatsapp {
            background-color: #25D366;
        }

        .facebook {
            background-color: #1877F2;
        }

        .twitter {
            background-color: #1DA1F2;
        }

        @media (max-width: 576px) {
            .referral-container {
                padding: 0 15px;
            }

            .hero-section {
                padding: 30px 20px;
            }

            .code-display {
                font-size: 1.8rem;
                padding: 10px 20px;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn-action {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>

<body>
    <nav class="navbar">
        <div class="container d-flex justify-content-between">
            <a class="navbar-brand" href="{{ route('home') }}">
                <img src="{{ asset('asset/images/grabbasket.png') }}" alt="Logo">
                GrabBasket
            </a>
            <a href="{{ route('home') }}" class="btn btn-outline-secondary btn-sm rounded-pill">
                <i class="fas fa-home"></i> Home
            </a>
        </div>
    </nav>

    <div class="referral-container">
        <div class="hero-section">
            <div class="hero-icon">🎁</div>
            <h1>Refer & Get Rewards!</h1>
            <p>Earn 300 points for every friend you invite to GrabBasket</p>
        </div>

        <div class="referral-code-card">
            <div class="referral-label">Share Your Referral Code</div>
            <div class="code-display" id="referralCode">{{ auth()->user()->referral_code }}</div>

            <div class="action-buttons">
                <button class="btn-action btn-copy" onclick="copyCode()">
                    <i class="far fa-copy"></i> Copy Code
                </button>
                <button class="btn-action btn-share" onclick="shareCode()">
                    <i class="fas fa-share-alt"></i> Share Now
                </button>
            </div>

            <div class="social-share">
                <a href="javascript:void(0)" onclick="shareWhatsApp()" class="social-icon whatsapp">
                    <i class="fab fa-whatsapp"></i>
                </a>
                <a href="javascript:void(0)" onclick="shareFacebook()" class="social-icon facebook">
                    <i class="fab fa-facebook-f"></i>
                </a>
                <a href="javascript:void(0)" onclick="shareTwitter()" class="social-icon twitter">
                    <i class="fab fa-twitter"></i>
                </a>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value text-success">{{ auth()->user()->referrals()->count() }}</div>
                <div class="stat-name">Successful Referrals</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">{{ auth()->user()->wallet_point }}</div>
                <div class="stat-name">Current Wallet Points</div>
            </div>
        </div>

        @if(!auth()->user()->referrer_id)
            <div class="redeem-section">
                <h5 class="section-title"><i class="fas fa-ticket-alt me-2"></i>Have a friend's code?</h5>
                <p class="text-muted small">Enter it here to link your account and help your friend earn rewards!</p>
                <div class="redeem-form">
                    <input type="text" id="applyCodeInput" class="redeem-input" placeholder="ENTER CODE" maxlength="8">
                    <button class="btn-redeem" onclick="applyReferralCode()">Redeem</button>
                </div>
            </div>
        @endif

        <div class="how-it-works">
            <h5 class="section-title">How it works</h5>
            <div class="step-item">
                <div class="step-number">1</div>
                <div>
                    <strong>Share your code</strong>
                    <p class="text-muted small mb-0">Copy your unique referral code and send it to your friends.</p>
                </div>
            </div>
            <div class="step-item">
                <div class="step-number">2</div>
                <div>
                    <strong>Friends Register</strong>
                    <p class="text-muted small mb-0">When they sign up for a new account, they should enter your code.
                    </p>
                </div>
            </div>
            <div class="step-item">
                <div class="step-number">3</div>
                <div>
                    <strong>Get Rewards</strong>
                    <p class="text-muted small mb-0">Once they register, you'll receive 300 wallet points instantly!</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Notification Container -->
    <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
        <div id="liveToast" class="toast hide" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-orange text-white">
                <img src="{{ asset('asset/images/grabbasket.png') }}" class="rounded me-2" alt="..."
                    style="height: 15px;">
                <strong class="me-auto">GrabBasket</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"
                    aria-label="Close"></button>
            </div>
            <div class="toast-body" id="toastMessage"></div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const referralCode = "{{ auth()->user()->referral_code }}";
        const referralUrl = "{{ url('/register') }}";
        const shareMessage = `Join GrabBasket using my referral code ${referralCode} and get started! Register here: ${referralUrl}`;

        function showToast(message, isError = false) {
            const toastEl = document.getElementById('liveToast');
            const toastBody = document.getElementById('toastMessage');
            const toastHeader = toastEl.querySelector('.toast-header');

            toastBody.textContent = message;
            toastHeader.className = isError ? 'toast-header bg-danger text-white' : 'toast-header bg-success text-white';

            const toast = new bootstrap.Toast(toastEl);
            toast.show();
        }

        function copyCode() {
            navigator.clipboard.writeText(referralCode).then(() => {
                showToast('Referral code copied to clipboard!');
            });
        }

        async function shareCode() {
            if (navigator.share) {
                try {
                    await navigator.share({
                        title: 'GrabBasket Referral',
                        text: shareMessage,
                        url: referralUrl,
                    });
                } catch (err) {
                    console.log('Error sharing:', err);
                }
            } else {
                // Fallback: Copy to clipboard if Web Share API not available
                copyCode();
                showToast('Share API not supported. Code copied instead!');
            }
        }

        function shareWhatsApp() {
            window.open(`https://wa.me/?text=${encodeURIComponent(shareMessage)}`, '_blank');
        }

        function shareFacebook() {
            window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(referralUrl)}&quote=${encodeURIComponent(shareMessage)}`, '_blank');
        }

        function shareTwitter() {
            window.open(`https://twitter.com/intent/tweet?text=${encodeURIComponent(shareMessage)}`, '_blank');
        }

        async function applyReferralCode() {
            const code = document.getElementById('applyCodeInput').value.trim();
            if (!code) {
                showToast('Please enter a referral code.', true);
                return;
            }

            try {
                const response = await fetch("{{ route('referral.apply') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': "{{ csrf_token() }}"
                    },
                    body: JSON.stringify({ referral_code: code })
                });

                const data = await response.json();

                if (response.ok) {
                    showToast(data.message);
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    showToast(data.message || 'Failed to apply code.', true);
                }
            } catch (err) {
                showToast('An error occurred. Please try again.', true);
            }
        }
    </script>
</body>

</html>