<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

include('db.php');

$data = json_decode(file_get_contents("php://input"), true);
$id = $data['order_id'] ?? $data['id'] ?? $_GET['id'] ?? null;

if (!$id) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing order_id'
    ]);
    exit;
}

try {
    $conn->beginTransaction();

    // 1. Delete associated order items
    $stmt1 = $conn->prepare("DELETE FROM order_items WHERE order_id = ?");
    $stmt1->execute([$id]);

    // 2. Delete the order
    $stmt2 = $conn->prepare("DELETE FROM orders WHERE id = ?");
    $stmt2->execute([$id]);

    $conn->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'Order deleted successfully'
    ]);
} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
