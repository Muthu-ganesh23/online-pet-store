<?php
require_once "../config/database.php";
require_once "../config/functions.php";

requireAdmin();

/* =========================
   DASHBOARD COUNTS
========================= */

// Total Users
$result = $conn->query("SELECT COUNT(*) AS total FROM users");
$totalUsers = $result->fetch_assoc()['total'];

// Total Products
$result = $conn->query("SELECT COUNT(*) AS total FROM products");
$totalProducts = $result->fetch_assoc()['total'];

// Total Orders
$result = $conn->query("SELECT COUNT(*) AS total FROM orders");
$totalOrders = $result->fetch_assoc()['total'];

// Total Revenue
$result = $conn->query("
    SELECT COALESCE(SUM(total_amount), 0) AS revenue
    FROM orders
    WHERE order_status != 'Cancelled'
");
$totalRevenue = $result->fetch_assoc()['revenue'];

// Total Stock
$result = $conn->query("
    SELECT COALESCE(SUM(stock), 0) AS stock
    FROM products
    WHERE status = 'active'
");
$totalStock = $result->fetch_assoc()['stock'];

// Total Profit
$result = $conn->query("
    SELECT COALESCE(
        SUM(
            (oi.selling_price - oi.cost_price) * oi.quantity
        ), 0
    ) AS profit
    FROM order_items oi
    INNER JOIN orders o ON oi.order_id = o.id
    WHERE o.order_status != 'Cancelled'
");
$totalProfit = $result->fetch_assoc()['profit'];

// Low Stock
$lowStock = $conn->query("
    SELECT id, name, stock
    FROM products
    WHERE status = 'active'
    AND stock <= 5
    ORDER BY stock ASC, name ASC
    LIMIT 10
");

// Recent Orders
$recentOrders = $conn->query("
    SELECT 
        o.id,
        o.total_amount,
        o.payment_method,
        o.order_status,
        o.order_date,
        u.name AS user_name
    FROM orders o
    INNER JOIN users u ON o.user_id = u.id
    ORDER BY o.order_date DESC
    LIMIT 8
");

function statusClass($status) {
    switch ($status) {
        case "Delivered":
            return "delivered";
        case "Shipped":
            return "shipped";
        case "Processing":
            return "processing";
        case "Confirmed":
            return "confirmed";
        case "Cancelled":
            return "cancelled";
        default:
            return "pending";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Admin Dashboard | Royal Paws</title>

<style>

/* =========================
   RESET
========================= */

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: Arial, Helvetica, sans-serif;
}

body {
    background: #f4f1ea;
    color: #29241d;
}

/* =========================
   SIDEBAR
========================= */

.sidebar {
    position: fixed;
    left: 0;
    top: 0;
    width: 250px;
    height: 100vh;
    background: linear-gradient(
        180deg,
        #24180c,
        #3d2812,
        #1d140b
    );
    color: white;
    padding: 25px 18px;
    z-index: 1000;
    box-shadow: 5px 0 20px rgba(0,0,0,0.18);
}

.logo-area {
    text-align: center;
    margin-bottom: 35px;
}

.logo-circle {
    width: 65px;
    height: 65px;
    margin: auto;
    border-radius: 50%;
    background: linear-gradient(135deg, #d4af37, #f6df8b);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #24180c;
    font-size: 30px;
    box-shadow: 0 0 20px rgba(212,175,55,0.45);
}

.logo-area h2 {
    margin-top: 12px;
    color: #f4d77a;
    font-size: 21px;
}

.logo-area p {
    color: #d5c7aa;
    font-size: 12px;
    margin-top: 4px;
}

.menu {
    list-style: none;
}

.menu li {
    margin: 8px 0;
}

.menu a {
    display: block;
    padding: 13px 15px;
    text-decoration: none;
    color: #eee5d1;
    border-radius: 10px;
    transition: 0.3s;
    font-size: 14px;
}

.menu a:hover,
.menu a.active {
    background: linear-gradient(
        90deg,
        #c69a2d,
        #e6c45c
    );
    color: #25190b;
    transform: translateX(4px);
}

.logout-link {
    margin-top: 25px;
    border-top: 1px solid rgba(255,255,255,0.15);
    padding-top: 20px;
}

/* =========================
   MAIN
========================= */

.main {
    margin-left: 250px;
    padding: 28px;
    min-height: 100vh;
}

.topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 28px;
}

.topbar h1 {
    color: #3a2814;
    font-size: 30px;
}

.topbar p {
    margin-top: 5px;
    color: #756b5c;
    font-size: 14px;
}

.admin-badge {
    background: white;
    padding: 11px 18px;
    border-radius: 25px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    color: #654914;
    font-weight: bold;
}

/* =========================
   STAT CARDS
========================= */

.stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin-bottom: 25px;
}

.card {
    background: white;
    border-radius: 18px;
    padding: 23px;
    box-shadow: 0 8px 25px rgba(53,39,20,0.08);
    position: relative;
    overflow: hidden;
    transition: 0.3s;
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 30px rgba(53,39,20,0.14);
}

.card::after {
    content: "";
    position: absolute;
    right: -30px;
    bottom: -30px;
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background: rgba(212,175,55,0.08);
}

.card-icon {
    font-size: 30px;
    margin-bottom: 12px;
}

.card-title {
    color: #756b5c;
    font-size: 13px;
}

.card-value {
    font-size: 27px;
    font-weight: bold;
    color: #382612;
    margin-top: 6px;
}

.profit {
    color: #18864b;
}

.revenue {
    color: #b18418;
}

/* =========================
   CONTENT GRID
========================= */

.content-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 22px;
}

.panel {
    background: white;
    border-radius: 18px;
    padding: 22px;
    box-shadow: 0 8px 25px rgba(53,39,20,0.08);
}

.panel h2 {
    color: #382612;
    margin-bottom: 18px;
    font-size: 20px;
}

.panel-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.view-btn {
    text-decoration: none;
    color: #a77b16;
    font-size: 13px;
    font-weight: bold;
}

/* =========================
   LOW STOCK
========================= */

.stock-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 13px 0;
    border-bottom: 1px solid #eee8dc;
}

.stock-item:last-child {
    border-bottom: none;
}

.stock-name {
    font-weight: bold;
    color: #46341e;
}

.stock-id {
    font-size: 12px;
    color: #918878;
    margin-top: 3px;
}

.stock-number {
    padding: 7px 12px;
    border-radius: 20px;
    background: #fff0e8;
    color: #c54b25;
    font-weight: bold;
    font-size: 12px;
}

.no-stock {
    text-align: center;
    padding: 25px;
    color: #5c8b68;
}

/* =========================
   ORDERS TABLE
========================= */

.table-wrapper {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th {
    text-align: left;
    background: #f8f4ea;
    padding: 12px;
    font-size: 12px;
    color: #6d604c;
}

td {
    padding: 13px 12px;
    border-bottom: 1px solid #eee9df;
    font-size: 13px;
}

.order-id {
    color: #a67a15;
    font-weight: bold;
}

.status {
    display: inline-block;
    padding: 6px 9px;
    border-radius: 15px;
    font-size: 11px;
    font-weight: bold;
}

.pending {
    background: #fff3cd;
    color: #946c00;
}

.confirmed {
    background: #e7f1ff;
    color: #1d63a5;
}

.processing {
    background: #efe7ff;
    color: #7041b8;
}

.shipped {
    background: #e1f5ed;
    color: #16724e;
}

.delivered {
    background: #dff5e4;
    color: #16713a;
}

.cancelled {
    background: #ffe5e5;
    color: #b12a2a;
}

/* =========================
   BOTTOM ACTIONS
========================= */

.quick-actions {
    margin-top: 22px;
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 15px;
}

.action-btn {
    text-decoration: none;
    background: white;
    padding: 18px;
    border-radius: 14px;
    text-align: center;
    color: #49351c;
    font-weight: bold;
    box-shadow: 0 5px 18px rgba(0,0,0,0.06);
    transition: 0.3s;
}

.action-btn:hover {
    transform: translateY(-4px);
    background: #fffaf0;
}

.action-icon {
    display: block;
    font-size: 25px;
    margin-bottom: 8px;
}

/* =========================
   RESPONSIVE
========================= */

@media (max-width: 1100px) {

    .stats {
        grid-template-columns: repeat(2, 1fr);
    }

    .content-grid {
        grid-template-columns: 1fr;
    }

    .quick-actions {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 750px) {

    .sidebar {
        position: relative;
        width: 100%;
        height: auto;
    }

    .main {
        margin-left: 0;
        padding: 18px;
    }

    .stats {
        grid-template-columns: 1fr;
    }

    .topbar {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }

    .quick-actions {
        grid-template-columns: 1fr;
    }
}

</style>
</head>

<body>

<!-- =========================
     SIDEBAR
========================= -->

<aside class="sidebar">

    <div class="logo-area">

        <div class="logo-circle">
            🐾
        </div>

        <h2>Royal Paws</h2>

        <p>Admin Control Panel</p>

    </div>

    <ul class="menu">

        <li>
            <a href="dashboard.php" class="active">
                🏠 Dashboard
            </a>
        </li>

        <li>
            <a href="products.php">
                📦 Products
            </a>
        </li>

        <li>
            <a href="add_product.php">
                ➕ Add Product
            </a>
        </li>

        <li>
            <a href="stock.php">
                📊 Stock Management
            </a>
        </li>

        <li>
            <a href="orders.php">
                🛒 Orders
            </a>
        </li>

        <li>
            <a href="feedback.php">
                ⭐ Feedback
            </a>
        </li>

        <li class="logout-link">
            <a href="logout.php">
                🚪 Logout
            </a>
        </li>

    </ul>

</aside>


<!-- =========================
     MAIN
========================= -->

<main class="main">

    <div class="topbar">

        <div>
            <h1>Admin Dashboard</h1>
            <p>Welcome to Royal Paws Store Management</p>
        </div>

        <div class="admin-badge">
            👑 Admin Panel
        </div>

    </div>


    <!-- =========================
         STATISTICS
    ========================= -->

    <section class="stats">

        <div class="card">

            <div class="card-icon">👥</div>

            <div class="card-title">
                Total Users
            </div>

            <div class="card-value">
                <?php echo number_format($totalUsers); ?>
            </div>

        </div>


        <div class="card">

            <div class="card-icon">📦</div>

            <div class="card-title">
                Total Products
            </div>

            <div class="card-value">
                <?php echo number_format($totalProducts); ?>
            </div>

        </div>


        <div class="card">

            <div class="card-icon">🛒</div>

            <div class="card-title">
                Total Orders
            </div>

            <div class="card-value">
                <?php echo number_format($totalOrders); ?>
            </div>

        </div>


        <div class="card">

            <div class="card-icon">💰</div>

            <div class="card-title">
                Total Revenue
            </div>

            <div class="card-value revenue">
                <?php echo money($totalRevenue); ?>
            </div>

        </div>


        <div class="card">

            <div class="card-icon">📊</div>

            <div class="card-title">
                Available Stock
            </div>

            <div class="card-value">
                <?php echo number_format($totalStock); ?>
            </div>

        </div>


        <div class="card">

            <div class="card-icon">💎</div>

            <div class="card-title">
                Total Profit
            </div>

            <div class="card-value profit">
                <?php echo money($totalProfit); ?>
            </div>

        </div>

    </section>


    <!-- =========================
         PANELS
    ========================= -->

    <section class="content-grid">


        <!-- LOW STOCK -->

        <div class="panel">

            <div class="panel-header">

                <h2>⚠️ Low Stock Products</h2>

                <a href="stock.php" class="view-btn">
                    Manage →
                </a>

            </div>

            <?php if ($lowStock->num_rows > 0): ?>

                <?php while ($product = $lowStock->fetch_assoc()): ?>

                    <div class="stock-item">

                        <div>

                            <div class="stock-name">
                                <?php echo e($product['name']); ?>
                            </div>

                            <div class="stock-id">
                                Product ID:
                                #<?php echo (int)$product['id']; ?>
                            </div>

                        </div>

                        <div class="stock-number">
                            <?php echo (int)$product['stock']; ?> left
                        </div>

                    </div>

                <?php endwhile; ?>

            <?php else: ?>

                <div class="no-stock">
                    ✅ All products have sufficient stock.
                </div>

            <?php endif; ?>

        </div>


        <!-- RECENT ORDERS -->

        <div class="panel">

            <div class="panel-header">

                <h2>🛍️ Recent Orders</h2>

                <a href="orders.php" class="view-btn">
                    View All →
                </a>

            </div>

            <div class="table-wrapper">

                <table>

                    <thead>

                        <tr>

                            <th>ID</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Status</th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php if ($recentOrders->num_rows > 0): ?>

                        <?php while ($order = $recentOrders->fetch_assoc()): ?>

                            <tr>

                                <td class="order-id">
                                    #<?php echo (int)$order['id']; ?>
                                </td>

                                <td>
                                    <?php echo e($order['user_name']); ?>
                                </td>

                                <td>
                                    <?php echo money($order['total_amount']); ?>
                                </td>

                                <td>

                                    <span class="status <?php echo statusClass($order['order_status']); ?>">

                                        <?php echo e($order['order_status']); ?>

                                    </span>

                                </td>

                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>

                            <td colspan="4" style="text-align:center;">
                                No orders yet.
                            </td>

                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </section>


    <!-- =========================
         QUICK ACTIONS
    ========================= -->

    <div class="quick-actions">

        <a href="products.php" class="action-btn">

            <span class="action-icon">
                📦
            </span>

            Manage Products

        </a>


        <a href="add_product.php" class="action-btn">

            <span class="action-icon">
                ➕
            </span>

            Add Product

        </a>


        <a href="orders.php" class="action-btn">

            <span class="action-icon">
                🛒
            </span>

            Manage Orders

        </a>


        <a href="feedback.php" class="action-btn">

            <span class="action-icon">
                ⭐
            </span>

            Customer Feedback

        </a>

    </div>

</main>


<script>

/* =========================
   SIMPLE DASHBOARD ANIMATION
========================= */

document.addEventListener("DOMContentLoaded", function () {

    const cards = document.querySelectorAll(".card");

    cards.forEach(function(card, index) {

        card.style.opacity = "0";
        card.style.transform = "translateY(20px)";

        setTimeout(function() {

            card.style.transition = "all 0.5s ease";

            card.style.opacity = "1";
            card.style.transform = "translateY(0)";

        }, index * 100);

    });

});

</script>

</body>
</html>