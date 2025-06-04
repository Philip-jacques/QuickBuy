<?php
session_start(); // Starts or resumes a session to manage user data across requests.
require_once 'db.php'; // Includes the database connection file.

$buyerId = $_SESSION['buyer_id'] ?? null; // Retrieves the buyer's ID from the session, or sets to null if not found.

if (!$buyerId) {
    header("Location: LoginPage.php"); // Redirects to the login page if no buyer ID is in the session.
    exit(); // Halts script execution after redirection.
}

// --- LOGIC FOR CANCEL BUTTON ---
// This block processes the 'cancel' action from the payment form.
if (isset($_POST['payment_action']) && $_POST['payment_action'] === 'cancel') {
    $conn->begin_transaction(); // Initiates a database transaction.
    try {
        // Prepares a SQL statement to delete all items from the buyer's cart.
        $clearCartQuery = "DELETE FROM cart WHERE buyer_id = ?";
        $clearCartStmt = $conn->prepare($clearCartQuery);
        if (!$clearCartStmt) {
            error_log("Prepare failed to clear cart: " . $conn->error); // Logs a database error.
            throw new Exception("Failed to prepare statement for cart clearance."); // Throws an exception if statement preparation fails.
        }
        $clearCartStmt->bind_param("i", $buyerId); // Binds the buyer ID to the prepared statement.
        $clearCartStmt->execute(); // Executes the statement to clear the cart.
        $clearCartStmt->close(); // Closes the prepared statement.

        $conn->commit(); // Commits the transaction if cart clearance is successful.

        $_SESSION['success_message'] = "Your order has been cancelled and items removed from your cart."; // Sets a success message in the session.

        header("Location: BrowseItemsPage.php"); // Redirects to the browse items page.
        exit(); // Halts script execution after redirection.

    } catch (Exception $e) {
        $conn->rollback(); // Rolls back the transaction if an error occurs.
        $_SESSION['error_message'] = "Failed to cancel order and clear cart due to a system error. Please try again or contact support: " . $e->getMessage(); // Sets an error message in the session.
        header("Location: viewCart.php"); // Redirects back to the cart page.
        exit(); // Halts script execution after redirection.
    } finally {
        // Closes the database connection if it's open and still active.
        if (isset($conn) && $conn instanceof mysqli && $conn->ping()) {
            $conn->close();
        }
    }
}

// --- The rest of the script (payment processing) should only run if 'proceed' was clicked ---
// This block processes the 'proceed' action from the payment form.
if (isset($_POST['payment_action']) && $_POST['payment_action'] === 'proceed') {

    // Retrieves and sanitizes form input for delivery address, payment method, and amount.
    $deliveryAddress = trim($_POST['delivery_address'] ?? '');
    $paymentMethod = $_POST['payment_method'] ?? '';
    $amount = floatval($_POST['amount'] ?? 0);

    // Performs basic validation for required checkout details.
    if (empty($deliveryAddress) || empty($paymentMethod) || $amount <= 0) {
        $_SESSION['payment_error'] = "Please provide all required checkout details (delivery address and payment method)."; // Sets an error message.
        header("Location: viewCart.php"); // Redirects back to the cart page.
        exit(); // Halts script execution.
    }

    // Defines valid payment methods and validates the selected method.
    $validMethods = ['payfast', 'cod', 'instant_eft'];
    if (!in_array($paymentMethod, $validMethods)) {
        $_SESSION['payment_error'] = "Invalid payment method selected."; // Sets an error message.
        header("Location: viewCart.php"); // Redirects back to the cart page.
        exit(); // Halts script execution.
    }

    // --- COURIER CALCULATION FUNCTIONS ---
    /**
     * Simulates fetching coordinates for a given address based on predefined locations.
     * @param string $address The address to find coordinates for.
     * @return array An associative array containing 'lat' (latitude) and 'lng' (longitude).
     */
    function getSimulatedCoordinates($address) {
        $address = strtolower(trim($address)); // Converts address to lowercase and trims whitespace.
        $locations = [
            'malmesbury' => ['lat' => -33.4594, 'lng' => 18.7218],
            'cape town' => ['lat' => -33.9249, 'lng' => 18.4241],
            'durban' => ['lat' => -29.8587, 'lng' => 31.0218],
            'johannesburg' => ['lat' => -26.2041, 'lng' => 28.0473],
            'pretoria' => ['lat' => -25.7479, 'lng' => 28.2293],
            'bellville' => ['lat' => -33.9021, 'lng' => 18.6258],
            'stellenbosch' => ['lat' => -33.9346, 'lng' => 18.8610],
            'parow' => ['lat' => -33.8980, 'lng' => 18.6017],
            'goodwood' => ['lat' => -33.8998, 'lng' => 18.5669],
            'gauteng' => ['lat' => -26.2708, 'lng' => 28.1123],
            'western cape' => ['lat' => -33.5500, 'lng' => 20.5000],
            'moorreesburg' => ['lat' => -33.1447, 'lng' => 18.6403],
            'darling' => ['lat' => -33.3731, 'lng' => 18.3875],
            'yzerfontein' => ['lat' => -33.5091, 'lng' => 18.1561],
            'rietvlei' => ['lat' => -33.3489, 'lng' => 18.5189],
            'koringberg' => ['lat' => -33.0833, 'lng' => 18.7167],
            'piketberg' => ['lat' => -32.9078, 'lng' => 18.7444],
            'porterville' => ['lat' => -33.0333, 'lng' => 19.0167],
            'citrusdal' => ['lat' => -32.5833, 'lng' => 19.0333],
            'wellington' => ['lat' => -33.6417, 'lng' => 19.0000],
        ];

        // Iterates through predefined locations to find a match in the address.
        foreach ($locations as $key => $coords) {
            if (strpos($address, $key) !== false) {
                return $coords; // Returns coordinates if a match is found.
            }
        }
        return $locations['malmesbury']; // Returns Malmesbury coordinates as a default if no match.
    }

    /**
     * Calculates the distance between two sets of geographical coordinates using the Haversine formula.
     * @param float $lat1 Latitude of the first point.
     * @param float $lon1 Longitude of the first point.
     * @param float $lat2 Latitude of the second point.
     * @param float $lon2 Longitude of the second point.
     * @return float The distance in kilometers.
     */
    function calculateDistance($lat1, $lon1, $lat2, $lon2) {
        $R = 6371; // Earth's radius in kilometers.
        $dLat = deg2rad($lat2 - $lat1); // Difference in latitude in radians.
        $dLon = deg2rad($lon2 - $lon1); // Difference in longitude in radians.
        // Haversine formula to calculate distance.
        $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $R * $c; // Distance in km.
        return $distance; // Returns the calculated distance.
    }

    // Gets simulated coordinates for the business and customer addresses.
    $businessCoords = getSimulatedCoordinates('31 Smuts Str, Malmesbury');
    $customerCoords = getSimulatedCoordinates($deliveryAddress);

    // Calculates the distance and courier cost.
    $distance = calculateDistance($businessCoords['lat'], $businessCoords['lng'], $customerCoords['lat'], $customerCoords['lng']);
    $courierCost = round($distance * 5, 2); // Calculates courier cost at R5/km, rounded to 2 decimal places.
    $totalAmount = $amount + $courierCost; // Calculates the total amount including courier cost.

    // --- START TRANSACTION FOR PROCEED LOGIC ---
    $conn->begin_transaction(); // Initiates a database transaction for payment processing.

    try {
        // --- Step 1: Fetch items from the cart for final verification ---
        // Selects cart items and their corresponding product details, locking relevant rows for update.
        $cartQuery = "SELECT c.product_id, c.quantity, c.price_at_add, p.itemName, p.quantity AS stock_quantity
                      FROM cart c
                      JOIN products p ON c.product_id = p.id
                      WHERE c.buyer_id = ? FOR UPDATE"; // Locks cart items and indirectly products through join.
        $cartStmt = $conn->prepare($cartQuery);
        if (!$cartStmt) { // Checks if the statement preparation was successful.
            error_log("Prepare failed for cart query: " . $conn->error); // Logs a database error.
            throw new Exception("Database error fetching cart items."); // Throws an exception.
        }
        $cartStmt->bind_param("i", $buyerId); // Binds the buyer ID.
        $cartStmt->execute(); // Executes the query.
        $cartResult = $cartStmt->get_result(); // Gets the result set.
        $cartItems = $cartResult->fetch_all(MYSQLI_ASSOC); // Fetches all cart items as an associative array.
        $cartStmt->close(); // Closes the statement.

        // Throws an exception if the cart is empty.
        if (empty($cartItems)) {
            throw new Exception("Your cart is empty. Cannot proceed with payment.");
        }

        $insufficientStock = false;
        $stockCheckFailedItems = [];

        // --- Step 2: Verify stock for each item in the cart ---
        // Iterates through cart items to check stock availability.
        foreach ($cartItems as $item) {
            if ($item['quantity'] > $item['stock_quantity']) {
                $insufficientStock = true; // Sets flag if stock is insufficient.
                $stockCheckFailedItems[] = $item['itemName'] . " (only " . $item['stock_quantity'] . " available, " . $item['quantity'] . " needed)"; // Records details of insufficient stock.
            }
        }

        // --- Step 3: Handle insufficient stock ---
        // Throws an exception if insufficient stock is detected.
        if ($insufficientStock) {
            $_SESSION['stock_error'] = "Sorry, some items in your cart are now out of stock or have insufficient quantity: " . implode(", ", $stockCheckFailedItems) . ". Please review your cart."; // Sets a stock error message.
            throw new Exception("Insufficient stock detected."); // Throws to trigger rollback.
        }

        // --- Step 4: Create the order if stock is sufficient ---
        // Inserts a new order record into the 'orders' table.
        $orderQuery = "INSERT INTO orders (buyer_id, total_amount, delivery_address, courier_cost, order_date) VALUES (?, ?, ?, ?, NOW())";
        $orderStmt = $conn->prepare($orderQuery);
        if (!$orderStmt) { // Checks if statement preparation was successful.
            error_log("Prepare failed for order insert: " . $conn->error); // Logs a database error.
            throw new Exception("Database error creating order."); // Throws an exception.
        }
        $orderStmt->bind_param("idss", $buyerId, $amount, $deliveryAddress, $courierCost); // Binds parameters.
        $orderStmt->execute(); // Executes the statement.
        $orderId = $orderStmt->insert_id; // Gets the ID of the newly inserted order.
        $orderStmt->close(); // Closes the statement.

        // --- Step 5: Add items to order_items and decrement product stock ---
        // Iterates through each cart item to add to order_items and update product stock.
        foreach ($cartItems as $item) {
            $productId = $item['product_id'];
            $quantity = $item['quantity'];
            $priceAtAdd = $item['price_at_add'];

            // Inserts item details into the 'order_items' table.
            $itemQuery = "INSERT INTO order_items (order_id, product_id, quantity, price_at_purchase) VALUES (?, ?, ?, ?)";
            $itemStmt = $conn->prepare($itemQuery);
            if (!$itemStmt) { // Checks if statement preparation was successful.
                error_log("Prepare failed for order item insert: " . $conn->error); // Logs a database error.
                throw new Exception("Database error adding order items."); // Throws an exception.
            }
            $itemStmt->bind_param("iiid", $orderId, $productId, $quantity, $priceAtAdd); // Binds parameters.
            $itemStmt->execute(); // Executes the statement.
            $itemStmt->close(); // Closes the statement.

            // Decrements the quantity of the product in the 'products' table.
            $updateStockQuery = "UPDATE products SET quantity = quantity - ? WHERE id = ?";
            $updateStockStmt = $conn->prepare($updateStockQuery);
            if (!$updateStockStmt) { // Checks if statement preparation was successful.
                error_log("Prepare failed for stock update: " . $conn->error); // Logs a database error.
                throw new Exception("Database error updating product stock."); // Throws an exception.
            }
            $updateStockStmt->bind_param("ii", $quantity, $productId); // Binds parameters.
            $updateStockStmt->execute(); // Executes the statement.
            $updateStockStmt->close(); // Closes the statement.
        }

        // --- Step 6: Record payment information ---
        $paymentStatus = "Pending"; // Initial payment status.
        $status = "Initiated"; // Initial status of the payment attempt.

        // Inserts payment details into the 'payments' table.
        $paymentQuery = "INSERT INTO payments (order_id, buyer_id, delivery_address, payment_method, cart_amount, courier_cost, total_amount, payment_status, payment_date, status)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)";

        $paymentStmt = $conn->prepare($paymentQuery);
        if (!$paymentStmt) { // Checks if statement preparation was successful.
            error_log("Prepare failed for payment insert: " . $conn->error); // Logs a database error.
            throw new Exception("Database error recording payment."); // Throws an exception.
        }
        $paymentStmt->bind_param("iissddsss", $orderId, $buyerId, $deliveryAddress, $paymentMethod, $amount, $courierCost, $totalAmount, $paymentStatus, $status); // Binds parameters.
        $paymentStmt->execute(); // Executes the statement.
        $paymentId = $paymentStmt->insert_id; // Gets the ID of the newly inserted payment record.
        $paymentStmt->close(); // Closes the statement.

        // --- Step 7: Clear the cart ---
        // Deletes items from the buyer's cart after successful order and payment processing.
        $clearCartQuery = "DELETE FROM cart WHERE buyer_id = ?";
        $clearCartStmt = $conn->prepare($clearCartQuery);
        if (!$clearCartStmt) { // Checks if statement preparation was successful.
            error_log("Prepare failed for final cart clear: " . $conn->error); // Logs a database error.
            throw new Exception("Database error clearing cart after order."); // Throws an exception.
        }
        $clearCartStmt->bind_param("i", $buyerId); // Binds the buyer ID.
        $clearCartStmt->execute(); // Executes the statement.
        $clearCartStmt->close(); // Closes the statement.

        $conn->commit(); // Commits the transaction if all steps are successful.

        // --- Step 8: Redirect based on payment method ---
        // Redirects the user to the appropriate payment processing page based on the selected method.
        switch ($paymentMethod) {
            case 'instant_eft':
                header("Location: instant_eft_process.php?order_id=$orderId&payment_id=$paymentId");
                exit();
            case 'cod':
                header("Location: cod_confirmation.php?order_id=$orderId");
                exit();
            case 'payfast':
                header("Location: payfastRedirect.php?order_id=$orderId&payment_id=$paymentId");
                exit();
            default:
                throw new Exception("Error: Unknown payment method."); // Throws an exception for an unknown payment method.
        }

    } catch (Exception $e) {
        $conn->rollback(); // Rolls back the transaction if any error occurs.
        if (!isset($_SESSION['stock_error'])) { // Preserves a specific stock error message if already set.
            $_SESSION['payment_error'] = "An error occurred during checkout: " . $e->getMessage(); // Sets a general payment error message.
        }
        header("Location: viewCart.php"); // Redirects back to the cart page.
        exit(); // Halts script execution.
    } finally {
        // Ensures the database connection is closed.
        if (isset($conn) && $conn instanceof mysqli && $conn->ping()) {
            $conn->close();
        }
    }
} else {
    // This block handles cases where 'payment_action' is not 'proceed' or 'cancel',
    // or if the script is accessed directly without a POST request containing 'payment_action'.
    header("Location: viewCart.php"); // Redirects to the view cart page.
    exit(); // Halts script execution.
}
?>