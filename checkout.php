<?php

require_once "config/database.php";
require_once "config/functions.php";

requireUser();

$user_id = (int) $_SESSION["user_id"];

/*
|--------------------------------------------------------------------------
| ROYAL PAWS UPI QR SETTINGS
|--------------------------------------------------------------------------
| Replace this with your REAL UPI ID.
*/
$merchant_upi_id = "YOUR_REAL_UPI_ID@upi";
$merchant_name = "Royal Paws";

$error = "";

/*
|--------------------------------------------------------------------------
| Get User Details
|--------------------------------------------------------------------------
*/

$user = null;

$stmt = $conn->prepare(
    "SELECT id, name, email, phone, address
     FROM users
     WHERE id = ?
     LIMIT 1"
);

if ($stmt) {

    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
    }

    $stmt->close();
}

if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Get Cart
|--------------------------------------------------------------------------
*/

$cart_items = [];

$stmt = $conn->prepare(
    "SELECT
        c.id AS cart_id,
        c.product_id,
        c.quantity,
        p.name,
        p.description,
        p.cost_price,
        p.selling_price,
        p.stock,
        p.image,
        p.status
     FROM cart c
     INNER JOIN products p
     ON c.product_id = p.id
     WHERE c.user_id = ?
     ORDER BY c.created_at ASC"
);

if ($stmt) {

    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $cart_items[] = $row;
    }

    $stmt->close();
}


/*
|--------------------------------------------------------------------------
| Empty Cart
|--------------------------------------------------------------------------
*/

if (empty($cart_items)) {

    header("Location: cart.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Calculate Total
|--------------------------------------------------------------------------
*/

$subtotal = 0;
$total_items = 0;

foreach ($cart_items as $item) {

    $subtotal +=
        (float)$item["selling_price"]
        * (int)$item["quantity"];

    $total_items +=
        (int)$item["quantity"];
}

$delivery_charge = 0;

if ($subtotal > 0 && $subtotal < 1000) {
    $delivery_charge = 50;
}

$grand_total =
    $subtotal + $delivery_charge;


/*
|--------------------------------------------------------------------------
| Form Defaults
|--------------------------------------------------------------------------
*/

$shipping_name =
    $user["name"] ?? "";

$shipping_phone =
    $user["phone"] ?? "";

$shipping_address =
    $user["address"] ?? "";

$payment_method = "COD";


/*
|--------------------------------------------------------------------------
| Place Order
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $shipping_name =
        trim($_POST["shipping_name"] ?? "");

    $shipping_phone =
        trim($_POST["shipping_phone"] ?? "");

    $shipping_address =
        trim($_POST["shipping_address"] ?? "");

    $payment_method =
        strtoupper(
            trim($_POST["payment_method"] ?? "COD")
        );

    $card_number =
        preg_replace(
            "/\s+/",
            "",
            $_POST["card_number"] ?? ""
        );

    $card_name =
        trim($_POST["card_name"] ?? "");

    $expiry =
        trim($_POST["expiry"] ?? "");

    $cvv =
        trim($_POST["cvv"] ?? "");

    $upi_id =
        trim($_POST["upi_id"] ?? "");


    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if (
        $shipping_name === "" ||
        $shipping_phone === "" ||
        $shipping_address === ""
    ) {

        $error =
            "Please fill all delivery details.";

    } elseif (!in_array(
        $payment_method,
        ["COD", "UPI", "CARD"],
        true
    )) {

        $error =
            "Please select a valid payment method.";

    } elseif (
        !preg_match(
            "/^[0-9]{10}$/",
            preg_replace(
                "/\D/",
                "",
                $shipping_phone
            )
        )
    ) {

        $error =
            "Please enter a valid 10-digit phone number.";

    } elseif (
        $payment_method === "UPI" &&
        $upi_id === ""
    ) {

        $error =
            "Please enter your UPI ID.";

    } elseif (
        $payment_method === "CARD" &&
        (
            $card_number === "" ||
            $card_name === "" ||
            $expiry === "" ||
            $cvv === ""
        )
    ) {

        $error =
            "Please fill all card details.";

    } elseif (
        $payment_method === "CARD" &&
        !preg_match(
            "/^[0-9]{16}$/",
            $card_number
        )
    ) {

        $error =
            "Please enter a valid 16-digit card number.";

    } elseif (
        $payment_method === "CARD" &&
        !preg_match(
            "/^[0-9]{3,4}$/",
            $cvv
        )
    ) {

        $error =
            "Please enter a valid CVV.";

    } else {

        /*
        |--------------------------------------------------------------------------
        | Re-check Stock Before Order
        |--------------------------------------------------------------------------
        */

        $stock_error = "";

        foreach ($cart_items as $item) {

            if (
                (int)$item["stock"] <
                (int)$item["quantity"]
            ) {

                $stock_error =
                    $item["name"] .
                    " does not have enough stock.";

                break;
            }

            if (
                $item["status"] !== "active"
            ) {

                $stock_error =
                    $item["name"] .
                    " is currently unavailable.";

                break;
            }
        }


        if ($stock_error !== "") {

            $error = $stock_error;

        } else {

            /*
            |--------------------------------------------------------------------------
            | Payment Status
            |--------------------------------------------------------------------------
            */

            if ($payment_method === "COD") {

                $payment_status = "Pending";

            } else {

                /*
                | Demo project payment flow.
                | UPI/Card selection is recorded as Paid.
                */

                $payment_status = "Paid";
            }


            /*
            |--------------------------------------------------------------------------
            | Transaction Start
            |--------------------------------------------------------------------------
            */

            $conn->begin_transaction();

            try {

                /*
                |--------------------------------------------------------------------------
                | Insert Order
                |--------------------------------------------------------------------------
                */

                $stmt = $conn->prepare(
                    "INSERT INTO orders
                    (
                        user_id,
                        total_amount,
                        payment_method,
                        payment_status,
                        order_status,
                        shipping_name,
                        shipping_phone,
                        shipping_address
                    )
                    VALUES (?, ?, ?, ?, 'Confirmed', ?, ?, ?)"
                );

                if (!$stmt) {
                    throw new Exception(
                        "Unable to create order."
                    );
                }


                $stmt->bind_param(
                    "idsssss",
                    $user_id,
                    $grand_total,
                    $payment_method,
                    $payment_status,
                    $shipping_name,
                    $shipping_phone,
                    $shipping_address
                );


                if (!$stmt->execute()) {

                    throw new Exception(
                        "Order creation failed."
                    );
                }


                $order_id =
                    (int)$conn->insert_id;

                $stmt->close();


                /*
                |--------------------------------------------------------------------------
                | Insert Order Items
                |--------------------------------------------------------------------------
                */

                $item_stmt = $conn->prepare(
                    "INSERT INTO order_items
                    (
                        order_id,
                        product_id,
                        product_name,
                        quantity,
                        cost_price,
                        selling_price,
                        subtotal
                    )
                    VALUES (?, ?, ?, ?, ?, ?, ?)"
                );


                if (!$item_stmt) {

                    throw new Exception(
                        "Unable to save order items."
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | Update Stock
                |--------------------------------------------------------------------------
                */

                $stock_stmt = $conn->prepare(
                    "UPDATE products
                     SET stock = stock - ?
                     WHERE id = ?
                     AND stock >= ?"
                );


                if (!$stock_stmt) {

                    throw new Exception(
                        "Unable to update stock."
                    );
                }


                foreach ($cart_items as $item) {

                    $product_id =
                        (int)$item["product_id"];

                    $product_name =
                        $item["name"];

                    $quantity =
                        (int)$item["quantity"];

                    $cost_price =
                        (float)$item["cost_price"];

                    $selling_price =
                        (float)$item["selling_price"];

                    $item_subtotal =
                        $selling_price * $quantity;


                    /*
                    | Insert item
                    */

                    $item_stmt->bind_param(
                        "iisiddd",
                        $order_id,
                        $product_id,
                        $product_name,
                        $quantity,
                        $cost_price,
                        $selling_price,
                        $item_subtotal
                    );


                    if (!$item_stmt->execute()) {

                        throw new Exception(
                            "Unable to save product details."
                        );
                    }


                    /*
                    | Reduce stock
                    */

                    $stock_stmt->bind_param(
                        "iii",
                        $quantity,
                        $product_id,
                        $quantity
                    );


                    if (!$stock_stmt->execute()) {

                        throw new Exception(
                            "Unable to update product stock."
                        );
                    }


                    if (
                        $stock_stmt->affected_rows !== 1
                    ) {

                        throw new Exception(
                            "Stock changed. Please try again."
                        );
                    }
                }


                $item_stmt->close();
                $stock_stmt->close();


                /*
                |--------------------------------------------------------------------------
                | Clear Cart
                |--------------------------------------------------------------------------
                */

                $clear_stmt = $conn->prepare(
                    "DELETE FROM cart
                     WHERE user_id = ?"
                );


                if (!$clear_stmt) {

                    throw new Exception(
                        "Unable to clear cart."
                    );
                }


                $clear_stmt->bind_param(
                    "i",
                    $user_id
                );

                $clear_stmt->execute();

                $clear_stmt->close();


                /*
                |--------------------------------------------------------------------------
                | Commit
                |--------------------------------------------------------------------------
                */

                $conn->commit();


                /*
                |--------------------------------------------------------------------------
                | Redirect
                |--------------------------------------------------------------------------
                */

                header(
                    "Location: order_success.php?id=" .
                    $order_id
                );

                exit;


            } catch (Throwable $e) {

                /*
                |--------------------------------------------------------------------------
                | Rollback
                |--------------------------------------------------------------------------
                */

                $conn->rollback();

                $error =
                    "Unable to place your order. Please try again.";
            }
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

    <title>Checkout | Royal Paws</title>


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
                    rgba(212,175,55,0.13),
                    transparent 35%
                ),
                linear-gradient(
                    135deg,
                    #100d08,
                    #211b11,
                    #0d0b08
                );

            color: #fff;
        }


        /* =====================================================
           NAVBAR
        ===================================================== */

        .navbar {

            height: 72px;

            padding: 0 6%;

            display: flex;

            align-items: center;

            justify-content: space-between;

            background:
                rgba(14,11,7,0.94);

            border-bottom:
                1px solid
                rgba(212,175,55,0.22);

            position: sticky;

            top: 0;

            z-index: 100;

            backdrop-filter: blur(12px);
        }


        .brand {

            display: flex;

            align-items: center;

            gap: 10px;

            text-decoration: none;

            color: #f5d875;

            font-size: 22px;

            font-weight: 800;
        }


        .brand-logo {

            width: 42px;

            height: 42px;

            border-radius: 50%;

            display: flex;

            align-items: center;

            justify-content: center;

            background:
                linear-gradient(
                    135deg,
                    #f7df8a,
                    #d4af37,
                    #9f7c18
                );

            color: #201808;

            font-size: 22px;
        }


        .nav-links {

            display: flex;

            gap: 22px;

            align-items: center;
        }


        .nav-links a {

            color: #cfc7b6;

            text-decoration: none;

            font-size: 14px;

            transition: 0.3s;
        }


        .nav-links a:hover {

            color: #f5d875;
        }


        .user-name {

            color: #f1df9a;

            font-size: 13px;
        }


        /* =====================================================
           MAIN
        ===================================================== */

        .page {

            width: 92%;

            max-width: 1200px;

            margin: 42px auto 70px;
        }


        .page-title {

            margin-bottom: 30px;
        }


        .page-title h1 {

            color: #f5d875;

            font-size: 35px;

            margin-bottom: 7px;
        }


        .page-title p {

            color: #aaa291;

            font-size: 14px;
        }


        /* =====================================================
           ERROR
        ===================================================== */

        .error-box {

            padding: 14px 18px;

            margin-bottom: 25px;

            border-radius: 12px;

            background:
                rgba(220,53,69,0.13);

            border:
                1px solid
                rgba(220,53,69,0.4);

            color: #ffb6bc;

            animation:
                shake 0.4s ease;
        }


        @keyframes shake {

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


        /* =====================================================
           LAYOUT
        ===================================================== */

        .checkout-layout {

            display: grid;

            grid-template-columns:
                1fr 370px;

            gap: 25px;

            align-items: start;
        }


        .card {

            background:
                rgba(255,255,255,0.055);

            border:
                1px solid
                rgba(212,175,55,0.22);

            border-radius: 20px;

            padding: 25px;

            backdrop-filter: blur(12px);

            box-shadow:
                0 20px 50px
                rgba(0,0,0,0.25);
        }


        .card-title {

            color: #f5d875;

            font-size: 21px;

            margin-bottom: 22px;
        }


        /* =====================================================
           FORM
        ===================================================== */

        .form-group {

            margin-bottom: 18px;
        }


        .form-group label {

            display: block;

            color: #e6ddca;

            font-size: 13px;

            font-weight: 600;

            margin-bottom: 7px;
        }


        .form-control {

            width: 100%;

            padding: 13px 15px;

            background:
                rgba(0,0,0,0.25);

            border:
                1px solid
                rgba(255,255,255,0.14);

            border-radius: 10px;

            color: #fff;

            outline: none;

            transition: 0.3s;
        }


        .form-control::placeholder {

            color: #90897b;
        }


        .form-control:focus {

            border-color: #d4af37;

            box-shadow:
                0 0 0 3px
                rgba(212,175,55,0.1);
        }


        textarea.form-control {

            min-height: 100px;

            resize: vertical;
        }


        .form-row {

            display: grid;

            grid-template-columns:
                1fr 1fr;

            gap: 15px;
        }


        /* =====================================================
           PAYMENT
        ===================================================== */

        .payment-title {

            color: #f5d875;

            font-size: 21px;

            margin:
                28px 0 16px;
        }


        .payment-options {

            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 10px;
        }


        .payment-option {

            position: relative;
        }


        .payment-option input {

            position: absolute;

            opacity: 0;

            pointer-events: none;
        }


        .payment-label {

            display: flex;

            flex-direction: column;

            align-items: center;

            justify-content: center;

            min-height: 105px;

            padding: 15px 8px;

            text-align: center;

            cursor: pointer;

            border:
                1px solid
                rgba(255,255,255,0.13);

            border-radius: 13px;

            background:
                rgba(0,0,0,0.18);

            color: #cfc7b6;

            transition: 0.3s;
        }


        .payment-label span:first-child {

            font-size: 27px;

            margin-bottom: 7px;
        }


        .payment-label small {

            font-size: 11px;
        }


        .payment-option input:checked
        + .payment-label {

            border-color: #d4af37;

            background:
                rgba(212,175,55,0.12);

            color: #f5d875;

            box-shadow:
                0 0 20px
                rgba(212,175,55,0.08);
        }


        /* =====================================================
           PAYMENT DETAILS
        ===================================================== */

        .payment-details {

            display: none;

            margin-top: 18px;

            padding: 18px;

            border-radius: 13px;

            background:
                rgba(212,175,55,0.055);

            border:
                1px solid
                rgba(212,175,55,0.16);

            animation:
                fadeIn 0.3s ease;
        }


        .payment-details.active {

            display: block;
        }


        @keyframes fadeIn {

            from {
                opacity: 0;
                transform: translateY(8px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }


        .payment-details h3 {

            color: #f5d875;

            font-size: 15px;

            margin-bottom: 14px;
        }


        .demo-note {

            color: #a9a08e;

            font-size: 11px;

            line-height: 1.5;

            margin-top: 8px;
        }


        /* =====================================================
           UPI QR
        ===================================================== */

        .qr-box {

            margin-top: 12px;

            text-align: center;

            padding: 15px;

            border-radius: 12px;

            background:
                rgba(255,255,255,0.05);
        }


        .qr-placeholder {

            width: 125px;

            height: 125px;

            margin: auto;

            display: flex;

            align-items: center;

            justify-content: center;

            border:
                2px dashed
                rgba(212,175,55,0.5);

            border-radius: 10px;

            font-size: 42px;

            color: #d4af37;
        }


        .qr-box p {

            margin-top: 8px;

            color: #aaa291;

            font-size: 11px;
        }

        /* REAL UPI QR */
        .upi-qr-image {
            display: block;
            width: 220px;
            height: 220px;
            max-width: 100%;
            margin: 0 auto 12px;
            padding: 8px;
            background: #ffffff;
            border-radius: 12px;
            object-fit: contain;
            box-shadow: 0 10px 30px rgba(0,0,0,0.25);
        }

        .qr-upi-name {
            margin-top: 8px;
            color: #d8cfbd;
            font-size: 12px;
            word-break: break-all;
        }

        .qr-upi-name strong,
        .qr-amount strong {
            color: #f5d875;
        }

        .qr-amount {
            margin-top: 6px;
            color: #c8bfad;
            font-size: 13px;
        }

        .qr-box p {
            line-height: 1.5;
        }

        .qr-note {
            display: block;
            margin-top: 5px;
            color: #7f786c;
            font-size: 10px;
        }


        /* =====================================================
           ORDER SUMMARY
        ===================================================== */

        .summary {

            position: sticky;

            top: 95px;
        }


        .summary h2 {

            color: #f5d875;

            font-size: 21px;

            margin-bottom: 20px;
        }


        .summary-row {

            display: flex;

            justify-content: space-between;

            padding: 11px 0;

            border-bottom:
                1px solid
                rgba(255,255,255,0.06);

            color: #aaa291;

            font-size: 14px;
        }


        .summary-row strong {

            color: #eee5d1;
        }


        .product-list {

            max-height: 260px;

            overflow-y: auto;

            margin-bottom: 10px;
        }


        .mini-product {

            display: flex;

            justify-content: space-between;

            gap: 10px;

            padding: 10px 0;

            border-bottom:
                1px solid
                rgba(255,255,255,0.06);
        }


        .mini-product-name {

            color: #ddd4c1;

            font-size: 13px;
        }


        .mini-product-qty {

            color: #8f887b;

            font-size: 11px;

            margin-top: 3px;
        }


        .mini-product-price {

            color: #f3d66f;

            font-size: 13px;

            font-weight: 700;

            white-space: nowrap;
        }


        .grand-total {

            display: flex;

            justify-content: space-between;

            margin-top: 18px;

            padding-top: 18px;

            border-top:
                1px solid
                rgba(212,175,55,0.25);

            color: #f5d875;

            font-size: 20px;

            font-weight: 800;
        }


        /* =====================================================
           PLACE ORDER
        ===================================================== */

        .place-order-btn {

            width: 100%;

            margin-top: 22px;

            padding: 15px;

            border: none;

            border-radius: 12px;

            cursor: pointer;

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

            transition: 0.3s;

            box-shadow:
                0 10px 25px
                rgba(212,175,55,0.15);
        }


        .place-order-btn:hover {

            transform:
                translateY(-3px);

            box-shadow:
                0 15px 30px
                rgba(212,175,55,0.3);
        }


        .place-order-btn:disabled {

            opacity: 0.7;

            cursor: not-allowed;

            transform: none;
        }


        .back-cart {

            display: block;

            text-align: center;

            margin-top: 14px;

            color: #aaa291;

            text-decoration: none;

            font-size: 13px;
        }


        .back-cart:hover {

            color: #f5d875;
        }


        .secure-note {

            margin-top: 18px;

            text-align: center;

            color: #8e887a;

            font-size: 11px;

            line-height: 1.5;
        }


        /* =====================================================
           MOBILE
        ===================================================== */

        @media(max-width:900px) {

            .checkout-layout {

                grid-template-columns: 1fr;
            }

            .summary {

                position: static;
            }
        }


        @media(max-width:600px) {

            .navbar {

                padding: 0 4%;
            }

            .nav-links a {

                display: none;
            }

            .page {

                width: 94%;
            }

            .page-title h1 {

                font-size: 29px;
            }

            .form-row {

                grid-template-columns: 1fr;

                gap: 0;
            }

            .payment-options {

                grid-template-columns: 1fr;
            }

            .card {

                padding: 20px;
            }
        }

    </style>

</head>


<body>


<!-- =====================================================
     NAVBAR
===================================================== -->

<nav class="navbar">

    <a
        href="index.php"
        class="brand"
    >

        <span class="brand-logo">
            🐾
        </span>

        Royal Paws

    </a>


    <div class="nav-links">

        <span class="user-name">

            Hi,
            <?php
            echo e(
                $_SESSION["user_name"] ?? "User"
            );
            ?>

        </span>

        <a href="index.php">
            Home
        </a>

        <a href="products.php">
            Products
        </a>

        <a href="cart.php">
            🛒 Cart
        </a>

        <a href="orders.php">
            Orders
        </a>

        <a href="logout.php">
            Logout
        </a>

    </div>

</nav>


<!-- =====================================================
     PAGE
===================================================== -->

<main class="page">


    <div class="page-title">

        <h1>
            💳 Secure Checkout
        </h1>

        <p>
            Complete your delivery details and select a payment method.
        </p>

    </div>


    <?php if ($error !== ""): ?>

        <div class="error-box">

            ⚠️
            <?php echo e($error); ?>

        </div>

    <?php endif; ?>


    <form
        method="POST"
        id="checkoutForm"
    >


        <div class="checkout-layout">


            <!-- =================================================
                 LEFT
            ================================================== -->

            <div class="card">

                <h2 class="card-title">
                    📦 Delivery Details
                </h2>


                <div class="form-row">


                    <div class="form-group">

                        <label for="shipping_name">
                            Full Name *
                        </label>

                        <input
                            type="text"
                            id="shipping_name"
                            name="shipping_name"
                            class="form-control"
                            value="<?php
                            echo e($shipping_name);
                            ?>"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="shipping_phone">
                            Phone Number *
                        </label>

                        <input
                            type="tel"
                            id="shipping_phone"
                            name="shipping_phone"
                            class="form-control"
                            placeholder="10 digit mobile number"
                            value="<?php
                            echo e($shipping_phone);
                            ?>"
                            maxlength="10"
                            required
                        >

                    </div>

                </div>


                <div class="form-group">

                    <label for="shipping_address">
                        Delivery Address *
                    </label>

                    <textarea
                        id="shipping_address"
                        name="shipping_address"
                        class="form-control"
                        placeholder="Enter complete delivery address"
                        required
                    ><?php
                    echo e($shipping_address);
                    ?></textarea>

                </div>


                <!-- PAYMENT -->

                <h2 class="payment-title">
                    💰 Payment Method
                </h2>


                <div class="payment-options">


                    <!-- COD -->

                    <div class="payment-option">

                        <input
                            type="radio"
                            id="cod"
                            name="payment_method"
                            value="COD"
                            <?php
                            echo $payment_method === "COD"
                                ? "checked"
                                : "";
                            ?>
                        >

                        <label
                            for="cod"
                            class="payment-label"
                        >

                            <span>💵</span>

                            <strong>
                                Cash on Delivery
                            </strong>

                            <small>
                                Pay when delivered
                            </small>

                        </label>

                    </div>


                    <!-- UPI -->

                    <div class="payment-option">

                        <input
                            type="radio"
                            id="upi"
                            name="payment_method"
                            value="UPI"
                            <?php
                            echo $payment_method === "UPI"
                                ? "checked"
                                : "";
                            ?>
                        >

                        <label
                            for="upi"
                            class="payment-label"
                        >

                            <span>📱</span>

                            <strong>
                                UPI
                            </strong>

                            <small>
                                UPI payment
                            </small>

                        </label>

                    </div>


                    <!-- CARD -->

                    <div class="payment-option">

                        <input
                            type="radio"
                            id="card"
                            name="payment_method"
                            value="CARD"
                            <?php
                            echo $payment_method === "CARD"
                                ? "checked"
                                : "";
                            ?>
                        >

                        <label
                            for="card"
                            class="payment-label"
                        >

                            <span>💳</span>

                            <strong>
                                Card
                            </strong>

                            <small>
                                Debit / Credit
                            </small>

                        </label>

                    </div>

                </div>


                <!-- UPI DETAILS -->

                <div
                    class="payment-details"
                    id="upiDetails"
                >

                    <h3>
                        📱 UPI Payment Details
                    </h3>


                    <div class="form-group">

                        <label for="upi_id">
                            UPI ID
                        </label>

                        <input
                            type="text"
                            id="upi_id"
                            name="upi_id"
                            class="form-control"
                            placeholder="example@upi"
                            value="<?php
                            echo e(
                                $_POST["upi_id"] ?? ""
                            );
                            ?>"
                        >

                    </div>


                    <div class="qr-box" id="qrPaymentBox">

                        <img
                            id="upiQrImage"
                            class="upi-qr-image"
                            src=""
                            alt="UPI Payment QR Code"
                            width="220"
                            height="220"
                        >

                        <div class="qr-upi-name">
                            Pay to:
                            <strong><?php echo e($merchant_upi_id); ?></strong>
                        </div>

                        <div class="qr-amount">
                            Amount:
                            <strong>₹<?php echo number_format($grand_total, 2); ?></strong>
                        </div>

                        <p>
                            Scan this QR with Google Pay, PhonePe,
                            Paytm or another UPI app.
                        </p>

                        <small class="qr-note">
                            QR is generated for this order amount.
                        </small>

                    </div>

                </div>


                <!-- CARD DETAILS -->

                <div
                    class="payment-details"
                    id="cardDetails"
                >

                    <h3>
                        💳 Card Details
                    </h3>


                    <div class="form-group">

                        <label for="card_name">
                            Card Holder Name
                        </label>

                        <input
                            type="text"
                            id="card_name"
                            name="card_name"
                            class="form-control"
                            placeholder="Name on card"
                            value="<?php
                            echo e(
                                $_POST["card_name"] ?? ""
                            );
                            ?>"
                        >

                    </div>


                    <div class="form-group">

                        <label for="card_number">
                            Card Number
                        </label>

                        <input
                            type="text"
                            id="card_number"
                            name="card_number"
                            class="form-control"
                            placeholder="16 digit card number"
                            maxlength="19"
                            inputmode="numeric"
                            value="<?php
                            echo e(
                                $_POST["card_number"] ?? ""
                            );
                            ?>"
                        >

                    </div>


                    <div class="form-row">

                        <div class="form-group">

                            <label for="expiry">
                                Expiry
                            </label>

                            <input
                                type="text"
                                id="expiry"
                                name="expiry"
                                class="form-control"
                                placeholder="MM/YY"
                                maxlength="5"
                                value="<?php
                                echo e(
                                    $_POST["expiry"] ?? ""
                                );
                                ?>"
                            >

                        </div>


                        <div class="form-group">

                            <label for="cvv">
                                CVV
                            </label>

                            <input
                                type="password"
                                id="cvv"
                                name="cvv"
                                class="form-control"
                                placeholder="CVV"
                                maxlength="4"
                                inputmode="numeric"
                            >

                        </div>

                    </div>


                    <p class="demo-note">

                        🔒 This is a college project payment
                        demonstration. No real payment gateway
                        is connected.

                    </p>

                </div>


            </div>


            <!-- =================================================
                 RIGHT SUMMARY
            ================================================== -->

            <aside class="card summary">

                <h2>
                    🛍️ Order Summary
                </h2>


                <div class="product-list">

                    <?php foreach ($cart_items as $item): ?>

                        <div class="mini-product">

                            <div>

                                <div class="mini-product-name">

                                    <?php
                                    echo e(
                                        $item["name"]
                                    );
                                    ?>

                                </div>

                                <div class="mini-product-qty">

                                    Qty:
                                    <?php
                                    echo (int)
                                        $item["quantity"];
                                    ?>

                                </div>

                            </div>


                            <div class="mini-product-price">

                                ₹<?php
                                echo number_format(
                                    (float)
                                    $item["selling_price"]
                                    *
                                    (int)
                                    $item["quantity"],
                                    2
                                );
                                ?>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>


                <div class="summary-row">

                    <span>
                        Total Items
                    </span>

                    <strong>
                        <?php
                        echo $total_items;
                        ?>
                    </strong>

                </div>


                <div class="summary-row">

                    <span>
                        Subtotal
                    </span>

                    <strong>

                        ₹<?php
                        echo number_format(
                            $subtotal,
                            2
                        );
                        ?>

                    </strong>

                </div>


                <div class="summary-row">

                    <span>
                        Delivery
                    </span>

                    <strong>

                        <?php if ($delivery_charge > 0): ?>

                            ₹<?php
                            echo number_format(
                                $delivery_charge,
                                2
                            );
                            ?>

                        <?php else: ?>

                            FREE

                        <?php endif; ?>

                    </strong>

                </div>


                <div class="grand-total">

                    <span>
                        Total
                    </span>

                    <span>

                        ₹<?php
                        echo number_format(
                            $grand_total,
                            2
                        );
                        ?>

                    </span>

                </div>


                <button
                    type="submit"
                    class="place-order-btn"
                    id="placeOrderBtn"
                >
                    🐾 Place Order
                </button>


                <a
                    href="cart.php"
                    class="back-cart"
                >
                    ← Back to Cart
                </a>


                <div class="secure-note">

                    🔒 Your order details are securely
                    processed by the Royal Paws system.

                </div>

            </aside>


        </div>

    </form>

</main>


<script>

    /*
    |--------------------------------------------------------------------------
    | Payment Sections
    |--------------------------------------------------------------------------
    */

    const paymentRadios =
        document.querySelectorAll(
            'input[name="payment_method"]'
        );

    const upiDetails =
        document.getElementById(
            "upiDetails"
        );

    const cardDetails =
        document.getElementById(
            "cardDetails"
        );


    function updatePaymentDetails() {

        const selected =
            document.querySelector(
                'input[name="payment_method"]:checked'
            );


        upiDetails.classList.remove(
            "active"
        );

        cardDetails.classList.remove(
            "active"
        );


        if (!selected) {
            return;
        }


        if (selected.value === "UPI") {

            upiDetails.classList.add(
                "active"
            );
        }


        if (selected.value === "CARD") {

            cardDetails.classList.add(
                "active"
            );
        }

    }


    paymentRadios.forEach(
        function(radio) {

            radio.addEventListener(
                "change",
                updatePaymentDetails
            );

        }
    );


    updatePaymentDetails();


    /*
    |--------------------------------------------------------------------------
    | REAL UPI QR GENERATION
    |--------------------------------------------------------------------------
    */

    const merchantUpiId =
        <?php echo json_encode($merchant_upi_id); ?>;

    const merchantName =
        <?php echo json_encode($merchant_name); ?>;

    const orderAmount =
        <?php echo json_encode(number_format($grand_total, 2, ".", "")); ?>;

    const upiQrImage =
        document.getElementById("upiQrImage");

    function generateUpiQr() {

        if (!upiQrImage) {
            return;
        }

        // Keep the QR visible by default.
        // Replace the fallback with your real UPI ID for real payment.
        const upiId =
            merchantUpiId.trim() &&
            merchantUpiId.trim() !== "YOUR_REAL_UPI_ID@upi"
                ? merchantUpiId.trim()
                : "demo@upi";

        const upiUrl =
            "upi://pay" +
            "?pa=" + encodeURIComponent(upiId) +
            "&pn=" + encodeURIComponent(merchantName) +
            "&am=" + encodeURIComponent(orderAmount) +
            "&cu=INR";

        /*
         * Converts the UPI URI into an actual QR image.
         */
        const qrImageUrl =
            "https://api.qrserver.com/v1/create-qr-code/" +
            "?size=500x500" +
            "&format=png" +
            "&margin=10" +
            "&data=" + encodeURIComponent(upiUrl);

        upiQrImage.onload = function () {
            upiQrImage.style.display = "block";
        };

        upiQrImage.onerror = function () {
            upiQrImage.style.display = "none";
        };

        upiQrImage.src = qrImageUrl;
        upiQrImage.alt =
            "Real UPI payment QR for ₹" + orderAmount;
    }

    generateUpiQr();


    /*
    |--------------------------------------------------------------------------
    | Card Number Formatting
    |--------------------------------------------------------------------------
    */

    const cardNumber =
        document.getElementById(
            "card_number"
        );


    if (cardNumber) {

        cardNumber.addEventListener(
            "input",
            function() {

                let value =
                    cardNumber.value
                    .replace(/\D/g, "")
                    .substring(0, 16);

                let formatted = "";

                for (
                    let i = 0;
                    i < value.length;
                    i++
                ) {

                    if (
                        i > 0 &&
                        i % 4 === 0
                    ) {

                        formatted += " ";
                    }

                    formatted += value[i];
                }

                cardNumber.value =
                    formatted;
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Expiry Formatting
    |--------------------------------------------------------------------------
    */

    const expiry =
        document.getElementById(
            "expiry"
        );


    if (expiry) {

        expiry.addEventListener(
            "input",
            function() {

                let value =
                    expiry.value
                    .replace(/\D/g, "")
                    .substring(0, 4);


                if (value.length >= 3) {

                    value =
                        value.substring(0, 2)
                        + "/"
                        + value.substring(2);
                }


                expiry.value = value;
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | CVV Numbers Only
    |--------------------------------------------------------------------------
    */

    const cvv =
        document.getElementById(
            "cvv"
        );


    if (cvv) {

        cvv.addEventListener(
            "input",
            function() {

                cvv.value =
                    cvv.value
                    .replace(/\D/g, "")
                    .substring(0, 4);

            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Phone Numbers Only
    |--------------------------------------------------------------------------
    */

    const phone =
        document.getElementById(
            "shipping_phone"
        );


    if (phone) {

        phone.addEventListener(
            "input",
            function() {

                phone.value =
                    phone.value
                    .replace(/\D/g, "")
                    .substring(0, 10);

            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Form Loading
    |--------------------------------------------------------------------------
    */

    const checkoutForm =
        document.getElementById(
            "checkoutForm"
        );

    const placeOrderBtn =
        document.getElementById(
            "placeOrderBtn"
        );


    checkoutForm.addEventListener(
        "submit",
        function(event) {

            const phoneValue =
                phone.value.trim();


            if (
                phoneValue.length !== 10
            ) {

                event.preventDefault();

                alert(
                    "Please enter a valid 10-digit phone number."
                );

                phone.focus();

                return;
            }


            const selected =
                document.querySelector(
                    'input[name="payment_method"]:checked'
                );


            if (!selected) {

                event.preventDefault();

                alert(
                    "Please select a payment method."
                );

                return;
            }


            placeOrderBtn.disabled =
                true;

            placeOrderBtn.textContent =
                "⏳ Placing Order...";

        }
    );

</script>


</body>

</html>