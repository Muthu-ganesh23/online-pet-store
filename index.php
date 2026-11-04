<?php

session_start();

require_once "config/database.php";
require_once "config/functions.php";


/* =========================================================
   IMAGE HELPER
========================================================= */

function safeProductImage($image)
{
    $image = trim((string)$image);

    if ($image === "") {
        return "";
    }

    if (function_exists("productImage")) {
        return productImage($image);
    }

    return "assets/images/" . basename($image);
}


/* =========================================================
   GET CATEGORIES
========================================================= */

$categoryQuery = $conn->query("
    SELECT
        id,
        name,
        description,
        image
    FROM categories
    ORDER BY id ASC
");


/* =========================================================
   GET FEATURED PRODUCTS
========================================================= */

$productQuery = $conn->query("
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
    LIMIT 8
");


/* =========================================================
   USER
========================================================= */

$userName = $_SESSION["user_name"] ?? "";

$isLoggedIn = isset($_SESSION["user_id"]);


/* =========================================================
   CART COUNT
========================================================= */

$cartTotal = 0;

if ($isLoggedIn) {

    $userId = (int)$_SESSION["user_id"];

    $cartStmt = $conn->prepare("
        SELECT COALESCE(SUM(quantity), 0) AS total
        FROM cart
        WHERE user_id = ?
    ");

    if ($cartStmt) {

        $cartStmt->bind_param("i", $userId);

        if ($cartStmt->execute()) {

            $cartResult = $cartStmt->get_result();

            if ($cartResult) {

                $cartRow = $cartResult->fetch_assoc();

                if ($cartRow) {
                    $cartTotal = (int)$cartRow["total"];
                }
            }
        }

        $cartStmt->close();
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

<meta
    name="description"
    content="Royal Paws - Premium online pet store for food, toys and accessories."
>

<title>Royal Paws | Online Pet Store</title>


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

    color: #292015;

    overflow-x: hidden;

    background:
        #f8f4ec;
}

a {
    -webkit-tap-highlight-color: transparent;
}


/* =========================================================
   LOADER
========================================================= */

.loader {

    position: fixed;
    inset: 0;

    width: 100%;
    height: 100vh;

    background:
        radial-gradient(
            circle at 50% 45%,
            #24170f 0%,
            #100b08 45%,
            #050505 100%
        );

    z-index: 999999;

    display: flex;

    justify-content: center;
    align-items: center;

    flex-direction: column;

    overflow: hidden;

    opacity: 1;
    visibility: visible;

    transition:
        opacity .9s ease,
        visibility .9s ease;
}

.loader::before {

    content: "";

    position: absolute;

    width: 500px;
    height: 500px;

    border-radius: 50%;

    background:
        radial-gradient(
            circle,
            rgba(170,20,20,.20),
            transparent 70%
        );

    filter: blur(30px);

    animation:
        loaderGlow 3s ease-in-out infinite;
}

@keyframes loaderGlow {

    0%,100% {
        transform: scale(.85);
        opacity: .45;
    }

    50% {
        transform: scale(1.15);
        opacity: .85;
    }
}

.loader.hide {

    opacity: 0;
    visibility: hidden;
    pointer-events: none;
}

.loader-brand {

    position: relative;
    z-index: 2;

    color: #d4af37;

    font-size:
        clamp(35px,7vw,70px);

    font-weight: bold;

    letter-spacing:
        clamp(5px,1.5vw,13px);

    text-align: center;

    opacity: 0;

    transform: scale(.55);

    animation:
        netflixLogoIn 1.2s
        cubic-bezier(.16,1,.3,1)
        forwards;

    text-shadow:
        0 0 10px rgba(212,175,55,.25),
        0 0 30px rgba(212,175,55,.18);
}

@keyframes netflixLogoIn {

    0% {
        opacity: 0;
        transform: scale(.55);
        letter-spacing: 20px;
    }

    55% {
        opacity: 1;
        transform: scale(1.08);
    }

    100% {
        opacity: 1;
        transform: scale(1);
        letter-spacing: clamp(5px,1.5vw,13px);
    }
}

.loader-paws {

    position: relative;
    z-index: 2;

    margin-top: 13px;

    font-size: 23px;

    letter-spacing: 7px;

    opacity: 0;

    animation:
        pawsIn 1s ease 1s forwards;
}

@keyframes pawsIn {

    from {
        opacity: 0;
        transform: translateY(12px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.loader-subtitle {

    position: relative;
    z-index: 2;

    margin-top: 18px;

    color: #d2c6b4;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    font-size: 11px;

    letter-spacing: 4px;

    text-transform: uppercase;

    opacity: 0;

    animation:
        subtitleIn 1s ease 1.25s forwards;
}

@keyframes subtitleIn {

    from {
        opacity: 0;
        transform: translateY(10px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.loader-line {

    position: relative;
    z-index: 2;

    width:
        min(360px,72vw);

    height: 3px;

    margin-top: 30px;

    background:
        rgba(255,255,255,.10);

    overflow: hidden;

    border-radius: 20px;

    opacity: 0;

    animation:
        lineAppear .5s ease 1.4s forwards;
}

@keyframes lineAppear {

    from {
        opacity: 0;
    }

    to {
        opacity: 1;
    }
}

.loader-progress {

    position: absolute;

    left: -100%;
    top: 0;

    width: 100%;
    height: 100%;

    background:
        linear-gradient(
            90deg,
            #6f0e0e,
            #b51d1d,
            #d4af37,
            #fff1ae,
            #d4af37,
            #b51d1d
        );

    box-shadow:
        0 0 15px rgba(212,175,55,.8);

    animation:
        netflixProgress 2.4s
        cubic-bezier(.4,0,.2,1)
        .5s forwards;
}

@keyframes netflixProgress {

    0% {
        left: -100%;
    }

    100% {
        left: 100%;
    }
}

.loader-status {

    position: relative;
    z-index: 2;

    margin-top: 13px;

    color: #a99b87;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    font-size: 9px;

    letter-spacing: 3px;

    opacity: 0;

    animation:
        statusIn .6s ease 1.6s forwards;
}

@keyframes statusIn {

    from {
        opacity: 0;
    }

    to {
        opacity: .8;
    }
}

.loader-corner {

    position: absolute;

    width: 80px;
    height: 80px;

    border-color:
        rgba(212,175,55,.3);

    border-style: solid;

    z-index: 1;
}

.loader-corner.top-left {
    top: 35px;
    left: 35px;
    border-width: 1px 0 0 1px;
}

.loader-corner.top-right {
    top: 35px;
    right: 35px;
    border-width: 1px 1px 0 0;
}

.loader-corner.bottom-left {
    bottom: 35px;
    left: 35px;
    border-width: 0 0 1px 1px;
}

.loader-corner.bottom-right {
    bottom: 35px;
    right: 35px;
    border-width: 0 1px 1px 0;
}


/* =========================================================
   NAVBAR
========================================================= */

.navbar {

    position: sticky;
    top: 0;

    z-index: 1000;

    background:
        rgba(24,18,12,.97);

    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);

    border-bottom:
        1px solid
        rgba(212,175,55,.35);
}

.nav-container {

    width: 92%;
    max-width: 1250px;

    margin: auto;

    min-height: 76px;

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 20px;
}

.logo {

    color: #d4af37;

    text-decoration: none;

    display: flex;

    align-items: center;

    gap: 10px;

    flex-shrink: 0;
}

.logo-icon {

    width: 45px;
    height: 45px;

    border:
        2px solid #d4af37;

    border-radius: 50%;

    display: flex;

    justify-content: center;
    align-items: center;

    font-size: 22px;
}

.logo-text {

    font-size: 23px;

    font-weight: bold;

    letter-spacing: 1px;
}

.logo-text span {
    color: #fff4d6;
}

.nav-links {

    display: flex;

    align-items: center;

    justify-content: flex-end;

    gap: 5px;

    flex-wrap: wrap;
}

.nav-links a {

    color: #eee3cc;

    text-decoration: none;

    padding: 10px 13px;

    border-radius: 8px;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

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

.user-greeting {

    display: inline-block;

    margin-bottom: 22px;

    color: #f7e5ad;

    background: rgba(36, 25, 20, 0.94);

    border: 1px solid rgba(212, 175, 55, 0.55);

    border-radius: 10px;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    font-size: 15px;

    font-weight: 700;

    padding: 10px 15px;

    white-space: nowrap;

    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.18);
}

.cart-btn {

    border:
        1px solid #d4af37;

    color:
        #f7e5ad !important;
}

.cart-count {

    display: inline-flex;

    min-width: 20px;
    height: 20px;

    align-items: center;
    justify-content: center;

    background: #d4af37;

    color: #17120d;

    border-radius: 50%;

    font-family:
        Arial,
        sans-serif;

    font-size: 11px;

    font-weight: bold;

    margin-left: 4px;
}


/* =========================================================
   HERO
========================================================= */

.hero {

    min-height: 680px;

    position: relative;

    overflow: hidden;

    background-image:

        linear-gradient(
            90deg,
            rgba(9,6,4,.96) 0%,
            rgba(20,12,7,.82) 43%,
            rgba(20,12,7,.48) 100%
        ),

        url("https://images.unsplash.com/photo-1450778869180-41d0601e046e?auto=format&fit=crop&w=2200&q=90");

    background-size: cover;

    background-position: center;

    background-repeat: no-repeat;

    background-attachment: fixed;

    display: flex;

    align-items: center;
}

.hero::before {

    content: "";

    position: absolute;

    inset: 0;

    background:
        radial-gradient(
            circle at 75% 50%,
            rgba(212,175,55,.12),
            transparent 35%
        );
}

.hero-container {

    width: 92%;
    max-width: 1250px;

    margin: auto;

    display: grid;

    grid-template-columns:
        1.15fr .85fr;

    gap: 50px;

    align-items: center;

    position: relative;

    z-index: 2;
}

.hero-content {

    animation:
        heroIn 1s ease forwards;
}

@keyframes heroIn {

    from {
        opacity: 0;
        transform: translateY(35px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.badge {

    display: inline-block;

    padding: 8px 15px;

    border:
        1px solid #d4af37;

    color: #e6c75c;

    border-radius: 30px;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    font-size: 12px;

    letter-spacing: 2px;

    margin-bottom: 20px;
}

.hero h1 {

    font-size:
        clamp(45px,6vw,78px);

    line-height: 1;

    color: #fff4d6;

    margin-bottom: 22px;
}

.hero h1 span {
    color: #d4af37;
}

.hero p {

    color: #cfc4b0;

    max-width: 600px;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    line-height: 1.8;

    font-size: 16px;
}

.hero-buttons {

    display: flex;

    gap: 15px;

    margin-top: 32px;

    flex-wrap: wrap;
}

.btn {

    text-decoration: none;

    display: inline-block;

    padding: 14px 25px;

    border-radius: 8px;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    font-weight: bold;

    transition: .3s;

    cursor: pointer;
}

.btn-gold {

    background: #d4af37;

    color: #17120d;
}

.btn-gold:hover {

    transform: translateY(-4px);

    box-shadow:
        0 12px 25px
        rgba(212,175,55,.25);
}

.btn-outline {

    border:
        1px solid #d4af37;

    color: #f2dfaa;
}

.btn-outline:hover {

    background:
        rgba(212,175,55,.1);

    transform:
        translateY(-4px);
}


/* =========================================================
   HERO IMAGE
========================================================= */

.hero-art {

    min-height: 470px;

    display: flex;

    justify-content: center;

    align-items: center;

    position: relative;
}

.pet-circle {

    width: 390px;
    height: 390px;

    border-radius: 32px;

    border:
        2px solid
        rgba(212,175,55,.65);

    overflow: hidden;

    position: relative;

    background: #24170f;

    box-shadow:
        0 25px 70px rgba(0,0,0,.45),
        0 0 60px rgba(212,175,55,.14);

    animation:
        floating 5s ease-in-out infinite;
}

.pet-circle img {

    width: 100%;
    height: 100%;

    object-fit: cover;

    display: block;

    filter:
        saturate(1.05)
        contrast(1.03);
}

.hero-image-shine {

    position: absolute;

    inset: 0;

    z-index: 2;

    pointer-events: none;

    background:
        linear-gradient(
            125deg,
            rgba(255,255,255,.20),
            transparent 28%,
            transparent 70%,
            rgba(212,175,55,.16)
        );
}

.float-photo {

    position: absolute;

    width: 105px;
    height: 105px;

    border-radius: 50%;

    overflow: hidden;

    border:
        3px solid #d4af37;

    background: #24170f;

    box-shadow:
        0 15px 35px rgba(0,0,0,.35);

    z-index: 4;

    animation:
        smallFloat 3.5s ease-in-out infinite;
}

.float-photo img {

    width: 100%;
    height: 100%;

    object-fit: cover;
}

.float-photo-one {
    top: 5px;
    right: 5px;
}

.float-photo-two {

    bottom: 15px;
    left: 0;

    animation-delay: .8s;
}

.hero-paw-glow {

    position: absolute;

    right: 18px;
    bottom: -5px;

    z-index: 3;

    color:
        rgba(255,244,214,.78);

    font-family:
        Arial,
        sans-serif;

    font-size: 10px;

    letter-spacing: 4px;

    padding: 10px 14px;

    border:
        1px solid
        rgba(212,175,55,.35);

    border-radius: 20px;

    background:
        rgba(20,14,9,.55);

    backdrop-filter: blur(8px);
}

@keyframes floating {

    0%,100% {
        transform:
            translateY(0)
            rotate(-2deg);
    }

    50% {
        transform:
            translateY(-18px)
            rotate(2deg);
    }
}

@keyframes smallFloat {

    0%,100% {
        transform: translateY(0);
    }

    50% {
        transform: translateY(-15px);
    }
}


/* =========================================================
   ALL NORMAL SECTIONS
========================================================= */

.section {

    width: 92%;
    max-width: 1250px;

    margin: auto;

    padding: 90px 0;
}

.section-heading {

    text-align: center;

    margin-bottom: 45px;

    position: relative;

    z-index: 3;
}

.section-heading small {

    color: #a07c16;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    text-transform: uppercase;

    letter-spacing: 3px;
}

.section-heading h2 {

    font-size: 42px;

    margin-top: 10px;

    color: #302315;
}

.section-heading p {

    margin-top: 10px;

    color: #766d60;

    font-family:
        Arial,
        Helvetica,
        sans-serif;
}


/* =========================================================
   CATEGORY SECTION BACKGROUND
========================================================= */

.category-section {

    width: 100%;

    position: relative;

    overflow: hidden;

    background:

        linear-gradient(
            rgba(248,244,236,.91),
            rgba(248,244,236,.94)
        ),

        url("https://images.unsplash.com/photo-1601758123927-1966a2c7d0b0?auto=format&fit=crop&w=2200&q=90");

    background-size: cover;

    background-position: center;

    background-attachment: fixed;
}

.category-section::before {

    content: "";

    position: absolute;

    inset: 0;

    background:
        radial-gradient(
            circle at 20% 30%,
            rgba(212,175,55,.10),
            transparent 30%
        ),
        radial-gradient(
            circle at 80% 70%,
            rgba(110,75,30,.08),
            transparent 35%
        );

    pointer-events: none;
}

.category-section .section {
    position: relative;
    z-index: 2;
}


/* =========================================================
   CATEGORY GRID
========================================================= */

.category-grid {

    display: grid;

    grid-template-columns:
        repeat(4,1fr);

    gap: 22px;
}

.category-card {

    background:
        rgba(255,253,248,.91);

    backdrop-filter: blur(7px);

    border:
        1px solid #e5d8bd;

    border-radius: 18px;

    padding: 18px;

    text-align: center;

    text-decoration: none;

    color: #302315;

    transition: .35s;

    position: relative;

    overflow: hidden;

    opacity: 0;

    transform:
        translateY(25px);

    box-shadow:
        0 10px 35px
        rgba(60,40,15,.08);
}

.category-card:hover {

    transform:
        translateY(-10px);

    border-color:
        #d4af37;

    box-shadow:
        0 22px 45px
        rgba(74,55,22,.17);
}

.category-image {

    width: 100%;

    height: 220px;

    border-radius: 15px;

    overflow: hidden;

    position: relative;

    margin-bottom: 18px;

    background: #eee7d8;

    border:
        1px solid
        rgba(212,175,55,.22);
}

.category-image img {

    width: 100%;
    height: 100%;

    display: block;

    object-fit: cover;

    opacity: 0;

    transform: scale(1.03);

    transition:
        opacity .55s ease,
        transform .65s cubic-bezier(.16,1,.3,1);
}

.category-image img.loaded {
    opacity: 1;
}

.category-card:hover
.category-image img {
    transform: scale(1.10);
}

.category-image::after {

    content: "";

    position: absolute;

    inset: 0;

    background:
        linear-gradient(
            180deg,
            transparent 52%,
            rgba(20,13,8,.25)
        );

    pointer-events: none;
}

.category-image-loading {

    position: absolute;

    inset: 0;

    background:
        linear-gradient(
            90deg,
            #eee7d8 0%,
            #faf4e8 50%,
            #eee7d8 100%
        );

    background-size: 200% 100%;

    animation:
        imageShimmer 1.5s linear infinite;

    z-index: 1;
}

.category-image-loading.hide {
    display: none;
}

.category-card h3 {
    font-size: 23px;
}

.category-card p {

    margin-top: 8px;

    color: #766d60;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    font-size: 13px;

    line-height: 1.5;
}


/* =========================================================
   OFFER
========================================================= */

.offer-section {

    width: 92%;

    max-width: 1250px;

    margin: auto;
}

.offer {

    background:

        linear-gradient(
            100deg,
            rgba(28,18,10,.94),
            rgba(59,39,18,.90)
        ),

        url("https://images.unsplash.com/photo-1601758228041-f3b2795255f1?auto=format&fit=crop&w=1800&q=90");

    background-size: cover;

    background-position: center;

    border-radius: 25px;

    padding: 55px;

    color: white;

    position: relative;

    overflow: hidden;

    box-shadow:
        0 20px 60px
        rgba(35,23,10,.22);
}

.offer::after {

    content: "50%";

    position: absolute;

    right: 30px;
    top: 15px;

    font-size: 150px;

    font-weight: bold;

    color:
        rgba(212,175,55,.07);

    pointer-events: none;
}

.offer h2 {

    color: #f5df9b;

    font-size: 40px;

    position: relative;

    z-index: 2;
}

.offer p {

    margin-top: 12px;

    color: #d2c7b3;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    position: relative;

    z-index: 2;
}

.offer .btn {

    margin-top: 22px;

    position: relative;

    z-index: 2;
}


/* =========================================================
   FEATURED PRODUCT SECTION BACKGROUND
========================================================= */

.products-section {

    width: 100%;

    position: relative;

    overflow: hidden;

    background:

        linear-gradient(
            rgba(245,239,228,.91),
            rgba(248,244,236,.96)
        ),

        url("https://images.unsplash.com/photo-1587300003388-59208cc962cb?auto=format&fit=crop&w=2200&q=90");

    background-size: cover;

    background-position: center;

    background-attachment: fixed;
}

.products-section::before {

    content: "";

    position: absolute;

    inset: 0;

    background:
        radial-gradient(
            circle at 75% 20%,
            rgba(212,175,55,.10),
            transparent 28%
        ),
        radial-gradient(
            circle at 15% 80%,
            rgba(85,55,25,.08),
            transparent 30%
        );

    pointer-events: none;
}

.products-section .section {

    position: relative;

    z-index: 2;
}


/* =========================================================
   PRODUCT GRID
========================================================= */

.product-grid {

    display: grid;

    grid-template-columns:
        repeat(4,1fr);

    gap: 22px;
}

.product-card {

    background:
        rgba(255,253,248,.94);

    backdrop-filter:
        blur(8px);

    border:
        1px solid #e4d8c0;

    border-radius: 18px;

    overflow: hidden;

    transition: .35s;

    opacity: 0;

    transform:
        translateY(25px);

    box-shadow:
        0 10px 35px
        rgba(63,44,16,.08);
}

.product-card.visible,
.category-card.visible {

    opacity: 1;

    transform:
        translateY(0);
}

.product-card:hover {

    transform:
        translateY(-8px);

    box-shadow:
        0 22px 42px
        rgba(63,44,16,.15);

    border-color:
        #d4af37;
}


/* =========================================================
   PRODUCT IMAGE
========================================================= */

.product-image {

    height: 230px;

    background:
        #eee7d8;

    overflow: hidden;

    position: relative;
}

.product-image img {

    width: 100%;
    height: 100%;

    display: block;

    object-fit: cover;

    opacity: 0;

    transition:
        opacity .5s ease,
        transform .5s ease;
}

.product-image img.loaded {
    opacity: 1;
}

.product-card:hover
.product-image img {
    transform: scale(1.08);
}


/* =========================================================
   SHIMMER
========================================================= */

.image-loading {

    position: absolute;

    inset: 0;

    background:
        linear-gradient(
            90deg,
            #eee7d8 0%,
            #f8f1e2 50%,
            #eee7d8 100%
        );

    background-size: 200% 100%;

    animation:
        imageShimmer 1.5s linear infinite;

    z-index: 1;
}

.image-loading.hide {
    display: none;
}

@keyframes imageShimmer {

    from {
        background-position: 200% 0;
    }

    to {
        background-position: -200% 0;
    }
}


/* =========================================================
   PRODUCT INFO
========================================================= */

.product-info {
    padding: 20px;
}

.product-category {

    color: #a07c16;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    font-size: 11px;

    text-transform: uppercase;

    letter-spacing: 1px;
}

.product-info h3 {

    margin: 7px 0;

    font-size: 20px;

    color: #302315;
}

.product-info p {

    color: #777064;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    font-size: 13px;

    line-height: 1.5;

    min-height: 40px;
}

.price {

    color: #8a6811;

    font-size: 21px;

    font-weight: bold;

    margin-top: 13px;
}

.stock {

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    font-size: 12px;

    margin-top: 5px;
}

.stock.available {
    color: #5e7352;
}

.stock.out {

    color: #b83232;

    font-weight: bold;
}

.product-actions {
    margin-top: 15px;
}

.view-btn {

    width: 100%;

    text-align: center;

    background: #24190e;

    color: #f4dc94;
}

.view-btn:hover {

    background: #d4af37;

    color: #24190e;
}


/* =========================================================
   EMPTY
========================================================= */

.empty-state {

    grid-column: 1 / -1;

    background: #fffdf8;

    border:
        1px solid #e4d8c0;

    border-radius: 18px;

    text-align: center;

    padding: 50px 20px;

    color: #766d60;

    font-family:
        Arial,
        Helvetica,
        sans-serif;
}

.empty-state .empty-icon {

    font-size: 50px;

    margin-bottom: 15px;
}

.empty-state h3 {

    color: #302315;

    font-family:
        Georgia,
        serif;

    margin-bottom: 8px;
}


/* =========================================================
   FOOTER
========================================================= */

footer {

    background:
        linear-gradient(
            135deg,
            #17120d,
            #24170e
        );

    color: #cfc3ad;

    padding:
        50px 0 25px;

    margin-top: 0;
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

footer p {

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    line-height: 1.7;

    font-size: 14px;
}

footer h3 {

    color: #f3dda0;

    margin-bottom: 15px;
}

footer a {

    display: block;

    color: #cfc3ad;

    text-decoration: none;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    margin: 9px 0;

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

    text-align: center;

    margin-top: 40px;

    padding-top: 20px;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    font-size: 13px;
}


/* =========================================================
   TABLET
========================================================= */

@media (max-width:1100px) {

    .category-grid,
    .product-grid {

        grid-template-columns:
            repeat(2,1fr);
    }

}


/* =========================================================
   MOBILE
========================================================= */

@media (max-width:800px) {

    .navbar {
        position: relative;
    }

    .nav-container {

        flex-direction: column;

        padding: 15px 0;

        min-height: auto;
    }

    .nav-links {

        width: 100%;

        justify-content: center;
    }

    .nav-links a {

        font-size: 13px;

        padding: 8px 9px;
    }

    .user-greeting {
        font-size: 12px;
        padding: 8px 10px;
        margin-bottom: 18px;
    }

    .hero {

        padding: 70px 0;

        background-attachment: scroll;
    }

    .hero-container {

        grid-template-columns: 1fr;

        text-align: center;
    }

    .hero p {
        margin: auto;
    }

    .hero-buttons {
        justify-content: center;
    }

    .hero-art {
        min-height: 300px;
    }

    .pet-circle {

        width: min(330px,82vw);

        height: min(330px,82vw);
    }

    .category-section,
    .products-section {
        background-attachment: scroll;
    }

    .section {
        padding: 60px 0;
    }

    .section-heading h2 {
        font-size: 34px;
    }

    .offer {
        padding: 30px;
    }

    .offer h2 {
        font-size: 30px;
    }

    .offer::after {
        font-size: 90px;
        right: -10px;
    }

    .footer-container {
        grid-template-columns: 1fr;
    }
}


/* =========================================================
   SMALL MOBILE
========================================================= */

@media (max-width:520px) {

    .loader-corner {

        width: 45px;
        height: 45px;
    }

    .loader-corner.top-left,
    .loader-corner.top-right {
        top: 20px;
    }

    .loader-corner.bottom-left,
    .loader-corner.bottom-right {
        bottom: 20px;
    }

    .loader-corner.top-left,
    .loader-corner.bottom-left {
        left: 20px;
    }

    .loader-corner.top-right,
    .loader-corner.bottom-right {
        right: 20px;
    }

    .loader-subtitle {

        font-size: 8px;

        letter-spacing: 2px;
    }

    .loader-line {
        width: 75vw;
    }

    .nav-links {
        gap: 3px;
    }

    .nav-links a {

        padding: 7px 6px;

        font-size: 12px;
    }

    .logo-text {
        font-size: 21px;
    }

    .hero h1 {
        font-size: 43px;
    }

    .hero p {
        font-size: 14px;
    }

    .hero-buttons {

        flex-direction: column;

        width: 100%;
    }

    .hero-buttons .btn {

        width: 100%;

        text-align: center;
    }

    .category-grid,
    .product-grid {
        grid-template-columns: 1fr;
    }

    .product-image {
        height: 250px;
    }

    .section-heading h2 {
        font-size: 30px;
    }

}

</style>

</head>


<body>

<!-- =========================================================
     LOADER
========================================================= -->

<div class="loader" id="loader">

    <div class="loader-corner top-left"></div>
    <div class="loader-corner top-right"></div>
    <div class="loader-corner bottom-left"></div>
    <div class="loader-corner bottom-right"></div>

    <div class="loader-brand">
        ROYAL PAWS
    </div>

    <div class="loader-paws">
        🐾 🐾 🐾
    </div>

    <div class="loader-subtitle">
        PREMIUM PET STORE
    </div>

    <div class="loader-line">
        <div class="loader-progress"></div>
    </div>

    <div class="loader-status">
        PREPARING YOUR PET WORLD...
    </div>

</div>


<!-- =========================================================
     NAVBAR
========================================================= -->

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


            <?php if ($isLoggedIn): ?>

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

<!-- =========================================================
     HERO
========================================================= -->

<section class="hero">

    <div class="hero-container">

        <div class="hero-content">

            <?php if ($isLoggedIn): ?>
                <div class="user-greeting">
                    👋 Hello, <?php echo e($userName); ?>
                </div>
            <?php endif; ?>

            <span class="badge">
                PREMIUM PET STORE
            </span>

            <h1>
                Everything Your
                <span>Pet</span>
                Deserves
            </h1>

            <p>
                Discover premium food, toys and accessories
                for your beloved dogs, cats, birds and fish.
                Give your pets the royal treatment they deserve.
            </p>

            <div class="hero-buttons">

                <a
                    href="products.php"
                    class="btn btn-gold"
                >
                    🛍️ Shop Now
                </a>

                <a
                    href="categories.php"
                    class="btn btn-outline"
                >
                    Explore Categories
                </a>

            </div>

        </div>


        <div class="hero-art">

            <!-- MAIN HERO: LABRADOR -->

            <div class="pet-circle">

                <div class="hero-image-shine"></div>

                <img
                    src="https://images.unsplash.com/photo-1558788353-f76d92427f16?auto=format&fit=crop&w=1200&q=90"
                    alt="Labrador Dog"
                >

            </div>


            <!-- POMERANIAN -->

            <div class="float-photo float-photo-one">

                <img
                    src="https://images.unsplash.com/photo-1558929996-da64ba858215?auto=format&fit=crop&w=600&q=90"
                    alt="Pomeranian Dog"
                >

            </div>


            <!-- CAT -->

            <div class="float-photo float-photo-two">

                <img
                    src="https://images.unsplash.com/photo-1514888286974-6c03e2ca1dba?auto=format&fit=crop&w=600&q=90"
                    alt="Cat"
                >

            </div>


            <div class="hero-paw-glow">
                ROYAL PAWS
            </div>

        </div>

    </div>

</section>


<!-- =========================================================
     SHOP BY CATEGORY
========================================================= -->

<section class="category-section">

    <div class="section">

        <div class="section-heading">

            <small>
                Explore
            </small>

            <h2>
                Shop By Category
            </h2>

            <p>
                Everything your pet needs in one royal place.
            </p>

        </div>


        <div class="category-grid">


            <?php if (
                $categoryQuery &&
                $categoryQuery->num_rows > 0
            ): ?>


                <?php while (
                    $category =
                    $categoryQuery->fetch_assoc()
                ): ?>


                    <a
                        href="products.php?category=<?php echo (int)$category["id"]; ?>"
                        class="category-card"
                        data-category-name="<?php echo e($category["name"]); ?>"
                    >

                        <div class="category-image">

                            <div class="category-image-loading"></div>

                            <img
                                src=""
                                alt="<?php echo e($category["name"]); ?>"
                                loading="lazy"
                                decoding="async"
                            >

                        </div>


                        <h3>
                            <?php
                            echo e($category["name"]);
                            ?>
                        </h3>


                        <p>

                            <?php

                            $categoryDescription =
                                trim(
                                    (string)
                                    $category["description"]
                                );

                            if (
                                $categoryDescription !== ""
                            ) {

                                echo e(
                                    $categoryDescription
                                );

                            } else {

                                echo
                                    "Explore our "
                                    .
                                    e(
                                        $category["name"]
                                    )
                                    .
                                    " products.";
                            }

                            ?>

                        </p>

                    </a>


                <?php endwhile; ?>


            <?php else: ?>


                <div class="empty-state">

                    <div class="empty-icon">
                        🐾
                    </div>

                    <h3>
                        No Categories Available
                    </h3>

                    <p>
                        Categories will appear here once they are added.
                    </p>

                </div>


            <?php endif; ?>


        </div>

    </div>

</section>


<!-- =========================================================
     OFFER
========================================================= -->

<div class="offer-section">

    <div class="section">

        <div class="offer">

            <h2>
                Royal Mega Sale 👑
            </h2>

            <p>
                Special offers on selected pet products.
                Shop more and make your furry friends happy!
            </p>

            <a
                href="products.php"
                class="btn btn-gold"
            >
                View Offers
            </a>

        </div>

    </div>

</div>


<!-- =========================================================
     FEATURED PRODUCTS
========================================================= -->

<section
    class="products-section"
    id="products"
>

    <div class="section">

        <div class="section-heading">

            <small>
                Our Collection
            </small>

            <h2>
                Featured Products
            </h2>

            <p>
                Carefully selected products for your beloved pets.
            </p>

        </div>


        <div class="product-grid">


            <?php if (
                $productQuery &&
                $productQuery->num_rows > 0
            ): ?>


                <?php while (
                    $product =
                    $productQuery->fetch_assoc()
                ): ?>


                    <?php

                    $productId =
                        (int)$product["id"];

                    $productName =
                        (string)$product["name"];

                    $productDescription =
                        trim(
                            (string)
                            $product["description"]
                        );

                    $productStock =
                        (int)$product["stock"];

                    $productPrice =
                        (float)
                        $product["selling_price"];

                    $databaseImage =
                        safeProductImage(
                            $product["image"]
                        );

                    ?>


                    <div
                        class="product-card"

                        data-product-name="<?php
                            echo e($productName);
                        ?>"

                        data-product-category="<?php
                            echo e($product["category_name"]);
                        ?>"

                        data-database-image="<?php
                            echo e($databaseImage);
                        ?>"
                    >


                        <div class="product-image">

                            <div
                                class="image-loading"
                            ></div>

                            <img
                                src=""
                                alt="<?php
                                    echo e($productName);
                                ?>"
                                loading="lazy"
                                decoding="async"
                            >

                        </div>


                        <div class="product-info">


                            <div class="product-category">

                                <?php
                                echo e(
                                    $product["category_name"]
                                );
                                ?>

                            </div>


                            <h3>

                                <?php
                                echo e(
                                    $productName
                                );
                                ?>

                            </h3>


                            <p>

                                <?php

                                if (
                                    $productDescription === ""
                                ) {

                                    echo
                                        "Premium quality product for your beloved pet.";

                                } elseif (
                                    strlen(
                                        $productDescription
                                    ) > 70
                                ) {

                                    echo e(
                                        substr(
                                            $productDescription,
                                            0,
                                            70
                                        )
                                    );

                                    echo "...";

                                } else {

                                    echo e(
                                        $productDescription
                                    );
                                }

                                ?>

                            </p>


                            <div class="price">

                                ₹<?php
                                echo number_format(
                                    $productPrice,
                                    2
                                );
                                ?>

                            </div>


                            <?php if ($productStock > 0): ?>

                                <div class="stock available">

                                    ✓
                                    <?php
                                    echo $productStock;
                                    ?>
                                    items available

                                </div>

                            <?php else: ?>

                                <div class="stock out">
                                    ✕ Out of stock
                                </div>

                            <?php endif; ?>


                            <div class="product-actions">

                                <a
                                    href="product_details.php?id=<?php echo $productId; ?>"
                                    class="btn view-btn"
                                >
                                    View Product
                                </a>

                            </div>


                        </div>

                    </div>


                <?php endwhile; ?>


            <?php else: ?>


                <div class="empty-state">

                    <div class="empty-icon">
                        📦
                    </div>

                    <h3>
                        No Products Available
                    </h3>

                    <p>
                        No products are available right now.
                        Please check again later.
                    </p>

                    <a
                        href="products.php"
                        class="btn btn-gold"
                        style="margin-top:20px;"
                    >
                        Browse Products
                    </a>

                </div>


            <?php endif; ?>


        </div>

    </div>

</section>


<!-- =========================================================
     FOOTER
========================================================= -->

<footer>

    <div class="footer-container">

        <div>

            <div class="footer-logo">
                🐾 Royal Paws
            </div>

            <p>
                Your trusted online destination for premium
                pet food, toys and accessories.
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


            <?php if ($isLoggedIn): ?>

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

/* =========================================================
   LOADER
========================================================= */

(function () {

    const loader =
        document.getElementById("loader");

    let loaderHidden = false;

    const minimumLoaderTime = 2700;

    const loaderStarted = Date.now();


    function hideLoader() {

        if (
            loaderHidden ||
            !loader
        ) {
            return;
        }

        loaderHidden = true;

        loader.classList.add("hide");

        setTimeout(
            function () {

                if (loader) {
                    loader.style.display = "none";
                }

            },
            950
        );
    }


    function finishLoader() {

        const elapsed =
            Date.now() -
            loaderStarted;

        const remaining =
            Math.max(
                0,
                minimumLoaderTime -
                elapsed
            );

        setTimeout(
            hideLoader,
            remaining
        );
    }


    window.addEventListener(
        "load",
        finishLoader
    );


    setTimeout(
        hideLoader,
        6500
    );

})();


/* =========================================================
   TEXT NORMALIZER
========================================================= */

function normalizeProductText(text) {

    return String(text || "")
        .toLowerCase()
        .replace(
            /[^a-z0-9\s]/g,
            " "
        )
        .replace(
            /\s+/g,
            " "
        )
        .trim();
}


/* =========================================================
   USED IMAGE TRACKER
========================================================= */

const usedVisualImages = new Set();


/* =========================================================
   EXACT PRODUCT IMAGE COLLECTION
========================================================= */

const royalPawsImages = [

    /* =====================================================
       AQUARIUM FILTER
    ===================================================== */

    {
        keywords: [
            "aquarium filter",
            "fish filter",
            "water filter",
            "tank filter",
            "internal filter",
            "external filter",
            "sponge filter",
            "hang on filter"
        ],

        urls: [
            "aqua.jpg",
            "https://images.unsplash.com/photo-1524704654690-b56c05c78a00?auto=format&fit=crop&w=1000&q=90"
        ]
    },


    /* =====================================================
       FISH FOOD
    ===================================================== */

    {
        keywords: [
            "fish food",
            "fish feed",
            "aquarium food",
            "fish flakes",
            "fish pellets",
            "tropical fish food"
        ],

        urls: [
            "https://images.unsplash.com/photo-1544551763-46a013bb70d5?auto=format&fit=crop&w=1000&q=90",
            "https://images.unsplash.com/photo-1535591273668-578e31182c4f?auto=format&fit=crop&w=1000&q=90"
        ]
    },


    /* =====================================================
       AQUARIUM / FISH
    ===================================================== */

    {
        keywords: [
            "aquarium",
            "fish",
            "fishes",
            "goldfish",
            "gold fish",
            "betta",
            "betta fish",
            "guppy",
            "guppy fish",
            "tropical fish",
            "fish tank",
            "fish bowl",
            "aquatic"
        ],

        urls: [
            "https://images.unsplash.com/photo-1520990261762-8b0b2f1a5f5d?auto=format&fit=crop&w=1000&q=90",
            "https://images.unsplash.com/photo-1544550285-f813152fb2fd?auto=format&fit=crop&w=1000&q=90",
            "https://images.unsplash.com/photo-1535591273668-578e31182c4f?auto=format&fit=crop&w=1000&q=90",
            "https://images.unsplash.com/photo-1524704654690-b56c05c78a00?auto=format&fit=crop&w=1000&q=90"
        ]
    },


    /* =====================================================
       BIRD SEED MIX
    ===================================================== */

    {
        keywords: [
            "bird seed mix",
            "bird seeds",
            "bird seed",
            "seed mix",
            "seeds",
            "parrot seed",
            "bird feed",
            "bird food mix"
        ],

        urls: [
            "https://images.unsplash.com/photo-1497250681960-ef046c08a56e?auto=format&fit=crop&w=1000&q=90",
            "https://images.unsplash.com/photo-1518717758536-85ae29035b6d?auto=format&fit=crop&w=1000&q=90"
        ]
    },


    /* =====================================================
       BIRDS
    ===================================================== */

    {
        keywords: [
            "bird",
            "birds",
            "parrot",
            "cockatiel",
            "budgie",
            "lovebird",
            "bird cage"
        ],

        urls: [
            "https://images.unsplash.com/photo-1444464666168-49d633b86797?auto=format&fit=crop&w=1000&q=90",
            "https://images.unsplash.com/photo-1452570053594-1b985d6ea890?auto=format&fit=crop&w=1000&q=90",
            "https://images.unsplash.com/photo-1552728089-57bdde30beb3?auto=format&fit=crop&w=1000&q=90"
        ]
    },


    /* =====================================================
       POMERANIAN
    ===================================================== */

    {
        keywords: [
            "pomeranian",
            "pomeranian dog",
            "pom dog",
            "pom puppy"
        ],

        urls: [
            "https://images.unsplash.com/photo-1558929996-da64ba858215?auto=format&fit=crop&w=1000&q=90",
            "https://images.unsplash.com/photo-1583512603805-3cc6b41f3edb?auto=format&fit=crop&w=1000&q=90"
        ]
    },


    /* =====================================================
       LABRADOR
    ===================================================== */

    {
        keywords: [
            "labrador",
            "lab",
            "labrador dog",
            "lab dog",
            "labrador puppy"
        ],

        urls: [
            "https://images.unsplash.com/photo-1558788353-f76d92427f16?auto=format&fit=crop&w=1000&q=90",
            "https://images.unsplash.com/photo-1517849845537-4d257902454a?auto=format&fit=crop&w=1000&q=90"
        ]
    },


    /* =====================================================
       DOG FOOD
    ===================================================== */

    {
        keywords: [
            "dog food",
            "puppy food",
            "dry dog food",
            "dog kibble",
            "puppy",
            "dog"
        ],

        urls: [
            "https://images.unsplash.com/photo-1589924691995-400dc9ecc119?auto=format&fit=crop&w=1000&q=90",
            "https://images.unsplash.com/photo-1558788353-f76d92427f16?auto=format&fit=crop&w=1000&q=90"
        ]
    },


    /* =====================================================
       CAT FOOD
    ===================================================== */

    {
        keywords: [
            "cat food",
            "kitten food",
            "cat"
        ],

        urls: [
            "https://images.unsplash.com/photo-1574158622682-e40e69881006?auto=format&fit=crop&w=1000&q=90",
            "https://images.unsplash.com/photo-1514888286974-6c03e2ca1dba?auto=format&fit=crop&w=1000&q=90"
        ]
    },


    /* =====================================================
       DOG TOYS
    ===================================================== */

    {
        keywords: [
            "dog toy",
            "pet toy",
            "chew toy",
            "rope toy",
            "dog ball",
            "toy"
        ],

        urls: [
            "https://images.unsplash.com/photo-1601758064138-732e0e6d8c0c?auto=format&fit=crop&w=1000&q=90",
            "https://images.unsplash.com/photo-1535930749574-1399327ce78f?auto=format&fit=crop&w=1000&q=90"
        ]
    },


    /* =====================================================
       CAT TOYS
    ===================================================== */

    {
        keywords: [
            "cat toy",
            "kitten toy"
        ],

        urls: [
            "https://images.unsplash.com/photo-1592194996308-7b43878e84a6?auto=format&fit=crop&w=1000&q=90"
        ]
    },


    /* =====================================================
       PET BED
    ===================================================== */

    {
        keywords: [
            "dog bed",
            "pet bed",
            "cat bed",
            "puppy bed",
            "pet mattress",
            "bed"
        ],

        urls: [
            "https://images.unsplash.com/photo-1601758174114-e711c0cbaa69?auto=format&fit=crop&w=1000&q=90",
            "https://images.unsplash.com/photo-1583511655857-d19b40a7a54e?auto=format&fit=crop&w=1000&q=90"
        ]
    },


    /* =====================================================
       COLLAR
    ===================================================== */

    {
        keywords: [
            "collar",
            "dog collar",
            "pet collar",
            "cat collar"
        ],

        urls: [
            "https://images.unsplash.com/photo-1558788353-f76d92427f16?auto=format&fit=crop&w=1000&q=90"
        ]
    },


    /* =====================================================
       LEASH
    ===================================================== */

    {
        keywords: [
            "leash",
            "dog leash",
            "pet leash"
        ],

        urls: [
            "https://images.unsplash.com/photo-1583337130417-3346a1be7dee?auto=format&fit=crop&w=1000&q=90"
        ]
    },


    /* =====================================================
       TREATS
    ===================================================== */

    {
        keywords: [
            "treat",
            "snack",
            "biscuit",
            "dog biscuit",
            "pet treat"
        ],

        urls: [
            "https://images.unsplash.com/photo-1552053831-71594a27632d?auto=format&fit=crop&w=1000&q=90"
        ]
    },


    /* =====================================================
       GROOMING
    ===================================================== */

    {
        keywords: [
            "shampoo",
            "grooming",
            "brush",
            "comb",
            "nail cutter",
            "pet grooming"
        ],

        urls: [
            "https://images.unsplash.com/photo-1548199973-03cce0bbc87b?auto=format&fit=crop&w=1000&q=90",
            "https://images.unsplash.com/photo-1583511655857-d19b40a7a54e?auto=format&fit=crop&w=1000&q=90"
        ]
    },


    /* =====================================================
       RABBIT
    ===================================================== */

    {
        keywords: [
            "rabbit",
            "bunny"
        ],

        urls: [
            "https://images.unsplash.com/photo-1585110396000-c9ffd4e4b308?auto=format&fit=crop&w=1000&q=90"
        ]
    },


    /* =====================================================
       HAMSTER
    ===================================================== */

    {
        keywords: [
            "hamster"
        ],

        urls: [
            "https://images.unsplash.com/photo-1425082661705-1834bfd09dca?auto=format&fit=crop&w=1000&q=90"
        ]
    }

];


/* =========================================================
   FALLBACK IMAGES
========================================================= */

const royalPawsFallbackImages = [

    "https://images.unsplash.com/photo-1537151608828-ea2b11777ee8?auto=format&fit=crop&w=1000&q=90",

    "https://images.unsplash.com/photo-1561037404-61cd46aa615b?auto=format&fit=crop&w=1000&q=90",

    "https://images.unsplash.com/photo-1517849845537-4d257902454a?auto=format&fit=crop&w=1000&q=90",

    "https://images.unsplash.com/photo-1583512603805-3cc6b41f3edb?auto=format&fit=crop&w=1000&q=90",

    "https://images.unsplash.com/photo-1596854407944-bf87f6fdd49e?auto=format&fit=crop&w=1000&q=90"

];


/* =========================================================
   UNIQUE IMAGE
========================================================= */

function getUniqueImage(urls) {

    if (!Array.isArray(urls)) {
        return "";
    }

    for (
        let i = 0;
        i < urls.length;
        i++
    ) {

        const url = urls[i];

        if (
            !usedVisualImages.has(url)
        ) {

            usedVisualImages.add(url);

            return url;
        }
    }

    return "";
}


/* =========================================================
   FIND EXACT PRODUCT IMAGE
========================================================= */

function findUniqueProductImage(
    productName,
    categoryName
) {

    const productText =
        normalizeProductText(productName);

    const categoryText =
        normalizeProductText(categoryName);

    const combinedText =
        productText +
        " " +
        categoryText;


    /* =====================================================
       VERY IMPORTANT:
       SPECIFIC PRODUCT MATCH FIRST
    ===================================================== */

    const specificRules = [

        {
            words: [
                "aquarium filter",
                "fish filter",
                "water filter",
                "tank filter",
                "sponge filter"
            ],
            group: "aquarium filter"
        },

        {
            words: [
                "bird seed mix",
                "bird seeds",
                "bird seed",
                "seed mix",
                "bird feed"
            ],
            group: "bird seed mix"
        },

        {
            words: [
                "fish food",
                "fish feed",
                "fish flakes",
                "fish pellets",
                "aquarium food"
            ],
            group: "fish food"
        },

        {
            words: [
                "pomeranian",
                "pomeranian dog"
            ],
            group: "pomeranian"
        },

        {
            words: [
                "labrador",
                "labrador dog",
                "lab dog"
            ],
            group: "labrador"
        }

    ];


    for (
        let i = 0;
        i < specificRules.length;
        i++
    ) {

        const rule =
            specificRules[i];

        const matched =
            rule.words.some(
                function(word) {

                    return combinedText.includes(
                        normalizeProductText(word)
                    );

                }
            );


        if (matched) {

            const group =
                royalPawsImages.find(
                    function(item) {

                        return item.keywords.includes(
                            rule.group
                        );

                    }
                );


            if (group) {

                const image =
                    getUniqueImage(
                        group.urls
                    );

                if (image) {
                    return image;
                }
            }
        }
    }


    /* =====================================================
       GENERAL MATCH
    ===================================================== */

    for (
        let i = 0;
        i < royalPawsImages.length;
        i++
    ) {

        const item =
            royalPawsImages[i];


        const matched =
            item.keywords.some(
                function(keyword) {

                    return combinedText.includes(
                        normalizeProductText(
                            keyword
                        )
                    );

                }
            );


        if (matched) {

            const image =
                getUniqueImage(
                    item.urls
                );


            if (image) {
                return image;
            }
        }
    }


    /* =====================================================
       FALLBACK
    ===================================================== */

    const fallback =
        getUniqueImage(
            royalPawsFallbackImages
        );


    if (fallback) {
        return fallback;
    }


    return "";
}


/* =========================================================
   CATEGORY IMAGES
========================================================= */

const royalPawsCategoryImages = {

    dogs: [

        /* POMERANIAN FIRST */

        "https://images.unsplash.com/photo-1558929996-da64ba858215?auto=format&fit=crop&w=1200&q=90",

        "https://images.unsplash.com/photo-1583512603805-3cc6b41f3edb?auto=format&fit=crop&w=1200&q=90",

        "https://images.unsplash.com/photo-1558788353-f76d92427f16?auto=format&fit=crop&w=1200&q=90"

    ],


    cats: [

        "https://images.unsplash.com/photo-1514888286974-6c03e2ca1dba?auto=format&fit=crop&w=1200&q=90",

        "https://images.unsplash.com/photo-1574158622682-e40e69881006?auto=format&fit=crop&w=1200&q=90",

        "https://images.unsplash.com/photo-1533740348177-836f3c3b5a3c?auto=format&fit=crop&w=1200&q=90"

    ],


    birds: [

        "https://images.unsplash.com/photo-1444464666168-49d633b86797?auto=format&fit=crop&w=1200&q=90",

        "https://images.unsplash.com/photo-1452570053594-1b985d6ea890?auto=format&fit=crop&w=1200&q=90",

        "https://images.unsplash.com/photo-1552728089-57bdde30beb3?auto=format&fit=crop&w=1200&q=90"

    ],


    fish: [

        "https://images.unsplash.com/photo-1520990261762-8b0b2f1a5f5d?auto=format&fit=crop&w=1200&q=90",

        "https://images.unsplash.com/photo-1544550285-f813152fb2fd?auto=format&fit=crop&w=1200&q=90",

        "https://images.unsplash.com/photo-1535591273668-578e31182c4f?auto=format&fit=crop&w=1200&q=90"

    ],


    food: [

        "https://images.unsplash.com/photo-1589924691995-400dc9ecc119?auto=format&fit=crop&w=1200&q=90",

        "https://images.unsplash.com/photo-1494947665470-20322015e3a8?auto=format&fit=crop&w=1200&q=90",

        "https://images.unsplash.com/photo-1558788353-f76d92427f16?auto=format&fit=crop&w=1200&q=90"

    ],


    toys: [

        "https://images.unsplash.com/photo-1601758064138-732e0e6d8c0c?auto=format&fit=crop&w=1200&q=90",

        "https://images.unsplash.com/photo-1535930749574-1399327ce78f?auto=format&fit=crop&w=1200&q=90",

        "https://images.unsplash.com/photo-1592194996308-7b43878e84a6?auto=format&fit=crop&w=1200&q=90"

    ],


    default: [

        "https://images.unsplash.com/photo-1537151608828-ea2b11777ee8?auto=format&fit=crop&w=1200&q=90",

        "https://images.unsplash.com/photo-1561037404-61cd46aa615b?auto=format&fit=crop&w=1200&q=90",

        "https://images.unsplash.com/photo-1517849845537-4d257902454a?auto=format&fit=crop&w=1200&q=90"

    ]

};


/* =========================================================
   CATEGORY KEY
========================================================= */

function getCategoryKey(name) {

    const text =
        normalizeProductText(name);


    if (
        text.includes("dog") ||
        text.includes("puppy")
    ) {
        return "dogs";
    }


    if (
        text.includes("cat") ||
        text.includes("kitten")
    ) {
        return "cats";
    }


    if (
        text.includes("bird") ||
        text.includes("parrot")
    ) {
        return "birds";
    }


    if (
        text.includes("fish") ||
        text.includes("aquarium")
    ) {
        return "fish";
    }


    if (
        text.includes("food") ||
        text.includes("feed")
    ) {
        return "food";
    }


    if (
        text.includes("toy") ||
        text.includes("toys")
    ) {
        return "toys";
    }


    return "default";
}


/* =========================================================
   CATEGORY IMAGE CHOICE
========================================================= */

function chooseCategoryImage(name) {

    const key =
        getCategoryKey(name);

    const pool =
        royalPawsCategoryImages[key] ||
        royalPawsCategoryImages.default;


    for (
        let i = 0;
        i < pool.length;
        i++
    ) {

        const url =
            pool[i];


        if (
            !usedVisualImages.has(url)
        ) {

            usedVisualImages.add(url);

            return url;
        }
    }


    return "";
}


/* =========================================================
   LOAD PRODUCT IMAGES
========================================================= */

document.addEventListener(
    "DOMContentLoaded",
    function () {

        const productCards =
            document.querySelectorAll(
                ".product-card"
            );


        productCards.forEach(
            function(card) {

                const img =
                    card.querySelector(
                        ".product-image img"
                    );

                const loading =
                    card.querySelector(
                        ".image-loading"
                    );


                if (!img) {
                    return;
                }


                const productName =
                    card.dataset.productName || "";

                const categoryName =
                    card.dataset.productCategory || "";

                const databaseImage =
                    card.dataset.databaseImage || "";


                img.addEventListener(
                    "load",
                    function() {

                        if (
                            img.naturalWidth > 0
                        ) {

                            img.classList.add(
                                "loaded"
                            );

                            if (loading) {

                                loading.classList.add(
                                    "hide"
                                );
                            }
                        }
                    }
                );


                img.addEventListener(
                    "error",
                    function() {

                        if (
                            img.dataset.failed === "1"
                        ) {

                            img.style.display =
                                "none";

                            if (loading) {
                                loading.classList.add(
                                    "hide"
                                );
                            }

                            return;
                        }


                        img.dataset.failed = "1";


                        const replacement =
                            findUniqueProductImage(
                                productName,
                                categoryName
                            );


                        if (replacement) {

                            img.src =
                                replacement;

                        } else {

                            img.style.display =
                                "none";

                            if (loading) {
                                loading.classList.add(
                                    "hide"
                                );
                            }
                        }
                    }
                );


                /*
                 * ALWAYS TRY RELEVANT IMAGE FIRST.
                 */

                const uniqueImage =
                    findUniqueProductImage(
                        productName,
                        categoryName
                    );


                if (uniqueImage) {

                    img.src =
                        uniqueImage;

                } else if (
                    databaseImage &&
                    databaseImage.trim() !== ""
                ) {

                    img.src =
                        databaseImage;
                }


                if (
                    img.complete &&
                    img.naturalWidth > 0
                ) {

                    img.classList.add(
                        "loaded"
                    );

                    if (loading) {

                        loading.classList.add(
                            "hide"
                        );
                    }
                }

            }
        );

    }
);


/* =========================================================
   CATEGORY IMAGE LOADER
========================================================= */

document.addEventListener(
    "DOMContentLoaded",
    function() {

        const categoryCards =
            document.querySelectorAll(
                ".category-card"
            );


        categoryCards.forEach(
            function(card) {

                const img =
                    card.querySelector(
                        ".category-image img"
                    );

                const loading =
                    card.querySelector(
                        ".category-image-loading"
                    );


                if (!img) {
                    return;
                }


                const categoryName =
                    card.dataset.categoryName || "";


                img.addEventListener(
                    "load",
                    function() {

                        if (
                            img.naturalWidth > 0
                        ) {

                            img.classList.add(
                                "loaded"
                            );

                            if (loading) {

                                loading.classList.add(
                                    "hide"
                                );
                            }
                        }
                    }
                );


                img.addEventListener(
                    "error",
                    function() {

                        if (
                            img.dataset.failed === "1"
                        ) {

                            img.style.display =
                                "none";

                            if (loading) {
                                loading.classList.add(
                                    "hide"
                                );
                            }

                            return;
                        }


                        img.dataset.failed = "1";


                        const fallback =
                            chooseCategoryImage(
                                categoryName
                            );


                        if (fallback) {

                            img.src =
                                fallback;

                        } else {

                            img.style.display =
                                "none";

                            if (loading) {
                                loading.classList.add(
                                    "hide"
                                );
                            }
                        }

                    }
                );


                /*
                 * FIX CATEGORY IMAGE
                 */

                const categoryImage =
                    chooseCategoryImage(
                        categoryName
                    );


                if (categoryImage) {

                    img.src =
                        categoryImage;
                }

            }
        );

    }
);


/* =========================================================
   SCROLL ANIMATION
========================================================= */

document.addEventListener(
    "DOMContentLoaded",
    function() {

        const cards =
            document.querySelectorAll(
                ".category-card, .product-card"
            );


        if (
            "IntersectionObserver"
            in window
        ) {

            const observer =
                new IntersectionObserver(
                    function(entries) {

                        entries.forEach(
                            function(entry) {

                                if (
                                    entry.isIntersecting
                                ) {

                                    entry.target.classList.add(
                                        "visible"
                                    );

                                    observer.unobserve(
                                        entry.target
                                    );
                                }

                            }
                        );

                    },
                    {
                        threshold: .08
                    }
                );


            cards.forEach(
                function(card,index) {

                    card.style.transitionDelay =
                        (index * .06) + "s";

                    observer.observe(
                        card
                    );

                }
            );


        } else {

            cards.forEach(
                function(card) {

                    card.classList.add(
                        "visible"
                    );

                }
            );

        }

    }
);

</script>


</body>
</html>

