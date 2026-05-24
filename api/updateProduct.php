<?php

error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, x-user-id');

set_error_handler(function($errno, $errstr, $errfile, $errline) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Server error'
    ]);
    exit;
});

set_exception_handler(function($exception) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Server error'
    ]);
    exit;
});

include('db.php');

$data = json_decode(file_get_contents('php://input'), true);

if (
    !isset($data['id']) ||
    !isset($data['name']) ||
    !isset($data['price']) ||
    !isset($data['category'])
) {

    echo json_encode([
        'status' => 'error',
        'message' => 'Missing required fields'
    ]);

    exit;
}

$id = $data['id'];
$name = $data['name'];
$description = $data['description'] ?? '';
$price = $data['price'];
$image = $data['image'] ?? '';
$category = $data['category'];
$discount = $data['discount'] ?? 0;
$enabled = $data['enabled'] ?? true;

$colors = $data['variant_id'] ?? $data['colors'] ?? [];

// Calculate total stock from variants
$totalStock = 0;
foreach ($colors as $color) {
    $totalStock += ($color['stock'] ?? 0);
}
$stock = $totalStock;

try {

    $conn->beginTransaction();

    // =========================
    // UPDATE PRODUCT
    // =========================

    $sql = $conn->prepare("
        UPDATE products
        SET
            name = ?,
            description = ?,
            price = ?,
            image = ?,
            category = ?,
            stock = ?,
            discount = ?,
            enabled = ?
        WHERE id = ?
    ");

    $sql->execute([
        $name,
        $description,
        $price,
        $image,
        $category,
        $stock,
        $discount,
        $enabled,
        $id
    ]);

    // =========================
    // DELETE OLD variant_id
    // =========================

    $deleteSql = $conn->prepare("
        DELETE FROM product_variants
        WHERE product_id = ?
    ");

    $deleteSql->execute([$id]);

    // =========================
    // INSERT NEW variant_id
    // =========================

    if (!empty($colors)) {

        $variantSql = $conn->prepare("
            INSERT INTO product_variants
            (
                product_id,
                color_name,
                color_code,
                image,
                stock
            )
            VALUES (?, ?, ?, ?, ?)
        ");

        foreach ($colors as $color) {

            $images = isset($color['images']) && is_array($color['images']) ? json_encode($color['images']) : (isset($color['image']) ? $color['image'] : '[]');

            $variantSql->execute([
                $id,
                $color['name'],
                $color['code'],
                $images,
                $color['stock'] ?? 0
            ]);
        }
    }

    $conn->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'Product updated successfully'
    ]);

} catch (PDOException $e) {

    $conn->rollBack();

    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>