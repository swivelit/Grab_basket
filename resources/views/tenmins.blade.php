<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta-name="viewport" content="width=device-width, initial-scale=1.0">
        <title>10 Mins Delivery</title>

        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap"
            rel="stylesheet">

        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

            body {
                font-family: 'Poppins', sans-serif;
                min-height: 100vh;
                display: flex;
                justify-content: center;
                align-items: center;
                position: relative;
                overflow: hidden;
            }

            /* BACKGROUND + BLUR */
            body::before {
                content: "";
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: url('https://images.unsplash.com/photo-1504754524776-8f4f37790ca0?auto=format&fit=crop&w=1600&q=80') center/cover no-repeat;
                filter: blur(6px) brightness(85%);
                z-index: -1;
                transform: scale(1.1);
                /* for smooth blur */
            }

            .main-wrapper {
                text-align: center;
                width: 100%;
            }

            .title {
                font-size: 46px;
                font-weight: 800;
                padding: 18px 40px;
                margin-bottom: 10px;
                background: rgba(255, 255, 255, 0.45);
                border: 2px solid rgba(0, 0, 0, 0.1);
                border-radius: 18px;
                color: #000;
                letter-spacing: 2px;
                backdrop-filter: blur(6px);
            }

            .subtitle {
                font-size: 22px;
                font-weight: 600;
                color: #000;
                margin-bottom: 35px;
                text-shadow: 0 2px 6px rgba(255, 255, 255, 0.6);
            }

            .category-container {
                display: flex;
                justify-content: center;
                align-items: center;
                gap: 40px;
                flex-wrap: wrap;
            }

            .cat-card {
                width: 220px;
                height: 200px;
                border-radius: 20px;
                overflow: hidden;
                cursor: pointer;
                position: relative;
                transition: 0.4s ease;
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            }

            .cat-card:hover img {
                filter: brightness(55%);
                transform: scale(1.12);
            }

            .cat-card img {
                width: 100%;
                height: 100%;
                object-fit: cover;
                filter: brightness(85%);
                transition: 0.4s ease-in-out;
            }

            .cat-layer {
                position: absolute;
                bottom: 15px;
                width: 100%;
                text-align: center;
            }

            .cat-layer h2 {
                font-size: 26px;
                font-weight: 700;
                color: #ffffff;
                text-shadow: 0 3px 12px rgba(0, 0, 0, 0.9);
            }

            .icon-badge {
                position: absolute;
                top: 10px;
                left: 10px;
                font-size: 15px;
                font-weight: 700;
                color: white;
                text-shadow: 0 3px 8px rgba(0, 0, 0, 0.9);
            }

            @media (max-width: 768px) {
                .title {
                    font-size: 32px;
                }

                .subtitle {
                    font-size: 18px;
                }

                .cat-card {
                    width: 50%;
                    height: 170px;
                }
            }
        </style>
</head>

<body>

    <div class="main-wrapper">

        <h1 class="title">GRABBASKETS DELIVERY</h1>

        <h2 class="subtitle">Enjoy Your Order – Fast & Fresh Delivery</h2>

        <div class="category-container">

            <div class="cat-card" onclick="goTo('/food/customer')">
                <span class="icon-badge">🍽</span>
                <img src="https://images.unsplash.com/photo-1540189549336-e6e99c3679fe?auto=format&fit=crop&w=800&q=80">
                <div class="cat-layer">
                    <h2>Food</h2>
                </div>
            </div>

            <div class="cat-card" onclick="goTo('/ten-min-products')">
                <span class="icon-badge">🥬</span>
                <img
                    src="https://cdn.apartmenttherapy.info/image/upload/v1559186495/k/archive/2d4ea32ed14a1f75cf1b454748dfa99cd4a1fa62.jpg">
                <div class="cat-layer">
                    <h2>Grocery</h2>
                </div>
            </div>

        </div>

    </div>

    <script>
        function goTo(page) {
            window.location.href = page;
        }
    </script>

</body>

</html>