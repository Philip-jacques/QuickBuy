<?php
/**
 * @file InstantEFTPage.php
 *
 * @brief This page displays details for an Instant EFT (Electronic Funds Transfer) payment.
 * It fetches order and buyer information from the database and presents the necessary
 * banking details for the buyer to complete their payment. It also provides instructions
 * on how to submit proof of payment and what to expect next.
 *
 */

// Start a new session or resume the existing one. This is crucial for accessing
// session variables like 'buyer_id'.
session_start();

require_once 'db.php';

// --- Session and Order ID Validation ---
/**
 * @brief Checks if the 'buyer_id' is set in the session and 'order_id' is set
 * in the GET request. If either is missing, it redirects the user to
 * the login page to ensure authenticated access and a valid order context.
 */
if (!isset($_SESSION['buyer_id']) || !isset($_GET['order_id'])) {
    // Redirect to the login page or a general error page if prerequisites are not met.
    header("Location: LoginPage.php");
    exit(); // Always exit after a header redirect to prevent further script execution.
}

// Sanitize and store the order ID from the GET request.
$orderId = $_GET['order_id'];

// --- Fetch Order Details from Database ---
/**
 * @brief Prepares and executes a SQL query to fetch order details, including
 * the associated username, email, cart amount, courier cost, and payment ID.
 * It joins the 'payments' and 'users' tables to retrieve comprehensive data.
 * Error handling includes logging, setting a session error message, and redirecting.
 */
$orderQuery = "SELECT u.username, u.email, p.cart_amount, p.courier_cost, p.id as payment_id
               FROM payments p
               JOIN users u ON p.buyer_id = u.id
               WHERE p.order_id = ?";
$orderStmt = $conn->prepare($orderQuery); // Prepare the SQL statement to prevent SQL injection.

// Check if the statement preparation was successful.
if ($orderStmt) {
    // Bind the order ID parameter to the prepared statement. 'i' indicates integer type.
    $orderStmt->bind_param("i", $orderId);
    $orderStmt->execute(); // Execute the prepared statement.
    $orderResult = $orderStmt->get_result(); // Get the result set from the executed statement.

    // Check if an order was found.
    if ($orderResult->num_rows == 0) {
        // Log an error if the order is not found, which helps in debugging.
        error_log("Order not found for order_id: " . $orderId . " for buyer_id: " . $_SESSION['buyer_id']);
        // Set an error message in the session to display on the dashboard.
        $_SESSION['error_message'] = "Sorry, the order you are trying to view could not be found.";
        // Redirect to the buyer's dashboard.
        header("Location: BuyersDashBoard.php");
        exit();
    }

    // Fetch the order data as an associative array.
    $orderData = $orderResult->fetch_assoc();

    // Assign fetched data to variables for easier use in the HTML.
    $buyerName = $orderData['username'];
    $buyerEmail = $orderData['email'];
    $itemCost = $orderData['cart_amount'];
    $courierCost = $orderData['courier_cost'];
    $totalAmount = $itemCost + $courierCost; // Calculate the total amount due.
    $paymentId = $orderData['payment_id']; // This is the payment ID, though not explicitly displayed.
    $orderStmt->close(); // Close the statement.
} else {
    // Log an error if the statement preparation failed.
    error_log("Error preparing order details query: " . $conn->error);
    // Set a generic error message and redirect to the dashboard.
    $_SESSION['error_message'] = "An error occurred while fetching order details. Please try again.";
    header("Location: BuyersDashBoard.php");
    exit();
}

// --- Fetch Order Items (Optional, for display purposes) ---
/**
 * @brief This section fetches individual items associated with the order.
 * Although the provided HTML for this page doesn't explicitly display
 * these items, the logic is kept for potential future use or debugging.
 */
$orderItems = []; // Initialize an empty array to hold order items.
$orderItemsQuery = "SELECT p.itemName, oi.quantity, p.price
                    FROM order_items oi
                    JOIN products p ON oi.product_id = p.id
                    WHERE oi.order_id = ?";
$orderItemsStmt = $conn->prepare($orderItemsQuery);

if ($orderItemsStmt) {
    $orderItemsStmt->bind_param("i", $orderId);
    $orderItemsStmt->execute();
    $orderItemsResult = $orderItemsStmt->get_result();
    // Fetch all order items and add them to the $orderItems array.
    while ($item = $orderItemsResult->fetch_assoc()) {
        $orderItems[] = $item;
    }
    $orderItemsStmt->close(); // Close the statement.
} else {
    // Log an error if the statement preparation failed.
    error_log("Error preparing order items query for Order ID: " . $orderId . " Error: " . $conn->error);
    // This error might not require stopping the page, as it's for display items.
}

// Close the database connection at the end of the PHP script execution.
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instant EFT Payment - QuickBuy</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /*
         * @brief CSS Variables for consistent theming.
         * Defines a color palette and other common styling values.
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
            --light-font: #fefefe; /* Used for frosted glass text */

            /* Red for danger/logout */
            --danger-red: #dc3545;
            --danger-red-hover: #bd2130;

            /* Additional colors for this page */
            --highlight-color: #ffe066; /* A soft yellow for important details */
            --bank-details-bg: rgba(255, 255, 255, 0.08); /* Lighter frosted for bank details */
            --bank-details-border: rgba(255, 255, 255, 0.15);
            --next-steps-bg: rgba(0, 100, 102, 0.1); /* Light Caribbean Current for next steps */
            --next-steps-border: var(--caribbean-current);
            --info-text-color: var(--cool-gray);
            --link-color: var(--true-blue);
            --link-hover-color: var(--sapphire);
        }

        /*
         * @brief Universal Box-Sizing for consistent layout behavior across all elements.
         */
        html {
            box-sizing: border-box;
        }
        *, *::before, *::after {
            box-sizing: inherit;
        }

        /*
         * @brief Base styles for html and body.
         * Sets minimum height, prevents horizontal overflow, and applies
         * a gradient background with animation.
         */
        html, body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            overflow-x: hidden; /* Prevent horizontal scroll */
        }

        body {
            background: linear-gradient(135deg,
                var(--deep-space-blue),
                var(--midnight-green-3),
                var(--prussian-blue),
                var(--oxford-blue),
                var(--true-blue)
            );
            background-size: 300% 300%;
            animation: bgShift 25s ease infinite; /* Animates the background gradient */
            font-family: 'Poppins', sans-serif;
            color: var(--ghost-white); /* Default text color for body */
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            padding-top: 30px;
            padding-bottom: 30px;
        }

        /*
         * @brief Keyframe animation for the background gradient.
         * Shifts the background position to create a subtle, continuous movement.
         */
        @keyframes bgShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /*
         * @brief Styles for the main content container.
         * Sets maximum width, padding, and centers text.
         */
        .container {
            max-width: 95%;
            width: 100%;
            padding: 20px;
            margin-top: 20px;
            text-align: center;
        }

        /*
         * @brief Styles for the main heading (H1).
         * Applies font size, color, margin, text alignment, weight,
         * letter spacing, text shadow, and a fadeIn animation.
         */
        h1 {
            font-size: 2.8rem;
            color: var(--white-pop);
            margin-bottom: 30px;
            text-align: center;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-shadow: 0 3px 6px rgba(0,0,0,0.4);
            animation: fadeIn 1s ease-out;
        }

        /*
         * @brief Keyframe animation for fading in elements.
         */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /*
         * @brief Styles for the main content card.
         * Implements a frosted glass effect with a translucent background,
         * backdrop blur, border, shadow, padding, border-radius, and slideIn animation.
         */
        .main-content-card {
            background-color: rgba(255, 255, 255, 0.1); /* Frosted glass effect */
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 25px;
            text-align: left;
            animation: slideIn 1s ease-out;
            color: var(--light-font); /* Light text on frosted card */
            max-width: 700px; /* Increased max-width for better content flow */
            margin-left: auto;
            margin-right: auto;
        }

        /*
         * @brief Keyframe animation for sliding in elements.
         */
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /*
         * @brief Styles for paragraphs within the main content card.
         */
        .main-content-card p {
            color: var(--ghost-white); /* Light text for paragraphs */
            font-size: 1.05rem;
            margin-bottom: 15px;
            line-height: 1.6;
        }

        /*
         * @brief Styles for strong text within main content card paragraphs.
         */
        .main-content-card p strong {
            color: var(--white-pop);
            font-weight: 600;
        }

        /*
         * @brief Styles for section headings (H2).
         * Sets font size, color, margins, padding, border, and weight.
         */
        h2.section-heading {
            font-size: 1.8rem;
            color: var(--white-pop);
            margin-top: 35px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid rgba(255, 255, 255, 0.2);
            font-weight: 600;
            text-align: left;
        }

        /*
         * @brief Styles for the bank details section.
         * Applies a lighter frosted background, border, padding, border-radius,
         * margins, and shadow.
         */
        .bank-details {
            background-color: var(--bank-details-bg);
            border: 1px solid var(--bank-details-border);
            padding: 30px;
            border-radius: 12px;
            margin-top: 25px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }

        /*
         * @brief Styles for the heading within the bank details section.
         */
        .bank-details h2 {
            color: var(--highlight-color);
            font-size: 1.6rem;
            margin-top: 0;
            margin-bottom: 20px;
            font-weight: 600;
            text-align: center;
        }

        /*
         * @brief Styles for the unordered list within bank details.
         */
        .bank-details ul {
            list-style: none; /* Removes default list bullets */
            padding: 0;
            margin: 0;
        }

        /*
         * @brief Styles for list items within bank details.
         * Uses flexbox for alignment, sets font size, and adds a dashed border.
         */
        .bank-details li {
            margin-bottom: 15px;
            font-size: 1.1rem;
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            border-bottom: 1px dashed rgba(255, 255, 255, 0.1);
            padding-bottom: 8px;
        }

        /*
         * @brief Removes bottom margin and border for the last list item.
         */
        .bank-details li:last-child {
            margin-bottom: 0;
            border-bottom: none;
        }

        /*
         * @brief Styles for strong text (labels) within bank details list items.
         */
        .bank-details li strong {
            color: var(--white-pop);
            font-weight: 500;
            flex-basis: 40%; /* Adjust label width */
        }

        /*
         * @brief Styles for span elements (values) within bank details list items.
         */
        .bank-details li span {
            color: var(--ghost-white);
            font-weight: 400;
            text-align: right;
            flex-basis: 60%; /* Adjust value width */
        }

        /*
         * @brief Styles for important values (like amount and reference) within bank details.
         */
        .bank-details li .important-value {
            color: var(--highlight-color); /* Highlight the amount */
            font-weight: 700;
            font-size: 1.2rem;
            text-shadow: 0 0 5px rgba(255, 255, 100, 0.3);
        }

        /*
         * @brief Styles for the "What happens next?" section.
         * Applies a light Caribbean Current background, border, padding,
         * margins, border-radius, font size, line height, color, and shadow.
         */
        #next-steps {
            background-color: var(--next-steps-bg);
            border: 1px solid var(--next-steps-border);
            border-left: 5px solid var(--next-steps-border); /* Stronger left border */
            padding: 30px;
            margin-top: 30px;
            border-radius: 12px;
            font-size: 1rem;
            line-height: 1.7;
            color: var(--ghost-white);
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }

        /*
         * @brief Styles for the heading within the "next steps" section.
         */
        #next-steps h3 {
            color: var(--highlight-color);
            margin-bottom: 20px;
            font-size: 1.6rem;
            font-weight: 600;
            text-align: center;
        }

        /*
         * @brief Styles for paragraphs within the "next steps" section.
         * Uses flexbox to align text with potential emojis.
         */
        #next-steps p {
            margin-bottom: 15px;
            color: var(--ghost-white);
            display: flex;
            align-items: center;
            gap: 10px; /* Space between emoji and text */
        }

        /*
         * @brief Removes bottom margin for the last paragraph in "next steps".
         */
        #next-steps p:last-of-type {
            margin-bottom: 0;
        }

        /*
         * @brief Styles for links within the "next steps" section.
         */
        #next-steps a {
            color: var(--highlight-color);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease, text-decoration 0.3s ease;
        }

        /*
         * @brief Hover effect for links in "next steps".
         */
        #next-steps a:hover {
            color: var(--white-pop);
            text-decoration: underline;
        }

        /*
         * @brief Styles for the "Important Notes" section.
         * Applies a light red background, border, padding, border-radius,
         * margins, color, font weight, line height, and inner shadow.
         */
        .important-notes {
            margin-top: 30px;
            padding: 20px;
            background-color: rgba(220, 53, 69, 0.1); /* Light red for warning */
            border: 1px solid var(--danger-red);
            border-radius: 12px;
            color: var(--danger-red);
            font-weight: 500;
            line-height: 1.5;
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);
        }

        /*
         * @brief Styles for the title within "Important Notes".
         */
        .important-notes p.important-title {
            color: var(--danger-red);
            font-weight: 700;
            font-size: 1.2rem;
            margin-bottom: 15px;
            text-align: center;
        }

        /*
         * @brief Styles for the unordered list within "Important Notes".
         */
        .important-notes ul {
            list-style: disc; /* Use discs for this list */
            padding-left: 25px; /* Indent the list */
            margin: 0;
            color: var(--ghost-white); /* Text color for notes */
        }

        /*
         * @brief Styles for list items within "Important Notes".
         */
        .important-notes li {
            margin-bottom: 8px;
            font-size: 0.95rem;
            color: var(--ghost-white);
        }

        /*
         * @brief Styles for strong text within "Important Notes" list items.
         */
        .important-notes li strong {
            color: var(--white-pop);
            font-weight: 600;
        }

        /*
         * @brief Styles for the "Back to Dashboard" button/link.
         * Applies background, color, padding, border-radius, font size,
         * cursor, margins, display, transition, text decoration, alignment,
         * width, and shadow.
         */
        .back-to-dashboard {
            background-color: var(--info-text-color);
            color: var(--white-pop);
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

        /*
         * @brief Hover effect for the "Back to Dashboard" button/link.
         */
        .back-to-dashboard:hover {
            background-color: var(--info-text-color); /* Stays the same color on hover */
            transform: translateY(-2px); /* Slight lift effect */
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3); /* Enhanced shadow on hover */
        }

        /*
         * @brief Responsive adjustments for screens up to 768px wide (tablets).
         */
        @media (max-width: 768px) {
            h1 {
                font-size: 2.2rem;
            }
            .main-content-card {
                padding: 25px;
            }
            h2.section-heading {
                font-size: 1.6rem;
                margin-top: 30px;
                margin-bottom: 15px;
            }
            .bank-details, #next-steps, .important-notes {
                padding: 20px;
            }
            .bank-details h2, #next-steps h3 {
                font-size: 1.4rem;
            }
            .bank-details li, #next-steps p {
                font-size: 1rem;
            }
            .bank-details li .important-value {
                font-size: 1.1rem;
            }
            .important-notes li {
                font-size: 0.9rem;
            }
            .back-to-dashboard {
                padding: 12px 25px;
                font-size: 1rem;
            }
        }

        /*
         * @brief Responsive adjustments for screens up to 480px wide (mobile phones).
         */
        @media (max-width: 480px) {
            body {
                padding: 15px;
            }
            h1 {
                font-size: 1.8rem;
                margin-bottom: 20px;
            }
            .main-content-card {
                padding: 18px;
                border-radius: 10px;
            }
            .main-content-card p {
                font-size: 0.9rem;
                margin-bottom: 10px;
            }
            h2.section-heading {
                font-size: 1.4rem;
                margin-top: 25px;
                margin-bottom: 10px;
            }
            .bank-details, #next-steps, .important-notes {
                padding: 15px;
                border-radius: 10px;
                margin-top: 20px;
                margin-bottom: 20px;
            }
            .bank-details h2, #next-steps h3 {
                font-size: 1.2rem;
                margin-bottom: 15px;
            }
            .bank-details li, #next-steps p, .important-notes li {
                font-size: 0.9rem;
                margin-bottom: 8px;
                flex-direction: column; /* Stack label and value on small screens */
                align-items: flex-start; /* Align labels to start */
            }
            .bank-details li strong {
                margin-bottom: 5px; /* Add space below label */
                flex-basis: auto; /* Reset flex basis */
            }
            .bank-details li span {
                text-align: left; /* Align value to left when stacked */
                flex-basis: auto; /* Reset flex basis */
            }
            #next-steps p {
                gap: 5px; /* Reduce gap for stacked elements */
                align-items: flex-start; /* Align emoji to start */
            }
            .important-notes p.important-title {
                font-size: 1.1rem;
                margin-bottom: 10px;
            }
            .important-notes ul {
                padding-left: 20px;
            }
            .back-to-dashboard {
                padding: 10px 20px;
                font-size: 0.9rem;
                margin-top: 25px;
                border-radius: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Instant EFT Payment Details</h1>

        <div class="main-content-card">
            <p>Dear <strong class="highlight-value"><?php echo htmlspecialchars($buyerName); ?></strong>, please use the following banking details to complete your Instant EFT payment. Your total amount due is <span class="important-value">R<?php echo number_format($totalAmount, 2); ?></span>. Your order will be processed once the payment is confirmed.</p>

            <div class="bank-details">
                <h2>QuickBuy Banking Details</h2>
                <ul>
                    <li><strong>Bank Name:</strong> <span>FNB (First National Bank)</span></li>
                    <li><strong>Account Number:</strong> <span>50060369847</span></li>
                    <li><strong>Branch Code:</strong> <span>250655</span></li>
                    <li><strong>Account Holder:</strong> <span>QuickBuy (Pty) Ltd</span></li>
                    <li><strong>Amount Due:</strong> <span class="important-value">R<?php echo number_format($totalAmount, 2); ?></span></li>
                    <li><strong>Payment Reference:</strong> <span class="important-value"><?php echo htmlspecialchars($orderId); ?></span></li>
                </ul>
            </div>

            <div id="next-steps">
                <h3>What happens next?</h3>
                <p>📄 Please email your Proof of Payment (POP) to: <a href="mailto:accounts@quickbuy.co.za">accounts@quickbuy.co.za</a></p>
                <p>👆 Alternatively, you can directly upload your POP using the link below:</p>
                <p>👉 <a href="uploadPOP.php?order_id=<?php echo htmlspecialchars($orderId); ?>">Click here to upload your Proof of Payment</a></p>
                <p>🚚 Your order will be dispatched within 24–48 hours after POP verification.</p>
                <p>📞 For delivery queries, you can contact our courier service directly at: **022 485 2258**.</p>
                <p>⚠️ **Important:** Courier delivery will not proceed until your Proof of Payment has been successfully received and confirmed by QuickBuy.</p>
            </div>

            <div class="important-notes">
                <p class="important-title">Important Notes:</p>
                <ul>
                    <li>Always use your **Order ID (<?php echo htmlspecialchars($orderId); ?>)** as the payment reference. This is crucial for us to match your payment to your order quickly.</li>
                    <li>Failure to provide a valid Proof of Payment within **48 hours** may result in the cancellation of your order.</li>
                    <li>If you encounter any issues or have questions, please contact our support team immediately.</li>
                </ul>
            </div>

            <a href="BuyersDashBoard.php" class="back-to-dashboard">Back to Dashboard</a>
        </div>
    </div>
</body>
</html>