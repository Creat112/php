<?php
// test_insert.php
// Reads tmp_test_product.json and inserts into DB using DBHelper

require 'DBHelper.php';

$jsonFile = __DIR__ . '/tmp_test_product.json';
if (!file_exists($jsonFile)) {
    echo "Test JSON file not found.\n";
    exit(1);
}
$data = json_decode(file_get_contents($jsonFile), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo "Invalid JSON: " . json_last_error_msg() . "\n";
    exit(1);
}

$name = $data['name'] ?? '';
$description = $data['description'] ?? '';
$price = $data['price'] ?? 0;
$category = $data['category'] ?? '';
$stock = $data['stock'] ?? 0;
$image = $data['image'] ?? '';
$colors = $data['colors'] ?? [];

// Insert product
$stmt = DBHelper::query(
    "INSERT INTO products (name, description, price, category, stock, image) VALUES (?,?,?,?,?,?)",
    [$name, $description, $price, $category, $stock, $image]
);
$productId = DBHelper::getConnection()->lastInsertId();

echo "Inserted product ID: $productId\n";

// Insert variants if any
foreach ($colors as $c) {
    // Support single image or array of images
    $variantImages = [];
    if (isset($c['image'])) {
        $variantImages[] = $c['image'];
    }
    if (isset($c['images']) && is_array($c['images'])) {
        $variantImages = array_merge($variantImages, $c['images']);
    }
    $imagesJson = json_encode($variantImages);
    DBHelper::query(
        "INSERT INTO product_variants (product_id, color_name, color_code, image) VALUES (?,?,?,?)",
        [$productId, $c['name'] ?? '', $c['code'] ?? '', $imagesJson]
    );
}

echo "Done.\n";
?>
