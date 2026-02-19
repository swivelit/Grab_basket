<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>GrabBasket Login</title>

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <style>
    * {
      margin: 0;
      padding: 0;
      font-family: "Poppins", sans-serif;
      box-sizing: border-box;
    }

    body {
      background: linear-gradient(140deg, #eaf1ff, #ffffff);
      height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
    }

    .container {
      text-align: center;
      animation: fadeIn 0.8s ease-in-out;
    }

    .logo {
      font-size: 36px;
      margin-bottom: 15px;
      font-weight: 800;
    }

    .login-box {
      background: #fff;
      padding: 50px 45px;
      border-radius: 22px;
      width: 420px;
      box-shadow: 0px 12px 35px rgba(0, 0, 0, 0.15);
      animation: slideUp 0.9s ease-in-out;
    }

    h3 {
      margin-bottom: 20px;
      font-size: 26px;
      color: #2a2a2a;
      font-weight: 800;
    }

    .sub-note {
      font-size: 16px;
      color: #6b6b6b;
      margin-top: -5px;
      margin-bottom: 25px;
      font-style: italic;
    }

    .input-group {
      position: relative;
      margin-bottom: 25px;
    }

    .input-group input {
      width: 100%;
      padding: 16px 50px;
      border-radius: 14px;
      border: 1.8px solid #c8c8c8;
      outline: none;
      font-size: 17px;
    }

    .input-group input::placeholder {
      font-size: 16px;
    }

    .input-group input:focus {
      border-color: #002b7a;
      box-shadow: 0 0 12px rgba(0, 43, 122, 0.28);
    }

    .icon {
      position: absolute;
      top: 17px;
      left: 15px;
      font-size: 18px;
      color: #666;
    }

    .eye {
      position: absolute;
      right: 15px;
      top: 17px;
      font-size: 18px;
      cursor: pointer;
    }

    .error-text {
      font-size: 14px;
      color: #d60000;
      text-align: left;
      margin-top: -12px;
      margin-bottom: 12px;
      display: none;
    }

    .extra-options {
      display: flex;
      justify-content: space-between;
      margin-bottom: 25px;
      font-size: 16px;
    }

    .extra-options input {
      transform: scale(1.2);
    }

    #loginBtn {
      width: 100%;
      padding: 16px;
      background: linear-gradient(90deg, #003366, #0056b3);
      border: none;
      border-radius: 14px;
      color: white;
      font-size: 18px;
      cursor: pointer;
      font-weight: 700;
      transition: 0.3s ease-in-out;
    }

    #loginBtn:hover {
      transform: translateY(-2px);
      box-shadow: 0px 8px 20px rgba(0, 43, 122, 0.4);
    }

    /* SIGN UP SECTION */
    .signup-text {
      margin-top: 25px;
      font-size: 17px;
      color: #333;
    }

    .signup-link {
      color: #0056b3;
      font-weight: 700;
      text-decoration: none;
    }

    .signup-link:hover {
      color: #003d80;
      text-decoration: underline;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
      }

      to {
        opacity: 1;
      }
    }

    @keyframes slideUp {
      from {
        transform: translateY(30px);
        opacity: 0;
      }

      to {
        transform: translateY(0);
        opacity: 1;
      }
    }
  </style>
</head>

<body>

  <div class="container">

    <div class="logo">
      Grab<span style="color:#ff7b00;">Baskets</span>
    </div>

    <form method="POST" action="{{ route('login') }}" id="loginForm">
      @csrf

      <div class="login-box">

        <h3>Login to Your Account</h3>
        <p class="sub-note">✨ Grow your business with GrabBasket ✨</p>

        @error('login')
        <div class="text-danger small mt-1">{{ $message }}</div>
        @enderror

        @if(session('error'))
        <p class="error-text" style="display:block;">⚠ {{ session('error') }}</p>
        @endif

        <div class="input-group">
          <i class="fa-solid fa-envelope icon"></i>
          <input type="text" id="email" name="login" placeholder="Email or Phone" />
        </div>
        <p id="emailError" class="error-text">⚠ Please enter a valid email or 10-digit phone.</p>

        <div class="input-group">
          <i class="fa-solid fa-lock icon"></i>
          <input type="password" id="password" name="password" placeholder="Password" />
          <i class="fa-solid fa-eye-slash eye" id="togglePassword"></i>
        </div>
        <p id="passError" class="error-text">⚠ Password must be at least 6 characters.</p>

        <div class="extra-options">
          <label><input type="checkbox" name="remember" /> Remember me</label>
          <a href="{{ route('password.request') }}">Forgot Password?</a>
        </div>

        <button id="loginBtn" type="submit">Log In</button>

        <!-- SIGNUP LINK ADDED -->
        <p class="signup-text">
          Don’t have an account?
          <a href="{{ route('register') }}" class="signup-link">Create one</a>
        </p>

      </div>
    </form>

  </div>

  <script>
    // Toggle password
    document.getElementById("togglePassword").addEventListener("click", () => {
      const password = document.getElementById("password");
      const icon = document.getElementById("togglePassword");

      password.type = password.type === "password" ? "text" : "password";
      icon.classList.toggle("fa-eye");
      icon.classList.toggle("fa-eye-slash");
    });

    // Validation
    document.getElementById("loginForm").addEventListener("submit", function(e) {
      let valid = true;

      const email = document.getElementById("email").value.trim();
      const pass = document.getElementById("password").value.trim();

      const emailPattern = /^[^ ]+@[^ ]+\.[a-z]{2,6}$/;
      const phonePattern = /^[0-9]{10}$/;

      if (!emailPattern.test(email) && !phonePattern.test(email)) {
        valid = false;
        document.getElementById("emailError").style.display = "block";
      } else {
        document.getElementById("emailError").style.display = "none";
      }

      if (pass.length < 6) {
        valid = false;
        document.getElementById("passError").style.display = "block";
      } else {
        document.getElementById("passError").style.display = "none";
      }

      if (!valid) e.preventDefault();
    });
  </script>

</body>

</html>
