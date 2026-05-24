<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

include('db.php');

$data = json_decode(file_get_contents("php://input"), true);
$id = $data['id'] ?? $_GET['id'] ?? null;

if (!$id) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing coupon id'
    ]);
    exit;
}

try {
    $stmt = $conn->prepare("DELETE FROM coupons WHERE id = ?");
    if ($stmt->execute([$id])) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Coupon deleted successfully'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to delete coupon'
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
