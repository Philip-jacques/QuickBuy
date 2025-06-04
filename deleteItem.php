<?php
include 'db.php';

if (isset($_POST['id'])) {
    $id = (int) $_POST['id'];

    // Prepare DELETE statement to prevent SQL injection
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo "success:Item deleted successfully.";  // Success message
    } else {
        echo "error:Failed to delete item.";  // Error message
    }
} else {
    echo "error:No item ID provided.";  // If no ID was provided
}
?>
