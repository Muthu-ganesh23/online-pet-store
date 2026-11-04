```php
<?php
session_start();

require_once "config/database.php";
require_once "config/functions.php";

/* =========================
   GET CATEGORIES
========================= */

$categoryQuery = $conn->query("
    SELECT
        c.id,
        c.name,
        c.description,
        c.image,
        COUNT(p.id) AS product_count
    FROM categories c
    LEFT JOIN products p
        ON c.id = p.category_id
        AND p.status = 'active'
    GROUP BY
        c.id,
        c.name,
        c.description,
        c.image
    ORDER BY c.id ASC
");


/* =========================
   CART COUNT
========================= */

$cartTotal = 0;

if (isset($_SESSION['user_id'])) {

    $userId = (int)$_SESSION['user_id'];

    $cartStmt = $conn->prepare("
        SELECT COALESCE(SUM(quantity), 0) AS total
        FROM cart
        WHERE user_id = ?
    ");

    $cartStmt->bind_param("i", $userId);

    $cartStmt->execute();

    $cartResult = $cartStmt->get_result();

    $cartRow = $cartResult->fetch_assoc();

    $cartTotal = (int)$cartRow['total'];
}


/* =========================
   CATEGORY ICON
========================= */

function categoryIcon($name)
{
    switch (strtolower(trim($name))) {

        case "dogs":
            return "🐶";

        case "cats":
            return "🐱";

        case "birds":
            return "🐦";

        case "fish":
            return "🐟";

        default:
            return "🐾";
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

<title>Royal Paws | Categories</title>


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
    font-family: Georgia, "Times New Roman", serif;
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

    background: rgba(24,18,12,.97);

    border-bottom:
        1px solid rgba(212,175,55,.35);
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

    transform: translateY(-2px);
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
   HERO
========================= */

.page-hero {
    min-height: 420px;

    display: flex;
    align-items: center;
    justify-content: center;

    text-align: center;

    padding: 70px 20px;

    background:
        linear-gradient(
            120deg,
            #1c140c,
            #3c2a15,
            #1c140c
        );

    position: relative;

    overflow: hidden;

    color: white;
}

.page-hero::before {
    content: "";

    position: absolute;

    width: 420px;
    height: 420px;

    border:
        1px solid rgba(212,175,55,.18);

    border-radius: 50%;

    top: -200px;
    left: -120px;
}

.page-hero::after {
    content: "";

    position: absolute;

    width: 500px;
    height: 500px;

    border:
        1px solid rgba(212,175,55,.12);

    border-radius: 50%;

    bottom: -300px;
    right: -120px;
}

.hero-content {
    position: relative;

    z-index: 2;

    animation:
        heroAppear .9s ease;
}

@keyframes heroAppear {

    from {
        opacity: 0;
        transform: translateY(30px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }

}

.hero-paw {
    font-size: 60px;

    display: inline-block;

    animation:
        pawFloat 3s ease-in-out infinite;
}

@keyframes pawFloat {

    0%,100% {
        transform: translateY(0);
    }

    50% {
        transform: translateY(-10px);
    }

}

.page-hero small {
    display: block;

    margin-top: 12px;

    color: #d4af37;

    font-family: Arial, sans-serif;

    letter-spacing: 3px;

    text-transform: uppercase;

    font-size: 12px;
}

.page-hero h1 {
    margin-top: 10px;

    font-size:
        clamp(43px, 6vw, 68px);

    color: #fff1c9;
}

.page-hero p {
    max-width: 650px;

    margin: 15px auto 0;

    color: #cfc3ad;

    font-family: Arial, sans-serif;

    line-height: 1.7;
}


/* =========================
   CATEGORY SECTION
========================= */

.container {
    width: 92%;

    max-width: 1250px;

    margin: auto;

    padding: 75px 0;
}

.section-title {
    text-align: center;

    margin-bottom: 45px;
}

.section-title small {
    color: #a07b15;

    font-family: Arial, sans-serif;

    text-transform: uppercase;

    letter-spacing: 3px;

    font-size: 11px;
}

.section-title h2 {
    margin-top: 9px;

    font-size: 40px;

    color: #302417;
}

.section-title p {
    margin-top: 10px;

    color: #756d61;

    font-family: Arial, sans-serif;
}


/* =========================
   CATEGORY GRID
========================= */

.category-grid {
    display: grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap: 25px;
}


/* =========================
   CATEGORY CARD
========================= */

.category-card {
    background: #fffdf8;

    border:
        1px solid #e2d6bd;

    border-radius: 22px;

    overflow: hidden;

    text-decoration: none;

    color: #302417;

    position: relative;

    transition:
        transform .4s ease,
        box-shadow .4s ease,
        border-color .4s ease;

    opacity: 0;

    transform: translateY(35px);
}

.category-card.show {
    opacity: 1;

    transform:
        translateY(0);
}

.category-card:hover {
    transform:
        translateY(-12px);

    border-color: #d4af37;

    box-shadow:
        0 20px 45px
        rgba(55,39,12,.15);
}


/* =========================
   CATEGORY IMAGE
========================= */

.category-image {
    height: 230px;

    background:
        linear-gradient(
            135deg,
            #eee6d5,
            #f8f3e8
        );

    position: relative;

    overflow: hidden;

    display: flex;

    align-items: center;

    justify-content: center;
}

.category-image img {
    width: 100%;
    height: 100%;

    object-fit: cover;

    transition: .6s;
}

.category-card:hover
.category-image img {
    transform: scale(1.08);
}


/* Image fallback */

.category-image .icon-fallback {
    position: absolute;

    font-size: 85px;

    z-index: 0;
}

.category-image img {
    position: relative;

    z-index: 1;
}


/* =========================
   CATEGORY ICON
========================= */

.category-icon {
    position: absolute;

    z-index: 3;

    left: 50%;
    top: 50%;

    transform:
        translate(-50%, -50%);

    width: 80px;
    height: 80px;

    border-radius: 50%;

    display: flex;

    align-items: center;
    justify-content: center;

    background:
        rgba(25,18,11,.75);

    border:
        2px solid #d4af37;

    font-size: 38px;

    opacity: 0;

    transition: .4s;
}

.category-card:hover
.category-icon {
    opacity: 1;
}


/* =========================
   CARD CONTENT
========================= */

.category-content {
    padding: 25px;

    text-align: center;
}

.category-content h3 {
    font-size: 27px;

    color: #342719;
}

.category-content p {
    margin-top: 10px;

    min-height: 45px;

    color: #766d61;

    font-family: Arial, sans-serif;

    font-size: 13px;

    line-height: 1.6;
}

.product-count {
    display: inline-block;

    margin-top: 16px;

    padding: 7px 13px;

    border-radius: 20px;

    background: #f2ead8;

    color: #806415;

    font-family: Arial, sans-serif;

    font-size: 12px;

    font-weight: bold;
}

.explore-btn {
    display: block;

    margin-top: 17px;

    padding: 12px;

    border-radius: 9px;

    background: #251a0f;

    color: #f3dc94;

    font-family: Arial, sans-serif;

    font-size: 13px;

    font-weight: bold;

    transition: .3s;
}

.category-card:hover
.explore-btn {
    background: #d4af37;

    color: #21170d;
}


/* =========================
   BOTTOM BANNER
========================= */

.bottom-banner {
    margin-top: 70px;

    padding: 45px;

    border-radius: 22px;

    background:
        linear-gradient(
            110deg,
            #271b0e,
            #412d15
        );

    text-align: center;

    color: white;

    position: relative;

    overflow: hidden;
}

.bottom-banner::after {
    content: "ROYAL PAWS";

    position: absolute;

    right: -20px;
    bottom: -45px;

    font-size: 100px;

    font-weight: bold;

    color: rgba(212,175,55,.06);
}

.bottom-banner h2 {
    color: #f4dfa0;

    font-size: 34px;

    position: relative;

    z-index: 2;
}

.bottom-banner p {
    margin-top: 10px;

    color: #cfc4b1;

    font-family: Arial, sans-serif;

    position: relative;

    z-index: 2;
}

.shop-btn {
    display: inline-block;

    margin-top: 22px;

    padding: 13px 24px;

    border-radius: 9px;

    background: #d4af37;

    color: #21170d;

    text-decoration: none;

    font-family: Arial, sans-serif;

    font-weight: bold;

    position: relative;

    z-index: 2;

    transition: .3s;
}

.shop-btn:hover {
    transform: translateY(-4px);

    box-shadow:
        0 10px 25px
        rgba(212,175,55,.25);
}


/* =========================
   FOOTER
========================= */

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
    color: #f3dda0;

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
        1px solid rgba(255,255,255,.08);

    margin-top: 40px;

    padding-top: 20px;

    text-align: center;

    font-family: Arial, sans-serif;

    font-size: 13px;
}


/* =========================
   RESPONSIVE
========================= */

@media(max-width:1000px) {

    .category-grid {
        grid-template-columns:
            repeat(2, 1fr);
    }

}

@media(max-width:700px) {

    .nav-container {
        flex-direction: column;

        padding: 15px 0;
    }

    .nav-links {
        justify-content: center;
    }

    .category-grid {
        grid-template-columns: 1fr;
    }

    .page-hero {
        min-height: 360px;
    }

    .bottom-banner {
        padding: 30px 20px;
    }

    .footer-container {
        grid-template-columns: 1fr;
    }

}

</style>

</head>


<body>


<!-- =========================
     NAVBAR
========================= -->

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
                    <?php echo $cartTotal; ?>
                </span>

            </a>

        </div>

    </div>

</nav>


<!-- =========================
     PAGE HERO
========================= -->

<section class="page-hero">

    <div class="hero-content">

        <div class="hero-paw">
            🐾
        </div>

        <small>
            Royal Paws Collection
        </small>

        <h1>
            Pet Categories
        </h1>

        <p>
            Find everything your beloved pets need.
            Explore our specially organized categories
            for dogs, cats, birds and fish.
        </p>

    </div>

</section>


<!-- =========================
     CATEGORY SECTION
========================= -->

<main class="container">


    <div class="section-title">

        <small>
            Choose Your Pet
        </small>

        <h2>
            Shop By Category
        </h2>

        <p>
            Select a category and discover our complete collection.
        </p>

    </div>


    <div class="category-grid">


        <?php if (
            $categoryQuery &&
            $categoryQuery->num_rows > 0
        ): ?>


            <?php

            $delay = 0;

            while (
                $category =
                $categoryQuery->fetch_assoc()
            ):

                $delay += 150;

                $icon =
                    categoryIcon(
                        $category['name']
                    );

            ?>


                <a
                    href="products.php?category=<?php
                        echo (int)$category['id'];
                    ?>"
                    class="category-card"
                    data-delay="<?php
                        echo $delay;
                    ?>"
                >


                    <div class="category-image">


                        <div class="icon-fallback">
                            <?php echo $icon; ?>
                        </div>


                        <?php if (
                            !empty($category['image'])
                        ): ?>

                            <img
                                src="<?php
                                    echo e(
                                        productImage(
                                            $category['image']
                                        )
                                    );
                                ?>"
                                alt="<?php
                                    echo e(
                                        $category['name']
                                    );
                                ?>"
                                onerror="
                                    this.style.display='none';
                                "
                            >

                        <?php endif; ?>


                        <div class="category-icon">
                            <?php echo $icon; ?>
                        </div>


                    </div>


                    <div class="category-content">


                        <h3>
                            <?php
                            echo e(
                                $category['name']
                            );
                            ?>
                        </h3>


                        <p>
                            <?php

                            if (
                                !empty(
                                    $category['description']
                                )
                            ) {

                                echo e(
                                    $category['description']
                                );

                            } else {

                                echo "Explore premium products for your pet.";

                            }

                            ?>
                        </p>


                        <span class="product-count">

                            <?php
                            echo (int)$category[
                                'product_count'
                            ];
                            ?>

                            Products

                        </span>


                        <div class="explore-btn">
                            Explore Category →
                        </div>


                    </div>

                </a>


            <?php endwhile; ?>


        <?php else: ?>


            <div
                style="
                    grid-column:1/-1;
                    text-align:center;
                    padding:70px 20px;
                    background:#fffdf8;
                    border:1px solid #e2d6bd;
                    border-radius:20px;
                "
            >

                <div style="font-size:55px;">
                    🐾
                </div>

                <h2 style="margin-top:15px;">
                    No Categories Available
                </h2>

                <p
                    style="
                        margin-top:10px;
                        color:#756d61;
                        font-family:Arial,sans-serif;
                    "
                >
                    Please add categories from the admin panel.
                </p>

            </div>


        <?php endif; ?>


    </div>


    <!-- =========================
         BOTTOM BANNER
    ========================= -->

    <div class="bottom-banner">

        <h2>
            Give Your Pet The Royal Treatment 👑
        </h2>

        <p>
            Premium products, beautiful accessories
            and everything your pet needs.
        </p>

        <a
            href="products.php"
            class="shop-btn"
        >
            Shop All Products
        </a>

    </div>


</main>


<!-- =========================
     FOOTER
========================= -->

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

        © <?php echo date("Y"); ?>

        Royal Paws.

        All Rights Reserved.

    </div>

</footer>


<script>

/* =========================
   CATEGORY SCROLL ANIMATION
========================= */

const cards =
    document.querySelectorAll(".category-card");


const categoryObserver =
    new IntersectionObserver(

        function(entries) {

            entries.forEach(function(entry) {

                if (entry.isIntersecting) {

                    const delay =
                        entry.target.dataset.delay || 0;

                    setTimeout(
                        function() {

                            entry.target
                                .classList
                                .add("show");

                        },
                        parseInt(delay)
                    );

                    categoryObserver
                        .unobserve(
                            entry.target
                        );
                }

            });

        },

        {
            threshold: 0.15
        }

    );


cards.forEach(function(card) {

    categoryObserver.observe(card);

});


/* =========================
   IMAGE ERROR HANDLING
========================= */

document
    .querySelectorAll(".category-image img")
    .forEach(function(img) {

        img.addEventListener(
            "error",
            function() {

                this.style.display = "none";

            }
        );

    });


/* =========================
   CARD CLICK EFFECT
========================= */

cards.forEach(function(card) {

    card.addEventListener(
        "click",
        function() {

            this.style.transform =
                "scale(.98)";

            setTimeout(
                function() {

                    card.style.transform = "";

                },
                150
            );

        }
    );

});

</script>


</body>
</html>
```
