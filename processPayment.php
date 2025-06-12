<?php
session_start(); // Starts or resumes a session to manage user data across requests.
require_once 'db.php'; // Includes the database connection file.

// Checks if essential session variables for order processing are set.
if (!isset($_SESSION['buyer_id'], $_SESSION['delivery_address'], $_SESSION['grand_total'], $_SESSION['courier_cost'])) {
    header("Location: viewCart.php"); // Redirects to the cart page if any essential data is missing.
    exit(); // Halts script execution after redirection.
}

// Retrieves order details from session variables.
$buyerId = $_SESSION['buyer_id'];
$deliveryAddress = $_SESSION['delivery_address'];
$totalAmount = $_SESSION['grand_total'];
$courierCost = $_SESSION['courier_cost'];
$orderDate = date("Y-m-d H:i:s"); // Generates the current date and time for the order.

// Prepares a SQL statement to insert a new order into the 'orders' table.
$query = "INSERT INTO orders (buyer_id, delivery_address, total_amount, order_date, courier_cost) VALUES (?, ?, ?, ?, ?)";
$stmt = $conn->prepare($query);
// Binds parameters to the prepared statement: buyer_id (integer), delivery_address (string), total_amount (double), order_date (string), courier_cost (integer).
$stmt->bind_param("issdi", $buyerId, $deliveryAddress, $totalAmount, $orderDate, $courierCost);

// Executes the prepared statement.
if ($stmt->execute()) {
    $orderId = $stmt->insert_id; // Retrieves the ID of the newly inserted order.

    // Deletes all items from the buyer's cart after the order has been successfully placed.
    
    $conn->query("DELETE FROM cart WHERE buyer_id = $buyerId");

    // Redirects to a payment success page, passing the new order ID as a URL parameter.
    header("Location: paymentSuccess.php?order_id=" . $orderId);
    exit(); // Halts script execution after redirection.
} else {
    // Displays an error message if the order could not be saved to the database.
    echo "Order could not be saved. Please try again.";
}
?>
