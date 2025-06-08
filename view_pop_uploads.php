<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: LoginPage.php");
    exit();
}

require_once 'db.php';

// Fetch proof of payment uploads with payment details, ordered by ID
$sql = "SELECT pu.id, pu.payment_id, pu.filename, pu.uploaded_at,
               p.order_id, p.total_amount, p.payment_method, p.payment_date
        FROM pop_uploads pu
        JOIN payments p ON pu.payment_id = p.id
        ORDER BY pu.id ASC";
$result = $conn->query($sql);

$pop_uploads = [];
if ($result) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $pop_uploads[] = $row;
        }
    }
    $result->free(); // Free the result set
} else {
    // Handle query error
    error_log("Database query error in view_pop_uploads.php: " . $conn->error);
    
}

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Proof of Payments - QuickBuy Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
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

            /* Admin Dashboard Specific Colors (inherited) */
            --admin-bg-start: var(--deep-space-blue);
            --admin-bg-end: var(--midnight-green-3);
            --dashboard-card-bg: var(--white-pop);
            --card-border: var(--cool-gray);
            --header-color: var(--oxford-blue);
            --section-title-color: var(--sapphire);
            --text-color-primary: var(--dark-font);
            --text-color-secondary: var(--paynes-gray);
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
            --link-color: var(--sapphire); /* For view links */
            --link-hover-color: var(--true-blue);
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

        /* --- Table Styling --- */
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .pop-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 20px;
            background-color: var(--dashboard-card-bg);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px var(--shadow-light);
            border: 1px solid var(--table-border-color);
        }

        .pop-table th, .pop-table td {
            border: 1px solid var(--table-border-color);
            padding: 12px 15px;
            text-align: left;
            vertical-align: middle;
            font-size: 0.88rem;
            color: var(--text-color-primary);
        }

        .pop-table thead th {
            background-color: var(--table-header-bg);
            color: var(--light-font);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.8rem;
            border-bottom: 1px solid var(--table-header-bg);
        }

        .pop-table tbody tr:nth-child(even) {
            background-color: var(--table-row-even-bg);
        }

        .pop-table tbody tr:last-child td {
            border-bottom: none;
        }

        .pop-table a {
            color: var(--link-color);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s ease-in-out;
        }
        .pop-table a:hover {
            color: var(--link-hover-color);
            text-decoration: underline;
        }

        /* --- Back Button --- */
        .back-link {
            text-align: center;
            margin-top: 30px;
        }

        .btn {
            display: inline-block;
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

        /* --- Empty state --- */
        .empty-message {
            text-align: center;
            padding: 30px;
            color: var(--text-color-secondary);
            font-size: 1rem;
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
            .pop-table th, .pop-table td {
                font-size: 0.95rem;
                padding: 15px 18px;
            }
            .btn {
                padding: 12px 22px;
                font-size: 1rem;
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
            .pop-table th, .pop-table td {
                font-size: 0.75rem;
                padding: 8px 10px;
            }
            .pop-table tbody tr {
                display: block; /* Make table rows stack on small screens */
                margin-bottom: 10px;
                border: 1px solid var(--table-border-color);
                border-radius: 8px;
                overflow: hidden;
            }
            .pop-table thead {
                display: none; /* Hide table header on small screens */
            }
            .pop-table tbody td {
                display: flex; /* Use flexbox for label-value pairing */
                justify-content: space-between; /* Space out label and value */
                padding: 8px 10px;
                border-bottom: 1px solid var(--table-border-color);
            }
            .pop-table tbody td:last-child {
                border-bottom: none;
            }
            .pop-table tbody td::before {
                content: attr(data-label);
                font-weight: 600;
                color: var(--section-title-color); /* Use a themed color for labels */
                margin-right: 10px;
            }
            /* Assign data-labels for stacked table cells */
            .pop-table tbody td:nth-of-type(1)::before { content: "PoP ID:"; }
            .pop-table tbody td:nth-of-type(2)::before { content: "Payment ID:"; }
            .pop-table tbody td:nth-of-type(3)::before { content: "Order ID:"; }
            .pop-table tbody td:nth-of-type(4)::before { content: "Total Amount:"; }
            .pop-table tbody td:nth-of-type(5)::before { content: "Method:"; }
            .pop-table tbody td:nth-of-type(6)::before { content: "Payment Date:"; }
            .pop-table tbody td:nth-of-type(7)::before { content: "Filename:"; }
            .pop-table tbody td:nth-of-type(8)::before { content: "Uploaded At:"; }
            .pop-table tbody td:nth-of-type(9)::before { content: "Action:"; }

            .btn {
                padding: 8px 15px;
                font-size: 0.85rem;
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
    <h2>Proof of Payments</h2>

    <?php if (!empty($pop_uploads)): ?>
        <div class="table-responsive">
            <table class="pop-table">
                <thead>
                    <tr>
                        <th>PoP ID</th>
                        <th>Payment ID</th>
                        <th>Order ID</th>
                        <th>Total Amount</th>
                        <th>Method</th>
                        <th>Payment Date</th>
                        <th>Filename</th>
                        <th>Uploaded At</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pop_uploads as $upload): ?>
                        <tr>
                            <td data-label="PoP ID:"><?php echo htmlspecialchars($upload['id']); ?></td>
                            <td data-label="Payment ID:"><?php echo htmlspecialchars($upload['payment_id']); ?></td>
                            <td data-label="Order ID:"><?php echo htmlspecialchars($upload['order_id']); ?></td>
                            <td data-label="Total Amount:">R<?php echo htmlspecialchars(number_format($upload['total_amount'], 2)); ?></td>
                            <td data-label="Method:"><?php echo htmlspecialchars($upload['payment_method']); ?></td>
                            <td data-label="Payment Date:"><?php echo htmlspecialchars($upload['payment_date']); ?></td>
                            <td data-label="Filename:"><?php echo htmlspecialchars($upload['filename']); ?></td>
                            <td data-label="Uploaded At:"><?php echo htmlspecialchars($upload['uploaded_at']); ?></td>
                            <td data-label="Action:"><a href="/uploads/<?php echo urlencode($upload['filename']); ?>" target="_blank">View</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="empty-message">No proof of payments have been uploaded yet.</p>
    <?php endif; ?>

    <p class="back-link"><a href="adminDashboard.php" class="btn btn-outline-secondary">← Back to Dashboard</a></p>
</div>

</body>
</html>
