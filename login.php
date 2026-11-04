<?php
session_start();

require_once "config/database.php";
require_once "config/functions.php";

if (isUserLoggedIn()) {
    header("Location: index.php");
    exit;
}

$error = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($email === "" || $password === "") {
        $error = "Please enter your email and password.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {

        $stmt = $conn->prepare(
            "SELECT id, name, email, password
             FROM users
             WHERE email = ?
             LIMIT 1"
        );

        if ($stmt) {

            $stmt->bind_param("s", $email);
            $stmt->execute();

            $result = $stmt->get_result();

            if ($result && $result->num_rows === 1) {

                $user = $result->fetch_assoc();

                if (password_verify($password, $user["password"])) {

                    session_regenerate_id(true);

                    $_SESSION["user_id"] = $user["id"];
                    $_SESSION["user_name"] = $user["name"];
                    $_SESSION["user_email"] = $user["email"];

                    header("Location: index.php");
                    exit;

                } else {
                    $error = "Incorrect password.";
                }

            } else {
                $error = "No account found with this email.";
            }

            $stmt->close();

        } else {
            $error = "Something went wrong. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login | Royal Paws</title>

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Segoe UI", Arial, sans-serif;
        }

        body {
            min-height: 100vh;
            background:
                radial-gradient(circle at top left, rgba(212,175,55,0.18), transparent 35%),
                radial-gradient(circle at bottom right, rgba(212,175,55,0.12), transparent 35%),
                linear-gradient(135deg, #120f0a, #211b12, #0d0b08);

            display: flex;
            align-items: center;
            justify-content: center;

            overflow-x: hidden;
            color: #fff;
        }

        /* Floating background icons */

        .pet {
            position: fixed;
            font-size: 40px;
            opacity: 0.08;
            animation: floatPet 7s infinite ease-in-out;
            pointer-events: none;
        }

        .pet1 {
            top: 10%;
            left: 8%;
        }

        .pet2 {
            top: 25%;
            right: 8%;
            animation-delay: 1s;
        }

        .pet3 {
            bottom: 15%;
            left: 12%;
            animation-delay: 2s;
        }

        .pet4 {
            bottom: 8%;
            right: 15%;
            animation-delay: 3s;
        }

        @keyframes floatPet {

            0%,100% {
                transform: translateY(0) rotate(0deg);
            }

            50% {
                transform: translateY(-25px) rotate(8deg);
            }
        }

        /* Main container */

        .login-container {
            width: 100%;
            max-width: 440px;
            padding: 20px;
            animation: showBox 0.8s ease;
        }

        @keyframes showBox {

            from {
                opacity: 0;
                transform: translateY(40px) scale(0.96);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .login-box {
            background: rgba(255,255,255,0.07);
            border: 1px solid rgba(212,175,55,0.35);

            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);

            border-radius: 25px;
            padding: 38px 35px;

            box-shadow:
                0 25px 70px rgba(0,0,0,0.55),
                inset 0 0 30px rgba(212,175,55,0.03);

            position: relative;
            overflow: hidden;
        }

        .login-box::before {
            content: "";
            position: absolute;
            width: 180px;
            height: 180px;

            background: rgba(212,175,55,0.08);

            border-radius: 50%;

            top: -90px;
            right: -90px;
        }

        .login-box::after {
            content: "";
            position: absolute;
            width: 120px;
            height: 120px;

            background: rgba(212,175,55,0.05);

            border-radius: 50%;

            bottom: -60px;
            left: -60px;
        }

        /* Logo */

        .logo-area {
            text-align: center;
            margin-bottom: 28px;
            position: relative;
            z-index: 2;
        }

        .logo {
            width: 85px;
            height: 85px;

            margin: auto;
            margin-bottom: 14px;

            border-radius: 50%;

            background: linear-gradient(
                135deg,
                #f7df8a,
                #d4af37,
                #9f7c18
            );

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 42px;

            box-shadow:
                0 0 25px rgba(212,175,55,0.35);

            animation: logoPulse 2.5s infinite;
        }

        @keyframes logoPulse {

            0%,100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.05);
            }
        }

        .logo-area h1 {
            font-size: 28px;
            color: #f7df8a;
            letter-spacing: 1px;
        }

        .logo-area p {
            color: #cfc7b3;
            font-size: 14px;
            margin-top: 6px;
        }

        /* Error */

        .error-box {
            background: rgba(220,53,69,0.15);
            border: 1px solid rgba(220,53,69,0.45);

            color: #ffb3b9;

            padding: 12px 15px;
            border-radius: 10px;

            margin-bottom: 20px;

            font-size: 14px;
            text-align: center;

            animation: errorShake 0.4s ease;
        }

        @keyframes errorShake {

            0% {
                transform: translateX(-5px);
            }

            25% {
                transform: translateX(5px);
            }

            50% {
                transform: translateX(-4px);
            }

            75% {
                transform: translateX(4px);
            }

            100% {
                transform: translateX(0);
            }
        }

        /* Form */

        .form-group {
            margin-bottom: 20px;
            position: relative;
            z-index: 2;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;

            color: #e9dfc7;
            font-size: 14px;
            font-weight: 600;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;

            left: 15px;
            top: 50%;

            transform: translateY(-50%);

            font-size: 18px;
            opacity: 0.8;
        }

        .form-control {
            width: 100%;

            padding: 14px 45px;

            border-radius: 12px;

            border: 1px solid rgba(255,255,255,0.15);

            background: rgba(0,0,0,0.25);

            color: #fff;

            outline: none;

            transition: 0.3s;
        }

        .form-control::placeholder {
            color: #9d9688;
        }

        .form-control:focus {
            border-color: #d4af37;

            box-shadow:
                0 0 0 3px rgba(212,175,55,0.12),
                0 0 20px rgba(212,175,55,0.08);
        }

        .password-toggle {
            position: absolute;

            right: 13px;
            top: 50%;

            transform: translateY(-50%);

            cursor: pointer;

            font-size: 17px;
            opacity: 0.7;

            user-select: none;
        }

        .password-toggle:hover {
            opacity: 1;
        }

        /* Button */

        .login-btn {
            width: 100%;

            border: none;
            border-radius: 12px;

            padding: 15px;

            margin-top: 5px;

            background:
                linear-gradient(
                    135deg,
                    #f4d675,
                    #d4af37,
                    #a77d16
                );

            color: #1b1509;

            font-size: 16px;
            font-weight: 800;

            cursor: pointer;

            transition: 0.3s;

            position: relative;
            z-index: 2;

            box-shadow:
                0 10px 25px rgba(212,175,55,0.18);
        }

        .login-btn:hover {
            transform: translateY(-3px);

            box-shadow:
                0 15px 30px rgba(212,175,55,0.3);
        }

        .login-btn:active {
            transform: translateY(0);
        }

        /* Register */

        .register-text {
            text-align: center;

            margin-top: 25px;

            color: #bdb5a4;

            font-size: 14px;

            position: relative;
            z-index: 2;
        }

        .register-text a {
            color: #f1cf67;

            text-decoration: none;

            font-weight: 700;

            transition: 0.3s;
        }

        .register-text a:hover {
            color: #fff0a9;
        }

        /* Home link */

        .home-link {
            display: block;

            text-align: center;

            margin-top: 18px;

            color: #aaa291;

            text-decoration: none;

            font-size: 13px;

            position: relative;
            z-index: 2;

            transition: 0.3s;
        }

        .home-link:hover {
            color: #f1cf67;
        }

        /* Loading */

        .loading {
            display: none;

            width: 18px;
            height: 18px;

            border: 3px solid rgba(0,0,0,0.2);
            border-top-color: #1b1509;

            border-radius: 50%;

            animation: spin 0.7s linear infinite;

            margin: auto;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* Mobile */

        @media(max-width:500px) {

            .login-container {
                padding: 15px;
            }

            .login-box {
                padding: 30px 22px;
                border-radius: 20px;
            }

            .logo-area h1 {
                font-size: 24px;
            }

            .pet {
                font-size: 30px;
            }
        }

    </style>
</head>

<body>

    <div class="pet pet1">🐶</div>
    <div class="pet pet2">🐱</div>
    <div class="pet pet3">🐦</div>
    <div class="pet pet4">🐠</div>

    <div class="login-container">

        <div class="login-box">

            <div class="logo-area">

                <div class="logo">
                    🐾
                </div>

                <h1>Royal Paws</h1>

                <p>Welcome back, pet lover!</p>

            </div>


            <?php if ($error !== ""): ?>

                <div class="error-box">
                    ⚠️ <?php echo e($error); ?>
                </div>

            <?php endif; ?>


            <form method="POST" id="loginForm">

                <div class="form-group">

                    <label for="email">
                        Email Address
                    </label>

                    <div class="input-wrapper">

                        <span class="input-icon">📧</span>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="form-control"
                            placeholder="Enter your email"
                            value="<?php echo e($email); ?>"
                            autocomplete="email"
                            required
                        >

                    </div>

                </div>


                <div class="form-group">

                    <label for="password">
                        Password
                    </label>

                    <div class="input-wrapper">

                        <span class="input-icon">🔒</span>

                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control"
                            placeholder="Enter your password"
                            autocomplete="current-password"
                            required
                        >

                        <span
                            class="password-toggle"
                            id="togglePassword"
                            title="Show password"
                        >
                            👁️
                        </span>

                    </div>

                </div>


                <button
                    type="submit"
                    class="login-btn"
                    id="loginBtn"
                >

                    <span id="btnText">
                        🔐 Login
                    </span>

                    <span
                        class="loading"
                        id="loading"
                    ></span>

                </button>

            </form>


            <div class="register-text">

                Don't have an account?

                <a href="register.php">
                    Create Account
                </a>

            </div>


            <a href="index.php" class="home-link">
                ← Back to Royal Paws
            </a>

        </div>

    </div>


    <script>

        /* Password show / hide */

        const passwordInput =
            document.getElementById("password");

        const togglePassword =
            document.getElementById("togglePassword");

        togglePassword.addEventListener("click", function () {

            if (passwordInput.type === "password") {

                passwordInput.type = "text";

                togglePassword.textContent = "🙈";

                togglePassword.title = "Hide password";

            } else {

                passwordInput.type = "password";

                togglePassword.textContent = "👁️";

                togglePassword.title = "Show password";
            }

        });


        /* Button loading animation */

        const loginForm =
            document.getElementById("loginForm");

        const loginBtn =
            document.getElementById("loginBtn");

        const btnText =
            document.getElementById("btnText");

        const loading =
            document.getElementById("loading");

        loginForm.addEventListener("submit", function () {

            loginBtn.disabled = true;

            btnText.style.display = "none";

            loading.style.display = "block";

        });

    </script>

</body>
</html>