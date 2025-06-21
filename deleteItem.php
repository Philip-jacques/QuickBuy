<?php
include 'db.php';

if (isset($_POST['id'])) {
    $id = (int) $_POST['id'];

    // Start a transaction for atomicity
    $conn->begin_transaction();

    try {
        // 1. Delete dependent order_items first
        $stmt_order_items = $conn->prepare("DELETE FROM order_items WHERE product_id = ?");
        $stmt_order_items->bind_param("i", $id);
        $stmt_order_items->execute();
        // No need to check rows affected here, as some products might not be in any order_items

        // 2. Then, delete the product
        $stmt_products = $conn->prepare("DELETE FROM products WHERE id = ?");
        $stmt_products->bind_param("i", $id);

        if ($stmt_products->execute()) {
            if ($stmt_products->affected_rows > 0) {
                $conn->commit(); // Commit if both operations succeed
                echo "success:Item deleted successfully.";
            } else {
                $conn->rollback(); // Rollback if no product was deleted (e.g., ID not found)
                echo "error:Product not found or already deleted.";
            }
        } else {
            $conn->rollback(); // Rollback on product deletion failure
            echo "error:Failed to delete product.";
        }

    } catch (mysqli_sql_exception $e) {
        $conn->rollback(); // Rollback on any SQL exception
        echo "error:Database error: " . $e->getMessage();
    }

} else {
    echo "error:No item ID provided.";
}
?>
