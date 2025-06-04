<?php
include 'db.php';

if (isset($_POST['id'], $_POST['price'], $_POST['quantity'])) {
    $id = (int) $_POST['id'];  // Ensure it's an integer
    $price = isset($_POST['price']) ? (float) $_POST['price'] : 0.0;  // Ensure float and fallback to 0 if null
    $quantity = isset($_POST['quantity']) ? (int) $_POST['quantity'] : 0;  // Ensure integer and fallback to 0 if null

    // Validate price and quantity to avoid invalid data
    if ($price < 0 || $quantity < 0) {
        echo "error:Price or quantity cannot be negative.";
        exit;
    }

    // Prepare UPDATE statement to prevent SQL injection
    $stmt = $conn->prepare("UPDATE products SET price = ?, quantity = ? WHERE id = ?");
    $stmt->bind_param("dii", $price, $quantity, $id);

    if ($stmt->execute()) {
        echo "success:Item updated successfully.";  // Success message
    } else {
        echo "error:Failed to update item.";  // Error message
    }
} else {
    echo "error:Invalid input.";  // If missing required fields
}
?>
