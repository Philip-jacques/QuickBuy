<?php
/**
 * @file manage_admins.php
 * @brief This file handles the administration of user accounts, allowing for adding, editing,
 * deleting, and searching admin users. It includes robust access control and
 * validation to ensure only authorized users (admins and superadmins) can perform
 * these operations.
 */

// Start a new session or resume the existing one.
session_start();

// Include the database connection file.
include 'db.php';

// --- Access Control ---
// Check if the user is logged in and has 'admin' or 'superadmin' role.
// If not, redirect them to the login page and terminate script execution.
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'superadmin')) {
    header("Location: LoginPage.php");
    exit();
}

// Initialize a message variable to provide feedback to the user.
$message = '';

// --- Add Admin Functionality ---
// This block processes requests to add a new administrator.
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['new_username'], $_POST['new_email'])) {
    // Sanitize and validate input for the new admin.
    $newUsername = trim($_POST['new_username']);
    $newEmail = filter_var(trim($_POST['new_email']), FILTER_VALIDATE_EMAIL); // Validate email format.
    $newRank = $_POST['new_rank'] ?? 'normal'; // Default rank to 'normal' if not set.

    // Define allowed ranks to prevent arbitrary rank assignments.
    $allowedRanks = ['normal', 'superadmin'];
    // If the submitted rank is not allowed, default it to 'normal'.
    if (!in_array($newRank, $allowedRanks)) {
        $newRank = 'normal';
    }

    // --- Superadmin Creation Policy ---
    // A 'normal' admin cannot create a 'superadmin' account.
    // Also, if a superadmin already exists, only an existing superadmin can create another.
    if ($newRank === 'superadmin' && $_SESSION['role'] !== 'superadmin') {
        $message = "Error: Only superadmins can create new superadmin accounts.";
    } elseif (empty($newUsername) || !$newEmail) {
        // Basic validation: ensure username is not empty and email is valid.
        $message = "Error: Username and a valid email are required.";
    } else {
        // Additional check for superadmin creation:
        // Prevent adding a new 'superadmin' if one already exists and the current user is not a 'superadmin'.
        if ($newRank === 'superadmin') {
            $checkSuperadmin = $conn->prepare("SELECT COUNT(*) FROM admins WHERE rank = 'superadmin'");
            $checkSuperadmin->execute();
            $superadminCount = $checkSuperadmin->get_result()->fetch_row()[0];
            $checkSuperadmin->close(); // Close the statement after use.

            // If a superadmin already exists and the current user is not a superadmin, restrict creation.
            if ($superadminCount >= 1 && $_SESSION['role'] !== 'superadmin') {
                $message = "Error: Cannot add another superadmin if one already exists and you are not a superadmin yourself.";
            }
        }

        // If no validation errors or permission issues, proceed with adding the admin.
        if (empty($message)) {
            // Prepare a SQL statement for inserting a new admin.
            $stmt = $conn->prepare("INSERT INTO admins (username, email, `rank`) VALUES (?, ?, ?)");
            if ($stmt) {
                // Bind parameters and execute the statement.
                $stmt->bind_param("sss", $newUsername, $newEmail, $newRank);
                if ($stmt->execute()) {
                    $message = "Admin added successfully.";
                } else {
                    // Log detailed database error for debugging, show generic error to user.
                    error_log("Add Admin Error: " . $stmt->error);
                    $message = "Error adding admin. Please try again later.";
                }
                $stmt->close(); // Close the statement.
            } else {
                // Log preparation error.
                error_log("Prepare statement failed for add admin: " . $conn->error);
                $message = "An internal error occurred. Please try again.";
            }
        }
    }
}

// --- Delete Admin Functionality ---
// This block processes requests to delete an existing administrator.
if (isset($_GET['delete_admin'])) {
    // Validate and sanitize the admin ID.
    $adminId = filter_var($_GET['delete_admin'], FILTER_VALIDATE_INT);

    if ($adminId === false) { // If the ID is not a valid integer.
        $message = "Error: Invalid admin ID provided.";
    } else {
        // Fetch details of the admin to be deleted to apply specific deletion rules.
        $check = $conn->prepare("SELECT username, `rank` FROM admins WHERE id = ?");
        $check->bind_param("i", $adminId);
        $check->execute();
        $result = $check->get_result()->fetch_assoc();
        $check->close();

        // --- Superadmin Deletion Policy ---
        // Prevent deletion of 'superadmin' accounts under specific conditions.
        if ($result && $result['rank'] === 'superadmin') {
            // Count current superadmins to ensure we don't delete the last one.
            $superadminCountStmt = $conn->prepare("SELECT COUNT(*) FROM admins WHERE rank = 'superadmin'");
            $superadminCountStmt->execute();
            $currentSuperadminCount = $superadminCountStmt->get_result()->fetch_row()[0];
            $superadminCountStmt->close();

            // If it's the last superadmin, prevent deletion.
            if ($currentSuperadminCount <= 1) {
                $message = "Cannot delete the last superadmin account.";
            } else if ($_SESSION['role'] !== 'superadmin') {
                // A normal admin cannot delete a superadmin account.
                $message = "You do not have permission to delete a superadmin account.";
            }
        }

        // If no prior error message, proceed with deletion.
        if (empty($message)) {
            // Prepare and execute the delete statement.
            $stmt = $conn->prepare("DELETE FROM admins WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $adminId);
                if ($stmt->execute()) {
                    $message = "Admin deleted.";
                } else {
                    error_log("Delete Admin Error: " . $stmt->error);
                    $message = "Error deleting admin. Please try again later.";
                }
                $stmt->close();
            } else {
                error_log("Prepare statement failed for delete admin: " . $conn->error);
                $message = "An internal error occurred. Please try again.";
            }
        }
    }
}

// --- Edit Admin Functionality ---
// This block processes requests to edit an existing administrator.
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['edit_id'])) {
    // Sanitize and validate input for editing.
    $editId = filter_var($_POST['edit_id'], FILTER_VALIDATE_INT);
    $editUsername = trim($_POST['edit_username']);
    $editEmail = filter_var(trim($_POST['edit_email']), FILTER_VALIDATE_EMAIL);
    $editRank = $_POST['edit_rank'] ?? 'normal';

    // Validate the rank input against allowed values.
    $allowedRanks = ['normal', 'superadmin'];
    if (!in_array($editRank, $allowedRanks)) {
        $editRank = 'normal'; // Default to normal if invalid.
    }

    // Basic validation for input fields.
    if ($editId === false || empty($editUsername) || !$editEmail) {
        $message = "Error: Invalid input for editing admin.";
    } else {
        // Fetch the current details of the admin being edited.
        $currentAdminStmt = $conn->prepare("SELECT username, `rank` FROM admins WHERE id = ?");
        $currentAdminStmt->bind_param("i", $editId);
        $currentAdminStmt->execute();
        $currentAdmin = $currentAdminStmt->get_result()->fetch_assoc();
        $currentAdminStmt->close();

        if ($currentAdmin) {
            // --- Superadmin Promotion/Demotion Policy ---
            // If the admin being edited is a 'superadmin'.
            if ($currentAdmin['rank'] === 'superadmin') {
                // Prevent a non-superadmin from demoting a superadmin.
                if ($editRank === 'normal' && $_SESSION['role'] !== 'superadmin') {
                    $message = "Error: You do not have permission to demote a superadmin.";
                } else if ($editRank === 'normal') {
                    // If a superadmin is trying to demote themselves, check if they are the last superadmin.
                    $superadminCountStmt = $conn->prepare("SELECT COUNT(*) FROM admins WHERE rank = 'superadmin'");
                    $superadminCountStmt->execute();
                    $currentSuperadminCount = $superadminCountStmt->get_result()->fetch_row()[0];
                    $superadminCountStmt->close();

                    if ($currentSuperadminCount <= 1) {
                        $message = "Error: Cannot demote the last superadmin account.";
                    }
                }
            }
            // Prevent a 'normal' admin from elevating another account to 'superadmin' if a superadmin already exists.
            if ($editRank === 'superadmin' && $_SESSION['role'] !== 'superadmin') {
                $checkExistingSuperadmin = $conn->prepare("SELECT COUNT(*) FROM admins WHERE rank = 'superadmin'");
                $checkExistingSuperadmin->execute();
                $existingSuperadminCount = $checkExistingSuperadmin->get_result()->fetch_row()[0];
                $checkExistingSuperadmin->close();

                // If a superadmin already exists, only another superadmin can elevate.
                if ($existingSuperadminCount > 0) {
                    $message = "Error: Only existing superadmins can elevate accounts to superadmin rank if one already exists.";
                }                
            }
        } else {
            $message = "Error: Admin not found for editing.";
        }

        // If no prior error message, proceed with updating the admin.
        if (empty($message)) {
            // Prepare and execute the update statement.
            $stmt = $conn->prepare("UPDATE admins SET username = ?, email = ?, `rank` = ? WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("sssi", $editUsername, $editEmail, $editRank, $editId);
                if ($stmt->execute()) {
                    $message = "Admin updated.";
                } else {
                    error_log("Edit Admin Error: " . $stmt->error);
                    $message = "Error updating admin. Please try again later.";
                }
                $stmt->close();
            } else {
                error_log("Prepare statement failed for edit admin: " . $conn->error);
                $message = "An internal error occurred. Please try again.";
            }
        }
    }
}

// --- Search Functionality ---
// Retrieve the search query from the GET request, default to empty.
$search = $_GET['search'] ?? '';
// Prepare a SQL statement to search for admins by username or email.
// Using CONCAT with '%' allows for partial matching.
$admins = $conn->prepare("SELECT * FROM admins WHERE username LIKE CONCAT('%', ?, '%') OR email LIKE CONCAT('%', ?, '%') ORDER BY id ASC");
// Bind the search parameter twice for both username and email.
$admins->bind_param("ss", $search, $search);
$admins->execute();
// Get the result set.
$results = $admins->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Admins - QuickBuy Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* --- CSS Styling  --- */
        /* Reused Color Palette and General Styles from view_website_feedback.php */
        :root {
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
            --link-color: var(--sapphire);
            --link-hover-color: var(--true-blue);
            --error-bg: #f8d7da;
            --error-border: #f5c6cb;
            --error-text: #721c24;
            --success-bg: #d4edda; /* Bootstrap success */
            --success-border: #c3e6cb;
            --success-text: #155724;
        }

        html {
            box-sizing: border-box;
            overflow-x: hidden;
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
            align-items: center; /* Center vertically on smaller content */
            padding: 20px;
            color: var(--text-color-primary);
            overflow-x: hidden;
        }

        @keyframes bgShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .container {
            max-width: 95%; /* Adjusted for this page content */
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

        /* --- Buttons Container --- */
        .button-container {
            display: flex;
            justify-content: center; /* Center the button */
            margin-top: 25px; /* Add margin above the button */
            margin-bottom: 15px; /* Add margin below the button */
            gap: 10px; /* Space between buttons if you add more */
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

        .btn-success {
            background-color: var(--sapphire); /* Custom success button color */
            border-color: var(--sapphire);
            color: var(--light-font);
        }
        .btn-success:hover {
            background-color: var(--true-blue);
            border-color: var(--true-blue);
            transform: translateY(-2px);
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.2);
        }

        .btn-primary {
            background-color: var(--midnight-green); /* Custom primary button color */
            border-color: var(--midnight-green);
            color: var(--light-font);
        }
        .btn-primary:hover {
            background-color: var(--midnight-green-2);
            border-color: var(--midnight-green-2);
            transform: translateY(-2px);
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.2);
        }

        .btn-danger {
            background-color: #dc3545; /* Bootstrap red */
            border-color: #dc3545;
            color: var(--light-font);
        }
        .btn-danger:hover {
            background-color: #c82333;
            border-color: #bd2130;
            transform: translateY(-2px);
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.2);
        }


        table th, table td {
            vertical-align: middle !important;
            font-size: 0.85rem;
            padding: 12px; /* Increased padding for better spacing */
            border: 1px solid var(--table-border-color); /* Added borders */
        }
        table {
            border-collapse: separate; /* Required for border-radius */
            border-spacing: 0;
            border-radius: 12px; /* Match container border-radius */
            overflow: hidden; /* Ensures borders and shadows are contained */
            box-shadow: 0 4px 15px var(--shadow-light); /* Added shadow to table */
        }

        .table-dark thead {
            background-color: var(--table-header-bg) !important; /* Custom table header background */
            color: var(--light-font);
        }
        .table-hover tbody tr:hover {
            background-color: var(--table-row-even-bg); /* Use a subtle hover effect */
        }
        .table-bordered {
            border: 1px solid var(--table-border-color);
        }
        .table-bordered th, .table-bordered td {
            border-color: var(--table-border-color);
        }
        .table tbody tr:nth-child(even) {
            background-color: var(--table-row-even-bg);
        }


        select.form-select, input.form-control {
            font-size: 0.9rem; /* Slightly larger font */
            padding: 10px; /* More padding */
            border-radius: 8px; /* More rounded */
            border: 1px solid var(--cool-gray); /* Custom border color */
        }

        /* Custom styling for select dropdown arrow */
        select.form-select {
            -webkit-appearance: none; /* Remove default arrow for Webkit browsers */
            -moz-appearance: none;    /* Remove default arrow for Mozilla browsers */
            appearance: none;         /* Remove default arrow for all browsers */
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3E%3Cpath fill='none' stroke='%23333' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3E%3C/svg%3E"); /* Custom SVG arrow */
            background-repeat: no-repeat;
            background-position: right 0.5rem center;
            background-size: 16px 12px;
            padding-right: 2.5rem;
        }

        select.form-select::-ms-expand {
            display: none; /* Hide default arrow for IE/Edge */
        }


        select.form-select:focus, input.form-control:focus {
            border-color: var(--sapphire);
            box-shadow: 0 0 0 0.25rem rgba(3, 83, 164, 0.25); /* Custom focus shadow */
        }

        .alert-info {
            background-color: var(--success-bg); /* Use success colors for info alerts */
            color: var(--success-text);
            border-color: var(--success-border);
            border-radius: 8px;
            margin-bottom: 20px; /* More margin */
            font-size: 0.95rem;
            padding: 12px;
            text-align: center;
        }
        .alert-error { /* Custom alert class for error messages, matching feedback page */
            background-color: var(--error-bg);
            color: var(--error-text);
            border: 1px solid var(--error-border);
            border-radius: 8px;
            padding: 15px 20px;
            margin-top: 20px;
            text-align: center;
            font-size: 0.95rem;
            width: fit-content;
            margin-left: auto;
            margin-right: auto;
            max-width: 90%;
        }


        .mb-4 { margin-bottom: 2rem; }
        .input-group { margin-bottom: 20px; /* Adjusted margin */ }
        .input-group .form-control { border-radius: 8px 0 0 8px; } /* More rounded */
        .input-group .btn { border-radius: 0 8px 8px 0; } /* More rounded */
        .table-responsive {
            margin-top: 20px; /* Spacing */
        }
        .btn-sm { font-size: 0.85rem; padding: 6px 10px; border-radius: 6px; }
        .badge { font-size: 0.85rem; border-radius: 6px; padding: 6px 10px; }
        .badge.bg-secondary {
            background-color: var(--paynes-gray) !important;
            color: var(--light-font);
        }
        .mt-4 { margin-top: 2.5rem; }
        .text-center { text-align: center; }
        .w-100 { width: 100%; }
        .mb-1 { margin-bottom: 0.5rem; }

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

        /* Media queries for larger screens */
        @media (min-width: 768px) {
            body {
                align-items: flex-start; /* Align to top for larger content */
            }
            .container {
                max-width: 1000px; /* Specific max-width for manage_admins */
                margin: 40px auto;
                padding: 30px;
                border-radius: 16px;
                box-shadow: 0 8px 25px var(--shadow-medium);
            }
            h2 {
                font-size: 2.5rem;
                margin-bottom: 40px;
            }
            h2::after {
                width: 90px;
            }
            .btn {
                font-size: 1rem;
                padding: 12px 20px; /* Slightly larger buttons */
                border-radius: 10px;
            }
            table th, table td {
                font-size: 0.9rem;
                padding: 15px; /* More padding */
            }
            select.form-select, input.form-control {
                font-size: 0.95rem;
                padding: 12px;
                border-radius: 10px;
            }
            .alert {
                font-size: 1rem;
                padding: 15px;
                border-radius: 10px;
            }
            .mb-4 { margin-bottom: 2.5rem; }
            .input-group { margin-bottom: 25px; }
            .btn-sm { font-size: 0.9rem; padding: 8px 12px; border-radius: 8px; }
            .badge { font-size: 0.9rem; border-radius: 8px; padding: 8px 12px; }
            .mt-4 { margin-top: 3rem; }
        }

        /* Mobile specific adjustments */
        @media (max-width: 767px) {
            body {
                padding: 10px;
            }
            .container {
                padding: 15px;
                border-radius: 8px;
                margin: 20px auto;
            }
            h2 {
                font-size: 1.8rem;
                margin-bottom: 25px;
            }

            .table-responsive table,
            .table-responsive thead,
            .table-responsive tbody,
            .table-responsive th,
            .table-responsive td,
            .table-responsive tr {
                display: block; /* Make table elements stack */
            }

            .table-responsive thead tr {
                position: absolute;
                top: -9999px;
                left: -9999px; /* Hide header row visually */
            }

            .table-responsive tr {
                border: 1px solid var(--table-border-color);
                margin-bottom: 15px;
                border-radius: 8px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.05);
                background-color: var(--dashboard-card-bg);
            }

            .table-responsive td {
                border: none;
                border-bottom: 1px solid var(--table-border-color);
                /* Remove padding-left and text-align from here, flex will manage */
                padding: 10px 15px !important; /* Adjust padding for the cell itself */
                display: flex; /* Make td a flex container */
                flex-wrap: wrap; /* Allow content to wrap if necessary */
                align-items: center; /* Vertically align label and content */
                gap: 10px; /* Add a small gap between label and content */
            }

            .table-responsive td:last-child {
                border-bottom: 0;
            }

            .table-responsive td::before {
                content: attr(data-label);
                flex-basis: 50%; /* Label takes roughly half the width initially */
                flex-shrink: 0; /* Prevent label from shrinking */
                text-align: left; /* Align label text to the left */
                font-weight: 600;
                color: var(--section-title-color);
                white-space: nowrap; /* Keep label on one line */
                overflow: hidden; /* Hide overflowed label text */
                text-overflow: ellipsis; /* Show ellipsis for overflowed label text */
                /* Remove position: absolute, left, and width from here, flex handles positioning */
            }

            /* Adjust labels for each column */
            .table-responsive td:nth-of-type(1)::before { content: "ID:"; }
            .table-responsive td:nth-of-type(2)::before { content: "Username:"; }
            .table-responsive td:nth-of-type(3)::before { content: "Email:"; }
            .table-responsive td:nth-of-type(4)::before { content: "Rank:"; }
            .table-responsive td:nth-of-type(5)::before { content: "Created:"; }
            .table-responsive td:nth-of-type(6)::before { content: "Actions:"; }

            /* Styles for the actual content within the td */
            .table-responsive td input,
            .table-responsive td select,
            .table-responsive td .badge,
            .table-responsive td form { /* Apply to all value-holding elements */
                flex-grow: 1; /* Allow content to grow and fill remaining space */
                flex-shrink: 1; /* Allow content to shrink if needed */
                min-width: 0; /* Crucial: Allows flex item to shrink smaller than its intrinsic width */
                text-align: right; /* Align value content to the right */
            }

            /* Special handling for the Actions column to ensure buttons stack nicely */
            .table-responsive td:nth-of-type(6) {
                flex-direction: column; /* Stack children vertically */
                align-items: stretch; /* Make children stretch to full width of the cell */
            }

            .table-responsive td:nth-of-type(6) form {
                width: 100%; /* Ensure form takes full width */
                flex-direction: column; /* Stack buttons within the form vertically */
                align-items: stretch; /* Make buttons within form stretch */
            }

            .table-responsive td:nth-of-type(6) .btn-sm,
            .table-responsive td:nth-of-type(6) .badge {
                width: 100%; /* Make buttons and badge take full width */
                margin-right: 0 !important; /* Remove any conflicting horizontal margins */
                margin-left: 0 !important; /* Remove any conflicting horizontal margins */
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
    <h2>Manage Admin Accounts</h2>

    <?php // Display success or error messages to the user.
    if ($message): ?>
        <div class="alert <?php echo strpos($message, 'Error') !== false || strpos($message, 'Cannot') !== false ? 'alert-error' : 'alert-info'; ?>">
            <?php echo htmlspecialchars($message); // Escaped for XSS protection to prevent script injection. ?>
        </div>
    <?php endif; ?>

    <div class="text-center mt-4 mb-4">
        <a href="adminDashboard.php" class="btn btn-primary">
            <i class="fas fa-arrow-left btn-icon"></i> Back to Dashboard
        </a>
    </div>

    <h3>Add New Admin</h3>
    <form method="POST" class="row g-2 mb-4">
        <div class="col-md-3 col-sm-12 mb-2 mb-md-0">
            <input type="text" name="new_username" placeholder="Username" class="form-control" required>
        </div>
        <div class="col-md-4 col-sm-12 mb-2 mb-md-0">
            <input type="email" name="new_email" placeholder="Email" class="form-control" required>
        </div>
        <div class="col-md-3 col-sm-12 mb-2 mb-md-0">
            <select name="new_rank" class="form-select">
                <option value="normal" selected>Normal</option>
                <option value="superadmin">Super</option>
            </select>
        </div>
        <div class="col-md-2 col-sm-12">
            <button type="submit" class="btn btn-success w-100">Add Admin</button>
        </div>
    </form>

    <h3>Search Admins</h3>
    <form method="GET" class="mb-4">
        <div class="input-group">
            <input type="text" name="search" class="form-control" placeholder="Search admin by username or email..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="btn btn-outline-secondary">Search</button>
            <?php if (!empty($search)): ?>
                <a href="manageAdmins.php" class="btn btn-outline-secondary">Clear Search</a>
            <?php endif; ?>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Rank</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($results->num_rows > 0): ?>
                    <?php while ($admin = $results->fetch_assoc()): ?>
                        <tr data-admin-id="<?php echo $admin['id']; ?>">
                            <td data-label="ID:"><?php echo htmlspecialchars($admin['id']); ?></td>
                            <td data-label="Username:">
                                <span class="view-mode-username"><?php echo htmlspecialchars($admin['username']); ?></span>
                                <input type="text" name="edit_username" class="form-control edit-mode-field" value="<?php echo htmlspecialchars($admin['username']); ?>" style="display: none;">
                            </td>
                            <td data-label="Email:">
                                <span class="view-mode-email"><?php echo htmlspecialchars($admin['email']); ?></span>
                                <input type="email" name="edit_email" class="form-control edit-mode-field" value="<?php echo htmlspecialchars($admin['email']); ?>" style="display: none;">
                            </td>
                            <td data-label="Rank:">
                                <span class="view-mode-rank">
                                    <?php
                                        $badgeClass = ($admin['rank'] === 'superadmin') ? 'bg-danger' : 'bg-secondary';
                                        echo '<span class="badge ' . $badgeClass . '">' . htmlspecialchars(ucfirst($admin['rank'])) . '</span>';
                                    ?>
                                </span>
                                <select name="edit_rank" class="form-select edit-mode-field" style="display: none;">
                                    <option value="normal" <?php echo ($admin['rank'] === 'normal') ? 'selected' : ''; ?>>Normal</option>
                                    <option value="superadmin" <?php echo ($admin['rank'] === 'superadmin') ? 'selected' : ''; ?>>Super</option>
                                </select>
                            </td>
                            <td data-label="Created:"><?php echo htmlspecialchars($admin['created_at']); ?></td>
                            <td data-label="Actions:">
                                <div class="action-buttons">
                                    <button type="button" class="btn btn-info btn-sm me-1 edit-button">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <form method="GET" style="display: inline-block;" onsubmit="return confirm('Are you sure you want to delete this admin?');">
                                        <input type="hidden" name="delete_admin" value="<?php echo $admin['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash-alt"></i> Delete
                                        </button>
                                    </form>
                                    <button type="button" class="btn btn-success btn-sm me-1 save-button" style="display: none;">
                                        <i class="fas fa-save"></i> Save
                                    </button>
                                    <button type="button" class="btn btn-warning btn-sm cancel-button" style="display: none;">
                                        <i class="fas fa-times"></i> Cancel
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center">No admin accounts found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.edit-button').forEach(button => {
        button.addEventListener('click', function() {
            const row = this.closest('tr');
            row.classList.add('editing'); // Add a class to indicate editing mode

            // Hide view mode elements and show edit mode elements
            row.querySelectorAll('.view-mode-username, .view-mode-email, .view-mode-rank, .edit-button, form button.btn-danger').forEach(el => {
                el.style.display = 'none';
            });
            row.querySelectorAll('.edit-mode-field, .save-button, .cancel-button').forEach(el => {
                el.style.display = ''; // Display as per default (e.g., block, inline-block)
            });
        });
    });

    document.querySelectorAll('.cancel-button').forEach(button => {
        button.addEventListener('click', function() {
            const row = this.closest('tr');
            row.classList.remove('editing'); // Remove editing class

            // Show view mode elements and hide edit mode elements
            row.querySelectorAll('.view-mode-username, .view-mode-email, .view-mode-rank, .edit-button, form button.btn-danger').forEach(el => {
                el.style.display = '';
            });
            row.querySelectorAll('.edit-mode-field, .save-button, .cancel-button').forEach(el => {
                el.style.display = 'none';
            });

            // Revert input values to original (displayed in view mode spans)
            row.querySelector('[name="edit_username"]').value = row.querySelector('.view-mode-username').textContent;
            row.querySelector('[name="edit_email"]').value = row.querySelector('.view-mode-email').textContent;
            // For the select, find the option that matches the current rank text
            const currentRankText = row.querySelector('.view-mode-rank .badge').textContent.toLowerCase();
            const selectElement = row.querySelector('[name="edit_rank"]');
            Array.from(selectElement.options).forEach(option => {
                if (option.value === currentRankText) {
                    option.selected = true;
                } else {
                    option.selected = false;
                }
            });
        });
    });

    document.querySelectorAll('.save-button').forEach(button => {
        button.addEventListener('click', function() {
            const row = this.closest('tr');
            const adminId = row.dataset.adminId;
            const newUsername = row.querySelector('[name="edit_username"]').value;
            const newEmail = row.querySelector('[name="edit_email"]').value;
            const newRank = row.querySelector('[name="edit_rank"]').value;

            // Basic client-side validation
            if (!newUsername.trim() || !newEmail.trim()) {
                alert('Username and Email cannot be empty.');
                return;
            }
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(newEmail)) {
                alert('Please enter a valid email address.');
                return;
            }

            // Create a form to send data via POST
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'manageAdmin.php'; // Submit to the same page

            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'edit_id';
            idInput.value = adminId;
            form.appendChild(idInput);

            const usernameInput = document.createElement('input');
            usernameInput.type = 'hidden';
            usernameInput.name = 'edit_username';
            usernameInput.value = newUsername;
            form.appendChild(usernameInput);

            const emailInput = document.createElement('input');
            emailInput.type = 'hidden';
            emailInput.name = 'edit_email';
            emailInput.value = newEmail;
            form.appendChild(emailInput);

            const rankInput = document.createElement('input');
            rankInput.type = 'hidden';
            rankInput.name = 'edit_rank';
            rankInput.value = newRank;
            form.appendChild(rankInput);

            document.body.appendChild(form); // Append form to body to submit
            form.submit(); // Submit the form
        });
    });
});
</script>
