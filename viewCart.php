<?php
/**
 * This page displays the buyer's shopping cart, allows them to update item quantities,
 * remove items, and proceed to checkout.
 *
 * It handles:
 * - Session management for buyer authentication.
 * - Database interactions for fetching and updating cart items and product stock.
 * - Processing form submissions for updating quantities and removing items.
 * - Displaying cart contents, total amount, and a checkout form.
 * - Basic stock validation and error handling.
 */

// Start a new session or resume the existing one.
session_start();

// Include the database connection file.
require_once 'db.php';

// --- User Authentication and Authorization ---

// Check if the 'buyer_id' session variable is set.
$buyerId = $_SESSION['buyer_id'] ?? null;

// If the buyer is not logged in (buyer_id is not set), redirect to the login page and exit.
if (!$buyerId) {
    header("Location: LoginPage.php");
    exit();
}

// --- Session-based Error Handling ---

// Retrieve any stock-related error message from the session.
$stockError = $_SESSION['stock_error'] ?? null;
// Clear the error message from the session after it's been retrieved to display it only once.
unset($_SESSION['stock_error']);

// --- Handle POST Requests (Cart Updates and Removals) ---

// Check if the current request method is POST.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- Update Cart Item Quantity ---
    // Check if the 'update' action and necessary product details are set in the POST data.
    if (isset($_POST['update']) && isset($_POST['product_id'], $_POST['quantity'])) {
        // Sanitize and validate product ID and quantity.
        $productId = $_POST['product_id'];
        $quantity = intval($_POST['quantity']); // Ensure quantity is an integer.

        // Fetch current stock quantity for the product from the database to prevent overselling.
        $stockStmt = $conn->prepare("SELECT itemName, quantity AS stock_quantity FROM products WHERE id = ?");
        $stockStmt->bind_param("i", $productId); // Bind product ID as an integer.
        $stockStmt->execute();
        $stockResult = $stockStmt->get_result();
        $product = $stockResult->fetch_assoc(); // Fetch product details.
        $stockStmt->close(); // Close the statement.

        // Check if the product exists.
        if ($product) {
            // If the requested quantity is zero or negative, remove the item from the cart.
            if ($quantity <= 0) {
                $removeQuery = "DELETE FROM cart WHERE product_id = ? AND buyer_id = ?";
                $removeStmt = $conn->prepare($removeQuery);
                $removeStmt->bind_param("ii", $productId, $buyerId); // Bind product and buyer IDs.
                $removeStmt->execute();
                $removeStmt->close();
            }
            // If the requested quantity exceeds the available stock, set a session error message.
            elseif ($quantity > $product['stock_quantity']) {
                $_SESSION['stock_error'] = "Sorry, only " . htmlspecialchars($product['stock_quantity']) . " of '" . htmlspecialchars($product['itemName']) . "' are currently in stock.";
            }
            // Otherwise, update the quantity of the item in the cart.
            else {
                $updateQuery = "UPDATE cart SET quantity = ? WHERE product_id = ? AND buyer_id = ?";
                $updateStmt = $conn->prepare($updateQuery);
                $updateStmt->bind_param("iii", $quantity, $productId, $buyerId); // Bind quantity, product, and buyer IDs.
                $updateStmt->execute();
                $updateStmt->close();
            }
        } else {
            // If the product is not found, set an error message.
            $_SESSION['stock_error'] = "Product not found.";
        }
    }
    // --- Remove Item from Cart ---
    // Check if the 'remove' action and product ID are set in the POST data.
    elseif (isset($_POST['remove']) && isset($_POST['product_id'])) {
        $productId = $_POST['product_id'];

        // Delete the item from the cart.
        $removeQuery = "DELETE FROM cart WHERE product_id = ? AND buyer_id = ?";
        $removeStmt = $conn->prepare($removeQuery);
        $removeStmt->bind_param("ii", $productId, $buyerId); // Bind product and buyer IDs.
        $removeStmt->execute();
        $removeStmt->close();
    }
    // Redirect to the same page (viewCart.php) to refresh the cart display
    // and show any updated quantities or error messages.
    header("Location: viewCart.php");
    exit(); // Exit to prevent further script execution.
}

// --- Fetch Cart Items for Display ---

// SQL query to retrieve cart items along with their product details (name, price)
// and the current stock quantity from the 'products' table.
$query = "SELECT c.product_id, p.itemName, p.price, c.quantity, p.quantity AS stock_quantity
          FROM cart c
          JOIN products p ON c.product_id = p.id
          WHERE c.buyer_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $buyerId); // Bind the buyer ID as an integer.
$stmt->execute();
$result = $stmt->get_result(); // Get the result set.

$items = []; // Initialize an empty array to store cart items.
$totalAmount = 0; // Initialize total amount to zero.

// Loop through each row in the result set.
while ($row = $result->fetch_assoc()) {
    $items[] = $row; // Add the current item to the $items array.
    // Calculate the subtotal for the current item and add it to the total amount.
    $totalAmount += $row['price'] * $row['quantity'];
}

$stmt->close(); // Close the prepared statement.
$conn->close(); // Close the database connection.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart - QuickBuy</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* CSS variables for consistent theming */
        :root {
            /* Main blues */
            --true-blue: #0466c8ff;
            --sapphire: #0353a4ff;
            --yale-blue: #023e7dff;
            --oxford-blue: #002855ff;
            --oxford-blue-2: #001845ff;
            --oxford-blue-3: #001233ff;

            /* Greens & Deeper Blues */
            --caribbean-current: #006466ff;
            --midnight-green: #065a60ff;
            --midnight-green-2: #0b525bff;
            --midnight-green-3: #144552ff;
            --prussian-blue: #212f45ff;
            --deep-space-blue: #0d1b2a;

            /* Neutrals */
            --gunmetal: #30343fff;
            --ghost-white: #fafaffff;
            --delft-blue: #273469ff;
            --space-cadet: #1e2749ff;
            --paynes-gray: #5c677dff;
            --slate-gray: #7d8597ff;
            --cool-gray: #979dacff;
            --charcoal: #1b3a4bff;

            /* Accent */
            --white-pop: #FFFFFF;
            --dark-font: #333;
            --light-font: #fefefe;

            /* Red for danger/logout */
            --danger-red: #dc3545;
            --danger-red-hover: #bd2130;
            
            /* Themed UI Colors */
            --table-header-bg: var(--prussian-blue);
            --table-row-bg-odd: rgba(255, 255, 255, 0.05); /* Light frost for odd rows */
            --table-row-bg-even: rgba(255, 255, 255, 0.1); /* Slightly darker frost for even rows */
            --input-bg: rgba(255, 255, 255, 0.1);
            --input-border: var(--delft-blue);
            --button-update-bg: var(--caribbean-current);
            --button-update-hover: var(--midnight-green);
            --button-remove-bg: var(--danger-red);
            --button-remove-hover: var(--danger-red-hover);
            --checkout-bg: rgba(255, 255, 255, 0.15); /* Frosted background for checkout section */
            --proceed-button-bg: var(--midnight-green);
            --proceed-button-hover: var(--caribbean-current);
            --cancel-button-bg: var(--paynes-gray);
            --cancel-button-hover: var(--gunmetal);
        }

        /* Universal Box-Sizing */
        html {
            box-sizing: border-box;
        }
        *, *::before, *::after {
            box-sizing: inherit;
        }

        /* Basic Body and HTML Styling */
        html, body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            overflow-x: hidden; /* Prevent horizontal scroll */
        }

        body {
            /* Gradient background with animation */
            background: linear-gradient(135deg,
                var(--deep-space-blue),
                var(--midnight-green-3),
                var(--prussian-blue),
                var(--oxford-blue),
                var(--true-blue)
            );
            background-size: 300% 300%;
            animation: bgShift 25s ease infinite; /* Animates the background position */
            font-family: 'Poppins', sans-serif;
            color: var(--ghost-white);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            padding-top: 30px;
            padding-bottom: 30px;
        }

        /* Keyframe animation for background shift */
        @keyframes bgShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Main container for page content */
        .container {
            max-width: 95%;
            width: 100%;
            padding: 20px;
            margin-top: 20px;
            text-align: center;
        }

        /* Page title styling */
        h1 {
            font-size: 2.8rem;
            color: var(--white-pop);
            margin-bottom: 30px;
            text-align: center;
            font-weight: 700;
        }

        /* Style for the "Back to Browse Categories" link */
        .back-link {
            display: inline-block;
            margin-bottom: 25px;
            text-decoration: none;
            background-color: var(--cool-gray);
            color: var(--white-pop);
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 500;
            transition: background-color 0.3s ease, transform 0.2s ease;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
            align-self: flex-start; /* Align to the left in flex container */
            margin-left: 2.5%; /* Adjust to roughly align with container content */
            margin-right: auto;
        }

        .back-link:hover {
            background-color: var(--paynes-gray);
            transform: scale(1.02); /* Slightly enlarge on hover */
        }

        /* Styling for stock error messages */
        .stock-error {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 10px;
            font-size: 1rem;
            text-align: center;
            background-color: rgba(220, 53, 69, 0.2); /* Red with transparency */
            backdrop-filter: blur(8px); /* Frosted glass effect */
            border: 1px solid var(--danger-red);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
            color: var(--danger-red);
            font-weight: 600;
        }

        /* Table styling for cart items */
        table {
            width: 100%;
            border-collapse: separate; /* Use separate for rounded corners on cells */
            border-spacing: 0;
            margin-bottom: 30px;
            border-radius: 15px; /* Rounded corners for the entire table */
            overflow: hidden; /* Ensures internal borders don't show outside radius */
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.25);
            backdrop-filter: blur(8px); /* Frosted effect for table */
            background-color: rgba(255, 255, 255, 0.08); /* Light base for table */
        }

        /* Table header and data cell styling */
        th, td {
            padding: 15px;
            text-align: left;
            color: var(--ghost-white);
            font-size: 0.95rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1); /* Subtle internal borders */
        }
        
        th {
            background-color: var(--table-header-bg);
            color: var(--white-pop);
            font-weight: 600;
            position: sticky; /* Make headers sticky for scrolling tables */
            top: 0;
            z-index: 1; /* Ensure header stays above rows */
        }

        /* Rounded corners for first/last cells in header/body */
        th:first-child { border-top-left-radius: 15px; }
        th:last-child { border-top-right-radius: 15px; }
        tr:last-child td:first-child { border-bottom-left-radius: 15px; }
        tr:last-child td:last-child { border-bottom-right-radius: 15px; }

        /* Alternating row background colors for readability */
        tbody tr:nth-child(odd) {
            background-color: var(--table-row-bg-odd);
        }

        tbody tr:nth-child(even) {
            background-color: var(--table-row-bg-even);
        }

        /* Hover effect for table rows */
        tbody tr:hover {
            background-color: rgba(255, 255, 255, 0.15); /* Slightly more opaque on hover */
        }

        /* Styling for quantity input fields */
        input[type="number"] {
            width: 80px;
            padding: 10px;
            border-radius: 8px;
            border: 1px solid var(--input-border);
            background-color: var(--input-bg);
            color: var(--ghost-white);
            font-size: 0.9rem;
            text-align: center;
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }

        input[type="number"]:focus {
            outline: none;
            border-color: var(--true-blue);
            background-color: rgba(255, 255, 255, 0.2);
        }
        
        /* Hide spin buttons for number input */
        input[type="number"]::-webkit-inner-spin-button,
        input[type="number"]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        input[type="number"] {
            -moz-appearance: textfield; /* Firefox specific */
        }

        /* Styling for the actions cell (update/remove buttons) */
        .actions-cell {
            white-space: nowrap; /* Prevent wrapping of content */
            display: flex; /* Use flex to arrange buttons */
            gap: 10px; /* Space between buttons */
            flex-wrap: wrap; /* Allow buttons to wrap on smaller screens */
            justify-content: flex-start; /* Align to the start */
        }
        
        .actions-cell form {
            display: flex; /* Make forms flex containers as well */
            align-items: center; /* Align items vertically in forms */
            gap: 8px; /* Space between input and button */
        }

        /* General button styling within actions cell */
        .actions-cell button {
            padding: 9px 15px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            font-size: 0.85rem;
            transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.2s ease;
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.2);
        }

        /* Specific styling for update button */
        .actions-cell button[name="update"] {
            background-color: var(--button-update-bg);
            color: var(--white-pop);
        }
        .actions-cell button[name="update"]:hover {
            background-color: var(--button-update-hover);
            transform: translateY(-2px);
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.3);
        }

        /* Specific styling for remove button */
        .actions-cell button[name="remove"] {
            background-color: var(--button-remove-bg);
            color: var(--white-pop);
        }
        .actions-cell button[name="remove"]:hover {
            background-color: var(--button-remove-hover);
            transform: translateY(-2px);
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.3);
        }
        
        /* Styling for the "Only X in stock" message */
        td p { 
            margin: 0; /* Remove default paragraph margin */
            font-size: 0.75rem;
            color: var(--danger-red);
            font-weight: 500;
            white-space: normal; /* Allow text to wrap */
        }

        /* Styling for the total amount row in the cart table */
        .total-row td {
            font-weight: 600;
            text-align: right;
            padding-top: 20px;
            padding-bottom: 20px;
            background-color: rgba(255, 255, 255, 0.12); /* Slightly distinct background */
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            color: var(--white-pop);
        }
        .total-amount {
            color: var(--price-color); /* Use the price color for total */
            font-size: 1.3rem;
        }

        /* Styling for the checkout section */
        .checkout-section {
            background-color: var(--checkout-bg);
            padding: 25px;
            border-radius: 15px;
            margin-top: 30px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(8px);
            text-align: left; /* Align form labels and inputs to the left */
        }

        .checkout-section h2 {
            color: var(--white-pop);
            font-size: 1.5rem;
            margin-bottom: 15px;
            font-weight: 600;
        }
        /* Styling for form labels within checkout section */
        .form-label {
            display: block;
            margin-bottom: 10px;
            font-weight: 500;
            color: var(--light-font);
        }

        /* Styling for text input fields (delivery address) */
        input[type="text"] {
            width: calc(100% - 22px); /* Account for padding */
            padding: 12px 15px;
            margin-bottom: 20px;
            border: 1px solid var(--input-border);
            border-radius: 8px;
            background-color: var(--input-bg);
            color: var(--ghost-white);
            font-size: 0.95rem;
            box-shadow: inset 0 2px 5px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }
        input[type="text"]::placeholder {
            color: var(--cool-gray);
        }
        input[type="text"]:focus {
            outline: none;
            border-color: var(--true-blue);
            background-color: rgba(255, 255, 255, 0.2);
        }

        /* Styling for payment option radio buttons */
        .payment-options label {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            color: var(--light-font);
            font-weight: 400;
            cursor: pointer;
            font-size: 1rem;
        }
        .payment-options input[type="radio"] {
            margin-right: 10px;
            /* Custom radio button styling */
            appearance: none; /* Hide default radio button */
            width: 18px;
            height: 18px;
            border: 2px solid var(--true-blue);
            border-radius: 50%;
            background-color: var(--input-bg);
            position: relative;
            cursor: pointer;
            transition: background-color 0.2s ease, border-color 0.2s ease;
        }
        .payment-options input[type="radio"]:checked {
            background-color: var(--true-blue);
            border-color: var(--true-blue);
        }
        .payment-options input[type="radio"]:checked::after {
            content: '';
            width: 8px;
            height: 8px;
            background-color: var(--white-pop);
            border-radius: 50%;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        /* Container for checkout buttons */
        .checkout-buttons {
            margin-top: 30px;
            text-align: center;
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
        }
        /* General styling for checkout buttons */
        .checkout-buttons button {
            padding: 14px 25px;
            font-size: 1.1rem;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
            transition: background-color 0.3s ease, transform 0.2s ease;
        }
        /* Specific styling for 'Proceed to Payment' button */
        .proceed-button {
            background-color: var(--proceed-button-bg);
            color: var(--white-pop);
        }
        .proceed-button:hover {
            background-color: var(--proceed-button-hover);
            transform: translateY(-2px);
        }
        /* Specific styling for 'Cancel' button */
        .cancel-button {
            background-color: var(--cancel-button-bg);
            color: var(--white-pop);
        }
        .cancel-button:hover {
            background-color: var(--cancel-button-hover);
            transform: translateY(-2px);
        }

        /* Responsive adjustments for various screen sizes */
        @media (min-width: 768px) {
            body {
                padding-top: 50px;
                padding-bottom: 50px;
            }
            .container {
                max-width: 900px;
                padding: 30px;
            }
            h1 {
                font-size: 3.5rem;
                margin-bottom: 40px;
            }
            .back-link {
                padding: 12px 25px;
                font-size: 1.05rem;
                margin-bottom: 30px;
                margin-left: 0; /* Reset for larger screens */
            }
            .stock-error {
                padding: 20px;
                font-size: 1.1rem;
            }
            th, td {
                padding: 18px;
                font-size: 1rem;
            }
            input[type="number"] {
                width: 90px;
                padding: 12px;
                font-size: 1rem;
            }
            .actions-cell {
                gap: 15px;
            }
            .actions-cell button {
                padding: 10px 18px;
                font-size: 0.9rem;
            }
            td p {
                font-size: 0.85rem;
            }
            .total-amount {
                font-size: 1.5rem;
            }
            .checkout-section {
                padding: 35px;
            }
            .checkout-section h2 {
                font-size: 1.8rem;
                margin-bottom: 20px;
            }
            input[type="text"] {
                padding: 15px;
                margin-bottom: 25px;
                font-size: 1.05rem;
            }
            .payment-options label {
                font-size: 1.1rem;
            }
            .checkout-buttons button {
                padding: 16px 30px;
                font-size: 1.2rem;
            }
        }

        /* Responsive adjustments for screens smaller than 768px (tablets and mobile) */
        @media (max-width: 767px) {
            /* General table adjustments for smaller screens */
            table {
                display: block; /* Make the table a block element */
                width: 100%; /* Ensure it takes full width */
                overflow-x: auto; /* Allow horizontal scrolling if absolutely necessary for wider content */
                -webkit-overflow-scrolling: touch; /* Smooth scrolling on iOS */
            }

            thead {
                display: none; /* Hide the table header on small screens */
            }

            tbody, tr {
                display: block; /* Make tbody and tr block elements */
                width: 100%; /* Take full width */
            }

            tr {
                margin-bottom: 15px; 
                border: 1px solid rgba(255, 255, 255, 0.1);
                border-radius: 15px; /* Retain rounded corners for each "card" */
                background-color: rgba(255, 255, 255, 0.08); /* Apply base background */
                box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
                padding: 15px; /* Add padding inside each "card" */
            }

            /* Remove specific border-radius for cells when rows are stacked */
            tr:last-child td:first-child,
            tr:last-child td:last-child {
                border-bottom-left-radius: 0;
                border-bottom-right-radius: 0;
            }

            td {
                display: flex; 
                justify-content: space-between; /* Space out label and value */
                align-items: center; /* Vertically align items */
                padding: 10px 0; /* Adjust padding for stacked cells */
                text-align: right; /* Align values to the right */
                border-bottom: 1px dashed rgba(255, 255, 255, 0.05); /* Lighter separator */
                font-size: 0.9rem; /* Slightly smaller font size */
            }

            td:last-child {
                border-bottom: none; /* Remove border from the last cell in a stacked row */
                padding-bottom: 0;
            }

            td::before {
                /* This creates the "label" for each piece of data on mobile */
                content: attr(data-label); /* Get the label from a data-attribute */
                font-weight: 600; /* Make the label bold */
                text-align: left; /* Align label to the left */
                flex-basis: 40%; /* Allocate space for the label */
                flex-shrink: 0; /* Prevent label from shrinking */
                color: var(--cool-gray); /* Color for the label */
                font-size: 0.95rem;
            }

            /* Special handling for actions and quantity cells */
            td.actions-cell {
                flex-direction: row; /* Stack buttons vertically */
                align-items: center; /* Align actions to the left */
                justify-content: space-between;
                gap: 10px; /* Space between stacked action items */
                padding-top: 15px; /* More space above actions */
            }
            
            td.actions-cell::before {
                align-self: center; /* Align the "Actions" label to the start */
                margin-bottom: 0; /* Space between label and buttons */
            }

            td.actions-cell form {
		flex-direction: row; /* Keep forms in a row */
                width: auto; /* Make forms take full width */
                justify-content: flex-end; /* Align content within forms to start */
            }

	    /* Ensure remove button aligns correctly on mobile */
            td.actions-cell form button[name="remove"] {
                margin-left: auto; /* Push remove button to the right */
            }

            input[type="number"] {
                width: 70px; /* Adjust width for quantity input */
                text-align: center;
            }

            /* Price and Subtotal alignment for mobile */
            td:nth-of-type(2), /* Price column */
            td:nth-of-type(4) { /* Subtotal column */
                text-align: right; /* Keep price values right-aligned */
            }

            /* Total row specific styling for mobile */
            .total-row {
                background-color: transparent; /* Remove background, as it's now a standalone element */
                display: flex; /* Use flex for total row */
                justify-content: space-between; /* Space out total label and amount */
                align-items: center;
                padding: 15px;
                margin-top: 20px;
                border: 1px solid rgba(255, 255, 255, 0.1);
                border-radius: 15px;
                box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
                backdrop-filter: blur(8px);
                background-color: rgba(255, 255, 255, 0.12); /* Slightly distinct background */
                font-size: 1.1rem;
            }

            .total-row td {
                border-bottom: none; /* Remove borders from total row cells */
                padding: 0; /* Remove internal padding */
                text-align: left; /* Revert text alignment */
                flex-basis: auto; /* Reset flex basis */
                font-size: inherit; /* Inherit font size */
            }

            .total-row td:first-child {
                font-weight: 600; /* Bold the "Total Amount" label */
                color: var(--white-pop);
            }
            .total-row td:first-child::before {
                content: none; /* Hide generated label for total amount row */
            }

            .total-amount {
                font-size: 1.4rem;
                font-weight: 700;
                text-align: right;
            }
	    td[data-label="Value"]::before {
                content: none;
            }
        }
        
        @media (max-width: 480px) {
            .back-link {
                padding: 8px 15px;
                font-size: 0.85rem;
            }
            h1 {
                font-size: 2.2rem;
            }
            /* Adjust padding and font size for smaller screens within the stacked table cells */
            td {
                padding: 8px 0;
                font-size: 0.8rem;
            }
            td::before {
                font-size: 0.85rem;
            }
            input[type="number"] {
                width: 60px;
                padding: 6px;
                font-size: 0.8rem;
            }
            .actions-cell {
                gap: 8px;
            }
            .actions-cell form {
                gap: 5px;
            }
            .actions-cell button {
                padding: 7px 12px;
                font-size: 0.75rem;
            }
            td p {
                font-size: 0.7rem;
            }
            .total-row {
                font-size: 1rem;
                padding: 12px;
            }
            .total-amount {
                font-size: 1.2rem;
            }
            .checkout-section {
                padding: 20px;
            }
            .checkout-section h2 {
                font-size: 1.3rem;
            }
            input[type="text"] {
                padding: 10px;
                font-size: 0.9rem;
            }
            .payment-options label {
                font-size: 0.9rem;
            }
            .checkout-buttons button {
                padding: 10px 18px;
                font-size: 0.95rem;
                margin: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Your Cart</h1>
        <a href="BrowseItemsPage.php" class="back-link">&larr; Back to Browse Categories</a>

        <?php
        // Display stock error message if it exists.
        if ($stockError): ?>
            <div class="stock-error"><?= $stockError ?></div>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Subtotal</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Check if the cart is empty.
                if (empty($items)): ?>
                    <tr><td colspan="5" style="text-align: center; padding: 20px; color: var(--cool-gray);">Your cart is empty. Why not add some great deals?</td></tr>
                <?php else: ?>
                    <?php
                    // Loop through each item in the cart and display its details.
                    foreach ($items as $item): ?>
                    <tr>
                        <td data-label="Item"><?= htmlspecialchars($item['itemName']) ?></td>
                        <td data-label="Price">R<?= number_format($item['price'], 2) ?></td>
                        <td data-label="Quantity">
                            <form action="" method="POST">
                                <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                                <input type="number" name="quantity" value="<?= $item['quantity'] ?>" min="1" max="<?= htmlspecialchars($item['stock_quantity']) ?>">
                                <button type="submit" name="update">Update</button>
                            </form>
                            <?php
                            // Display a warning if the quantity in cart exceeds available stock.
                            if ($item['quantity'] > $item['stock_quantity']): ?>
                                <p>Only <?= htmlspecialchars($item['stock_quantity']) ?> in stock.</p>
                            <?php endif; ?>
                        </td>
                        <td data-label="Subtotal">R<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                        <td data-label="Actions" class="actions-cell">
                            <form action="" method="POST">
                                <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                                <button type="submit" name="remove">Remove</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="total-row">
                        <td data-label="Total Amount" colspan="3"><strong>Total Amount:</strong></td>
                        <td data-label="Value" colspan="2" class="total-amount"><strong>R<?= number_format($totalAmount, 2) ?></strong></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <?php
        // Display the checkout section only if the cart is not empty.
        if (!empty($items)): ?>
        <div class="checkout-section">
            <form method="POST" action="paymentGateway.php">
                <h2 class="form-label">Your Delivery Address:</h2>
                <input type="text" name="delivery_address" id="delivery_address" required placeholder="Enter your delivery address">

                <h2 class="form-label">Select Payment Method:</h2>
                <div class="payment-options">
                    <label for="payfast"><input type="radio" name="payment_method" value="payfast" id="payfast" required>PayFast</label>
                    <label for="cod"><input type="radio" name="payment_method" value="cod" id="cod">Cash on Delivery</label>
                    <label for="eft"><input type="radio" name="payment_method" value="instant_eft" id="eft">Instant EFT</label>
                </div>

                <input type="hidden" name="amount" value="<?= htmlspecialchars($totalAmount) ?>">

                <div class="checkout-buttons">
                    <button type="submit" name="payment_action" value="proceed" class="proceed-button">Proceed to Payment</button>
            		<button type="submit" name="payment_action" value="cancel" class="cancel-button" formnovalidate>Cancel</button>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>