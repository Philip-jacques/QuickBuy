<?php
require 'db.php';
session_start();

$buyerId = $_SESSION['buyer_id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $buyerId) {
    $cartIds = $_POST['cart_ids'] ?? [];
    $quantities = $_POST['quantities'] ?? [];

    foreach ($cartIds as $index => $cartId) {
        $qty = (int) $quantities[$index];
        if ($qty > 0) {
            $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND buyer_id = ?");
            $stmt->execute([$qty, $cartId, $buyerId]);
        }
    }
}

header("Location: ViewCartPage.php");
exit;
