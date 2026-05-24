<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, x-user-id');

include('db.php');

$data = json_decode(file_get_contents("php://input"), true);

$user_id = $data['user_id'] ?? null;
$product_id = $data['product_id'] ?? null;
$variant_id = $data['variant_id'] ?? null;
$color = $data['color'] ?? null;

if (!$user_id || !$product_id) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing required fields'
    ]);
    exit;
}

if (!$variant_id && $color) {
    $stmt = $conn->prepare("SELECT id FROM product_variants WHERE product_id = ? AND color_name = ?");
    $stmt->execute([$product_id, $color]);
    $variant = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($variant) {
        $variant_id = $variant['id'];
    }
}

if (!$variant_id) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Variant not found'
    ]);
    exit;
}

try {

    $sql = $conn->prepare("
        DELETE FROM cart 
        WHERE user_id = ? 
        AND product_id = ? 
        AND variant_id = ?
    ");

    $sql->execute([$user_id, $product_id, $variant_id]);

    if ($sql->rowCount() > 0) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Item removed from cart'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Item not found'
        ]);
    }

} catch (PDOException $e) {

    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>