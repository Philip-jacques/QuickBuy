<?php
// Start a new session or resume the existing one. This is crucial for managing user states (like login status).
session_start();

// Enable all error reporting for development purposes. This helps in debugging by displaying all PHP errors.
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the database connection file.
require 'db.php'; 

// Set the HTTP header to indicate that the response will be in JSON format.
header('Content-Type: application/json');

// Initialize the response array. This array will hold the status, message, and any relevant data to be sent back to the client.
$response = ['success' => false, 'message' => 'An unknown error occurred.', 'newQuantity' => null, 'productId' => null];

// Retrieve the buyer_id from the session. Use the null coalescing operator (??) to set it to null if not found.
$buyerId = $_SESSION['buyer_id'] ?? null;
// Retrieve the product_id from the POST request.
$productId = $_POST['product_id'] ?? null;
// Retrieve the quantity to add from the POST request and cast it to an integer. Default to 1 if not provided.
$quantityToAdd = (int)($_POST['quantity'] ?? 1); // Renamed to quantityToAdd for clarity

// Always include the product ID in the response, even if there's an error, for client-side debugging/identification.
$response['productId'] = $productId; 

// --- Input Validation ---

// Check if the buyer ID is not set. If so, the user is not logged in.
if (!$buyerId) {
    $response['message'] = "You must be logged in to add items to your cart.";
    echo json_encode($response); // Encode the response array into a JSON string and send it.
    exit(); // Terminate script execution.
}

// Check if a product ID was not provided in the request.
if (!$productId) {
    $response['message'] = "No product selected.";
    echo json_encode($response); // Encode the response and send.
    exit(); // Terminate script execution.
}

// --- Database Transaction ---

// Start a database transaction. This ensures that a series of database operations are treated as a single, atomic unit.
// If any step fails, all changes can be rolled back, maintaining data integrity.
$conn->begin_transaction();

try {
    // --- Step 1: Fetch Current Stock and Price from products table ---
    // Prepare a SQL statement to select the item name, current stock quantity, and price for the given product ID.
    // 'FOR UPDATE' locks the selected row to prevent other transactions from modifying it until this transaction commits or rolls back.
    // This is crucial to prevent race conditions when checking and updating stock.
    $stockStmt = $conn->prepare("SELECT itemName, quantity AS stock_quantity, price FROM products WHERE id = ? FOR UPDATE");
    // Check if the statement preparation failed.
    if (!$stockStmt) {
        throw new Exception("Prepare failed: " . $conn->error); // Throw an exception with the database error.
    }
    $stockStmt->bind_param("i", $productId); // Bind the product ID as an integer.
    $stockStmt->execute(); // Execute the prepared statement.
    $stockResult = $stockStmt->get_result(); // Get the result set from the executed query.
    $product = $stockResult->fetch_assoc(); // Fetch the product data as an associative array.
    $stockStmt->close(); // Close the prepared statement.

    // Check if no product was found with the given ID.
    if (!$product) {
        throw new Exception("Product does not exist."); // Throw an exception if the product isn't found.
    }

    $itemName = $product['itemName']; // Store the item name.
    $stockQuantity = $product['stock_quantity']; // Store the current stock quantity.
    $itemPrice = $product['price']; // Get price to store at time of add (important for historical cart data if prices change).

    // --- Step 2: Check Existing Cart Item ---
    // Prepare a SQL statement to check if the product already exists in the buyer's cart.
    // 'FOR UPDATE' also locks this cart row to prevent race conditions if multiple requests try to modify the same cart item.
    $checkStmt = $conn->prepare("SELECT id, quantity AS cart_quantity FROM cart WHERE buyer_id = ? AND product_id = ? FOR UPDATE");
    // Check if the statement preparation failed.
    if (!$checkStmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $checkStmt->bind_param("ii", $buyerId, $productId); // Bind the buyer ID and product ID.
    $checkStmt->execute(); // Execute the statement.
    $result = $checkStmt->get_result(); // Get the result.
    $existing = $result->fetch_assoc(); // Fetch existing cart item data.
    $checkStmt->close(); // Close the statement.

    // --- Step 3: Compare Quantities and Handle Cart Update/Insert ---

    // If the product already exists in the cart:
    if ($existing) {
        $newCartQty = $existing['cart_quantity'] + $quantityToAdd; // Calculate the new total quantity for the cart.
        // Check if the new total cart quantity exceeds the available stock.
        if ($newCartQty > $stockQuantity) {
            // --- Insufficient Stock (Existing Item) ---
            throw new Exception("Sorry, only {$stockQuantity} of '" . htmlspecialchars($itemName) . "' are currently in stock. Adding {$quantityToAdd} would result in {$newCartQty} in your cart.");
        } else {
            // --- Update Cart (Sufficient Stock) ---
            // Prepare a statement to update the quantity and 'price_at_add' for the existing cart item.
            $updateStmt = $conn->prepare("UPDATE cart SET quantity = ?, price_at_add = ? WHERE id = ?");
            // Check for prepare failure.
            if (!$updateStmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $updateStmt->bind_param("idi", $newCartQty, $itemPrice, $existing['id']); // Bind new quantity, item price, and cart item ID.
            $updateStmt->execute(); // Execute the update.
            $updateStmt->close(); // Close the statement.
            $response['message'] = "'" . htmlspecialchars($itemName) . "' quantity updated to {$newCartQty} in your cart."; // Success message.
        }
    } else {
        // --- Adding New Item ---
        // If the product is not yet in the cart.
        // Check if the quantity the user wants to add exceeds the available stock.
        if ($quantityToAdd > $stockQuantity) {
            // --- Insufficient Stock (New Item) ---
            throw new Exception("Sorry, only {$stockQuantity} of '" . htmlspecialchars($itemName) . "' are currently in stock. You are trying to add {$quantityToAdd}.");
        } else {
            // --- Insert into Cart (Sufficient Stock) ---
            // Prepare a statement to insert a new item into the cart table.
            $insertStmt = $conn->prepare("INSERT INTO cart (buyer_id, product_id, quantity, price_at_add) VALUES (?, ?, ?, ?)");
            // Check for prepare failure.
            if (!$insertStmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $insertStmt->bind_param("iiid", $buyerId, $productId, $quantityToAdd, $itemPrice); // Bind buyer ID, product ID, quantity, and item price.
            $insertStmt->execute(); // Execute the insert.
            $insertStmt->close(); // Close the statement.
            $response['message'] = "'" . htmlspecialchars($itemName) . "' added to your cart."; // Success message.
        }
    }

    // If all operations within the try block succeed, commit the transaction.
    $conn->commit();
    $response['success'] = true; // Set success status to true.
    // Return the actual stock quantity. Important: Stock is only reduced at checkout, not when adding to cart.
    $response['newQuantity'] = $stockQuantity;

} catch (Exception $e) {
    // If any exception is caught, rollback the transaction to undo all changes made during the transaction.
    $conn->rollback();
    $response['message'] = $e->getMessage(); // Set the error message in the response.
} finally {
    // This block always executes, whether an exception occurred or not.
    // Check if the database connection object exists and is still alive (ping() checks connection status).
    if (isset($conn) && $conn->ping()) {
        $conn->close(); // Close the database connection to free up resources.
    }
}

// Encode the final response array as a JSON string and send it back to the client.
echo json_encode($response);
exit(); // Terminate script execution.
?>
