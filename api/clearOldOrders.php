<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

include('db.php');

$data = json_decode(file_get_contents("php://input"), true);
$period = $data['period'] ?? null;

if (!$period) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing period'
    ]);
    exit;
}

// Map the period to a SQL interval
switch ($period) {
    case '30days':
        $interval = '30 DAY';
        break;
    case '3months':
        $interval = '3 MONTH';
        break;
    case '6months':
        $interval = '6 MONTH';
        break;
    case '1year':
        $interval = '1 YEAR';
        break;
    default:
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid period. Allowed values: 30days, 3months, 6months, 1year'
        ]);
        exit;
}

try {
    $conn->beginTransaction();

    // 1. Delete associated order items for orders older than interval
    $stmt1 = $conn->prepare("
        DELETE order_items FROM order_items
        INNER JOIN orders ON order_items.order_id = orders.id
        WHERE orders.created_at < NOW() - INTERVAL $interval
    ");
    $stmt1->execute();

    // 2. Delete the orders
    $stmt2 = $conn->prepare("
        DELETE FROM orders 
        WHERE created_at < NOW() - INTERVAL $interval
    ");
    $stmt2->execute();
    
    $deletedCount = $stmt2->rowCount();

    $conn->commit();

    echo json_encode([
        'status' => 'success',
        'message' => "Purged $deletedCount orders older than $period successfully"
    ]);
} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
