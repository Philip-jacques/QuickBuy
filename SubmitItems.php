<?php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Session check and seller ID retrieval
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'seller') {
        // Use http_response_code for AJAX requests for better client-side handling
        http_response_code(403); // Forbidden
        echo "Unauthorized access. Please login as a seller.";
        exit();
    }

    $sellerId = $_SESSION['user_id'];

    // 2. Input Sanitization and Validation
    // Used filter_input for better security and type casting
    $itemName = filter_input(INPUT_POST, 'itemName', FILTER_SANITIZE_STRING);
    $itemDescription = filter_input(INPUT_POST, 'itemDescription', FILTER_SANITIZE_STRING);
    $altText = filter_input(INPUT_POST, 'altText', FILTER_SANITIZE_STRING);
    $category = filter_input(INPUT_POST, 'category', FILTER_SANITIZE_STRING);
    $dateAdded = filter_input(INPUT_POST, 'dateAdded', FILTER_SANITIZE_STRING); // YYYY-MM-DD format
    $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);

    // Basic validation for required fields
    if (empty($itemName) || empty($itemDescription) || empty($category) || empty($dateAdded) || $price === false || $quantity === false) {
        http_response_code(400); // Bad Request
        echo "Please ensure all required fields are filled and valid.";
        exit();
    }

    // For debugging, use error_log instead of die() for AJAX
    error_log("POST Data: " . print_r($_POST, true));

    // 3. Image Upload Handling
    $itemPicturePath = null;
    $uploadDir = 'images/';

    // Create directory if it doesn't exist
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true)) {
            // Log error, but don't stop execution immediately if directory can't be made.
            // This might mean images won't save, but the product details might.
            error_log("Failed to create the images directory: " . $uploadDir);
            http_response_code(500); // Internal Server Error
            echo "Server error: Failed to create upload directory.";
            exit(); // Or choose to proceed without image path
        }
    }

    if (isset($_FILES['itemPicture']) && $_FILES['itemPicture']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['itemPicture']['tmp_name'];
        $fileName = uniqid() . '_' . basename($_FILES['itemPicture']['name']); // Using uniqid() is good
        $filePath = $uploadDir . $fileName;

        // Add server-side validation for file type and size (mirroring client-side)
        $allowedFileTypes = ['image/jpeg', 'image/png', 'image/webp'];
        $maxFileSize = 2 * 1024 * 1024; // 2MB (from your AddItemPage.php)

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detectedFileType = finfo_file($finfo, $fileTmpPath);
        finfo_close($finfo);

        if (!in_array($detectedFileType, $allowedFileTypes)) {
            http_response_code(400);
            echo "Invalid file type. Only JPEG, PNG, or WebP images are allowed.";
            exit();
        }
        if ($_FILES['itemPicture']['size'] > $maxFileSize) {
            http_response_code(400);
            echo "Image size must be under 2MB.";
            exit();
        }


        if (move_uploaded_file($fileTmpPath, $filePath)) {
            $itemPicturePath = $filePath;
            error_log("File uploaded successfully. Path: " . $itemPicturePath);
        } else {
            $error = error_get_last();
            error_log("File upload failed for " . $_FILES['itemPicture']['name'] . ": " . ($error['message'] ?? 'Unknown error'));
            http_response_code(500); // Internal Server Error
            echo "Error uploading file. Please try again.";
            exit(); // Exit if image upload fails, as it's likely a critical part of the listing
        }
    } else {
        // If no image is uploaded, or an error occurred during upload
        // You can choose to allow items without images or require them.
        // For now, it will proceed without an image path if no image is uploaded.
        error_log("No file uploaded or upload error code: " . ($_FILES['itemPicture']['error'] ?? 'N/A'));
    }

    error_log("itemPicturePath before insert: " . ($itemPicturePath ?? 'NULL'));

    // 4. Set the initial status to 'pending'
    $status = 'pending';

    // 5. Prepare and Execute SQL INSERT statement
    // Add 'status' column to your INSERT query and 's' to bind_param types
    $stmt = $conn->prepare("
        INSERT INTO products
        (itemName, itemDescription, alt_text, category, dateAdded, dateSold, price, quantity, seller_id, item_picture, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        http_response_code(500); // Internal Server Error
        echo "Database error preparing statement: " . $conn->error;
        exit();
    }

    // Used binding types: s=string, d=double, i=integer
    
    $stmt->bind_param(
        "ssssssdiiss", 
        $itemName,
        $itemDescription,
        $altText,
        $category,
        $dateAdded,
        $dateSold,
        $price,
        $quantity,
        $sellerId,
        $itemPicturePath,
        $status // This will always be 'pending' for new submissions
    );

    // Log the query parameters for debugging
    $params = [
        $itemName, $itemDescription, $altText, $category, $dateAdded,
        $dateSold, $price, $quantity, $sellerId, $itemPicturePath, $status
    ];
    error_log("Executing insert with parameters: " . print_r($params, true));

    if ($stmt->execute()) {
        http_response_code(200); // Success response for AJAX
        echo "Item successfully submitted for review!"; // Message for client-side alert
    } else {
        error_log("Database Error: " . $stmt->error);
        http_response_code(500); // Internal Server Error
        echo "Database Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
} else {
    http_response_code(405); // Method Not Allowed
    echo "Invalid request method.";
}
?>
