<?php

require_once "../config/database.php";

/*
|--------------------------------------------------------------------------
| ADMIN ACCOUNT CREATION
|--------------------------------------------------------------------------
*/

$name = "Royal Paws Admin";
$username = "admin";
$password = "admin123";

/*
|--------------------------------------------------------------------------
| CHECK WHETHER ADMIN ALREADY EXISTS
|--------------------------------------------------------------------------
*/

$check = $conn->prepare("
    SELECT id
    FROM admins
    WHERE username = ?
    LIMIT 1
");

$check->bind_param("s", $username);
$check->execute();

$result = $check->get_result();

/*
|--------------------------------------------------------------------------
| CREATE ADMIN
|--------------------------------------------------------------------------
*/

if ($result->num_rows > 0) {

    echo "
    <!DOCTYPE html>
    <html>
    <head>
        <title>Admin Already Exists</title>

        <style>
            body {
                margin: 0;
                min-height: 100vh;
                display: flex;
                justify-content: center;
                align-items: center;
                background: #080808;
                font-family: Arial, sans-serif;
                color: white;
            }

            .box {
                width: 90%;
                max-width: 500px;
                padding: 40px;
                text-align: center;
                background: #151515;
                border: 1px solid #d4af37;
                border-radius: 20px;
                box-shadow: 0 0 35px rgba(212,175,55,0.15);
            }

            h1 {
                color: #d4af37;
            }

            p {
                color: #ccc;
                line-height: 1.7;
            }

            a {
                display: inline-block;
                margin-top: 20px;
                padding: 12px 25px;
                background: #d4af37;
                color: #111;
                text-decoration: none;
                border-radius: 8px;
                font-weight: bold;
            }
        </style>
    </head>

    <body>

        <div class='box'>

            <h1>⚠️ Admin Already Exists</h1>

            <p>
                Admin username <b>admin</b> already exists
                in the database.
            </p>

            <p>
                Please use the existing admin account.
            </p>

            <a href='login.php'>
                Go To Admin Login
            </a>

        </div>

    </body>
    </html>
    ";

    exit;
}

/*
|--------------------------------------------------------------------------
| HASH PASSWORD
|--------------------------------------------------------------------------
*/

$hashedPassword = password_hash(
    $password,
    PASSWORD_DEFAULT
);

/*
|--------------------------------------------------------------------------
| INSERT ADMIN
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    INSERT INTO admins
    (
        name,
        username,
        password
    )
    VALUES
    (?, ?, ?)
");

$stmt->bind_param(
    "sss",
    $name,
    $username,
    $hashedPassword
);

/*
|--------------------------------------------------------------------------
| RESULT
|--------------------------------------------------------------------------
*/

if ($stmt->execute()) {

    echo "
    <!DOCTYPE html>
    <html>

    <head>

        <title>Admin Created</title>

        <style>

            * {
                box-sizing: border-box;
                font-family: Arial, sans-serif;
            }

            body {

                margin: 0;

                min-height: 100vh;

                display: flex;

                justify-content: center;

                align-items: center;

                background:
                    radial-gradient(
                        circle at top left,
                        rgba(212,175,55,0.15),
                        transparent 30%
                    ),
                    #080808;

                color: white;
            }

            .box {

                width: 90%;

                max-width: 500px;

                padding: 45px 35px;

                text-align: center;

                background: #151515;

                border: 1px solid #d4af37;

                border-radius: 22px;

                box-shadow:
                    0 0 40px
                    rgba(212,175,55,0.15);

                animation: showBox 0.7s ease;
            }

            @keyframes showBox {

                from {
                    opacity: 0;
                    transform: translateY(30px) scale(0.95);
                }

                to {
                    opacity: 1;
                    transform: translateY(0) scale(1);
                }

            }

            .icon {

                font-size: 60px;

                margin-bottom: 15px;
            }

            h1 {

                color: #d4af37;

                margin-bottom: 20px;
            }

            .details {

                margin-top: 25px;

                padding: 20px;

                background: #0d0d0d;

                border-radius: 12px;

                border: 1px solid #292929;

                text-align: left;
            }

            .details p {

                margin: 12px 0;

                color: #ccc;
            }

            .details strong {

                color: #d4af37;
            }

            .login-btn {

                display: inline-block;

                margin-top: 25px;

                padding: 13px 28px;

                background:
                    linear-gradient(
                        135deg,
                        #d4af37,
                        #f4d77d
                    );

                color: #111;

                text-decoration: none;

                border-radius: 9px;

                font-weight: bold;

                transition: 0.3s;
            }

            .login-btn:hover {

                transform: translateY(-2px);

                box-shadow:
                    0 10px 25px
                    rgba(212,175,55,0.25);
            }

            .warning {

                margin-top: 25px;

                color: #ff8585;

                font-size: 13px;

                line-height: 1.6;
            }

        </style>

    </head>

    <body>

        <div class='box'>

            <div class='icon'>
                🐾
            </div>

            <h1>
                Admin Created Successfully!
            </h1>

            <p>
                Royal Paws admin account has been
                created successfully.
            </p>

            <div class='details'>

                <p>
                    <strong>Name:</strong>
                    Royal Paws Admin
                </p>

                <p>
                    <strong>Username:</strong>
                    admin
                </p>

                <p>
                    <strong>Password:</strong>
                    admin123
                </p>

            </div>

            <a
                href='login.php'
                class='login-btn'
            >
                🔐 Go To Admin Login
            </a>

            <div class='warning'>

                ⚠️ For security, delete
                <b>create_admin.php</b>
                after creating the admin account.

            </div>

        </div>

    </body>

    </html>
    ";

} else {

    echo "
    <!DOCTYPE html>
    <html>

    <head>

        <title>Admin Creation Failed</title>

        <style>

            body {

                margin: 0;

                min-height: 100vh;

                display: flex;

                justify-content: center;

                align-items: center;

                background: #080808;

                font-family: Arial, sans-serif;

                color: white;
            }

            .box {

                width: 90%;

                max-width: 500px;

                padding: 40px;

                text-align: center;

                background: #151515;

                border: 1px solid #e74c3c;

                border-radius: 20px;
            }

            h1 {
                color: #ff6b6b;
            }

            p {
                color: #ccc;
            }

        </style>

    </head>

    <body>

        <div class='box'>

            <h1>
                ❌ Admin Creation Failed
            </h1>

            <p>
                " . htmlspecialchars($stmt->error) . "
            </p>

        </div>

    </body>

    </html>
    ";
}

$stmt->close();
$check->close();
$conn->close();

?>