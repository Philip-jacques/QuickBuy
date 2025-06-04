<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in via session variables (basic check)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || !isset($_SESSION['username'])) {
    // If session variables are not set, user is not considered logged in.
    // Destroy any lingering session data and redirect to login page.
    session_unset();
    session_destroy();
    header("Location: LoginPage.php?msg=not_logged_in");
    exit();
}

// Get current session details from PHP's $_SESSION
$current_user_id = $_SESSION['user_id'];
$current_role = $_SESSION['role'];
$current_session_id = session_id(); // Get the actual PHP session ID for the current request

// --- Database Check for Active Session ---
// This checks if the *current PHP session ID* for *this specific user* is still marked as active in the database.
$stmt = $conn->prepare("SELECT is_active, last_activity FROM login_logs WHERE user_id = ? AND role = ? AND session_id = ?");
if ($stmt) {
    $stmt->bind_param("iss", $current_user_id, $current_role, $current_session_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $log_entry = $result->fetch_assoc();

        // If 'is_active' is 0, it means this session was either kicked out by a new login
        // from another device, or it was marked inactive by the stale session cleanup.
        if ($log_entry['is_active'] == 0) {
            // This session is no longer considered active in the database.
            // Force logout this user on this device.
            session_unset();
            session_destroy();
            // Redirect with a message indicating the reason for logout
            header("Location: LoginPage.php?msg=logged_out_elsewhere");
            exit();
        } else {
            // Session is active in DB. Update its 'last_activity' timestamp to keep it alive.
            // This prevents the general cleanup on LoginPage.php from prematurely marking it inactive.
            $update_activity_stmt = $conn->prepare("UPDATE login_logs SET last_activity = NOW() WHERE user_id = ? AND role = ? AND session_id = ? AND is_active = 1");
            if ($update_activity_stmt) {
                $update_activity_stmt->bind_param("iss", $current_user_id, $current_role, $current_session_id);
                $update_activity_stmt->execute();
                $update_activity_stmt->close();
            } else {
                error_log("Failed to update last_activity in session_validate: " . $conn->error);
            }
        }
    } else {
        // This scenario means the session ID in the browser does not correspond to an active
        // or even existing entry in login_logs. This could happen if the entry was manually deleted
        // or if there's a serious inconsistency. Treat as invalid.
        session_unset();
        session_destroy();
        header("Location: LoginPage.php?msg=invalid_session_db");
        exit();
    }
    $stmt->close();
} else {
    // Database query preparation failed, likely a critical error (e.g., column missing)
    error_log("Failed to prepare session validation statement: " . $conn->error);
    session_unset();
    session_destroy();
    header("Location: LoginPage.php?msg=db_error"); // Inform user about a database error
    exit();
}

// If the script reaches this point, the user is genuinely logged in and their session is valid and active.
// Any role-specific checks or dashboard content can follow this include.

?>