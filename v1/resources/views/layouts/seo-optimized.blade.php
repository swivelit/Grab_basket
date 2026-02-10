<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Primary Meta Tags -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- SEO Meta Tags -->
    <title>@yield('meta_title', config('app.name') . ' - Online Grocery Shopping & Quick Delivery in India')</title>
    <meta name="description" content="@yield('meta_description', 'Shop groceries, fruits, vegetables & daily essentials online. Get 10-minute express delivery. Fresh products, best prices. Order now on GrabBaskets!')">
    <meta name="keywords" content="@yield('meta_keywords', 'online grocery, quick delivery, grocery shopping, fresh vegetables, fruits delivery, daily essentials, GrabBaskets, online shopping India')">
    <meta name="author" content="GrabBaskets">
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
    <meta name="googlebot" content="index, follow">
    <link rel="canonical" href="{{ url()->current() }}">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="@yield('og_title', config('app.name') . ' - Online Grocery Shopping & Quick Delivery')">
    <meta property="og:description" content="@yield('og_description', 'Shop groceries online with 10-minute express delivery. Fresh products, best prices.')">
    <meta property="og:image" content="@yield('og_image', asset('asset/images/grabbaskets-og.jpg'))">
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:locale" content="en_IN">
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="{{ url()->current() }}">
    <meta name="twitter:title" content="@yield('twitter_title', config('app.name') . ' - Online Grocery Shopping')">
    <meta name="twitter:description" content="@yield('twitter_description', 'Shop groceries online with 10-minute express delivery')">
    <meta name="twitter:image" content="@yield('twitter_image', asset('asset/images/grabbaskets-og.jpg'))">
    
    <!-- Mobile App Meta -->
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="{{ config('app.name') }}">
    <meta name="theme-color" content="#0C831F">
    
    <!-- Favicon -->
    <link rel="icon" type="image/jpeg" href="{{ asset('asset/images/grabbaskets.jpg') }}">
    <link rel="apple-touch-icon" href="{{ asset('asset/images/grabbaskets.jpg') }}">
    <link rel="shortcut icon" href="{{ asset('asset/images/grabbaskets.jpg') }}">
    
    <!-- Preconnect to external domains -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link rel="preconnect" href="https://maps.googleapis.com">
    <link rel="dns-prefetch" href="https://checkout.razorpay.com">
    
    <!-- Critical CSS - Bootstrap & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Structured Data - Organization -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Organization",
        "name": "{{ config('app.name') }}",
        "url": "{{ config('app.url') }}",
        "logo": "{{ asset('asset/images/logo-image.png') }}",
        "description": "Online grocery shopping and quick delivery service in India",
        "address": {
            "@type": "PostalAddress",
            "addressCountry": "IN"
        },
        "contactPoint": {
            "@type": "ContactPoint",
            "telephone": "+91-XXXXXXXXXX",
            "contactType": "Customer Service",
            "areaServed": "IN",
            "availableLanguage": ["English", "Hindi"]
        },
        "sameAs": [
            "https://facebook.com/grabbaskets",
            "https://twitter.com/grabbaskets",
            "https://instagram.com/grabbaskets"
        ]
    }
    </script>
    
    <!-- Structured Data - Website -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebSite",
        "name": "{{ config('app.name') }}",
        "url": "{{ config('app.url') }}",
        "potentialAction": {
            "@type": "SearchAction",
            "target": "{{ config('app.url') }}/search?q={search_term_string}",
            "query-input": "required name=search_term_string"
        }
    }
    </script>
    
    @stack('styles')
</head>
<body>
    @yield('content')
    
    <!-- Essential Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    @stack('scripts')
</body>
</html>
