<?php
require_once "config/database.php";
require_once "config/functions.php";

requireUser();

$userId = (int)$_SESSION['user_id'];
$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($orderId <= 0) {
    redirect("index.php");
}

/* Get order only for logged-in user */
$stmt = $conn->prepare("
    SELECT 
        o.id,
        o.total_amount,
        o.payment_method,
        o.payment_status,
        o.order_status,
        o.shipping_name,
        o.shipping_phone,
        o.shipping_address,
        o.order_date
    FROM orders o
    WHERE o.id = ? AND o.user_id = ?
    LIMIT 1
");

$stmt->bind_param("ii", $orderId, $userId);
$stmt->execute();

$result = $stmt->get_result();
$order = $result->fetch_assoc();

if (!$order) {
    redirect("index.php");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Order Successful | Royal Paws</title>

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
                radial-gradient(circle at top left, rgba(212,175,55,0.15), transparent 30%),
                radial-gradient(circle at bottom right, rgba(212,175,55,0.12), transparent 30%),
                #0b0b0b;
            color: #fff;
            overflow-x: hidden;
        }

        /* Background Glow */

        .glow {
            position: fixed;
            width: 250px;
            height: 250px;
            border-radius: 50%;
            background: rgba(212,175,55,0.08);
            filter: blur(70px);
            z-index: 0;
        }

        .glow.one {
            top: 5%;
            left: 5%;
        }

        .glow.two {
            bottom: 5%;
            right: 5%;
        }

        /* Main */

        .container {
            width: 92%;
            max-width: 1000px;
            margin: auto;
            padding: 50px 0;
            position: relative;
            z-index: 2;
        }

        /* Success Card */

        .success-card {
            background: rgba(20,20,20,0.95);
            border: 1px solid rgba(212,175,55,0.4);
            border-radius: 25px;
            padding: 45px;
            box-shadow:
                0 20px 60px rgba(0,0,0,0.6),
                0 0 35px rgba(212,175,55,0.08);
            text-align: center;
            animation: cardIn 0.8s ease;
        }

        @keyframes cardIn {
            from {
                opacity: 0;
                transform: translateY(40px) scale(0.96);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        /* Success Icon */

        .success-icon {
            width: 110px;
            height: 110px;
            margin: 0 auto 25px;
            border-radius: 50%;
            border: 4px solid #d4af37;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 55px;
            color: #d4af37;
            animation: pop 0.8s ease;
            box-shadow: 0 0 35px rgba(212,175,55,0.25);
        }

        @keyframes pop {
            0% {
                transform: scale(0);
            }

            70% {
                transform: scale(1.15);
            }

            100% {
                transform: scale(1);
            }
        }

        h1 {
            color: #d4af37;
            font-size: 38px;
            margin-bottom: 10px;
        }

        .subtitle {
            color: #ccc;
            font-size: 17px;
            margin-bottom: 30px;
        }

        .order-number {
            display: inline-block;
            padding: 12px 22px;
            border-radius: 30px;
            background: rgba(212,175,55,0.1);
            border: 1px solid rgba(212,175,55,0.4);
            color: #d4af37;
            font-weight: bold;
            margin-bottom: 35px;
        }

        /* Details */

        .details-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-top: 20px;
            text-align: left;
        }

        .detail-box {
            background: #151515;
            border: 1px solid #2d2d2d;
            border-radius: 16px;
            padding: 20px;
            transition: 0.3s;
        }

        .detail-box:hover {
            border-color: #d4af37;
            transform: translateY(-3px);
        }

        .detail-box h3 {
            color: #d4af37;
            margin-bottom: 10px;
            font-size: 16px;
        }

        .detail-box p {
            color: #ddd;
            line-height: 1.6;
        }

        .total-box {
            margin-top: 25px;
            padding: 22px;
            border-radius: 16px;
            background: linear-gradient(
                135deg,
                rgba(212,175,55,0.12),
                rgba(212,175,55,0.03)
            );
            border: 1px solid rgba(212,175,55,0.4);
        }

        .total-box span {
            color: #aaa;
            font-size: 15px;
        }

        .total-amount {
            color: #d4af37;
            font-size: 32px;
            font-weight: bold;
            margin-top: 5px;
        }

        /* Status */

        .status {
            display: inline-block;
            padding: 8px 15px;
            border-radius: 20px;
            background: rgba(46,204,113,0.12);
            color: #5ee68a;
            border: 1px solid rgba(46,204,113,0.3);
            font-size: 14px;
            font-weight: bold;
        }

        /* Buttons */

        .buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            flex-wrap: wrap;
            margin-top: 35px;
        }

        .btn {
            text-decoration: none;
            padding: 13px 24px;
            border-radius: 10px;
            font-weight: bold;
            transition: 0.3s;
            display: inline-block;
        }

        .btn-primary {
            background: linear-gradient(135deg, #d4af37, #f4d77d);
            color: #111;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(212,175,55,0.25);
        }

        .btn-secondary {
            border: 1px solid #d4af37;
            color: #d4af37;
        }

        .btn-secondary:hover {
            background: #d4af37;
            color: #111;
        }

        /* Floating Icons */

        .pet {
            position: fixed;
            font-size: 30px;
            opacity: 0.12;
            animation: float 5s infinite ease-in-out;
            z-index: 1;
        }

        .pet1 {
            left: 5%;
            top: 20%;
        }

        .pet2 {
            right: 7%;
            top: 35%;
            animation-delay: 1s;
        }

        .pet3 {
            left: 10%;
            bottom: 15%;
            animation-delay: 2s;
        }

        .pet4 {
            right: 12%;
            bottom: 20%;
            animation-delay: 3s;
        }

        @keyframes float {
            0%,100% {
                transform: translateY(0) rotate(0deg);
            }

            50% {
                transform: translateY(-20px) rotate(8deg);
            }
        }

        /* Confetti */

        .confetti {
            position: fixed;
            width: 9px;
            height: 14px;
            top: -20px;
            z-index: 9999;
            animation: fall linear forwards;
        }

        @keyframes fall {
            0% {
                transform: translateY(0) rotate(0deg);
                opacity: 1;
            }

            100% {
                transform: translateY(110vh) rotate(720deg);
                opacity: 0;
            }
        }

        /* Mobile */

        @media (max-width: 700px) {

            .container {
                padding: 25px 0;
            }

            .success-card {
                padding: 25px 18px;
            }

            h1 {
                font-size: 28px;
            }

            .success-icon {
                width: 90px;
                height: 90px;
                font-size: 45px;
            }

            .details-grid {
                grid-template-columns: 1fr;
            }

            .total-amount {
                font-size: 27px;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
</head>

<body>

<div class="glow one"></div>
<div class="glow two"></div>

<!-- Floating Pet Icons -->

<div class="pet pet1">🐶</div>
<div class="pet pet2">🐱</div>
<div class="pet pet3">🐦</div>
<div class="pet pet4">🐠</div>

<div class="container">

    <div class="success-card">

        <div class="success-icon">
            ✓
        </div>

        <h1>Order Placed Successfully!</h1>

        <p class="subtitle">
            Thank you for shopping with Royal Paws 🐾
        </p>

        <div class="order-number">
            Order ID: #<?php echo e($order['id']); ?>
        </div>

        <div class="details-grid">

            <div class="detail-box">
                <h3>👤 Customer</h3>

                <p>
                    <?php echo e($order['shipping_name']); ?><br>
                    <?php echo e($order['shipping_phone']); ?>
                </p>
            </div>

            <div class="detail-box">
                <h3>💳 Payment</h3>

                <p>
                    Method:
                    <strong>
                        <?php echo e($order['payment_method']); ?>
                    </strong>
                    <br>

                    Payment Status:
                    <span class="status">
                        <?php echo e($order['payment_status']); ?>
                    </span>
                </p>
            </div>

            <div class="detail-box">
                <h3>📦 Order Status</h3>

                <p>
                    <span class="status">
                        <?php echo e($order['order_status']); ?>
                    </span>
                </p>
            </div>

            <div class="detail-box">
                <h3>📅 Order Date</h3>

                <p>
                    <?php
                    echo date(
                        "d M Y, h:i A",
                        strtotime($order['order_date'])
                    );
                    ?>
                </p>
            </div>

        </div>

        <div class="detail-box" style="margin-top:20px;text-align:left;">

            <h3>📍 Delivery Address</h3>

            <p>
                <?php echo nl2br(e($order['shipping_address'])); ?>
            </p>

        </div>

        <div class="total-box">

            <span>Total Amount</span>

            <div class="total-amount">
                <?php echo money($order['total_amount']); ?>
            </div>

        </div>

        <div class="buttons">

            <a href="orders.php" class="btn btn-primary">
                📦 My Orders
            </a>

            <a href="index.php" class="btn btn-secondary">
                🛍️ Continue Shopping
            </a>

        </div>

    </div>

</div>

<script>

    /* Confetti Animation */

    function createConfetti() {

        const pieces = 100;

        for (let i = 0; i < pieces; i++) {

            const confetti = document.createElement("div");

            confetti.className = "confetti";

            confetti.style.left =
                Math.random() * 100 + "vw";

            confetti.style.animationDuration =
                (Math.random() * 3 + 2) + "s";

            confetti.style.animationDelay =
                Math.random() * 1.5 + "s";

            confetti.style.background =
                [
                    "#d4af37",
                    "#f4d77d",
                    "#ffffff",
                    "#e67e22",
                    "#5ee68a",
                    "#3498db"
                ][Math.floor(Math.random() * 6)];

            confetti.style.transform =
                "rotate(" + Math.random() * 360 + "deg)";

            document.body.appendChild(confetti);

            setTimeout(() => {
                confetti.remove();
            }, 6000);
        }
    }

    /* Start celebration */

    window.addEventListener("load", function () {
        createConfetti();

        setTimeout(() => {
            createConfetti();
        }, 1200);
    });

</script>

</body>
</html>