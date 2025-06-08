<?php
/**
 * @file CashOnDeliveryConfirmation.php
 * @brief This page confirms a Cash on Delivery (COD) order, displaying order details
 * and instructions for the buyer.
 *
 * It retrieves order information and associated items from the database
 * based on the `order_id` passed in the URL.
 *
 * @uses session_start() To start the PHP session for user authentication.
 * @uses require_once 'db.php' To include the database connection file.
 */

session_start(); // Start the PHP session to access session variables.
require_once 'db.php'; // Include the database connection.

// --- Authentication and Input Validation ---
/**
 * @brief Redirects to the login page if the buyer is not logged in or if
 * the 'order_id' is missing from the URL parameters.
 */
if (!isset($_SESSION['buyer_id']) || !isset($_GET['order_id'])) {
    header("Location: LoginPage.php"); // Redirect to the login page.
    exit(); // Terminate script execution after redirection.
}

/**
 * @var int $orderId The order ID, retrieved from the URL query parameter.
 * @remark It's good practice to sanitize this input further if it were used
 * in contexts other than a prepared statement's bind_param.
 */
$orderId = $_GET['order_id'];

// --- Fetch Order Details ---
/**
 * @brief SQL query to fetch order details (username, email, address, cart amount, courier cost)
 * by joining the 'payments' and 'users' tables.
 * @var string $orderQuery
 */
$orderQuery = "SELECT u.username, u.email, u.address, p.cart_amount, p.courier_cost
               FROM payments p
               JOIN users u ON p.buyer_id = u.id
               WHERE p.order_id = ?";
/**
 * @var mysqli_stmt|false $orderStmt Prepared statement for fetching order details.
 */
$orderStmt = $conn->prepare($orderQuery);

/**
 * @brief Error handling for the prepared statement creation.
 * Logs the error and displays a generic message to the user.
 */
if ($orderStmt === false) {
    error_log("Failed to prepare order query: " . $conn->error); // Log the database error.
    echo "An error occurred. Please try again later."; // Display a user-friendly error.
    exit(); // Terminate script.
}

$orderStmt->bind_param("i", $orderId); // Bind the integer order ID parameter.
$orderStmt->execute(); // Execute the prepared statement.
/**
 * @var mysqli_result $orderResult Result set from the order details query.
 */
$orderResult = $orderStmt->get_result();

/**
 * @brief Checks if an order was found. If not, displays an error and exits.
 */
if ($orderResult->num_rows == 0) {
    echo "Error: Order not found."; // Display error if no order is found.
    exit(); // Terminate script.
}

/**
 * @var array<string, mixed> $orderData Associative array containing the fetched order details.
 */
$orderData = $orderResult->fetch_assoc();

// Assign fetched data to variables for easier access in HTML.
$buyerName = $orderData['username'];
$buyerEmail = $orderData['email'];
$deliveryAddress = $orderData['address'];
$itemCost = $orderData['cart_amount'];
$courierCost = $orderData['courier_cost'];
/**
 * @var float $totalAmount Calculates the total amount due (item cost + courier cost).
 */
$totalAmount = $itemCost + $courierCost;

$orderStmt->close(); // Close the statement for order details.

// --- Fetch Order Items ---
/**
 * @brief SQL query to fetch individual items within the order, including item name, quantity, and price.
 * Joins 'order_items' and 'products' tables.
 * @var string $orderItemsQuery
 */
$orderItemsQuery = "SELECT p.itemName, oi.quantity, p.price
                    FROM order_items oi
                    JOIN products p ON oi.product_id = p.id
                    WHERE oi.order_id = ?";
/**
 * @var mysqli_stmt|false $orderItemsStmt Prepared statement for fetching order items.
 */
$orderItemsStmt = $conn->prepare($orderItemsQuery);

/**
 * @var array<array<string, mixed>> $orderItems Array to store the fetched order items.
 */
$orderItems = []; // Initialize as an empty array.

/**
 * @brief Error handling for the prepared statement for order items.
 * Logs the error; in this case, it proceeds with an empty order items array.
 */
if ($orderItemsStmt === false) {
    error_log("Error preparing order items query: " . $conn->error); // Log the error.
    // In a real application, you might want to display a more prominent error or redirect.
} else {
    $orderItemsStmt->bind_param("i", $orderId); // Bind the integer order ID parameter.
    $orderItemsStmt->execute(); // Execute the prepared statement.
    /**
     * @var mysqli_result $orderItemsResult Result set from the order items query.
     */
    $orderItemsResult = $orderItemsStmt->get_result();

    // Fetch all order items into the $orderItems array.
    while ($item = $orderItemsResult->fetch_assoc()) {
        $orderItems[] = $item;
    }
    $orderItemsStmt->close(); // Close the statement for order items.
}

$conn->close(); // Close the database connection.
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cash on Delivery Confirmation - QuickBuy</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /*
        * @brief CSS styles for the Cash on Delivery Confirmation page.
        *
        * This section defines a modern, visually appealing "Galactic Market" theme
        * with a gradient background, glassmorphism effects, and distinct color palettes
        * for various elements like containers, text, tables, and buttons.
        */

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
            --ghost-white: #fafaffff; /* Pure white for popping text */
            --delft-blue: #273469ff;
            --space-cadet: #1e2749ff;
            --paynes-gray: #5c677dff;
            --slate-gray: #7d8597ff;
            --cool-gray: #979dacff;
            --charcoal: #1b3a4bff;

            /* Accent Colors (Specific for success/confirmation) */
            --success-green: #28a745; /* Bootstrap success green */
            --success-green-hover: #218838; /* Darker green for hover */
            --warning-orange: #ffc107; /* Bootstrap warning orange */
            --warning-orange-dark: #e0a800; /* Darker orange for hover */
            --info-blue: #17a2b8; /* Bootstrap info blue */
            --cancel-red: #dc3545; /* Bootstrap danger red */
            --quickbuy-yellow: #ffdd00; /* Custom QuickBuy Yellow */
            --quickbuy-yellow-darker: #ccaa00;

            /* Page specific colors for the "Galactic Market" theme */
            --body-bg-start: var(--deep-space-blue); /* Darker blue */
            --body-bg-end: var(--oxford-blue-2); /* Lighter blue */
            --container-bg: rgba(255, 255, 255, 0.08); /* Semi-transparent white for glass effect */
            --container-border: rgba(255, 255, 255, 0.15); /* Lighter border for glass effect */
            --text-color: var(--ghost-white); /* White for most text */
            --heading-color: var(--true-blue); /* A vibrant blue for most headings */
            --table-header-bg: var(--oxford-blue); /* Darker blue for table header */
            --table-header-text: var(--ghost-white);
            --table-row-odd-bg: rgba(255, 255, 255, 0.03); /* Very light transparent for odd rows */
            --table-row-even-bg: rgba(255, 255, 255, 0.06); /* Slightly more transparent for even rows */
            --border-light: rgba(255, 255, 255, 0.1); /* Very light border for table lines */
            --important-text-color: var(--warning-orange); /* Orange for warnings */
            --important-bg-color: rgba(255, 193, 7, 0.1); /* Light transparent orange */
            --important-border-color: rgba(255, 193, 7, 0.4); /* More opaque orange border */
            --button-bg: var(--midnight-green);
            --button-hover-bg: var(--caribbean-current);
            --success-message-bg: rgba(40, 167, 69, 0.1);
            --success-message-border: var(--success-green);
        }

        /* Universal Box-Sizing for consistent layout */
        html {
            box-sizing: border-box;
        }
        *, *::before, *::after {
            box-sizing: inherit;
        }

        html, body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            overflow-x: hidden; /* Prevent horizontal scroll */
        }

        body {
            /* Gradient background with animation for a dynamic feel */
            background: linear-gradient(135deg,
                var(--deep-space-blue),
                var(--midnight-green-3),
                var(--prussian-blue),
                var(--oxford-blue),
                var(--true-blue)
            );
            background-size: 300% 300%; /* Larger background for animation */
            animation: bgShift 25s ease infinite; /* Animation for background movement */
            font-family: 'Poppins', sans-serif;
            color: var(--text-color); /* Default text color */
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 30px;
            box-sizing: border-box;
            line-height: 1.6;
        }

        @keyframes bgShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .container {
            width: 100%;
            max-width: 800px; /* Slightly wider for content */
            text-align: center;
            margin-bottom: 20px;
        }

        h1 {
            color: var(--ghost-white); /* White for the main title */
            margin-bottom: 30px;
            font-size: 2.8rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-shadow: 0 3px 6px rgba(0,0,0,0.4);
            animation: fadeIn 1s ease-out; /* Fade-in animation for title */
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .main-content-container {
            background-color: var(--container-bg); /* Transparent white */
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3); /* Deeper shadow */
            margin-bottom: 25px;
            border: 1px solid var(--container-border); /* Transparent border */
            text-align: left; /* Align text within this container to left */
            animation: slideIn 1s ease-out; /* Slide-in animation for content */
            backdrop-filter: blur(10px); /* Glassmorphism effect */
            color: var(--ghost-white); /* Set default text color inside to white */
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .main-content-container p {
            color: var(--text-color); /* White text */
            font-size: 1.05rem;
            margin-bottom: 15px;
        }

        .main-content-container h2 {
            color: var(--ghost-white); /* White headings */
            font-size: 1.6rem;
            margin-top: 25px;
            margin-bottom: 15px;
            border-bottom: 2px solid var(--border-light); /* Lighter border */
            padding-bottom: 8px;
            font-weight: 600;
        }

        .main-content-container p strong {
            color: var(--ghost-white); /* White strong text */
            font-weight: 600;
        }

        .important {
            color: var(--quickbuy-yellow); /* Yellow for important text */
            font-weight: 700;
            background-color: rgba(255, 221, 0, 0.1); /* Light transparent yellow background */
            padding: 15px;
            border-radius: 10px;
            border: 1px solid var(--quickbuy-yellow-darker); /* Darker yellow border */
            margin-top: 25px;
            display: block; /* Make it a block element to take full width */
            line-height: 1.5;
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.05);
            backdrop-filter: blur(5px); /* Subtle blur for important box */
        }

        .important-highlight {
            color: var(--quickbuy-yellow); /* Specific highlight for monetary amount in yellow */
            font-weight: 700;
        }

        .order-summary {
            margin-top: 30px;
        }

        .order-summary h2 {
            color: var(--ghost-white); /* White Order Summary heading */
        }

        .order-summary table {
            width: 100%;
            border-collapse: separate; /* Use separate to allow border-radius on cells */
            border-spacing: 0;
            margin-top: 20px;
            background-color: rgba(255, 255, 255, 0.05); /* Lighter transparent white for table background */
            border-radius: 12px;
            overflow: hidden; /* Ensures rounded corners on table content */
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: var(--ghost-white); /* White text in the table */
        }

        .order-summary table th,
        .order-summary table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-light); /* Lighter border */
            color: var(--ghost-white); /* White text for table content */
            font-size: 0.95rem;
        }

        .order-summary table th {
            background-color: var(--oxford-blue); /* Darker blue header */
            color: var(--ghost-white);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 1rem;
            letter-spacing: 0.5px;
        }

        /* Adjusting first/last column for rounded corners for consistency */
        .order-summary table th:first-child { border-top-left-radius: 12px; }
        .order-summary table th:last-child { border-top-right-radius: 12px; }
        .order-summary table tr:last-child td:first-child { border-bottom-left-radius: 12px; border-bottom: none; }
        .order-summary table tr:last-child td:last-child { border-bottom-right-radius: 12px; border-bottom: none; }
        .order-summary table tr:last-child td { border-bottom: none; } /* Remove border from last row */


        .order-summary table tr:nth-child(odd) {
            background-color: var(--table-row-odd-bg);
        }
        .order-summary table tr:nth-child(even) {
            background-color: var(--table-row-even-bg);
        }

        .order-summary table tfoot td {
            font-weight: 600;
            border-top: 2px solid var(--border-light);
            font-size: 1rem;
            padding-top: 18px;
            padding-bottom: 18px;
            color: var(--ghost-white); /* Ensure footer text is white and stands out */
        }
        .order-summary table tfoot tr:last-child td {
            border-bottom: none;
        }

        #thank-you-message-container {
            background-color: var(--success-message-bg); /* Light green background for success */
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            margin-top: 30px;
            color: var(--text-color); /* White text */
            border: 1px solid var(--success-message-border); /* Green border */
            line-height: 1.6;
            font-weight: 500;
            backdrop-filter: blur(5px); /* Subtle blur */
        }

        #thank-you-message-container p {
            margin-bottom: 0; /* Remove default paragraph margin */
            font-size: 1.05rem;
        }

        .back-button {
            background-color: var(--button-bg);
            color: var(--ghost-white);
            padding: 15px 30px;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            cursor: pointer;
            margin: 30px auto 0;
            display: block;
            transition: background 0.3s ease, transform 0.2s ease, box-shadow 0.2s ease;
            text-decoration: none;
            text-align: center;
            width: fit-content;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            font-weight: 600;
        }

        .back-button:hover {
            background-color: var(--button-hover-bg);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
        }

        /* Responsive adjustments for smaller screens */
        @media (max-width: 768px) {
            h1 {
                font-size: 2.2rem;
            }
            .main-content-container {
                padding: 25px;
            }
            .main-content-container h2 {
                font-size: 1.4rem;
            }
            .order-summary table th,
            .order-summary table td {
                padding: 12px;
                font-size: 0.9rem;
            }
            .order-summary table tfoot td {
                font-size: 0.95rem;
                padding-top: 15px;
                padding-bottom: 15px;
            }
            .back-button {
                padding: 12px 25px;
                font-size: 1rem;
            }
        }

        @media (max-width: 600px) {
            body {
                padding: 20px;
            }
            h1 {
                font-size: 1.8rem;
                margin-bottom: 20px;
            }
            .main-content-container {
                padding: 20px;
                border-radius: 12px;
            }
            .main-content-container p {
                font-size: 0.95rem;
            }
            .main-content-container h2 {
                font-size: 1.3rem;
                margin-top: 20px;
                margin-bottom: 10px;
            }
            .important {
                padding: 12px;
                font-size: 0.9rem;
            }
            .order-summary table th,
            .order-summary table td {
                padding: 10px;
                font-size: 0.85rem;
            }
            .order-summary table tfoot td {
                font-size: 0.9rem;
            }
            #thank-you-message-container {
                padding: 15px;
                font-size: 0.95rem;
            }
            .back-button {
                padding: 10px 20px;
                font-size: 0.95rem;
                margin-top: 25px;
                border-radius: 8px;
            }
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
        <h1>Cash on Delivery Confirmation</h1>
        <div class="main-content-container">
            <p>Dear **<?php echo htmlspecialchars($buyerName); ?>** (<a href="mailto:<?php echo htmlspecialchars($buyerEmail); ?>" style="color: var(--quickbuy-yellow);"><?php echo htmlspecialchars($buyerEmail); ?></a>), your order has been confirmed and will be delivered to the address below. Please have <span class="important-highlight">R<?php echo number_format($totalAmount, 2); ?></span> ready for payment upon delivery.</p>

            <h2>Delivery Address</h2>
            <p><strong><?php echo nl2br(htmlspecialchars($deliveryAddress)); ?></strong></p>
            <p class="important">
                **Important:** When our courier (**022 485 2258**) contacts you with a delivery timeframe, please ensure you are available at the delivery address to receive your order promptly. Failure to be available may result in a missed delivery, and a second delivery attempt may incur an additional courier fee.
            </p>

            <div class="order-summary">
                <h2>Order Summary for Order #<?php echo htmlspecialchars($orderId); ?></h2>
                <table>
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Quantity</th>
                            <th style="text-align: right;">Price</th>
                            <th style="text-align: right;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $subtotal = 0; // Initialize subtotal for order items.
                        /**
                         * @brief Loops through each item in the order to display its details.
                         */
                        foreach ($orderItems as $item):
                            $total = $item['price'] * $item['quantity']; // Calculate total for each item.
                            $subtotal += $total; // Add item total to subtotal.
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['itemName']); ?></td>
                            <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                            <td style="text-align: right;">R<?php echo number_format($item['price'], 2); ?></td>
                            <td style="text-align: right;">R<?php echo number_format($total, 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" style="text-align: right;"><strong>Subtotal:</strong></td>
                            <td style="text-align: right;">R<?php echo number_format($subtotal, 2); ?></td>
                        </tr>
                        <tr>
                            <td colspan="3" style="text-align: right;"><strong>Courier Cost:</strong></td>
                            <td style="text-align: right;">R<?php echo number_format($courierCost, 2); ?></td>
                        </tr>
                        <tr>
                            <td colspan="3" style="text-align: right;"><strong>Total Amount:</strong></td>
                            <td style="text-align: right;">R<?php echo number_format($totalAmount, 2); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div id="thank-you-message-container">
                <p>Thank you for your order. Our courier (**022 485 2258**) will contact you soon to arrange a delivery time and will provide a receipt for your signature upon collection.</p>
            </div>

            <a href="BuyersDashBoard.php" class="back-button">Back to Dashboard</a>
        </div>
    </div>
</body>
</html>
