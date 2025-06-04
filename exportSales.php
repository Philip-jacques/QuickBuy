<?php
/**
 * @file exportSales.php
 * @brief This script generates an Excel-compatible (XLS) report containing
 * details of sales transactions from the last 7 days, mirroring the "Recent Sales"
 * table on the platform reports page.
 *
 * The report is directly downloaded by the browser as an Excel file.
 *
 * @uses require 'db.php' To establish a database connection.
 * @uses session_start() To access session variables (e.g., for admin check).
 */

session_start(); // Start the session
require 'db.php'; // Include the database connection file.

 if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
//Redirect or display an error if not an admin
header("Location: LoginPage.php"); // Example redirect
exit("Access denied. You must be an admin to view this report.");
 }

// --- Set HTTP Headers for Excel Download ---
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=Recent_Sales_Summary_Report_" . date("Y-m-d") . ".xls");

// --- SQL Query to Fetch Recent Sales Summary Data (Last 7 Days) ---
// This query is directly from your platformReports.php for the "Recent Sales" table
$sql = "SELECT
    pm.id AS payment_id,
    u.username AS buyer,
    pm.total_amount AS amount,
    pm.payment_date,
    pm.payment_status AS status,
    GROUP_CONCAT(p.itemName SEPARATOR ', ') AS products_purchased
FROM payments pm
JOIN users u ON pm.buyer_id = u.id
JOIN orders o ON pm.order_id = o.id
JOIN order_items oi ON o.id = oi.order_id
JOIN products p ON oi.product_id = p.id
WHERE (pm.payment_status = 'completed' OR pm.payment_status = 'Complete' OR pm.payment_status = 'Successful')
AND pm.payment_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
GROUP BY pm.id
ORDER BY pm.payment_date DESC";

$result = $conn->query($sql);

// Check if query was successful
if (!$result) {
    error_log("Database query error for recent sales summary export: " . $conn->error);
    die("Error generating report. Please try again later.");
}

// --- Generate HTML Table for Excel Output ---
echo "<table border='1'>";
echo "<thead>
        <tr>
            <th>Buyer</th>
            <th>Amount</th>
            <th>Payment Date</th>
            <th>Products Purchased</th>
            <th>Status</th>
        </tr>
      </thead>";
echo "<tbody>";

// Initialize a variable to calculate the sum of recent sales
$totalRecentSalesAmount = 0;

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>" . htmlspecialchars($row['buyer']) . "</td>
                <td>R" . number_format($row['amount'], 2) . "</td>
                <td>" . $row['payment_date'] . "</td>
                <td>" . htmlspecialchars($row['products_purchased']) . "</td>
                <td>" . htmlspecialchars(ucfirst($row['status'])) . "</td>
              </tr>";
        $totalRecentSalesAmount += $row['amount']; // Summing pm.total_amount directly
    }
} else {
    // If no sales, display a single row indicating no data, spanning all columns
    echo "<tr>
            <td colspan='5' style='text-align:center;'>No completed sales data available for the last 7 days.</td>
          </tr>";
}

echo "</tbody>";
echo "<tfoot>"; // Use tfoot for the total row
    echo "<tr>
            <td colspan='3' style='text-align:right; font-weight:bold;'>Total Recent Sales (Last 7 Days):</td>
            <td style='font-weight:bold;'>R" . number_format($totalRecentSalesAmount, 2) . "</td>
            <td></td> </tr>";
echo "</tfoot>";
echo "</table>";

// --- Close Database Connection ---
$conn->close();
?>