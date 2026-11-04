<?php
require_once "config/database.php";
require_once "config/functions.php";

requireUser();

$userId = (int)$_SESSION['user_id'];
$message = "";
$messageType = "";

/* Submit feedback */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $productId = isset($_POST['product_id'])
        ? (int)$_POST['product_id']
        : 0;

    $rating = isset($_POST['rating'])
        ? (int)$_POST['rating']
        : 0;

    $comment = trim($_POST['comment'] ?? "");

    /* Validation */

    if ($productId <= 0) {

        $message = "Please select a product.";
        $messageType = "error";

    } elseif ($rating < 1 || $rating > 5) {

        $message = "Please select a rating between 1 and 5.";
        $messageType = "error";

    } elseif ($comment === "") {

        $message = "Please enter your feedback.";
        $messageType = "error";

    } else {

        /*
         * Check whether product exists
         */

        $check = $conn->prepare("
            SELECT id, name
            FROM products
            WHERE id = ?
            LIMIT 1
        ");

        $check->bind_param("i", $productId);
        $check->execute();

        $productResult = $check->get_result();

        if ($productResult->num_rows === 0) {

            $message = "Selected product was not found.";
            $messageType = "error";

        } else {

            /*
             * Insert feedback
             */

            $stmt = $conn->prepare("
                INSERT INTO feedback
                (user_id, product_id, rating, comment)
                VALUES (?, ?, ?, ?)
            ");

            $stmt->bind_param(
                "iiis",
                $userId,
                $productId,
                $rating,
                $comment
            );

            if ($stmt->execute()) {

                $message =
                    "Thank you! Your feedback has been submitted successfully.";

                $messageType = "success";

            } else {

                $message =
                    "Unable to submit feedback. Please try again.";

                $messageType = "error";
            }

            $stmt->close();
        }

        $check->close();
    }
}


/* Get products */

$productQuery = $conn->query("
    SELECT
        id,
        name,
        image,
        selling_price
    FROM products
    WHERE status = 'active'
    ORDER BY name ASC
");


/* Get user's previous feedback */

$feedbackStmt = $conn->prepare("
    SELECT
        f.rating,
        f.comment,
        f.created_at,
        p.name AS product_name
    FROM feedback f
    INNER JOIN products p
        ON f.product_id = p.id
    WHERE f.user_id = ?
    ORDER BY f.created_at DESC
");

$feedbackStmt->bind_param("i", $userId);
$feedbackStmt->execute();

$feedbackResult = $feedbackStmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Feedback | Royal Paws</title>

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
                    rgba(212,175,55,0.15),
                    transparent 30%
                ),
                radial-gradient(
                    circle at bottom right,
                    rgba(212,175,55,0.10),
                    transparent 30%
                ),
                #090909;

            color: #fff;
        }


        /* NAVBAR */

        .navbar {

            position: sticky;
            top: 0;
            z-index: 1000;

            display: flex;
            justify-content: space-between;
            align-items: center;

            padding: 15px 5%;

            background: rgba(10,10,10,0.96);

            border-bottom:
                1px solid rgba(212,175,55,0.25);

            backdrop-filter: blur(12px);
        }


        .logo {

            text-decoration: none;

            color: #d4af37;

            font-size: 25px;

            font-weight: bold;
        }


        .logo span {
            color: #fff;
        }


        .nav-links {

            display: flex;

            align-items: center;

            gap: 20px;
        }


        .nav-links a {

            text-decoration: none;

            color: #ddd;

            transition: 0.3s;
        }


        .nav-links a:hover {
            color: #d4af37;
        }


        .cart-btn {

            padding: 8px 14px;

            border:
                1px solid #d4af37;

            border-radius: 8px;

            color: #d4af37 !important;
        }


        /* CONTAINER */

        .container {

            width: 92%;

            max-width: 1050px;

            margin: auto;

            padding: 45px 0;
        }


        .page-title {

            text-align: center;

            margin-bottom: 35px;
        }


        .page-title h1 {

            color: #d4af37;

            font-size: 38px;

            margin-bottom: 8px;
        }


        .page-title p {

            color: #aaa;

            font-size: 15px;
        }


        /* MAIN GRID */

        .main-grid {

            display: grid;

            grid-template-columns:
                1fr 1fr;

            gap: 25px;
        }


        /* CARD */

        .card {

            background:
                rgba(20,20,20,0.96);

            border:
                1px solid rgba(212,175,55,0.25);

            border-radius: 20px;

            padding: 28px;

            box-shadow:
                0 15px 45px rgba(0,0,0,0.4);
        }


        .card h2 {

            color: #d4af37;

            margin-bottom: 20px;

            font-size: 22px;
        }


        /* MESSAGE */

        .message {

            padding: 13px 15px;

            border-radius: 10px;

            margin-bottom: 20px;

            font-size: 14px;
        }


        .message.success {

            background:
                rgba(46,204,113,0.10);

            border:
                1px solid rgba(46,204,113,0.3);

            color: #66e69a;
        }


        .message.error {

            background:
                rgba(231,76,60,0.10);

            border:
                1px solid rgba(231,76,60,0.3);

            color: #ff8585;
        }


        /* FORM */

        .form-group {

            margin-bottom: 20px;
        }


        .form-group label {

            display: block;

            color: #ddd;

            margin-bottom: 8px;

            font-size: 14px;

            font-weight: 600;
        }


        select,
        textarea {

            width: 100%;

            background: #111;

            color: #fff;

            border:
                1px solid #333;

            border-radius: 10px;

            padding: 13px;

            outline: none;

            transition: 0.3s;
        }


        select:focus,
        textarea:focus {

            border-color: #d4af37;

            box-shadow:
                0 0 12px rgba(212,175,55,0.08);
        }


        textarea {

            min-height: 130px;

            resize: vertical;
        }


        /* STAR RATING */

        .rating-box {

            display: flex;

            flex-direction: row-reverse;

            justify-content: flex-end;

            gap: 5px;

            margin-top: 8px;
        }


        .rating-box input {

            display: none;
        }


        .rating-box label {

            font-size: 38px;

            color: #444;

            cursor: pointer;

            transition: 0.2s;
        }


        .rating-box label:hover,
        .rating-box label:hover ~ label {

            color: #d4af37;

            transform: scale(1.08);
        }


        .rating-box input:checked ~ label {

            color: #d4af37;
        }


        /* BUTTON */

        .submit-btn {

            width: 100%;

            padding: 14px;

            border: none;

            border-radius: 10px;

            background:
                linear-gradient(
                    135deg,
                    #d4af37,
                    #f4d77d
                );

            color: #111;

            font-size: 16px;

            font-weight: bold;

            cursor: pointer;

            transition: 0.3s;
        }


        .submit-btn:hover {

            transform: translateY(-2px);

            box-shadow:
                0 8px 25px rgba(212,175,55,0.25);
        }


        /* PREVIOUS FEEDBACK */

        .feedback-item {

            background: #151515;

            border:
                1px solid #292929;

            border-radius: 13px;

            padding: 17px;

            margin-bottom: 15px;

            transition: 0.3s;
        }


        .feedback-item:hover {

            border-color:
                rgba(212,175,55,0.45);

            transform: translateY(-2px);
        }


        .feedback-top {

            display: flex;

            justify-content: space-between;

            gap: 10px;

            margin-bottom: 8px;
        }


        .product-name {

            color: #d4af37;

            font-weight: bold;
        }


        .date {

            color: #777;

            font-size: 12px;
        }


        .stars {

            color: #d4af37;

            letter-spacing: 2px;

            margin-bottom: 8px;
        }


        .comment {

            color: #ccc;

            line-height: 1.6;

            font-size: 14px;
        }


        .empty-feedback {

            text-align: center;

            padding: 40px 10px;

            color: #777;
        }


        .empty-feedback div {

            font-size: 45px;

            margin-bottom: 10px;
        }


        /* MOBILE */

        @media(max-width: 750px) {

            .nav-links {
                gap: 10px;
            }

            .nav-links a:not(.cart-btn) {
                display: none;
            }

            .main-grid {
                grid-template-columns: 1fr;
            }

            .page-title h1 {
                font-size: 30px;
            }

            .card {
                padding: 22px 18px;
            }

        }

    </style>

</head>


<body>


<!-- NAVBAR -->

<nav class="navbar">

    <a href="index.php" class="logo">
        🐾 <span>Royal</span> Paws
    </a>


    <div class="nav-links">

        <a href="index.php">
            Home
        </a>

        <a href="products.php">
            Products
        </a>

        <a href="orders.php">
            My Orders
        </a>

        <a
            href="cart.php"
            class="cart-btn"
        >
            🛒 Cart
        </a>

    </div>

</nav>


<div class="container">


    <div class="page-title">

        <h1>⭐ Customer Feedback</h1>

        <p>
            Share your experience with Royal Paws
        </p>

    </div>


    <div class="main-grid">


        <!-- FEEDBACK FORM -->

        <div class="card">

            <h2>
                ✍️ Give Your Feedback
            </h2>


            <?php if ($message !== ""): ?>

                <div
                    class="message
                    <?php echo e($messageType); ?>"
                >

                    <?php
                    echo e($message);
                    ?>

                </div>

            <?php endif; ?>


            <form
                method="POST"
                action=""
                onsubmit="return validateFeedback();"
            >


                <!-- PRODUCT -->

                <div class="form-group">

                    <label>
                        Select Product
                    </label>

                    <select
                        name="product_id"
                        id="product_id"
                        required
                    >

                        <option value="">
                            -- Select Product --
                        </option>


                        <?php while (
                            $product =
                            $productQuery->fetch_assoc()
                        ): ?>

                            <option
                                value="<?php
                                    echo e(
                                        $product['id']
                                    );
                                ?>"
                            >

                                <?php
                                echo e(
                                    $product['name']
                                );
                                ?>

                                -
                                <?php
                                echo money(
                                    $product[
                                        'selling_price'
                                    ]
                                );
                                ?>

                            </option>

                        <?php endwhile; ?>

                    </select>

                </div>


                <!-- RATING -->

                <div class="form-group">

                    <label>
                        Your Rating
                    </label>


                    <div class="rating-box">

                        <input
                            type="radio"
                            id="star5"
                            name="rating"
                            value="5"
                        >

                        <label for="star5">
                            ★
                        </label>


                        <input
                            type="radio"
                            id="star4"
                            name="rating"
                            value="4"
                        >

                        <label for="star4">
                            ★
                        </label>


                        <input
                            type="radio"
                            id="star3"
                            name="rating"
                            value="3"
                        >

                        <label for="star3">
                            ★
                        </label>


                        <input
                            type="radio"
                            id="star2"
                            name="rating"
                            value="2"
                        >

                        <label for="star2">
                            ★
                        </label>


                        <input
                            type="radio"
                            id="star1"
                            name="rating"
                            value="1"
                        >

                        <label for="star1">
                            ★
                        </label>

                    </div>

                </div>


                <!-- COMMENT -->

                <div class="form-group">

                    <label>
                        Your Comment
                    </label>

                    <textarea
                        name="comment"
                        id="comment"
                        maxlength="500"
                        placeholder="Tell us about your experience..."
                        required
                    ></textarea>

                    <small
                        id="charCount"
                        style="
                        color:#777;
                        display:block;
                        margin-top:6px;
                        "
                    >
                        0 / 500
                    </small>

                </div>


                <button
                    type="submit"
                    class="submit-btn"
                >
                    ⭐ Submit Feedback
                </button>


            </form>

        </div>


        <!-- PREVIOUS FEEDBACK -->

        <div class="card">

            <h2>
                💬 My Previous Feedback
            </h2>


            <?php if (
                $feedbackResult->num_rows > 0
            ): ?>


                <?php while (
                    $feedback =
                    $feedbackResult->fetch_assoc()
                ): ?>


                    <div class="feedback-item">


                        <div class="feedback-top">

                            <div class="product-name">

                                <?php
                                echo e(
                                    $feedback[
                                        'product_name'
                                    ]
                                );
                                ?>

                            </div>


                            <div class="date">

                                <?php
                                echo date(
                                    "d M Y",
                                    strtotime(
                                        $feedback[
                                            'created_at'
                                        ]
                                    )
                                );
                                ?>

                            </div>

                        </div>


                        <div class="stars">

                            <?php

                            $rating =
                                (int)$feedback['rating'];

                            echo str_repeat(
                                "★",
                                $rating
                            );

                            echo str_repeat(
                                "☆",
                                5 - $rating
                            );

                            ?>

                        </div>


                        <div class="comment">

                            <?php
                            echo nl2br(
                                e(
                                    $feedback[
                                        'comment'
                                    ]
                                )
                            );
                            ?>

                        </div>


                    </div>


                <?php endwhile; ?>


            <?php else: ?>


                <div class="empty-feedback">

                    <div>
                        ⭐
                    </div>

                    <p>
                        You haven't submitted
                        any feedback yet.
                    </p>

                </div>


            <?php endif; ?>


        </div>


    </div>

</div>


<script>

    /* Character Counter */

    const comment =
        document.getElementById("comment");

    const charCount =
        document.getElementById("charCount");


    if (comment) {

        comment.addEventListener(
            "input",
            function() {

                charCount.textContent =
                    this.value.length +
                    " / 500";

            }
        );

    }


    /* Form Validation */

    function validateFeedback() {

        const product =
            document.getElementById(
                "product_id"
            ).value;

        const rating =
            document.querySelector(
                'input[name="rating"]:checked'
            );

        const commentValue =
            document.getElementById(
                "comment"
            ).value.trim();


        if (product === "") {

            alert(
                "Please select a product."
            );

            return false;
        }


        if (!rating) {

            alert(
                "Please select a star rating."
            );

            return false;
        }


        if (commentValue === "") {

            alert(
                "Please enter your feedback."
            );

            return false;
        }


        return true;
    }

</script>


</body>

</html>