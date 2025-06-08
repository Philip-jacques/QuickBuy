<?php
/**
 * Buyer Profile Page
 *
 * This page allows a logged-in buyer to view and update their profile information
 * such as email, phone, and address.
 *
 * It ensures that only authenticated buyers can access this page and redirects
 * unauthorized users to the login page.
 */

// Start a new session or resume the existing one.
session_start();

// Include the database connection file.
include('db.php');

// Initialize user ID from session, defaulting to null if not set.
$user_id = $_SESSION['user_id'] ?? null;

// Initialize messages for displaying success or error notifications to the user.
$successMessage = "";
$errorMessage = "";

// --- Authentication and Authorization Check ---
// Redirect users who are not logged in or do not have the 'buyer' role.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'buyer') {
    header("Location: LoginPage.php"); // Redirect to login page.
    exit(); // Terminate script execution after redirection.
}

// Proceed only if a valid user ID is found in the session.
if ($user_id) {
    // --- Database Connection Verification ---
    // Ensure that $conn (the mysqli connection object) is properly established from db.php.
    if (!isset($conn) || !$conn instanceof mysqli) {
        // If the connection is not valid, terminate the script with an error message.
        die("Database connection not established correctly via db.php");
    }

    // --- Fetch Buyer Data ---
    // Prepare a SQL statement to retrieve the buyer's existing profile data.
    // Using a prepared statement prevents SQL injection.
    $sql = "SELECT username, email, phone, address FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);

    // Check if the statement preparation was successful.
    if ($stmt === false) {
        // Log or display an error if the statement cannot be prepared.
        die("Failed to prepare statement for fetching buyer data: " . $conn->error);
    }

    // Bind the user ID parameter to the prepared statement.
    // 'i' specifies that the parameter is an integer.
    $stmt->bind_param("i", $user_id);

    // Execute the prepared statement.
    $stmt->execute();

    // Get the result set from the executed statement.
    $result = $stmt->get_result();

    // Fetch the buyer's data as an associative array.
    $buyer = $result->fetch_assoc();

    // Assign fetched data to variables, providing empty strings as defaults
    // to prevent errors if a key is not set (though unlikely for a valid user_id).
    $username = $buyer['username'] ?? '';
    $email = $buyer['email'] ?? '';
    $phone = $buyer['phone'] ?? '';
    $address = $buyer['address'] ?? '';

    // --- Handle Profile Update Submission (POST Request) ---
    // Check if the form has been submitted using the POST method.
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        // Retrieve the new profile data from the POST request.
        // Use the null coalescing operator (??) to default to an empty string if not set.
        $newEmail = $_POST['email'] ?? '';
        $newPhone = $_POST['phone'] ?? '';
        $newAddress = $_POST['address'] ?? '';

        // Prepare an SQL statement to update the user's profile information.
        // Using a prepared statement protects against SQL injection.
        $update = $conn->prepare("UPDATE users SET email = ?, phone = ?, address = ? WHERE id = ?");

        // Check if the update statement preparation was successful.
        if ($update === false) {
            $errorMessage = "Failed to prepare update statement: " . $conn->error;
        } else {
            // Bind the new parameters to the update statement.
            // 'sssi' specifies string, string, string, integer for the parameters.
            $update->bind_param("sssi", $newEmail, $newPhone, $newAddress, $user_id);

            // Execute the update statement.
            if ($update->execute()) {
                $successMessage = "Profile updated successfully!";
                // Update session variables immediately to reflect the changes across the site
                // without requiring a full page reload or re-login.
                $_SESSION['email'] = $newEmail;
                $_SESSION['phone'] = $newPhone;
                $_SESSION['address'] = $newAddress;

                // Also update local variables to reflect changes in the current view.
                $email = $newEmail;
                $phone = $newPhone;
                $address = $newAddress;
            } else {
                // Set an error message if the update failed.
                $errorMessage = "Error updating profile: " . $update->error;
                // Log the error for debugging purposes.
                error_log("Buyer Profile update failed for user_id: $user_id - " . $update->error);
            }
            // Close the update statement to free up resources.
            $update->close();
        }
    }
    // Close the statement used for fetching buyer data.
    $stmt->close();
} else {
    // This block should ideally not be reached due to the redirect check at the top.
    // It serves as a fallback for unexpected scenarios.
    $errorMessage = "User not logged in or invalid session.";
}

// Close the database connection. It's good practice to close it when all
// database operations for the page are complete.
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buyer Profile - QuickBuy</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* --- CSS Variable Definitions --- */
        /* Defines a set of custom properties (CSS variables) for consistent theming. */
        :root {
            /* Main blues for background and primary elements */
            --true-blue: #0466c8ff;
            --sapphire: #0353a4ff;
            --yale-blue: #023e7dff;
            --oxford-blue: #002855ff;
            --oxford-blue-2: #001845ff;
            --oxford-blue-3: #001233ff;

            /* Greens & Deeper Blues for interactive elements like buttons */
            --caribbean-current: #006466ff; /* Darker green, e.g., for hover states */
            --midnight-green: #065a60ff;     /* Lighter green, e.g., for primary buttons */
            --midnight-green-2: #0b525bff;
            --midnight-green-3: #144552ff;
            --prussian-blue: #212f45ff;
            --deep-space-blue: #0d1b2a;

            /* Neutral colors for text, borders, and general elements */
            --gunmetal: #30343fff;
            --ghost-white: #fafaffff; /* Pure white, often for main text */
            --delft-blue: #273469ff;
            --space-cadet: #1e2749ff;
            --paynes-gray: #5c677dff;
            --slate-gray: #7d8597ff;
            --cool-gray: #979dacff;
            --charcoal: #1b3a4bff;

            /* Accent Colors for prominent elements */
            --white-pop: #FFFFFF; /* Bright white, e.g., for main titles */

            /* Alert Colors for success and error messages */
            --alert-success-text: #d4edda; /* Light green for success text */
            --alert-success-bg: rgba(6, 90, 96, 0.5); /* Semi-transparent background for success */
            --alert-success-border: #006466; /* Border color for success */

            --alert-error-text: #f8d7da; /* Light red for error text */
            --alert-error-bg: rgba(220, 53, 69, 0.5); /* Semi-transparent background for error */
            --alert-error-border: #dc3545; /* Border color for error */
        }

        /* --- Universal Box-Sizing --- */
        /* Ensures consistent box model behavior across all elements,
           making layout calculations more predictable. */
        html {
            box-sizing: border-box;
        }
        *, *::before, *::after {
            box-sizing: inherit;
        }

        /* --- Base HTML and Body Styling --- */
        html, body {
            margin: 0; /* Remove default margin */
            padding: 0; /* Remove default padding */
            min-height: 100vh; /* Ensure body takes at least the full viewport height */
            overflow-x: hidden; /* Prevent horizontal scrolling */
        }

        body {
            /* Galactic Market Animated Background */
            /* Creates a gradient background with multiple colors for a dynamic look. */
            background: linear-gradient(135deg,
                var(--deep-space-blue),
                var(--midnight-green-3),
                var(--prussian-blue),
                var(--oxford-blue),
                var(--true-blue)
            );
            background-size: 300% 300%; /* Larger background size to enable smooth animation */
            animation: bgShift 25s ease infinite; /* Applies the background animation */
            font-family: 'Poppins', sans-serif; /* Sets a modern, clean font */
            color: var(--ghost-white); /* Default text color for the entire body */
            display: flex; /* Enables flexbox for centering content */
            justify-content: center; /* Horizontally centers the content */
            align-items: center; /* Vertically centers the content */
            padding: 20px; /* Adds padding around the central content */
        }

        /* --- Background Animation Keyframes --- */
        /* Defines the animation for the background gradient to shift colors. */
        @keyframes bgShift {
            0% { background-position: 0% 50%; } /* Starts at 0% horizontal, 50% vertical */
            50% { background-position: 100% 50%; } /* Shifts to 100% horizontal, 50% vertical */
            100% { background-position: 0% 50%; } /* Returns to start, creating a loop */
        }

        /* --- Profile Card Styling --- */
        .profile-card {
            background-color: rgba(27, 42, 75, 0.4); /* Translucent dark background for a "frosted" look */
            border: 1px solid rgba(255, 255, 255, 0.08); /* Subtle white border */
            backdrop-filter: blur(10px); /* Applies the frosted glass effect */
            padding: 40px; /* Generous internal spacing */
            border-radius: 20px; /* Highly rounded corners */
            box-shadow: 0 0 30px rgba(0, 0, 0, 0.4); /* Large, soft shadow for depth */
            max-width: 600px; /* Limits the maximum width for readability */
            width: 100%; /* Ensures it takes full width on smaller screens */
            color: var(--ghost-white); /* Ensures text inside is light */
            box-sizing: border-box; /* Includes padding in width calculation */
            max-height: 95vh; /* Limits card height to viewport height */
            overflow-y: auto; /* Adds vertical scrollbar if content overflows */
        }

        /* --- Heading (H2) Styling --- */
        h2 {
            font-size: 2.5rem; /* Large and prominent */
            color: var(--white-pop); /* Pure white for high contrast */
            text-align: center; /* Centers the text */
            margin-bottom: 25px; /* Space below the heading */
            font-weight: 600; /* Semi-bold */
            letter-spacing: 1px; /* Slight letter spacing for visual appeal */
            text-shadow: 0 0 15px rgba(255, 255, 255, 0.2); /* Subtle glow effect */
            word-break: break-word; /* Allows long words to break and wrap */
            white-space: normal; /* Ensures text wraps naturally */
        }

        /* --- Paragraph for Sub-heading/Instructions --- */
        p.text-center {
            font-size: 1.1em;
            color: var(--ghost-white);
            text-align: center;
            margin-bottom: 30px; /* More space below the sub-heading */
            line-height: 1.5; /* Improves readability of the text */
        }

        /* --- Form Label Styling --- */
        .form-label {
            font-size: 1em;
            color: var(--ghost-white); /* Light color for labels */
            margin-bottom: 8px; /* Space below the label */
            display: block; /* Ensures label takes full width and moves next element to new line */
            font-weight: 500;
        }

        /* --- Form Control (Input) Styling --- */
        .form-control {
            width: 100%; /* Full width within its container */
            padding: 12px 15px; /* Internal padding */
            border: 1px solid rgba(255, 255, 255, 0.2); /* Light, transparent border */
            background-color: rgba(255, 255, 255, 0.1); /* Slightly translucent background */
            border-radius: 10px; /* Rounded input fields */
            font-size: 1em;
            color: var(--ghost-white); /* Text color inside the input */
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.2); /* Subtle inner shadow */
            transition: border-color 0.3s ease, background-color 0.3s ease; /* Smooth transitions for hover/focus */
        }

        /* --- Placeholder Text Styling --- */
        .form-control::placeholder {
            color: var(--cool-gray); /* Color for placeholder text */
            opacity: 0.8; /* Slightly transparent */
        }

        /* --- Form Control Focus State --- */
        .form-control:focus {
            background-color: rgba(255, 255, 255, 0.15); /* Slightly less translucent on focus */
            border-color: var(--caribbean-current); /* Highlight border color on focus */
            outline: none; /* Removes the default browser outline */
            box-shadow: 0 0 0 0.25rem rgba(0, 100, 102, 0.25); /* Glow effect on focus */
            color: var(--ghost-white); /* Ensure text color remains consistent */
        }

        /* --- Disabled/Read-only Form Control Styling --- */
        .form-control:disabled, .form-control[readonly] {
            background-color: rgba(255, 255, 255, 0.05); /* Very light translucent background */
            color: var(--cool-gray); /* Grayed out text color */
            cursor: not-allowed; /* Indicates the field is not editable */
            opacity: 0.7; /* Reduces opacity for disabled state */
            border-color: rgba(255, 255, 255, 0.1);
        }

        /* --- Button Group Styling --- */
        .button-group {
            display: flex; /* Arranges buttons in a row */
            justify-content: flex-end; /* Aligns buttons to the right */
            gap: 15px; /* Space between individual buttons */
            margin-top: 30px; /* Space above the button group */
            flex-wrap: wrap; /* Allows buttons to wrap to the next line on smaller screens */
        }

        /* --- General Button Styling --- */
        .btn {
            padding: 12px 25px; /* Internal padding for buttons */
            border: none; /* No border */
            border-radius: 10px; /* Rounded corners for buttons */
            font-size: 1em;
            font-weight: 600; /* Semi-bold text */
            cursor: pointer; /* Changes cursor to a pointer on hover */
            transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease; /* Smooth transitions */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); /* Initial shadow for depth */
            text-decoration: none; /* Removes underline for anchor tags acting as buttons */
            display: flex; /* Enables flexbox for content alignment within buttons */
            align-items: center; /* Vertically centers text/content */
            justify-content: center; /* Horizontally centers text/content */
        }

        /* --- Save Button Specific Styling --- */
        .btn-save {
            background-color: var(--midnight-green); /* Green color */
            color: var(--white-pop); /* White text */
        }

        .btn-save:hover {
            background-color: var(--caribbean-current); /* Darker green on hover */
            transform: translateY(-2px); /* Lifts the button slightly */
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.3); /* Enhanced shadow on hover */
        }

        /* --- Back Button Specific Styling --- */
        .btn-back {
            background-color: var(--cool-gray); /* Gray color */
            color: var(--white-pop); /* White text */
        }

        .btn-back:hover {
            background-color: var(--cool-gray); /* Retains gray on hover */
            transform: translateY(-2px); /* Lifts the button slightly */
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.3); /* Enhanced shadow on hover */
        }

        /* --- Alert Messages Styling --- */
        .alert-success-custom {
            padding: 12px 20px;
            margin-bottom: 20px;
            border: 1px solid var(--alert-success-border);
            border-radius: 10px;
            background-color: var(--alert-success-bg);
            color: var(--alert-success-text);
            font-size: 1rem;
            text-align: center;
            font-weight: 500;
        }

        .alert-error-custom {
            padding: 12px 20px;
            margin-bottom: 20px;
            border: 1px solid var(--alert-error-border);
            border-radius: 10px;
            background-color: var(--alert-error-bg);
            color: var(--alert-error-text);
            font-size: 1rem;
            text-align: center;
            font-weight: 500;
        }

        /* --- Responsive Adjustments --- */

        /* Media query for Tablets (portrait) and larger phones */
        @media (min-width: 481px) and (max-width: 767px) {
            body {
                padding: 15px; /* Reduced body padding */
            }
            .profile-card {
                padding: 25px; /* Reduced card padding */
                border-radius: 18px; /* Slightly less rounded corners */
                max-width: 90%; /* Wider on tablet screens */
            }
            h2 {
                font-size: 2em; /* Smaller heading */
                margin-bottom: 20px;
            }
            p.text-center {
                font-size: 1em;
                margin-bottom: 25px;
            }
            .form-label, .form-control, .btn {
                font-size: 0.95em; /* Slightly smaller font sizes */
            }
            .btn {
                padding: 10px 20px; /* Reduced button padding */
            }
            .button-group {
                gap: 10px; /* Reduced gap between buttons */
            }
        }

        /* Media query for Smartphones (most common sizes) - Portrait Only */
        @media (min-width: 376px) and (max-width: 480px) and (orientation: portrait) {
            body {
                padding: 10px;
                align-items: flex-start; /* Align to top to give more vertical room */
            }
            .profile-card {
                padding: 18px;
                border-radius: 15px;
                max-width: 98%; /* More width utilization */
                margin-top: 10px; /* Small margin from the top */
                max-height: 95vh;
                overflow-y: auto;
            }
            h2 {
                font-size: 1.5em;
                margin-bottom: 15px;
            }
            p.text-center {
                font-size: 0.9em;
                margin-bottom: 20px;
            }
            .form-label {
                font-size: 0.9em;
            }
            .form-control {
                font-size: 0.85em;
                padding: 8px 12px;
            }
            .btn {
                font-size: 0.85em;
                padding: 8px 15px;
            }
            .button-group {
                flex-direction: column; /* Stack buttons vertically */
                align-items: stretch; /* Make buttons full width */
                gap: 8px; /* Gap between stacked buttons */
            }
        }

        /* Media query for Smaller Smartphones (e.g., iPhone SE/5) - Portrait Only */
        @media (min-width: 321px) and (max-width: 375px) and (orientation: portrait) {
            body {
                padding: 5px;
                align-items: flex-start;
            }
            .profile-card {
                padding: 12px;
                border-radius: 12px;
                max-width: 98%;
                margin-top: 5px;
                max-height: 95vh;
                overflow-y: auto;
            }
            h2 {
                font-size: 1.2em;
                margin-bottom: 10px;
            }
            p.text-center {
                font-size: 0.8em;
                margin-bottom: 15px;
            }
            .form-label {
                font-size: 0.85em;
            }
            .form-control {
                font-size: 0.8em;
                padding: 6px 10px;
            }
            .btn {
                font-size: 0.8em;
                padding: 7px 12px;
            }
            .button-group {
                flex-direction: column;
                align-items: stretch;
                gap: 5px;
            }
        }

        /* Media query for Smartphones in Landscape Mode (critical for vertical space) */
        @media (min-width: 481px) and (max-width: 820px) and (orientation: landscape) {
            body {
                padding: 5px; /* Minimal body padding */
                align-items: flex-start; /* Align content to the very top */
            }
            .profile-card {
                padding: 10px; /* Reduced card padding */
                border-radius: 10px;
                max-width: 98%;
                margin-top: 5px; /* Small margin from top */
                max-height: 95vh;
                overflow-y: auto;
            }
            h2 {
                font-size: 1.1em; /* Aggressively smaller header */
                margin-bottom: 8px; /* Reduced margin */
                line-height: 1.1; /* Tighter line height */
            }
            p.text-center {
                font-size: 0.75em; /* Smaller intro text */
                margin-bottom: 10px;
            }
            .form-label {
                font-size: 0.8em;
            }
            .form-control {
                font-size: 0.75em; /* Smaller input text */
                padding: 5px 8px;
            }
            .btn {
                font-size: 0.75em; /* Smaller button text */
                padding: 6px 10px;
            }
            .button-group {
                flex-direction: row; /* Try to keep buttons inline if space allows, but tightly packed */
                justify-content: center; /* Center buttons */
                gap: 5px; /* Very small gap between buttons */
                flex-wrap: wrap; /* Allow buttons to wrap if they can't fit in one row */
            }
        }

        /* Media query for Smartwatches and very tiny viewports (max-width: 320px) */
        @media (max-width: 320px) {
            body {
                padding: 0px;
                align-items: flex-start;
            }
            .profile-card {
                padding: 5px;
                border-radius: 8px;
                max-width: 100%;
                margin-top: 2px;
                max-height: 98vh;
                overflow-y: auto;
            }
            h2 {
                font-size: 1em;
                margin-bottom: 5px;
            }
            p.text-center {
                font-size: 0.7em;
                margin-bottom: 8px;
            }
            .form-label {
                font-size: 0.75em;
            }
            .form-control {
                font-size: 0.7em;
                padding: 4px 6px;
            }
            .btn {
                font-size: 0.7em;
                padding: 5px 8px;
            }
            .button-group {
                flex-direction: column;
                align-items: stretch;
                gap: 3px;
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

    <div class="profile-card">
        <h2 class="mb-3 text-center">Welcome, <?php echo htmlspecialchars($username); ?>!</h2>
        <p class="text-center">Manage your buyer profile below.</p>

        <?php if (!empty($successMessage)): ?>
            <div class="alert-success-custom" role="alert">
                <?php echo htmlspecialchars($successMessage); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errorMessage)): ?>
            <div class="alert-error-custom" role="alert">
                <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php endif; ?>

        <form method="post">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" id="username" class="form-control" value="<?php echo htmlspecialchars($username); ?>" disabled>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($email); ?>" class="form-control">
            </div>

            <div class="mb-3">
                <label for="phone" class="form-label">Phone</label>
                <input type="text" name="phone" id="phone" value="<?php echo htmlspecialchars($phone); ?>" class="form-control">
            </div>

            <div class="mb-3">
                <label for="address" class="form-label">Address</label>
                <input type="text" name="address" id="address" value="<?php echo htmlspecialchars($address); ?>" class="form-control">
            </div>

            <div class="button-group">
                <button type="submit" class="btn btn-save">Save Changes</button>
                <a href="BuyersDashBoard.php" class="btn btn-back">Back to Dashboard</a>
            </div>
        </form>
    </div>

</body>
</html>
