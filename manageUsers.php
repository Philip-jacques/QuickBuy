<?php
/**
 * @file manageUsers.php
 * This script provides an administrative interface for managing user accounts,
 * including updating user details and deleting users. It incorporates search
 * and filter functionalities for user listings.
 *
 * This file is part of the QuickBuy Admin Panel.
 *
 * @category Admin
 * @package  UserManagement
 * @author   Your Name <your.email@example.com>
 * @license  http://opensource.org/licenses/MIT MIT License
 * @link     http://www.yourwebsite.com/admin/manageUsers.php
 */

// Start a new session or resume the existing one.
session_start();

// Include the database connection file.
include 'db.php';

// Check if the user is logged in and has 'admin' role.
// If not, redirect them to the login page and terminate script execution.
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: LoginPage.php");
    exit();
}

// Initialize an empty message variable to store success or error messages for the user.
$message = '';

/**
 * Handle user update requests.
 * This block processes POST requests when a user's details are submitted for modification.
 */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['edit_id'])) {
    // Sanitize and retrieve user input for update.
    $editId = $_POST['edit_id'];
    $editUsername = $_POST['edit_username'];
    $editEmail = $_POST['edit_email'];
    $editRole = $_POST['edit_role'];

    // Basic input validation. Enhance with more robust validation (e.g., uniqueness checks for username/email) as needed.
    if (empty($editUsername) || empty($editEmail) || empty($editRole)) {
        $message = "All fields are required for update.";
    } elseif (!filter_var($editEmail, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format.";
    } else {
        // Prepare an SQL UPDATE statement to prevent SQL injection.
        $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?");
        if ($stmt) {
            // Bind parameters to the prepared statement. 'sssi' stands for string, string, string, integer.
            $stmt->bind_param("sssi", $editUsername, $editEmail, $editRole, $editId);
            // Execute the prepared statement.
            if ($stmt->execute()) {
                $message = "User updated successfully.";
            } else {
                // Log and display error if execution fails.
                $message = "Error updating user: " . $stmt->error;
            }
            // Close the statement.
            $stmt->close();
        } else {
            // Log and display database error if statement preparation fails.
            $message = "Database error preparing update statement.";
        }
    }
}

/**
 * Handle user deletion requests.
 * This block processes GET requests when a user is to be deleted.
 */
if (isset($_GET['delete_user'])) {
    // Sanitize the user ID received from GET request to ensure it's an integer.
    $userId = intval($_GET['delete_user']);
    if ($userId > 0) {
        // Prepare an SQL DELETE statement.
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        if ($stmt) {
            // Bind the user ID parameter. 'i' stands for integer.
            $stmt->bind_param("i", $userId);
            // Execute the prepared statement.
            if ($stmt->execute()) {
                $message = "User deleted.";
            } else {
                // Log and display error if execution fails.
                $message = "Error deleting user: " . $stmt->error;
            }
            // Close the statement.
            $stmt->close();
        } else {
            // Log and display database error if statement preparation fails.
            $message = "Database error preparing delete statement.";
        }
    } else {
        $message = "Invalid user ID for deletion.";
    }
    // Redirect to clear GET parameters after deletion to prevent re-deletion on refresh.
    header("Location: manageUsers.php");
    exit();
}

/**
 * Search and filter functionality.
 * This section constructs the SQL query based on search terms and role filters.
 */
// Get search term from GET request, default to empty string.
$search = $_GET['search'] ?? '';
// Get filter role from GET request, default to 'all'.
$filter = $_GET['filter'] ?? 'all';

// Base SQL query to select all users, with conditions for username or email matching the search term.
$query = "SELECT * FROM users WHERE (username LIKE CONCAT('%', ?, '%') OR email LIKE CONCAT('%', ?, '%'))";
// Initial parameters array for binding. The first element 'ss' defines parameter types (two strings for search).
$params = ["ss", $search, $search];

// If a specific role filter is applied, append it to the query.
if ($filter !== 'all') {
    $query .= " AND role = ?";
    $params[0] .= "s"; // Add 's' to parameter types for the new string parameter.
    $params[] = $filter; // Add the filter value to the parameters array.
}

// Prepare the SQL statement.
$stmt = $conn->prepare($query);

// Check if the statement was prepared successfully.
if ($stmt) {
    // Extract the parameter types string from the first element of $params
    $paramTypes = array_shift($params); // Removes the type string and assigns it to $paramTypes

    // Now, $params contains only the values to be bound
    // Use the splat operator (...) to pass the elements of $params as separate arguments
    // to bind_param. All values in $params must already be variables.
    $stmt->bind_param($paramTypes, ...$params); // Corrected line

    // Execute the prepared statement.
    $stmt->execute();
    // Get the result set from the executed statement.
    $results = $stmt->get_result();
} else {
    // Handle prepare error: log the error and set a user-friendly message.
    error_log("Failed to prepare statement for user management: " . $conn->error);
    $message = "Database error occurred while fetching users.";
    $results = false; // Indicate that no results are available due to the error.
}
// $conn->close(); // Moved to the end of the file within the final PHP block.
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - QuickBuy Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* CSS variables for consistent theming. Defined within a :root selector for global access. */
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

            /* Admin Dashboard Specific Colors */
            --admin-bg-start: var(--deep-space-blue);
            --admin-bg-end: var(--midnight-green-3);
            --dashboard-card-bg: var(--white-pop);
            --card-border: var(--cool-gray);
            --header-color: var(--oxford-blue);
            --section-title-color: var(--sapphire);
            --text-color-primary: var(--dark-font);
            --text-color-secondary: var(--paynes-gray);
            --button-primary-bg: var(--true-blue);
            --button-primary-hover: var(--sapphire);
            --button-success-bg: var(--midnight-green);
            --button-success-hover: var(--caribbean-current);
            --button-danger-bg: #dc3545; /* Standard red for delete */
            --button-danger-hover: #c82333;
            --button-outline-secondary-bg: var(--ghost-white);
            --button-outline-secondary-text: var(--paynes-gray);
            --button-outline-secondary-border: var(--cool-gray);
            --button-outline-secondary-hover-bg: var(--paynes-gray);
            --button-outline-secondary-hover-text: var(--white-pop);
            --table-header-bg: var(--oxford-blue-2);
            --table-border-color: #e0e0e0; /* Lighter border for table cells */
            --table-row-even-bg: #fdfdfd; /* Very subtle light background for even rows */
            --alert-info-bg: #e0f7fa; /* Light blue for info alerts */
            --alert-info-color: #007bb2;
            --shadow-light: rgba(0, 0, 0, 0.08);
            --shadow-medium: rgba(0, 0, 0, 0.15);
        }

        /* Universal Box-Sizing for consistent layout calculation. */
        html {
            box-sizing: border-box;
        }
        *, *::before, *::after {
            box-sizing: inherit;
        }

        /* Body styling: background gradient, font, centering content. */
        body {
            background: linear-gradient(135deg, var(--admin-bg-start), var(--admin-bg-end));
            background-size: 300% 300%;
            animation: bgShift 20s ease infinite; /* Animated background effect. */
            min-height: 100vh;
            font-family: 'Poppins', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            color: var(--text-color-primary);
        }

        /* Keyframes for background animation. */
        @keyframes bgShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Container for the main content. */
        .container {
            max-width: 95%;
            margin: 30px auto;
            padding: 25px;
            background: var(--dashboard-card-bg);
            border-radius: 12px;
            box-shadow: 0 4px 15px var(--shadow-light);
            animation: fadeIn 0.8s ease-out forwards; /* Fade-in animation on load. */
        }

        /* Keyframes for fade-in animation. */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Heading styling with an underline effect. */
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

        /* Alert message styling. */
        .alert {
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            padding: 12px 20px;
            text-align: center;
            background-color: var(--alert-info-bg);
            color: var(--alert-info-color);
            border: 1px solid lighten(var(--alert-info-color), 20%); /* Lighter border from alert text color */
        }

        /* --- Form Elements Styling --- */
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--text-color-primary);
        }
        /* Styling for text inputs and select boxes. */
        .form-control, .form-select {
            display: block;
            width: 100%;
            padding: 10px 12px;
            font-size: 0.9rem;
            font-family: 'Poppins', sans-serif;
            line-height: 1.5;
            color: var(--text-color-primary);
            background-color: var(--white-pop);
            background-clip: padding-box;
            border: 1px solid var(--card-border);
            border-radius: 8px;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
            -webkit-appearance: none; /* Remove default styling for selects */
            -moz-appearance: none;
            appearance: none;
        }
        /* Focus state for form controls. */
        .form-control:focus, .form-select:focus {
            border-color: var(--true-blue);
            outline: 0;
            box-shadow: 0 0 0 0.25rem rgba(4, 102, 200, 0.25); /* Focus ring with theme color */
        }
        /* Custom arrow for select boxes. */
        .form-select {
            padding-right: 2.25rem; /* Space for custom arrow */
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 16px 12px;
        }

        /* --- Search and Filter Container Layout --- */
        .search-filter-container {
            display: flex;
            flex-wrap: wrap; /* Allows wrapping on smaller screens */
            gap: 15px; /* Space between items */
            margin-bottom: 25px;
            align-items: flex-end; /* Align items to the bottom if they have different heights */
        }
        .search-filter-item {
            flex: 1; /* Allows items to grow and shrink */
            min-width: 180px; /* Minimum width before wrapping */
        }
        .search-filter-item.button-item {
            flex-grow: 0; /* Prevent button from growing too much */
            min-width: 120px;
        }

        /* --- Buttons Styling --- */
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
            width: auto; /* Reset width for individual buttons */
        }
        .btn-sm { /* Smaller buttons for table actions */
            padding: 6px 12px;
            font-size: 0.8rem;
            border-radius: 6px;
        }

        /* Primary button (for search/filter) */
        .btn-primary {
            background-color: var(--button-primary-bg);
            color: var(--light-font);
            border-color: var(--button-primary-bg);
        }
        .btn-primary:hover {
            background-color: var(--button-primary-hover);
            border-color: var(--button-primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.2);
        }

        /* Success button (for update) */
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

        /* Danger button (for delete) */
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
        .btn-block { /* For full width buttons */
            display: block;
            width: 100%;
        }

        /* --- Table Styling --- */
        .table-responsive {
            overflow-x: auto; /* Allows table to scroll horizontally on small screens */
            -webkit-overflow-scrolling: touch; /* Smooth scrolling for touch devices */
        }

        table {
            width: 100%;
            border-collapse: separate; /* Allows border-radius on table */
            border-spacing: 0;
            margin-top: 20px;
            background-color: var(--dashboard-card-bg);
            border-radius: 12px;
            overflow: hidden; /* Ensures rounded corners are applied */
            box-shadow: 0 4px 15px var(--shadow-light);
            border: 1px solid var(--table-border-color); /* Overall table border */
        }

        table th, table td {
            border: 1px solid var(--table-border-color); /* Individual cell borders */
            padding: 12px 15px; /* More generous padding */
            text-align: left;
            vertical-align: middle;
            font-size: 0.88rem; /* Slightly larger font */
            color: var(--text-color-primary);
        }

        table thead th {
            background-color: var(--table-header-bg);
            color: var(--light-font);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.8rem;
            border-bottom: 1px solid var(--table-header-bg); /* Match background for header */
        }

        table tbody tr:nth-child(even) {
            background-color: var(--table-row-even-bg); /* Subtle alternate row color */
        }

        table tbody tr:last-child td {
            border-bottom: none; /* No bottom border for last row cells */
        }

        /* Styling for inputs within table cells */
        table td .form-control, table td .form-select {
            padding: 6px 8px; /* Smaller padding for inputs in table cells */
            font-size: 0.8rem;
            height: auto; /* Allow height to adjust */
            min-width: 80px; /* Ensure inputs don't become too narrow */
        }

        /* Table actions column */
        table td:last-child {
            white-space: nowrap; /* Prevent buttons from wrapping to new lines */
            text-align: center; /* Center buttons in action column */
        }
        table td .btn {
            margin: 0 2px; /* Small gap between action buttons */
        }
        .mb-1 { /* To match the margin-bottom on update button */
            margin-bottom: 4px;
        }


        /* --- Media Queries for Responsiveness --- */
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
            .alert {
                font-size: 1rem;
                padding: 15px 25px;
            }
            .form-control, .form-select {
                font-size: 1rem;
                padding: 12px 15px;
            }
            .btn {
                padding: 12px 22px;
                font-size: 1rem;
            }
            .btn-sm {
                padding: 8px 15px;
                font-size: 0.85rem;
            }
            table th, table td {
                font-size: 0.9rem;
                padding: 15px 18px;
            }
            table td .form-control, table td .form-select {
                font-size: 0.85rem;
                padding: 8px 10px;
            }
            .search-filter-container {
                flex-wrap: nowrap; /* Prevent wrapping on larger screens */
            }
            .search-filter-item {
                min-width: unset; /* Remove min-width to let flex handle it */
            }
            .search-filter-item.button-item {
                width: auto; /* Adjust button item width */
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
            .search-filter-container {
                flex-direction: column; /* Stack search/filter vertically */
                gap: 10px;
            }
            .search-filter-item {
                width: 100%; /* Full width for stacked items */
                min-width: unset;
            }
            .btn {
                padding: 8px 15px;
                font-size: 0.85rem;
                width: 100%; /* Full width buttons for search/filter */
            }
            table th, table td {
                font-size: 0.75rem;
                padding: 8px 10px;
            }
            table td .form-control, table td .form-select {
                padding: 4px 6px;
                font-size: 0.7rem;
            }
            table td:last-child {
                text-align: left; /* Back to left alignment for actions if table is narrow */
            }
            table td .btn {
                margin: 4px 0; /* Stack action buttons */
                display: block;
                width: 100%; /* Full width for action buttons */
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
    <h2>User Management</h2>

    <?php if ($message): // Display alert message if it exists. ?>
        <div class="alert"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <form method="GET" class="search-filter-container">
        <div class="search-filter-item">
            <input type="text" name="search" class="form-control" placeholder="Search by username or email" value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <div class="search-filter-item">
            <select name="filter" class="form-select">
                <option value="all" <?php if ($filter == 'all') echo 'selected'; ?>>All Roles</option>
                <option value="buyer" <?php if ($filter == 'buyer') echo 'selected'; ?>>Buyers</option>
                <option value="seller" <?php if ($filter == 'seller') echo 'selected'; ?>>Sellers</option>
            </select>
        </div>
        <div class="search-filter-item button-item">
            <button type="submit" class="btn btn-primary btn-block">Search/Filter</button>
        </div>
    </form>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($results && $results->num_rows > 0): // Check if there are results to display. ?>
                <?php while ($row = $results->fetch_assoc()): // Loop through each user row. ?>
                    <tr>
                        <form method="POST">
                            <td><?php echo $row['id']; ?></td>
                            <td><input type="text" name="edit_username" value="<?php echo htmlspecialchars($row['username']); ?>" class="form-control" required></td>
                            <td><input type="email" name="edit_email" value="<?php echo htmlspecialchars($row['email']); ?>" class="form-control" required></td>
                            <td>
                                <select name="edit_role" class="form-select" required>
                                    <option value="buyer" <?php if ($row['role'] == 'buyer') echo 'selected'; ?>>Buyer</option>
                                    <option value="seller" <?php if ($row['role'] == 'seller') echo 'selected'; ?>>Seller</option>
                                    <?php if ($row['role'] === 'admin'): // Preserve admin role if it exists and prevent changing it easily. ?>
                                        <option value="admin" selected>Admin</option>
                                    <?php endif; ?>
                                </select>
                            </td>
                            <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                            <td>
                                <input type="hidden" name="edit_id" value="<?php echo $row['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-success mb-1">Update</button>
                                <a href="?delete_user=<?php echo $row['id']; ?>" onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.');" class="btn btn-sm btn-danger">Delete</a>
                            </td>
                        </form>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" style="text-align: center; padding: 20px;">No users found matching your criteria.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="text-center mt-4">
        <a href="adminDashboard.php" class="btn btn-outline-secondary">← Back to Dashboard</a>
    </div>
</div>

</body>
</html>
<?php
// Close the prepared statement and database connection at the very end of the script.
// This ensures resources are freed after all database operations are complete.
if (isset($stmt) && $stmt instanceof mysqli_stmt) {
    $stmt->close();
}
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>
