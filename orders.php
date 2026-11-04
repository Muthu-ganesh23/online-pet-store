<?php
require_once "config/database.php";
require_once "config/functions.php";

requireUser();

$userId = (int)$_SESSION['user_id'];

/* Get all orders of logged-in user */
$stmt = $conn->prepare("
    SELECT
        id,
        total_amount,
        payment_method,
        payment_status,
        order_status,
        shipping_name,
        shipping_phone,
        shipping_address,
        order_date
    FROM orders
    WHERE user_id = ?
    ORDER BY order_date DESC
");

$stmt->bind_param("i", $userId);
$stmt->execute();

$ordersResult = $stmt->get_result();

/* Function for order status step */
function statusStep($status) {

    $steps = [
        "Pending" => 1,
        "Confirmed" => 2,
        "Processing" => 3,
        "Shipped" => 4,
        "Delivered" => 5
    ];

    return $steps[$status] ?? 1;
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

    <title>My Orders | Royal Paws</title>

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
                    rgba(212,175,55,0.14),
                    transparent 30%
                ),
                radial-gradient(
                    circle at bottom right,
                    rgba(212,175,55,0.10),
                    transparent 30%
                ),
                #090909;
            color: #fff;
        }

        /* NAVBAR */

        .navbar {
            position: sticky;
            top: 0;
            z-index: 1000;

            background: rgba(10,10,10,0.96);
            border-bottom: 1px solid rgba(212,175,55,0.25);

            padding: 15px 5%;

            display: flex;
            justify-content: space-between;
            align-items: center;

            backdrop-filter: blur(12px);
        }

        .logo {
            text-decoration: none;
            color: #d4af37;
            font-size: 25px;
            font-weight: bold;
        }

        .logo span {
            color: #fff;
        }

        .nav-links {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .nav-links a {
            text-decoration: none;
            color: #ddd;
            transition: 0.3s;
        }

        .nav-links a:hover {
            color: #d4af37;
        }

        .cart-btn {
            padding: 9px 15px;
            border: 1px solid #d4af37;
            border-radius: 8px;
            color: #d4af37 !important;
        }

        /* CONTAINER */

        .container {
            width: 92%;
            max-width: 1100px;
            margin: auto;
            padding: 45px 0;
        }

        .page-title {
            text-align: center;
            margin-bottom: 35px;
        }

        .page-title h1 {
            color: #d4af37;
            font-size: 38px;
            margin-bottom: 8px;
        }

        .page-title p {
            color: #aaa;
        }

        /* ORDER CARD */

        .order-card {
            background: rgba(20,20,20,0.96);
            border: 1px solid rgba(212,175,55,0.25);
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 25px;

            box-shadow:
                0 15px 45px rgba(0,0,0,0.45);

            animation: orderIn 0.6s ease;
        }

        @keyframes orderIn {

            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }

        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;

            padding-bottom: 18px;
            border-bottom: 1px solid #292929;
        }

        .order-id {
            color: #d4af37;
            font-size: 21px;
            font-weight: bold;
        }

        .order-date {
            color: #999;
            font-size: 14px;
        }

        /* STATUS */

        .status-badge {
            display: inline-block;
            padding: 7px 14px;
            border-radius: 20px;

            background: rgba(212,175,55,0.12);
            border: 1px solid rgba(212,175,55,0.35);

            color: #d4af37;
            font-size: 13px;
            font-weight: bold;
        }

        .status-badge.cancelled {
            color: #ff6b6b;
            border-color: rgba(255,107,107,0.4);
            background: rgba(255,107,107,0.08);
        }

        .status-badge.delivered {
            color: #5ee68a;
            border-color: rgba(94,230,138,0.35);
            background: rgba(94,230,138,0.08);
        }

        /* TRACKING */

        .tracking {
            margin: 28px 0;
        }

        .tracking-title {
            color: #ddd;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .tracking-line {
            display: grid;
            grid-template-columns:
                repeat(5, 1fr);

            position: relative;
        }

        .tracking-line::before {
            content: "";
            position: absolute;

            top: 17px;
            left: 10%;
            right: 10%;

            height: 3px;
            background: #333;

            z-index: 0;
        }

        .tracking-step {
            text-align: center;
            position: relative;
            z-index: 1;
        }

        .circle {
            width: 35px;
            height: 35px;

            margin: auto;

            border-radius: 50%;

            display: flex;
            align-items: center;
            justify-content: center;

            background: #222;
            border: 2px solid #555;

            color: #777;
            font-weight: bold;
        }

        .tracking-step.active .circle {
            background: #d4af37;
            border-color: #d4af37;
            color: #111;

            box-shadow:
                0 0 18px rgba(212,175,55,0.3);
        }

        .tracking-step span {
            display: block;
            margin-top: 9px;
            color: #777;
            font-size: 12px;
        }

        .tracking-step.active span {
            color: #d4af37;
        }

        /* ORDER INFO */

        .info-grid {
            display: grid;
            grid-template-columns:
                repeat(3, 1fr);

            gap: 15px;

            margin-top: 25px;
        }

        .info-box {
            background: #151515;
            border: 1px solid #292929;
            border-radius: 13px;
            padding: 17px;
        }

        .info-box h3 {
            color: #d4af37;
            font-size: 14px;
            margin-bottom: 8px;
        }

        .info-box p {
            color: #ddd;
            line-height: 1.5;
            font-size: 14px;
        }

        /* ITEMS */

        .items-title {
            color: #d4af37;
            margin: 25px 0 12px;
            font-size: 18px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
        }

        .items-table th,
        .items-table td {
            padding: 11px;
            border-bottom: 1px solid #292929;
            text-align: left;
        }

        .items-table th {
            color: #d4af37;
            font-size: 13px;
        }

        .items-table td {
            color: #ddd;
            font-size: 14px;
        }

        /* TOTAL */

        .order-total {
            display: flex;
            justify-content: flex-end;
            align-items: center;

            gap: 15px;

            margin-top: 20px;
            padding-top: 18px;

            border-top: 1px solid #292929;
        }

        .order-total span {
            color: #aaa;
        }

        .order-total strong {
            color: #d4af37;
            font-size: 25px;
        }

        /* EMPTY */

        .empty {
            text-align: center;
            padding: 70px 20px;

            background: #141414;
            border: 1px solid #292929;
            border-radius: 20px;
        }

        .empty-icon {
            font-size: 60px;
            margin-bottom: 15px;
        }

        .empty h2 {
            color: #d4af37;
            margin-bottom: 10px;
        }

        .empty p {
            color: #999;
            margin-bottom: 25px;
        }

        .shop-btn {
            display: inline-block;
            text-decoration: none;

            padding: 12px 22px;

            border-radius: 9px;

            background:
                linear-gradient(
                    135deg,
                    #d4af37,
                    #f4d77d
                );

            color: #111;
            font-weight: bold;

            transition: 0.3s;
        }

        .shop-btn:hover {
            transform: translateY(-3px);
            box-shadow:
                0 8px 25px rgba(212,175,55,0.25);
        }

        /* MOBILE */

        @media(max-width: 750px) {

            .nav-links {
                gap: 10px;
            }

            .nav-links a:not(.cart-btn) {
                display: none;
            }

            .page-title h1 {
                font-size: 30px;
            }

            .order-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .tracking-line {
                overflow-x: auto;
                min-width: 600px;
            }

            .tracking {
                overflow-x: auto;
            }

            .items-table {
                min-width: 600px;
            }

            .order-card {
                overflow-x: auto;
            }

            .order-total {
                justify-content: space-between;
            }
        }

    </style>

</head>

<body>

<!-- NAVBAR -->

<nav class="navbar">

    <a href="index.php" class="logo">
        🐾 <span>Royal</span> Paws
    </a>

    <div class="nav-links">

        <a href="index.php">
            Home
        </a>

        <a href="products.php">
            Products
        </a>

        <a href="categories.php">
            Categories
        </a>

        <a href="cart.php" class="cart-btn">
            🛒 Cart
        </a>

    </div>

</nav>


<div class="container">

    <div class="page-title">

        <h1>📦 My Orders</h1>

        <p>
            Track your Royal Paws orders and delivery status
        </p>

    </div>


    <?php if ($ordersResult->num_rows > 0): ?>

        <?php while ($order = $ordersResult->fetch_assoc()): ?>

            <?php

            $currentStep =
                statusStep($order['order_status']);

            $orderId =
                (int)$order['id'];

            /*
             * Get items for this order
             */

            $itemStmt = $conn->prepare("
                SELECT
                    product_name,
                    quantity,
                    selling_price,
                    subtotal
                FROM order_items
                WHERE order_id = ?
                ORDER BY id ASC
            ");

            $itemStmt->bind_param(
                "i",
                $orderId
            );

            $itemStmt->execute();

            $itemsResult =
                $itemStmt->get_result();

            ?>

            <div class="order-card">

                <!-- ORDER HEADER -->

                <div class="order-header">

                    <div>

                        <div class="order-id">
                            Order #<?php
                                echo e($order['id']);
                            ?>
                        </div>

                        <div class="order-date">

                            <?php
                            echo date(
                                "d M Y, h:i A",
                                strtotime(
                                    $order['order_date']
                                )
                            );
                            ?>

                        </div>

                    </div>


                    <div>

                        <?php if (
                            $order['order_status']
                            === "Delivered"
                        ): ?>

                            <span class="status-badge delivered">
                                ✓ Delivered
                            </span>

                        <?php elseif (
                            $order['order_status']
                            === "Cancelled"
                        ): ?>

                            <span class="status-badge cancelled">
                                ✕ Cancelled
                            </span>

                        <?php else: ?>

                            <span class="status-badge">
                                <?php
                                echo e(
                                    $order['order_status']
                                );
                                ?>
                            </span>

                        <?php endif; ?>

                    </div>

                </div>


                <!-- TRACKING -->

                <?php if (
                    $order['order_status']
                    !== "Cancelled"
                ): ?>

                    <div class="tracking">

                        <div class="tracking-title">
                            🚚 Order Tracking
                        </div>

                        <div class="tracking-line">

                            <?php

                            $trackingSteps = [
                                "Pending",
                                "Confirmed",
                                "Processing",
                                "Shipped",
                                "Delivered"
                            ];

                            foreach (
                                $trackingSteps
                                as $index => $step
                            ):

                                $stepNumber =
                                    $index + 1;

                                $active =
                                    $stepNumber
                                    <= $currentStep;

                            ?>

                                <div
                                    class="tracking-step
                                    <?php
                                    echo $active
                                        ? 'active'
                                        : '';
                                    ?>"
                                >

                                    <div class="circle">

                                        <?php
                                        echo $active
                                            ? "✓"
                                            : $stepNumber;
                                        ?>

                                    </div>

                                    <span>
                                        <?php
                                        echo e($step);
                                        ?>
                                    </span>

                                </div>

                            <?php endforeach; ?>

                        </div>

                    </div>

                <?php else: ?>

                    <div
                        style="
                        margin-top:20px;
                        padding:15px;
                        border-radius:10px;
                        background:rgba(255,107,107,0.08);
                        border:1px solid rgba(255,107,107,0.25);
                        color:#ff8c8c;
                        "
                    >
                        This order has been cancelled.
                    </div>

                <?php endif; ?>


                <!-- ORDER INFO -->

                <div class="info-grid">

                    <div class="info-box">

                        <h3>👤 Customer</h3>

                        <p>
                            <?php
                            echo e(
                                $order['shipping_name']
                            );
                            ?>

                            <br>

                            <?php
                            echo e(
                                $order['shipping_phone']
                            );
                            ?>
                        </p>

                    </div>


                    <div class="info-box">

                        <h3>💳 Payment</h3>

                        <p>

                            Method:
                            <?php
                            echo e(
                                $order['payment_method']
                            );
                            ?>

                            <br>

                            Status:
                            <?php
                            echo e(
                                $order['payment_status']
                            );
                            ?>

                        </p>

                    </div>


                    <div class="info-box">

                        <h3>📍 Delivery Address</h3>

                        <p>
                            <?php
                            echo nl2br(
                                e(
                                    $order[
                                        'shipping_address'
                                    ]
                                )
                            );
                            ?>
                        </p>

                    </div>

                </div>


                <!-- ITEMS -->

                <div class="items-title">
                    🛍️ Ordered Products
                </div>


                <div style="overflow-x:auto;">

                    <table class="items-table">

                        <thead>

                            <tr>

                                <th>
                                    Product
                                </th>

                                <th>
                                    Quantity
                                </th>

                                <th>
                                    Price
                                </th>

                                <th>
                                    Subtotal
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                            <?php while (
                                $item =
                                $itemsResult->fetch_assoc()
                            ): ?>

                                <tr>

                                    <td>
                                        <?php
                                        echo e(
                                            $item[
                                                'product_name'
                                            ]
                                        );
                                        ?>
                                    </td>

                                    <td>
                                        <?php
                                        echo e(
                                            $item['quantity']
                                        );
                                        ?>
                                    </td>

                                    <td>
                                        <?php
                                        echo money(
                                            $item[
                                                'selling_price'
                                            ]
                                        );
                                        ?>
                                    </td>

                                    <td>
                                        <?php
                                        echo money(
                                            $item['subtotal']
                                        );
                                        ?>
                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        </tbody>

                    </table>

                </div>


                <!-- TOTAL -->

                <div class="order-total">

                    <span>
                        Total Amount
                    </span>

                    <strong>
                        <?php
                        echo money(
                            $order['total_amount']
                        );
                        ?>
                    </strong>

                </div>

            </div>

        <?php endwhile; ?>

    <?php else: ?>

        <!-- NO ORDERS -->

        <div class="empty">

            <div class="empty-icon">
                🐾
            </div>

            <h2>
                No Orders Yet
            </h2>

            <p>
                You haven't placed any orders yet.
                Start shopping for your pets!
            </p>

            <a
                href="products.php"
                class="shop-btn"
            >
                🛍️ Start Shopping
            </a>

        </div>

    <?php endif; ?>

</div>


<script>

    /* Small page animation */

    window.addEventListener(
        "load",
        function () {

            const cards =
                document.querySelectorAll(
                    ".order-card"
                );

            cards.forEach(
                function(card, index) {

                    card.style.animationDelay =
                        (index * 0.1) + "s";

                }
            );

        }
    );

</script>

</body>
</html>