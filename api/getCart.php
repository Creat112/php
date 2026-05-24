<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, x-user-id');

include('db.php');

$user_id = $_GET['user_id'] ?? null;

if (!$user_id) {
    echo json_encode([
        'status' => 'error',
        'message' => 'user_id is required'
    ]);
    exit;
}

try {

    // =========================
    // GET CART ITEMS WITH DETAILS
    // =========================

    $sql = $conn->prepare("
        SELECT 
            cart.id AS cart_id,
            cart.quantity,

            products.id AS product_id,
            products.name,
            products.price,
            products.stock,
            products.image AS product_image,

            product_variants.id AS variant_id,
            product_variants.color_name AS color,
            product_variants.color_code AS color_code,
            product_variants.image AS variant_image

        FROM cart

        INNER JOIN products 
            ON cart.product_id = products.id

        INNER JOIN product_variants 
            ON cart.variant_id = product_variants.id

        WHERE cart.user_id = ?
    ");

    $sql->execute([$user_id]);

    $items = $sql->fetchAll(PDO::FETCH_ASSOC);

    // =========================
    // CALCULATE TOTAL
    // =========================

    $total = 0;

    foreach ($items as &$item) {

        $item['subtotal'] = $item['price'] * $item['quantity'];
        $total += $item['subtotal'];
    }

    // =========================
    // RESPONSE
    // =========================

    echo json_encode([
        'status' => 'success',
        'items' => $items,
        'total' => $total
    ]);

} catch (PDOException $e) {

    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>