<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: PUT');
header('Access-Control-Allow-Headers: Content-Type');

include('db.php');

$data = json_decode(file_get_contents("php://input"), true);

$order_id = $data['order_id'] ?? null;
$status = $data['status'] ?? null;

$allowed_status = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];

if (!$order_id || !$status) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing fields'
    ]);
    exit;
}

// =========================
// VALIDATE STATUS
// =========================

if (!in_array($status, $allowed_status)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid status'
    ]);
    exit;
}

try {

    $sql = $conn->prepare("
        UPDATE orders 
        SET status = ?
        WHERE id = ?
    ");

    $sql->execute([$status, $order_id]);

    echo json_encode([
        'status' => 'success',
        'message' => 'Order status updated',
        'new_status' => $status
    ]);

} catch (PDOException $e) {

    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>