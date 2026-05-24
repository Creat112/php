<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

include('db.php');

// Accept search query (could be order code e.g. AMBR-12, raw ID e.g. 12, or phone number)
$query = $_GET['query'] ?? $_GET['order_id'] ?? null;

if (!$query) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Search query is required'
    ]);
    exit;
}

try {
    $orders = [];
    
    // 1. Try to search by Order ID if query looks like an Order ID
    $searchId = null;
    if (strpos(strtoupper($query), 'AMBR-') === 0) {
        $searchId = (int) substr($query, 5);
    } elseif (is_numeric($query) && strlen($query) <= 6) {
        $searchId = (int) $query;
    }
    
    if ($searchId !== null) {
        $sql = $conn->prepare("
            SELECT 
                orders.id,
                orders.total,
                orders.status,
                orders.shipping_address,
                orders.payment_method,
                orders.created_at
            FROM orders
            WHERE orders.id = ?
        ");
        $sql->execute([$searchId]);
        $order = $sql->fetch(PDO::FETCH_ASSOC);
        if ($order) {
            $orders[] = $order;
        }
    }
    
    // 2. If no order found by ID or if query is a phone number/name, search by shipping_address
    if (empty($orders)) {
        $sql = $conn->prepare("
            SELECT 
                orders.id,
                orders.total,
                orders.status,
                orders.shipping_address,
                orders.payment_method,
                orders.created_at
            FROM orders
            WHERE orders.shipping_address LIKE ?
            ORDER BY orders.id DESC
        ");
        $sql->execute(['%' . $query . '%']);
        $orders = $sql->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // 3. For each found order, get its items and parse its shipping details
    foreach ($orders as &$order) {
        $order['order_code'] = 'AMBR-' . $order['id'];
        
        // Parse shipping details if stored as JSON
        $shipping = json_decode($order['shipping_address'], true);
        if ($shipping) {
            $order['full_name'] = $shipping['fullName'] ?? 'Customer';
            $order['phone'] = $shipping['phone'] ?? '';
            $order['address'] = $shipping['address'] ?? '';
            $order['city'] = $shipping['city'] ?? '';
            $order['governorate'] = $shipping['governorate'] ?? '';
        } else {
            // Fallback for older plaintext shipping addresses
            $order['full_name'] = 'Customer';
            $order['phone'] = '';
            $order['address'] = $order['shipping_address'];
            $order['city'] = '';
            $order['governorate'] = '';
        }
        
        // Fetch order items with variants
        $itemsSql = $conn->prepare("
            SELECT 
                order_items.quantity,
                order_items.price,
    
                products.name AS product_name,
                products.image,
    
                product_variants.color_name AS color,
                product_variants.color_code AS color_code,
                product_variants.image AS variant_image
    
            FROM order_items
    
            INNER JOIN products 
                ON order_items.product_id = products.id
    
            INNER JOIN product_variants 
                ON order_items.variant_id = product_variants.id
    
            WHERE order_items.order_id = ?
        ");
        $itemsSql->execute([$order['id']]);
        $order['items'] = $itemsSql->fetchAll(PDO::FETCH_ASSOC);
    }

    echo json_encode([
        'status' => 'success',
        'data' => $orders
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>