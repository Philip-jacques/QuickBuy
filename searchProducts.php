<?php
require_once 'db.php';

if (isset($_GET['query'])) {
    $query = "%" . $_GET['query'] . "%";

    $stmt = $conn->prepare("SELECT DISTINCT name FROM products WHERE name LIKE ?");
    $stmt->bind_param("s", $query);
    $stmt->execute();

    $result = $stmt->get_result();
    $productNames = [];

    while ($row = $result->fetch_assoc()) {
        $productNames[] = $row['name'];
    }

    echo json_encode($productNames);
}
?>
