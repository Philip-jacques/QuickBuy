<?php
/**
 * @file EditItem.php
 * @brief This page allows a logged-in seller to edit the details of an existing product,
 * including its name, description, price, quantity, and picture.
 *
 * It handles both GET requests (to display the item's current details)
 * and POST requests (to process updates).
 *
 * @uses session_start() To initiate the session and access user login status.
 * @uses include 'db.php' To establish a database connection.
 */

session_start(); // Start the PHP session.
include 'db.php'; // Include the database connection file.

// --- Authentication Check ---
/**
 * @brief Redirects the user to the login page if they are not logged in.
 */
if (!isset($_SESSION['user_id'])) {
    header("Location: LoginPage.php"); // Redirect to the login page.
    exit(); // Terminate script execution to prevent further processing.
}

/**
 * @var int $seller_id The ID of the currently logged-in seller, retrieved from the session.
 */
$seller_id = $_SESSION['user_id'];
/**
 * @var array|null $product Stores product details fetched from the database. Initialized to null.
 */
$product = null;
/**
 * @var string $message Stores feedback messages for the user (success or error).
 * Initialized as an empty string.
 */
$message = '';

// --- Handle GET Request (Display Product Details for Editing) ---
/**
 * @brief Checks if an 'id' (product ID) is provided in the URL query string.
 * If present, it attempts to fetch the corresponding product details from the database.
 */
if (isset($_GET['id'])) {
    /**
     * @var int $product_id The ID of the product to be edited, from the GET request.
     */
    $product_id = $_GET['id'];

    /**
     * @brief SQL query to select product details, ensuring the product belongs to the logged-in seller.
     * @var string $sql
     */
    $sql = "SELECT * FROM products WHERE id = ? AND seller_id = ?";
    /**
     * @var mysqli_stmt|false $stmt Prepared statement for fetching product details.
     */
    $stmt = $conn->prepare($sql);

    /**
     * @brief Error handling for the prepared statement creation.
     */
    if (!$stmt) {
        $message = "error: Prepare failed: " . $conn->error; // Store database error.
    } else {
        $stmt->bind_param("ii", $product_id, $seller_id); // Bind product ID and seller ID parameters.
        $stmt->execute(); // Execute the statement.
        /**
         * @var mysqli_result $result Result set from the product details query.
         */
        $result = $stmt->get_result();
        /**
         * @var array|null $product Fetched product row as an associative array, or null if not found.
         */
        $product = $result->fetch_assoc();

        /**
         * @brief Checks if the product was found and belongs to the seller.
         * If not, sets an error message.
         */
        if (!$product) {
            $message = "error:Product not found or not authorized.";
            // In a production environment, you might want a stronger redirect here,
            // e.g., header("Location: viewCurrentItems.php"); exit();
        }
        $stmt->close(); // Close the statement.
    }
} else {
    $message = "error:No product selected."; // Message if no product ID is provided.
    // In a production environment, you might want a stronger redirect here.
    // header("Location: viewCurrentItems.php"); exit();
}

// --- Handle POST Request (Update Product Details) ---
/**
 * @brief Processes the form submission when the user clicks "Update Item".
 * This block only executes for POST requests that include a 'product_id'.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    // Re-assign product_id from POST data, as this is an update submission.
    $product_id = $_POST['id'];

    // Sanitize and retrieve input values from the POST request.
    $name = $_POST['itemName'];
    $desc = $_POST['itemDescription'];
    $price = $_POST['price'];
    $qty = $_POST['quantity'];

    // Get current picture path from database BEFORE updating to handle image deletion/replacement.
    $sql_current_pic = "SELECT item_picture FROM products WHERE id = ?";
    $stmt_current_pic = $conn->prepare($sql_current_pic);
    $stmt_current_pic->bind_param("i", $product_id);
    $stmt_current_pic->execute();
    $result_current_pic = $stmt_current_pic->get_result();
    $current_picture_row = $result_current_pic->fetch_assoc();
    /**
     * @var string|null $current_picture Path to the product's current image.
     */
    $current_picture = $current_picture_row ? $current_picture_row['item_picture'] : null;
    $stmt_current_pic->close();

    /**
     * @var string $imageUploadDir Directory where product images are stored.
     */
    $imageUploadDir = 'images/'; // Your specified images directory.
    /**
     * @var string|null $itemPicturePath The path to the new (or existing) product image.
     * Initially set to the current picture path.
     */
    $itemPicturePath = $current_picture; // Default to existing picture path.

    // Check if a new image file was uploaded.
    if (isset($_FILES['item_picture']) && $_FILES['item_picture']['error'] == UPLOAD_ERR_OK) {
        // Ensure the images directory exists.
        if (!is_dir($imageUploadDir)) {
            mkdir($imageUploadDir, 0777, true); // Create directory with full permissions if it doesn't exist.
        }

        $fileName = basename($_FILES['item_picture']['name']);
        // Use a unique ID to prevent filename conflicts.
        $targetFilePath = $imageUploadDir . uniqid() . '_' . $fileName;
        $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);

        // Allow certain file formats for security and consistency.
        $allowTypes = array('jpg', 'png', 'jpeg', 'gif');
        if (in_array(strtolower($fileType), $allowTypes)) {            

            // Upload file to server.
            if (move_uploaded_file($_FILES['item_picture']['tmp_name'], $targetFilePath)) {
                $itemPicturePath = $targetFilePath; // Update path to the new image.

                // Optionally, delete the old image file if it exists and is not a default placeholder.
                if (!empty($current_picture) && file_exists($current_picture)) {
                    
                    if (strpos($current_picture, 'default_') === false) {
                        unlink($current_picture); // Delete the old image file.
                    }
                }
                $message = "success:Image uploaded successfully!"; // Set success message for image upload.
            } else {
                $message = "error:Failed to upload new image."; // Error if image upload fails.
            }
        } else {
            $message = "error:Sorry, only JPG, JPEG, PNG, & GIF files are allowed to upload."; // Error for invalid file type.
        }
    } elseif (isset($_POST['remove_picture']) && $_POST['remove_picture'] == 'yes') {
        // Option to remove the existing picture.
        if (!empty($current_picture) && file_exists($current_picture)) {
            // Ensure not to delete default system images.
            if (strpos($current_picture, 'default_') === false) {
                unlink($current_picture); // Delete the current picture file.
            }
        }
        $itemPicturePath = NULL; // Set to NULL in the database to clear the image.
        $message = "success:Picture removed successfully!"; // Set success message for picture removal.
    }

    // Update product details in the database.
    // Prepare the SQL statement for updating product details, including the image path.
    $update_sql = "UPDATE products SET itemName = ?, itemDescription = ?, price = ?, quantity = ?, item_picture = ? WHERE id = ? AND seller_id = ?";
    /**
     * @var mysqli_stmt|false $update_stmt Prepared statement for updating product details.
     */
    $update_stmt = $conn->prepare($update_sql);

    /**
     * @brief Error handling for the update statement preparation.
     */
    if (!$update_stmt) {
        $message = "error: Prepare failed: " . $conn->error; // Store database error.
    } else {
        // Bind parameters: string, string, double, string, string, int, int
        $update_stmt->bind_param("ssdssii", $name, $desc, $price, $qty, $itemPicturePath, $product_id, $seller_id);

        /**
         * @brief Executes the update statement and sets appropriate success or error messages.
         */
        if ($update_stmt->execute()) {
            // Only set success message for general update if no image message was already set.
            if (strpos($message, 'success') === false) {
                $message = "success:Item details updated successfully!";
            }
        } else {
            $message = "error:Failed to update product details: " . $conn->error; // Error if update fails.
        }
        $update_stmt->close(); // Close the update statement.
    }

    // Re-fetch product data to display the updated information on the form.
    if ($product_id) {
        $sql = "SELECT * FROM products WHERE id = ? AND seller_id = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("ii", $product_id, $seller_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $product = $result->fetch_assoc();
            $stmt->close();
        }
    }
    // IMPORTANT: Exit after echoing the message for AJAX requests.
    // This script is likely intended to be called via AJAX for updates.
    echo $message;
    exit();
}
// Close connection only if no POST request is being processed,
// as AJAX requests will exit earlier.
$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Item</title>
    <style>
        /*
         * @brief CSS styles for the Edit Item page.
         *
         * Provides basic styling for the form elements, message displays,
         * and image preview to ensure a clean and functional user interface.
         */
        body {
            font-family: Arial, sans-serif;
            background-color: #eef2f3;
            padding: 20px;
        }

        .container {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            max-width: 500px;
            margin: auto;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        h2 {
            text-align: center;
            color: #333;
        }

        label {
            display: block;
            margin-top: 10px;
            font-weight: bold;
        }

        input[type="text"],
        input[type="number"],
        textarea,
        input[type="file"] {
            width: 100%;
            padding: 8px;
            margin-top: 4px;
            border-radius: 5px;
            border: 1px solid #ccc;
            box-sizing: border-box;
        }
        input[type="file"] {
            padding: 3px;
        }

        button {
            margin-top: 20px;
            background-color: #3498db;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        button:hover {
            opacity: 0.9;
        }

        .back-link {
            display: inline-block;
            margin-top: 15px;
            color: #2c3e50;
            text-decoration: none;
        }
        .message {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            font-size: 0.9rem;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .current-image-preview {
            margin-top: 10px;
            text-align: center;
        }
        .current-image-preview img {
            max-width: 150px;
            height: auto;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 5px;
        }
        .current-image-preview p {
            font-size: 0.85em;
            color: #555;
            margin-bottom: 5px;
        }
    </style>
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-M37ZFNLZ9Q"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-M37ZFNLZ9Q');
</script>

</head>
<body>

<div class="container">
    <h2>Edit Item</h2>

    <?php
    /**
     * @brief Displays success or error messages to the user.
     * The message string is parsed to determine if it's a success or error.
     */
    if (!empty($message)):
        // Check if the message contains "success:" or "error:" prefix
        $message_class = '';
        $display_message = '';
        if (strpos($message, 'success:') === 0) {
            $message_class = 'success';
            $display_message = substr($message, 8); // Remove "success:" prefix
        } elseif (strpos($message, 'error:') === 0) {
            $message_class = 'error';
            $display_message = substr($message, 6); // Remove "error:" prefix
        } else {
            // Fallback for messages without explicit prefixes (e.g., initial "No product selected")
            $message_class = 'error'; // Treat as error by default for un-prefixed messages
            $display_message = $message;
        }
    ?>
        <div class="message <?= $message_class ?>">
            <?= htmlspecialchars($display_message) ?>
        </div>
    <?php endif; ?>

    <?php
    /**
     * @brief Displays the item editing form if product details were successfully fetched.
     * Otherwise, shows a message indicating that product details could not be loaded.
     */
    if ($product):
    ?>
    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?= htmlspecialchars($product['id'] ?? '') ?>">

        <label for="itemName">Item Name:</label>
        <input type="text" name="itemName" id="itemName" value="<?= htmlspecialchars($product['itemName'] ?? '') ?>" required>

        <label for="itemDescription">Description:</label>
        <textarea name="itemDescription" id="itemDescription" rows="4" required><?= htmlspecialchars($product['itemDescription'] ?? '') ?></textarea>

        <label for="price">Price (R):</label>
        <input type="number" name="price" id="price" step="0.01" value="<?= htmlspecialchars($product['price'] ?? '') ?>" required>

        <label for="quantity">Quantity:</label>
        <input type="number" name="quantity" id="quantity" value="<?= htmlspecialchars($product['quantity'] ?? '') ?>" required>

        <label for="item_picture">Item Picture:</label>
        <?php
        /**
         * @brief Displays the current image preview and an option to remove it if an image exists.
         */
        if (!empty($product['item_picture'])):
        ?>
            <div class="current-image-preview">
                <p>Current Image:</p>
                <img src="<?= htmlspecialchars($product['item_picture']) ?>" alt="Current Item Image">
                <input type="checkbox" id="remove_picture" name="remove_picture" value="yes">
                <label for="remove_picture" style="display:inline; margin-top:0;">Remove Current Picture</label>
            </div>
        <?php endif; ?>
        <input type="file" name="item_picture" id="item_picture" accept="image/*">
        <small>Leave blank to keep current image. Max file size: 2MB. Allowed types: JPG, JPEG, PNG, GIF.</small>

        <button type="submit">Update Item</button>
    </form>
    <?php
    else:
    ?>
        <p>Could not load product details. Please go back to your listed items.</p>
    <?php
    endif;
    ?>
    <a class="back-link" href="viewCurrentItems.php">← Back to Listed Items</a>
</div>

</body>
</html>
