<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

include('DBHelper.php');
include('Response.php');
$conn = DBHelper::getConnection();

$data = json_decode(file_get_contents("php://input"), true);

$amount = $data['amount'] ?? null;
$description = $data['description'] ?? null;

if ($amount === null || !$description) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing required fields: amount, description'
    ]);
    exit;
}

try {
    $stmt = DBHelper::query("INSERT INTO admin_adjustments (amount, description) VALUES (?, ?)", [$amount, $description]);
    if ($stmt) {
        Response::success('Adjustment recorded successfully');
    } else {
        Response::error('Failed to record adjustment');
    }
} catch (PDOException $e) {
    Response::error($e->getMessage());
}
?>
