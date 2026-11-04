<?php

require_once "config/database.php";
require_once "config/functions.php";

requireUser();

$user_id = (int) $_SESSION["user_id"];

/*
|--------------------------------------------------------------------------
| Update Cart Quantity
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action = $_POST["action"] ?? "";

    /*
    | Update Quantity
    */

    if ($action === "update") {

        $cart_id = (int) ($_POST["cart_id"] ?? 0);
        $quantity = (int) ($_POST["quantity"] ?? 1);

        if ($cart_id > 0) {

            if ($quantity < 1) {
                $quantity = 1;
            }

            $stmt = $conn->prepare(
                "SELECT p.stock
                 FROM cart c
                 INNER JOIN products p
                 ON c.product_id = p.id
                 WHERE c.id = ?
                 AND c.user_id = ?
                 LIMIT 1"
            );

            if ($stmt) {

                $stmt->bind_param(
                    "ii",
                    $cart_id,
                    $user_id
                );

                $stmt->execute();

                $result = $stmt->get_result();

                if ($result->num_rows === 1) {

                    $product = $result->fetch_assoc();

                    $stock = (int) $product["stock"];

                    if ($stock <= 0) {

                        $quantity = 1;

                        setMessage(
                            "This product is currently out of stock.",
                            "error"
                        );

                    } elseif ($quantity > $stock) {

                        $quantity = $stock;

                        setMessage(
                            "Quantity adjusted to available stock.",
                            "success"
                        );
                    }

                    $update = $conn->prepare(
                        "UPDATE cart
                         SET quantity = ?
                         WHERE id = ?
                         AND user_id = ?"
                    );

                    if ($update) {

                        $update->bind_param(
                            "iii",
                            $quantity,
                            $cart_id,
                            $user_id
                        );

                        $update->execute();

                        $update->close();

                        if (!isset($_SESSION["flash_message"])) {

                            setMessage(
                                "Cart updated successfully.",
                                "success"
                            );
                        }
                    }

                } else {

                    setMessage(
                        "Cart item not found.",
                        "error"
                    );
                }

                $stmt->close();
            }
        }

        header("Location: cart.php");
        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Remove Cart Item
    |--------------------------------------------------------------------------
    */

    if ($action === "remove") {

        $cart_id = (int) ($_POST["cart_id"] ?? 0);

        if ($cart_id > 0) {

            $stmt = $conn->prepare(
                "DELETE FROM cart
                 WHERE id = ?
                 AND user_id = ?"
            );

            if ($stmt) {

                $stmt->bind_param(
                    "ii",
                    $cart_id,
                    $user_id
                );

                $stmt->execute();

                $stmt->close();

                setMessage(
                    "Product removed from cart.",
                    "success"
                );
            }
        }

        header("Location: cart.php");
        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Clear Cart
    |--------------------------------------------------------------------------
    */

    if ($action === "clear") {

        $stmt = $conn->prepare(
            "DELETE FROM cart
             WHERE user_id = ?"
        );

        if ($stmt) {

            $stmt->bind_param(
                "i",
                $user_id
            );

            $stmt->execute();

            $stmt->close();

            setMessage(
                "Your cart has been cleared.",
                "success"
            );
        }

        header("Location: cart.php");
        exit;
    }
}


/*
|--------------------------------------------------------------------------
| Get Cart Products
|--------------------------------------------------------------------------
*/

$cart_items = [];

$stmt = $conn->prepare(
    "SELECT
        c.id AS cart_id,
        c.quantity,
        p.id AS product_id,
        p.name,
        p.description,
        p.selling_price,
        p.stock,
        p.image,
        p.status
     FROM cart c
     INNER JOIN products p
     ON c.product_id = p.id
     WHERE c.user_id = ?
     ORDER BY c.created_at DESC"
);

if ($stmt) {

    $stmt->bind_param(
        "i",
        $user_id
    );

    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $cart_items[] = $row;
    }

    $stmt->close();
}


/*
|--------------------------------------------------------------------------
| Calculate Cart Total
|--------------------------------------------------------------------------
*/

$subtotal = 0;
$total_items = 0;

foreach ($cart_items as &$item) {

    $price = (float) $item["selling_price"];

    $quantity = (int) $item["quantity"];

    $item["item_total"] =
        $price * $quantity;

    $subtotal +=
        $item["item_total"];

    $total_items +=
        $quantity;
}

unset($item);

$delivery_charge = 0;

/*
|--------------------------------------------------------------------------
| Free Delivery
|--------------------------------------------------------------------------
*/

if ($subtotal > 0 && $subtotal < 1000) {
    $delivery_charge = 50;
}

$grand_total =
    $subtotal + $delivery_charge;

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>My Cart | Royal Paws</title>


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
                    rgba(212,175,55,0.12),
                    transparent 35%
                ),
                linear-gradient(
                    135deg,
                    #100d08,
                    #1e190f,
                    #0d0b08
                );

            color: #fff;
        }


        /* =====================================================
           NAVBAR
        ===================================================== */

        .navbar {

            height: 75px;

            display: flex;

            align-items: center;

            justify-content: space-between;

            padding: 0 6%;

            background:
                rgba(15,12,8,0.92);

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

            color: #f4d675;

            font-size: 22px;

            font-weight: 800;

            letter-spacing: 0.5px;
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

            box-shadow:
                0 0 18px
                rgba(212,175,55,0.25);
        }


        .nav-links {

            display: flex;

            align-items: center;

            gap: 25px;
        }


        .nav-links a {

            color: #d7cfbd;

            text-decoration: none;

            font-size: 14px;

            transition: 0.3s;
        }


        .nav-links a:hover {

            color: #f4d675;

            transform:
                translateY(-1px);
        }


        .cart-link {

            position: relative;

            color: #f4d675 !important;
        }


        .cart-count {

            position: absolute;

            top: -12px;

            right: -13px;

            min-width: 20px;

            height: 20px;

            padding: 0 5px;

            display: flex;

            align-items: center;

            justify-content: center;

            background: #d4af37;

            color: #171208;

            border-radius: 50%;

            font-size: 11px;

            font-weight: 800;
        }


        .user-name {

            color: #f1df9a;

            font-size: 13px;
        }


        /* =====================================================
           PAGE
        ===================================================== */

        .page {

            width: 92%;

            max-width: 1250px;

            margin: 45px auto 70px;
        }


        .page-header {

            margin-bottom: 30px;
        }


        .page-header h1 {

            font-size: 36px;

            color: #f5da79;

            margin-bottom: 8px;
        }


        .page-header p {

            color: #aaa291;

            font-size: 14px;
        }


        /* =====================================================
           FLASH
        ===================================================== */

        .flash-message {

            padding: 13px 18px;

            border-radius: 12px;

            margin-bottom: 25px;

            border: 1px solid
                rgba(212,175,55,0.3);

            background:
                rgba(212,175,55,0.08);

            color: #f4df96;

            animation:
                slideDown 0.4s ease;
        }


        .flash-message.error {

            border-color:
                rgba(220,53,69,0.4);

            background:
                rgba(220,53,69,0.12);

            color: #ffb3b9;
        }


        @keyframes slideDown {

            from {

                opacity: 0;

                transform:
                    translateY(-15px);
            }

            to {

                opacity: 1;

                transform:
                    translateY(0);
            }
        }


        /* =====================================================
           CART LAYOUT
        ===================================================== */

        .cart-layout {

            display: grid;

            grid-template-columns:
                1fr 350px;

            gap: 25px;

            align-items: start;
        }


        .cart-box {

            background:
                rgba(255,255,255,0.055);

            border:
                1px solid
                rgba(212,175,55,0.22);

            border-radius: 20px;

            overflow: hidden;

            backdrop-filter: blur(12px);

            box-shadow:
                0 20px 50px
                rgba(0,0,0,0.25);
        }


        /* =====================================================
           CART ITEM
        ===================================================== */

        .cart-item {

            display: grid;

            grid-template-columns:
                105px 1fr auto;

            gap: 20px;

            align-items: center;

            padding: 22px;

            border-bottom:
                1px solid
                rgba(255,255,255,0.08);

            transition: 0.3s;
        }


        .cart-item:last-child {
            border-bottom: none;
        }


        .cart-item:hover {

            background:
                rgba(212,175,55,0.035);
        }


        .product-image {

            width: 105px;

            height: 105px;

            border-radius: 15px;

            object-fit: cover;

            border:
                1px solid
                rgba(212,175,55,0.2);

            background:
                rgba(255,255,255,0.05);
        }


        .product-info h3 {

            color: #f1d779;

            font-size: 18px;

            margin-bottom: 7px;
        }


        .product-info p {

            color: #aaa291;

            font-size: 13px;

            line-height: 1.5;

            margin-bottom: 10px;

            max-width: 500px;
        }


        .price {

            color: #fff;

            font-weight: 700;

            font-size: 16px;
        }


        .stock-info {

            color: #8fcf91;

            font-size: 12px;

            margin-top: 6px;
        }


        .stock-low {

            color: #e8bb64;
        }


        /* =====================================================
           QUANTITY
        ===================================================== */

        .item-actions {

            display: flex;

            flex-direction: column;

            align-items: flex-end;

            gap: 12px;
        }


        .quantity-form {

            display: flex;

            align-items: center;

            gap: 7px;
        }


        .quantity-form input {

            width: 60px;

            height: 38px;

            text-align: center;

            border-radius: 9px;

            border:
                1px solid
                rgba(212,175,55,0.25);

            background:
                rgba(0,0,0,0.25);

            color: #fff;

            outline: none;
        }


        .update-btn {

            height: 38px;

            padding: 0 12px;

            border: none;

            border-radius: 9px;

            background:
                rgba(212,175,55,0.15);

            border:
                1px solid
                rgba(212,175,55,0.25);

            color: #f4d675;

            cursor: pointer;

            transition: 0.3s;
        }


        .update-btn:hover {

            background:
                rgba(212,175,55,0.25);
        }


        .remove-btn {

            background: none;

            border: none;

            color: #d88c8c;

            cursor: pointer;

            font-size: 12px;

            transition: 0.3s;
        }


        .remove-btn:hover {

            color: #ff9e9e;

            text-decoration: underline;
        }


        .item-total {

            color: #f5da79;

            font-size: 17px;

            font-weight: 800;
        }


        /* =====================================================
           CART FOOTER
        ===================================================== */

        .cart-footer {

            padding: 20px 22px;

            border-top:
                1px solid
                rgba(255,255,255,0.08);

            display: flex;

            justify-content: space-between;

            align-items: center;
        }


        .clear-btn {

            background: none;

            border: none;

            color: #d88c8c;

            cursor: pointer;

            font-size: 13px;
        }


        .clear-btn:hover {

            color: #ff9e9e;

            text-decoration: underline;
        }


        /* =====================================================
           SUMMARY
        ===================================================== */

        .summary {

            padding: 25px;

            background:
                rgba(255,255,255,0.055);

            border:
                1px solid
                rgba(212,175,55,0.25);

            border-radius: 20px;

            position: sticky;

            top: 100px;

            box-shadow:
                0 20px 50px
                rgba(0,0,0,0.25);
        }


        .summary h2 {

            color: #f5da79;

            font-size: 22px;

            margin-bottom: 22px;
        }


        .summary-row {

            display: flex;

            justify-content: space-between;

            padding: 12px 0;

            color: #bcb4a4;

            font-size: 14px;

            border-bottom:
                1px solid
                rgba(255,255,255,0.06);
        }


        .summary-row strong {

            color: #eee5d1;
        }


        .free-delivery {

            color: #8fcf91;

            font-size: 12px;

            margin-top: 8px;

            text-align: right;
        }


        .total-row {

            display: flex;

            justify-content: space-between;

            margin-top: 18px;

            padding-top: 18px;

            border-top:
                1px solid
                rgba(212,175,55,0.25);

            font-size: 19px;

            font-weight: 800;

            color: #f5da79;
        }


        .checkout-btn {

            display: block;

            width: 100%;

            text-align: center;

            text-decoration: none;

            margin-top: 22px;

            padding: 15px;

            border-radius: 12px;

            background:
                linear-gradient(
                    135deg,
                    #f4d675,
                    #d4af37,
                    #a77d16
                );

            color: #1b1509;

            font-weight: 800;

            transition: 0.3s;

            box-shadow:
                0 10px 25px
                rgba(212,175,55,0.15);
        }


        .checkout-btn:hover {

            transform:
                translateY(-3px);

            box-shadow:
                0 15px 30px
                rgba(212,175,55,0.3);
        }


        .continue-btn {

            display: block;

            text-align: center;

            margin-top: 14px;

            color: #bcb4a4;

            text-decoration: none;

            font-size: 13px;

            transition: 0.3s;
        }


        .continue-btn:hover {
            color: #f5da79;
        }


        /* =====================================================
           EMPTY CART
        ===================================================== */

        .empty-cart {

            text-align: center;

            padding: 75px 25px;
        }


        .empty-icon {

            font-size: 75px;

            margin-bottom: 15px;

            animation:
                emptyFloat 2.5s infinite ease-in-out;
        }


        @keyframes emptyFloat {

            0%,100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-10px);
            }
        }


        .empty-cart h2 {

            color: #f4d675;

            margin-bottom: 8px;
        }


        .empty-cart p {

            color: #aaa291;

            font-size: 14px;

            margin-bottom: 25px;
        }


        .shop-btn {

            display: inline-block;

            padding: 13px 25px;

            border-radius: 10px;

            text-decoration: none;

            background:
                linear-gradient(
                    135deg,
                    #f4d675,
                    #d4af37
                );

            color: #1b1509;

            font-weight: 800;

            transition: 0.3s;
        }


        .shop-btn:hover {

            transform:
                translateY(-3px);
        }


        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media(max-width:900px) {

            .cart-layout {

                grid-template-columns: 1fr;
            }

            .summary {

                position: static;
            }
        }


        @media(max-width:650px) {

            .navbar {

                padding: 0 4%;
            }

            .nav-links {

                gap: 12px;
            }

            .nav-links a:not(.cart-link) {

                display: none;
            }

            .page-header h1 {

                font-size: 29px;
            }

            .cart-item {

                grid-template-columns:
                    80px 1fr;

                gap: 15px;
            }

            .product-image {

                width: 80px;

                height: 80px;
            }

            .item-actions {

                grid-column: 1 / -1;

                flex-direction: row;

                justify-content:
                    space-between;

                align-items: center;

                padding-top: 5px;
            }

            .cart-footer {

                flex-direction: column;

                gap: 12px;

                align-items: flex-start;
            }
        }

    </style>

</head>


<body>


<!-- =====================================================
     NAVBAR
===================================================== -->

<nav class="navbar">

    <a href="index.php" class="brand">

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

        <a
            href="cart.php"
            class="cart-link"
        >

            🛒 Cart

            <span class="cart-count">

                <?php
                echo cartCount($conn);
                ?>

            </span>

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
     MAIN
===================================================== -->

<main class="page">


    <div class="page-header">

        <h1>
            🛒 My Shopping Cart
        </h1>

        <p>
            Review your selected pet products before checkout.
        </p>

    </div>


    <?php showMessage(); ?>


    <?php if (empty($cart_items)): ?>

        <!-- EMPTY CART -->

        <div class="cart-box">

            <div class="empty-cart">

                <div class="empty-icon">
                    🛒
                </div>

                <h2>
                    Your Cart is Empty
                </h2>

                <p>
                    Looks like you haven't added
                    any products yet.
                </p>

                <a
                    href="products.php"
                    class="shop-btn"
                >
                    🐾 Start Shopping
                </a>

            </div>

        </div>


    <?php else: ?>


        <div class="cart-layout">


            <!-- =================================================
                 CART PRODUCTS
            ================================================== -->

            <div class="cart-box">


                <?php foreach ($cart_items as $item): ?>

                    <div class="cart-item">


                        <?php

                        $image = trim(
                            $item["image"] ?? ""
                        );

                        $image_path =
                            "assets/images/" . $image;

                        if (
                            $image === "" ||
                            !file_exists($image_path)
                        ) {

                            $image_path =
                                "assets/images/logo.png";
                        }

                        ?>


                        <img
                            src="<?php echo e($image_path); ?>"
                            alt="<?php echo e($item["name"]); ?>"
                            class="product-image"
                            onerror="
                                this.onerror=null;
                                this.src='assets/images/logo.png';
                            "
                        >


                        <div class="product-info">

                            <h3>
                                <?php
                                echo e(
                                    $item["name"]
                                );
                                ?>
                            </h3>


                            <p>

                                <?php

                                $description =
                                    $item["description"] ?? "";

                                if (
                                    strlen($description) > 100
                                ) {

                                    $description =
                                        substr(
                                            $description,
                                            0,
                                            100
                                        ) . "...";
                                }

                                echo e($description);

                                ?>

                            </p>


                            <div class="price">

                                ₹<?php
                                echo number_format(
                                    (float)
                                    $item["selling_price"],
                                    2
                                );
                                ?>

                                / item

                            </div>


                            <?php if (
                                (int)$item["stock"] <= 5
                            ): ?>

                                <div class="stock-info stock-low">

                                    ⚠️ Only
                                    <?php
                                    echo (int)
                                        $item["stock"];
                                    ?>
                                    left

                                </div>

                            <?php else: ?>

                                <div class="stock-info">

                                    ✓
                                    <?php
                                    echo (int)
                                        $item["stock"];
                                    ?>
                                    available

                                </div>

                            <?php endif; ?>

                        </div>


                        <div class="item-actions">


                            <form
                                method="POST"
                                class="quantity-form"
                            >

                                <input
                                    type="hidden"
                                    name="action"
                                    value="update"
                                >

                                <input
                                    type="hidden"
                                    name="cart_id"
                                    value="<?php
                                    echo (int)
                                        $item["cart_id"];
                                    ?>"
                                >

                                <input
                                    type="number"
                                    name="quantity"
                                    value="<?php
                                    echo (int)
                                        $item["quantity"];
                                    ?>"
                                    min="1"
                                    max="<?php
                                    echo max(
                                        1,
                                        (int)
                                        $item["stock"]
                                    );
                                    ?>"
                                    aria-label="Quantity"
                                >

                                <button
                                    type="submit"
                                    class="update-btn"
                                >
                                    Update
                                </button>

                            </form>


                            <div class="item-total">

                                ₹<?php
                                echo number_format(
                                    (float)
                                    $item["item_total"],
                                    2
                                );
                                ?>

                            </div>


                            <form method="POST">

                                <input
                                    type="hidden"
                                    name="action"
                                    value="remove"
                                >

                                <input
                                    type="hidden"
                                    name="cart_id"
                                    value="<?php
                                    echo (int)
                                        $item["cart_id"];
                                    ?>"
                                >

                                <button
                                    type="submit"
                                    class="remove-btn"
                                    onclick="
                                        return confirm(
                                            'Remove this product from cart?'
                                        );
                                    "
                                >
                                    🗑 Remove
                                </button>

                            </form>

                        </div>

                    </div>

                <?php endforeach; ?>


                <div class="cart-footer">

                    <span>

                        <?php
                        echo $total_items;
                        ?>
                        item(s) in your cart

                    </span>


                    <form method="POST">

                        <input
                            type="hidden"
                            name="action"
                            value="clear"
                        >

                        <button
                            type="submit"
                            class="clear-btn"
                            onclick="
                                return confirm(
                                    'Clear your entire cart?'
                                );
                            "
                        >
                            🗑 Clear Cart
                        </button>

                    </form>

                </div>

            </div>


            <!-- =================================================
                 SUMMARY
            ================================================== -->

            <aside class="summary">

                <h2>
                    Order Summary
                </h2>


                <div class="summary-row">

                    <span>
                        Items
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


                <?php if ($delivery_charge > 0): ?>

                    <div class="free-delivery">

                        Add ₹<?php
                        echo number_format(
                            1000 - $subtotal,
                            2
                        );
                        ?>
                        more for FREE delivery

                    </div>

                <?php else: ?>

                    <div class="free-delivery">

                        ✓ Free delivery applied

                    </div>

                <?php endif; ?>


                <div class="total-row">

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


                <a
                    href="checkout.php"
                    class="checkout-btn"
                    id="checkoutBtn"
                >
                    💳 Proceed to Checkout
                </a>


                <a
                    href="products.php"
                    class="continue-btn"
                >
                    ← Continue Shopping
                </a>

            </aside>


        </div>

    <?php endif; ?>

</main>


<script>

    /*
    |--------------------------------------------------------------------------
    | Quantity Validation
    |--------------------------------------------------------------------------
    */

    const quantityInputs =
        document.querySelectorAll(
            '.quantity-form input[type="number"]'
        );


    quantityInputs.forEach(function(input) {

        input.addEventListener(
            "change",
            function() {

                const min =
                    parseInt(
                        input.getAttribute("min")
                    ) || 1;

                const max =
                    parseInt(
                        input.getAttribute("max")
                    ) || 999;

                let value =
                    parseInt(input.value);

                if (isNaN(value)) {
                    value = min;
                }

                if (value < min) {
                    value = min;
                }

                if (value > max) {
                    value = max;
                }

                input.value = value;
            }
        );

    });


    /*
    |--------------------------------------------------------------------------
    | Checkout Button Loading
    |--------------------------------------------------------------------------
    */

    const checkoutBtn =
        document.getElementById("checkoutBtn");


    if (checkoutBtn) {

        checkoutBtn.addEventListener(
            "click",
            function() {

                checkoutBtn.style.opacity = "0.7";

                checkoutBtn.textContent =
                    "Loading Checkout...";

            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Auto Hide Flash Message
    |--------------------------------------------------------------------------
    */

    const flash =
        document.querySelector(
            ".flash-message"
        );


    if (flash) {

        setTimeout(
            function() {

                flash.style.transition =
                    "0.5s";

                flash.style.opacity =
                    "0";

                setTimeout(
                    function() {

                        flash.remove();

                    },
                    500
                );

            },
            3500
        );
    }

</script>


</body>

</html>