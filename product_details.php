
<?php
session_start();

require_once "config/database.php";
require_once "config/functions.php";

/* =========================
   GET PRODUCT ID
========================= */

$productId = isset($_GET['id'])
    ? (int)$_GET['id']
    : 0;

if ($productId <= 0) {
    header("Location: products.php");
    exit;
}


/* =========================
   ADD TO CART
========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_SESSION['user_id'])) {
        header(
            "Location: login.php?redirect=product_details.php?id="
            . $productId
        );
        exit;
    }

    $quantity = isset($_POST['quantity'])
        ? (int)$_POST['quantity']
        : 1;

    if ($quantity < 1) {
        $quantity = 1;
    }


    /* Check product stock */

    $stockStmt = $conn->prepare("
        SELECT stock
        FROM products
        WHERE id = ?
        AND status = 'active'
        LIMIT 1
    ");

    $stockStmt->bind_param(
        "i",
        $productId
    );

    $stockStmt->execute();

    $stockResult =
        $stockStmt->get_result();

    if ($stockResult->num_rows === 0) {

        header("Location: products.php");
        exit;

    }

    $stockRow =
        $stockResult->fetch_assoc();

    $stock = (int)$stockRow['stock'];


    if ($stock <= 0) {

        $_SESSION['message'] =
            "Sorry, this product is out of stock.";

        $_SESSION['message_type'] =
            "error";

    } elseif ($quantity > $stock) {

        $_SESSION['message'] =
            "Only " . $stock .
            " item(s) are available.";

        $_SESSION['message_type'] =
            "error";

    } else {


        /* Check existing cart item */

        $cartCheck = $conn->prepare("
            SELECT id, quantity
            FROM cart
            WHERE user_id = ?
            AND product_id = ?
            LIMIT 1
        ");

        $cartCheck->bind_param(
            "ii",
            $_SESSION['user_id'],
            $productId
        );

        $cartCheck->execute();

        $cartResult =
            $cartCheck->get_result();


        if ($cartResult->num_rows > 0) {

            $cartRow =
                $cartResult->fetch_assoc();

            $newQuantity =
                (int)$cartRow['quantity']
                + $quantity;


            if ($newQuantity > $stock) {

                $_SESSION['message'] =
                    "You cannot add more than "
                    . $stock
                    . " item(s).";

                $_SESSION['message_type'] =
                    "error";

            } else {

                $updateCart =
                    $conn->prepare("
                        UPDATE cart
                        SET quantity = ?
                        WHERE id = ?
                    ");

                $updateCart->bind_param(
                    "ii",
                    $newQuantity,
                    $cartRow['id']
                );

                $updateCart->execute();

                $_SESSION['message'] =
                    "Product quantity updated in cart.";

                $_SESSION['message_type'] =
                    "success";
            }

        } else {

            $insertCart =
                $conn->prepare("
                    INSERT INTO cart
                    (user_id, product_id, quantity)
                    VALUES (?, ?, ?)
                ");

            $insertCart->bind_param(
                "iii",
                $_SESSION['user_id'],
                $productId,
                $quantity
            );

            $insertCart->execute();

            $_SESSION['message'] =
                "Product added to your cart.";

            $_SESSION['message_type'] =
                "success";
        }
    }


    header(
        "Location: product_details.php?id="
        . $productId
    );

    exit;
}


/* =========================
   GET PRODUCT
========================= */

$stmt = $conn->prepare("
    SELECT
        p.id,
        p.name,
        p.description,
        p.cost_price,
        p.selling_price,
        p.stock,
        p.image,
        c.id AS category_id,
        c.name AS category_name
    FROM products p
    INNER JOIN categories c
        ON p.category_id = c.id
    WHERE p.id = ?
    AND p.status = 'active'
    LIMIT 1
");

$stmt->bind_param(
    "i",
    $productId
);

$stmt->execute();

$result =
    $stmt->get_result();


if ($result->num_rows === 0) {

    header("Location: products.php");
    exit;

}

$product =
    $result->fetch_assoc();


/* =========================
   CART COUNT
========================= */

$cartTotal = 0;

if (isset($_SESSION['user_id'])) {

    $userId =
        (int)$_SESSION['user_id'];

    $cartStmt =
        $conn->prepare("
            SELECT COALESCE(SUM(quantity), 0)
            AS total
            FROM cart
            WHERE user_id = ?
        ");

    $cartStmt->bind_param(
        "i",
        $userId
    );

    $cartStmt->execute();

    $cartResult =
        $cartStmt->get_result();

    $cartRow =
        $cartResult->fetch_assoc();

    $cartTotal =
        (int)$cartRow['total'];
}


/* =========================
   FLASH MESSAGE
========================= */

$message =
    $_SESSION['message'] ?? "";

$messageType =
    $_SESSION['message_type'] ?? "";

unset($_SESSION['message']);
unset($_SESSION['message_type']);

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
    <?php echo e($product['name']); ?>
    | Royal Paws
</title>


<style>

/* =========================
   RESET
========================= */

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

html {
    scroll-behavior: smooth;
}

body {
    font-family:
        Georgia,
        "Times New Roman",
        serif;

    background: #f8f4ec;

    color: #2d2418;
}


/* =========================
   NAVBAR
========================= */

.navbar {
    position: sticky;

    top: 0;

    z-index: 1000;

    background:
        rgba(24,18,12,.97);

    border-bottom:
        1px solid
        rgba(212,175,55,.35);
}

.nav-container {
    width: 92%;

    max-width: 1250px;

    min-height: 76px;

    margin: auto;

    display: flex;

    align-items: center;

    justify-content:
        space-between;

    gap: 20px;
}

.logo {
    display: flex;

    align-items: center;

    gap: 10px;

    text-decoration: none;

    color: #d4af37;
}

.logo-icon {
    width: 45px;

    height: 45px;

    border:
        2px solid #d4af37;

    border-radius: 50%;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 22px;
}

.logo-text {
    font-size: 23px;

    font-weight: bold;
}

.logo-text span {
    color: #fff2ce;
}

.nav-links {
    display: flex;

    align-items: center;

    flex-wrap: wrap;

    gap: 7px;
}

.nav-links a {
    color: #eee4d0;

    text-decoration: none;

    padding: 10px 12px;

    border-radius: 8px;

    font-family: Arial, sans-serif;

    font-size: 14px;

    transition: .3s;
}

.nav-links a:hover {
    color: #d4af37;

    background:
        rgba(212,175,55,.1);

    transform:
        translateY(-2px);
}

.cart-btn {
    border:
        1px solid #d4af37;

    color: #f4dc95 !important;
}

.cart-count {
    display: inline-flex;

    align-items: center;

    justify-content: center;

    width: 20px;

    height: 20px;

    margin-left: 4px;

    background: #d4af37;

    color: #17120d;

    border-radius: 50%;

    font-size: 11px;

    font-weight: bold;
}


/* =========================
   PAGE
========================= */

.container {
    width: 92%;

    max-width: 1200px;

    margin: auto;

    padding: 65px 0;
}


/* =========================
   BREADCRUMB
========================= */

.breadcrumb {
    margin-bottom: 25px;

    font-family:
        Arial, sans-serif;

    font-size: 13px;

    color: #796f61;
}

.breadcrumb a {
    color: #906d12;

    text-decoration: none;
}

.breadcrumb a:hover {
    text-decoration: underline;
}


/* =========================
   MESSAGE
========================= */

.message {
    padding: 15px 18px;

    border-radius: 10px;

    margin-bottom: 25px;

    font-family:
        Arial, sans-serif;

    font-size: 14px;

    animation:
        messageIn .5s ease;
}

@keyframes messageIn {

    from {
        opacity: 0;

        transform:
            translateY(-10px);
    }

    to {
        opacity: 1;

        transform:
            translateY(0);
    }

}

.message.success {
    background: #e5f2df;

    border:
        1px solid #bad6ad;

    color: #46643a;
}

.message.error {
    background: #f7e0df;

    border:
        1px solid #e4b5b2;

    color: #8d3934;
}


/* =========================
   PRODUCT DETAILS
========================= */

.product-details {
    display: grid;

    grid-template-columns:
        1fr 1fr;

    gap: 55px;

    align-items: start;

    background: #fffdf8;

    border:
        1px solid #e2d6bd;

    border-radius: 25px;

    padding: 35px;

    box-shadow:
        0 15px 45px
        rgba(50,35,10,.08);

    animation:
        pageIn .7s ease;
}

@keyframes pageIn {

    from {
        opacity: 0;

        transform:
            translateY(25px);
    }

    to {
        opacity: 1;

        transform:
            translateY(0);
    }

}


/* =========================
   PRODUCT IMAGE
========================= */

.image-section {
    position: relative;
}

.product-image {
    height: 500px;

    border-radius: 20px;

    overflow: hidden;

    background:
        linear-gradient(
            135deg,
            #eee6d5,
            #f8f3e8
        );

    border:
        1px solid #e0d3ba;

    display: flex;

    align-items: center;

    justify-content: center;
}

.product-image img {
    width: 100%;

    height: 100%;

    object-fit: cover;

    transition:
        transform .6s ease;
}

.product-image:hover img {
    transform:
        scale(1.05);
}


/* =========================
   DETAILS
========================= */

.category {
    display: inline-block;

    padding: 7px 13px;

    border-radius: 20px;

    background: #f2ead8;

    color: #8b6a15;

    font-family:
        Arial, sans-serif;

    font-size: 11px;

    font-weight: bold;

    text-transform: uppercase;

    letter-spacing: 1.3px;
}

.product-title {
    font-size:
        clamp(32px, 4vw, 48px);

    line-height: 1.1;

    margin-top: 18px;

    color: #302417;
}

.description {
    margin-top: 20px;

    color: #71685b;

    font-family:
        Arial, sans-serif;

    font-size: 15px;

    line-height: 1.8;
}


/* =========================
   PRICE
========================= */

.price-box {
    margin-top: 25px;

    padding: 20px;

    border-radius: 14px;

    background:
        linear-gradient(
            135deg,
            #f9f2df,
            #fffdf8
        );

    border:
        1px solid #e6d9bd;
}

.price-label {
    color: #817666;

    font-family:
        Arial, sans-serif;

    font-size: 12px;

    text-transform: uppercase;

    letter-spacing: 1px;
}

.price {
    color: #8d6b10;

    font-size: 34px;

    font-weight: bold;

    margin-top: 5px;
}


/* =========================
   STOCK
========================= */

.stock {
    margin-top: 20px;

    padding: 13px 15px;

    border-radius: 10px;

    background: #edf5e8;

    color: #4f6c43;

    font-family:
        Arial, sans-serif;

    font-size: 13px;

    font-weight: bold;
}

.stock.out {
    background: #f7e1df;

    color: #963d38;
}


/* =========================
   QUANTITY
========================= */

.quantity-area {
    margin-top: 25px;
}

.quantity-area label {
    display: block;

    margin-bottom: 9px;

    font-family:
        Arial, sans-serif;

    font-size: 13px;

    font-weight: bold;

    color: #4f463a;
}

.quantity-control {
    display: flex;

    align-items: center;

    width: 145px;

    border:
        1px solid #d7cab1;

    border-radius: 9px;

    overflow: hidden;

    background: #fff;
}

.quantity-control button {
    width: 40px;

    height: 42px;

    border: none;

    background: #f1eadb;

    cursor: pointer;

    font-size: 18px;

    color: #4c3b20;

    transition: .2s;
}

.quantity-control button:hover {
    background: #d4af37;
}

.quantity-control input {
    width: 65px;

    height: 42px;

    border: none;

    text-align: center;

    outline: none;

    font-size: 15px;

    font-weight: bold;

    color: #302417;
}


/* =========================
   BUTTONS
========================= */

.actions {
    display: flex;

    gap: 12px;

    margin-top: 25px;

    flex-wrap: wrap;
}

.add-cart {
    flex: 1;

    min-width: 200px;

    border: none;

    padding: 15px 22px;

    border-radius: 10px;

    background: #d4af37;

    color: #21170d;

    cursor: pointer;

    font-family:
        Arial, sans-serif;

    font-size: 14px;

    font-weight: bold;

    transition: .3s;
}

.add-cart:hover {
    transform:
        translateY(-3px);

    box-shadow:
        0 12px 25px
        rgba(212,175,55,.25);
}

.back-btn {
    display: inline-flex;

    align-items: center;

    justify-content: center;

    min-width: 150px;

    padding: 15px 22px;

    border-radius: 10px;

    border:
        1px solid #cdbf9f;

    text-decoration: none;

    color: #514736;

    font-family:
        Arial, sans-serif;

    font-size: 14px;

    transition: .3s;
}

.back-btn:hover {
    border-color: #d4af37;

    color: #8a6810;
}


/* =========================
   INFO BOXES
========================= */

.info-grid {
    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 12px;

    margin-top: 30px;
}

.info-box {
    text-align: center;

    padding: 15px 10px;

    border:
        1px solid #e4d9c2;

    border-radius: 12px;

    background: #fcfaf4;
}

.info-box .icon {
    font-size: 24px;
}

.info-box strong {
    display: block;

    margin-top: 7px;

    font-family:
        Arial, sans-serif;

    font-size: 12px;

    color: #4e4538;
}


/* =========================
   FOOTER
========================= */

footer {
    background: #17120d;

    color: #cfc3ad;

    padding: 50px 0 25px;

    margin-top: 20px;
}

.footer-container {
    width: 92%;

    max-width: 1200px;

    margin: auto;

    display: grid;

    grid-template-columns:
        2fr 1fr 1fr;

    gap: 40px;
}

.footer-logo {
    color: #d4af37;

    font-size: 26px;

    margin-bottom: 15px;
}

footer h3 {
    color: #f3dda0;

    margin-bottom: 15px;
}

footer p,
footer a {
    font-family:
        Arial, sans-serif;

    font-size: 14px;

    line-height: 1.7;
}

footer a {
    display: block;

    color: #cfc3ad;

    text-decoration: none;

    margin: 8px 0;

    transition: .3s;
}

footer a:hover {
    color: #d4af37;

    padding-left: 5px;
}

.copyright {
    border-top:
        1px solid
        rgba(255,255,255,.08);

    margin-top: 40px;

    padding-top: 20px;

    text-align: center;

    font-family:
        Arial, sans-serif;

    font-size: 13px;
}


/* =========================
   RESPONSIVE
========================= */

@media(max-width:850px) {

    .nav-container {
        flex-direction: column;

        padding: 15px 0;
    }

    .nav-links {
        justify-content: center;
    }

    .product-details {
        grid-template-columns: 1fr;

        gap: 30px;

        padding: 22px;
    }

    .product-image {
        height: 400px;
    }

    .footer-container {
        grid-template-columns: 1fr;
    }

}

@media(max-width:500px) {

    .product-image {
        height: 320px;
    }

    .product-details {
        border-radius: 18px;

        padding: 15px;
    }

    .product-title {
        font-size: 34px;
    }

    .info-grid {
        grid-template-columns: 1fr;
    }

    .actions {
        flex-direction: column;
    }

    .add-cart,
    .back-btn {
        width: 100%;
    }

}

</style>

</head>

<body>

<nav class="navbar">
    <div class="nav-container">

        <a href="index.php" class="logo">
            <div class="logo-icon">🐾</div>
            <div class="logo-text">Royal <span>Paws</span></div>
        </a>

        <div class="nav-links">
            <a href="index.php">Home</a>
            <a href="products.php">Products</a>
            <a href="categories.php">Categories</a>

            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="orders.php">My Orders</a>
                <a href="feedback.php">Feedback</a>
                <a href="cart.php" class="cart-btn">
                    🛒 Cart
                    <span class="cart-count">
                        <?php echo $cartTotal; ?>
                    </span>
                </a>
                <a href="logout.php">Logout</a>
            <?php else: ?>
                <a href="login.php">Login</a>
                <a href="register.php">Register</a>
            <?php endif; ?>
        </div>

    </div>
</nav>

<main class="container">

    <div class="breadcrumb">
        <a href="index.php">Home</a>
        <span> / </span>
        <a href="products.php">Products</a>
        <span> / </span>
        <?php echo e($product['name']); ?>
    </div>

    <?php if ($message !== ""): ?>
        <div class="message <?php echo e($messageType); ?>">
            <?php echo e($message); ?>
        </div>
    <?php endif; ?>

    <section class="product-details">

        <div class="image-section">
            <div class="product-image">
                <?php
                $imagePath = productImage($product['image']);
                ?>
                <img
                    src="<?php echo e($imagePath); ?>"
                    alt="<?php echo e($product['name']); ?>"
                    onerror="this.onerror=null;this.src='assets/images/logo.png';"
                >
            </div>
        </div>

        <div class="details-section">

            <span class="category">
                <?php echo e($product['category_name']); ?>
            </span>

            <h1 class="product-title">
                <?php echo e($product['name']); ?>
            </h1>

            <p class="description">
                <?php
                echo nl2br(e(
                    $product['description'] ?: "Premium quality pet product from Royal Paws."
                ));
                ?>
            </p>

            <div class="price-box">
                <div class="price-label">Selling Price</div>
                <div class="price">
                    <?php echo money($product['selling_price']); ?>
                </div>
            </div>

            <?php if ((int)$product['stock'] > 0): ?>
                <div class="stock">
                    ✓ In Stock — <?php echo (int)$product['stock']; ?> available
                </div>
            <?php else: ?>
                <div class="stock out">
                    ✕ Out of Stock
                </div>
            <?php endif; ?>

            <?php if ((int)$product['stock'] > 0): ?>

                <form method="POST" action="" id="cartForm">

                    <div class="quantity-area">
                        <label for="quantity">Quantity</label>

                        <div class="quantity-control">
                            <button type="button" onclick="changeQty(-1)">−</button>

                            <input
                                type="number"
                                id="quantity"
                                name="quantity"
                                value="1"
                                min="1"
                                max="<?php echo (int)$product['stock']; ?>"
                                required
                            >

                            <button type="button" onclick="changeQty(1)">+</button>
                        </div>
                    </div>

                    <div class="actions">
                        <button type="submit" class="add-cart">
                            🛒 Add to Cart
                        </button>

                        <a href="products.php" class="back-btn">
                            ← Back to Products
                        </a>
                    </div>

                </form>

            <?php else: ?>

                <div class="actions">
                    <a href="products.php" class="back-btn" style="width:100%;">
                        ← Back to Products
                    </a>
                </div>

            <?php endif; ?>

            <div class="info-grid">

                <div class="info-box">
                    <div class="icon">🚚</div>
                    <strong>Fast Delivery</strong>
                </div>

                <div class="info-box">
                    <div class="icon">🔒</div>
                    <strong>Secure Checkout</strong>
                </div>

                <div class="info-box">
                    <div class="icon">⭐</div>
                    <strong>Quality Product</strong>
                </div>

            </div>

        </div>

    </section>

</main>

<footer>

    <div class="footer-container">

        <div>
            <div class="footer-logo">🐾 Royal Paws</div>
            <p>
                Premium products, food and accessories for your beloved pets.
                Shop safely and comfortably with Royal Paws.
            </p>
        </div>

        <div>
            <h3>Quick Links</h3>
            <a href="index.php">Home</a>
            <a href="products.php">Products</a>
            <a href="categories.php">Categories</a>
        </div>

        <div>
            <h3>Customer</h3>
            <a href="cart.php">Cart</a>
            <a href="orders.php">My Orders</a>
            <a href="feedback.php">Feedback</a>
        </div>

    </div>

    <div class="copyright">
        © <?php echo date("Y"); ?> Royal Paws. All Rights Reserved.
    </div>

</footer>

<script>
function changeQty(amount) {
    const input = document.getElementById("quantity");

    if (!input) return;

    let current = parseInt(input.value, 10) || 1;
    const min = parseInt(input.min, 10) || 1;
    const max = parseInt(input.max, 10) || 999999;

    current += amount;

    if (current < min) current = min;
    if (current > max) current = max;

    input.value = current;
}

document.addEventListener("DOMContentLoaded", function () {

    const form = document.getElementById("cartForm");
    const input = document.getElementById("quantity");

    if (input) {
        input.addEventListener("input", function () {
            let value = parseInt(this.value, 10) || 1;
            const min = parseInt(this.min, 10) || 1;
            const max = parseInt(this.max, 10) || 999999;

            if (value < min) value = min;
            if (value > max) value = max;

            this.value = value;
        });
    }

    if (form) {
        form.addEventListener("submit", function () {
            const button = form.querySelector(".add-cart");

            if (button) {
                button.disabled = true;
                button.innerHTML = "Adding to Cart...";
            }
        });
    }

});
</script>

</body>
</html>
