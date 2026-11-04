<?php

session_start();

require_once "config/database.php";
require_once "config/functions.php";


/* =========================================================
   CATEGORY FILTER
========================================================= */

$categoryId = isset($_GET['category'])
    ? (int)$_GET['category']
    : 0;

$search = isset($_GET['search'])
    ? trim($_GET['search'])
    : "";


/* =========================================================
   CATEGORIES
========================================================= */

$categories = [];

$categoryQuery = $conn->query("
    SELECT id, name
    FROM categories
    ORDER BY name ASC
");

if ($categoryQuery) {

    while ($row = $categoryQuery->fetch_assoc()) {
        $categories[] = $row;
    }

}


/* =========================================================
   PRODUCTS
========================================================= */

$products = false;

if ($categoryId > 0 && $search !== "") {

    $stmt = $conn->prepare("
        SELECT
            p.id,
            p.name,
            p.description,
            p.selling_price,
            p.stock,
            p.image,
            c.name AS category_name
        FROM products p
        INNER JOIN categories c
            ON p.category_id = c.id
        WHERE p.status = 'active'
        AND p.category_id = ?
        AND (
            p.name LIKE ?
            OR p.description LIKE ?
        )
        ORDER BY p.id DESC
    ");

    $searchValue = "%" . $search . "%";

    $stmt->bind_param(
        "iss",
        $categoryId,
        $searchValue,
        $searchValue
    );

    $stmt->execute();

    $products = $stmt->get_result();


} elseif ($categoryId > 0) {

    $stmt = $conn->prepare("
        SELECT
            p.id,
            p.name,
            p.description,
            p.selling_price,
            p.stock,
            p.image,
            c.name AS category_name
        FROM products p
        INNER JOIN categories c
            ON p.category_id = c.id
        WHERE p.status = 'active'
        AND p.category_id = ?
        ORDER BY p.id DESC
    ");

    $stmt->bind_param(
        "i",
        $categoryId
    );

    $stmt->execute();

    $products = $stmt->get_result();


} elseif ($search !== "") {

    $stmt = $conn->prepare("
        SELECT
            p.id,
            p.name,
            p.description,
            p.selling_price,
            p.stock,
            p.image,
            c.name AS category_name
        FROM products p
        INNER JOIN categories c
            ON p.category_id = c.id
        WHERE p.status = 'active'
        AND (
            p.name LIKE ?
            OR p.description LIKE ?
            OR c.name LIKE ?
        )
        ORDER BY p.id DESC
    ");

    $searchValue = "%" . $search . "%";

    $stmt->bind_param(
        "sss",
        $searchValue,
        $searchValue,
        $searchValue
    );

    $stmt->execute();

    $products = $stmt->get_result();


} else {

    $products = $conn->query("
        SELECT
            p.id,
            p.name,
            p.description,
            p.selling_price,
            p.stock,
            p.image,
            c.name AS category_name
        FROM products p
        INNER JOIN categories c
            ON p.category_id = c.id
        WHERE p.status = 'active'
        ORDER BY p.id DESC
    ");

}


/* =========================================================
   CART COUNT
========================================================= */

$cartTotal = 0;

if (isset($_SESSION['user_id'])) {

    $userId = (int)$_SESSION['user_id'];

    $cartStmt = $conn->prepare("
        SELECT COALESCE(SUM(quantity), 0) AS total
        FROM cart
        WHERE user_id = ?
    ");

    $cartStmt->bind_param(
        "i",
        $userId
    );

    $cartStmt->execute();

    $cartResult = $cartStmt->get_result();

    if ($cartResult) {

        $cartRow = $cartResult->fetch_assoc();

        $cartTotal = (int)$cartRow['total'];
    }
}


/* =========================================================
   REALISTIC IMAGE FALLBACKS
========================================================= */

$imageFallbacks = [

    "dog" => "https://images.unsplash.com/photo-1589924691995-400dc9ecc119?auto=format&fit=crop&w=900&q=85",

    "cat" => "https://images.unsplash.com/photo-1519052537078-e6302a4968d4?auto=format&fit=crop&w=900&q=85",

    "fish" => "https://images.unsplash.com/photo-1520990267784-9b0d7f1f3d1b?auto=format&fit=crop&w=900&q=85",

    "bird" => "https://images.unsplash.com/photo-1444464666168-49d633b86797?auto=format&fit=crop&w=900&q=85"

];


/* =========================================================
   PRODUCT IMAGE FUNCTION
========================================================= */

function getProductImage($image, $productName, $categoryName)
{

    $image = trim((string)$image);

    /*
     * If local image exists, use it.
     */

    if ($image !== "") {

        $imageName = basename($image);

        $localPath =
            "assets/images/" . $imageName;

        $serverPath =
            __DIR__
            . "/assets/images/"
            . $imageName;

        if (file_exists($serverPath)) {

            return $localPath;
        }
    }


    /*
     * Otherwise use realistic category image.
     */

    $category =
        strtolower(
            trim($categoryName)
        );

    $product =
        strtolower(
            trim($productName)
        );


    if (
        strpos($category, "dog") !== false ||
        strpos($product, "dog") !== false
    ) {

        return "https://images.unsplash.com/photo-1589924691995-400dc9ecc119?auto=format&fit=crop&w=900&q=85";

    }


    if (
        strpos($category, "cat") !== false ||
        strpos($product, "cat") !== false
    ) {

        return "https://images.unsplash.com/photo-1519052537078-e6302a4968d4?auto=format&fit=crop&w=900&q=85";

    }


    if (
        strpos($category, "fish") !== false ||
        strpos($product, "fish") !== false ||
        strpos($product, "aquarium") !== false
    ) {

        return "https://images.unsplash.com/photo-1520990267784-9b0d7f1f3d1b?auto=format&fit=crop&w=900&q=85";

    }


    if (
        strpos($category, "bird") !== false ||
        strpos($product, "bird") !== false
    ) {

        return "https://images.unsplash.com/photo-1444464666168-49d633b86797?auto=format&fit=crop&w=900&q=85";

    }


    return "assets/images/logo.png";
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

<title>Royal Paws | Products</title>


<style>

/* =========================================================
   RESET
========================================================= */

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

    overflow-x: hidden;
}


/* =========================================================
   NAVBAR
========================================================= */

.navbar {

    position: sticky;

    top: 0;

    z-index: 1000;

    background:
        rgba(24,18,12,.98);

    border-bottom:
        1px solid
        rgba(212,175,55,.35);

    backdrop-filter:
        blur(10px);
}

.nav-container {

    width: 92%;

    max-width: 1250px;

    min-height: 76px;

    margin: auto;

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 20px;
}

.logo {

    text-decoration: none;

    color: #d4af37;

    display: flex;

    align-items: center;

    gap: 10px;
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

    text-decoration: none;

    color: #eee4d0;

    font-family: Arial, sans-serif;

    font-size: 14px;

    padding: 10px 12px;

    border-radius: 8px;

    transition: .3s;
}

.nav-links a:hover {

    color: #d4af37;

    background:
        rgba(212,175,55,.1);
}

.cart-btn {

    border:
        1px solid #d4af37;

    color:
        #f4dc95 !important;
}

.cart-count {

    display: inline-flex;

    width: 20px;

    height: 20px;

    align-items: center;

    justify-content: center;

    background: #d4af37;

    color: #17120d;

    border-radius: 50%;

    font-size: 11px;

    font-weight: bold;

    margin-left: 4px;
}


/* =========================================================
   HERO
========================================================= */

.page-header {

    min-height: 330px;

    padding: 80px 20px;

    text-align: center;

    color: white;

    position: relative;

    overflow: hidden;

    background:
        linear-gradient(
            110deg,
            rgba(29,21,14,.96),
            rgba(80,55,23,.92),
            rgba(29,21,14,.96)
        );
}

.page-header::before {

    content: "🐕";

    position: absolute;

    left: 5%;

    bottom: -45px;

    font-size: 220px;

    opacity: .08;

    transform: rotate(-8deg);
}

.page-header::after {

    content: "🐈";

    position: absolute;

    right: 6%;

    top: -50px;

    font-size: 220px;

    opacity: .08;

    transform: rotate(8deg);
}

.page-header small {

    color: #d4af37;

    font-family: Arial, sans-serif;

    letter-spacing: 3px;

    text-transform: uppercase;

    position: relative;

    z-index: 2;
}

.page-header h1 {

    margin-top: 12px;

    font-size:
        clamp(42px,6vw,65px);

    color: #fff0c6;

    position: relative;

    z-index: 2;
}

.page-header p {

    margin: 15px auto 0;

    max-width: 650px;

    color: #e4d8c1;

    font-family: Arial, sans-serif;

    line-height: 1.7;

    position: relative;

    z-index: 2;
}


/* =========================================================
   CONTAINER
========================================================= */

.container {

    width: 92%;

    max-width: 1250px;

    margin: auto;

    padding: 60px 0;
}


/* =========================================================
   SEARCH
========================================================= */

.search-box {

    background: #fffdf8;

    border:
        1px solid #e2d7bf;

    border-radius: 18px;

    padding: 20px;

    display: flex;

    gap: 12px;

    margin-bottom: 35px;

    box-shadow:
        0 8px 25px
        rgba(50,35,10,.06);
}

.search-input {

    flex: 1;

    border:
        1px solid #d8ccb4;

    border-radius: 10px;

    padding: 14px 16px;

    font-family: Arial, sans-serif;

    outline: none;

    font-size: 14px;
}

.search-input:focus {

    border-color: #d4af37;

    box-shadow:
        0 0 0 3px
        rgba(212,175,55,.12);
}

.search-button {

    border: none;

    background: #251a0f;

    color: #f3dc94;

    padding: 0 25px;

    border-radius: 10px;

    cursor: pointer;

    font-weight: bold;

    transition: .3s;
}

.search-button:hover {

    background: #d4af37;

    color: #21170d;
}


/* =========================================================
   CATEGORY
========================================================= */

.category-filter {

    display: flex;

    gap: 10px;

    flex-wrap: wrap;

    margin-bottom: 40px;
}

.category-filter a {

    text-decoration: none;

    padding: 10px 18px;

    border-radius: 25px;

    border:
        1px solid #d9cdb6;

    color: #5f5547;

    background: #fffdf8;

    font-family: Arial, sans-serif;

    font-size: 13px;

    transition: .3s;
}

.category-filter a:hover {

    border-color: #d4af37;

    color: #916f13;

    transform:
        translateY(-2px);
}

.category-filter a.active {

    background: #d4af37;

    border-color: #d4af37;

    color: #21170d;

    font-weight: bold;
}


/* =========================================================
   TITLE
========================================================= */

.results-title {

    margin-bottom: 25px;

    display: flex;

    justify-content: space-between;

    align-items: center;

    gap: 15px;
}

.results-title h2 {

    font-size: 28px;
}

.results-title span {

    font-family: Arial, sans-serif;

    color: #806f52;

    font-size: 13px;
}


/* =========================================================
   GRID
========================================================= */

.product-grid {

    display: grid;

    grid-template-columns:
        repeat(4,minmax(0,1fr));

    gap: 22px;
}


/* =========================================================
   CARD
========================================================= */

.product-card {

    background: #fffdf8;

    border:
        1px solid #e1d5bc;

    border-radius: 18px;

    overflow: hidden;

    transition:
        transform .35s ease,
        box-shadow .35s ease,
        border-color .35s ease;

    opacity: 0;

    transform:
        translateY(25px);

    animation:
        cardShow .7s
        ease forwards;
}

@keyframes cardShow {

    to {

        opacity: 1;

        transform:
            translateY(0);
    }
}

.product-card:hover {

    transform:
        translateY(-8px);

    border-color: #d4af37;

    box-shadow:
        0 18px 40px
        rgba(59,42,13,.15);
}


/* =========================================================
   IMAGE
========================================================= */

.product-image {

    width: 100%;

    height: 250px;

    background:
        #eee7d8;

    overflow: hidden;

    position: relative;
}

.product-image img {

    width: 100%;

    height: 100%;

    object-fit: cover;

    object-position: center;

    display: block;

    transition:
        transform .5s ease;

    backface-visibility: hidden;
}

.product-card:hover
.product-image img {

    transform:
        scale(1.06);
}


/* =========================================================
   STOCK
========================================================= */

.stock-badge {

    position: absolute;

    top: 12px;

    right: 12px;

    padding: 7px 10px;

    border-radius: 20px;

    font-family: Arial, sans-serif;

    font-size: 11px;

    font-weight: bold;

    background: #e7f1df;

    color: #476338;
}

.stock-badge.out {

    background: #f4dddd;

    color: #9b3b3b;
}


/* =========================================================
   INFO
========================================================= */

.product-info {

    padding: 20px;
}

.category-name {

    color: #a17d15;

    font-family: Arial, sans-serif;

    font-size: 11px;

    letter-spacing: 1.5px;

    text-transform: uppercase;
}

.product-info h3 {

    font-size: 19px;

    margin: 8px 0;

    line-height: 1.35;
}

.description {

    color: #776e60;

    font-family: Arial, sans-serif;

    font-size: 13px;

    line-height: 1.6;

    min-height: 42px;
}

.price {

    margin-top: 13px;

    color: #896711;

    font-size: 22px;

    font-weight: bold;
}

.product-button {

    display: block;

    text-align: center;

    text-decoration: none;

    margin-top: 16px;

    padding: 12px;

    border-radius: 9px;

    background: #251a0f;

    color: #f3dc94;

    font-family: Arial, sans-serif;

    font-weight: bold;

    font-size: 13px;

    transition: .3s;
}

.product-button:hover {

    background: #d4af37;

    color: #21170d;

    transform:
        translateY(-2px);
}


/* =========================================================
   EMPTY
========================================================= */

.empty {

    background: #fffdf8;

    border:
        1px solid #e2d6bd;

    border-radius: 18px;

    padding: 70px 20px;

    text-align: center;
}

.empty-icon {

    font-size: 55px;
}

.empty h3 {

    margin-top: 15px;

    font-size: 25px;
}

.empty p {

    margin-top: 8px;

    color: #756c5e;

    font-family: Arial, sans-serif;
}


/* =========================================================
   FOOTER
========================================================= */

footer {

    background: #17120d;

    color: #cfc3ad;

    padding: 50px 0 25px;
}

.footer-container {

    width: 92%;

    max-width: 1250px;

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

    color: #f2dda0;

    margin-bottom: 15px;
}

footer p,
footer a {

    font-family: Arial, sans-serif;

    font-size: 14px;

    line-height: 1.7;
}

footer a {

    display: block;

    text-decoration: none;

    color: #cfc3ad;

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

    font-family: Arial, sans-serif;

    font-size: 13px;
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media(max-width:1050px) {

    .product-grid {

        grid-template-columns:
            repeat(3,minmax(0,1fr));
    }

}

@media(max-width:800px) {

    .nav-container {

        flex-direction: column;

        padding: 15px 0;
    }

    .nav-links {

        justify-content: center;
    }

    .product-grid {

        grid-template-columns:
            repeat(2,minmax(0,1fr));
    }

}

@media(max-width:550px) {

    .product-grid {

        grid-template-columns: 1fr;
    }

    .search-box {

        flex-direction: column;
    }

    .search-button {

        padding: 14px;
    }

    .results-title {

        flex-direction: column;

        align-items: flex-start;
    }

    .footer-container {

        grid-template-columns: 1fr;
    }

    .product-image {

        height: 280px;
    }

}

</style>

</head>


<body>


<!-- =====================================================
     NAVBAR
===================================================== -->

<nav class="navbar">

    <div class="nav-container">

        <a
            href="index.php"
            class="logo"
        >

            <div class="logo-icon">
                🐾
            </div>

            <div class="logo-text">
                Royal <span>Paws</span>
            </div>

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


            <?php if (isset($_SESSION['user_id'])): ?>

                <a href="orders.php">
                    My Orders
                </a>

                <a href="feedback.php">
                    Feedback
                </a>

                <a href="logout.php">
                    Logout
                </a>

            <?php else: ?>

                <a href="login.php">
                    Login
                </a>

                <a href="register.php">
                    Register
                </a>

            <?php endif; ?>


            <a
                href="cart.php"
                class="cart-btn"
            >

                🛒 Cart

                <span class="cart-count">
                    <?php
                    echo $cartTotal;
                    ?>
                </span>

            </a>

        </div>

    </div>

</nav>


<!-- =====================================================
     HEADER
===================================================== -->

<section class="page-header">

    <small>
        Royal Paws Collection
    </small>

    <h1>
        Our Products
    </h1>

    <p>
        Explore premium products specially selected
        for your beloved pets.
    </p>

</section>


<!-- =====================================================
     MAIN
===================================================== -->

<main class="container">


    <!-- SEARCH -->

    <form
        method="GET"
        action="products.php"
        class="search-box"
    >

        <?php if ($categoryId > 0): ?>

            <input
                type="hidden"
                name="category"
                value="<?php
                    echo $categoryId;
                ?>"
            >

        <?php endif; ?>


        <input
            type="text"
            name="search"
            class="search-input"
            placeholder="Search pet products..."
            value="<?php
                echo e($search);
            ?>"
        >


        <button
            type="submit"
            class="search-button"
        >

            🔍 Search

        </button>

    </form>


    <!-- CATEGORY -->

    <div class="category-filter">

        <a
            href="products.php"
            class="<?php
                echo $categoryId === 0
                    ? 'active'
                    : '';
            ?>"
        >
            All Products
        </a>


        <?php foreach ($categories as $category): ?>

            <a
                href="products.php?category=<?php
                    echo (int)$category['id'];
                ?>"
                class="<?php
                    echo
                    $categoryId ===
                    (int)$category['id']
                        ? 'active'
                        : '';
                ?>"
            >

                <?php
                echo e($category['name']);
                ?>

            </a>

        <?php endforeach; ?>

    </div>


    <!-- RESULT TITLE -->

    <div class="results-title">

        <h2>

            <?php

            if ($categoryId > 0) {

                foreach (
                    $categories
                    as $category
                ) {

                    if (
                        (int)$category['id']
                        === $categoryId
                    ) {

                        echo e(
                            $category['name']
                        );

                        break;
                    }
                }

            } else {

                echo "All Products";

            }

            ?>

        </h2>


        <?php if ($search !== ""): ?>

            <span>

                Search:
                "<?php
                echo e($search);
                ?>"

            </span>

        <?php endif; ?>

    </div>


    <!-- =================================================
         PRODUCT GRID
    ================================================= -->

    <?php if (
        $products &&
        $products->num_rows > 0
    ): ?>

        <div class="product-grid">


            <?php

            $delay = 0;

            while (
                $product =
                $products->fetch_assoc()
            ):

                $delay += 80;


                $imagePath =
                    getProductImage(
                        $product['image'],
                        $product['name'],
                        $product['category_name']
                    );

            ?>

                <div
                    class="product-card"
                    style="
                        animation-delay:
                        <?php echo $delay; ?>ms;
                    "
                >


                    <!-- IMAGE -->

                    <div class="product-image">

                        <img
                            src="<?php
                                echo e(
                                    $imagePath
                                );
                            ?>"
                            alt="<?php
                                echo e(
                                    $product['name']
                                );
                            ?>"
                            loading="lazy"
                            onerror="
                                this.onerror=null;
                                this.src='assets/images/logo.png';
                            "
                        >


                        <?php if (
                            (int)$product['stock'] > 0
                        ): ?>

                            <div
                                class="stock-badge"
                            >

                                ✓

                                <?php
                                echo
                                (int)$product['stock'];
                                ?>

                                Available

                            </div>

                        <?php else: ?>

                            <div
                                class="stock-badge out"
                            >

                                Out of Stock

                            </div>

                        <?php endif; ?>

                    </div>


                    <!-- INFO -->

                    <div class="product-info">


                        <div class="category-name">

                            <?php
                            echo e(
                                $product[
                                    'category_name'
                                ]
                            );
                            ?>

                        </div>


                        <h3>

                            <?php
                            echo e(
                                $product['name']
                            );
                            ?>

                        </h3>


                        <div class="description">

                            <?php

                            $description =
                                (string)
                                $product[
                                    'description'
                                ];

                            if (
                                strlen(
                                    $description
                                ) > 85
                            ) {

                                $description =
                                    substr(
                                        $description,
                                        0,
                                        85
                                    ) . "...";
                            }

                            echo e(
                                $description
                            );

                            ?>

                        </div>


                        <div class="price">

                            ₹<?php

                            echo number_format(
                                (float)
                                $product[
                                    'selling_price'
                                ],
                                2
                            );

                            ?>

                        </div>


                        <a
                            href="product_details.php?id=<?php
                                echo (int)
                                    $product['id'];
                            ?>"
                            class="product-button"
                        >

                            View Product →

                        </a>


                    </div>

                </div>


            <?php endwhile; ?>


        </div>


    <?php else: ?>


        <div class="empty">

            <div class="empty-icon">
                🐾
            </div>

            <h3>
                No Products Found
            </h3>

            <p>
                Try another search or choose
                a different category.
            </p>

            <br>

            <a
                href="products.php"
                class="product-button"
                style="
                    max-width:200px;
                    margin:auto;
                "
            >

                View All Products

            </a>

        </div>


    <?php endif; ?>


</main>


<!-- =====================================================
     FOOTER
===================================================== -->

<footer>

    <div class="footer-container">


        <div>

            <div class="footer-logo">
                🐾 Royal Paws
            </div>

            <p>
                Premium food, toys and accessories
                for your beloved pets.
            </p>

        </div>


        <div>

            <h3>
                Quick Links
            </h3>

            <a href="index.php">
                Home
            </a>

            <a href="products.php">
                Products
            </a>

            <a href="categories.php">
                Categories
            </a>

            <a href="cart.php">
                Cart
            </a>

        </div>


        <div>

            <h3>
                Account
            </h3>


            <?php if (isset($_SESSION['user_id'])): ?>

                <a href="orders.php">
                    My Orders
                </a>

                <a href="feedback.php">
                    Feedback
                </a>

                <a href="logout.php">
                    Logout
                </a>

            <?php else: ?>

                <a href="login.php">
                    Login
                </a>

                <a href="register.php">
                    Register
                </a>

            <?php endif; ?>

        </div>

    </div>


    <div class="copyright">

        ©
        <?php echo date("Y"); ?>
        Royal Paws.
        All Rights Reserved.

    </div>

</footer>


<script>

/* =====================================================
   SEARCH ESCAPE
===================================================== */

const searchInput =
    document.querySelector(
        ".search-input"
    );

if (searchInput) {

    searchInput.addEventListener(
        "keydown",
        function(event) {

            if (
                event.key === "Escape"
            ) {

                this.value = "";

                this.focus();

            }

        }
    );

}


/* =====================================================
   IMAGE FALLBACK
===================================================== */

document
.querySelectorAll(".product-image img")
.forEach(function(img) {

    img.addEventListener(
        "error",
        function() {

            if (
                !this.dataset.failed
            ) {

                this.dataset.failed =
                    "true";

                this.src =
                    "assets/images/logo.png";
            }

        }
    );

});


/* =====================================================
   CARD Z-INDEX
===================================================== */

document
.querySelectorAll(".product-card")
.forEach(function(card) {

    card.addEventListener(
        "mouseenter",
        function() {

            this.style.zIndex = "5";

        }
    );

    card.addEventListener(
        "mouseleave",
        function() {

            this.style.zIndex = "1";

        }
    );

});

</script>


</body>

</html>