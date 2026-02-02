<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'GrabBaskets') }} - 10 Minute Grocery Delivery</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <!-- Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            /* Brand Colors */
            --primary: #3C096C;
            --primary-light: #5A189A;
            --secondary: #FF6B00;
            --accent: #FFD700;
            /* Gold */
            --logout-red: linear-gradient(135deg, #ff416c, #ff4b2b);
            /* UI Colors */
            --bg-body: #f5f5f5;
            --bg-white: #ffffff;
            --text-main: #212529;
            --text-muted: #6c757d;
            --border-light: #e9ecef;
            --search-border: #d0d0d0;
            /* Spacing */
            --header-height-mobile: 110px;
            --bottom-nav-height: 70px;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-body);
            color: var(--text-main);
            -webkit-tap-highlight-color: transparent;
        }

        /* Utilities */
        .text-primary {
            color: var(--primary) !important;
        }

        .fw-bold {
            font-weight: 700 !important;
        }

        .fw-semibold {
            font-weight: 600 !important;
        }

        .fs-7 {
            font-size: 0.85rem;
        }

        .fs-8 {
            font-size: 0.75rem;
        }

        .truncate-1 {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* ===== ALL NAV BUTTONS: 10px RADIUS ===== */
        .join-btn,
        .cart-btn,
        .auth-name,
        .logout-btn,
        .icon-btn,
        .mobile-logout-btn,
        .browse-btn,
        .add-btn,
        .add-to-cart-btn-desktop {
            border-radius: 10px !important;
        }

        .navbar-gradient {
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
        }

        /* Join Button */
        .join-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 22px;
            font-weight: 700;
            font-size: 0.95rem;
            border: none;
            transition: all 0.3s ease;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            box-shadow: 0 4px 10px rgba(60, 9, 108, 0.25);
        }

        .join-btn i {
            color: var(--accent);
            /* Gold icon */
            font-size: 1.2rem;
        }

        .join-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(60, 9, 108, 0.4);
            background: linear-gradient(135deg, var(--primary-light), #7b2cbf);
        }

        /* Cart Button - Now matches Join Button */
        .cart-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 22px;
            font-weight: 700;
            font-size: 0.95rem;
            border: none;
            transition: all 0.3s ease;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            box-shadow: 0 4px 10px rgba(60, 9, 108, 0.25);
            text-decoration: none;
        }

        .cart-btn i {
            color: var(--accent);
            /* Gold icon */
            font-size: 1.2rem;
        }

        .cart-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(60, 9, 108, 0.4);
            background: linear-gradient(135deg, var(--primary-light), #7b2cbf);
        }

        /* Mobile Header Redesign Styles */
        .mobile-only .mobile-header {
            padding: 15px 15px 20px;
            position: sticky;
            top: 0;
            z-index: 1000;
            border-bottom-left-radius: 20px;
            border-bottom-right-radius: 20px;
        }

        .mobile-delivery-announcement {
            display: none;
        }

        @media (max-width: 991px) {
            .mobile-delivery-announcement {
                display: block;
                background: linear-gradient(135deg, #FF6B00 0%, #FF8533 100%);
                color: white !important;
                text-align: center;
                padding: 18px 20px;
                font-size: 1rem;
                font-weight: 800;
                text-decoration: none;
                border-radius: 20px;
                margin: 0 15px 25px;
                box-shadow: 0 10px 25px rgba(255, 107, 0, 0.25);
                letter-spacing: 0.5px;
                text-transform: uppercase;
                border: 1px solid rgba(255, 255, 255, 0.2);
                transition: transform 0.2s ease, box-shadow 0.2s ease;
            }

            .mobile-delivery-announcement:active {
                transform: scale(0.97);
                box-shadow: 0 5px 15px rgba(255, 107, 0, 0.2);
            }

            .mobile-delivery-announcement i {
                font-size: 1.4rem;
                vertical-align: middle;
                margin-left: 5px;
            }
        }

        .mobile-menu-trigger {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 12px;
            font-size: 1.4rem;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(5px);
        }

        .mobile-icon-btn {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            text-decoration: none;
            backdrop-filter: blur(5px);
        }

        .mobile-profile-link img {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            border: 2px solid white;
            object-fit: cover;
        }

        .mobile-search-wrapper {
            margin-top: 10px;
        }

        .search-bar-modern {
            position: relative;
            background: white;
            border-radius: 15px;
            padding: 12px 15px;
            display: flex;
            align-items: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .search-bar-modern i {
            color: #6a11cb;
            font-size: 1.1rem;
            margin-right: 10px;
        }

        .search-bar-modern input {
            border: none;
            outline: none;
            width: 100%;
            font-size: 0.95rem;
            font-weight: 500;
            color: #333;
        }

        /* Quick Services Grid */
        .quick-services-section {
            padding: 20px 15px;
        }

        .services-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
        }

        .service-tile {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none;
            padding: 12px 5px;
            border-radius: 18px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: white;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .service-tile:active {
            transform: scale(0.92);
        }

        .tile-icon {
            font-size: 1.8rem;
            margin-bottom: 8px;
        }

        /* Mobile Category Drawer Styles */
        .mobile-category-drawer {
            position: fixed;
            top: 0;
            left: -100%;
            width: 80%;
            height: 100%;
            background: white;
            z-index: 2000;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 10px 0 30px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            border-top-right-radius: 25px;
            border-bottom-right-radius: 25px;
        }

        .mobile-category-drawer.active {
            left: 0;
        }

        .drawer-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(4px);
            z-index: 1999;
            display: none;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .drawer-overlay.active {
            display: block;
            opacity: 1;
        }

        .drawer-header {
            padding: 25px 20px;
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top-right-radius: 25px;
        }

        .drawer-header h5 {
            margin: 0;
            font-weight: 800;
            letter-spacing: 0.5px;
        }

        .close-drawer {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            width: 35px;
            height: 35px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .drawer-body {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
        }

        .drawer-item {
            display: flex;
            align-items: center;
            padding: 15px;
            text-decoration: none;
            color: #333;
            border-radius: 15px;
            margin-bottom: 8px;
            transition: all 0.2s ease;
            background: #f8f9fa;
        }

        .drawer-item:active {
            background: #e9ecef;
            transform: scale(0.98);
        }

        .drawer-item .item-emoji {
            font-size: 1.5rem;
            margin-right: 15px;
            width: 45px;
            height: 45px;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }

        .drawer-item .item-text {
            font-weight: 600;
            font-size: 0.95rem;
        }

        /* Hide category grid on mobile home */
        .mobile-only .category-grid {
            display: none !important;
        }

        .tile-label {
            font-size: 0.7rem;
            font-weight: 700;
            text-align: center;
            line-height: 1.2;
            color: #444;
        }

        .grocery-tile { border: 1px solid #e1f5fe; background: #f1faff; }
        .food-tile { border: 1px solid #fff3e0; background: #fff8eb; }
        .meat-tile { border: 1px solid #ffebee; background: #fff5f6; }
        .library-tile { border: 1px solid #f3e5f5; background: #f9f2fa; }

        .mobile-main-content {
            padding-bottom: 80px;
        }

        /* Responsive Categories */
        .category-grid {
            display: flex;
            overflow-x: auto;
            gap: 15px;
            padding: 10px 15px 25px;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }

        .category-grid::-webkit-scrollbar {
            display: none;
        }

        .cat-icon-box {
            width: 65px;
            height: 65px;
            background: white;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            margin-bottom: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.06);
            transition: all 0.2s ease;
        }
.auth-name{
    color:white
    
}

        .auth-name i {
            color: var(--primary);
            font-size: 1.1rem;
        }

        .auth-name:hover {
            background: white;
            color: var(--primary-light);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
        }

        .logout-btn {
            background: var(--logout-red);
            color: white;
            border: none;
            padding: 10px 18px;
            font-weight: 700;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(255, 75, 43, 0.2);
        }

        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(255, 75, 43, 0.4);
            filter: brightness(1.1);
        }

        .logout-btn i {
            font-size: 1rem;
        }

        /* Search Bar */
        .search-bar-container {
            position: relative;
            width: 100%;
        }

        .search-input {
            width: 100%;
            background: transparent !important;
            border: 1px solid var(--search-border) !important;
            border-radius: 50px !important;
            padding: 10px 20px 10px 50px;
            color: white !important;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .search-input::placeholder {
            color: rgba(255, 255, 255, 0.8) !important;
        }

        .search-input:focus {
            background: white !important;
            color: var(--text-main) !important;
            border: 1px solid var(--search-border) !important;
            outline: none;
            box-shadow: 0 0 0 2px rgba(60, 9, 108, 0.2) !important;
        }

        .search-icon {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.9) !important;
            font-size: 1.2rem;
            pointer-events: none;
        }

        .search-input:focus~.search-icon {
            color: var(--text-muted) !important;
        }

        /* Bounce Animation for Down Arrow */
        @keyframes bounce {

            0%,
            20%,
            50%,
            80%,
            100% {
                transform: translateY(0);
            }

            40% {
                transform: translateY(-8px);
            }

            60% {
                transform: translateY(-4px);
            }
        }

        .down-arrow {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            animation: bounce 2s infinite;
        }

        /* Firework Animation (Global) */
        @keyframes explode {
            0% { transform: scale(1); opacity: 1; box-shadow: 0 0 0 #fdcf58, 0 0 0 #fdcf58, 0 0 0 #fdcf58, 0 0 0 #fdcf58, 0 0 0 #fdcf58, 0 0 0 #fdcf58, 0 0 0 #fdcf58, 0 0 0 #fdcf58; }
            100% { transform: scale(1.5); opacity: 0; box-shadow: -20px -30px 0 #fdcf58, 20px -30px 0 #ff00ea, -30px 10px 0 #00ffea, 30px 10px 0 #ff004c, -10px -50px 0 #ffac38, 10px -50px 0 #ffac38, -40px 0px 0 #ffac38, 40px 0px 0 #ffac38; }
        }

        .firework {
            position: absolute;
            width: 6px;
            height: 6px;
            border-radius: 50%;
            animation: explode 1.5s infinite ease-out;
            opacity: 0;
            z-index: 1;
            pointer-events: none; /* Prevent clicking interaction issues */
        }
        .fw-1 { top: 20%; left: 20%; animation-delay: 0s; }
        .fw-2 { top: 30%; right: 20%; animation-delay: 0.5s; }
        .fw-3 { bottom: 30%; left: 40%; animation-delay: 1s; }
        .fw-4 { top: 15%; right: 40%; animation-delay: 0.8s; }

        /* ===== GLOBAL ANIMATIONS & STYLES ===== */
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        @keyframes floatBanner {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }

        .fast-delivery-badge {
            background: #ff0000;
            color: white;
            padding: 5px 12px;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: bold;
            display: inline-block;
            width: fit-content;
            margin-bottom: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.3);
            animation: pulse 2s infinite;
        }

        /* Third Hiring Slide - Elegant Theme */
        .hero-banner-hiring-3 {
             /* New Business/Future Theme - Cache Busting Applied */
             background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('https://images.unsplash.com/photo-1556761175-5973dc0f32e7?ixlib=rb-4.0.3&auto=format&fit=crop&w=1932&q=80&v=2') !important;
             background-size: cover !important;
             background-position: center !important;
        }

        /* ===== MOBILE VIEW ===== */
        @media (max-width: 991px) {
            .desktop-only {
                display: none !important;
            }

            body {
                padding-bottom: calc(var(--bottom-nav-height) + 20px);
            }

            .mobile-header {
                position: sticky;
                top: 0;
                z-index: 1000;
                padding: 12px 15px;
            }

            .brand-mobile {
                font-size: 1.6rem;
                font-weight: 800;
                color: white;
                text-decoration: none;
                display: flex;
                align-items: center;
                gap: 8px;
            }

            .search-bar-container {
                margin-top: 12px;
            }

            .search-input {
                padding: 12px 15px 12px 48px;
                border-radius: 12px !important;
            }

            .search-icon {
                left: 15px;
                font-size: 1.1rem;
            }

            /* Mobile Icons - Now use navbar background */
            .mobile-icons {
                display: flex;
                align-items: center;
                gap: 14px;
            }

            .icon-btn {
                width: 44px;
                height: 44px;
                border-radius: 10px;
                /* Updated to 10px */
                background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
                /* Navbar bg */
                display: flex;
                align-items: center;
                justify-content: center;
                text-decoration: none;
                transition: all 0.2s ease;
                box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            }

            .icon-btn i {
                color: var(--accent) !important;
                /* Gold icon */
                font-size: 1.3rem;
                font-weight: bold;
            }

            .icon-btn:hover {
                transform: scale(1.08);
                opacity: 0.9;
            }

            .mobile-auth-group {
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .mobile-logout-btn {
                background: var(--logout-red);
                border: none;
                color: white;
                width: 36px;
                height: 36px;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .mobile-logout-btn i {
                font-size: 1.1rem;
            }

            .mobile-logout-btn:hover {
                transform: scale(1.1);
            }

            /* Banners - Match desktop style */
            .hero-banner {
                /* Premium Image - Dark City Night for Fireworks */
                background: url('https://images.unsplash.com/photo-1478760329108-5c3ed9d495a0?ixlib=rb-4.0.3&auto=format&fit=crop&w=1674&q=80');
                background-size: cover;
                background-position: center;
                animation: kenBurns 20s ease-in-out infinite alternate;
                height: auto;
                min-height: 260px;
                border-radius: 24px;
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center; /* Center Content */
                text-align: center; /* Center Text */
                padding: 35px 25px;
                color: white;
                margin: 0; /* Margin handled by carousel */
                position: relative;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
                overflow: hidden;
                border: 1px solid rgba(255, 255, 255, 0.1);
            }

            /* Second Hiring Slide - Dark Team Theme + Fireworks */
            .hero-banner-hiring-2 {
                /* Dark overlay to make fireworks pop on office image */
                background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('https://images.unsplash.com/photo-1522071820081-009f0129c71c?ixlib=rb-4.0.3&auto=format&fit=crop&w=1770&q=80');
                background-size: cover;
                background-position: center;
            }

            /* Partnership Slide */
            .hero-banner-alt {
                background: url('https://images.unsplash.com/photo-1521791136064-7986c2920216?ixlib=rb-4.0.3&auto=format&fit=crop&w=1769&q=80');
                background-size: cover;
                background-position: center;
            }



            @keyframes kenBurns {
                0% { background-size: 100%; transform: scale(1); }
                100% { background-size: 120%; transform: scale(1.05); }
            }



            /* REMOVED: Decorative Circle / Overlay Effect */
            .hero-banner::before {
                display: none;
            }

            .hero-banner h2 {
                font-size: 1.8rem;
                font-weight: 800;
                margin-bottom: 12px;
                line-height: 1.2;
                color: #fff;
                text-shadow: 2px 2px 4px rgba(0,0,0,0.9); /* Stronger shadow for night mode */
                z-index: 2;
                position: relative;
            }

            .hero-banner p {
                 font-size: 1rem;
                 opacity: 1; /* Full opacity */
                 margin-bottom: 24px;
                 font-weight: 500;
                 line-height: 1.5;
                 color: #f1f1f1;
                 text-shadow: 1px 1px 3px rgba(0,0,0,0.9);
                 z-index: 2;
                 position: relative;
            }
            
            .style-join-btn {
                position: relative;
                z-index: 2;
                display: inline-block; /* Ensure centering works */
            }

            .join-btn-banner {
                background: #ffffff;
                color: var(--primary);
                border: none;
                padding: 12px 30px;
                font-weight: 700;
                border-radius: 50px; /* Pillow shape */
                text-align: center;
                text-decoration: none;
                width: fit-content;
                box-shadow: 0 5px 15px rgba(0,0,0,0.15);
                transition: transform 0.2s, box-shadow 0.2s;
                font-size: 1rem;
                z-index: 2;
                display: inline-block;
            }

            .join-btn-banner:hover {
                 transform: translateY(-2px);
                 box-shadow: 0 8px 20px rgba(0,0,0,0.2);
                 background: #f8f9fa;
            }

            .munchies-banner {
                background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('https://images.unsplash.com/photo-1563805042-7684c019e1cb?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80');
                background-size: cover;
                background-position: center;
                height: 280px;
                border-radius: 20px;
                padding: 30px;
                display: flex;
                flex-direction: column;
                justify-content: center;
                margin: 20px 15px;
                box-shadow: 0 10px 20px rgba(0,0,0,0.2);
                border: 1px solid rgba(255,255,255,0.2);
            }

            /* Unify mobile carousel items height and animation */
            #mobileBannerCarousel .hero-banner,
            #mobileBannerCarousel .munchies-banner,
            #mobileBannerCarousel .food-promo-card {
                height: 280px !important;
                min-height: 280px !important;
                animation: floatBanner 4s ease-in-out infinite !important;
                margin: 0 !important;
            }

            .munchies-banner h3 {
                font-size: 1.8rem;
                margin-bottom: 5px;
                color: #ffffff;
                font-weight: 800;
                text-shadow: 2px 2px 4px rgba(0,0,0,0.8);
            }

            .munchies-banner p {
                color: #f1f1f1;
                margin-bottom: 20px;
                font-weight: 500;
                font-size: 1.1rem;
                text-shadow: 1px 1px 3px rgba(0,0,0,0.8);
            }
            


            .browse-btn {
                background: linear-gradient(135deg, #FF512F 0%, #DD2476 100%);
                color: white;
                border: none;
                padding: 10px 24px;
                font-weight: 600;
                display: inline-flex;
                align-items: center;
                gap: 8px;
                text-decoration: none;
                transition: all 0.3s ease;
                width: fit-content;
            }

            .browse-btn:hover {
                background: #2a064d;
                transform: translateY(-2px);
                box-shadow: 0 4px 8px rgba(60, 9, 108, 0.3);
            }

            .category-grid {
                display: grid;
                grid-template-columns: repeat(3, 1fr); /* 3 items per row */
                gap: 15px 10px;
                padding: 20px 15px;
                background: #fff;
                border-radius: 0 0 20px 20px; /* Only bottom rounded */
                margin-bottom: 10px;
            }

            .category-item {
                text-align: center;
                text-decoration: none;
                color: var(--text-main);
            }

            .cat-icon-box {
                width: 60px;
                height: 60px;
                background: #f0fdf4;
                border-radius: 16px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 1.8rem;
                margin: 0 auto 8px;
                transition: transform 0.2s;

            }

            .product-rail {
                background: #fff;
                padding: 20px 0;
                margin-bottom: 10px;
            }

            .rail-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 0 15px 15px;
            }

            .rail-scroll {
                display: flex;
                overflow-x: auto;
                gap: 15px;
                padding: 0 15px;
                scrollbar-width: none;
            }

            .rail-scroll::-webkit-scrollbar {
                display: none;
            }

            .product-card-mobile {
                min-width: 140px;
                width: 140px;
                flex-shrink: 0;
            }

            .pm-image-box {
                width: 100%;
                height: 140px;
                background: #f8f9fa;
                border-radius: 14px;
                padding: 10px;
                display: flex;
                align-items: center;
                justify-content: center;
                margin-bottom: 10px;
                border: 1px solid var(--border-light);
            }

            .pm-image {
                max-width: 100%;
                max-height: 100%;
                object-fit: contain;
                mix-blend-mode: multiply;
            }

            .add-btn {
                background: #fff;
                border: 1px solid var(--primary);
                color: var(--primary);
                padding: 5px 0;
                width: 100%;
                font-weight: 600;
                font-size: 0.9rem;
                margin-top: 5px;
                border-radius: 8px; /* Rounded */
                transition: all 0.2s;
            }

            /* Quantity Control Styles */
            .qty-control {
                display: flex;
                align-items: center;
                justify-content: space-between;
                background: var(--primary);
                border-radius: 8px;
                padding: 4px;
                width: 100%;
                margin-top: 5px;
                height: 33px;
            }

            .qty-btn {
                background: none;
                border: none;
                color: white;
                font-weight: 700;
                width: 24px;
                height: 100%;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
            }

            .qty-val {
                color: white;
                font-weight: 600;
                font-size: 0.9rem;
            }

            /* Fix product card layout shift */
            .product-card-mobile {
                min-width: 140px;
                width: 140px;
                flex-shrink: 0;
                display: flex;
                flex-direction: column;
            }

            /* Mobile Header Enhancements */
            .mobile-header {
                padding: 15px 15px 20px;
                background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
                border-bottom-left-radius: 20px;
                border-bottom-right-radius: 20px;
                margin-bottom: 20px;
                box-shadow: 0 4px 15px rgba(37, 117, 252, 0.2);
            }
            
            main {
                padding-left: 5px;
                padding-right: 5px;
            }

            .add-btn:active {
                background: var(--primary);
                color: #fff;
            }

            /* ===== PREMIUM BOTTOM NAV ===== */
            .bottom-nav {
                position: fixed;
                bottom: 20px;
                left: 50%;
                transform: translateX(-50%);
                width: 90%;
                max-width: 400px;
                height: 70px;
                background: rgba(255, 255, 255, 0.95);
                backdrop-filter: blur(12px);
                -webkit-backdrop-filter: blur(12px);
                display: flex;
                justify-content: space-evenly;
                align-items: center;
                box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1), 0 0 0 1px rgba(255,255,255,0.5);
                z-index: 1000;
                border-radius: 25px;
                padding: 0 5px;
            }

            .nav-link-mobile {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                text-decoration: none;
                color: #94a3b8; /* Slate-400 */
                font-size: 10px;
                font-weight: 600;
                width: 60px;
                height: 60px;
                border-radius: 20px;
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                position: relative;
                letter-spacing: 0.3px;
            }

            .nav-link-mobile i {
                font-size: 24px;
                margin-bottom: 4px;
                transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            }

            .nav-link-mobile span {
                transition: all 0.2s ease;
                transform-origin: center;
            }

            /* Active State */
            .nav-link-mobile.active {
                color: var(--primary);
                background: linear-gradient(135deg, rgba(106, 17, 203, 0.1) 0%, rgba(37, 117, 252, 0.1) 100%);
            }

            .nav-link-mobile.active i {
                transform: translateY(-2px);
                filter: drop-shadow(0 4px 8px rgba(106, 17, 203, 0.3));
            }

            .nav-link-mobile.active span {
                font-weight: 700;
            }

            /* Active Indicator Dot */
            .nav-link-mobile.active::after {
                content: '';
                position: absolute;
                bottom: 8px;
                width: 4px;
                height: 4px;
                border-radius: 50%;
                background: var(--primary);
                box-shadow: 0 0 10px var(--primary);
            }

            /* Hover Effect */
            .nav-link-mobile:not(.active):active {
                transform: scale(0.92);
                background: rgba(0,0,0,0.03);
            }

             /* Cart Badge Sizing */
            #mobile-cart-badge {
                font-size: 0.65rem !important;
                padding: 0.35em 0.55em !important;
                border: 2px solid white;
                box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            }
        }

        /* ===== DESKTOP VIEW ===== */
        @media (min-width: 992px) {
            .mobile-only {
                display: none !important;
            }

            .desktop-navbar {
                background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
                padding: 15px 0;
                position: sticky;
                top: 0;
                z-index: 1000;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
            }

            .navbar-container {
                display: flex;
                align-items: center;
                justify-content: space-between;
                width: 100%;
                max-width: 1400px;
                margin: 0 auto;
                padding: 0 20px;
            }

            .brand-section {
                display: flex;
                align-items: center;
            }

            .brand-logo {
                font-size: 1.8rem;
                font-weight: 800;
                color: white;
                text-decoration: none;
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .delivery-info {
                max-width: 200px;
                line-height: 1.2;
                border-left: 1px solid rgba(255, 255, 255, 0.3);
                padding-left: 15px;
                margin-left: 15px;
                color: rgba(255, 255, 255, 0.9);
            }

            .desktop-search {
                flex: 1;
                max-width: 500px;
                margin: 0 20px;
            }

            .nav-actions {
                display: flex;
                align-items: center;
                gap: 15px;
                flex-shrink: 0;
            }

            .main-layout {
                padding-top: 30px;
                display: grid;
                grid-template-columns: 240px 1fr;
                gap: 30px;
            }

            .sidebar-menu {
                background: #fff;
                border-radius: 16px;
                padding: 15px;
                position: sticky;
                top: 100px;
                max-height: calc(100vh - 120px);
                overflow-y: auto;
            }

            .side-link {
                display: flex;
                align-items: center;
                gap: 12px;
                padding: 12px 15px;
                color: var(--text-main);
                text-decoration: none;
                border-radius: 10px;
                transition: all 0.2s;
                font-weight: 500;
            }

            .side-link:hover {
                background: #f8f9fa;
                color: var(--primary);
                transform: translateX(5px);
            }

            .desktop-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 20px;
                margin-bottom: 40px;
            }

            .product-card-desktop {
                background: #fff;
                border: 1px solid var(--border-light);
                border-radius: 16px;
                padding: 15px;
                display: flex;
                flex-direction: column;
                transition: all 0.3s ease;
                height: 100%;
                cursor: pointer;
            }

            .product-card-desktop:hover {
                transform: translateY(-8px);
                box-shadow: 0 15px 30px rgba(0, 0, 0, 0.12);
                border-color: var(--primary-light);
            }

            .pd-image-box {
                height: 180px;
                display: flex;
                align-items: center;
                justify-content: center;
                margin-bottom: 15px;
                padding: 10px;
            }

            .pd-image {
                max-height: 100%;
                max-width: 100%;
                object-fit: contain;
            }

            .add-to-cart-btn-desktop {
                margin-top: auto;
                background: #fff;
                border: 1px solid var(--primary);
                color: var(--primary);
                padding: 8px;
                font-weight: 600;
                width: 100%;
                transition: all 0.2s;
            }

            .add-to-cart-btn-desktop:hover {
                background: var(--primary);
                color: #fff;
            }

            /* Banners - Match Mobile Elegant Style */
            .hero-banner {
                /* Premium Image - Dark City Night for Fireworks */
                background: url('https://images.unsplash.com/photo-1478760329108-5c3ed9d495a0?ixlib=rb-4.0.3&auto=format&fit=crop&w=1674&q=80');
                background-size: cover;
                background-position: center;
                animation: kenBurns 20s ease-in-out infinite alternate;
                height: auto;
                min-height: 260px;
                border-radius: 24px;
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center; /* Center Content */
                text-align: center; /* Center Text */
                padding: 35px 25px;
                color: white;
                margin-bottom: 30px;
                position: relative;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
                overflow: hidden;
                border: 1px solid rgba(255, 255, 255, 0.1);
            }

            /* Second Hiring Slide */
            .hero-banner-hiring-2 {
                background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('https://images.unsplash.com/photo-1522071820081-009f0129c71c?ixlib=rb-4.0.3&auto=format&fit=crop&w=1770&q=80');
                background-size: cover;
                background-position: center;
            }

            /* Partnership Slide */
            .hero-banner-alt {
                background: url('https://images.unsplash.com/photo-1521791136064-7986c2920216?ixlib=rb-4.0.3&auto=format&fit=crop&w=1769&q=80');
                background-size: cover;
                background-position: center;
            }

            /* REMOVED: Decorative Circle / Overlay */
            .hero-banner::before {
                display: none;
            }

            .hero-banner h2 {
                font-size: 2rem;
                font-weight: 800;
                margin-bottom: 12px;
                line-height: 1.2;
                background: linear-gradient(to right, #fff, #e0e0e0);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                z-index: 2;
            }

            .hero-banner p {
                 font-size: 1.1rem;
                 opacity: 0.95;
                 margin-bottom: 24px;
                 font-weight: 400;
                 line-height: 1.5;
                 color: rgba(255, 255, 255, 0.9);
                 z-index: 2;
            }

            .join-btn-banner {
                background: #ffffff;
                color: var(--primary);
                border: none;
                padding: 12px 30px;
                font-weight: 700;
                border-radius: 50px;
                text-align: center;
                text-decoration: none;
                width: fit-content;
                box-shadow: 0 5px 15px rgba(0,0,0,0.15);
                transition: transform 0.2s, box-shadow 0.2s;
                font-size: 1rem;
                z-index: 2;
                display: inline-block;
            }

            .join-btn-banner:hover {
                 transform: translateY(-2px);
                 box-shadow: 0 8px 20px rgba(0,0,0,0.2);
                 background: #f8f9fa;
            }

            .munchies-banner {
                background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('https://images.unsplash.com/photo-1563805042-7684c019e1cb?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80');
                background-size: cover;
                background-position: center;
                height: 250px;
                /* width: 370px; removed for responsiveness */
                border-radius: 20px;
                padding: 30px;
                display: flex;
                flex-direction: column;
                justify-content: center;
                margin-top: 10px;
                box-shadow: 0 10px 20px rgba(0,0,0,0.2);
                border: 1px solid rgba(255,255,255,0.2);
                animation: floatBanner 4s ease-in-out infinite;
            }

            .munchies-banner h3 {
                font-size: 1.8rem;
                margin-bottom: 5px;
                color: #ffffff;
                font-weight: 800;
                text-shadow: 2px 2px 4px rgba(0,0,0,0.8);
            }

            .munchies-banner p {
                color: #f1f1f1;
                margin-bottom: 20px;
                font-weight: 500;
                font-size: 1.1rem;
                text-shadow: 1px 1px 3px rgba(0,0,0,0.8);
            }

            .browse-btn {
                background: linear-gradient(135deg, #FF512F 0%, #DD2476 100%);
                color: white;
                border: none;
                padding: 10px 24px;
                font-weight: 600;
                display: inline-flex;
                align-items: center;
                gap: 8px;
                text-decoration: none;
                transition: all 0.3s ease;
                width: fit-content;
            }

            .browse-btn:hover {
                background: #2a064d;
                transform: translateY(-2px);
                box-shadow: 0 4px 8px rgba(60, 9, 108, 0.3);
            }
        }

        .location-tracker {
            cursor: pointer;
        }

        .location-tracker:hover {
            opacity: 0.8;
        }

        /* Food Promo Section - Premium Design */
        .food-promo-section {
            margin: 40px 0;
            perspective: 1000px;
        }

        .food-promo-card {
            background: linear-gradient(135deg, #FF6B00 0%, #FF2D00 100%);
            border-radius: 30px;
            padding: 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            overflow: hidden;
            position: relative;
            box-shadow: 0 20px 40px rgba(255, 107, 0, 0.3);
            transition: transform 0.4s ease, box-shadow 0.4s ease;
        }

        .food-promo-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 30px 60px rgba(255, 107, 0, 0.4);
        }

        .food-promo-content {
            flex: 1;
            z-index: 2;
            color: white;
            text-align: left;
        }

        .food-badge {
            background: rgba(255, 255, 255, 0.2);
            padding: 8px 16px;
            border-radius: 50px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            font-weight: 700;
            margin-bottom: 20px;
            backdrop-filter: blur(5px);
        }

        .promo-title {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 15px;
            line-height: 1.1;
            color: white !important;
            text-shadow: none !important;
            background: none !important;
            -webkit-text-fill-color: initial !important;
        }

        .promo-text {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 30px;
            max-width: 450px;
            color: rgba(255, 255, 255, 0.9);
        }

        .cta-food-btn {
            background: white;
            color: #FF6B00;
            padding: 15px 35px;
            border-radius: 50px;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .cta-food-btn:hover {
            transform: scale(1.05);
            background: #fff;
            color: #FF2D00;
        }

        .food-promo-image {
            flex: 0 0 300px;
            height: 300px;
            position: relative;
            z-index: 1;
        }

        .food-promo-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 20px;
            transform: rotate(5deg);
            box-shadow: -20px 20px 40px rgba(0, 0, 0, 0.2);
            transition: transform 0.4s ease;
        }

        .food-promo-card:hover .food-promo-image img {
            transform: rotate(0deg) scale(1.05);
        }

        @media (max-width: 991px) {
            .food-promo-section {
                margin: 40px 15px;
            }
            .food-promo-card {
                flex-direction: column;
                padding: 30px;
                text-align: center;
                height: auto;
            }
            .food-promo-content {
                margin-bottom: 30px;
                text-align: center;
            }
            .promo-title {
                font-size: 1.8rem;
            }
            .promo-text {
                margin: 0 auto 20px;
                font-size: 1rem;
            }
            .food-promo-image {
                flex: 0 0 220px;
                height: 220px;
                width: 220px;
            }
            .food-promo-image img {
                transform: rotate(0deg);
            }
        }

        
/* ===== FOOTER STYLES ===== */
.site-footer {
    background: linear-gradient(135deg, #1a0b2e 0%, #2d1b4e 100%);
    color: rgba(255, 255, 255, 0.9);
    padding: 60px 0 0;
    margin-top: 80px;
    position: relative;
    overflow: hidden;
}

.site-footer::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--primary), var(--primary-light), var(--secondary), var(--accent));
}

.footer-brand {
    font-size: 1.8rem;
    font-weight: 800;
    color: white;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.footer-brand i {
    color: var(--accent);
}

.footer-tagline {
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.95rem;
    margin-bottom: 25px;
    line-height: 1.6;
}

.footer-section-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: white;
    margin-bottom: 20px;
    position: relative;
    padding-bottom: 10px;
}

.footer-section-title::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 40px;
    height: 3px;
    background: linear-gradient(90deg, var(--accent), var(--secondary));
    border-radius: 2px;
}

.footer-links {
    list-style: none;
    padding: 0;
    margin: 0;
}

.footer-links li {
    margin-bottom: 12px;
}

.footer-links a {
    color: rgba(255, 255, 255, 0.7);
    text-decoration: none;
    transition: all 0.3s ease;
    font-size: 0.95rem;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.footer-links a:hover {
    color: var(--accent);
    transform: translateX(5px);
}

.footer-links a i {
    font-size: 0.85rem;
}

.social-links {
    display: flex;
    gap: 15px;
    margin-top: 20px;
    flex-wrap: wrap;
}

.social-link {
    width: 45px;
    height: 45px;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    text-decoration: none;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
}

.social-link:hover {
    background: var(--accent);
    color: var(--primary);
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(255, 215, 0, 0.3);
}

.social-link i {
    font-size: 1.2rem;
}

.download-badges {
    display: flex;
    gap: 12px;
    margin-top: 20px;
    flex-wrap: wrap;
}

.app-badge {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 12px 20px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    color: white;
    text-decoration: none;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.app-badge:hover {
    background: white;
    color: var(--primary);
    transform: translateY(-3px);
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
}

.app-badge i {
    font-size: 1.8rem;
}

.app-badge-text small {
    display: block;
    font-size: 0.7rem;
    opacity: 0.8;
}

.app-badge-text strong {
    font-size: 0.95rem;
    font-weight: 700;
}

.footer-bottom {
    margin-top: 50px;
    padding: 25px 0;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    text-align: center;
}

.footer-bottom-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
}

.copyright {
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.9rem;
}

.footer-legal-links {
    display: flex;
    gap: 25px;
    flex-wrap: wrap;
}

.footer-legal-links a {
    color: rgba(255, 255, 255, 0.6);
    text-decoration: none;
    font-size: 0.9rem;
    transition: color 0.3s ease;
}

.footer-legal-links a:hover {
    color: var(--accent);
}

.contact-info {
    display: flex;
    flex-direction: column;
    gap: 15px;
    margin-top: 20px;
}

.contact-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.95rem;
}

.contact-item i {
    color: var(--accent);
    font-size: 1.1rem;
    margin-top: 2px;
    flex-shrink: 0;
}

.contact-item a {
    color: rgba(255, 255, 255, 0.7);
    text-decoration: none;
    transition: color 0.3s ease;
}

.contact-item a:hover {
    color: var(--accent);
}

@media (max-width: 768px) {
    .site-footer {
        padding: 40px 0 0;
        margin-top: 50px;
    }

    .footer-brand {
        font-size: 1.5rem;
    }

    .footer-bottom-content {
        flex-direction: column;
        text-align: center;
    }

    .footer-legal-links {
        justify-content: center;
    }

    .download-badges {
        flex-direction: column;
    }

    .app-badge {
        width: 100%;
        justify-content: center;
    }
}
</style>
    </style>
</head>

<body>

    <!-- MOBILE VIEW -->
    <div class="mobile-only">
        <div class="mobile-header navbar-gradient">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="d-flex align-items-center">
                    <button class="mobile-menu-trigger me-2" onclick="toggleNavDrawer()">
                        <i class="bi bi-list"></i>
                    </button>
                    <a href="{{ route('home') }}" class="brand-mobile">
                        <i class="bi bi-bag-check-fill ms-1"></i> GrabBaskets
                    </a>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('cart.index') }}" class="mobile-icon-btn position-relative">
                        <i class="bi bi-cart3"></i>
                        @auth
                            @php $cartCount = \App\Models\CartItem::where('user_id', auth()->id())->sum('quantity'); @endphp
                            @if($cartCount > 0)
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem;">
                                    {{ $cartCount }}
                                </span>
                            @endif
                        @endauth
                    </a>
                    @auth
                        <div class="dropdown">
                            <a href="#" class="mobile-profile-link dropdown-toggle" role="button" data-bs-toggle="dropdown" aria-expanded="false" style="border: none; display: block;">
                                <img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=fff&color=6a11cb" alt="User">
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 mt-2" style="border-radius: 12px; min-width: 200px; padding: 10px 0;">
                                <li class="px-3 py-2 border-bottom mb-2">
                                    <div class="fw-bold text-primary">{{ Auth::user()->name }}</div>
                                    <div class="fs-8 text-muted truncate-1">{{ Auth::user()->email }}</div>
                                </li>
                                <li><a class="dropdown-item py-2 d-flex align-items-center" href="{{ route('profile.show') }}">
                                    <i class="bi bi-person me-2 text-primary"></i> Profile
                                </a></li>
                                <li><a class="dropdown-item py-2 d-flex align-items-center" href="/wishlist">
                                    <i class="bi bi-heart me-2 text-danger"></i> Wishlist
                                </a></li> 
                                <li><a class="dropdown-item py-2 d-flex align-items-center" href="/orders/track">
                                    <i class="bi bi-truck me-2 text-primary"></i> Orders
                                </a></li>
                                <li><a class="dropdown-item py-2 d-flex align-items-center" href="/buyer/dashboard">
                                    <i class="bi bi-speedometer2 me-2 text-success"></i> Buyer
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form action="{{ route('logout') }}" method="POST" class="px-2">
                                        @csrf
                                        <button type="submit" class="dropdown-item py-2 text-danger fw-bold d-flex align-items-center" style="border-radius: 8px;">
                                            <i class="bi bi-box-arrow-right me-2"></i> Logout
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    @else
                        <a href="{{ route('login') }}" class="cart-btn py-2 px-3 fs-7" style="border-radius: 12px !important;">
                             Login
                        </a>
                    @endauth
                </div>
            </div>

            <!-- Mobile Location Detection -->
            <div class="location-tracker mb-3 text-white px-1" onclick="getUserLocation()" style="cursor: pointer;">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-geo-alt-fill text-warning"></i>
                    <div style="line-height: 1.2;">
                        <div class="fw-bold fs-7 location-text-display">Detect your location</div>
                        <div class="small opacity-75 location-subtext-display" style="font-size: 0.7rem;">Click to find your location</div>
                    </div>
                </div>
            </div>
            
            <div class="mobile-search-wrapper">
                <form action="{{ route('products.index') }}" method="GET" class="search-bar-modern">
                    <i class="bi bi-search"></i>
                    <input type="text" name="q" placeholder="Search for groceries, food & more..." autocomplete="off">
                </form>
            </div>
        </div>

        <main class="mobile-main-content">
            <a href="/tenmins" class="mobile-delivery-announcement">
                ⚡ 10-Minute Delivery – Groceries & Food <i class="bi bi-arrow-right-short"></i>
            </a>
            <div id="mobileBannerCarousel" class="carousel slide mb-4" data-bs-ride="carousel" data-bs-interval="5000" data-bs-pause="hover" style="margin: 20px 15px 20px;">
                <div class="carousel-indicators">
                    <button type="button" data-bs-target="#mobileBannerCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
                    <button type="button" data-bs-target="#mobileBannerCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
                    <button type="button" data-bs-target="#mobileBannerCarousel" data-bs-slide-to="2" aria-label="Slide 3"></button>
                </div>
                <div class="carousel-inner" style="border-radius: 24px; overflow: hidden;">
                    <!-- Slide 1: Hiring (Theme 3 content) -->
                    <div class="carousel-item active">
                        <div class="hero-banner hero-banner-hiring-3">
                            <div class="firework fw-1"></div>
                            <div class="firework fw-2"></div>
                            <div class="firework fw-3"></div>
                            <div class="firework fw-4"></div>
                            <span class="badge bg-warning text-dark mb-2" style="position:relative; z-index:2;">🚀 Career Opportunity</span>
                            <h2>We Are Hiring: Secure Your Future</h2>
                            <p>Join our elite program! Pay a one-time fee of ₹30,000 and earn a guaranteed ₹15,000 per month.</p>
                            <button class="join-btn-banner mt-2 style-join-btn" onclick="window.location.href='https://forms.gle/zsTCTdKv1dxcA7QL7'">
                                Join Now
                            </button>
                        </div>
                    </div>
                    <!-- Slide 2: Munchies Banner -->
                    <div class="carousel-item">
                        <div class="munchies-banner" style="border-radius: 24px;">
                            <div class="fast-delivery-badge"><i class="bi bi-stopwatch-fill"></i> 10 Mins Fast Delivery</div>
                            <h3>⚡ Instant Munchies</h3>
                            <p>Quick snacks and drinks delivered in minutes!</p>
                            <a href="/tenmins" class="browse-btn">Browse <i class="bi bi-arrow-right"></i></a>
                        </div>
                    </div>
                    <!-- Slide 3: Delicious Food Banner -->
                    <div class="carousel-item">
                        <a href="{{ route('customer.food.index') }}" class="text-decoration-none">
                            <div class="food-promo-card" style="border-radius: 24px; padding: 25px; position: relative; overflow: hidden;">
                                <div class="firework fw-1"></div>
                                <div class="firework fw-2"></div>
                                <div class="firework fw-3"></div>
                                <div class="firework fw-4"></div>
                                <div class="food-promo-content">
                                    <div class="food-badge">
                                        <i class="bi bi-fire"></i> Hot & Fresh
                                    </div>
                                    <h2 class="promo-title" style="font-size: 1.5rem;">Delicious Food <br> Delivered to Your Door</h2>
                                    <p class="promo-text" style="font-size: 0.9rem; margin-bottom: 15px;">Order from top restaurants and get it delivered in minutes.</p>
                                    <span class="cta-food-btn" style="padding: 10px 20px;">
                                        Order Now <i class="bi bi-arrow-right"></i>
                                    </span>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>




            <div class="product-rail">
                <div class="rail-header">
                    <h5 class="fw-bold mb-0">🔥 Trending Now</h5>
                    <a href="#" class="text-primary text-decoration-none fs-7 fw-bold">See All</a>
                </div>
                <div class="rail-scroll">
                    @foreach(collect($ten_min_products ?? [])->take(6) as $prod)
                        <div class="product-card-mobile"
                            onclick="window.location.href='{{ route('product.details', $prod->id) }}'">
                            <div class="pm-image-box">
                                <img src="{{ $prod->image_url ?? asset('images/no-image.png') }}" alt="{{ $prod->name }}"
                                    class="pm-image" onerror="this.src='{{ asset('images/no-image.png') }}'">
                            </div>
                            <div class="fs-8 text-muted truncate-1">1 unit</div>
                            <div class="fs-7 fw-bold truncate-2 mb-1" style="height: 38px;">{{ $prod->name }}</div>
                            <div class="d-flex align-items-center justify-content-between">
                                <span class="fs-7 fw-bold">₹{{ number_format($prod->price, 0) }}</span>
                                <s class="fs-8 text-muted">₹{{ number_format($prod->price * 1.2, 0) }}</s>
                            </div>
                            <div id="btn-container-{{ $prod->id }}">
                                @auth
                                    @php
                                        $cartItem = \App\Models\CartItem::where('user_id', auth()->id())
                                                    ->where('product_id', $prod->id)
                                                    ->first();
                                        $qty = $cartItem ? $cartItem->quantity : 0;
                                    @endphp
                                    @if($qty > 0)
                                        <div class="qty-control" onclick="event.stopPropagation();">
                                            <button class="qty-btn" onclick="updateCart({{ $prod->id }}, 'decrease')">-</button>
                                            <span class="qty-val">{{ $qty }}</span>
                                            <button class="qty-btn" onclick="updateCart({{ $prod->id }}, 'increase')">+</button>
                                        </div>
                                    @else
                                        <button class="add-btn" onclick="event.stopPropagation(); updateCart({{ $prod->id }}, 'add')">
                                            ADD
                                        </button>
                                    @endif
                                @else
                                    <a href="{{ route('login') }}" class="add-btn" style="text-align:center; text-decoration:none;">
                                        Login
                                    </a>
                                @endauth
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>




            <div class="product-rail">
                <div class="rail-header">
                    <h5 class="fw-bold mb-0">🥬 Fresh Vegetables</h5>
                    <a href="#" class="text-primary text-decoration-none fs-7 fw-bold">See All</a>
                </div>
                <div class="rail-scroll">
                    @forelse(($products ?? [])->take(6) as $prod)
                        <div class="product-card-mobile"
                            onclick="window.location.href='{{ route('product.details', $prod->id) }}'">
                            <div class="pm-image-box">
                                <img src="{{ $prod->image_url ?? asset('images/no-image.png') }}" alt="{{ $prod->name }}"
                                    class="pm-image" onerror="this.src='{{ asset('images/no-image.png') }}'">
                            </div>
                            <div class="fs-8 text-muted truncate-1">500g</div>
                            <div class="fs-7 fw-bold truncate-2 mb-1" style="height: 38px;">{{ $prod->name }}</div>
                            <div class="d-flex align-items-center justify-content-between">
                                <span class="fs-7 fw-bold">₹{{ number_format($prod->price, 0) }}</span>
                            </div>
                            <div id="btn-container-{{ $prod->id }}">
                                @auth
                                    @php
                                        $cartItem = \App\Models\CartItem::where('user_id', auth()->id())
                                                    ->where('product_id', $prod->id)
                                                    ->first();
                                        $qty = $cartItem ? $cartItem->quantity : 0;
                                    @endphp
                                    @if($qty > 0)
                                        <div class="qty-control" onclick="event.stopPropagation();">
                                            <button class="qty-btn" onclick="updateCart({{ $prod->id }}, 'decrease')">-</button>
                                            <span class="qty-val">{{ $qty }}</span>
                                            <button class="qty-btn" onclick="updateCart({{ $prod->id }}, 'increase')">+</button>
                                        </div>
                                    @else
                                        <button class="add-btn"
                                            onclick="event.stopPropagation(); updateCart({{ $prod->id }}, 'add')">ADD</button>
                                    @endif
                                @else
                                    <a href="{{ route('login') }}" class="add-btn" style="text-align:center; text-decoration:none; display:block;">Login</a>
                                @endauth
                            </div>
                        </div>
                    @empty
                        <div class="p-3 text-center w-100 text-muted">No items available</div>
                    @endforelse
                </div>
            </div>


        </main>

        <nav class="bottom-nav">
            <a href="{{ route('home') }}" class="nav-link-mobile active">
                <i class="bi bi-house-door-fill"></i>
                <span>Home</span>
            </a>
            <a href="javascript:void(0)" onclick="toggleCategoryDrawer()" class="nav-link-mobile">
                <i class="bi bi-grid-fill"></i>
                <span>Categories</span>
            </a>
            <a href="{{ route('cart.index') }}" class="nav-link-mobile position-relative">
                <div class="position-relative">
                    <i class="bi bi-bag-fill"></i>
                    <span id="mobile-cart-badge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                        style="display: {{ (\App\Models\CartItem::where('user_id', auth()->id())->sum('quantity') ?? 0) > 0 ? 'inline-block' : 'none' }};">
                        @auth
                            {{ \App\Models\CartItem::where('user_id', auth()->id())->sum('quantity') ?? 0 }}
                        @else
                            0
                        @endauth
                    </span>
                </div>
                <span>Cart</span>
            </a>
            @auth
                <a href="{{ route('profile.show') }}" class="nav-link-mobile">
                    <i class="bi bi-person-fill"></i>
                    <span>Profile</span>
                </a>
            @else
                <a href="{{ route('login') }}" class="nav-link-mobile">
                    <i class="bi bi-person-circle"></i>
                    <span>Login</span>
                </a>
            @endauth
        </nav>
    </div>

    <!-- DESKTOP VIEW -->
    <div class="desktop-only">
        <nav class="desktop-navbar">
            <div class="navbar-container">
                <div class="brand-section">
                    <a href="{{ route('home') }}" class="brand-logo">
                        <i class="bi bi-bag-check-fill"></i> GrabBaskets
                    </a>
                    <div class="delivery-info location-tracker" onclick="getUserLocation()">
                        <div class="fw-bold fs-7">
                            <i class="bi bi-geo-alt-fill"></i>
                            <span class="location-text-display">Detect your location</span>
                        </div>
                        <div class="text-muted fs-8 truncate-1 location-subtext-display">
                            Click to find your location
                        </div>
                    </div>

                </div>
                <form action="{{ route('products.index') }}" method="GET" class="search-bar-container desktop-search">
                    <input type="text" name="q" class="search-input" placeholder="Search for products, brands and more">
                    <i class="bi bi-search search-icon"></i>
                </form>
                <div class="nav-actions">
                    <button class="join-btn" onclick="window.location.href='/joinus'">
                        <i class="bi bi-shop"></i> Join With Us
                    </button>

                    <a href="{{ route('cart.index') }}" class="cart-btn position-relative">
                        <i class="bi bi-cart3"></i> Cart
                        <span id="desktop-cart-badge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                             style="font-size: 0.6rem; display: {{ (\App\Models\CartItem::where('user_id', auth()->id())->sum('quantity') ?? 0) > 0 ? 'inline-block' : 'none' }};">
                            @auth
                                {{ \App\Models\CartItem::where('user_id', auth()->id())->sum('quantity') ?? 0 }}
                            @else
                                0
                            @endauth
                        </span>
                    </a>
                    @auth
                        <div class="dropdown">
                            <a href="#" class="auth-name dropdown-toggle d-flex align-items-center gap-2 py-2 px-3 text-white text-decoration-none" role="button" data-bs-toggle="dropdown" aria-expanded="false" style="background: rgba(255, 255, 255, 0.15); border-radius: 10px;">
                                <i class="bi bi-person-circle"></i>
                                <span>{{ Auth::user()->name }}</span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 mt-2" style="border-radius: 12px; min-width: 200px; padding: 10px 0;">
                                <li class="px-3 py-2 border-bottom mb-2">
                                    <div class="fw-bold text-primary">{{ Auth::user()->name }}</div>
                                    <div class="fs-8 text-muted truncate-1">{{ Auth::user()->email }}</div>
                                </li>
                                <li><a class="dropdown-item py-2 d-flex align-items-center" href="{{ route('profile.show') }}">
                                    <i class="bi bi-person me-2 text-primary"></i> Profile
                                </a></li>
                                <li><a class="dropdown-item py-2 d-flex align-items-center" href="/wishlist">
                                    <i class="bi bi-heart me-2 text-danger"></i> Wishlist
                                </a></li>
                                <li><a class="dropdown-item py-2 d-flex align-items-center" href="/orders/track">
                                    <i class="bi bi-truck me-2 text-primary"></i> Orders
                                </a></li>
                                <li><a class="dropdown-item py-2 d-flex align-items-center" href="/buyer/dashboard">
                                    <i class="bi bi-speedometer2 me-2 text-success"></i> Buyer
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form action="{{ route('logout') }}" method="POST" class="px-2">
                                        @csrf
                                        <button type="submit" class="dropdown-item py-2 text-danger fw-bold d-flex align-items-center" style="border-radius: 8px;">
                                            <i class="bi bi-box-arrow-right me-2"></i> Logout
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    @else
                        <a href="{{ route('login') }}" class="cart-btn">
                            <i class="bi bi-person"></i> Login
                        </a>
                    @endauth
                </div>
            </div>
        </nav>

        <div class="container main-layout">
            <aside>
                <div class="sidebar-menu shadow-sm-custom">
                    <div class="text-muted fs-8 fw-bold mb-3 px-3 text-uppercase tracking-wider">Categories</div>
                    @foreach(collect($categories ?? [])->take(10) as $cat)
                        <a href="{{ route('buyer.productsByCategory', $cat->id ?? 1) }}" class="side-link">
                            <span class="fs-5">{{ $cat->emoji ?? '📦' }}</span> {{ $cat->name ?? 'Category' }}
                        </a>
                    @endforeach
                    <a href="{{ route('categories.index') }}" class="side-link text-primary mt-2">
                        <i class="bi bi-grid fs-5"></i> View All
                    </a>
                </div>
            </aside>
            <main>
                <div class="row mb-5">
                    <div class="col-8">
                        <div id="desktopBannerCarousel" class="carousel slide mb-4" data-bs-ride="carousel" data-bs-interval="5000" data-bs-pause="hover">
                             <div class="carousel-indicators">
                                <button type="button" data-bs-target="#desktopBannerCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
                                <button type="button" data-bs-target="#desktopBannerCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
                                <button type="button" data-bs-target="#desktopBannerCarousel" data-bs-slide-to="2" aria-label="Slide 3"></button>
                            </div>
                            <div class="carousel-inner" style="border-radius: 24px; overflow: hidden;">
                                <!-- Slide 1: Hiring / Night City -->
                                <div class="carousel-item active">
                                    <div class="hero-banner">
                                        <div class="firework fw-1"></div>
                                        <div class="firework fw-2"></div>
                                        <div class="firework fw-3"></div>
                                        <div class="firework fw-4"></div>
                                        <span class="badge bg-warning text-dark mb-2" style="position:relative; z-index:2;">🚀 Career Opportunity</span>
                                        <h2>We Are Hiring: Secure Your Future</h2>
                                        <p>Join our elite program! Pay a one-time fee of ₹50,000 and earn a guaranteed ₹24,000 per month.</p>
                                        <button class="join-btn-banner mt-2 style-join-btn" onclick="window.location.href='https://forms.gle/zsTCTdKv1dxcA7QL7'">
                                            Join Now
                                        </button>
                                    </div>
                                </div>
                                <!-- Slide 2: Hiring / Dark Team -->
                                <div class="carousel-item">
                                    <div class="hero-banner hero-banner-hiring-2">
                                        <div class="firework fw-1"></div>
                                        <div class="firework fw-2"></div>
                                        <div class="firework fw-3"></div>
                                        <div class="firework fw-4"></div>
                                        <span class="badge bg-warning text-dark mb-2" style="position:relative; z-index:2;">🚀 Career Opportunity</span>
                                        <h2>We Are Hiring: Secure Your Future</h2>
                                        <p>Join our elite program! Pay a one-time fee of ₹30,000 and earn a guaranteed ₹15,000 per month.</p>
                                        <button class="join-btn-banner mt-2 style-join-btn" onclick="window.location.href='https://forms.gle/zsTCTdKv1dxcA7QL7'">
                                            Join Now
                                        </button>
                                    </div>
                                </div>
                                <!-- Slide 3: Partnership -->
                                <!-- Slide 3: Hiring / Delivery Theme -->
                                <div class="carousel-item">
                                    <div class="hero-banner hero-banner-hiring-3">
                                        <div class="firework fw-1"></div>
                                        <div class="firework fw-2"></div>
                                        <div class="firework fw-3"></div>
                                        <div class="firework fw-4"></div>
                                        <span class="badge bg-warning text-dark mb-2" style="position:relative; z-index:2;">🚀 Career Opportunity</span>
                                        <h2>We Are Hiring: Secure Your Future</h2>
                                        <p>Join our elite program! Pay a one-time fee of ₹30,000 and earn a guaranteed ₹15,000 per month.</p>
                                        <button class="join-btn-banner mt-2 style-join-btn" onclick="window.location.href='https://forms.gle/zsTCTdKv1dxcA7QL7'">
                                            Join Now
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="munchies-banner">
                            <div class="fast-delivery-badge"><i class="bi bi-stopwatch-fill"></i> 10 Mins Fast Delivery</div>
                            <h3>⚡ Instant Munchies</h3>
                            <p>Quick snacks and drinks delivered in minutes!</p>
                            <a href="/tenmins" class="browse-btn">Browse <i class="bi bi-arrow-right"></i></a>
                        </div>
                    </div>
                </div>
                <!-- Food Order Promotion -->
                <div class="food-promo-section mb-5">
                    <a href="{{ route('customer.food.index') }}" class="text-decoration-none">
                        <div class="food-promo-card">
                            <div class="food-promo-content">
                                <div class="food-badge">
                                    <i class="bi bi-fire"></i> Hot & Fresh
                                </div>
                                <h2 class="promo-title">Delicious Food <br> Delivered to Your Door</h2>
                                <p class="promo-text">Order from top restaurants and get it delivered in minutes. Experience the taste of your city with GrabBaskets.</p>
                                <span class="cta-food-btn">
                                    Order Now <i class="bi bi-arrow-right"></i>
                                </span>
                            </div>
                            <div class="food-promo-image">
                                <img src="https://images.unsplash.com/photo-1546069901-ba9599a7e63c?ixlib=rb-4.0.3&auto=format&fit=crop&w=1160&q=80" alt="Delicious Food">
                            </div>
                        </div>
                    </a>
                </div>

                <!-- Daily Staples Section -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="fw-bold m-0" id="daily">Daily Staples</h3>
                    <a href="#" class="text-primary text-decoration-none fw-bold">see all</a>
                </div>
                <div class="desktop-grid">
                    @forelse(collect($all_products ?? [])->take(8) as $prod)
                        <div class="product-card-desktop"
                            onclick="window.location.href='{{ route('product.details', $prod->id) }}'">
                            <div class="pd-image-box">
                                <img src="{{ $prod->image_url ?? asset('images/no-image.png') }}" class="pd-image"
                                    alt="{{ $prod->name }}" onerror="this.src='{{ asset('images/no-image.png') }}'">
                            </div>
                            <div class="text-muted fs-8 mb-1">1 unit</div>
                            <h6 class="fw-bold truncate-2 mb-3" style="min-height: 40px;">{{ $prod->name }}</h6>
                            <div class="d-flex justify-content-between align-items-end mt-auto">
                                <div>
                                    <div class="text-decoration-line-through text-muted fs-8">
                                        ₹{{ number_format($prod->price * 1.1, 0) }}</div>
                                    <div class="fw-bold fs-5">₹{{ number_format($prod->price, 0) }}</div>
                                </div>
                                <div id="btn-container-{{ $prod->id }}" style="width: 100px;"> <!-- Fixed width wrapper -->
                                    @auth
                                        @php
                                            $cartItem = \App\Models\CartItem::where('user_id', auth()->id())
                                                        ->where('product_id', $prod->id)
                                                        ->first();
                                            $qty = $cartItem ? $cartItem->quantity : 0;
                                        @endphp
                                        @if($qty > 0)
                                            <div class="qty-control" onclick="event.stopPropagation();">
                                                <button class="qty-btn" onclick="updateCart({{ $prod->id }}, 'decrease')">-</button>
                                                <span class="qty-val">{{ $qty }}</span>
                                                <button class="qty-btn" onclick="updateCart({{ $prod->id }}, 'increase')">+</button>
                                            </div>
                                        @else
                                            <button class="btn btn-outline-primary rounded-3 px-3 fw-bold w-100"
                                                onclick="event.stopPropagation(); updateCart({{ $prod->id }}, 'add')">ADD</button>
                                        @endif
                                    @else
                                        <a href="{{ route('login') }}" class="btn btn-outline-primary rounded-3 px-3 fw-bold w-100">
                                            Login
                                        </a>
                                    @endauth
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12 text-center py-5 text-muted bg-white rounded-4">
                            <i class="bi bi-basket display-4 mb-3 d-block"></i>
                            No products found
                        </div>
                    @endforelse
                </div>

                <!-- Snacks & Drinks Section Moved Here -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="fw-bold m-0">Snacks & Drinks</h3>
                    <a href="#" class="text-primary text-decoration-none fw-bold">see all</a>
                </div>
                <div class="desktop-grid">
                    @foreach(collect($ten_min_products ?? [])->skip(6)->take(4) as $prod)
                        <div class="product-card-desktop"
                            onclick="window.location.href='{{ route('product.details', $prod->id) }}'">
                            <div class="pd-image-box">
                                <img src="{{ $prod->image_url ?? asset('images/no-image.png') }}" class="pd-image"
                                    alt="{{ $prod->name }}" onerror="this.src='{{ asset('images/no-image.png') }}'">
                            </div>
                            <div class="text-muted fs-8 mb-1">Pack</div>
                            <h6 class="fw-bold truncate-2 mb-3" style="min-height: 40px;">{{ $prod->name }}</h6>
                            <div class="d-flex justify-content-between align-items-end mt-auto">
                                <div class="fw-bold fs-5">₹{{ number_format($prod->price, 0) }}</div>
                                <div id="btn-container-{{ $prod->id }}" style="width: 100px;">
                                    @auth
                                        @php
                                            $cartItem = \App\Models\CartItem::where('user_id', auth()->id())
                                                        ->where('product_id', $prod->id)
                                                        ->first();
                                            $qty = $cartItem ? $cartItem->quantity : 0;
                                        @endphp
                                        @if($qty > 0)
                                            <div class="qty-control" onclick="event.stopPropagation();">
                                                <button class="qty-btn" onclick="updateCart({{ $prod->id }}, 'decrease')">-</button>
                                                <span class="qty-val">{{ $qty }}</span>
                                                <button class="qty-btn" onclick="updateCart({{ $prod->id }}, 'increase')">+</button>
                                            </div>
                                        @else
                                            <button class="btn btn-outline-primary rounded-3 px-3 fw-bold w-100"
                                                onclick="event.stopPropagation(); updateCart({{ $prod->id }}, 'add')">ADD</button>
                                        @endif
                                    @else
                                        <a href="{{ route('login') }}" class="btn btn-outline-primary rounded-3 px-3 fw-bold w-100">
                                            Login
                                        </a>
                                    @endauth
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </main>
        </div>
    </div>

    <div class="mobile-category-drawer" id="categoryDrawer">
        <div class="drawer-header">
            <h5><i class="bi bi-grid-fill me-2"></i> Categories</h5>
            <button class="close-drawer" onclick="toggleCategoryDrawer()">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="drawer-body">
            @foreach(($categories ?? []) as $cat)
                <a href="{{ route('buyer.productsByCategory', $cat->id ?? 1) }}" class="drawer-item">
                    <div class="item-emoji">{{ $cat->emoji ?? '🥬' }}</div>
                    <div class="item-text">{{ $cat->name ?? 'Category' }}</div>
                </a>
            @endforeach
            @if(count($categories ?? []) == 0)
                @foreach(['Fruits', 'Veggies', 'Dairy', 'Bakery', 'Munchies', 'Cold Drinks', 'Instant', 'Cleaning', 'Home', 'Beauty', 'Pharma', 'Pet'] as $dummy)
                    <a href="#" class="drawer-item">
                        <div class="item-emoji">📦</div>
                        <div class="item-text">{{ $dummy }}</div>
                    </a>
                @endforeach
            @endif
        </div>
    </div>

    <!-- Mobile Nav Drawer (Hamburger Menu) -->
    <div class="mobile-category-drawer" id="navDrawer">
        <div class="drawer-header">
            <h5><i class="bi bi-list me-2"></i> Menu</h5>
            <button class="close-drawer" onclick="toggleNavDrawer()">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="drawer-body">
            <a href="/joinus" class="drawer-item">
                <div class="item-emoji">🤝</div>
                <div class="item-text">Join With Us</div>
            </a>
            <a href="{{ route('cart.index') }}" class="drawer-item">
                <div class="item-emoji">🛒</div>
                <div class="item-text">Cart</div>
            </a>
            @auth
                <form action="{{ route('logout') }}" method="POST" id="mobile-logout-form" style="display:none;">
                    @csrf
                </form>
                <a href="javascript:void(0)" onclick="document.getElementById('mobile-logout-form').submit();" class="drawer-item">
                    <div class="item-emoji">🚪</div>
                    <div class="item-text">Logout</div>
                </a>
            @else
                <a href="{{ route('login') }}" class="drawer-item" style="background: linear-gradient(135deg, var(--primary), var(--primary-light)); color: white;">
                    <div class="item-emoji">👤</div>
                    <div class="item-text">Login</div>
                </a>
            @endauth
        </div>
    </div>
<!-- ===== FOOTER SECTION ===== -->
<!-- Add this CSS to your existing <style> tag -->


<!-- Add this HTML before closing </body> tag, after all your content -->
<footer class="site-footer">
    <div class="container">
        <div class="row g-4">
            <!-- Brand Section -->
            <div class="col-lg-4 col-md-6">
                <div class="footer-brand">
                    <i class="bi bi-bag-check-fill"></i>
                    GrabBaskets
                </div>
                <p class="footer-tagline">
                    Your trusted partner for lightning-fast grocery delivery. 
                    Fresh products delivered to your doorstep in just 10 minutes!
                </p>
         <div class="social-links">

    <a href="https://wa.me/918300504230" class="social-link" aria-label="WhatsApp" target="_blank" rel="noopener noreferrer">
        <i class="bi bi-whatsapp"></i>
    </a>

    <a href="https://www.facebook.com/p/Swivel-Education-61573324123476/" class="social-link" aria-label="Facebook" target="_blank"rel="noopener noreferrer">
        <i class="bi bi-facebook"></i>
    </a>

    <a href="https://www.instagram.com/grab_baskets/"class="social-link" aria-label="Instagram" target="_blank" rel="noopener noreferrer">
        <i class="bi bi-instagram"></i>
    </a>

    <a href="https://youtube.com/@swivel-training?si=6AhKUo6pd7lpBNCw" class="social-link" aria-label="YouTube" target="_blank" rel="noopener noreferrer">
        <i class="bi bi-youtube"></i>
    </a>

    <a href="https://www.linkedin.com/in/jey-groups-2557933a3/"class="social-link" aria-label="LinkedIn" target="_blank" rel="noopener noreferrer">
        <i class="bi bi-linkedin"></i>
    </a>

</div>

            </div>

            <!-- Quick Links -->
            <div class="col-lg-2 col-md-6">
                <h5 class="footer-section-title">Quick Links</h5>
                <ul class="footer-links">
                    <li><a href="{{ route('home') }}"><i class="bi bi-chevron-right"></i> Home</a></li>
                    <li><a href="/tenmins"><i class="bi bi-chevron-right"></i> Ten-Mins-Delivery</a></li>
                    <li><a href="{{ route('categories.index') }}"><i class="bi bi-chevron-right"></i> Card</a></li>
                    <li><a href="{{ route('customer.food.index') }}"><i class="bi bi-chevron-right"></i> Food Delivery</a></li>
                    <li><a href="/joinus"><i class="bi bi-chevron-right"></i> Join With Us</a></li>
                </ul>
            </div>

            <!-- Customer Service -->
            <div class="col-lg-3 col-md-6">
                <h5 class="footer-section-title">Customer Service</h5>
                <ul class="footer-links">
                    <li><a href="#"><i class="bi bi-chevron-right"></i> Help Center</a></li>
                    <li><a href="#"><i class="bi bi-chevron-right"></i> Track Order</a></li>
                    <li><a href="#"><i class="bi bi-chevron-right"></i> Returns & Refunds</a></li>
                    <li><a href="#"><i class="bi bi-chevron-right"></i> Shipping Info</a></li>
                    <li><a href="#"><i class="bi bi-chevron-right"></i> FAQs</a></li>
                </ul>
            </div>

            <!-- Contact & Download -->
            <div class="col-lg-3 col-md-6">
                <h5 class="footer-section-title">Get In Touch</h5>
                <div class="contact-info">
                    <div class="contact-item">
                        <i class="bi bi-geo-alt-fill"></i>
                        <span>DLF IT Park,<br>Mount Poonamallee Road, Porur,<br>
                    Chennai, Tamil Nadu, 600116.</span>
                    </div>
                    <div class="contact-item">
                        <i class="bi bi-telephone-fill"></i>
                        <a href="tel:+911234567890">+91-830 050 4230</a>
                    </div>
                    <div class="contact-item">
                        <i class="bi bi-envelope-fill"></i>
                        <a href="mailto:admin@swivel.co.in">admin@swivel.co.in</a>
                    </div>
                </div>
                <div class="download-badges mt-4">
                    <a href="#" class="app-badge">
                        <i class="bi bi-google-play"></i>
                        <div class="app-badge-text">
                            <small>GET IT ON</small>
                            <strong>Google Play</strong>
                        </div>
                    </a>
                    <a href="#" class="app-badge">
                        <i class="bi bi-apple"></i>
                        <div class="app-badge-text">
                            <small>Download on the</small>
                            <strong>App Store</strong>
                        </div>
                    </a>
                </div>
            </div>
        </div>

        <!-- Footer Bottom -->
        <div class="footer-bottom">
            <div class="footer-bottom-content">
                <div class="copyright">
                    © 2025 GrabBaskets. All rights reserved.
                </div>
                <div class="footer-legal-links">
                    <a href="#">Privacy Policy</a>
                    <a href="#">Terms of Service</a>
                    <a href="#">Cookie Policy</a>
                    <a href="#">Sitemap</a>
                </div>
            </div>
        </div>
    </div>
</footer>
    <!-- Scripts -->
    <script>
        function toggleCategoryDrawer() {
            const drawer = document.getElementById('categoryDrawer');
            const overlay = document.getElementById('drawerOverlay');
            
            // Close nav drawer if open
            const navDrawer = document.getElementById('navDrawer');
            if (navDrawer.classList.contains('active')) {
                navDrawer.classList.remove('active');
            }

            drawer.classList.toggle('active');
            overlay.classList.toggle('active');
            
            if (drawer.classList.contains('active')) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = 'auto';
            }
        }

        function toggleNavDrawer() {
            const drawer = document.getElementById('navDrawer');
            const overlay = document.getElementById('drawerOverlay');
            
            // Close category drawer if open
            const catDrawer = document.getElementById('categoryDrawer');
            if (catDrawer.classList.contains('active')) {
                catDrawer.classList.remove('active');
            }

            drawer.classList.toggle('active');
            overlay.classList.toggle('active');
            
            if (drawer.classList.contains('active')) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = 'auto';
                overlay.classList.remove('active'); // Extra safety
            }
        }

        // Close all drawers when clicking overlay
        document.getElementById('drawerOverlay').onclick = function() {
            const navDrawer = document.getElementById('navDrawer');
            const catDrawer = document.getElementById('categoryDrawer');
            const overlay = document.getElementById('drawerOverlay');
            
            navDrawer.classList.remove('active');
            catDrawer.classList.remove('active');
            overlay.classList.remove('active');
            document.body.style.overflow = 'auto';
        };
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/location-delivery.js') }}"></script>
    <script>
        // Update Cart Logic (Handles Add, Increase, Decrease)
        function updateCart(productId, action) {
            @auth
                let quantityChange = 0;
                let currentQty = parseInt(document.querySelector(`#btn-container-${productId} .qty-val`)?.innerText || 0);

                if (action === 'add') {
                    quantityChange = 1;
                    currentQty = 1;
                } else if (action === 'increase') {
                    quantityChange = 1;
                    currentQty++;
                } else if (action === 'decrease') {
                    quantityChange = -1;
                    currentQty--;
                }

                // Optimistic UI Update
                renderProductButton(productId, currentQty);
                
                // Optimistic Badge Update
                 const mobileBadge = document.getElementById('mobile-cart-badge');
                 if (mobileBadge) {
                     let currentCount = parseInt(mobileBadge.innerText || 0);
                     let newCount = currentCount + quantityChange;
                     updateCartBadge(newCount);
                 }

                // Server Request
                fetch('{{ route('cart.add') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        product_id: productId,
                        quantity: quantityChange // delta
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.cart_count !== undefined) {
                        updateCartBadge(data.cart_count);
                    } else {
                        refreshCartCount(); 
                    }
                })
                .catch(error => console.error('Error:', error));
            @else
                window.location.href = '{{ route('login') }}';
            @endauth
        }

        // Render Button State (Add vs +/-)
        function renderProductButton(productId, qty) {
            const container = document.getElementById(`btn-container-${productId}`);
            if (!container) return;

            if (qty > 0) {
                container.innerHTML = `
                    <div class="qty-control" onclick="event.stopPropagation();">
                        <button class="qty-btn" onclick="updateCart(${productId}, 'decrease')">-</button>
                        <span class="qty-val">${qty}</span>
                        <button class="qty-btn" onclick="updateCart(${productId}, 'increase')">+</button>
                    </div>
                `;
            } else {
                container.innerHTML = `
                    <button class="add-btn" onclick="event.stopPropagation(); updateCart(${productId}, 'add')">
                        ADD
                    </button>
                `;
            }
        }

        function updateCartBadge(count) {
             const mobileBadge = document.getElementById('mobile-cart-badge');
             if (mobileBadge) {
                 mobileBadge.innerText = count;
                 mobileBadge.style.display = count > 0 ? 'inline-block' : 'none';
             }
        }

        function refreshCartCount() {
             fetch('{{ route('cart.index') }}');
        }

        // Desktop Backwards Compatibility
        function addToCart(productId) {
            updateCart(productId, 'add');
        }
    </script>

    <script>
        function getUserLocation() {
            if (!navigator.geolocation) {
                alert("Geolocation is not supported by your browser");
                return;
            }

            const textDisplays = document.querySelectorAll(".location-text-display");
            const subtextDisplays = document.querySelectorAll(".location-subtext-display");

            textDisplays.forEach(el => el.innerText = "Detecting...");
            subtextDisplays.forEach(el => el.innerText = "Please allow location access");

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;

                    textDisplays.forEach(el => el.innerText = "Your Location");
                    subtextDisplays.forEach(el => el.innerText = `Lat: ${lat.toFixed(4)}, Lng: ${lng.toFixed(4)}`);
                },
                () => {
                    textDisplays.forEach(el => el.innerText = "Location denied");
                    subtextDisplays.forEach(el => el.innerText = "Please enable location access");
                }
            );
        }
    </script>

</body>

</html>