<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, x-user-id');

include('DBHelper.php');
include('Response.php');

$data = json_decode(file_get_contents('php://input'), true);

if (
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

$name = $data['name'];
$description = $data['description'] ?? '';
$price = $data['price'];
$image = $data['image'] ?? '';
$category = $data['category'];
$discount = $data['discount'] ?? 0;
$enabled = $data['enabled'] ?? true;

// Calculate total stock from variants
$colors = $data['colors'] ?? [];
$totalStock = 0;
foreach ($colors as $color) {
    $totalStock += ($color['stock'] ?? 0);
}
$stock = $totalStock;



try {

    DBHelper::getConnection()->beginTransaction();

    // =========================
    // INSERT PRODUCT
    // =========================

    $sql = "INSERT INTO products (name, description, price, image, category, stock, discount, enabled) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    DBHelper::query($sql, [$name, $description, $price, $image, $category, $stock, $discount, $enabled]);
    $product_id = DBHelper::getConnection()->lastInsertId();

    // =========================
    // INSERT VARIANTS
    // =========================

    $colors = $data['colors'] ?? [];

    if (!empty($colors)) {
        $variantSql = "INSERT INTO product_variants (product_id, color_name, color_code, image, stock) VALUES (?, ?, ?, ?, ?)";
        foreach ($colors as $color) {
            $images = isset($color['images']) && is_array($color['images']) ? json_encode($color['images']) : (isset($color['image']) ? $color['image'] : '[]');
            DBHelper::query($variantSql, [
                $product_id,
                $color['name'],
                $color['code'],
                $images,
                $color['stock'] ?? 0
            ]);
        }
    }

    DBHelper::getConnection()->commit();
    Response::success('Product added successfully');

} catch (PDOException $e) {

    DBHelper::getConnection()->rollBack();
    Response::error($e->getMessage());
}
?>