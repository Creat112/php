<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, x-user-id');

include('db.php');
include('stockHelper.php');

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['user_id']) || !isset($data['product_id']) || !isset($data['quantity'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
    exit;
}

$user_id = (int) $data['user_id'];
$product_id = (int) $data['product_id'];
$variant_id = $data['variant_id'] ?? null;
$color = $data['color'] ?? null;
$quantity = (int) $data['quantity'];

if (!$variant_id && $color) {
    $stmt = $conn->prepare('SELECT id FROM product_variants WHERE product_id = ? AND color_name = ?');
    $stmt->execute([$product_id, $color]);
    $variant = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($variant) {
        $variant_id = (int) $variant['id'];
    }
}

if (!$variant_id) {
    echo json_encode(['status' => 'error', 'message' => 'Variant not found']);
    exit;
}

$variant_id = (int) $variant_id;

try {
    $stockError = validateCartStock($conn, $user_id, $product_id, $variant_id, $quantity);
    if ($stockError) {
        echo json_encode(['status' => 'error', 'message' => $stockError]);
        exit;
    }

    $stmt = $conn->prepare(
        'SELECT id FROM cart WHERE user_id = ? AND product_id = ? AND variant_id = ?'
    );
    $stmt->execute([$user_id, $product_id, $variant_id]);
    $existingItem = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existingItem) {
        $stmt = $conn->prepare('UPDATE cart SET quantity = ? WHERE id = ?');
        $stmt->execute([$quantity, $existingItem['id']]);
    } else {
        $stmt = $conn->prepare(
            'INSERT INTO cart (user_id, product_id, variant_id, quantity) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$user_id, $product_id, $variant_id, $quantity]);
    }

    echo json_encode(['status' => 'success', 'message' => 'Cart item updated successfully']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to update cart item: ' . $e->getMessage()]);
}
