<?php
// PHP Configuration for Error Reporting:
// Enable error display for development. In a production environment, set 'display_errors' to 0
// and log errors to a file for security and to prevent revealing sensitive information.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Session Management:
// Start the PHP session. This must be the very first thing executed in your script
// to ensure session variables are available and properly managed.
session_start();

// Database Connection:
include 'db.php'; 

// Error Message Initialization:
// Initialize a variable to hold any error messages that need to be displayed to the user.
$error = null; 

// --- Handle Messages from Redirects ---
// This block checks for a 'msg' query parameter in the URL.
// This is used to display status or error messages redirected from other pages
// (e.g., after logout, session invalidation, or other system events).
if (isset($_GET['msg'])) {
    $message = ''; // Initialize message string
    // Use a switch statement to handle different message codes gracefully.
    switch ($_GET['msg']) {
        case 'logged_out_elsewhere':
            $message = "You have been logged out because your account was accessed from another device.";
            break;
        case 'invalid_session_db':
            $message = "Your session is no longer valid. Please log in again.";
            break;
        case 'db_error':
            $message = "An internal database error occurred. Please try again later.";
            break;
        case 'not_logged_in':
            $message = "Please log in to access this page.";
            break;
        case 'logged_out':
            $message = "You have been successfully logged out.";
            break;
        // Add more cases for other messages if needed, adhering to a consistent messaging strategy.
    }
    // If a message was set by the switch, assign it to the $error variable for display.
    if ($message) {
        $error = $message; 
    }
}

// Process Login Form Submission:
// This block executes when the login form is submitted via POST request.
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Input Sanitization:
    // Get username and password from the POST request.
    // trim() is used to remove leading/trailing whitespace from the username.
    $username = trim($_POST['username']); 
    $password = $_POST['password'];

    // Variables to store authentication results.
    $user_id_found = null;  // Stores the ID of the authenticated user/admin.
    $role_found = null;     // Stores the role ('admin', 'seller', 'buyer') of the authenticated user.
    $table_name = null;     // Tracks which database table the user was found in ('admins' or 'users').
    $user_data = null;      // Stores the complete row of fetched user/admin data.

    try {
        // --- User Authentication Logic ---
        // Attempt to authenticate as an admin first.
        // Using prepared statements prevents SQL injection vulnerabilities.
        $adminCheck = $conn->prepare("SELECT id, username, password, rank FROM admins WHERE username = ?");
        $adminCheck->bind_param("s", $username); // Bind username as a string parameter.
        $adminCheck->execute();                     // Execute the prepared query.
        $adminResult = $adminCheck->get_result();   // Get the result set from the executed query.

        // Check if an admin with the provided username was found.
        if ($adminResult->num_rows === 1) {
            $admin = $adminResult->fetch_assoc(); // Fetch the admin's data as an associative array.
            // Password Verification:
            // Use password_verify() to securely check the provided password against the hashed password
            // stored in the database. This is essential for security.
            if (password_verify($password, $admin['password'])) {
                // If password matches, set the found user's details.
                $user_id_found = $admin['id'];
                $role_found = 'admin';
                $table_name = 'admins';
                $user_data = $admin; // Store all admin data for later use (e.g., session assignment).
            } else {
                // Generic error message for incorrect credentials.
                // This helps prevent username enumeration attacks (attacker cannot tell if username exists).
                $error = "Incorrect username or password.";
            }
        }
        $adminCheck->close(); // Close the prepared statement to free up resources.

        // Proceed to check the 'users' table ONLY if the user was not found as an admin
        // and no authentication error has occurred yet.
        if ($user_id_found === null && $error === null) {
            // --- Check for regular user login (buyers and sellers are in the 'users' table) ---
            $userCheck = $conn->prepare("SELECT id, username, password, role, email FROM users WHERE username = ?");
            $userCheck->bind_param("s", $username); // Bind username parameter.
            $userCheck->execute();                      // Execute the query.
            $userResult = $userCheck->get_result();    // Get the result set.

            // Check if a user with the provided username was found.
            if ($userResult->num_rows === 1) {
                $user = $userResult->fetch_assoc(); // Fetch the user's data.
                // Password Verification for regular users.
                if (password_verify($password, $user['password'])) {
                    // If password matches, set the found user's details.
                    $user_id_found = $user['id'];
                    $role_found = $user['role']; // Role can be 'buyer' or 'seller'.
                    $table_name = 'users';
                    $user_data = $user; // Store all user data.
                } else {
                    // Generic error message for incorrect credentials.
                    $error = "Incorrect username or password.";
                }
            } else {
                // Generic error message if username not found in either 'admins' or 'users' table.
                $error = "Incorrect username or password.";
            }
            $userCheck->close(); // Close the prepared statement.
        }

    } catch (mysqli_sql_exception $e) {
        // Database Error Handling:
        // Log the detailed database error message (e.g., to a server error log file).
        // Display a generic error message to the user for security.
        error_log("Authentication database query failed: " . $e->getMessage());
        $error = "An internal error occurred during authentication. Please try again.";
    }


    // --- Post-Authentication Processing (if credentials are valid) ---
    // This block executes ONLY if a user/admin was successfully found and their password was correct,
    // and no prior error message was set.
    if ($user_id_found !== null && $error === null) {

        try {
            // --- Crucial Security: Regenerate Session ID ---
            // session_regenerate_id(true) generates a new session ID and deletes the old one.
            // This is vital to prevent session fixation attacks, where an attacker might
            // force a user to use a known session ID, then hijack that session.
            session_regenerate_id(true);

            // --- "Last Login Wins" Session Invalidation Policy ---
            // This query sets any *previous* active sessions for the currently logging-in user to inactive.
            // This ensures that a user can only have one active login session at a time,
            // effectively "kicking out" anyone logged in elsewhere with the same account.
            $invalidate_old_sessions = $conn->prepare("UPDATE login_logs SET is_active = 0, logout_time = NOW() WHERE user_id = ? AND role = ? AND is_active = 1");
            $invalidate_old_sessions->bind_param("is", $user_id_found, $role_found);
            $invalidate_old_sessions->execute();
            // Log how many old sessions were invalidated for debugging/auditing purposes.
            error_log("Invalidated " . $invalidate_old_sessions->affected_rows . " old sessions for UserID: " . $user_id_found . " Role: " . $role_found);
            $invalidate_old_sessions->close();


            // --- Session Creation and Data Storage ---
            // Store essential user information in session variables.
            // This data will be accessible across subsequent pages for the authenticated session.
            $_SESSION['user_id'] = $user_id_found;
            $_SESSION['username'] = $username;
            $_SESSION['role'] = $role_found;

            // Update the 'last_login' timestamp in the appropriate user/admin table.
            $update_last_login_stmt = null;
            if ($table_name === 'admins') {
                $_SESSION['rank'] = $user_data['rank']; // Store admin-specific data.
                $update_last_login_stmt = $conn->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?");
            } else { // 'users' table (for sellers and buyers)
                $_SESSION['email'] = $user_data['email']; // Store user-specific data.
                $update_last_login_stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            }

            // Execute the last_login update if the statement was successfully prepared.
            if ($update_last_login_stmt) { 
                $update_last_login_stmt->bind_param("i", $user_id_found);
                $update_last_login_stmt->execute();
                $update_last_login_stmt->close();
            }

            // --- Log New Login Event ---
            // Get current session details for logging.
            $session_id = session_id(); // Get the NEWLY REGENERATED PHP session ID.
            $ip_address = $_SERVER['REMOTE_ADDR']; // Client's IP address.
            $user_agent = $_SERVER['HTTP_USER_AGENT']; // Client's browser/OS information.

            // Insert a new record into the `login_logs` table for the current active session.
            // This provides an audit trail of user logins.
            $login_stmt = $conn->prepare("INSERT INTO login_logs (user_id, login_time, role, ip_address, session_id, is_active, last_activity, user_agent) VALUES (?, NOW(), ?, ?, ?, 1, NOW(), ?)");
            $login_stmt->bind_param("issss", $user_id_found, $role_found, $ip_address, $session_id, $user_agent);
            $login_stmt->execute();
            // Log the insertion result for auditing.
            error_log("New session inserted for UserID: " . $user_id_found . " Role: " . $role_found . " Session ID: " . $session_id . " - Rows Affected: " . $login_stmt->affected_rows);

            // Verify that the login log entry was successfully inserted.
            if ($login_stmt->affected_rows === 1) {
                $login_stmt->close(); // Close the statement.

                // --- Redirect User Based on Role ---
                // Redirect the user to their respective dashboard based on their role.
                // `exit()` is crucial after a header redirect to stop script execution.
                if ($role_found === 'seller') {
                    $_SESSION['seller_id'] = $user_id_found; // Store seller-specific ID if needed.
                    header("Location: SellersDashBoard.php");
                    exit();
                } elseif ($role_found === 'buyer') {
                    $_SESSION['buyer_id'] = $user_id_found; // Store buyer-specific ID if needed.
                    header("Location: BuyersDashBoard.php");
                    exit();
                } elseif ($role_found === 'admin') {
                    header("Location: adminDashboard.php");
                    exit();
                } else {
                    // Fallback redirect for roles not explicitly handled.
                    header("Location: Dashboard.php");
                    exit();
                }
            } else {
                // Handle cases where the login_log insertion failed unexpectedly.
                // This could indicate a database issue that didn't throw an exception.
                error_log("Failed to insert new login_log entry for UserID: " . $user_id_found . " Role: " . $role_found . " - Affected rows: " . $login_stmt->affected_rows);
                $error = "Login failed due to an internal session issue. Please try again.";
                $login_stmt->close();
            }

        } catch (mysqli_sql_exception $e) {
            // Critical Error during Session Setup/Logging:
            // Log the detailed error message for critical issues during session management.
            // Display a generic error to the user and destroy the session to prevent a half-logged-in state.
            error_log("Critical login session setup error: " . $e->getMessage() . " - UserID: " . ($user_id_found ?? 'N/A'));
            $error = "An unexpected error occurred during login. Please contact support.";
            session_unset();    // Unset all session variables.
            session_destroy();  // Destroy the session.
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Login - QuickBuy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        /* CSS Custom Properties (Variables) for consistent theming */
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
        }

        /* Basic HTML and Body Reset */
        html, body {
            margin: 0;
            padding: 0;
            height: 100%;
            box-sizing: border-box;
        }

        /* Body Styling for Background and Layout */
        body {
            /* Galactic Market Background - A linear gradient for a dynamic, space-like feel */
            background: linear-gradient(135deg,
                var(--deep-space-blue),
                var(--midnight-green-3),
                var(--prussian-blue),
                var(--oxford-blue),
                var(--true-blue)
            );
            background-size: 300% 300%; /* Larger background for smooth animation */
            animation: bgShift 25s ease infinite; /* Animation for background color shift */
            font-family: 'Poppins', sans-serif; /* Apply Poppins font */
            color: var(--ghost-white); /* Default text color */
            display: flex; /* Use flexbox for centering content */
            justify-content: center; /* Center horizontally */
            align-items: center; /* Center vertically */
            flex-direction: column; /* Stack items vertically */
            padding: 20px; /* Padding around the content */
            min-height: 100vh; /* Ensure body takes full viewport height */
            overflow-x: hidden; /* Prevent horizontal scroll on smaller screens */
        }

        /* Keyframe animation for background shift */
        @keyframes bgShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Login Container Styling */
        .login-container {
            background-color: rgba(27, 42, 75, 0.4); /* Semi-transparent background for a "glassmorphism" effect */
            border: 1px solid rgba(255, 255, 255, 0.05); /* Subtle border for definition */
            backdrop-filter: blur(12px); /* Apply blur to elements behind the container */
            padding: 40px; /* Generous padding */
            border-radius: 20px; /* Rounded corners */
            box-shadow: 0 0 40px rgba(0, 0, 0, 0.5); /* Deep shadow for depth */
            width: 90%; /* Responsive width */
            max-width: 450px; /* Maximum width for larger screens */
            text-align: center; /* Center text within the container */
            box-sizing: border-box; /* Include padding and border in the element's total width and height */
            color: var(--ghost-white); /* Ensure text color is consistent within the container */
        }

        /* Heading within Login Container */
        .login-container h2 {
            font-size: 2.5em; /* Large, prominent heading */
            color: var(--white-pop); /* Bright white color for emphasis */
            margin-bottom: 30px; /* Space below the heading */
        }

        /* Form Group Styling */
        .form-group {
            position: relative; /* Needed for positioning the toggle password icon */
            margin-bottom: 25px; /* Space between form fields */
        }

        /* Input Field Styling (Username & Password) */
        input[type="text"], input[type="password"] {
            width: 100%; /* Full width inputs */
            padding: 15px 50px 15px 20px; /* Padding, with extra space on right for toggle icon */
            border: 1px solid rgba(255, 255, 255, 0.2); /* Light, semi-transparent border */
            background-color: rgba(255, 255, 255, 0.08); /* Light, semi-transparent background */
            border-radius: 10px; /* Softly rounded corners */
            font-size: 1.1em; /* Slightly larger font size */
            color: var(--ghost-white); /* Text color inside inputs */
            box-sizing: border-box; /* Include padding and border in the element's total width and height */
            transition: border-color 0.3s ease, background-color 0.3s ease; /* Smooth transitions for focus states */
        }

        /* Input Field Focus State */
        input[type="text"]:focus, input[type="password"]:focus {
            outline: none; /* Remove default outline */
            border-color: var(--true-blue); /* Highlight border on focus */
            background-color: rgba(255, 255, 255, 0.15); /* Slightly more opaque background on focus */
        }

        /* Placeholder Text Styling */
        input::placeholder {
            color: var(--cool-gray); /* Color for placeholder text */
            opacity: 0.8; /* Slightly transparent placeholder */
        }

        /* Toggle Password Icon Styling */
        .toggle-password {
            position: absolute; /* Position relative to the parent .form-group */
            right: 18px; /* Horizontal position */
            top: 50%; /* Vertical position to center */
            transform: translateY(-50%); /* Adjust vertically to true center */
            cursor: pointer; /* Indicate interactivity */
            font-size: 1.2rem; /* Larger icon size */
            color: var(--cool-gray); /* Icon color */
            transition: color 0.2s ease; /* Smooth color transition on hover */
        }

        /* Toggle Password Icon Hover State */
        .toggle-password:hover {
            color: var(--white-pop); /* Change icon color on hover */
        }

        /* Custom Button Styling */
        .btn-custom {
            background-color: var(--midnight-green); /* Greenish button background */
            color: var(--ghost-white); /* Button text color */
            padding: 16px 40px; /* Generous padding */
            border: none; /* No border */
            border-radius: 12px; /* Rounded corners */
            font-size: 1.1em; /* Larger font size */
            font-weight: bold; /* Bold text */
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.3); /* Shadow for depth */
            cursor: pointer; /* Indicate interactivity */
            text-decoration: none; /* Remove underline for anchor-like buttons */
            transition: background-color 0.3s ease, transform 0.2s; /* Smooth transitions for hover effects */
            display: inline-block; /* Allow padding and sizing */
            width: 100%; /* Full width button */
            margin-top: 15px; /* Space above the button */
        }

        /* Custom Button Hover State */
        .btn-custom:hover {
            background-color: var(--caribbean-current); /* Change background on hover */
            transform: scale(1.02); /* Slight scale up effect on hover */
        }

        /* Alert/Error Message Styling */
        .alert-danger {
            color: var(--white-pop); /* White text for error */
            background-color: rgba(255, 0, 0, 0.2); /* Semi-transparent red background */
            border: 1px solid rgba(255, 0, 0, 0.4); /* Red border */
            padding: 12px; /* Padding around the message */
            border-radius: 10px; /* Rounded corners */
            margin-bottom: 25px; /* Space below the alert */
            font-size: 1em; /* Standard font size */
            text-align: center; /* Center the error message text */
            backdrop-filter: blur(5px); /* Blur effect for the alert background */
        }

        /* Utility Class for Centering Text */
        .text-center {
            text-align: center;
        }

        /* Margin Top Utility Class and Registration Link Container */
        .mt-3 {
            margin-top: 1.5rem; /* Increased top margin */
            color: var(--cool-gray); /* Text color for "Don't have an account?" */
            font-size: 1.05em; /* Slightly larger font for readability */
            display: flex; /* Use flexbox for horizontal alignment */
            justify-content: center; /* Center content horizontally */
            align-items: center; /* Center content vertically */
            gap: 8px; /* Space between text and link */
            padding: 15px 20px; /* Padding for the container itself */
            background-color: rgba(0, 0, 0, 0.2); /* Subtle dark background */
            border-radius: 10px; /* Rounded corners */
            border: 1px solid rgba(255, 255, 255, 0.08); /* Thin, subtle border */
            backdrop-filter: blur(5px); /* Subtle blur for this container */
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2); /* Gentle shadow */
            max-width: fit-content; /* Limit width to content size */
            margin-left: auto; /* Auto margins to center the element */
            margin-right: auto;
        }

        /* Registration Link Styling */
        .mt-3 a {
            color: var(--white-pop); /* Pure white link color for high contrast */
            text-decoration: none; /* No underline by default */
            font-weight: bold; /* Bold text */
            transition: color 0.2s ease, text-shadow 0.2s ease; /* Smooth transitions for hover effects */
            text-shadow: 0 0 5px rgba(255, 255, 255, 0); /* Initial state: no text shadow */
        }

        /* Registration Link Hover State */
        .mt-3 a:hover {
            color: var(--caribbean-current); /* Vibrant green on hover */
            text-decoration: none; /* Ensure no underline on hover */
            text-shadow: 0 0 10px var(--caribbean-current), 0 0 20px var(--caribbean-current); /* Glow effect on hover */
        }

        /* Exit Button Specific Styling (inherits from .btn-custom) */
        .btn-exit {
            background-color: var(--oxford-blue-3); /* A darker, neutral blue for the exit button */
            margin-top: 20px; /* Space above the exit button */
        }

        /* Exit Button Hover State */
        .btn-exit:hover {
            background-color: var(--oxford-blue); /* Slightly lighter blue on hover */
            transform: scale(1.02); /* Maintain hover effect */
        }

        /* --- Responsive Adjustments (Media Queries) --- */

        /* Styles for screens up to 991px wide (e.g., tablets in portrait) */
        @media (max-width: 991px) {
            .login-container {
                padding: 35px;
                border-radius: 18px;
                max-width: 400px;
            }
            .login-container h2 {
                font-size: 2.2em;
            }
            input[type="text"], input[type="password"] {
                padding: 14px 45px 14px 18px;
                font-size: 1em;
            }
            .toggle-password {
                right: 15px;
                font-size: 1.1rem;
            }
            .btn-custom {
                padding: 14px 30px;
                font-size: 1em;
            }
            .alert-danger {
                padding: 10px;
                font-size: 0.9em;
            }
            .mt-3 {
                font-size: 1em; /* Adjust font size for smaller screens */
                padding: 15px 20px;
            }
            .btn-exit { 
                padding: 14px 30px;
                font-size: 1em;
            }
        }

        /* Styles for screens up to 575px wide (e.g., small tablets and large phones) */
        @media (max-width: 575px) {
            body {
                padding: 15px;
                align-items: center; /* Re-center items if they were stretching */
            }
            .login-container {
                padding: 30px;
                border-radius: 15px;
                max-width: 95%; /* Allow it to take more width on small screens */
            }
            .login-container h2 {
                font-size: 2em;
                margin-bottom: 25px;
            }
            input[type="text"], input[type="password"] {
                padding: 12px 40px 12px 15px;
                font-size: 0.95em;
            }
            .toggle-password {
                right: 12px;
                font-size: 1rem;
            }
            .btn-custom {
                padding: 12px 25px;
                font-size: 0.95em;
                margin-top: 10px;
            }
            .alert-danger {
                padding: 8px;
                font-size: 0.85em;
                margin-bottom: 20px;
            }
            .mt-3 {
                font-size: 0.95em; /* Adjust font size for smaller screens */
                padding: 12px 15px;
                flex-direction: column; /* Stack text and link on very small screens */
                gap: 5px;
            }
            .btn-exit { 
                padding: 12px 25px;
                font-size: 0.95em;
                margin-top: 10px;
            }
        }

        /* Styles for screens up to 400px wide (e.g., smaller phones) */
        @media (max-width: 400px) {
            .login-container {
                padding: 25px;
                border-radius: 12px;
            }
            .login-container h2 {
                font-size: 1.8em;
                margin-bottom: 20px;
            }
            input[type="text"], input[type="password"] {
                padding: 10px 35px 10px 12px;
                font-size: 0.9em;
            }
            .toggle-password {
                right: 10px;
                font-size: 0.9rem;
            }
            .btn-custom {
                padding: 10px 20px;
                font-size: 0.9em;
            }
            .alert-danger {
                font-size: 0.8em;
                padding: 6px;
            }
            .mt-3 {
                font-size: 0.85em;
                padding: 10px 12px;
            }
            .btn-exit {
                padding: 10px 20px;
                font-size: 0.9em;
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

    <div class="login-container">
        <h2>QuickBuy Login</h2>
        <form method="POST" action="">
            <?php 
            // Display error messages if $error variable is not null.
            // htmlspecialchars() is used to prevent XSS attacks when displaying user-controlled input.
            if ($error): 
            ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <div class="form-group">
                <input type="text" name="username" placeholder="Username" required>
            </div>
            
            <div class="form-group">
                <input type="password" name="password" id="password" placeholder="Password" required>
                <span class="toggle-password" onclick="togglePassword('password')">👁️</span>
            </div>
            
            <button type="submit" class="btn-custom">Login</button>
            
            <p class="text-center mt-3">
                Don't have an account? <a href="RegisterPage.php">Register</a>
            </p>

            <button type="button" class="btn-custom btn-exit" onclick="exitWebsite()">Exit</button>

        </form>
    </div>

    <script>
        // Function to toggle password input type between 'password' and 'text'.
        function togglePassword(id) {
            const input = document.getElementById(id);
            input.type = input.type === "password" ? "text" : "password";
        }

        // Function to redirect the user to the main index page.
        function exitWebsite() {
            window.location.href = 'index.html'; // Redirect to your homepage
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
