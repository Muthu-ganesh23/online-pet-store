<?php

require_once "../config/database.php";
require_once "../config/functions.php";

requireAdmin();

/* =========================================
   UPDATE STOCK
========================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $productId = isset($_POST["product_id"])
        ? (int) $_POST["product_id"]
        : 0;

    $stock = isset($_POST["stock"])
        ? (int) $_POST["stock"]
        : -1;

    if ($productId <= 0 || $stock < 0 || $stock > 999999) {

        setMessage(
            "Please enter a valid stock value.",
            "error"
        );

        redirect("stock.php");
        exit;
    }

    $stmt = $conn->prepare("
        UPDATE products
        SET stock = ?
        WHERE id = ?
    ");

    if (!$stmt) {

        setMessage(
            "Stock update failed.",
            "error"
        );

        redirect("stock.php");
        exit;
    }

    $stmt->bind_param(
        "ii",
        $stock,
        $productId
    );

    if ($stmt->execute()) {

        setMessage(
            "Stock updated successfully.",
            "success"
        );

    } else {

        setMessage(
            "Stock update failed.",
            "error"
        );
    }

    $stmt->close();

    redirect("stock.php");
    exit;
}


/* =========================================
   SEARCH
========================================= */

$search = isset($_GET["search"])
    ? trim($_GET["search"])
    : "";


/* =========================================
   GET PRODUCTS
========================================= */

if ($search !== "") {

    $searchLike = "%" . $search . "%";

    $stmt = $conn->prepare("
        SELECT
            p.id,
            p.name,
            p.stock,
            p.status,
            p.image,
            c.name AS category_name
        FROM products p
        INNER JOIN categories c
            ON p.category_id = c.id
        WHERE p.name LIKE ?
           OR c.name LIKE ?
        ORDER BY p.stock ASC, p.id DESC
    ");

    $stmt->bind_param(
        "ss",
        $searchLike,
        $searchLike
    );

} else {

    $stmt = $conn->prepare("
        SELECT
            p.id,
            p.name,
            p.stock,
            p.status,
            p.image,
            c.name AS category_name
        FROM products p
        INNER JOIN categories c
            ON p.category_id = c.id
        ORDER BY p.stock ASC, p.id DESC
    ");
}

$stmt->execute();

$products = $stmt->get_result();


/* =========================================
   STOCK SUMMARY
========================================= */

$totalProducts = 0;
$totalStock = 0;
$lowStock = 0;
$outOfStock = 0;

$summaryResult = $conn->query("
    SELECT
        COUNT(*) AS total_products,
        COALESCE(SUM(stock), 0) AS total_stock,
        COALESCE(
            SUM(
                CASE
                    WHEN stock <= 5 THEN 1
                    ELSE 0
                END
            ),
            0
        ) AS low_stock,
        COALESCE(
            SUM(
                CASE
                    WHEN stock = 0 THEN 1
                    ELSE 0
                END
            ),
            0
        ) AS out_stock
    FROM products
");

if ($summaryResult) {

    $summary = $summaryResult->fetch_assoc();

    $totalProducts = (int) $summary["total_products"];
    $totalStock = (int) $summary["total_stock"];
    $lowStock = (int) $summary["low_stock"];
    $outOfStock = (int) $summary["out_stock"];
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

<title>Stock Management - Royal Paws</title>


<style>

/* =========================================
   RESET
========================================= */

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

html {
    width: 100%;
    min-height: 100%;
    overflow-x: hidden;
}

body {
    width: 100%;
    min-height: 100vh;

    font-family:
        "Segoe UI",
        Arial,
        sans-serif;

    background:
        linear-gradient(
            135deg,
            #fffaf0,
            #f5ead0,
            #fff
        );

    color: #2f2419;

    overflow-x: hidden;
}


/* =========================================
   HEADER
========================================= */

.header {

    width: 100%;

    background:
        linear-gradient(
            135deg,
            #24150b,
            #5c3518,
            #24150b
        );

    color: #fff;

    padding: 20px 30px;

    display: flex;

    justify-content: space-between;

    align-items: center;

    box-shadow:
        0 5px 20px rgba(0,0,0,.25);

    position: sticky;

    top: 0;

    z-index: 100;

    min-height: 75px;
}

.logo {

    font-size: 25px;

    font-weight: 800;

    letter-spacing: 1px;

    white-space: nowrap;
}

.logo span {
    color: #e7b75b;
}

.header-right {

    display: flex;

    align-items: center;

    gap: 15px;
}

.admin-name {

    color: #f4d58d;

    font-size: 14px;

    white-space: nowrap;
}

.logout-btn {

    display: inline-flex;

    align-items: center;

    justify-content: center;

    text-decoration: none;

    background: #b8860b;

    color: #fff;

    padding: 9px 16px;

    border-radius: 8px;

    font-weight: 600;

    transition:
        background-color .2s ease,
        box-shadow .2s ease;
}

.logout-btn:hover {

    background: #d39b18;

    box-shadow:
        0 5px 12px rgba(0,0,0,.15);
}


/* =========================================
   LAYOUT
========================================= */

.layout {

    display: flex;

    width: 100%;

    min-height:
        calc(100vh - 75px);
}


/* =========================================
   SIDEBAR
========================================= */

.sidebar {

    width: 240px;

    min-width: 240px;

    background:
        linear-gradient(
            180deg,
            #29170d,
            #402514
        );

    padding: 25px 15px;

    box-shadow:
        5px 0 20px rgba(0,0,0,.12);
}

.sidebar-title {

    color: #e9c477;

    font-size: 12px;

    text-transform: uppercase;

    letter-spacing: 2px;

    margin:
        10px 15px 15px;
}

.sidebar a {

    display: block;

    width: 100%;

    text-decoration: none;

    color: #f6ead7;

    padding: 13px 15px;

    margin-bottom: 6px;

    border-radius: 10px;

    transition:
        background-color .2s ease,
        color .2s ease;
}

.sidebar a:hover,
.sidebar a.active {

    background:
        linear-gradient(
            135deg,
            #b8860b,
            #d6a52f
        );

    color: #fff;
}


/* =========================================
   CONTENT
========================================= */

.content {

    flex: 1;

    min-width: 0;

    padding: 35px;

    overflow-x: hidden;
}

.page-title {

    font-size: 32px;

    font-weight: 800;

    margin-bottom: 8px;

    color: #3a2413;
}

.page-subtitle {

    color: #796750;

    margin-bottom: 28px;
}


/* =========================================
   FLASH MESSAGE
========================================= */

.flash-message {

    padding: 14px 18px;

    border-radius: 10px;

    margin-bottom: 22px;

    font-weight: 600;

    animation:
        slideDown .35s ease;

    will-change: opacity, transform;
}

.flash-message.success {

    background: #e8f7ed;

    color: #1d6b35;

    border:
        1px solid #b9e4c4;
}

.flash-message.error {

    background: #fff0f0;

    color: #a12626;

    border:
        1px solid #efb5b5;
}

@keyframes slideDown {

    from {
        opacity: 0;
        transform: translateY(-10px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}


/* =========================================
   SUMMARY
========================================= */

.summary-grid {

    display: grid;

    grid-template-columns:
        repeat(4, minmax(0, 1fr));

    gap: 18px;

    margin-bottom: 28px;
}

.summary-card {

    background:
        rgba(255,255,255,.9);

    border-radius: 18px;

    padding: 22px;

    min-width: 0;

    box-shadow:
        0 8px 25px
        rgba(70,45,20,.10);

    border:
        1px solid #eadcc4;

    transition:
        box-shadow .2s ease;
}

.summary-card:hover {

    box-shadow:
        0 14px 30px
        rgba(70,45,20,.16);
}

.summary-icon {

    font-size: 28px;

    margin-bottom: 10px;
}

.summary-label {

    color: #806d57;

    font-size: 14px;
}

.summary-value {

    font-size: 29px;

    font-weight: 800;

    color: #4b2e17;

    margin-top: 5px;
}


/* =========================================
   SEARCH
========================================= */

.search-box {

    width: 100%;

    background:
        rgba(255,255,255,.9);

    padding: 20px;

    border-radius: 16px;

    box-shadow:
        0 8px 25px
        rgba(70,45,20,.08);

    margin-bottom: 25px;

    border:
        1px solid #eadcc4;
}

.search-form {

    display: flex;

    align-items: stretch;

    gap: 12px;

    width: 100%;
}

.search-input {

    flex: 1;

    min-width: 0;

    height: 44px;

    padding:
        13px 15px;

    border:
        1px solid #d8c7aa;

    border-radius: 9px;

    font-size: 15px;

    outline: none;

    background: #fff;
}

.search-input:focus {

    border-color: #b8860b;

    box-shadow:
        0 0 0 3px
        rgba(184,134,11,.12);
}

.search-btn {

    height: 44px;

    border: none;

    background:
        linear-gradient(
            135deg,
            #8d6507,
            #c99a25
        );

    color: white;

    padding:
        0 22px;

    border-radius: 9px;

    font-weight: 700;

    cursor: pointer;

    white-space: nowrap;

    transition:
        background-color .2s ease,
        box-shadow .2s ease;
}

.search-btn:hover {

    box-shadow:
        0 5px 12px
        rgba(100,70,10,.20);
}

.clear-btn {

    display: flex;

    align-items: center;

    justify-content: center;

    height: 44px;

    text-decoration: none;

    background: #eee4d4;

    color: #50351f;

    padding:
        0 18px;

    border-radius: 9px;

    font-weight: 600;

    white-space: nowrap;
}


/* =========================================
   TABLE
========================================= */

.table-card {

    width: 100%;

    background:
        rgba(255,255,255,.95);

    border-radius: 18px;

    overflow: hidden;

    box-shadow:
        0 8px 28px
        rgba(70,45,20,.10);

    border:
        1px solid #eadcc4;
}

.table-wrapper {

    width: 100%;

    overflow-x: auto;

    overflow-y: hidden;

    -webkit-overflow-scrolling: touch;
}

table {

    width: 100%;

    min-width: 850px;

    table-layout: fixed;

    border-collapse: collapse;
}

thead {

    background:
        linear-gradient(
            135deg,
            #3a2110,
            #62401d
        );

    color: #fff;
}

th {

    padding: 16px;

    text-align: left;

    font-size: 13px;

    text-transform: uppercase;

    letter-spacing: .5px;
}

td {

    padding:
        15px 16px;

    border-bottom:
        1px solid #eee3d2;

    vertical-align: middle;
}


/* =========================================
   FIXED TABLE COLUMN WIDTHS
========================================= */

th:nth-child(1),
td:nth-child(1) {
    width: 32%;
}

th:nth-child(2),
td:nth-child(2) {
    width: 18%;
}

th:nth-child(3),
td:nth-child(3) {
    width: 15%;
}

th:nth-child(4),
td:nth-child(4) {
    width: 13%;
}

th:nth-child(5),
td:nth-child(5) {
    width: 22%;
}


/* =========================================
   TABLE ROW
========================================= */

tbody tr {

    transition:
        background-color .2s ease;
}

tbody tr:hover {

    background: #fffaf2;
}


/* =========================================
   PRODUCT
========================================= */

.product-info {

    display: flex;

    align-items: center;

    gap: 13px;

    min-width: 0;
}

.product-info > div {

    min-width: 0;

    overflow: hidden;
}

.product-image {

    display: block;

    width: 55px;

    min-width: 55px;

    height: 55px;

    min-height: 55px;

    object-fit: cover;

    border-radius: 10px;

    border:
        2px solid #ead5ad;

    background: #fff;

    flex-shrink: 0;
}

.product-name {

    font-weight: 700;

    color: #432915;

    white-space: nowrap;

    overflow: hidden;

    text-overflow: ellipsis;
}

.category {

    font-size: 12px;

    color: #8a765d;

    margin-top: 3px;
}


/* =========================================
   STOCK STATUS
========================================= */

.stock-number {

    font-size: 20px;

    font-weight: 800;
}

.stock-good {
    color: #277441;
}

.stock-low {
    color: #c2790b;
}

.stock-out {
    color: #b12828;
}


/* =========================================
   STATUS BADGE
========================================= */

.status-badge {

    display: inline-block;

    padding:
        6px 10px;

    border-radius: 20px;

    font-size: 12px;

    font-weight: 700;

    white-space: nowrap;
}

.status-active {

    background: #e4f6e9;

    color: #277441;
}

.status-inactive {

    background: #f4e7e7;

    color: #8d3333;
}


/* =========================================
   STOCK FORM
========================================= */

.stock-form {

    display: flex;

    align-items: center;

    gap: 7px;

    white-space: nowrap;
}

.stock-input {

    width: 90px;

    min-width: 90px;

    height: 38px;

    padding:
        9px 10px;

    border:
        1px solid #d6c5aa;

    border-radius: 7px;

    text-align: center;

    font-weight: 700;

    outline: none;

    background: #fff;

    flex-shrink: 0;
}

.stock-input:focus {

    border-color: #b8860b;

    box-shadow:
        0 0 0 3px
        rgba(184,134,11,.12);
}


/* Remove browser number arrows */

.stock-input::-webkit-outer-spin-button,
.stock-input::-webkit-inner-spin-button {

    -webkit-appearance: none;

    margin: 0;
}

.stock-input[type=number] {

    -moz-appearance: textfield;
}


/* =========================================
   UPDATE BUTTON
========================================= */

.update-btn {

    height: 38px;

    border: none;

    background:
        linear-gradient(
            135deg,
            #6e4b15,
            #a77716
        );

    color: #fff;

    padding:
        9px 12px;

    border-radius: 7px;

    cursor: pointer;

    font-weight: 600;

    white-space: nowrap;

    transition:
        background-color .2s ease,
        box-shadow .2s ease;
}

.update-btn:hover {

    background: #8b6418;

    box-shadow:
        0 4px 10px
        rgba(80,50,10,.18);
}


/* =========================================
   ROW HIGHLIGHTS
========================================= */

.low-row {

    background: #fffaf0;
}

.out-row {

    background: #fff2f2;
}


/* =========================================
   EMPTY
========================================= */

.empty {

    text-align: center;

    padding:
        50px 20px;

    color: #806d57;

    font-size: 16px;
}


/* =========================================
   RESPONSIVE - 1000px
========================================= */

@media (max-width: 1000px) {

    .summary-grid {

        grid-template-columns:
            repeat(2, minmax(0, 1fr));
    }

    .sidebar {

        width: 200px;

        min-width: 200px;
    }

    .content {

        padding: 28px;
    }
}


/* =========================================
   RESPONSIVE - 700px
========================================= */

@media (max-width: 700px) {

    .header {

        padding:
            15px;

        min-height: 65px;
    }

    .logo {

        font-size: 21px;
    }

    .admin-name {

        display: none;
    }

    .layout {

        flex-direction: column;

        min-height:
            calc(100vh - 65px);
    }

    .sidebar {

        width: 100%;

        min-width: 100%;

        padding: 12px;

        display: flex;

        gap: 5px;

        overflow-x: auto;

        overflow-y: hidden;

        box-shadow:
            0 4px 12px
            rgba(0,0,0,.12);
    }

    .sidebar-title {

        display: none;
    }

    .sidebar a {

        width: auto;

        white-space: nowrap;

        margin: 0;

        flex-shrink: 0;
    }

    .content {

        width: 100%;

        padding:
            20px 14px;
    }

    .summary-grid {

        grid-template-columns:
            repeat(2, minmax(0, 1fr));

        gap: 12px;
    }

    .summary-card {

        padding: 18px;
    }

    .summary-value {

        font-size: 25px;
    }

    .search-form {

        flex-direction: column;

        gap: 10px;
    }

    .search-input {

        width: 100%;
    }

    .search-btn,
    .clear-btn {

        width: 100%;

        min-height: 44px;
    }

    .table-card {

        border-radius: 14px;
    }
}


/* =========================================
   RESPONSIVE - 450px
========================================= */

@media (max-width: 450px) {

    .summary-grid {

        grid-template-columns: 1fr;
    }

    .page-title {

        font-size: 25px;
    }

    .page-subtitle {

        font-size: 14px;
    }

    .logo {

        font-size: 19px;
    }

    .logout-btn {

        padding:
            8px 12px;

        font-size: 13px;
    }
}


/* =========================================
   ACCESSIBILITY
========================================= */

button,
input,
a {

    -webkit-tap-highlight-color:
        transparent;
}

button:focus-visible,
input:focus-visible,
a:focus-visible {

    outline:
        2px solid #b8860b;

    outline-offset: 2px;
}

</style>

</head>


<body>


<!-- =========================================
     HEADER
========================================= -->

<header class="header">

    <div class="logo">

        🐾 Royal <span>Paws</span>

    </div>


    <div class="header-right">

        <div class="admin-name">

            👑 Admin Panel

        </div>


        <a
            href="logout.php"
            class="logout-btn"
            onclick="return confirm('Logout from admin panel?');"
        >
            Logout
        </a>

    </div>

</header>



<div class="layout">


<!-- =========================================
     SIDEBAR
========================================= -->

<aside class="sidebar">

    <div class="sidebar-title">

        Administration

    </div>


    <a href="dashboard.php">

        📊 Dashboard

    </a>


    <a href="products.php">

        🛍️ Products

    </a>


    <a href="add_product.php">

        ➕ Add Product

    </a>


    <a
        href="stock.php"
        class="active"
    >

        📦 Stock

    </a>


    <a href="orders.php">

        🧾 Orders

    </a>


    <a href="feedback.php">

        ⭐ Feedback

    </a>

</aside>



<!-- =========================================
     CONTENT
========================================= -->

<main class="content">


    <h1 class="page-title">

        📦 Stock Management

    </h1>


    <p class="page-subtitle">

        Monitor and update your pet store inventory.

    </p>


    <?php showMessage(); ?>


    <!-- =====================================
         SUMMARY CARDS
    ====================================== -->

    <div class="summary-grid">


        <div class="summary-card">

            <div class="summary-icon">
                🛍️
            </div>

            <div class="summary-label">
                Total Products
            </div>

            <div class="summary-value">

                <?php
                echo $totalProducts;
                ?>

            </div>

        </div>



        <div class="summary-card">

            <div class="summary-icon">
                📦
            </div>

            <div class="summary-label">
                Total Stock
            </div>

            <div class="summary-value">

                <?php
                echo $totalStock;
                ?>

            </div>

        </div>



        <div class="summary-card">

            <div class="summary-icon">
                ⚠️
            </div>

            <div class="summary-label">
                Low Stock
            </div>

            <div class="summary-value">

                <?php
                echo $lowStock;
                ?>

            </div>

        </div>



        <div class="summary-card">

            <div class="summary-icon">
                🚫
            </div>

            <div class="summary-label">
                Out of Stock
            </div>

            <div class="summary-value">

                <?php
                echo $outOfStock;
                ?>

            </div>

        </div>


    </div>



    <!-- =====================================
         SEARCH
    ====================================== -->

    <div class="search-box">


        <form
            method="GET"
            class="search-form"
        >


            <input
                type="text"
                name="search"
                class="search-input"
                placeholder="Search product or category..."
                value="<?php echo e($search); ?>"
            >


            <button
                type="submit"
                class="search-btn"
            >

                🔍 Search

            </button>


            <?php if ($search !== ""): ?>

                <a
                    href="stock.php"
                    class="clear-btn"
                >

                    Clear

                </a>

            <?php endif; ?>


        </form>


    </div>



    <!-- =====================================
         TABLE
    ====================================== -->

    <div class="table-card">


        <div class="table-wrapper">


            <table>


                <thead>

                    <tr>

                        <th>
                            Product
                        </th>

                        <th>
                            Category
                        </th>

                        <th>
                            Current Stock
                        </th>

                        <th>
                            Status
                        </th>

                        <th>
                            Update Stock
                        </th>

                    </tr>

                </thead>


                <tbody>


                <?php if ($products->num_rows > 0): ?>


                    <?php while ($product = $products->fetch_assoc()): ?>


                        <?php

                        $stock =
                            (int) $product["stock"];


                        if ($stock === 0) {

                            $rowClass = "out-row";

                            $stockClass =
                                "stock-out";

                            $stockText =
                                "Out of stock";

                        } elseif ($stock <= 5) {

                            $rowClass = "low-row";

                            $stockClass =
                                "stock-low";

                            $stockText =
                                "Low stock";

                        } else {

                            $rowClass = "";

                            $stockClass =
                                "stock-good";

                            $stockText =
                                "Available";
                        }


                        /*
                         * IMAGE PATH
                         *
                         * Database should contain:
                         *
                         * dog-food.jpg
                         *
                         * NOT:
                         * ../assets/images/dog-food.jpg
                         */

                        $imageName =
                            trim(
                                (string)
                                $product["image"]
                            );


                        if ($imageName !== "") {

                            $image =
                                "../assets/images/"
                                . basename($imageName);

                        } else {

                            $image =
                                "../assets/images/logo.png";
                        }

                        ?>


                        <tr
                            class="<?php
                            echo $rowClass;
                            ?>"
                        >


                            <!-- PRODUCT -->

                            <td>


                                <div
                                    class="product-info"
                                >


                                    <img
                                        src="<?php
                                        echo e($image);
                                        ?>"
                                        class="product-image"
                                        alt="<?php
                                        echo e(
                                            $product["name"]
                                        );
                                        ?>"
                                        onerror="
                                            this.onerror=null;
                                            this.src='../assets/images/logo.png';
                                        "
                                    >


                                    <div>


                                        <div
                                            class="product-name"
                                            title="<?php
                                            echo e(
                                                $product["name"]
                                            );
                                            ?>"
                                        >

                                            <?php

                                            echo e(
                                                $product["name"]
                                            );

                                            ?>

                                        </div>


                                        <div class="category">

                                            ID:

                                            <?php

                                            echo (int)
                                                $product["id"];

                                            ?>

                                        </div>


                                    </div>


                                </div>


                            </td>



                            <!-- CATEGORY -->

                            <td>

                                <?php

                                echo e(
                                    $product[
                                        "category_name"
                                    ]
                                );

                                ?>

                            </td>



                            <!-- CURRENT STOCK -->

                            <td>


                                <span
                                    class="stock-number
                                    <?php
                                    echo $stockClass;
                                    ?>"
                                >

                                    <?php
                                    echo $stock;
                                    ?>

                                </span>


                                <div class="category">

                                    <?php
                                    echo $stockText;
                                    ?>

                                </div>


                            </td>



                            <!-- STATUS -->

                            <td>


                                <?php
                                if (
                                    $product["status"]
                                    === "active"
                                ):
                                ?>


                                    <span
                                        class="
                                        status-badge
                                        status-active
                                        "
                                    >

                                        Active

                                    </span>


                                <?php else: ?>


                                    <span
                                        class="
                                        status-badge
                                        status-inactive
                                        "
                                    >

                                        Inactive

                                    </span>


                                <?php endif; ?>


                            </td>



                            <!-- UPDATE STOCK -->

                            <td>


                                <form
                                    method="POST"
                                    class="stock-form"
                                    onsubmit="
                                        return validateStock(this);
                                    "
                                >


                                    <input
                                        type="hidden"
                                        name="product_id"
                                        value="<?php
                                        echo (int)
                                            $product["id"];
                                        ?>"
                                    >


                                    <input
                                        type="number"
                                        name="stock"
                                        class="stock-input"
                                        min="0"
                                        max="999999"
                                        value="<?php
                                        echo $stock;
                                        ?>"
                                        required
                                    >


                                    <button
                                        type="submit"
                                        class="update-btn"
                                    >

                                        Update

                                    </button>


                                </form>


                            </td>


                        </tr>


                    <?php endwhile; ?>


                <?php else: ?>


                    <tr>

                        <td colspan="5">


                            <div class="empty">

                                📦

                                <br><br>

                                No products found.

                            </div>


                        </td>

                    </tr>


                <?php endif; ?>


                </tbody>


            </table>


        </div>


    </div>


</main>


</div>



<script>

/* =========================================
   STOCK VALIDATION
========================================= */

function validateStock(form) {

    const input =
        form.querySelector(".stock-input");

    if (!input) {
        return false;
    }

    const value =
        parseInt(input.value, 10);


    if (
        isNaN(value) ||
        value < 0
    ) {

        alert(
            "Stock cannot be negative."
        );

        input.focus();

        return false;
    }


    if (value > 999999) {

        alert(
            "Please enter a valid stock value."
        );

        input.focus();

        return false;
    }


    return true;
}


/* =========================================
   AUTO HIDE FLASH MESSAGE
========================================= */

document.addEventListener(
    "DOMContentLoaded",
    function () {

        const message =
            document.querySelector(
                ".flash-message"
            );


        if (!message) {
            return;
        }


        setTimeout(
            function () {

                message.style.transition =
                    "opacity .4s ease, transform .4s ease";

                message.style.opacity = "0";

                message.style.transform =
                    "translateY(-8px)";


                setTimeout(
                    function () {

                        if (message) {

                            message.style.display =
                                "none";
                        }

                    },
                    450
                );


            },
            3500
        );

    }
);

</script>


</body>

</html>
