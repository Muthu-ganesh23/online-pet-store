<?php
require_once "config/database.php";
require_once "config/functions.php";

if (isUserLoggedIn()) {
    header("Location: index.php");
    exit;
}

$error = "";

$name = "";
$email = "";
$phone = "";
$address = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $address = trim($_POST["address"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    if ($name === "" || $email === "" || $password === "") {

        $error = "Please fill all required fields.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    } elseif (strlen($name) < 2) {

        $error = "Name must contain at least 2 characters.";

    } elseif (strlen($password) < 6) {

        $error = "Password must contain at least 6 characters.";

    } elseif ($password !== $confirm_password) {

        $error = "Passwords do not match.";

    } else {

        $check = $conn->prepare(
            "SELECT id FROM users WHERE email = ? LIMIT 1"
        );

        if ($check) {

            $check->bind_param("s", $email);
            $check->execute();

            $result = $check->get_result();

            if ($result->num_rows > 0) {

                $error = "An account already exists with this email.";

            } else {

                $hashed_password = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                $stmt = $conn->prepare(
                    "INSERT INTO users
                    (name, email, phone, password, address)
                    VALUES (?, ?, ?, ?, ?)"
                );

                if ($stmt) {

                    $stmt->bind_param(
                        "sssss",
                        $name,
                        $email,
                        $phone,
                        $hashed_password,
                        $address
                    );

                    if ($stmt->execute()) {

                        header("Location: login.php?registered=1");
                        exit;

                    } else {

                        $error = "Registration failed. Please try again.";
                    }

                    $stmt->close();

                } else {

                    $error = "Unable to create account right now.";
                }
            }

            $check->close();

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

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Create Account | Royal Paws</title>

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
                radial-gradient(
                    circle at top left,
                    rgba(212,175,55,0.18),
                    transparent 35%
                ),
                radial-gradient(
                    circle at bottom right,
                    rgba(212,175,55,0.12),
                    transparent 35%
                ),
                linear-gradient(
                    135deg,
                    #120f0a,
                    #211b12,
                    #0d0b08
                );

            color: #fff;

            display: flex;
            align-items: center;
            justify-content: center;

            padding: 30px 15px;

            overflow-x: hidden;
        }

        /* Floating Pets */

        .pet {

            position: fixed;

            font-size: 42px;

            opacity: 0.08;

            pointer-events: none;

            animation:
                floatPet 7s infinite ease-in-out;
        }

        .pet1 {
            top: 8%;
            left: 7%;
        }

        .pet2 {
            top: 20%;
            right: 7%;
            animation-delay: 1s;
        }

        .pet3 {
            bottom: 12%;
            left: 10%;
            animation-delay: 2s;
        }

        .pet4 {
            bottom: 7%;
            right: 12%;
            animation-delay: 3s;
        }

        @keyframes floatPet {

            0%, 100% {
                transform:
                    translateY(0)
                    rotate(0deg);
            }

            50% {
                transform:
                    translateY(-25px)
                    rotate(8deg);
            }
        }

        /* Main */

        .register-container {

            width: 100%;
            max-width: 520px;

            animation:
                showBox 0.8s ease;
        }

        @keyframes showBox {

            from {
                opacity: 0;
                transform:
                    translateY(40px)
                    scale(0.96);
            }

            to {
                opacity: 1;
                transform:
                    translateY(0)
                    scale(1);
            }
        }

        /* Box */

        .register-box {

            position: relative;

            background:
                rgba(255,255,255,0.07);

            border:
                1px solid
                rgba(212,175,55,0.35);

            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);

            border-radius: 25px;

            padding: 35px;

            box-shadow:
                0 25px 70px
                rgba(0,0,0,0.55);

            overflow: hidden;
        }

        .register-box::before {

            content: "";

            position: absolute;

            width: 180px;
            height: 180px;

            background:
                rgba(212,175,55,0.07);

            border-radius: 50%;

            top: -90px;
            right: -90px;
        }

        .register-box::after {

            content: "";

            position: absolute;

            width: 130px;
            height: 130px;

            background:
                rgba(212,175,55,0.05);

            border-radius: 50%;

            bottom: -65px;
            left: -65px;
        }

        /* Logo */

        .logo-area {

            text-align: center;

            margin-bottom: 25px;

            position: relative;
            z-index: 2;
        }

        .logo {

            width: 78px;
            height: 78px;

            margin: auto;
            margin-bottom: 12px;

            border-radius: 50%;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 38px;

            background:
                linear-gradient(
                    135deg,
                    #f7df8a,
                    #d4af37,
                    #9f7c18
                );

            box-shadow:
                0 0 25px
                rgba(212,175,55,0.35);

            animation:
                logoPulse 2.5s infinite;
        }

        @keyframes logoPulse {

            0%, 100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.05);
            }
        }

        .logo-area h1 {

            color: #f7df8a;

            font-size: 27px;

            letter-spacing: 1px;
        }

        .logo-area p {

            color: #cfc7b3;

            font-size: 14px;

            margin-top: 5px;
        }

        /* Error */

        .error-box {

            background:
                rgba(220,53,69,0.15);

            border:
                1px solid
                rgba(220,53,69,0.45);

            color: #ffb3b9;

            padding: 12px 15px;

            border-radius: 10px;

            margin-bottom: 20px;

            font-size: 14px;

            text-align: center;

            animation:
                errorShake 0.4s ease;
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

        .form-row {

            display: grid;

            grid-template-columns:
                1fr 1fr;

            gap: 15px;
        }

        .form-group {

            margin-bottom: 17px;

            position: relative;
            z-index: 2;
        }

        .form-group label {

            display: block;

            margin-bottom: 7px;

            color: #e9dfc7;

            font-size: 13px;

            font-weight: 600;
        }

        .input-wrapper {

            position: relative;
        }

        .input-icon {

            position: absolute;

            left: 14px;
            top: 50%;

            transform:
                translateY(-50%);

            font-size: 17px;

            opacity: 0.8;
        }

        .form-control {

            width: 100%;

            padding:
                13px 43px;

            border-radius: 11px;

            border:
                1px solid
                rgba(255,255,255,0.15);

            background:
                rgba(0,0,0,0.25);

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
                0 0 0 3px
                rgba(212,175,55,0.12);
        }

        textarea.form-control {

            min-height: 90px;

            resize: vertical;

            padding-left: 15px;
        }

        /* Password toggle */

        .password-toggle {

            position: absolute;

            right: 13px;
            top: 50%;

            transform:
                translateY(-50%);

            cursor: pointer;

            opacity: 0.7;

            user-select: none;
        }

        .password-toggle:hover {
            opacity: 1;
        }

        /* Password strength */

        .strength-container {

            margin-top: 7px;
        }

        .strength-bar {

            height: 5px;

            width: 100%;

            background:
                rgba(255,255,255,0.1);

            border-radius: 10px;

            overflow: hidden;
        }

        .strength-fill {

            height: 100%;

            width: 0%;

            transition: 0.3s;

            border-radius: 10px;
        }

        .strength-text {

            font-size: 11px;

            color: #aaa291;

            margin-top: 4px;
        }

        /* Button */

        .register-btn {

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
                0 10px 25px
                rgba(212,175,55,0.18);
        }

        .register-btn:hover {

            transform:
                translateY(-3px);

            box-shadow:
                0 15px 30px
                rgba(212,175,55,0.3);
        }

        .register-btn:disabled {

            opacity: 0.7;

            cursor: not-allowed;

            transform: none;
        }

        /* Bottom */

        .login-text {

            text-align: center;

            margin-top: 22px;

            color: #bdb5a4;

            font-size: 14px;

            position: relative;
            z-index: 2;
        }

        .login-text a {

            color: #f1cf67;

            text-decoration: none;

            font-weight: 700;
        }

        .login-text a:hover {
            color: #fff0a9;
        }

        .home-link {

            display: block;

            text-align: center;

            margin-top: 15px;

            color: #aaa291;

            text-decoration: none;

            font-size: 13px;

            position: relative;
            z-index: 2;
        }

        .home-link:hover {
            color: #f1cf67;
        }

        /* Loading */

        .loading {

            display: none;

            width: 18px;
            height: 18px;

            border:
                3px solid
                rgba(0,0,0,0.2);

            border-top-color:
                #1b1509;

            border-radius: 50%;

            animation:
                spin 0.7s linear infinite;

            margin: auto;
        }

        @keyframes spin {

            to {
                transform: rotate(360deg);
            }
        }

        /* Mobile */

        @media(max-width:600px) {

            .register-box {
                padding: 27px 20px;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .pet {
                font-size: 30px;
            }

            .logo-area h1 {
                font-size: 24px;
            }
        }

    </style>

</head>

<body>

    <div class="pet pet1">🐶</div>
    <div class="pet pet2">🐱</div>
    <div class="pet pet3">🐦</div>
    <div class="pet pet4">🐠</div>


    <div class="register-container">

        <div class="register-box">

            <div class="logo-area">

                <div class="logo">
                    🐾
                </div>

                <h1>Join Royal Paws</h1>

                <p>Create your pet lover account</p>

            </div>


            <?php if ($error !== ""): ?>

                <div class="error-box">

                    ⚠️
                    <?php echo e($error); ?>

                </div>

            <?php endif; ?>


            <form
                method="POST"
                id="registerForm"
                novalidate
            >

                <!-- Name + Phone -->

                <div class="form-row">

                    <div class="form-group">

                        <label for="name">
                            Full Name *
                        </label>

                        <div class="input-wrapper">

                            <span class="input-icon">
                                👤
                            </span>

                            <input
                                type="text"
                                id="name"
                                name="name"
                                class="form-control"
                                placeholder="Your name"
                                value="<?php echo e($name); ?>"
                                autocomplete="name"
                                required
                            >

                        </div>

                    </div>


                    <div class="form-group">

                        <label for="phone">
                            Phone Number
                        </label>

                        <div class="input-wrapper">

                            <span class="input-icon">
                                📱
                            </span>

                            <input
                                type="tel"
                                id="phone"
                                name="phone"
                                class="form-control"
                                placeholder="Phone number"
                                value="<?php echo e($phone); ?>"
                                autocomplete="tel"
                            >

                        </div>

                    </div>

                </div>


                <!-- Email -->

                <div class="form-group">

                    <label for="email">
                        Email Address *
                    </label>

                    <div class="input-wrapper">

                        <span class="input-icon">
                            📧
                        </span>

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


                <!-- Password -->

                <div class="form-group">

                    <label for="password">
                        Password *
                    </label>

                    <div class="input-wrapper">

                        <span class="input-icon">
                            🔒
                        </span>

                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control"
                            placeholder="Minimum 6 characters"
                            autocomplete="new-password"
                            required
                        >

                        <span
                            class="password-toggle"
                            id="togglePassword"
                        >
                            👁️
                        </span>

                    </div>


                    <div class="strength-container">

                        <div class="strength-bar">

                            <div
                                class="strength-fill"
                                id="strengthFill"
                            ></div>

                        </div>

                        <div
                            class="strength-text"
                            id="strengthText"
                        >
                            Password strength
                        </div>

                    </div>

                </div>


                <!-- Confirm Password -->

                <div class="form-group">

                    <label for="confirm_password">
                        Confirm Password *
                    </label>

                    <div class="input-wrapper">

                        <span class="input-icon">
                            🔐
                        </span>

                        <input
                            type="password"
                            id="confirm_password"
                            name="confirm_password"
                            class="form-control"
                            placeholder="Re-enter password"
                            autocomplete="new-password"
                            required
                        >

                        <span
                            class="password-toggle"
                            id="toggleConfirm"
                        >
                            👁️
                        </span>

                    </div>

                </div>


                <!-- Address -->

                <div class="form-group">

                    <label for="address">
                        Address
                    </label>

                    <textarea
                        id="address"
                        name="address"
                        class="form-control"
                        placeholder="Enter your delivery address"
                    ><?php echo e($address); ?></textarea>

                </div>


                <!-- Submit -->

                <button
                    type="submit"
                    class="register-btn"
                    id="registerBtn"
                >

                    <span id="btnText">
                        ✨ Create Account
                    </span>

                    <span
                        class="loading"
                        id="loading"
                    ></span>

                </button>

            </form>


            <div class="login-text">

                Already have an account?

                <a href="login.php">
                    Login
                </a>

            </div>


            <a
                href="index.php"
                class="home-link"
            >
                ← Back to Royal Paws
            </a>

        </div>

    </div>


    <script>

        /* Password toggle */

        const password =
            document.getElementById("password");

        const confirmPassword =
            document.getElementById("confirm_password");

        const togglePassword =
            document.getElementById("togglePassword");

        const toggleConfirm =
            document.getElementById("toggleConfirm");


        togglePassword.addEventListener(
            "click",
            function () {

                if (password.type === "password") {

                    password.type = "text";
                    togglePassword.textContent = "🙈";

                } else {

                    password.type = "password";
                    togglePassword.textContent = "👁️";

                }

            }
        );


        toggleConfirm.addEventListener(
            "click",
            function () {

                if (confirmPassword.type === "password") {

                    confirmPassword.type = "text";
                    toggleConfirm.textContent = "🙈";

                } else {

                    confirmPassword.type = "password";
                    toggleConfirm.textContent = "👁️";

                }

            }
        );


        /* Password strength */

        const strengthFill =
            document.getElementById("strengthFill");

        const strengthText =
            document.getElementById("strengthText");


        password.addEventListener(
            "input",
            function () {

                const value = password.value;

                let strength = 0;

                if (value.length >= 6) {
                    strength++;
                }

                if (/[A-Z]/.test(value)) {
                    strength++;
                }

                if (/[0-9]/.test(value)) {
                    strength++;
                }

                if (/[^A-Za-z0-9]/.test(value)) {
                    strength++;
                }


                if (value.length === 0) {

                    strengthFill.style.width = "0%";

                    strengthText.textContent =
                        "Password strength";

                } else if (strength <= 1) {

                    strengthFill.style.width = "25%";

                    strengthText.textContent =
                        "Weak password";

                } else if (strength === 2) {

                    strengthFill.style.width = "50%";

                    strengthText.textContent =
                        "Medium password";

                } else if (strength === 3) {

                    strengthFill.style.width = "75%";

                    strengthText.textContent =
                        "Good password";

                } else {

                    strengthFill.style.width = "100%";

                    strengthText.textContent =
                        "Strong password";
                }

            }
        );


        /* Client-side password confirmation */

        const registerForm =
            document.getElementById("registerForm");

        const registerBtn =
            document.getElementById("registerBtn");

        const btnText =
            document.getElementById("btnText");

        const loading =
            document.getElementById("loading");


        registerForm.addEventListener(
            "submit",
            function (event) {

                if (
                    password.value !==
                    confirmPassword.value
                ) {

                    event.preventDefault();

                    alert(
                        "Passwords do not match."
                    );

                    confirmPassword.focus();

                    return;
                }


                if (password.value.length < 6) {

                    event.preventDefault();

                    alert(
                        "Password must contain at least 6 characters."
                    );

                    password.focus();

                    return;
                }


                registerBtn.disabled = true;

                btnText.style.display = "none";

                loading.style.display = "block";

            }
        );

    </script>

</body>

</html>