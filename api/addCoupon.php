<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

include('DBHelper.php');
include('Response.php');

$data = json_decode(file_get_contents("php://input"), true);

$code = $data['code'] ?? null;
$type = $data['type'] ?? null;
$value = $data['value'] ?? null;

if (!$code || !$type || $value === null) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing required fields: code, type, value'
    ]);
    exit;
}

$code = strtoupper(trim($code));

if (!in_array($type, ['free_shipping', 'fixed', 'percentage'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid coupon type. Must be free_shipping, fixed, or percentage'
    ]);
    exit;
}

try {
    // Check if code already exists
    if (DBHelper::query("SELECT id FROM coupons WHERE code = ?", [$code])->fetch()) {
        Response::error('Coupon code already exists');
        exit;
    }

    if (DBHelper::query("INSERT INTO coupons (code, type, value, active) VALUES (?, ?, ?, 1)", [$code, $type, $value])) {
        Response::success('Coupon added successfully');
    } else {
        Response::error('Failed to add coupon');
    }
} catch (PDOException $e) {
    Response::error($e->getMessage());
}
?>
