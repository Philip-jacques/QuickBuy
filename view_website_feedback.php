<?php
/**
 * adminFeedbackReviews.php
 *
 * This page displays all website feedback reviews to an administrator.
 * It requires the user to be logged in and have an 'admin' role.
 * Feedback data is fetched from the database and presented in a responsive table.
 */

// Start a new session or resume the existing one.
session_start();

// Check if the user is logged in and has the 'admin' role.
// If not, redirect them to the login page and terminate the script.
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: LoginPage.php");
    exit();
}

// --- Database Connection Details ---
// Define constants for database connection parameters for security and readability.
$servername = "sql102.infinityfree.com";
$dbusername = "if0_39013745";
$dbpassword = "fsnMAST1Gm37";
$dbname = "if0_39013745_quickbuy_db";

// Initialize an empty array to store feedback data.
$feedbackData = [];
// Initialize an empty string to store any error messages during database operations.
$errorMessage = '';

try {
    // Establish a new PDO database connection.
    // The DSN (Data Source Name) specifies the database type (mysql), host, and database name.
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $dbusername, $dbpassword);
    // Set the PDO error mode to exception. This makes PDO throw PDOException on errors,
    // which can then be caught and handled.
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Prepare a SQL statement to fetch all feedback entries from the 'website_feedback' table.
    // The results are ordered by 'feedback_date' in descending order (newest first).
    $stmt = $conn->prepare("SELECT * FROM website_feedback ORDER BY feedback_date DESC");
    // Execute the prepared statement.
    $stmt->execute();
    // Fetch all results as an associative array.
    $feedbackData = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Catch any PDO exceptions (database errors).
    // Store the error message for display to the user.
    $errorMessage = "Database error: " . $e->getMessage();
    // Log the detailed error message to the server's error log for debugging purposes.
    error_log("Admin feedback view DB error: " . $e->getMessage());
} finally {
    // The 'finally' block ensures this code runs regardless of whether an exception occurred.
    // Close the database connection if it was successfully opened, to free up resources.
    if (isset($conn)) {
        $conn = null;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Website Feedback Reviews - QuickBuy Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /*
        * CSS Styling for the Admin Feedback Reviews Page
        *
        * This section defines the visual presentation of the page,
        * including layout, colors, typography, and responsive adjustments
        * for various screen sizes (desktop, tablet, mobile).
        */

        /* Test 2 - Focusing on content wrapping within columns */
        html {
            /* Set box-sizing to border-box globally for easier layout calculations. */
            box-sizing: border-box;
            /* Prevent overall page horizontal scroll if it's there. */
            overflow-x: hidden;
            /* Allow vertical scroll for the whole page content. */
            overflow-y: auto;
        }
        *, *::before, *::after {
            /* Inherit box-sizing for all elements and pseudo-elements. */
            box-sizing: inherit;
        }

        /* Define CSS variables for consistent theming and easier modification. */
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
            --link-color: var(--sapphire); /* For URL links */
            --link-hover-color: var(--true-blue);
            --star-color: gold; /* For star ratings */
            --error-bg: #f8d7da; /* Light red for error messages */
            --error-border: #f5c6cb;
            --error-text: #721c24;
        }

        body {
            /* Apply a linear gradient background with animation. */
            background: linear-gradient(135deg, var(--admin-bg-start), var(--admin-bg-end));
            background-size: 300% 300%;
            animation: bgShift 20s ease infinite;
            /* Ensure the body takes at least the full viewport height. */
            min-height: 100vh;
            /* Set the primary font family. */
            font-family: 'Poppins', sans-serif;
            /* Use flexbox for overall page layout to center content. */
            display: flex;
            justify-content: center;
            /* Align items to the top to allow scrolling of body content. */
            align-items: flex-start;
            padding: 20px;
            /* Set the default text color. */
            color: var(--text-color-primary);
            /* Ensure no horizontal scroll on the body itself. */
            overflow-x: hidden;
        }

        /* Keyframe animation for the background gradient shift. */
        @keyframes bgShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .container {
            /* Max width for desktop views, allows more columns. */
            max-width: 98%;
            /* Ensure it takes available width. */
            width: 100%;
            /* Center the container horizontally. */
            margin: 30px auto;
            padding: 25px;
            /* Set background and styling for the main content card. */
            background: var(--dashboard-card-bg);
            border-radius: 12px;
            box-shadow: 0 4px 15px var(--shadow-light);
            /* Apply a fade-in animation to the container. */
            animation: fadeIn 0.8s ease-out forwards;
        }

        /* Keyframe animation for the container fade-in effect. */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        h2 {
            /* Center align the main heading. */
            text-align: center;
            margin-bottom: 30px;
            font-weight: 700;
            font-size: 2.2rem;
            color: var(--header-color);
            position: relative;
            padding-bottom: 10px;
        }

        /* Pseudo-element for an underline effect on the main heading. */
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
            /* Allow horizontal scroll for the table if its content is too wide. */
            overflow-x: auto;
            /* Enable smooth scrolling on touch devices. */
            -webkit-overflow-scrolling: touch;
            margin-top: 20px;
            background-color: var(--dashboard-card-bg);
            border-radius: 12px;
            box-shadow: 0 4px 15px var(--shadow-light);
            border: 1px solid var(--table-border-color);
        }

        .feedback-table {
            /* Default to 100% width, allowing content to expand it if necessary. */
            width: 100%;
            /* Collapse table borders for a clean look. */
            border-collapse: separate;
            border-spacing: 0;
        }

        .feedback-table th, .feedback-table td {
            /* Apply common border, padding, and text alignment for table cells. */
            border: 1px solid var(--table-border-color);
            padding: 12px 15px;
            text-align: left;
            /* Align content to top for longer text. */
            vertical-align: top;
            font-size: 0.85rem;
            color: var(--text-color-primary);
            /* DEFAULT: Allow content to wrap in cells. */
            white-space: normal;
        }

        .feedback-table thead th {
            /* Styling for table header cells. */
            background-color: var(--table-header-bg);
            color: var(--light-font);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.8rem;
            border-bottom: 1px solid var(--table-header-bg);
            /* Headers should generally stay on one line. */
            white-space: nowrap;
        }

        /* Specific nowrap for cells that should NOT wrap (compact data like IDs, Ratings). */
        .feedback-table td:nth-child(1), /* ID */
        .feedback-table td:nth-child(2), /* User */
        .feedback-table td:nth-child(3), /* Rating */
        .feedback-table td:nth-child(4), /* Visit Purpose */
        .feedback-table td:nth-child(8), /* Date */
        .feedback-table td:nth-child(11) { /* IP Address */
            white-space: nowrap;
        }

        /* Styles for comment fields - ensure they wrap. */
        .feedback-table td:nth-child(5), /* Liked Comments */
        .feedback-table td:nth-child(6), /* Improvements */
        .feedback-table td:nth-child(7) { /* Other Comments */
            /* Explicitly allow wrapping. */
            white-space: normal;
            /* Ensure long words break. */
            word-wrap: break-word;
            /* Example max-width for comments to hint at horizontal scroll. */
            max-width: 250px;
        }

        /* Styles for URL and Browser Info - allow wrapping and break long strings. */
        .feedback-table td:nth-child(9), /* Page URL */
        .feedback-table td:nth-child(10) { /* Browser Info */
            /* Allow wrapping. */
            white-space: normal;
            /* Crucial for long URLs and browser strings to break. */
            word-break: break-all;
            /* Example max-width for URLs/browser info. */
            max-width: 280px;
        }

        /* Styling for even-numbered table rows. */
        .feedback-table tbody tr:nth-child(even) {
            background-color: var(--table-row-even-bg);
        }

        .feedback-table a {
            /* Styling for links within the table. */
            color: var(--link-color);
            text-decoration: none;
            font-weight: 500;
            /* Ensure URLs still break within the cell if they're very long. */
            word-break: break-all;
            transition: color 0.2s ease-in-out;
        }
        .feedback-table a:hover {
            color: var(--link-hover-color);
            text-decoration: underline;
        }

        .star-rating-display {
            /* Styling for the star rating display. */
            color: var(--star-color);
            font-size: 1.2em;
            letter-spacing: 1px;
            /* Keep stars on one line. */
            white-space: nowrap;
        }

        /* --- Back Button --- */
        .back-button {
            text-align: center;
            margin-top: 30px;
        }

        .btn {
            /* Generic button styling. */
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
            /* Specific styling for the secondary outline button. */
            background-color: transparent;
            color: var(--button-outline-secondary-text);
            border-color: var(--button-outline-secondary-border);
        }
        .btn-outline-secondary:hover {
            /* Hover effects for the secondary outline button. */
            background-color: var(--button-outline-secondary-hover-bg);
            color: var(--button-outline-secondary-hover-text);
            border-color: var(--button-outline-secondary-hover-bg);
            transform: translateY(-2px);
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.2);
        }

        /* --- Empty State / Error Message --- */
        .empty-message {
            /* Styling for messages when no feedback is available. */
            text-align: center;
            padding: 30px;
            color: var(--text-color-secondary);
            font-size: 1rem;
        }

        .alert-error { /* Custom alert class for error messages */
            /* Styling for error messages. */
            background-color: var(--error-bg);
            color: var(--error-text);
            border: 1px solid var(--error-border);
            border-radius: 8px;
            padding: 15px 20px;
            margin-top: 20px;
            text-align: center;
            font-size: 0.95rem;
            /* Adjust width to content and center it. */
            width: fit-content;
            margin-left: auto;
            margin-right: auto;
            /* Prevent alert from overflowing on small screens. */
            max-width: 90%;
        }

        /* --- Media Queries --- */

        /* Styles for large desktops and wider screens. */
        @media (min-width: 992px) {
            .container {
                max-width: 1400px;
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
            .feedback-table th, .feedback-table td {
                font-size: 0.9rem;
                padding: 15px 18px;
            }
            /* Re-applying text-wrap-cell for truncation if desired on desktop.
               Apply this class to relevant <td> elements in HTML if you want line clamping. */
            .text-wrap-cell {
                white-space: normal;
                word-wrap: break-word;
                overflow: hidden;
                text-overflow: ellipsis;
                display: -webkit-box;
                -webkit-line-clamp: 4; /* Allow more lines for comments */
                -webkit-box-orient: vertical;
                max-width: 300px; /* Optional: limit comment column width */
            }
            /* Specific treatment for URL and Browser Info on desktop */
            .feedback-table td:nth-child(9), /* Page URL */
            .feedback-table td:nth-child(10) { /* Browser Info */
                max-width: 300px; /* Limit how wide they can get before wrapping */
                word-wrap: break-word;
                white-space: normal; /* Ensure they wrap */
            }
        }

        /* Mobile specific styles (Smartphones and smaller tablets in portrait). */
        @media (max-width: 767px) {
            body {
                padding: 10px;
            }
            .container {
                padding: 15px;
                border-radius: 8px;
                margin: 20px auto; /* Adjust margin for mobile */
            }
            h2 {
                font-size: 1.8rem;
                margin-bottom: 20px;
            }

            /* --- Table specific mobile styling --- */
            .table-responsive {
                border: none; /* Remove outer border from table-responsive */
                box-shadow: none; /* Remove shadow on mobile for cleaner look */
                /* Hide horizontal scroll on mobile as table rows will stack vertically. */
                overflow-x: hidden;
            }

            .feedback-table {
                border: none; /* Remove table outer border */
                margin-top: 0;
            }

            .feedback-table thead {
                display: none; /* Hide table header on small screens */
            }

            .feedback-table tbody {
                /* Make tbody a block to allow rows to stack. */
                display: block;
                width: 100%;
            }

            .feedback-table tbody tr {
                /* Make table rows stack vertically. */
                display: block;
                margin-bottom: 15px; /* Space between stacked rows */
                border: 1px solid var(--table-border-color);
                border-radius: 8px;
                overflow: hidden; /* Ensure content stays within rounded corners */
                padding: 10px; /* Add some padding to the whole row */
                background-color: var(--dashboard-card-bg); /* Ensure background is white for each row */
                box-shadow: 0 2px 8px rgba(0,0,0,0.05); /* Subtle shadow for each row */
            }

            .feedback-table tbody td {
                /* Use flexbox for label-value pairing within each cell. */
                display: flex;
                /* Space out label and value. */
                justify-content: space-between;
                /* Align items to the top. */
                align-items: flex-start;
                padding: 8px 0; /* Vertical padding, no horizontal padding */
                /* Separator between cells. */
                border-bottom: 1px solid var(--table-border-color);
                font-size: 0.9rem; /* Slightly larger font for mobile data */
                /* Ensure long words break within the value side. */
                word-break: break-word;
                /* Allow text wrapping on mobile. */
                white-space: normal;
            }

            .feedback-table tbody td:last-child {
                border-bottom: none; /* No border for the last cell in a row */
            }

            .feedback-table tbody td::before {
                /* Pseudo-element to display data labels on mobile. */
                content: attr(data-label);
                font-weight: 600;
                color: var(--section-title-color); /* Themed color for labels */
                margin-right: 15px; /* Space between label and value */
                flex-basis: 120px; /* Give labels a fixed width to align them */
                flex-shrink: 0; /* Prevent label from shrinking */
                text-align: left; /* Align label text */
            }

            /* Assign data-labels for stacked table cells. These labels will be displayed by the ::before pseudo-element. */
            .feedback-table tbody td:nth-of-type(1)::before { content: "Feedback ID:"; }
            .feedback-table tbody td:nth-of-type(2)::before { content: "User:"; }
            .feedback-table tbody td:nth-of-type(3)::before { content: "Rating:"; }
            .feedback-table tbody td:nth-of-type(4)::before { content: "Visit Purpose:"; }
            .feedback-table tbody td:nth-of-type(5)::before { content: "Liked:"; }
            .feedback-table tbody td:nth-of-type(6)::before { content: "Improvements:"; }
            .feedback-table tbody td:nth-of-type(7)::before { content: "Other Comments:"; }
            .feedback-table tbody td:nth-of-type(8)::before { content: "Date:"; }
            .feedback-table tbody td:nth-of-type(9)::before { content: "URL:"; }
            .feedback-table tbody td:nth-of-type(10)::before { content: "Browser:"; }
            .feedback-table tbody td:nth-of-type(11)::before { content: "IP:"; }

            /* Ensure all content wraps properly on mobile, removing any desktop-specific constraints. */
            .feedback-table td {
                white-space: normal;
                word-break: break-word;
                max-width: unset; /* Remove any desktop max-width constraints */
                overflow: visible; /* Ensure content is visible */
                text-overflow: unset; /* Remove ellipsis */
                -webkit-line-clamp: unset; /* Remove line clamping */
                display: flex; /* Keep flex for label-value */
            }

            .feedback-table td a {
                word-break: break-all; /* Ensure URLs still break within the stacked cell */
            }

            .star-rating-display {
                font-size: 1.1em; /* Adjust star size for mobile */
            }

            .back-button {
                margin-top: 25px;
            }
            .btn-outline-secondary {
                width: 100%; /* Make button full width on mobile */
                padding: 12px 18px;
                font-size: 1rem;
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
    <h2>Website Feedback Reviews</h2>

    <?php
    // Display an error message if there was a database error.
    if (!empty($errorMessage)):
    ?>
        <div class="alert-error" role="alert">
            <?php echo $errorMessage; ?>
        </div>
    <?php
    // If no feedback data is found, display an empty message.
    elseif (empty($feedbackData)):
    ?>
        <p class="empty-message">No feedback has been submitted yet.</p>
    <?php
    // Otherwise, display the feedback data in a responsive table.
    else:
    ?>
        <div class="table-responsive">
            <table class="feedback-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Rating</th>
                        <th>Visit Purpose</th>
                        <th>Liked Comments</th>
                        <th>Improvements</th>
                        <th>Other Comments</th>
                        <th>Date</th>
                        <th>Page URL</th>
                        <th>Browser Info</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Loop through each feedback entry and display it as a table row.
                    foreach ($feedbackData as $feedback):
                    ?>
                        <tr>
                            <td data-label="Feedback ID:"><?php echo htmlspecialchars($feedback['feedback_id']); ?></td>
                            <td data-label="User:">
                                <?php
                                // Display username if available, otherwise display user_id or "Guest".
                                if (!empty($feedback['username'])) {
                                    echo htmlspecialchars($feedback['username']);
                                } elseif (!empty($feedback['user_id'])) {
                                    echo 'User ID: ' . htmlspecialchars($feedback['user_id']);
                                } else {
                                    echo 'Guest';
                                }
                                ?>
                            </td>
                            <td data-label="Rating:">
                                <span class="star-rating-display">
                                    <?php
                                    // Display filled stars based on the overall_rating.
                                    echo str_repeat('★', $feedback['overall_rating']);
                                    // Display empty stars for the remaining rating out of 5.
                                    echo str_repeat('☆', 5 - $feedback['overall_rating']);
                                    ?>
                                </span>
                            </td>
                            <td data-label="Visit Purpose:"><?php echo htmlspecialchars($feedback['visit_purpose']); ?></td>
                            <td data-label="Liked:"><?php echo htmlspecialchars($feedback['liked_comments']); ?></td>
                            <td data-label="Improvements:"><?php echo htmlspecialchars($feedback['improved_comments']); ?></td>
                            <td data-label="Other Comments:"><?php echo htmlspecialchars($feedback['other_comments']); ?></td>
                            <td data-label="Date:"><?php echo date('Y-m-d H:i', strtotime($feedback['feedback_date'])); ?></td>
                            <td data-label="URL:">
                                <a href="<?php echo htmlspecialchars($feedback['page_url']); ?>" target="_blank">
                                    <?php echo htmlspecialchars($feedback['page_url']); ?>
                                </a>
                            </td>
                            <td data-label="Browser:"><?php echo htmlspecialchars($feedback['browser_info']); ?></td>
                            <td data-label="IP:"><?php echo htmlspecialchars($feedback['ip_address']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <div class="back-button">
        <a href="adminDashboard.php" class="btn btn-outline-secondary">← Back to Dashboard</a>
    </div>
</div>

</body>
</html>
