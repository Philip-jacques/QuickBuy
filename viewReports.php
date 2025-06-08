<?php
session_start();
require 'db.php';

// Check if the user is logged in and has admin role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: LoginPage.php");
    exit();
}

// Fetch general statistics (these remain for the entire platform)
$totalUsers = $conn->query("SELECT COUNT(*) AS total FROM users")->fetch_assoc()['total'] ?? 0;
$sellers = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'seller'")->fetch_assoc()['total'] ?? 0;
$buyers = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'buyer'")->fetch_assoc()['total'] ?? 0;
$totalProducts = $conn->query("SELECT COUNT(*) AS total FROM products")->fetch_assoc()['total'] ?? 0;
// Note: $totalSales is still the overall platform total, but we won't use it for the "Recent Sales" section total.
$totalSales = $conn->query("SELECT SUM(total_amount) AS total FROM payments WHERE payment_status = 'completed' OR payment_status = 'Complete' OR payment_status = 'Successful'")->fetch_assoc()['total'] ?? 0; // Ensure this query correctly sums all completed sales regardless of status casing.

// Fetch recent sales data from the last week
$recentSalesSql = "SELECT
    pm.id AS payment_id,
    u.username AS buyer,
    pm.total_amount AS amount,
    pm.payment_date,
    pm.payment_status AS status,
    GROUP_CONCAT(p.itemName SEPARATOR ', ') AS products_purchased
FROM payments pm
JOIN users u ON pm.buyer_id = u.id
JOIN orders o ON pm.order_id = o.id
JOIN order_items oi ON o.id = oi.order_id
JOIN products p ON oi.product_id = p.id
WHERE (pm.payment_status = 'completed' OR pm.payment_status = 'Complete' OR pm.payment_status = 'Successful')
AND pm.payment_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
GROUP BY pm.id
ORDER BY pm.payment_date DESC;";

$recentSalesResult = $conn->query($recentSalesSql);
$recentSales = [];
$totalRecentSalesAmount = 0; // Initialize a variable to sum recent sales
if ($recentSalesResult) {
    while ($row = $recentSalesResult->fetch_assoc()) {
        $recentSales[] = $row;
        $totalRecentSalesAmount += $row['amount']; // Sum up the amount for each recent sale
    }
    $recentSalesResult->free(); // Free the result set
} else {
    error_log("Database query error for recent sales in platformReports.php: " . $conn->error);
}

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Platform Reports - QuickBuy Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Your existing CSS here (no changes needed for this specific issue) */
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

            /* Accent from Browse Categories */
            --white-pop: #FFFFFF;
            --dark-font: #333;
            --light-font: #fefefe;

            /* Admin Dashboard Specific Colors */
            --admin-bg-start: var(--deep-space-blue);
            --admin-bg-end: var(--midnight-green-3);
            --dashboard-card-bg: var(--white-pop);
            --card-border: var(--cool-gray);
            --header-color: var(--oxford-blue);
            --section-title-color: var(--sapphire);
            --text-color-primary: var(--dark-font);
            --text-color-secondary: var(--paynes-gray);
            --button-success-bg: var(--midnight-green);
            --button-success-hover: var(--caribbean-current);
            --button-outline-secondary-bg: var(--ghost-white);
            --button-outline-secondary-text: var(--paynes-gray);
            --button-outline-secondary-border: var(--cool-gray);
            --button-outline-secondary-hover-bg: var(--paynes-gray);
            --button-outline-secondary-hover-text: var(--white-pop);
            --table-header-bg: var(--oxford-blue-2);
            --table-border-color: #e0e0e0;
            --table-row-even-bg: #fdfdfd;
            --shadow-light: rgba(0, 0, 0, 0.08);
            --shadow-medium: rgba(0, 0, 0, 0.15);
            --card-value-color: var(--true-blue);
            --total-sales-bg: var(--sapphire);
            --total-sales-text: var(--light-font);
            --total-sales-amount-color: #79dd09; /* A vibrant green for total sales */
        }

        /* Universal Box-Sizing */
        html {
            box-sizing: border-box;
        }
        *, *::before, *::after {
            box-sizing: inherit;
        }

        body {
            background: linear-gradient(135deg, var(--admin-bg-start), var(--admin-bg-end));
            background-size: 300% 300%;
            animation: bgShift 20s ease infinite;
            min-height: 100vh;
            font-family: 'Poppins', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            color: var(--text-color-primary);
        }

        @keyframes bgShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .container {
            max-width: 95%;
            margin: 30px auto;
            padding: 25px;
            background: var(--dashboard-card-bg);
            border-radius: 12px;
            box-shadow: 0 4px 15px var(--shadow-light);
            animation: fadeIn 0.8s ease-out forwards;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        h2 {
            text-align: center;
            margin-bottom: 30px;
            font-weight: 700;
            font-size: 2.2rem;
            color: var(--header-color);
            position: relative;
            padding-bottom: 10px;
        }

        h2::after {
            content: '';
            position: absolute;
            left: 50%;
            bottom: 0;
            transform: translateX(-50%);
            width: 70px;
            height: 3px;
            background-color: var(--true-blue);
            border-radius: 2px;
        }

        h4 {
            font-size: 1.5rem;
            color: var(--section-title-color);
            margin-bottom: 20px;
            font-weight: 600;
        }

        /* --- Buttons Container --- */
        .button-container {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 25px;
            gap: 10px; /* Space between buttons */
            flex-wrap: wrap; /* Allow buttons to wrap on smaller screens */
        }
        .btn {
            display: inline-flex; /* Use flex for icon and text alignment */
            align-items: center;
            padding: 10px 18px;
            font-size: 0.95rem;
            font-weight: 500;
            line-height: 1.5;
            text-align: center;
            text-decoration: none;
            vertical-align: middle;
            cursor: pointer;
            border: 1px solid transparent;
            border-radius: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.1);
        }
        .btn-icon {
            margin-right: 8px; /* Space between icon and text */
        }

        /* Outline secondary button (Back to Dashboard) */
        .btn-outline-secondary {
            background-color: transparent;
            color: var(--button-outline-secondary-text);
            border-color: var(--button-outline-secondary-border);
        }
        .btn-outline-secondary:hover {
            background-color: var(--button-outline-secondary-hover-bg);
            color: var(--button-outline-secondary-hover-text);
            border-color: var(--button-outline-secondary-hover-bg);
            transform: translateY(-2px);
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.2);
        }

        /* Success button (Export) */
        .btn-success {
            background-color: var(--button-success-bg);
            color: var(--light-font);
            border-color: var(--button-success-bg);
        }
        .btn-success:hover {
            background-color: var(--button-success-hover);
            border-color: var(--button-success-hover);
            transform: translateY(-1px);
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.15);
        }

        /* --- Info Cards --- */
        .row {
            display: flex;
            flex-wrap: wrap;
            margin: 0 -10px; /* Adjust for padding in columns */
            justify-content: center; /* Center cards if they don't fill the row */
        }
        .col-md-3 {
            flex: 0 0 25%;
            max-width: 25%;
            padding: 0 10px; /* Space between cards */
            margin-bottom: 20px;
        }
        .card {
            background-color: var(--dashboard-card-bg);
            border: 1px solid var(--card-border);
            border-radius: 10px;
            box-shadow: 0 2px 10px var(--shadow-light);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            height: 100%; /* Ensure cards are same height in a row */
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 15px var(--shadow-medium);
        }
        .card-body {
            padding: 20px;
            text-align: center;
        }
        .card-body h5 {
            font-size: 1rem;
            margin-bottom: 10px;
            font-weight: 600;
            color: var(--text-color-secondary);
        }
        .card-body .statistic-value {
            font-size: 2.2rem; /* Larger font size for numbers */
            font-weight: 700;
            color: var(--card-value-color); /* Specific color for values */
            line-height: 1;
            margin-top: 5px;
        }

        /* --- Recent Sales Table --- */
        .table-wrapper {
            background: var(--dashboard-card-bg);
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 15px var(--shadow-light);
            margin-top: 30px;
            overflow-x: auto; /* For responsive tables */
            -webkit-overflow-scrolling: touch;
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 15px;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid var(--table-border-color);
        }

        table th, table td {
            border: 1px solid var(--table-border-color);
            padding: 12px 15px;
            text-align: left;
            vertical-align: middle;
            font-size: 0.88rem;
            color: var(--text-color-primary);
        }

        table thead th {
            background-color: var(--table-header-bg);
            color: var(--light-font);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.8rem;
            border-bottom: 1px solid var(--table-header-bg);
        }

        table tbody tr:nth-child(even) {
            background-color: var(--table-row-even-bg);
        }

        table tbody tr:last-child td {
            border-bottom: none;
        }

        .total-sales-row-in-table { /* This class is now used for the 7-day total within the table if it exists */
            background-color: var(--total-sales-bg) !important; /* Important to override striped */
            color: var(--total-sales-text);
            font-weight: 700;
            text-align: right;
        }
        .total-sales-row-in-table td {
            border-color: var(--sapphire); /* Match border to background */
            color: var(--total-sales-text);
            font-weight: inherit; /* Inherit font-weight from row */
        }
        .total-sales-row-in-table .total-sales-label {
            font-size: 1.1rem;
            margin-right: 10px;
            color: inherit; /* Inherit color from row */
        }
        .total-sales-row-in-table .total-sales-amount {
            font-size: 1.4rem;
            color: var(--total-sales-amount-color); /* Vibrant green */
            font-weight: 700;
        }

        .empty-message {
            text-align: center;
            padding: 30px;
            color: var(--text-color-secondary);
            font-size: 1rem;
        }

        /* Total sales summary for when no recent sales are present, or as a standalone total below the table */
        .recent-sales-total-summary { /* New class for the 7-day total display */
            text-align: right;
            margin-top: 20px;
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--sapphire);
            padding: 10px 15px; /* Add some padding */
            border-top: 1px solid var(--card-border); /* Add a subtle separator */
            background-color: var(--dashboard-card-bg); /* Match card background */
            border-radius: 0 0 12px 12px; /* Round bottom corners if placed at the end of table-wrapper */
        }
        .recent-sales-total-summary span {
            color: var(--total-sales-amount-color);
        }


        /* --- Media Queries --- */
        @media (min-width: 992px) { /* Large desktops */
            .container {
                max-width: 1200px;
                padding: 40px;
                border-radius: 15px;
                box-shadow: 0 6px 20px var(--shadow-medium);
            }
            h2 {
                font-size: 2.5rem;
                margin-bottom: 40px;
            }
            h2::after {
                width: 90px;
            }
            .button-container {
                margin-bottom: 30px;
            }
            .btn {
                padding: 12px 22px;
                font-size: 1rem;
            }
            .card-body h5 {
                font-size: 1.15rem;
            }
            .card-body .statistic-value {
                font-size: 2.5rem;
            }
            table th, table td {
                font-size: 0.9rem;
                padding: 15px 18px;
            }
            .table-wrapper {
                padding: 30px;
            }
            .total-sales-row-in-table .total-sales-amount {
                font-size: 1.6rem;
            }
            .recent-sales-total-summary {
                font-size: 1.4rem;
            }
        }

        @media (max-width: 767px) { /* Phones */
            body {
                padding: 10px;
            }
            .container {
                padding: 15px;
                border-radius: 8px;
            }
            h2 {
                font-size: 1.8rem;
                margin-bottom: 20px;
            }
            .button-container {
                flex-direction: column; /* Stack buttons */
                align-items: stretch; /* Make buttons full width */
                gap: 15px;
                margin-bottom: 20px;
            }
            .btn {
                font-size: 0.85rem;
                padding: 10px 15px;
            }
            .col-md-3 {
                flex: 0 0 100%; /* Full width for cards on small screens */
                max-width: 100%;
                padding: 0 0px; /* No horizontal padding for cards */
            }
            .card-body h5 {
                font-size: 1rem;
            }
            .card-body .statistic-value {
                font-size: 1.8rem;
            }
            .table-wrapper {
                padding: 15px;
                margin-top: 20px;
            }
            table th, table td {
                font-size: 0.75rem;
                padding: 8px 10px;
            }
            table tbody tr {
                display: block; /* Make table rows stack on small screens */
                margin-bottom: 10px;
                border: 1px solid var(--table-border-color);
                border-radius: 8px;
                overflow: hidden;
            }
            table thead {
                display: none; /* Hide table header on small screens */
            }
            table tbody td {
                display: flex; /* Use flexbox for label-value pairing */
                justify-content: space-between; /* Space out label and value */
                padding: 8px 10px;
                border-bottom: 1px solid var(--table-border-color);
            }
            table tbody td:last-child {
                border-bottom: none;
            }
            table tbody td::before {
                content: attr(data-label);
                font-weight: 600;
                color: var(--section-title-color); /* Use a themed color for labels */
                margin-right: 10px;
            }
            /* Assign data-labels for stacked table cells */
            table tbody td:nth-of-type(1)::before { content: "Buyer:"; }
            table tbody td:nth-of-type(2)::before { content: "Amount:"; }
            table tbody td:nth-of-type(3)::before { content: "Date:"; }
            table tbody td:nth-of-type(4)::before { content: "Products:"; }
            table tbody td:nth-of-type(5)::before { content: "Status:"; }

            /* --- Corrected Total Sales Row for Mobile --- */
            .total-sales-row-in-table {
                display: block; /* Treat the total row as a block */
                margin-bottom: 10px; /* Add margin like other rows */
                border: 1px solid var(--table-border-color);
                border-radius: 8px;
                overflow: hidden;
                background-color: var(--total-sales-bg) !important;
                color: var(--total-sales-text);
                text-align: left; /* Align text to left for mobile */
            }
            .total-sales-row-in-table td {
                display: flex; /* Make the cell a flex container */
                justify-content: space-between; /* Space out label and value */
                padding: 12px 15px; /* Adjust padding */
                border-bottom: none; /* No border for the single cell */
                color: inherit; /* Inherit color from the row */
            }
            .total-sales-row-in-table td:first-child::before {
                content: "Total Recent Sales:"; /* Ensure the label is present */
                font-weight: 600;
                color: inherit; /* Inherit color from the row */
                margin-right: 10px;
            }
            .total-sales-row-in-table td:last-child {
                display: none; /* Hide the second td which was redundant */
            }
            /* The .total-sales-amount class applies the vibrant green color */
            .total-sales-row-in-table .total-sales-amount {
                font-size: 1.2rem; /* Adjust font size for mobile */
                color: var(--total-sales-amount-color);
                font-weight: 700;
            }

            .recent-sales-total-summary { /* Adjust for mobile if no sales */
                text-align: left; /* Align left for mobile */
                padding: 10px 0; /* Adjust padding */
                font-size: 1.1rem;
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
    <div class="button-container">
        <a href="adminDashboard.php" class="btn btn-outline-secondary">← Back to Dashboard</a>
        <a href="exportSales.php" class="btn btn-success"><span class="btn-icon">📥</span> Export All Sales to Excel</a>
    </div>

    <h2>Platform Reports</h2>

    <div class="row">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5>Total Users</h5>
                    <p class="statistic-value"><?= $totalUsers ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5>Buyers</h5>
                    <p class="statistic-value"><?= $buyers ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5>Sellers</h5>
                    <p class="statistic-value"><?= $sellers ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5>Total Products</h5>
                    <p class="statistic-value"><?= $totalProducts ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="table-wrapper">
        <h4 class="mb-3">Recent Sales (Last 7 Days)</h4>
        <?php if (!empty($recentSales)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Buyer</th>
                        <th>Amount</th>
                        <th>Payment Date</th>
                        <th>Products Purchased</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentSales as $row): ?>
                        <tr>
                            <td data-label="Buyer:"><?= htmlspecialchars($row['buyer']) ?></td>
                            <td data-label="Amount:">R<?= number_format($row['amount'], 2) ?></td>
                            <td data-label="Payment Date:"><?= $row['payment_date'] ?></td>
                            <td data-label="Products:"><?= htmlspecialchars($row['products_purchased']) ?></td>
                            <td data-label="Status:"><?= htmlspecialchars(ucfirst($row['status'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="total-sales-row-in-table">
                        <td colspan="4" class="total-sales-label">Total Recent Sales (Last 7 Days):</td>
                        <td><span class="total-sales-amount">R<?= number_format($totalRecentSalesAmount, 2) ?></span></td>
                    </tr>
                </tbody>
            </table>
        <?php else: ?>
            <p class="empty-message">No completed sales data available for the last 7 days.</p>
            <div class="recent-sales-total-summary">
                Total Recent Sales (Last 7 Days): <span>R<?= number_format($totalRecentSalesAmount, 2) ?></span>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
