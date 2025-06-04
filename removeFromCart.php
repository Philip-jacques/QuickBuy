<?php
require 'db.php';
session_start();

$buyerId = $_SESSION['buyer_id'] ?? null;
$cartId = $_POST['cart_id'] ?? null;

if ($buyerId && $cartId) {
    $stmt = $conn->prepare("DELETE FROM cart WHERE id = ? AND buyer_id = ?");
    $stmt->execute([$cartId, $buyerId]);
}

header("Location: ViewCartPage.php");
exit;
