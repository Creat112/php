<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, x-user-id');

include('db.php');

try {
    $stmt = $conn->query("SELECT * FROM products ORDER BY id DESC");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($products as &$product) {
        $colorSql = $conn->prepare("
            SELECT id, color_name, color_code, image, stock
            FROM product_variants
            WHERE product_id = ?
        ");
        $colorSql->execute([$product['id']]);
        $colors = $colorSql->fetchAll(PDO::FETCH_ASSOC);

        $formattedColors = [];
        foreach ($colors as $color) {
            // Decode image field which may be JSON array or a single string
            $images = [];
            if (isset($color['image'])) {
                $decoded = json_decode($color['image'], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $images = $decoded;
                } else {
                    // Treat as a single image string
                    $images[] = $color['image'];
                }
            }
            $formattedColors[] = [
                'id' => (int) $color['id'],
                'name' => $color['color_name'],
                'code' => $color['color_code'],
                'images' => $images,
                'stock' => (int) ($color['stock'] ?? 0),
            ];
        }

        $product['colors'] = $formattedColors;
        $product['price'] = (float) $product['price'];
        $product['discount'] = (float) ($product['discount'] ?? 0);
        $product['enabled'] = (bool) ($product['enabled'] ?? true);
        $stock = (int) $product['stock'];
        $product['stock'] = $stock;
        $product['in_stock'] = $stock > 0;
    }
    unset($product);

    echo json_encode([
        'status' => 'success',
        'results' => count($products),
        'data' => $products,
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
    ]);
}
