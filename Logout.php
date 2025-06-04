<?php
// PHP Session Start:
// This line initializes or resumes the session. It MUST be the very first thing in your script
// to ensure session variables can be accessed and manipulated.
session_start();

// Database Connection:

require_once 'db.php';

// Check if a user is logged in:
// This conditional block ensures that we only attempt to log out a user if they actually
// have an active session with 'user_id' and 'role' set.
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    // Retrieve user details from session:
    // Get the user ID and role from the active session. This information is crucial
    // for identifying the specific login log entry to update in the database.
    $user_id = $_SESSION['user_id'];
    $role = $_SESSION['role']; 
    // Get the current PHP session ID. This is used to uniquely identify the session
    // that is being logged out in the `login_logs` table.
    $session_id = session_id();

    // Database Update for Logout:
    // Prepare a SQL statement to update the `login_logs` table.
    // This query marks the specific session as inactive (`is_active = 0`) and
    // records the `logout_time`. It's crucial for implementing a "last login wins"
    // policy or for tracking active sessions accurately.
    // It targets the specific user, role, and session ID that is currently active.
    $stmt = $conn->prepare("UPDATE login_logs SET is_active = 0, logout_time = NOW() WHERE user_id = ? AND role = ? AND session_id = ? AND is_active = 1");
    
    // Check if the statement was successfully prepared to prevent errors.
    if ($stmt) {
        // Bind parameters:
        // 'iss' specifies the types of the parameters: integer (user_id), string (role), string (session_id).
        $stmt->bind_param("iss", $user_id, $role, $session_id);
        
        // Execute the prepared statement.
        $execute_result = $stmt->execute();
        
        // Close the statement to free up resources.
        $stmt->close();

        // Optional: Add logging for successful or failed execution if needed for debugging.
        if (!$execute_result) {
            error_log("Failed to execute logout statement for UserID: " . $user_id . ", SessionID: " . $session_id . " - Error: " . $stmt->error);
        }

    } else {
        // Error Logging:
        // If the statement preparation fails, log the error for debugging purposes.
        // This usually indicates an issue with the SQL query itself or the database connection.
        error_log("Failed to prepare logout statement: " . $conn->error);
    }
}

// Session Destruction:
// This section is responsible for completely ending the PHP session.

// 1. Unset all session variables:
// This clears all data stored in the $_SESSION superglobal array for the current session.
$_SESSION = array();

// 2. Destroy the session cookie:
// This block checks if session cookies are being used. If so, it deletes the session cookie
// from the client's browser by setting its expiration time to a past date.
// This is critical for security to ensure the session ID is no longer valid on the client side.
if (ini_get("session.use_cookies")) {
    // Get current session cookie parameters.
    $params = session_get_cookie_params();
    // Set the session cookie with an expiration time in the past.
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Destroy the session on the server:
// This deletes the session data file on the server, completely invalidating the session.
session_destroy();

// Redirect to Login Page:
// After successfully logging out and destroying the session, redirect the user
// to the login page. This provides a clean transition and prevents access to
// authenticated content.
// `exit()` is crucial after a header redirect to ensure no further code is executed.
header("Location: LoginPage.php");
exit();
?>