<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

include('db.php');

try {

    // =========================
    // USERS COUNT
    // =========================

    $users = $conn->query("SELECT COUNT(*) as count FROM users")
                  ->fetch(PDO::FETCH_ASSOC)['count'];

    // =========================
    // PRODUCTS COUNT
    // =========================

    $products = $conn->query("SELECT COUNT(*) as count FROM products")
                     ->fetch(PDO::FETCH_ASSOC)['count'];

    $lowStock = $conn->query("SELECT COUNT(*) as count FROM products WHERE stock > 0 AND stock <= 5")
                     ->fetch(PDO::FETCH_ASSOC)['count'];

    $outOfStock = $conn->query("SELECT COUNT(*) as count FROM products WHERE stock <= 0")
                       ->fetch(PDO::FETCH_ASSOC)['count'];

    // =========================
    // ORDERS COUNT
    // =========================

    $orders = $conn->query("SELECT COUNT(*) as count FROM orders")
                   ->fetch(PDO::FETCH_ASSOC)['count'];

    // =========================
    // TOTAL SALES (FROM ORDERS)
    // =========================

    $orderSales = $conn->query("SELECT SUM(total) as total FROM orders")
                  ->fetch(PDO::FETCH_ASSOC)['total'];

    if (!$orderSales) {
        $orderSales = 0;
    }

    // =========================
    // TOTAL MANUAL ADJUSTMENTS
    // =========================

    $adjustmentsTotal = $conn->query("SELECT SUM(amount) as total FROM admin_adjustments")
                            ->fetch(PDO::FETCH_ASSOC)['total'];

    if (!$adjustmentsTotal) {
        $adjustmentsTotal = 0;
    }

    $sales = (float)$orderSales + (float)$adjustmentsTotal;

    // =========================
    // DAILY ORDERS (LAST 30 DAYS)
    // =========================

    $historyStmt = $conn->query("
        SELECT DATE(created_at) as date, COUNT(*) as count 
        FROM orders 
        WHERE created_at >= NOW() - INTERVAL 30 DAY 
        GROUP BY DATE(created_at) 
        ORDER BY DATE(created_at) ASC
    ");
    $history = $historyStmt->fetchAll(PDO::FETCH_ASSOC);

    // =========================
    // RESPONSE
    // =========================

    echo json_encode([
        'status' => 'success',
        'data' => [
            'users' => (int)$users,
            'products' => (int)$products,
            'orders' => (int)$orders,
            'sales' => (float)$sales,
            'orderSales' => (float)$orderSales,
            'adjustmentsTotal' => (float)$adjustmentsTotal,
            'orderHistory' => $history,
            'lowStock' => (int) $lowStock,
            'outOfStock' => (int) $outOfStock
        ]
    ]);

} catch (PDOException $e) {

    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>