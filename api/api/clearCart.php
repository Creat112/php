<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, x-user-id");
header("Content-Type: application/json");

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User ID required']);
    exit;
}

$user_id = $data['user_id'];

try {
    // Delete all cart items for the user
    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    
    echo json_encode(['status' => 'success', 'message' => 'Cart cleared successfully']);
    
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to clear cart: ' . $e->getMessage()]);
}
?>
