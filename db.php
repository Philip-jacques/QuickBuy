<?php
$servername = "sql102.infinityfree.com";
$username = "if0_39013745";
$password = "fsnMAST1Gm37";
$dbname = "if0_39013745_quickbuy_db";

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
} catch (mysqli_sql_exception $e) {
    // Log the error (do not display to public in production)
    error_log("Database connection failed: " . $e->getMessage());
    // Display a generic error message to the user
    die("<h1>Database connection failed. Please try again later.</h1>");
}

// Set charset to utf8mb4 for proper emoji and special character handling
$conn->set_charset("utf8mb4");
?>
