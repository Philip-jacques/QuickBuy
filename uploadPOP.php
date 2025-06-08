<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require 'db.php'; 

// Check if buyer_id is set in session
if (!isset($_SESSION['buyer_id'])) {
    header("Location: login.php"); // Or wherever your login page is
    exit();
}

$buyerId = $_SESSION['buyer_id'];
$message = '';

// --- NEW CANCELLATION LOGIC ---
if (isset($_GET['action']) && $_GET['action'] === 'cancel_payment') {
    $paymentIdToCancel = $_GET['payment_id'] ?? null;

    if ($paymentIdToCancel) {
        try {
            // Start a transaction for atomicity (optional but good practice for multiple updates)
            $conn->begin_transaction();

            // 1. Update the 'payments' table
            // Ensure the payment belongs to the current buyer and is in a pending state
            $updatePaymentStmt = $conn->prepare("UPDATE payments SET payment_status = 'Failed', status = 'Cancelled', updated_at = NOW() WHERE id = ? AND buyer_id = ? AND payment_status = 'Pending'");
            $updatePaymentStmt->bind_param("ii", $paymentIdToCancel, $buyerId);
            $updatePaymentStmt->execute();

            if ($updatePaymentStmt->affected_rows > 0) {
                // Fetch the order_id associated with this payment
                $stmtOrderId = $conn->prepare("SELECT order_id FROM payments WHERE id = ?");
                $stmtOrderId->bind_param("i", $paymentIdToCancel);
                $stmtOrderId->execute();
                $orderResult = $stmtOrderId->get_result();
                $orderData = $orderResult->fetch_assoc();
                $orderIdAssociated = $orderData['order_id'] ?? null;
                $stmtOrderId->close();

                // 2. Update the 'orders' table (if an order was created and is not already complete)
                // Assuming orders are created with a 'pending' or 'unpaid' status
                if ($orderIdAssociated) {
                    $updateOrderStmt = $conn->prepare("UPDATE orders SET order_status = 'cancelled', updated_at = NOW() WHERE id = ? AND order_status IN ('pending', 'unpaid')");
                    $updateOrderStmt->bind_param("i", $orderIdAssociated);
                    $updateOrderStmt->execute();
                    $updateOrderStmt->close();
                }

                // 3. Return product quantities to stock 
                    // Fetch all items associated with this cancelled order
                    $stmtItems = $conn->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
                    $stmtItems->bind_param("i", $orderIdAssociated);
                    $stmtItems->execute();
                    $orderItemsToReturn = $stmtItems->get_result()->fetch_all(MYSQLI_ASSOC);
                    $stmtItems->close();

                    foreach ($orderItemsToReturn as $item) {
                        $productId = $item['product_id'];
                        $quantityToReturn = $item['quantity'];

                        // Increment the product quantity in the products table
                        $updateProductStmt = $conn->prepare("UPDATE products SET quantity = quantity + ? WHERE id = ?");
                        $updateProductStmt->bind_param("ii", $quantityToReturn, $productId);
                        $updateProductStmt->execute();
                        $updateProductStmt->close(); // Close inside loop if not reusing, or prepare outside loop
                    }
                    // 4. Return product quantities to stock 

                $conn->commit(); // Commit the transaction
                header("Location: paymentCancelled.php?payment_id=" . urlencode($paymentIdToCancel));
                exit();
            } else {
                $conn->rollback(); // Rollback if no payment was updated (e.g., already cancelled or not pending)
                $message = "<span style='color: orange; font-weight: bold;'>⚠️ Payment not found or already processed/cancelled.</span>";
            }
            $updatePaymentStmt->close();

        } catch (mysqli_sql_exception $e) { // Use mysqli_sql_exception for MySQLi errors
            $conn->rollback(); // Rollback on error
            error_log("Payment cancellation DB error: " . $e->getMessage()); // This goes to the inaccessible server log
            // --- ADD THIS LINE TO SEE THE ERROR ON SCREEN ---
            $message = "<span style='color: red; font-weight: bold;'>❌ An error occurred during cancellation: " . htmlspecialchars($e->getMessage()) . ". Please try again or contact support.</span>";
            // --- END OF ADDITION ---
            // In a real application, you might redirect to a generic error page instead of staying here
        }
    } else {
        $message = "<span style='color: red; font-weight: bold;'>❌ Invalid payment ID for cancellation.</span>";
    }
}
// --- END NEW CANCELLATION LOGIC ---

// --- EXISTING POP UPLOAD LOGIC (unchanged for successful upload) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['pop_file']) && $_FILES['pop_file']['error'] === UPLOAD_ERR_OK) {
        $popFile = $_FILES['pop_file']['name'];
        $targetDir = "uploads/";
        $targetFilePath = $targetDir . basename($popFile);

        $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));
        $allowedTypes = array('jpg', 'jpeg', 'png', 'pdf');
        if (!in_array($fileType, $allowedTypes)) {
            $message = "<span style='color: red; font-weight: bold;'>❌ Only JPG, JPEG, PNG, and PDF files are allowed.</span>";
        } else if ($_FILES['pop_file']['size'] > 5000000) {
            $message = "<span style='color: red; font-weight: bold;'>❌ File is too large. Max 5MB allowed.</span>";
        } else {
            $paymentIdFromForm = $_POST['payment_id'] ?? null;

            $stmt = $conn->prepare("SELECT id, order_id FROM payments WHERE buyer_id = ? AND payment_status = 'Pending' ORDER BY id DESC LIMIT 1");
            $stmt->bind_param("i", $buyerId);
            $stmt->execute();
            $paymentResult = $stmt->get_result();

            $payment = null;
            if ($paymentResult->num_rows > 0) {
                $payment = $paymentResult->fetch_assoc();
            }
            $stmt->close();

            $paymentId = $payment['id'] ?? null;
            $orderId = $payment['order_id'] ?? null;

            if ($paymentIdFromForm && $paymentIdFromForm != $paymentId) {
                $message = "<span style='color: red; font-weight: bold;'>❌ Invalid payment ID provided for upload.</span>";
                $paymentId = null;
            }

            if ($paymentId) {
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0777, true);
                }

                if (move_uploaded_file($_FILES['pop_file']['tmp_name'], $targetFilePath)) {
                    $updateStmt = $conn->prepare("UPDATE payments SET payment_status = 'Successful', status = 'Complete' WHERE id = ?");
                    $updateStmt->bind_param("i", $paymentId);
                    if ($updateStmt->execute()) {
                        $insertStmt = $conn->prepare("INSERT INTO pop_uploads (payment_id, filename, uploaded_at) VALUES (?, ?, NOW())");
                        $insertStmt->bind_param("is", $paymentId, $popFile);
                        if ($insertStmt->execute()) {
                            header("Location: paymentSuccess.php?order_id=$orderId&method=eft");
                            exit();
                        } else {
                            $message = "<span style='color: red; font-weight: bold;'>❌ Error saving POP information. Please try again.</span>";
                        }
                        $insertStmt->close();
                    } else {
                        $message = "<span style='color: red; font-weight: bold;'>❌ Error updating payment status. Please try again.</span>";
                    }
                    $updateStmt->close();
                } else {
                    $message = "<span style='color: red; font-weight: bold;'>❌ Error uploading the file. Please check file permissions or try again.</span>";
                }
            } else {
                $message = "<span style='color: orange; font-weight: bold;'>⚠️ No pending payment found for you to upload POP.</span>";
            }
        }
    } else {
        // Only show this message if it's a POST request that wasn't a cancellation request
        if (!isset($_POST['action']) || $_POST['action'] !== 'cancel_payment') {
             $message = "<span style='color: red; font-weight: bold;'>❌ Please select a file to upload.</span>";
        }
    }
} else if (isset($_GET['payment_id']) && !isset($_GET['action'])) { // Exclude cancellation action from this block
    $paymentId = $_GET['payment_id'];
    $stmt = $conn->prepare("SELECT id FROM payments WHERE id = ? AND buyer_id = ? AND payment_status = 'Pending' ORDER BY id DESC LIMIT 1");
    $stmt->bind_param("ii", $paymentId, $buyerId);
    $stmt->execute();
    $paymentResult = $stmt->get_result();
    if ($paymentResult->num_rows == 0) {
        $message = "<span style='color: orange; font-weight: bold;'>⚠️ No pending payment found for the provided ID.</span>";
    }
    $stmt->close();
}


$currentPayment = null;
$orderItems = [];

// Fetch payment details and order items for display
$paymentIdToDisplay = $_GET['payment_id'] ?? ($_POST['payment_id'] ?? null);

// Ensure we have a valid payment ID to display
if ($paymentIdToDisplay) {
    $stmt = $conn->prepare("SELECT id, order_id, cart_amount, courier_cost, total_amount FROM payments WHERE id = ? AND buyer_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->bind_param("ii", $paymentIdToDisplay, $buyerId);
    $stmt->execute();
    $currentPaymentResult = $stmt->get_result();
    if ($currentPaymentResult->num_rows > 0) {
        $currentPayment = $currentPaymentResult->fetch_assoc();

        $sqlItems = "SELECT oi.product_id, p.itemName, oi.quantity, p.price
                      FROM order_items oi
                      JOIN products p ON oi.product_id = p.id
                      WHERE oi.order_id = ?";
        $stmtItems = $conn->prepare($sqlItems);
        $stmtItems->bind_param("i", $currentPayment['order_id']);
        $stmtItems->execute();
        $orderItems = $stmtItems->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmtItems->close();
    } else {
        $message = "<span style='color: orange; font-weight: bold;'>⚠️ No relevant payment details found.</span>";
    }
    $stmt->close();
} else {
    // If no specific payment ID is provided, try to find the latest pending one for display
    $stmt = $conn->prepare("SELECT id, order_id, cart_amount, courier_cost, total_amount FROM payments WHERE buyer_id = ? AND payment_status = 'Pending' ORDER BY id DESC LIMIT 1");
    $stmt->bind_param("i", $buyerId);
    $stmt->execute();
    $currentPaymentResult = $stmt->get_result();
    if ($currentPaymentResult->num_rows > 0) {
        $currentPayment = $currentPaymentResult->fetch_assoc();

        $sqlItems = "SELECT oi.product_id, p.itemName, oi.quantity, p.price
                      FROM order_items oi
                      JOIN products p ON oi.product_id = p.id
                      WHERE oi.order_id = ?";
        $stmtItems = $conn->prepare($sqlItems);
        $stmtItems->bind_param("i", $currentPayment['order_id']);
        $stmtItems->execute();
        $orderItems = $stmtItems->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmtItems->close();
    } else {
        $message = "<span style='color: orange; font-weight: bold;'>⚠️ No pending orders found for you.</span>";
    }
    $stmt->close();
}


$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Proof of Payment - QuickBuy</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Your existing CSS here */
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

            /* Page specific colors */
            --container-bg: rgba(255, 255, 255, 0.15);
            --container-border: rgba(255, 255, 255, 0.3);
            --text-color: var(--ghost-white);
            --heading-color: var(--white-pop);
            --table-header-bg: var(--caribbean-current); /* Dark green for table header */
            --table-border-color: rgba(255, 255, 255, 0.1);
            --table-row-odd-bg: rgba(255, 255, 255, 0.05); /* Light frost for odd rows */
            --table-row-even-bg: rgba(255, 255, 255, 0.1); /* Slightly darker frost for even rows */
            --amount-label-color: var(--ghost-white);
            --amount-value-color: var(--light-font); /* Amounts are light white */

            --upload-box-bg: rgba(0, 0, 0, 0.25);
            --upload-box-border: var(--sapphire);
            --upload-label-color: var(--white-pop);

            --button-submit-bg: var(--true-blue); /* Blue for primary action */
            --button-submit-hover: var(--sapphire);
            --button-cancel-bg: var(--paynes-gray); /* Grey for secondary action */
            --button-cancel-hover: var(--gunmetal);
            --message-success: var(--caribbean-current); /* Green for success */
            --message-error: var(--danger-red); /* Red for errors */
            --message-warning: orange; /* Orange for warnings */
        }

        /* Universal Box-Sizing */
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
            background: linear-gradient(135deg,
                var(--deep-space-blue),
                var(--midnight-green-3),
                var(--prussian-blue),
                var(--oxford-blue),
                var(--true-blue)
            );
            background-size: 300% 300%;
            animation: bgShift 25s ease infinite;
            font-family: 'Poppins', sans-serif;
            color: var(--text-color);
            display: flex;
            flex-direction: column; /* Allow vertical stacking */
            align-items: center;
            justify-content: center; /* Center content vertically too if short */
            padding: 30px;
            box-sizing: border-box;
        }

        @keyframes bgShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .container {
            background: var(--container-bg);
            backdrop-filter: blur(15px);
            border: 1px solid var(--container-border);
            padding: 35px 45px;
            border-radius: 25px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
            width: 100%;
            max-width: 900px; /* Wider container for tables */
            text-align: center;
            animation: fadeIn 1s ease-out;
            margin-bottom: 20px; /* Space between container and upload section */
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        h2 {
            color: var(--heading-color);
            margin-bottom: 30px;
            font-size: 2.5rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-shadow: 0 2px 5px rgba(0,0,0,0.3);
        }

        table {
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 25px;
            background: rgba(255, 255, 255, 0.08); /* Slightly frosted table background */
            border-radius: 15px;
            overflow: hidden; /* Ensures rounded corners on table content */
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.15);
        }

        th, td {
            border: 1px solid var(--table-border-color);
            padding: 15px;
            text-align: left;
            color: var(--text-color);
        }

        th {
            background-color: var(--table-header-bg);
            color: var(--white-pop);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 1rem;
            letter-spacing: 0.5px;
        }

        /* Adjusting first/last column for rounded corners for consistency */
        th:first-child { border-top-left-radius: 15px; }
        th:last-child { border-top-right-radius: 15px; }
        tr:last-child td:first-child { border-bottom-left-radius: 15px; }
        tr:last-child td:last-child { border-bottom-right-radius: 15px; }


        tr:nth-child(odd) {
            background-color: var(--table-row-odd-bg);
        }
        tr:nth-child(even) {
            background-color: var(--table-row-even-bg);
        }

        .amount-row td {
            font-weight: 600;
            padding-top: 20px;
            text-align: right;
            border-top: 2px solid rgba(255, 255, 255, 0.2); /* Stronger separator */
        }
        .amount-label {
            text-align: left;
            color: var(--amount-label-color);
        }
        .amount-value {
            color: var(--amount-value-color); /* Light white for amounts */
            font-size: 1.1em;
        }

        .upload-pop {
            background-color: var(--upload-box-bg);
            backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 18px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.35);
            width: 100%;
            max-width: 800px;
            margin-top: 30px; /* Space from table */
            border: 1px solid rgba(255, 255, 255, 0.2);
            text-align: center;
        }
        .upload-pop label {
            display: block;
            margin-bottom: 15px;
            font-weight: 600;
            color: var(--upload-label-color);
            font-size: 1.2rem;
            letter-spacing: 0.5px;
        }
        .upload-pop input[type="file"] {
            display: block; /* Take full width */
            margin: 0 auto 25px auto; /* Center and add margin below */
            padding: 12px 15px;
            background-color: rgba(255, 255, 255, 0.1); /* Frosted input background */
            border: 1px solid var(--upload-box-border); /* Highlight border */
            border-radius: 8px;
            width: calc(100% - 30px); /* Adjust for padding */
            max-width: 400px; /* Limit file input width */
            box-sizing: border-box;
            font-size: 1rem;
            color: var(--text-color);
            transition: border-color 0.3s ease;
            cursor: pointer;
        }
        .upload-pop input[type="file"]::-webkit-file-upload-button {
            background-color: var(--true-blue);
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .upload-pop input[type="file"]::-webkit-file-upload-button:hover {
            background-color: var(--sapphire);
        }

        .buttons {
            margin-top: 25px;
            display: flex;
            justify-content: center;
            gap: 20px; /* Space between buttons */
        }
        .buttons a, .buttons button {
            padding: 14px 25px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            text-decoration: none;
            color: var(--white-pop);
            font-weight: 600;
            font-size: 1rem;
            transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.2s ease;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            flex-grow: 1; /* Allow buttons to grow */
            max-width: 220px; /* Limit max width */
        }
        .buttons button[type="submit"] {
            background-color: var(--button-submit-bg);
        }
        .buttons button[type="submit"]:hover {
            background-color: var(--button-submit-hover);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
        }
        .buttons a {
            background-color: var(--button-cancel-bg);
        }
        .buttons a:hover {
            background-color: var(--button-cancel-hover);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
        }
        .buttons a.cancel-button:hover { /* Specific hover for cancel button */
            background-color: var(--danger-red-hover); /* A darker red for hover */
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
        }

        p.message {
            text-align: center;
            padding: 10px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
            font-size: 1.1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            width: fit-content;
            margin-left: auto;
            margin-right: auto;
            max-width: 90%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        p.message span[style*='color: green'] {
            background-color: rgba(0, 100, 102, 0.6); /* Caribbean current with opacity */
            color: var(--light-font) !important;
            border: 1px solid var(--message-success);
        }
        p.message span[style*='color: red'] {
            background-color: rgba(220, 53, 69, 0.6); /* Danger red with opacity */
            color: var(--light-font) !important;
            border: 1px solid var(--message-error);
        }
        p.message span[style*='color: orange'] {
            background-color: rgba(255, 165, 0, 0.6); /* Orange with opacity */
            color: var(--dark-font) !important; /* Darker text for contrast on orange */
            border: 1px solid var(--message-warning);
        }


        /* Responsive adjustments */
        @media (max-width: 960px) {
            .container {
                max-width: 95%;
                padding: 30px;
            }
            .upload-pop {
                max-width: 95%;
                padding: 25px;
            }
        }

        @media (max-width: 768px) {
            h2 {
                font-size: 2rem;
                margin-bottom: 25px;
            }
            th, td {
                padding: 12px;
                font-size: 0.95rem;
            }
            .amount-row td {
                padding-top: 15px;
            }
            .upload-pop label {
                font-size: 1.1rem;
            }
            .buttons {
                flex-direction: column; /* Stack buttons vertically */
                gap: 15px;
                align-items: center;
            }
            .buttons a, .buttons button {
                width: 90%; /* Make stacked buttons wider */
                max-width: 300px;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 15px;
            }
            .container {
                padding: 20px;
                border-radius: 20px;
            }
            h2 {
                font-size: 1.6rem;
                margin-bottom: 20px;
            }
            th, td {
                padding: 10px;
                font-size: 0.85rem;
            }
            .amount-row td {
                padding-top: 10px;
            }
            .upload-pop {
                padding: 20px;
                border-radius: 15px;
            }
            .upload-pop label {
                font-size: 1rem;
            }
            .upload-pop input[type="file"] {
                padding: 10px;
                font-size: 0.9rem;
            }
            .buttons a, .buttons button {
                padding: 10px 15px;
                font-size: 0.9rem;
            }
            p.message {
                font-size: 0.95rem;
                padding: 8px 12px;
            }
        }
    </style>
    <script>
        function validateAndSubmit() {
            const fileInput = document.querySelector('input[type="file"]');
            if (fileInput.files.length > 0) {
                return true; // Allow form submission
            } else {
                alert('❌ Please select a file to upload.'); // Still useful for immediate user feedback
                return false; // Prevent form submission
            }
        }
    </script>
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
        <h2>Upload Your Proof of Payment</h2>

        <?php if (!empty($message)) {
            $messageClass = '';
            if (strpos($message, '✅') !== false) {
                $messageClass = 'success';
            } elseif (strpos($message, '❌') !== false) {
                $messageClass = 'error';
            } elseif (strpos($message, '⚠️') !== false) {
                $messageClass = 'warning';
            }
            echo "<p class='message $messageClass'>$message</p>";
        } ?>

        <?php if ($currentPayment) : ?>
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th style='text-align: right;'>Price</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($orderItems)) : ?>
                        <?php foreach ($orderItems as $item) : ?>
                            <tr>
                                <td><?= htmlspecialchars($item['itemName']) ?></td>
                                <td><?= htmlspecialchars($item['quantity']) ?></td>
                                <td style='text-align: right;'>R <?= htmlspecialchars(number_format((float)$item['price'], 2)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr><td colspan='3'>No items found for this order.</td></tr>
                    <?php endif; ?>
                    <tr class='amount-row'>
                        <td colspan='2' class='amount-label'><strong>Cart Amount:</strong></td>
                        <td style='text-align: right;'><span class="amount-value">R <?= htmlspecialchars(number_format((float)$currentPayment['cart_amount'], 2)) ?></span></td>
                    </tr>
                    <tr class='amount-row'>
                        <td colspan='2' class='amount-label'><strong>Courier Cost:</strong></td>
                        <td style='text-align: right;'><span class="amount-value">R <?= htmlspecialchars(number_format((float)$currentPayment['courier_cost'], 2)) ?></span></td>
                    </tr>
                    <tr class='amount-row'>
                        <td colspan='2' class='amount-label'><strong>Total Amount (incl. courier):</strong></td>
                        <td style='text-align: right;'><span class="amount-value">R <?= htmlspecialchars(number_format((float)$currentPayment['total_amount'], 2)) ?></span></td>
                    </tr>
                </tbody>
            </table>

            <div class='upload-pop'>
                <label for="pop_file"><strong>Select your Proof of Payment:</strong></label>
                <form method='POST' enctype='multipart/form-data' onsubmit="return validateAndSubmit();">
                    <input type='file' name='pop_file' id='pop_file' required>
                    <div class='buttons'>
                        <input type='hidden' name='payment_id' value='<?= htmlspecialchars($currentPayment['id']) ?>'>
                        <button type='submit'>✅ Submit POP</button>
                        <a href='uploadPOP.php?action=cancel_payment&payment_id=<?= htmlspecialchars($currentPayment['id']) ?>' class="cancel-button" style="background-color: var(--danger-red);">❌ Cancel</a>
                    </div>
                </form>
            </div>
        <?php else : ?>
            <p style='text-align: center; color: var(--text-color); font-size: 1.1rem;'>
                <?= strip_tags($message); ?>
                <br><br>
                <a href='BrowseItemsPage.php' class="back-to-shop">← Go to Shop</a>
            </p>
        <?php endif; ?>
    </div>
</body>
</html>
