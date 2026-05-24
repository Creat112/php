<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: DELETE');
header('Access-Control-Allow-Headers: Content-Type, x-user-id');

include('db.php');

$data = json_decode(file_get_contents("php://input"), true);

$product_id = $data['product_id'] ?? null;

if (!$product_id) {
    echo json_encode([
        'status' => 'error',
        'message' => 'product_id is required'
    ]);
    exit;
}

try {

    // =========================
    // DELETE PRODUCT
    // =========================

    $sql = $conn->prepare("DELETE FROM products WHERE id = ?");
    $sql->execute([$product_id]);

    // variants & cart will auto delete بسبب foreign keys 👌

    echo json_encode([
        'status' => 'success',
        'message' => 'Product deleted successfully'
    ]);

} catch (PDOException $e) {

    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>