<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Join With Us</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Outfit', sans-serif;
        }

        body {
            min-height: 100vh;
            background: radial-gradient(circle at top, #1e293b, #020617);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
        }

        .join-wrapper {
            width: 100%;
            max-width: 1200px;
            padding: 40px 20px;
        }

        .join-title {
            text-align: center;
            margin-bottom: 40px;
        }

        .join-title h1 {
            font-size: 42px;
            font-weight: 700;
            background: linear-gradient(90deg, #ff7a00, #ffb703);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .join-title i {
            margin-right: 10px;
        }

        .join-title p {
            margin-top: 8px;
            font-size: 15px;
            color: #cbd5f5;
        }

        .join-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 30px;
        }

        .card {
            border-radius: 22px;
            overflow: hidden;
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(18px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.35);
            transition: .4s ease;
        }

        .card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 30px 70px rgba(255, 140, 0, .35);
        }

        .card img {
            width: 100%;
            height: 170px;
            object-fit: contain;
            filter: brightness(.9);
        }

        .card-body {
            padding: 22px;
            text-align: center;
        }

        .card-body i {
            font-size: 38px;
            color: #ffb703;
            margin-bottom: 12px;
        }

        .card-body h3 {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .card-body p {
            font-size: 14px;
            color: #e5e7eb;
            margin-bottom: 18px;
            line-height: 1.5;
        }

        .join-btn {
            padding: 10px 30px;
            border: none;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            color: #020617;
            background: linear-gradient(135deg, #ffb703, #ff7a00);
            box-shadow: 0 10px 25px rgba(255, 183, 3, .5);
            transition: .3s ease;
        }

        .join-btn:hover {
            transform: scale(1.08);
            box-shadow: 0 15px 35px rgba(255, 183, 3, .8);
        }

        @media(max-width:600px) {
            .join-title h1 {
                font-size: 32px;
            }
        }
    </style>
</head>

<body>

    <div class="join-wrapper">

        <div class="join-title">
            <h1><i class="fa-solid fa-handshake"></i>Join With GrabBaskets</h1>
            <p>Become a part of our growing partner ecosystem</p>
        </div>

        <div class="join-cards">

            <!-- Product Seller -->
            <div class="card">
                <img src="https://images.unsplash.com/photo-1606787366850-de6330128bfc" alt="Grocery & Vegetables">
                <div class="card-body">
                    <i class="fa-solid fa-store"></i>
                    <h3>Product Seller</h3>
                    <p>Sell groceries, vegetables and products to nearby customers.</p>
                    <button class="join-btn" onclick="window.location.href='/register'">Join Now</button>
                </div>
            </div>

            <!-- Delivery Partner -->
            <div class="card">
                <img src="https://img.freepik.com/free-vector/man-riding-scooter-white-background_1308-46379.jpg?semt=ais_hybrid&w=740&q=80"
                    alt="Delivery Partner Bike">
                <div class="card-body">
                    <i class="fa-solid fa-motorcycle"></i>
                    <h3>Delivery Partner</h3>
                    <p>Deliver orders using your bike and earn flexibly.</p>
                    <button class="join-btn" onclick="window.location.href='delivery-partner/register'">Join
                        Now</button>
                </div>
            </div>

            <!-- Hotel Owner -->
            <div class="card">
                <img src="https://images.unsplash.com/photo-1552566626-52f8b828add9" alt="Restaurant Food">
                <div class="card-body">
                    <i class="fa-solid fa-utensils"></i>
                    <h3>Hotel Owner</h3>
                    <p>Partner your restaurant and grow online food orders.</p>
                    <button class="join-btn" onclick="window.location.href='/hotel-owner/login'">Join Now</button>
                </div>
            </div>

        </div>
    </div>



</body>

</html>