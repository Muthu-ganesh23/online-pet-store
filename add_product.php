<?php
require_once "../config/database.php";
require_once "../config/functions.php";

requireAdmin();

$error = "";
$success = "";

/* =========================
   FETCH CATEGORIES
========================= */

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

/* =========================
   ADD PRODUCT
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $categoryId   = (int)($_POST["category_id"] ?? 0);
    $name         = trim($_POST["name"] ?? "");
    $description  = trim($_POST["description"] ?? "");
    $costPrice    = (float)($_POST["cost_price"] ?? 0);
    $sellingPrice = (float)($_POST["selling_price"] ?? 0);
    $stock        = (int)($_POST["stock"] ?? 0);
    $status       = $_POST["status"] ?? "active";

    /* =========================
       VALIDATION
    ========================= */

    if ($categoryId <= 0) {

        $error = "Please select a category.";

    } elseif ($name === "") {

        $error = "Please enter product name.";

    } elseif ($costPrice < 0) {

        $error = "Cost price cannot be negative.";

    } elseif ($sellingPrice < 0) {

        $error = "Selling price cannot be negative.";

    } elseif ($sellingPrice < $costPrice) {

        $error = "Selling price should not be lower than cost price.";

    } elseif ($stock < 0) {

        $error = "Stock cannot be negative.";

    } elseif (
        $status !== "active" &&
        $status !== "inactive"
    ) {

        $error = "Invalid product status.";

    }


    /* =========================
       CHECK CATEGORY
    ========================= */

    if ($error === "") {

        $checkCategory = $conn->prepare("
            SELECT id
            FROM categories
            WHERE id = ?
        ");

        if (!$checkCategory) {

            $error = "Category query failed.";

        } else {

            $checkCategory->bind_param(
                "i",
                $categoryId
            );

            $checkCategory->execute();

            $result = $checkCategory->get_result();

            if ($result->num_rows === 0) {
                $error = "Selected category does not exist.";
            }

            $checkCategory->close();
        }
    }


    /* =========================
       IMAGE UPLOAD
    ========================= */

    $imageName = "";

    if (
        $error === "" &&
        isset($_FILES["image"]) &&
        $_FILES["image"]["error"] !== UPLOAD_ERR_NO_FILE
    ) {

        if ($_FILES["image"]["error"] !== UPLOAD_ERR_OK) {

            $error = "Image upload failed.";

        } else {

            $originalName = $_FILES["image"]["name"];
            $tmpName      = $_FILES["image"]["tmp_name"];
            $fileSize     = $_FILES["image"]["size"];

            $extension = strtolower(
                pathinfo(
                    $originalName,
                    PATHINFO_EXTENSION
                )
            );

            $allowedExtensions = [
                "jpg",
                "jpeg",
                "png",
                "webp"
            ];

            if (
                !in_array(
                    $extension,
                    $allowedExtensions,
                    true
                )
            ) {

                $error =
                    "Only JPG, JPEG, PNG and WEBP images are allowed.";

            } elseif ($fileSize > 5 * 1024 * 1024) {

                $error =
                    "Image size must be less than 5 MB.";

            } else {

                $uploadDirectory = "../assets/images/";

                if (!is_dir($uploadDirectory)) {

                    if (!mkdir(
                        $uploadDirectory,
                        0777,
                        true
                    )) {

                        $error =
                            "Unable to create images folder.";
                    }
                }

                if ($error === "") {

                    $imageName =
                        time() .
                        "_" .
                        bin2hex(random_bytes(5)) .
                        "." .
                        $extension;

                    $uploadPath =
                        $uploadDirectory .
                        $imageName;

                    if (
                        !move_uploaded_file(
                            $tmpName,
                            $uploadPath
                        )
                    ) {

                        $error =
                            "Unable to save uploaded image.";

                        $imageName = "";
                    }
                }
            }
        }
    }


    /* =========================
       INSERT PRODUCT
    ========================= */

    if ($error === "") {

        $sql = "
            INSERT INTO products
            (
                category_id,
                name,
                description,
                cost_price,
                selling_price,
                stock,
                image,
                status
            )
            VALUES
            (?, ?, ?, ?, ?, ?, ?, ?)
        ";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {

            $error =
                "Database error: " .
                $conn->error;

        } else {

            $stmt->bind_param(
                "issddiss",
                $categoryId,
                $name,
                $description,
                $costPrice,
                $sellingPrice,
                $stock,
                $imageName,
                $status
            );

            if ($stmt->execute()) {

                $stmt->close();

                setMessage(
                    "Product added successfully!",
                    "success"
                );

                header("Location: products.php");
                exit;

            } else {

                $error =
                    "Product could not be added: " .
                    $stmt->error;

                $stmt->close();

                /* Delete uploaded image if DB insert failed */

                if (
                    $imageName !== "" &&
                    file_exists(
                        "../assets/images/" .
                        $imageName
                    )
                ) {

                    unlink(
                        "../assets/images/" .
                        $imageName
                    );
                }
            }
        }
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

<title>Add Product | Royal Paws</title>

<style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: Arial, Helvetica, sans-serif;
}

body {
    background: #f4f1ea;
    color: #302617;
}

/* SIDEBAR */

.sidebar {
    position: fixed;
    left: 0;
    top: 0;
    width: 250px;
    height: 100vh;

    background:
        linear-gradient(
            180deg,
            #24180c,
            #3d2812,
            #1d140b
        );

    color: white;
    padding: 25px 18px;

    box-shadow:
        5px 0 20px rgba(0,0,0,0.18);

    z-index: 1000;
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

    background:
        linear-gradient(
            135deg,
            #d4af37,
            #f6df8b
        );

    display: flex;
    align-items: center;
    justify-content: center;

    color: #24180c;
    font-size: 30px;

    box-shadow:
        0 0 20px
        rgba(212,175,55,0.45);
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

/* MENU */

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

    background:
        linear-gradient(
            90deg,
            #c69a2d,
            #e6c45c
        );

    color: #25190b;

    transform: translateX(4px);
}

.logout-link {

    margin-top: 25px;

    border-top:
        1px solid
        rgba(255,255,255,0.15);

    padding-top: 20px;
}

/* MAIN */

.main {
    margin-left: 250px;
    padding: 28px;
    min-height: 100vh;
}

/* HEADER */

.topbar {

    display: flex;

    justify-content:
        space-between;

    align-items: center;

    margin-bottom: 25px;
}

.topbar h1 {
    color: #3a2814;
    font-size: 30px;
}

.topbar p {
    color: #756b5c;
    font-size: 14px;
    margin-top: 5px;
}

.admin-badge {

    background: white;

    padding: 11px 18px;

    border-radius: 25px;

    box-shadow:
        0 5px 15px
        rgba(0,0,0,0.08);

    color: #654914;

    font-weight: bold;
}

/* FORM */

.form-container {

    max-width: 1000px;

    margin: auto;

    background: white;

    border-radius: 20px;

    padding: 30px;

    box-shadow:
        0 10px 30px
        rgba(53,39,20,0.09);
}

.form-header {

    border-bottom:
        1px solid #eee6d8;

    padding-bottom: 20px;

    margin-bottom: 25px;
}

.form-header h2 {
    color: #382612;
    font-size: 22px;
}

.form-header p {
    color: #817667;
    font-size: 13px;
    margin-top: 6px;
}

/* ERROR */

.error-message {

    background: #ffe8e8;

    color: #a62929;

    border:
        1px solid #efbebe;

    padding: 14px 18px;

    border-radius: 10px;

    margin-bottom: 20px;

    font-size: 14px;

    font-weight: bold;
}

/* GRID */

.form-grid {

    display: grid;

    grid-template-columns:
        repeat(2, 1fr);

    gap: 20px;
}

.form-group {

    display: flex;

    flex-direction: column;
}

.full-width {
    grid-column: 1 / -1;
}

/* LABEL */

label {

    margin-bottom: 7px;

    font-size: 13px;

    font-weight: bold;

    color: #4b3921;
}

.required {
    color: #b73535;
}

/* INPUT */

input,
select,
textarea {

    width: 100%;

    border:
        1px solid #ddd4c4;

    border-radius: 10px;

    padding: 12px 14px;

    font-size: 14px;

    outline: none;

    background: #fffdf9;

    transition: 0.25s;
}

input:focus,
select:focus,
textarea:focus {

    border-color: #c69a2d;

    box-shadow:
        0 0 0 3px
        rgba(198,154,45,0.12);
}

textarea {

    min-height: 130px;

    resize: vertical;
}

/* IMAGE */

.image-upload-box {

    border:
        2px dashed #d7c49a;

    border-radius: 14px;

    padding: 25px;

    text-align: center;

    background: #fffaf0;

    transition: 0.3s;
}

.image-upload-box:hover {

    border-color: #c69a2d;

    background: #fffdf7;
}

.upload-icon {

    font-size: 40px;

    margin-bottom: 8px;
}

.image-upload-box p {

    color: #817667;

    font-size: 12px;

    margin-bottom: 12px;
}

.file-input {

    background: white;

    padding: 9px;
}

/* PREVIEW */

.preview-area {

    display: none;

    margin-top: 15px;
}

.preview-image {

    width: 150px;

    height: 150px;

    object-fit: cover;

    border-radius: 14px;

    border:
        2px solid #d4af37;

    box-shadow:
        0 5px 20px
        rgba(0,0,0,0.12);
}

/* INFO */

.price-info {

    margin-top: 7px;

    font-size: 11px;

    color: #8a7b67;
}

/* PROFIT */

.profit-preview {

    margin-top: 15px;

    padding: 14px;

    background: #eef8f0;

    border-radius: 10px;

    color: #19713a;

    font-size: 13px;

    font-weight: bold;

    display: none;
}

/* BUTTONS */

.form-actions {

    margin-top: 30px;

    padding-top: 22px;

    border-top:
        1px solid #eee6d8;

    display: flex;

    justify-content: flex-end;

    gap: 12px;
}

.cancel-btn {

    text-decoration: none;

    padding: 12px 22px;

    border-radius: 10px;

    background: #eee9df;

    color: #5f5344;

    font-weight: bold;

    transition: 0.3s;
}

.cancel-btn:hover {
    background: #e1dbce;
}

.submit-btn {

    border: none;

    padding: 12px 25px;

    border-radius: 10px;

    background:
        linear-gradient(
            135deg,
            #b8891d,
            #e1bf55
        );

    color: #261a0b;

    font-weight: bold;

    cursor: pointer;

    transition: 0.3s;
}

.submit-btn:hover {

    transform:
        translateY(-2px);

    box-shadow:
        0 7px 18px
        rgba(155,113,20,0.25);
}

/* MOBILE */

@media (max-width: 800px) {

    .sidebar {

        position: relative;

        width: 100%;

        height: auto;
    }

    .main {

        margin-left: 0;

        padding: 18px;
    }

    .topbar {

        flex-direction: column;

        align-items: flex-start;

        gap: 15px;
    }

    .form-grid {

        grid-template-columns: 1fr;
    }

    .full-width {
        grid-column: auto;
    }

    .form-actions {

        flex-direction: column;
    }

    .cancel-btn,
    .submit-btn {

        text-align: center;

        width: 100%;
    }
}

</style>

</head>

<body>

<!-- SIDEBAR -->

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
            <a href="dashboard.php">
                🏠 Dashboard
            </a>
        </li>

        <li>
            <a href="products.php">
                📦 Products
            </a>
        </li>

        <li>
            <a
                href="add_product.php"
                class="active"
            >
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


<!-- MAIN -->

<main class="main">

    <div class="topbar">

        <div>

            <h1>Add New Product</h1>

            <p>
                Add a new pet product to your Royal Paws store.
            </p>

        </div>

        <div class="admin-badge">
            👑 Admin Panel
        </div>

    </div>


    <div class="form-container">

        <div class="form-header">

            <h2>
                📦 Product Information
            </h2>

            <p>
                Fill in all the required details below.
            </p>

        </div>


        <?php if ($error !== ""): ?>

            <div class="error-message">

                ⚠️
                <?php echo e($error); ?>

            </div>

        <?php endif; ?>


        <form
            method="POST"
            enctype="multipart/form-data"
            id="productForm"
        >

            <div class="form-grid">


                <!-- CATEGORY -->

                <div class="form-group">

                    <label>
                        Category
                        <span class="required">*</span>
                    </label>

                    <select
                        name="category_id"
                        required
                    >

                        <option value="">
                            Select Category
                        </option>

                        <?php foreach ($categories as $category): ?>

                            <option
                                value="<?php
                                    echo (int)$category["id"];
                                ?>"
                                <?php
                                if (
                                    (int)($_POST["category_id"] ?? 0)
                                    ===
                                    (int)$category["id"]
                                ) {
                                    echo "selected";
                                }
                                ?>
                            >

                                <?php
                                echo e(
                                    $category["name"]
                                );
                                ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- NAME -->

                <div class="form-group">

                    <label>
                        Product Name
                        <span class="required">*</span>
                    </label>

                    <input
                        type="text"
                        name="name"
                        maxlength="200"
                        placeholder="Example: Premium Dog Food"
                        value="<?php
                            echo e(
                                $_POST["name"] ?? ""
                            );
                        ?>"
                        required
                    >

                </div>


                <!-- COST -->

                <div class="form-group">

                    <label>
                        Cost Price
                        <span class="required">*</span>
                    </label>

                    <input
                        type="number"
                        name="cost_price"
                        id="costPrice"
                        min="0"
                        step="0.01"
                        placeholder="₹0.00"
                        value="<?php
                            echo e(
                                $_POST["cost_price"] ?? ""
                            );
                        ?>"
                        required
                    >

                    <div class="price-info">
                        Product purchase/cost price.
                    </div>

                </div>


                <!-- SELLING -->

                <div class="form-group">

                    <label>
                        Selling Price
                        <span class="required">*</span>
                    </label>

                    <input
                        type="number"
                        name="selling_price"
                        id="sellingPrice"
                        min="0"
                        step="0.01"
                        placeholder="₹0.00"
                        value="<?php
                            echo e(
                                $_POST["selling_price"] ?? ""
                            );
                        ?>"
                        required
                    >

                    <div class="price-info">
                        Customer selling price.
                    </div>

                </div>


                <!-- STOCK -->

                <div class="form-group">

                    <label>
                        Stock Quantity
                        <span class="required">*</span>
                    </label>

                    <input
                        type="number"
                        name="stock"
                        min="0"
                        step="1"
                        placeholder="Example: 25"
                        value="<?php
                            echo e(
                                $_POST["stock"] ?? ""
                            );
                        ?>"
                        required
                    >

                </div>


                <!-- STATUS -->

                <div class="form-group">

                    <label>
                        Product Status
                    </label>

                    <select name="status">

                        <option
                            value="active"
                            <?php
                            echo (
                                ($_POST["status"] ?? "active")
                                === "active"
                            )
                            ? "selected"
                            : "";
                            ?>
                        >
                            Active
                        </option>

                        <option
                            value="inactive"
                            <?php
                            echo (
                                ($_POST["status"] ?? "")
                                === "inactive"
                            )
                            ? "selected"
                            : "";
                            ?>
                        >
                            Inactive
                        </option>

                    </select>

                </div>


                <!-- DESCRIPTION -->

                <div class="form-group full-width">

                    <label>
                        Product Description
                    </label>

                    <textarea
                        name="description"
                        maxlength="2000"
                        placeholder="Enter product description..."
                    ><?php
                        echo e(
                            $_POST["description"] ?? ""
                        );
                    ?></textarea>

                </div>


                <!-- IMAGE -->

                <div class="form-group full-width">

                    <label>
                        Product Image
                    </label>

                    <div class="image-upload-box">

                        <div class="upload-icon">
                            🖼️
                        </div>

                        <p>
                            JPG, JPEG, PNG or WEBP
                            <br>
                            Maximum size: 5 MB
                        </p>

                        <input
                            type="file"
                            name="image"
                            id="imageInput"
                            class="file-input"
                            accept=".jpg,.jpeg,.png,.webp"
                        >

                        <div
                            class="preview-area"
                            id="previewArea"
                        >

                            <img
                                id="previewImage"
                                class="preview-image"
                                src=""
                                alt="Image Preview"
                            >

                        </div>

                    </div>

                </div>

            </div>


            <!-- PROFIT -->

            <div
                class="profit-preview"
                id="profitPreview"
            >

                💎 Estimated Profit:

                <span id="profitAmount">
                    ₹0.00
                </span>

            </div>


            <!-- ACTIONS -->

            <div class="form-actions">

                <a
                    href="products.php"
                    class="cancel-btn"
                >
                    ← Cancel
                </a>

                <button
                    type="submit"
                    class="submit-btn"
                >
                    ➕ Add Product
                </button>

            </div>

        </form>

    </div>

</main>


<script>

/* =========================
   IMAGE PREVIEW
========================= */

const imageInput =
    document.getElementById("imageInput");

const previewArea =
    document.getElementById("previewArea");

const previewImage =
    document.getElementById("previewImage");


imageInput.addEventListener(
    "change",
    function () {

        const file = this.files[0];

        if (!file) {

            previewArea.style.display = "none";

            return;
        }

        const allowedTypes = [
            "image/jpeg",
            "image/png",
            "image/webp"
        ];

        if (
            !allowedTypes.includes(file.type)
        ) {

            alert(
                "Please select JPG, JPEG or WEBP image."
            );

            this.value = "";

            previewArea.style.display = "none";

            return;
        }

        if (
            file.size >
            5 * 1024 * 1024
        ) {

            alert(
                "Image size must be less than 5 MB."
            );

            this.value = "";

            previewArea.style.display = "none";

            return;
        }

        const reader =
            new FileReader();

        reader.onload =
            function (event) {

                previewImage.src =
                    event.target.result;

                previewArea.style.display =
                    "block";
            };

        reader.readAsDataURL(file);
    }
);


/* =========================
   PROFIT
========================= */

const costPrice =
    document.getElementById("costPrice");

const sellingPrice =
    document.getElementById("sellingPrice");

const profitPreview =
    document.getElementById("profitPreview");

const profitAmount =
    document.getElementById("profitAmount");


function calculateProfit() {

    const cost =
        parseFloat(costPrice.value) || 0;

    const selling =
        parseFloat(sellingPrice.value) || 0;

    if (
        cost > 0 ||
        selling > 0
    ) {

        const profit =
            selling - cost;

        profitPreview.style.display =
            "block";

        profitAmount.textContent =
            "₹" + profit.toFixed(2);

        if (profit < 0) {

            profitPreview.style.background =
                "#ffe8e8";

            profitPreview.style.color =
                "#a62929";

        } else {

            profitPreview.style.background =
                "#eef8f0";

            profitPreview.style.color =
                "#19713a";
        }

    } else {

        profitPreview.style.display =
            "none";
    }
}


costPrice.addEventListener(
    "input",
    calculateProfit
);

sellingPrice.addEventListener(
    "input",
    calculateProfit
);


/* =========================
   FORM VALIDATION
========================= */

document
    .getElementById("productForm")
    .addEventListener(
        "submit",
        function (event) {

            const cost =
                parseFloat(
                    costPrice.value
                ) || 0;

            const selling =
                parseFloat(
                    sellingPrice.value
                ) || 0;

            if (selling < cost) {

                event.preventDefault();

                alert(
                    "Selling price cannot be lower than cost price."
                );

                sellingPrice.focus();
            }
        }
    );


/* =========================
   PAGE ANIMATION
========================= */

document.addEventListener(
    "DOMContentLoaded",
    function () {

        const form =
            document.querySelector(
                ".form-container"
            );

        if (!form) return;

        form.style.opacity = "0";

        form.style.transform =
            "translateY(20px)";

        setTimeout(
            function () {

                form.style.transition =
                    "all 0.5s ease";

                form.style.opacity =
                    "1";

                form.style.transform =
                    "translateY(0)";

            },
            100
        );
    }
);

</script>

</body>
</html>