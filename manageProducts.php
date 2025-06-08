<?php
session_start(); // Start the PHP session to manage user login status and messages.
require 'db.php'; // Include the database connection file.

// Redirect non-admin users to the login page. This ensures only authorized personnel can access this page.
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: LoginPage.php");
    exit();
}

// --- User Feedback Messages (Initialize) ---
// Initialize variables to store user feedback messages (e.g., success or error).
$message = '';
$messageType = ''; // Can be 'success' or 'error', used for dynamic styling.

// Handle product approval/rejection actions initiated via GET requests.
if (isset($_GET['action']) && isset($_GET['id'])) {
    $productId = intval($_GET['id']); // Sanitize product ID by converting to integer.
    $action = ''; // Initialize action variable.

    // Determine the action (approve or reject) based on the GET parameter.
    if ($_GET['action'] === 'approve') {
        $action = 'approved';
    } elseif ($_GET['action'] === 'reject') {
        $action = 'rejected';
    }

    // Proceed if a valid action and product ID are provided.
    if ($action && $productId > 0) {
        // Prepare an SQL statement to update the product status.
        // Using prepared statements prevents SQL injection.
        $stmt = $conn->prepare("UPDATE products SET status = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("si", $action, $productId); // Bind parameters: 's' for string (status), 'i' for integer (product ID).
            if ($stmt->execute()) {
                // Set success message if the update was successful.
                $message = "Product ID " . $productId . " has been successfully " . $action . ".";
                $messageType = 'success';
            } else {
                // Log and set error message if execution failed.
                error_log("Error updating product status: " . $stmt->error);
                $message = "Error updating product status for ID " . $productId . ": " . $stmt->error;
                $messageType = 'error';
            }
            $stmt->close(); // Close the prepared statement.
        } else {
            // Log and set error message if statement preparation failed.
            error_log("Database error preparing update statement: " . $conn->error);
            $message = "A database error occurred: " . $conn->error;
            $messageType = 'error';
        }
    } else {
        // Set error message for invalid action or product ID.
        $message = "Invalid action or product ID provided.";
        $messageType = 'error';
    }

    // Store the message and its type in the session.
    // This allows messages to persist across redirects.
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $messageType;

    // Redirect to the same page using POST-redirect-GET pattern.
    // This prevents form re-submission issues on page refresh.
    header("Location: manageProducts.php");
    exit();
}

// --- Fetch and clear session messages on page load ---
// Check if there are any messages stored in the session from a previous action.
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message']; // Retrieve the message.
    $messageType = $_SESSION['message_type']; // Retrieve the message type.
    unset($_SESSION['message']); // Clear the message from session after retrieval.
    unset($_SESSION['message_type']); // Clear the message type from session.
}

// --- Filter logic for products ---
// Determine the current filter based on GET parameter, default to 'pending'.
$currentFilter = $_GET['filter'] ?? 'pending';

// Base SQL query to fetch product details along with seller's username.
$sql = "SELECT p.id, p.itemName, u.username AS seller, p.price, p.quantity, p.status, p.dateAdded
        FROM products p
        JOIN users u ON p.seller_id = u.id";

// Add a WHERE clause to filter products by status if a specific filter is selected.
if ($currentFilter === 'pending' || $currentFilter === 'approved' || $currentFilter === 'rejected') {
    // Use real_escape_string to prevent SQL injection for the filter value.
    $sql .= " WHERE p.status = '" . $conn->real_escape_string($currentFilter) . "'";
}
// If 'all' is selected, no WHERE clause for status is added, fetching all products.

// Order the results by the date they were added, newest first.
$sql .= " ORDER BY p.dateAdded DESC";

// Execute the SQL query.
$result = $conn->query($sql);

// Check if the query execution was successful.
if (!$result) {
    // Log the database query error.
    error_log("Database query error in manageProducts.php: " . $conn->error);
    $products = []; // Initialize products as an empty array on error.
    $message = "Could not fetch products: " . $conn->error; // Set error message for display.
    $messageType = 'error';
} else {
    $products = $result->fetch_all(MYSQLI_ASSOC); // Fetch all results as an associative array.
    $result->free(); // Free the result set memory.
}

$conn->close(); // Close the database connection.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - QuickBuy Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            /* Define CSS variables for consistent theming and easy updates */
            --true-blue: #0466c8ff;
            --sapphire: #0353a4ff;
            --yale-blue: #023e7dff;
            --oxford-blue: #002855ff;
            --oxford-blue-2: #001845ff;
            --oxford-blue-3: #001233ff;
            --caribbean-current: #006466ff;
            --midnight-green: #065a60ff;
            --midnight-green-2: #0b525bff;
            --midnight-green-3: #144552ff;
            --prussian-blue: #212f45ff;
            --deep-space-blue: #0d1b2a;
            --gunmetal: #30343fff;
            --ghost-white: #fafaffff;
            --delft-blue: #273469ff;
            --space-cadet: #1e2749ff;
            --paynes-gray: #5c677dff;
            --slate-gray: #7d8597ff;
            --cool-gray: #979dacff;
            --charcoal: #1b3a4bff;
            --white-pop: #FFFFFF;
            --dark-font: #333;
            --light-font: #fefefe;
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
            --button-danger-bg: #dc3545; /* Standard red for reject */
            --button-danger-hover: #c82333;
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
            --badge-approved-bg: var(--midnight-green);
            --badge-pending-bg: #ffc107;
            --badge-pending-text: #212529;
            --badge-rejected-bg: var(--button-danger-bg);

            /* New: Message box colors */
            --message-success-bg: #d4edda;
            --message-success-text: #155724;
            --message-error-bg: #f8d7da;
            --message-error-text: #721c24;
        }

        /* Universal Box-Sizing for consistent layout calculation */
        html {
            box-sizing: border-box;
        }
        *, *::before, *::after {
            box-sizing: inherit;
        }

        /* Body styling: sets background, font, and centers content */
        body {
            background: linear-gradient(135deg, var(--admin-bg-start), var(--admin-bg-end));
            background-size: 300% 300%;
            animation: bgShift 20s ease infinite; /* Animates the background gradient */
            min-height: 100vh;
            font-family: 'Poppins', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            color: var(--text-color-primary);
        }

        /* Keyframe animation for background shift */
        @keyframes bgShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Container for the main content, with styling for appearance and animation */
        .container {
            max-width: 95%;
            margin: 30px auto;
            padding: 25px;
            background: var(--dashboard-card-bg);
            border-radius: 12px;
            box-shadow: 0 4px 15px var(--shadow-light);
            animation: fadeIn 0.8s ease-out forwards; /* Fade-in animation on load */
        }

        /* Keyframe animation for container fade-in */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Heading 2 styling */
        h2 {
            text-align: center;
            margin-bottom: 30px;
            font-weight: 700;
            font-size: 2.2rem;
            color: var(--header-color);
            position: relative;
            padding-bottom: 10px;
        }

        /* Underline effect for Heading 2 */
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

        /* --- Message Box Styling --- */
        .message-box {
            padding: 10px 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-size: 0.95rem;
            text-align: center;
            border: 1px solid transparent;
            opacity: 0; /* Start hidden for animation */
            animation: fadein-message 0.5s ease-out forwards; /* Fade-in animation for messages */
            animation-delay: 0.2s; /* Delay slightly after container fades in */
        }
        /* Success message specific styling */
        .message-box.success {
            background-color: var(--message-success-bg);
            color: var(--message-success-text);
            border-color: #28a745; /* A darker green border */
        }
        /* Error message specific styling */
        .message-box.error {
            background-color: var(--message-error-bg);
            color: var(--message-error-text);
            border-color: #dc3545; /* A darker red border */
        }
        /* Keyframe animation for message fade-in */
        @keyframes fadein-message {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* --- Filter Tabs Styling --- */
        .filter-tabs {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 25px;
            flex-wrap: wrap; /* Allow tabs to wrap on smaller screens */
        }
        /* Styling for individual filter tab links */
        .filter-tabs a {
            padding: 10px 20px;
            border-radius: 25px;
            text-decoration: none;
            color: var(--sapphire);
            background-color: var(--ghost-white);
            border: 2px solid var(--sapphire);
            font-weight: 500;
            transition: all 0.3s ease; /* Smooth transitions for hover effects */
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            white-space: nowrap; /* Prevent text wrapping within a tab */
        }
        /* Hover effect for filter tabs */
        .filter-tabs a:hover {
            background-color: var(--sapphire);
            color: var(--light-font);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            transform: translateY(-2px); /* Slight lift effect on hover */
        }
        /* Styling for the active filter tab */
        .filter-tabs a.active {
            background-color: var(--oxford-blue);
            color: var(--light-font);
            border-color: var(--oxford-blue);
            font-weight: 600;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            transform: translateY(-1px);
        }

        /* --- Table Styling --- */
        /* Makes the table responsive by allowing horizontal scrolling */
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch; /* Improves scrolling on touch devices */
        }

        /* General table styling */
        table {
            width: 100%;
            border-collapse: separate; /* Allows for border-spacing and rounded corners */
            border-spacing: 0;
            margin-top: 20px;
            background-color: var(--dashboard-card-bg);
            border-radius: 12px;
            overflow: hidden; /* Ensures rounded corners are visible */
            box-shadow: 0 4px 15px var(--shadow-light);
            border: 1px solid var(--table-border-color);
        }

        /* Styling for table headers and data cells */
        table th, table td {
            border: 1px solid var(--table-border-color);
            padding: 12px 15px;
            text-align: left;
            vertical-align: middle;
            font-size: 0.88rem;
            color: var(--text-color-primary);
        }

        /* Styling for table header row */
        table thead th {
            background-color: var(--table-header-bg);
            color: var(--light-font);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.8rem;
            border-bottom: 1px solid var(--table-header-bg);
        }

        /* Zebra striping for table rows */
        table tbody tr:nth-child(even) {
            background-color: var(--table-row-even-bg);
        }

        /* Remove bottom border for the last row's cells */
        table tbody tr:last-child td {
            border-bottom: none;
        }

        /* --- Badges for Status --- */
        .badge {
            display: inline-block;
            padding: 0.5em 0.8em;
            font-size: 0.75rem;
            font-weight: 600;
            line-height: 1;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: 6px;
            color: var(--light-font); /* Default text color for badges */
        }
        /* Specific badge colors based on status */
        .badge-approved {
            background-color: var(--badge-approved-bg);
        }
        .badge-pending {
            background-color: var(--badge-pending-bg);
            color: var(--badge-pending-text); /* Dark text for yellow */
        }
        .badge-rejected {
            background-color: var(--badge-rejected-bg);
        }
        /* Styling for "No Actions" text when product is not pending */
        .text-muted-no-action {
            color: var(--text-color-secondary); /* Greyed out text for no actions */
            font-size: 0.8rem;
            font-style: italic;
        }

        /* --- Buttons --- */
        /* Base button styling */
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
            transition: all 0.3s ease; /* Smooth transitions for hover effects */
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.1);
        }
        /* Smaller buttons for table actions */
        .btn-sm {
            padding: 6px 12px;
            font-size: 0.8rem;
            border-radius: 6px;
            margin: 2px; /* Small margin between action buttons */
        }

        /* Success button (for approve) styling */
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

        /* Danger button (for reject) styling */
        .btn-danger {
            background-color: var(--button-danger-bg);
            color: var(--light-font);
            border-color: var(--button-danger-bg);
        }
        .btn-danger:hover {
            background-color: var(--button-danger-hover);
            border-color: var(--button-danger-hover);
            transform: translateY(-1px);
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.15);
        }

        /* Outline secondary button (Back to Dashboard) styling */
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

        /* Empty state message styling for when no products are found */
        .empty-message {
            text-align: center;
            padding: 30px;
            color: var(--text-color-secondary);
            font-size: 1rem;
        }

        /* --- Media Queries for responsiveness --- */
        @media (min-width: 768px) {
            .container {
                max-width: 1100px;
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
            table th, table td {
                font-size: 0.9rem;
                padding: 15px 18px;
            }
            .badge {
                font-size: 0.85rem;
                padding: 0.6em 1em;
            }
            .btn {
                padding: 12px 22px;
                font-size: 1rem;
            }
            .btn-sm {
                padding: 8px 15px;
                font-size: 0.85rem;
            }
            .filter-tabs {
                gap: 15px;
            }
        }

        @media (max-width: 575px) {
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
            /* Add data-label attribute content for accessibility and mobile display */
            table tbody td::before {
                content: attr(data-label);
                font-weight: 600;
                color: var(--section-title-color); /* Use a themed color for labels */
                margin-right: 10px;
            }
            /* Assign specific data-labels for each column */
            table tbody td:nth-of-type(1)::before { content: "ID:"; }
            table tbody td:nth-of-type(2)::before { content: "Name:"; }
            table tbody td:nth-of-type(3)::before { content: "Seller:"; }
            table tbody td:nth-of-type(4)::before { content: "Price:"; }
            table tbody td:nth-of-type(5)::before { content: "Quantity:"; }
            table tbody td:nth-of-type(6)::before { content: "Status:"; }
            table tbody td:nth-of-type(7)::before { content: "Date Added:"; }
            table tbody td:nth-of-type(8)::before { content: "Actions:"; }

            table td .btn {
                margin: 4px 0; /* Stack action buttons */
                display: block;
                width: 100%; /* Full width for action buttons */
            }
            .filter-tabs {
                flex-direction: column; /* Stack filter tabs vertically */
                align-items: stretch; /* Make them full width */
            }
            .filter-tabs a {
                border-radius: 8px; /* Less rounded for stacked */
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
        <h2>Manage Product Listings</h2>

        <?php if ($message): // Display message if set ?>
            <div class="message-box <?= htmlspecialchars($messageType) ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="filter-tabs">
            <a href="?filter=pending" class="<?= $currentFilter === 'pending' ? 'active' : '' ?>">Pending</a>
            <a href="?filter=approved" class="<?= $currentFilter === 'approved' ? 'active' : '' ?>">Approved</a>
            <a href="?filter=rejected" class="<?= $currentFilter === 'rejected' ? 'active' : '' ?>">Rejected</a>
            <a href="?filter=all" class="<?= $currentFilter === 'all' ? 'active' : '' ?>">All Products</a>
        </div>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Seller</th>
                        <th>Price</th>
                        <th>Qty</th>
                        <th>Status</th>
                        <th>Date Added</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($products)): // Check if there are products to display ?>
                        <?php foreach ($products as $row): // Loop through each product ?>
                            <tr>
                                <td data-label="ID:"><?= $row['id'] ?></td>
                                <td data-label="Name:"><?= htmlspecialchars($row['itemName']) ?></td>
                                <td data-label="Seller:"><?= htmlspecialchars($row['seller']) ?></td>
                                <td data-label="Price:">R<?= number_format($row['price'], 2) ?></td>
                                <td data-label="Quantity:"><?= $row['quantity'] ?></td>
                                <td data-label="Status:">
                                    <span class="badge <?php
                                        if ($row['status'] === 'approved') {
                                            echo 'badge-approved';
                                        } elseif ($row['status'] === 'rejected') {
                                            echo 'badge-rejected';
                                        } else {
                                            echo 'badge-pending';
                                        }
                                    ?>">
                                        <?= ucfirst($row['status']) ?>
                                    </span>
                                </td>
                                <td data-label="Date Added:"><?= $row['dateAdded'] ?></td>
                                <td data-label="Actions:">
                                    <?php if ($row['status'] === 'pending'): // Only show action buttons for pending products ?>
                                        <a href="?action=approve&id=<?= $row['id'] ?>&filter=<?= $currentFilter ?>" class="btn btn-sm btn-success" onclick="return confirm('Are you sure you want to approve this product?');">Approve</a>
                                        <a href="?action=reject&id=<?= $row['id'] ?>&filter=<?= $currentFilter ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to reject this product? This cannot be undone.');">Reject</a>
                                    <?php else: ?>
                                        <span class="text-muted-no-action">No Actions</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="empty-message">No product listings found with status "<?= htmlspecialchars($currentFilter) ?>".</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div style="text-align: center; margin-top: 30px;">
            <a href="adminDashboard.php" class="btn btn-outline-secondary">← Back to Dashboard</a>
        </div>
    </div>
</body>
</html>
