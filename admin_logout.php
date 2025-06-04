<?php
// Start a new session or resume the existing one. This is necessary to access session variables.
session_start();

// Check if the 'admin_id' session variable is set. This indicates an admin is currently logged in.
if (isset($_SESSION['admin_id'])) {
    // If an admin is logged in, unset (destroy) specific session variables related to the admin.
    // This removes the admin's ID, username, and role from the current session, effectively logging them out.
    unset($_SESSION['admin_id']);
    unset($_SESSION['username']);
    unset($_SESSION['role']);
    // You might have other admin-specific session variables to unset here if they exist.
}

// Destroy all data registered to the session. This completely clears all session variables for the current session.
// It's a more comprehensive logout step than just unsetting specific variables.
session_destroy();

// Redirect the user to the 'LoginPage.php' after logging out.
// This sends an HTTP header to the browser instructing it to navigate to the specified URL.
header("Location: LoginPage.php");
// Terminate the script execution. It's crucial to call exit() after a header redirect
// to ensure that no further code is executed and that the redirect happens immediately.
exit();
?>