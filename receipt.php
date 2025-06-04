<?php
session_start(); // Starts or resumes a session to manage user data across requests.
require_once 'db.php'; // Includes the database connection file.

// Checks if the user is logged in as a buyer; otherwise, redirects to the login page.
if (!isset($_SESSION['buyer_id'])) {
    header("Location: LoginPage.php");
    exit();
}

$buyer_id = $_SESSION['buyer_id']; // Retrieves the buyer's ID from the session.

// Validates and sanitizes the order ID from the URL query parameter.
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

// Redirects to the 'My Purchases' page with an error if the order ID is invalid.
if ($order_id <= 0) {
    $_SESSION['error_message'] = "Invalid order ID provided.";
    header("Location: MyPurchases.php");
    exit();
}

// Prepares a SQL query to fetch detailed information for a specific order and its items.
// It joins 'orders', 'order_items', 'products', and 'users' tables to gather all necessary data.
$orderQuery = "
    SELECT
        o.order_date,         -- Date of the order
        o.delivery_address,   -- Delivery address for the order
        o.total_amount,       -- Total amount of the order (excluding courier cost in this context, check payment details)
        o.courier_cost,       -- Courier cost for the order
        oi.quantity,          -- Quantity of each product in the order
        p.itemName AS product_name, -- Name of the product
        p.price,              -- Price of the product at the time of order
        u.username AS seller_name   -- Username of the seller
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    JOIN users u ON p.seller_id = u.id
    WHERE o.id = ? AND o.buyer_id = ?
";
$orderStmt = $conn->prepare($orderQuery); // Prepares the SQL statement.

// Handles errors if the statement preparation fails.
if (!$orderStmt) {
    error_log("Failed to prepare statement for receipt: " . $conn->error); // Logs the actual database error.
    $_SESSION['error_message'] = "Database error occurred. Please try again.";
    header("Location: MyPurchases.php");
    exit();
}

// Binds the order ID and buyer ID parameters to the prepared statement.
$orderStmt->bind_param("ii", $order_id, $buyer_id);
$orderStmt->execute(); // Executes the query.
$orderResult = $orderStmt->get_result(); // Gets the result set from the executed query.

// Checks if any rows were returned; if not, redirects with an error message.
if ($orderResult->num_rows === 0) {
    $_SESSION['error_message'] = "Order not found or you do not have permission to view this receipt.";
    header("Location: MyPurchases.php");
    exit();
}

$order_items = []; // Initializes an array to store order items.
// Fetches each row from the result set and adds it to the $order_items array.
while ($row = $orderResult->fetch_assoc()) {
    $order_items[] = $row;
}

// Sets $order_info to the first item in $order_items (contains general order details).
// This assumes all items in the same order share the same order_date, delivery_address, total_amount, and courier_cost.
$order_info = !empty($order_items) ? $order_items[0] : null;

// Defines hardcoded company information for the receipt.
$company_name = "QuickBuy";
$company_number = "022 487 2258";
$company_address = "31 Smuts Str, Malmesbury, 7300";

$orderStmt->close(); // Closes the prepared statement.
$conn->close(); // Closes the database connection.
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Receipt - QuickBuy</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* CSS variables for consistent theming based on QuickBuy's palette. */
        :root {
            /* Main blues from Browse Categories */
            --true-blue: #0466c8ff;
            --sapphire: #0353a4ff;
            --yale-blue: #023e7dff;
            --oxford-blue: #002855ff;
            --oxford-blue-2: #001845ff;
            --oxford-blue-3: #001233ff;

            /* Greens & Deeper Blues from Browse Categories */
            --caribbean-current: #006466ff;
            --midnight-green: #065a60ff;
            --midnight-green-2: #0b525bff;
            --midnight-green-3: #144552ff;
            --prussian-blue: #212f45ff;
            --deep-space-blue: #0d1b2a;

            /* Neutrals from Browse Categories */
            --gunmetal: #30343fff;
            --ghost-white: #fafaffff;
            --delft-blue: #273469ff;
            --space-cadet: #1e2749ff;
            --paynes-gray: #5c677dff;
            --slate-gray: #7d8597ff;
            --cool-gray: #979dacff;
            --charcoal: #1b3a4bff;

            /* Accent from Browse Categories */
            --white-pop: #FFFFFF;
            --dark-font: #333;
            --light-font: #fefefe;

            /* Receipt-specific colors (adapted to QuickBuy palette) */
            --primary-blue-receipt: var(--oxford-blue); /* Darker blue for background and headings */
            --secondary-blue-receipt: var(--true-blue); /* Lighter blue for background gradient & accents */
            --receipt-bg: var(--white-pop); /* White background for the receipt container */
            --text-dark-receipt: var(--dark-font); /* Darker text for general content */
            --text-medium-receipt: var(--paynes-gray); /* Medium grey for details */
            --text-light-receipt: var(--light-font); /* Light text for headers/buttons */
            --border-light-receipt: var(--cool-gray); /* Light border for table rows */
            --border-medium-receipt: var(--slate-gray); /* Medium border for general elements */
            --header-bg-receipt: var(--oxford-blue-2); /* Darker blue for table headers */
            --total-bg-receipt: #f2f2f2; /* Light grey for total row background */
            --button-green-receipt: var(--midnight-green); /* Green for success/call to action */
            --button-green-hover-receipt: var(--caribbean-current);
            --button-blue-receipt: var(--sapphire); /* Blue for primary actions */
            --button-blue-hover-receipt: var(--yale-blue);
        }

        /* Universal Box-Sizing for consistent layout. */
        html {
            box-sizing: border-box;
        }
        *, *::before, *::after {
            box-sizing: inherit;
        }

        /* Base body styles, including a gradient background with animation. */
        body {
            background: linear-gradient(135deg,
                var(--deep-space-blue),
                var(--midnight-green-3),
                var(--prussian-blue),
                var(--oxford-blue),
                var(--true-blue)
            );
            background-size: 300% 300%;
            animation: bgShift 25s ease infinite;
            min-height: 100vh;
            font-family: 'Poppins', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            color: var(--text-dark-receipt);
        }

        /* Keyframe animation for the background gradient. */
        @keyframes bgShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Styles for the main receipt container. */
        .container {
            background-color: var(--receipt-bg);
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
            width: 95%;
            max-width: 800px;
            border: 4px solid var(--receipt-bg);
            animation: fadeInScale 0.7s ease-out forwards; /* Fade-in and scale animation on load. */
        }

        /* Keyframe animation for the container's fade-in and scale effect. */
        @keyframes fadeInScale {
            from { opacity: 0; transform: scale(0.95) translateY(20px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }

        /* Styles for the company information section. */
        .company-info {
            text-align: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px dashed var(--border-medium-receipt); /* Dashed border for a receipt aesthetic. */
        }

        /* Styles for the company name within the receipt. */
        .company-name {
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--primary-blue-receipt);
            margin-bottom: 5px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        /* Styles for general company details (e.g., address, phone number). */
        .company-details {
            font-size: 0.95rem;
            color: var(--text-medium-receipt);
            margin-bottom: 3px;
        }

        /* Styles for the main heading of the receipt. */
        h1 {
            color: var(--primary-blue-receipt);
            text-align: center;
            margin-bottom: 30px;
            font-size: 2.5rem;
            font-weight: 600;
            position: relative;
            padding-bottom: 10px;
        }

        /* Pseudo-element for an underline effect on the main heading. */
        h1::after {
            content: '';
            position: absolute;
            left: 50%;
            bottom: 0;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background-color: var(--secondary-blue-receipt);
            border-radius: 2px;
        }

        /* General paragraph styles. */
        p {
            line-height: 1.6;
            margin-bottom: 10px;
            color: var(--text-dark-receipt);
            font-size: 0.95rem;
        }

        /* Strong/bold text emphasis. */
        strong {
            font-weight: 600;
            color: var(--text-medium-receipt);
        }

        /* Table styles for displaying order items. */
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 30px;
            background-color: var(--receipt-bg);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            border: 1px solid var(--border-light-receipt);
        }

        /* Table header cell styles. */
        thead th {
            background-color: var(--header-bg-receipt);
            color: var(--text-light-receipt);
            padding: 15px 12px;
            text-align: left;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            font-size: 0.9rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        /* Table body cell styles. */
        tbody td {
            padding: 12px;
            border-bottom: 1px solid var(--border-light-receipt);
            font-size: 0.85rem;
            color: var(--text-dark-receipt);
            line-height: 1.5;
        }

        /* Alternating row background color for readability. */
        tbody tr:nth-child(even) {
            background-color: #fcfcfc;
        }

        /* Removes bottom border from the last row in the table body. */
        tbody tr:last-child td {
            border-bottom: none;
        }

        /* Right-aligns numeric columns for better presentation of currency. */
        tbody tr td:nth-last-child(1),
        tbody tr td:nth-last-child(2) {
            text-align: right;
        }

        /* Styles for the grand total row in the table. */
        .grand-total-row td {
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--primary-blue-receipt);
            background-color: var(--total-bg-receipt);
            padding-top: 15px;
            padding-bottom: 15px;
            border-top: 2px solid var(--border-medium-receipt);
        }
        /* Ensures strong text within the grand total row matches the primary blue. */
        .grand-total-row td strong {
            color: var(--primary-blue-receipt);
            font-size: 1.2rem;
        }
        /* Specific styling for the grand total amount cell to highlight it. */
        .grand-total-row td:last-child {
            background-color: var(--secondary-blue-receipt);
            color: var(--white-pop);
            border-radius: 0 0 12px 0;
            text-align: right;
            padding-right: 20px;
        }
        /* Ensures strong text in the grand total amount cell is white. */
        .grand-total-row td:last-child strong {
            color: var(--white-pop);
        }
        /* Styling for the courier cost row in the table. */
        .table-courier-cost td {
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--text-dark-receipt);
            padding: 10px 12px;
            background-color: #f5f5f5;
            border-top: 1px dashed var(--border-light-receipt);
        }
        /* Right-aligns the courier cost value. */
        .table-courier-cost td:last-child {
            text-align: right;
        }

        /* Styles for the action buttons container. */
        .actions {
            margin-top: 40px;
            text-align: center;
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        /* General styles for buttons. */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 25px;
            text-decoration: none;
            color: var(--white-pop);
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
            min-width: 160px;
        }

        /* Specific styles for the "Continue Shopping" button. */
        .btn:first-child {
            background-color: var(--button-green-receipt);
        }

        /* Hover effect for the "Continue Shopping" button. */
        .btn:first-child:hover {
            background-color: var(--button-green-hover-receipt);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.25);
        }

        /* Specific styles for the "Print Receipt" button. */
        .btn:last-child {
            background-color: var(--button-blue-receipt);
        }

        /* Hover effect for the "Print Receipt" button. */
        .btn:last-child:hover {
            background-color: var(--button-blue-hover-receipt);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.25);
        }

        /* Print-specific styles to optimize the receipt for printing. */
        @media print {
            body {
                background: none;
                print-color-adjust: exact;
            }
            body * {
                visibility: hidden; /* Hides everything initially. */
            }

            .container, .container * {
                visibility: visible; /* Makes the receipt container and its contents visible. */
                color: #000 !important; /* Forces black text for better print readability. */
            }

            .container {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                border: none;
                box-shadow: none;
                border-radius: 0;
                padding: 0;
                margin: 0;
            }

            /* Ensures key text elements are black when printed. */
            .company-name, h1, .grand-total-row td strong {
                color: #000 !important;
            }

            /* Adjusts borders and backgrounds for print. */
            .company-info, h1::after, table, thead th, tbody td, .grand-total-row td, .table-courier-cost td {
                border-color: #999 !important;
                background-color: transparent !important;
            }
            /* Maintains a subtle background for the total amount on print. */
            .grand-total-row td:last-child {
                background-color: #f2f2f2 !important;
                color: #000 !important;
            }

            .actions {
                display: none; /* Hides action buttons during printing. */
            }
            /* Adjusts table padding for print readability. */
            thead th, tbody td {
                padding: 8px 10px !important;
            }
        }

        /* Responsive adjustments for smaller screens (max-width: 768px). */
        @media (max-width: 768px) {
            .container {
                padding: 25px;
                border-radius: 12px;
            }
            .company-name {
                font-size: 1.8rem;
            }
            h1 {
                font-size: 2rem;
                margin-bottom: 25px;
            }
            thead th {
                padding: 12px 10px;
                font-size: 0.85rem;
            }
            tbody td {
                padding: 10px;
                font-size: 0.8rem;
            }
            .grand-total-row td {
                font-size: 1rem;
                padding-top: 12px;
                padding-bottom: 12px;
            }
            .grand-total-row td strong {
                font-size: 1.1rem;
            }
            .btn {
                padding: 10px 20px;
                font-size: 0.9rem;
                min-width: unset;
            }
            .actions {
                gap: 15px;
            }
        }

        /* Responsive adjustments for very small screens (max-width: 576px). */
        @media (max-width: 576px) {
            body {
                padding: 15px;
            }
            .container {
                padding: 20px;
                border-radius: 10px;
            }
            .company-name {
                font-size: 1.5rem;
            }
            .company-details {
                font-size: 0.85rem;
            }
            h1 {
                font-size: 1.8rem;
                margin-bottom: 20px;
            }
            /* Makes table cells stack vertically for better mobile display. */
            table, thead, tbody, th, td, tr {
                display: block;
            }
            thead tr {
                position: absolute;
                top: -9999px; /* Hides table headers. */
                left: -9999px;
            }
            tr {
                border: 1px solid var(--border-light-receipt);
                margin-bottom: 10px;
                border-radius: 8px;
                overflow: hidden;
            }
            tbody td {
                border: none;
                padding: 10px;
                text-align: left;
                font-size: 0.9rem;
                display: flex;
                flex-direction: column;
                align-items: flex-start;
            }
            /* Adds data labels as pseudo-elements for stacked table cells. */
            tbody td::before {
                content: attr(data-label);
                display: block;
                position: static;
                width: auto;
                text-align: left;
                font-weight: 600;
                color: var(--primary-blue-receipt);
                margin-bottom: 3px;
                white-space: normal;
                overflow: visible;
                text-overflow: clip;
            }
            /* Specific data-labels for each column. */
            td:nth-of-type(1)::before { content: "Product:"; }
            td:nth-of-type(2)::before { content: "Seller:"; }
            td:nth-of-type(3)::before { content: "Quantity:"; }
            td:nth-of-type(4)::before { content: "Price (Each):"; }
            td:nth-of-type(5)::before { content: "Total:"; }

            /* Adjusts courier cost row for mobile. */
            .table-courier-cost td {
                display: flex;
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
                padding: 10px;
                font-size: 0.95rem;
            }
            .table-courier-cost td::before {
                display: none; /* Hides generated label for courier cost. */
            }
            .table-courier-cost td strong {
                color: var(--text-dark-receipt);
            }

            /* Adjusts grand total row for mobile. */
            .grand-total-row td {
                display: flex;
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
                padding: 15px 10px;
                font-size: 1.05rem;
            }
            .grand-total-row td::before {
                display: none; /* Hides generated label for grand total. */
            }
            .grand-total-row td:last-child {
                text-align: right;
            }
            .grand-total-row td strong {
                color: inherit;
            }

            /* Stacks action buttons vertically on very small screens. */
            .actions {
                flex-direction: column;
                gap: 10px;
                margin-top: 30px;
            }
            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="company-info">
            <div class="company-name"><?= htmlspecialchars($company_name) ?></div>
            <div class="company-details">Tel: <?= htmlspecialchars($company_number) ?></div>
            <div class="company-details">Address: <?= htmlspecialchars($company_address) ?></div>
            <div class="company-details">Buy and sell directly with people near you</div>
        </div>
        <h1>🧾 Order Receipt</h1>

        <?php if ($order_info): // Checks if order information is available to display. ?>
            <p><strong>Order ID:</strong> #<?= htmlspecialchars($order_id) ?></p>
            <p><strong>Order Date:</strong> <?= htmlspecialchars($order_info['order_date']) ?></p>
            <p><strong>Delivery Address:</strong> <?= htmlspecialchars($order_info['delivery_address']) ?></p>

            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Seller</th>
                        <th>Quantity</th>
                        <th>Price (Each)</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $grand_total = $order_info['courier_cost']; // Initializes grand total with courier cost.
                    foreach ($order_items as $item): // Iterates through each item in the order.
                        $item_total = $item['price'] * $item['quantity']; // Calculates total for the current item.
                        $grand_total += $item_total; // Adds item total to the running grand total.
                    ?>
                        <tr>
                            <td data-label="Product:"><?= htmlspecialchars($item['product_name']) ?></td>
                            <td data-label="Seller:"><?= htmlspecialchars($item['seller_name']) ?></td>
                            <td data-label="Quantity:"><?= $item['quantity'] ?></td>
                            <td data-label="Price (Each):">R<?= number_format($item['price'], 2) ?></td>
                            <td data-label="Total:">R<?= number_format($item_total, 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="table-courier-cost">
                        <td colspan="4"><strong>Courier Cost:</strong></td>
                        <td><strong>R<?= number_format($order_info['courier_cost'], 2) ?></strong></td>
                    </tr>
                    <tr class="grand-total-row">
                        <td colspan="4"><strong>Grand Total:</strong></td>
                        <td><strong>R<?= number_format($grand_total, 2) ?></strong></td>
                    </tr>
                </tbody>
            </table>

            <div class="actions">
                <a href="BrowseItemsPage.php" class="btn">Continue Shopping</a>
                <button onclick="window.print()" class="btn">Print Receipt</button>
            </div>
        <?php else: // Displays a message if no order details are found. ?>
            <p style="text-align: center; color: var(--text-dark-receipt); margin-top: 30px;">
                No order details found for this receipt.
            </p>
            <div class="actions">
                <a href="MyPurchases.php" class="btn">Back to My Purchases</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>