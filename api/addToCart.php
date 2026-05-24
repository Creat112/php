<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, x-user-id');

include('DBHelper.php');
include('stockHelper.php');
$conn = DBHelper::getConnection();

$data = json_decode(file_get_contents('php://input'), true);

$user_id = $data['user_id'] ?? null;
$product_id = $data['product_id'] ?? null;
$variant_id = $data['variant_id'] ?? null;
$color = $data['color'] ?? null;
$quantity = (int) ($data['quantity'] ?? 1);

if (!$user_id || !$product_id) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
    exit;
}

if (!$variant_id && $color) {
    $stmt = $conn->prepare('SELECT id FROM product_variants WHERE product_id = ? AND color_name = ?');
    $stmt->execute([$product_id, $color]);
    $variant = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($variant) {
        $variant_id = $variant['id'];
    }
}

if (!$variant_id) {
    echo json_encode(['status' => 'error', 'message' => 'Variant not found']);
    exit;
}

try {
    $check = $conn->prepare(
        'SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ? AND variant_id = ?'
    );
    $check->execute([$user_id, $product_id, $variant_id]);
    $existing = $check->fetch(PDO::FETCH_ASSOC);

    $newLineQty = $existing ? (int) $existing['quantity'] + $quantity : $quantity;
    $stockError = validateCartStock($conn, (int) $user_id, (int) $product_id, (int) $variant_id, $newLineQty);

    if ($stockError) {
        echo json_encode(['status' => 'error', 'message' => $stockError]);
        exit;
    }

    if ($existing) {
        $update = $conn->prepare('UPDATE cart SET quantity = ? WHERE id = ?');
        $update->execute([$newLineQty, $existing['id']]);
        echo json_encode(['status' => 'success', 'message' => 'Cart updated']);
    } else {
        $insert = $conn->prepare(
            'INSERT INTO cart (user_id, product_id, variant_id, quantity) VALUES (?, ?, ?, ?)'
        );
        $insert->execute([$user_id, $product_id, $variant_id, $quantity]);
        echo json_encode(['status' => 'success', 'message' => 'Added to cart']);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
