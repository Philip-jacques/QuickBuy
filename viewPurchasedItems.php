<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
/**
 * @brief This page displays a list of items sold by the logged-in seller,
 * including details about the purchased products and buyer information.
 *
 * It retrieves sales data from the database, allows sorting by purchase date,
 * and presents the information in a user-friendly format.
 */

// Start a new session or resume the existing session.
// This is crucial for accessing session variables like 'user_id'.
session_start();

// Include the database connection file.
include 'db.php';

// --- User Authentication Check ---
// Check if the 'user_id' session variable is not set.
// This means the user is not logged in.
if (!isset($_SESSION['user_id'])) {
    // Redirect the user to the login page.
    header("Location: LoginPage.php");
    // Terminate script execution to prevent further output.
    exit;
}

// Get the seller's user ID from the session.
// This ID is used to fetch sales specifically for this seller.
$seller_id = $_SESSION['user_id'];

// Determine the sorting order for the query.
// It defaults to 'desc' (descending) if no 'sort' parameter is provided in the URL,
// or if the 'sort' parameter is not 'asc'.
$sort = $_GET['sort'] ?? 'desc';

// Set the SQL sorting direction based on the $sort variable.
// If $sort is 'asc', order by ascending; otherwise, order by descending.
$sort_sql = ($sort === 'asc') ? 'ASC' : 'DESC';

// --- Database Query Preparation and Execution ---
// SQL query to retrieve purchased items for the logged-in seller.
// It joins multiple tables (order_items, products, orders, users)
// to gather comprehensive details about each sale.
$sql = "SELECT
            oi.quantity,
            oi.product_id,
            pr.itemName,
            pr.price,
            o.order_date,
            u.username AS buyer_name,
            u.email AS buyer_email,
            u.phone AS buyer_phone 
        FROM order_items oi
        JOIN products pr ON oi.product_id = pr.id 
        JOIN orders o ON oi.order_id = o.id     
        JOIN users u ON o.buyer_id = u.id       
        WHERE pr.seller_id = ?                  
        ORDER BY o.order_date $sort_sql";       

// Prepare the SQL statement to prevent SQL injection.
$stmt = $conn->prepare($sql);

// Check if the statement preparation failed.
if (!$stmt) {
    // Log the detailed database error for debugging purposes.
    error_log("Database prepare error: " . $conn->error);
    // Display a user-friendly error message.
    echo "<p style='color: white;'>An error occurred. Please try again later.</p>";
    // Terminate script execution.
    exit;
}

// Bind the seller_id parameter to the prepared statement.
// 'i' specifies that the parameter is an integer.
$stmt->bind_param("i", $seller_id);

// Execute the prepared statement.
$stmt->execute();

// Get the result set from the executed statement.
$result = $stmt->get_result();

// Check if the query execution itself resulted in an error.
if (!$result) {
    // Log the detailed database query error for debugging.
    error_log("Database query error: " . $conn->error);
    // Display a user-friendly error message.
    echo "<p style='color: white;'>An error occurred while fetching data. Please try again later.</p>";
    // Terminate script execution.
    exit;
}

// The database connection ($conn) is intentionally not closed here.
// This allows other parts of the page or linked functions to potentially use it.
// If this were the only database interaction on the page, $conn->close() would be appropriate here.
// $conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Purchased Items</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* --- CSS Custom Properties (Variables) for consistent theming --- */
        :root {
            /* Main blues */
            --true-blue: #0466c8ff;
            --sapphire: #0353a4ff;
            --yale-blue: #023e7dff;
            --oxford-blue: #002855ff;
            --oxford-blue-2: #001845ff;
            --oxford-blue-3: #001233ff;

            /* Greens & Deeper Blues */
            --caribbean-current: #006466ff; /* Your desired darker green */
            --midnight-green: #065a60ff;     /* Your desired lighter green */
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
        }

        /* --- Universal Box-Sizing for consistent layout behavior --- */
        html {
            box-sizing: border-box;
        }
        *, *::before, *::after {
            box-sizing: inherit;
        }

        /* --- Base Body Styles --- */
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
            background-size: 300% 300%; /* Larger background to enable animation */
            animation: bgShift 25s ease infinite; /* Smooth background shift animation */
            font-family: 'Poppins', sans-serif; /* Apply Poppins font */
            color: var(--ghost-white); /* Default text color */
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 15px; /* Base padding for mobile */
        }

        /* Keyframe animation for background gradient */
        @keyframes bgShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* --- Container for Main Content --- */
        .container {
            width: 100%;
            max-width: 900px; /* Max width for the main content area */
            margin: 0 auto; /* Center the container */
            padding: 20px 0; /* Vertical padding */
        }

        /* --- Heading Styles --- */
        h2 {
            color: var(--white-pop); /* Bright white for headings */
            text-align: center;
            margin-bottom: 25px;
            font-size: 2.2em; /* Adjusted font size for better visibility */
        }

        /* --- Back Button Styles --- */
        .back-btn {
            background-color: var(--midnight-green); /* Green color */
            color: var(--ghost-white);
            padding: 10px 20px; /* Slightly more padding */
            margin-bottom: 20px; /* Increased margin for spacing */
            display: inline-block;
            border-radius: 8px; /* Slightly more rounded */
            text-decoration: none;
            font-weight: 500;
            transition: background-color 0.3s ease, transform 0.2s; /* Smooth transitions */
        }
        .back-btn:hover {
            background-color: var(--caribbean-current); /* Darker green on hover */
            transform: scale(1.02); /* Subtle scale effect */
            color: var(--ghost-white);
        }

        /* --- Sort Wrapper and Filter Container Styles --- */
        .sort-wrapper {
            display: flex;
            justify-content: center;
            margin-bottom: 25px; /* Adjusted default margin */
        }

        .filter-container {
            border: 1px solid rgba(255, 255, 255, 0.2); /* Subtle white border */
            border-radius: 10px;
            padding: 10px 15px; /* Adjusted padding */
            display: flex;
            align-items: center;
            gap: 10px; /* Space between label and dropdown */
            background-color: rgba(27, 42, 75, 0.4); /* Match category section background */
            color: var(--white-pop);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2); /* Subtle shadow */
        }

        .filter-container label {
            font-size: 1em; /* Adjusted default font size */
            font-weight: 500;
        }

        .filter-container select {
            padding: 8px 12px; /* Adjusted padding */
            border-radius: 8px; /* More rounded */
            border: 1px solid rgba(255, 255, 255, 0.2);
            font-size: 1em; /* Adjusted default font size */
            background-color: rgba(255, 255, 255, 0.08); /* Transparent background */
            color: var(--ghost-white);
            appearance: none; /* Remove default select styling */
            /* Custom dropdown arrow using SVG data URI */
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3E%3Cpath fill='none' stroke='%23fafaff' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 10px center; /* Initial position for arrow */
            background-size: 1em; /* Size of the arrow */
            padding-right: 35px; /* Added right padding to prevent overlap with arrow */
        }
        .filter-container select option {
            background-color: var(--prussian-blue); /* Darker background for options */
            color: var(--white-pop);
        }
        .filter-container select:focus {
            outline: none;
            border-color: var(--true-blue); /* Highlight on focus */
            background-color: rgba(255, 255, 255, 0.15);
        }

        /* --- Orders Container (Grid Layout) --- */
        .orders-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); /* Responsive grid columns */
            gap: 20px; /* Adjusted default gap */
        }

        /* --- Individual Order Card Styles --- */
        .order-card {
            background-color: rgba(255, 255, 255, 0.1); /* Lighter background for cards */
            padding: 20px; /* Adjusted default padding */
            border-radius: 15px; /* More rounded */
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2); /* Stronger shadow */
            color: var(--ghost-white); /* Ensure text is visible */
            transition: transform 0.2s ease, background-color 0.3s ease; /* Smooth transitions */
        }
        .order-card:hover {
            transform: translateY(-3px); /* Subtle lift effect on hover */
            background-color: rgba(255, 255, 255, 0.15);
        }

        .order-card h3 {
            margin-top: 0;
            font-size: 1.4em; /* Adjusted font size */
            color: var(--white-pop); /* Match theme color */
            margin-bottom: 10px; /* Adjusted default margin */
            border-bottom: 1px solid rgba(255, 255, 255, 0.1); /* Subtle separator */
            padding-bottom: 8px;
        }

        .order-card p {
            margin: 6px 0; /* Adjusted default margin */
            font-size: 0.95em; /* Adjusted default font size */
            color: var(--cool-gray);
        }
        .order-card p strong { /* Ensure strong tags within paragraphs are styled */
            color: var(--ghost-white);
        }

        /* --- Contact Buyer Button Styles --- */
        .contact-btn {
            display: inline-block;
            margin-top: 15px; /* More margin */
            padding: 10px 18px; /* More padding */
            background-color: var(--sapphire); /* Blue theme color */
            color: var(--white-pop);
            text-decoration: none;
            border-radius: 8px; /* More rounded */
            font-size: 0.95em; /* Adjusted font size */
            font-weight: 500;
            transition: background-color 0.3s ease, transform 0.2s;
        }
        .contact-btn:hover {
            background-color: var(--true-blue); /* Darker blue on hover */
            transform: scale(1.02);
        }

        /* --- Message for No Purchases --- */
        body > p {
            color: var(--cool-gray);
            text-align: center;
            font-size: 1.1em;
            margin-top: 30px;
        }


        /* --- Media Queries for various devices --- */

        /* Tablets and smaller Laptops */
        @media (min-width: 768px) {
            body {
                padding: 30px;
            }
            h2 {
                font-size: 2.8em;
                margin-bottom: 30px;
            }
            .back-btn {
                padding: 12px 25px;
                font-size: 1.1em;
                margin-bottom: 25px;
            }
            .sort-wrapper {
                margin-bottom: 30px;
            }
            .filter-container {
                padding: 12px 20px;
            }
            .filter-container label {
                font-size: 1.1em;
            }
            .filter-container select {
                padding: 10px 15px;
                font-size: 1.1em;
                /* Adjusted background-position for the arrow on larger screens */
                background-position: right 12px center;
                padding-right: 40px; /* Maintain padding for arrow */
            }
            .orders-container {
                grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); /* Larger minmax for cards */
                gap: 25px;
            }
            .order-card {
                padding: 25px;
                border-radius: 18px;
            }
            .order-card h3 {
                font-size: 1.6em;
                margin-bottom: 12px;
            }
            .order-card p {
                font-size: 1em;
                margin: 8px 0;
            }
            .contact-btn {
                padding: 12px 20px;
                font-size: 1em;
            }
        }

        /* Desktops / Laptops */
        @media (min-width: 1024px) {
            body {
                padding: 40px;
            }
            h2 {
                font-size: 3.2em;
                margin-bottom: 40px;
            }
            .back-btn {
                padding: 15px 30px;
                font-size: 1.2em;
                margin-bottom: 30px;
            }
            .filter-container select {
                /* Fine-tune background-position for very large screens if needed */
                background-position: right 15px center;
                padding-right: 45px; /* Increase padding further for more space */
            }
            .orders-container {
                grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); /* Even larger cards */
                gap: 30px;
            }
            .order-card {
                padding: 30px;
                border-radius: 20px;
            }
            .order-card h3 {
                font-size: 1.8em;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a class="back-btn" href="SellersDashBoard.php">&larr; Back to Dashboard</a>
        <h2>Purchased Items</h2>

        <div class="sort-wrapper">
            <div class="filter-container">
                <form method="GET" action="viewPurchasedItems.php">
                    <label for="sort">Sort by Purchase Date:</label>
                    <select name="sort" onchange="this.form.submit()">
                        <option value="desc" <?= $sort === 'desc' ? 'selected' : '' ?>>Newest First</option>
                        <option value="asc" <?= $sort === 'asc' ? 'selected' : '' ?>>Oldest First</option>
                    </select>
                </form>
            </div>
        </div>

        <div class="orders-container">
        <?php
        // Check if there are any results (purchases) to display.
        if ($result->num_rows > 0) {
            // Loop through each row (purchased item) fetched from the database.
            while ($row = $result->fetch_assoc()) {
                // Sanitize and retrieve product and buyer information from the current row.
                $product_name = !empty($row['itemName']) ? $row['itemName'] : 'Unnamed Product';
                $quantity = $row['quantity'];
                $price_per_item = $row['price']; // Get price per item from the database
                $total = $quantity * $price_per_item; // Calculate total sale amount
                $buyer_name = $row['buyer_name'] ?? 'Unknown Buyer';
                $buyer_email = $row['buyer_email'] ?? 'N/A';
                $buyer_phone = $row['buyer_phone'] ?? 'N/A';
                $order_date = $row['order_date'];

                // Output an HTML div for each order card.
                echo "<div class='order-card'>";
                echo "<h3>" . htmlspecialchars($product_name) . "</h3>"; // Display product name
                echo "<p><strong>Quantity Sold:</strong> " . htmlspecialchars($quantity) . "</p>"; // Display quantity sold
                echo "<p><strong>Price per Item:</strong> R" . htmlspecialchars(number_format((float)$price_per_item, 2)) . "</p>"; // Display price per item, formatted as currency
                echo "<p><strong>Total Sale:</strong> R" . htmlspecialchars(number_format($total, 2)) . "</p>"; // Display total sale, formatted as currency
                echo "<p><strong>Buyer:</strong> " . htmlspecialchars($buyer_name) . "</p>"; // Display buyer's name
                // Display buyer's email, making it a clickable mailto link while maintaining styling.
                echo "<p><strong>Email:</strong> <a href='mailto:" . htmlspecialchars($buyer_email) . "' style='color: inherit; text-decoration: none;'>" . htmlspecialchars($buyer_email) . "</a></p>";
                echo "<p><strong>Phone:</strong> " . htmlspecialchars($buyer_phone) . "</p>"; // Display buyer's phone number
                echo "<p><strong>Date Purchased:</strong> " . htmlspecialchars($order_date) . "</p>"; // Display purchase date
                // Button to contact the buyer via email.
                echo "<a class='contact-btn' href='mailto:" . htmlspecialchars($buyer_email) . "'>Contact Buyer</a>";
                echo "</div>";
            }
        } else {
            // Display a message if no purchases are found for the seller.
            echo "<p style='color: var(--cool-gray); text-align: center; margin-top: 30px; width: 100%;'>No purchases found.</p>";
        }

        // Close the prepared statement to free up resources.
        $stmt->close();
        // Check if the database connection ($conn) is still set before attempting to close it.
        // This prevents errors if the connection was already closed or not successfully established.
        if (isset($conn)) {
            $conn->close();
        }
        ?>
        </div>
    </div>
</body>
</html>