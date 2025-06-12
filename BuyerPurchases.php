<?php
/**
 * My Purchases Page
 *
 * This page displays a list of orders made by the logged-in buyer.
 * It fetches order details, including associated products and seller information,
 * from the database and presents them in a sortable table.
 * Users can view their purchase history and generate receipts for individual orders.
 */

// Start a new session or resume the existing one. This is crucial for accessing session variables.
session_start();

// Include the database connection file.
include 'db.php';

// --- Security Check: User Authentication ---
// Redirect to the login page if the 'buyer_id' session variable is not set.
// This prevents unauthorized access to the purchase history page.
if (!isset($_SESSION['buyer_id'])) {
    header('Location: LoginPage.php');
    exit(); // Always call exit() after a header redirect to prevent further script execution.
}

// Retrieve the buyer ID from the session. This ID is used to fetch orders specific to the logged-in user.
$buyer_id = $_SESSION['buyer_id'];

// --- Sorting Logic for Order ID ---
// Determine the sorting order for the 'order_id' column based on the 'sort' GET parameter.
// Default to 'DESC' (descending) if 'sort' is not set or is not 'asc'.
$sortOrder = isset($_GET['sort']) && $_GET['sort'] === 'asc' ? 'ASC' : 'DESC';

// Toggle the sort order for the next click. If current is 'DESC', next will be 'asc', and vice versa.
$sortToggle = $sortOrder === 'DESC' ? 'asc' : 'desc';

// --- SQL Query to Fetch Order Data ---
// This query retrieves comprehensive information about orders and their corresponding items.
// It uses LEFT JOIN to ensure that orders are still displayed even if there are issues
// with associated order items, products, or sellers.

$sql = "SELECT
            o.id AS order_id,            -- Order ID
            o.order_date,                -- Date of the order
            o.delivery_address,          -- Delivery address for the order
            o.total_amount,              -- Total amount of the order (could be recalculated on display for better integrity)
            o.courier_cost,              -- Cost of courier for the order
            oi.quantity,                 -- Quantity of a specific product within an order item
            p.itemName AS product_name,  -- Name of the product
            p.price,                     -- Price of a single unit of the product
            u.username AS seller_name    -- Username of the seller
        FROM orders o                   -- Alias 'o' for the orders table
        LEFT JOIN order_items oi ON o.id = oi.order_id -- Join with order_items to get product quantities
        LEFT JOIN products p ON oi.product_id = p.id   -- Join with products to get product details
        LEFT JOIN users u ON p.seller_id = u.id       -- Join with users to get seller information
        WHERE o.buyer_id = ?             -- Filter orders by the logged-in buyer's ID (important for security)
        ORDER BY o.id $sortOrder, o.order_date DESC"; // Primary sort by order ID (dynamic), secondary by order date (fixed).

// --- Prepare and Execute SQL Statement ---
// Prepare the SQL statement to prevent SQL injection.
// The '?' acts as a placeholder for the buyer_id.
$stmt = $conn->prepare($sql);

// Check if the statement preparation was successful.
if ($stmt) {
    // Bind the 'buyer_id' parameter to the prepared statement.
    // 'i' specifies that the parameter is an integer.
    $stmt->bind_param("i", $buyer_id);
    // Execute the prepared statement.
    $stmt->execute();
    // Get the result set from the executed statement.
    $result = $stmt->get_result();
} else {
    // --- Error Handling for Database Query Failure ---
    // Log the error for debugging purposes (e.g., to a server error log).
    error_log("Failed to prepare statement for MyPurchases.php: " . $conn->error);
    // Set a user-friendly error message in the session.
    $_SESSION['error_message'] = "An unexpected error occurred. Please try again later.";
    // Redirect the buyer to their dashboard with an error message.
    header('Location: BuyersDashBoard.php');
    exit(); // Crucial to stop script execution after redirect.
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Purchases - QuickBuy</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        /*
         * CSS Custom Properties (Variables)
         *
         * Defines a consistent color palette for the entire page, making it easy
         * to manage and update the theme. Organized by color families (blues, greens, neutrals, accents).
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
            --paynes-gray: #5c677dff; /* Used for grey button */
            --slate-gray: #7d8597ff; /* Used for grey button hover */
            --cool-gray: #979dacff;
            --charcoal: #1b3a4bff;

            /* Accent */
            --white-pop: #FFFFFF;
            --dark-font: #333;
            --light-font: #fefefe; /* Used for frosted glass text */

            /* Red for danger/logout */
            --danger-red: #dc3545;
            --danger-red-hover: #bd2130;

            /* Table & Button Specifics */
            --table-header-bg: var(--oxford-blue);
            --table-row-odd-bg: rgba(255, 255, 255, 0.05);
            --table-row-even-bg: rgba(255, 255, 255, 0.03);
            --table-border-color: rgba(255, 255, 255, 0.15);
            --button-primary-bg: var(--sapphire); /* This is now unused for .btn-secondary */
            --button-primary-hover-bg: var(--true-blue); /* This is now unused for .btn-secondary */
            --button-info-bg: var(--caribbean-current);
            --button-info-hover-bg: var(--midnight-green);
        }

        /* Universal Box-Sizing for consistent layout behavior */
        html {
            box-sizing: border-box;
        }
        *, *::before, *::after {
            box-sizing: inherit;
        }

        /* Body Styling */
        body {
            /* Gradient background with animation for a dynamic visual effect */
            background: linear-gradient(135deg,
                var(--deep-space-blue),
                var(--midnight-green-3),
                var(--prussian-blue),
                var(--oxford-blue),
                var(--true-blue)
            );
            background-size: 300% 300%; /* Allows for larger gradient movement */
            animation: bgShift 25s ease infinite; /* Smooth, infinite background animation */
            font-family: 'Poppins', sans-serif; /* Professional and readable font */
            color: var(--ghost-white); /* Default text color */
            display: flex;
            flex-direction: column;
            align-items: center; /* Center content horizontally */
            justify-content: flex-start; /* Align content to the top initially */
            padding-top: 30px;
            padding-bottom: 30px;
            min-height: 100vh; /* Full viewport height */
            overflow-x: hidden; /* Prevent horizontal scrollbar */
        }

        /* Keyframe animation for background shift */
        @keyframes bgShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Container Styling (Frosted Glass Effect) */
        .container {
            max-width: 1200px; /* Wider container for better table display */
            width: 100%;
            padding: 30px;
            margin-top: 20px;
            background-color: rgba(255, 255, 255, 0.08); /* Semi-transparent white for frosted effect */
            backdrop-filter: blur(10px); /* Blurs the content behind the container */
            border: 1px solid rgba(255, 255, 255, 0.15); /* Subtle border */
            border-radius: 15px; /* Rounded corners */
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.4); /* Deep shadow for depth */
            text-align: left;
            animation: fadeIn 1s ease-out; /* Fade-in animation on load */
        }

        /* Keyframe animation for container fade-in */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Heading Styling */
        h2 {
            font-size: 2.5rem;
            color: var(--white-pop);
            margin-bottom: 30px;
            text-align: center;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-shadow: 0 3px 6px rgba(0,0,0,0.4); /* Text shadow for depth */
        }

        /* Table Styling */
        .table {
            margin-top: 20px;
            color: var(--ghost-white);
            border-radius: 12px;
            overflow: hidden; /* Ensures rounded corners apply to content */
            border: 1px solid var(--table-border-color); /* Overall table border */
        }

        /* Table Header Styling */
        .table-dark thead th {
            background-color: var(--table-header-bg);
            color: var(--white-pop);
            font-weight: 600;
            border-bottom: 1px solid var(--table-border-color);
            padding: 15px 10px;
            font-size: 1.05rem;
            vertical-align: middle; /* Align text vertically in middle */
        }

        /* Bootstrap Table Dark Theme Overrides */
        .table-dark {
            --bs-table-bg: var(--table-row-odd-bg); /* Default background for odd rows */
            --bs-table-striped-bg: var(--table-row-even-bg); /* Background for even rows when .table-striped is used */
            --bs-table-striped-color: var(--ghost-white); /* Text color for striped rows */
            --bs-table-hover-bg: rgba(255, 255, 255, 0.12); /* Subtle hover effect */
            --bs-table-hover-color: var(--white-pop); /* Text color on hover */
            --bs-table-border-color: var(--table-border-color); /* Border color for table cells */
            --bs-table-color: var(--ghost-white); /* Default text color for table body */
        }

        /* Bordered Table Specifics */
        .table-bordered {
            border: 1px solid var(--table-border-color);
        }

        .table-bordered th, .table-bordered td {
            border: 1px solid var(--table-border-color);
        }

        /* Table Body Cell Styling */
        .table tbody tr td {
            padding: 12px 10px;
            vertical-align: middle;
            font-size: 0.95rem;
        }

        /* Sortable Header Link Styling */
        .sortable-header {
            display: flex;
            align-items: center;
            text-decoration: none !important; /* Remove underline */
            color: var(--white-pop) !important; /* Ensure consistent color */
            transition: color 0.3s ease; /* Smooth color transition on hover */
        }

        .sortable-header:hover {
            color: var(--highlight-color) !important; /* Placeholder for a highlight color, consider defining it */
        }

        /* Sort Arrow Styling */
        .sort-arrow {
            margin-left: 8px;
            font-size: 0.8em;
            vertical-align: middle;
        }

        /* --- Button Styling --- */

        /* Back to Dashboard Button (.btn-secondary) */
        .btn-secondary {
            background-color: var(--paynes-gray); /* Grey color */
            border-color: var(--paynes-gray);
            color: var(--white-pop);
            padding: 10px 20px;
            font-size: 1rem;
            border-radius: 8px;
            transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.2s ease;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            text-decoration: none;
            display: inline-flex; /* Allows icon and text to sit side-by-side */
            align-items: center;
            gap: 5px; /* Space between icon and text */
        }

        .btn-secondary:hover {
            background-color: var(--slate-gray); /* Darker grey on hover */
            border-color: var(--slate-gray);
            transform: translateY(-2px); /* Slight lift effect */
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.3);
        }

        /* View Receipt Button (.btn-info) */
        .btn-info {
            background-color: var(--button-info-bg);
            border-color: var(--button-info-bg);
            color: var(--white-pop);
            padding: 8px 15px;
            font-size: 0.9rem;
            border-radius: 6px;
            transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.2s ease;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.15);
        }

        .btn-info:hover {
            background-color: var(--button-info-hover-bg);
            border-color: var(--button-info-hover-bg);
            transform: translateY(-1px);
            box-shadow: 0 5px 12px rgba(0, 0, 0, 0.25);
        }

        /* Alert Message Styling (e.g., "No orders yet") */
        .alert-info {
            background-color: rgba(76, 175, 80, 0.15); /* Light green tint */
            color: var(--ghost-white);
            border-color: var(--caribbean-current);
            font-size: 1.1rem;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            margin-top: 30px;
        }

        /* Styling for "No product linked" message */
        .text-danger {
            color: var(--danger-red) !important; /* Override Bootstrap's default danger color if needed */
            font-weight: 500;
        }

        /* --- Responsive Adjustments using Media Queries --- */

        /* Medium screens (e.g., tablets, up to 992px) */
        @media (max-width: 992px) {
            .container {
                padding: 25px;
            }
            h2 {
                font-size: 2.2rem;
            }
            .table-dark thead th, .table tbody tr td {
                padding: 12px 8px;
                font-size: 0.9rem;
            }
            .btn-secondary {
                padding: 8px 15px;
                font-size: 0.95rem;
            }
            .btn-info {
                padding: 7px 12px;
                font-size: 0.85rem;
            }
        }

        /* Small screens (e.g., larger smartphones, up to 768px) */
        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }
            h2 {
                font-size: 2rem;
                margin-bottom: 25px;
            }
            /* Make table scrollable horizontally on small screens to prevent overflow */
            .table-responsive-sm {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch; /* Smooth scrolling on iOS */
            }
            .table tbody tr td {
                font-size: 0.85rem;
            }
            .btn-secondary {
                padding: 7px 12px;
                font-size: 0.9rem;
                margin-bottom: 20px;
            }
        }

        /* Extra small screens (e.g., smaller smartphones, up to 576px) */
        @media (max-width: 576px) {
            body {
                padding-top: 20px;
                padding-bottom: 20px;
            }
            .container {
                padding: 15px;
                border-radius: 10px;
            }
            h2 {
                font-size: 1.8rem;
                margin-bottom: 20px;
            }
            /* Stack table content vertically on very small screens (card-like display) */
            .table thead {
                display: none; /* Hide table headers */
            }
            .table tbody, .table tr, .table td {
                display: block; /* Make table rows and cells block-level */
                width: 100%;
            }
            .table tr {
                margin-bottom: 15px;
                border: 1px solid var(--table-border-color);
                border-radius: 8px;
                padding: 10px;
                background-color: var(--table-row-odd-bg); /* Apply background to the entire row */
            }
            .table tr:nth-child(even) {
                background-color: var(--table-row-even-bg); /* Apply striped background to even rows */
            }
            .table td {
                text-align: left;
                position: relative;
                border: none; /* Remove individual cell borders as rows have borders */
                padding-left: 10px;
                padding-right: 10px;
                display: flex; /* Use flex to stack label and content */
                flex-direction: column;
                align-items: flex-start;
            }
            .table td::before {
                /* Add a pseudo-element to display column headers as labels */
                content: attr(data-label); /* Content comes from the data-label attribute on the td */
                display: block;
                position: static;
                width: auto;
                text-align: left;
                font-weight: 600;
                color: var(--white-pop);
                font-size: 0.9em;
                margin-bottom: 5px;
            }
            /* Specific data-labels for each column for accessibility and mobile display */
            td:nth-of-type(1)::before { content: "Order ID:"; }
            td:nth-of-type(2)::before { content: "Product:"; }
            td:nth-of-type(3)::before { content: "Quantity:"; }
            td:nth-of-type(4)::before { content: "Price (Each):"; }
            td:nth-of-type(5)::before { content: "Total:"; }
            td:nth-of-type(6)::before { content: "Order Date:"; }
            td:nth-of-type(7)::before { content: "Address:"; }
            td:nth-of-type(8)::before { content: "Courier Cost:"; }
            td:nth-of-type(9)::before { content: "Seller:"; }
            td:nth-of-type(10)::before { content: "Receipt:"; } /* Add receipt data-label */

            .btn-info {
                width: 100%; /* Make buttons full width on small screens */
                margin-top: 5px;
                padding: 10px 15px;
                font-size: 0.95rem;
            }
            .alert-info {
                font-size: 1rem;
                padding: 12px;
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
        <h2 class="text-center">My Purchase History</h2>

        <a href="BuyersDashBoard.php" class="btn btn-secondary mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-left" viewBox="0 0 16 16">
                <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8"/>
            </svg>
            Back to Dashboard
        </a>

        <?php
        // --- Conditional Display of Orders or "No Orders" Message ---
        // Check if there are any rows returned from the database query.
        if ($result->num_rows > 0):
        ?>
            <div class="table-responsive-sm">
                <table class="table table-bordered table-striped table-dark">
                    <thead>
                        <tr>
                            <th>
                                <a href="?sort=<?= $sortToggle ?>" class="sortable-header">
                                    Order ID
                                    <span class="sort-arrow">
                                        <?php if ($sortOrder === 'ASC'): ?>
                                            ▲
                                        <?php else: ?>
                                            ▼
                                        <?php endif; ?>
                                    </span>
                                </a>
                            </th>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Price (Each)</th>
                            <th>Total</th>
                            <th>Order Date</th>
                            <th>Delivery Address</th>
                            <th>Courier Cost</th>
                            <th>Seller</th>
                            <th>Receipt</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Loop through each row of the query result to display order items.
                        while ($row = $result->fetch_assoc()):
                        ?>
                            <tr>
                                <td data-label="Order ID:"><?= htmlspecialchars($row['order_id']) ?></td>
                                <td data-label="Product:"><?= $row['product_name'] ? htmlspecialchars($row['product_name']) : '<span class="text-danger">No product linked</span>' ?></td>
                                <td data-label="Quantity:"><?= $row['quantity'] ?? '—' ?></td>
                                <td data-label="Price (Each):"><?= $row['price'] ? 'R' . number_format($row['price'], 2) : '—' ?></td>
                                <td data-label="Total:">
                                    <?php
                                    if ($row['price'] && $row['quantity']) {
                                        echo 'R' . number_format($row['price'] * $row['quantity'], 2);
                                    } else {
                                        echo '—';
                                    }
                                    ?>
                                </td>
                                <td data-label="Order Date:"><?= htmlspecialchars($row['order_date']) ?></td>
                                <td data-label="Delivery Address:"><?= htmlspecialchars($row['delivery_address']) ?></td>
                                <td data-label="Courier Cost:">R<?= number_format($row['courier_cost'], 2) ?></td>
                                <td data-label="Seller:"><?= $row['seller_name'] ? htmlspecialchars($row['seller_name']) : '—' ?></td>
                                <td data-label="Receipt:">
                                    <a href="receipt.php?order_id=<?= htmlspecialchars($row['order_id']) ?>" class="btn btn-sm btn-info">
                                        View Receipt
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info">You have not placed any orders yet. Start shopping now!</div>
        <?php endif; ?>

        <?php

        $stmt->close();
        $conn->close();
        ?>
    </div>
</body>
</html>
