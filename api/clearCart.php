<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, x-user-id');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

include('DBHelper.php');
include('Response.php');
$conn = DBHelper::getConnection();

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['user_id'])) {

    Response::error('User ID required', 400);

    exit;
}

$user_id = $data['user_id'];

try {

    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");

    if ($stmt->execute([$user_id])) {

        Response::success('Cart cleared successfully');

    } else {

        Response::error('Failed to clear cart', 500);

    }

} catch (PDOException $e) {

    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to clear cart: ' . $e->getMessage()
    ]);

}
?>