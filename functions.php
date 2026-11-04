<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* Escape HTML */
function e($value)
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}

/* User Login Check */
function isUserLoggedIn()
{
    return isset($_SESSION['user_id']);
}

/* Admin Login Check */
function isAdminLoggedIn()
{
    return isset($_SESSION['admin_id']);
}

/* Require User Login */
function requireUser()
{
    if (!isUserLoggedIn()) {
        header("Location: login.php");
        exit;
    }
}

/* Require Admin Login */
function requireAdmin()
{
    if (!isAdminLoggedIn()) {
        header("Location: login.php");
        exit;
    }
}

/* Redirect */
function redirect($url)
{
    header("Location: " . $url);
    exit;
}

/* Cart Count */
function cartCount($conn)
{
    if (!isUserLoggedIn()) {
        return 0;
    }

    $userId = (int)$_SESSION['user_id'];

    $stmt = $conn->prepare(
        "SELECT COALESCE(SUM(quantity), 0) AS total
         FROM cart
         WHERE user_id = ?"
    );

    if (!$stmt) {
        return 0;
    }

    $stmt->bind_param("i", $userId);
    $stmt->execute();

    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    return (int)$row['total'];
}

/* Money Format */
function money($amount)
{
    return "₹" . number_format(
        (float)$amount,
        2
    );
}

/* Product Image */
function productImage($image)
{
    if (empty($image)) {
        return "assets/images/logo.png";
    }

    return "assets/images/" . $image;
}

/* Flash Message */
function setMessage(
    $message,
    $type = "success"
) {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

/* Show Flash Message */
function showMessage()
{
    if (isset($_SESSION['flash_message'])) {

        $message = $_SESSION['flash_message'];

        $type = $_SESSION['flash_type']
            ?? "success";

        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);

        echo '<div class="flash-message '
            . e($type)
            . '">'
            . e($message)
            . '</div>';
    }
}

?>