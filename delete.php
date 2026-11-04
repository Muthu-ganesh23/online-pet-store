<?php
require_once "../config/database.php";
require_once "../config/functions.php";

requireAdmin();

/* --------------------------------
   CHECK PRODUCT ID
--------------------------------- */
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    redirect("products.php");
}

$productId = isset($_POST["id"]) ? (int)$_POST["id"] : 0;

if ($productId <= 0) {
    setMessage("Invalid product ID.", "error");
    redirect("products.php");
}

/* --------------------------------
   GET PRODUCT
--------------------------------- */
$stmt = $conn->prepare("
    SELECT id, name, image
    FROM products
    WHERE id = ?
    LIMIT 1
");

$stmt->bind_param("i", $productId);
$stmt->execute();

$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product) {
    setMessage("Product not found.", "error");
    redirect("products.php");
}

/* --------------------------------
   CHECK ORDER HISTORY
--------------------------------- */
$orderCheck = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM order_items
    WHERE product_id = ?
");

$orderCheck->bind_param("i", $productId);
$orderCheck->execute();

$orderResult = $orderCheck->get_result();
$orderData = $orderResult->fetch_assoc();

$orderCount = (int)$orderData["total"];

/*
   Product has previous orders.
   Do NOT physically delete it because
   historical order/profit information
   should remain available.
*/
if ($orderCount > 0) {
    setMessage(
        "This product has order history. It cannot be deleted. Please set the product status to Inactive instead.",
        "error"
    );

    redirect("products.php");
}

/* --------------------------------
   DELETE PRODUCT
--------------------------------- */
$deleteStmt = $conn->prepare("
    DELETE FROM products
    WHERE id = ?
");

$deleteStmt->bind_param("i", $productId);

if ($deleteStmt->execute()) {

    /* ------------------------------
       DELETE IMAGE FILE
    ------------------------------ */
    if (!empty($product["image"])) {

        $imagePath = "../assets/images/" . $product["image"];

        if (file_exists($imagePath)) {
            unlink($imagePath);
        }
    }

    setMessage(
        "Product deleted successfully.",
        "success"
    );

} else {

    setMessage(
        "Product could not be deleted.",
        "error"
    );
}

redirect("products.php");
?>