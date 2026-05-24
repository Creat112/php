<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, x-user-id');

include('db.php');
include('stockHelper.php');

$data = json_decode(file_get_contents('php://input'), true);

$user_id = $data['user_id'] ?? null;
$shipping_address = $data['shipping_address'] ?? '';
$payment_method = $data['payment_method'] ?? 'cash';

if (!$user_id) {
    echo json_encode(['status' => 'error', 'message' => 'user_id is required']);
    exit;
}

try {
    $conn->beginTransaction();

    $cartSql = $conn->prepare("
        SELECT 
            cart.product_id,
            cart.variant_id,
            cart.quantity,
            products.price,
            products.name,
            products.stock
        FROM cart
        INNER JOIN products ON cart.product_id = products.id
        WHERE cart.user_id = ?
    ");
    $cartSql->execute([$user_id]);
    $cartItems = $cartSql->fetchAll(PDO::FETCH_ASSOC);

    if (count($cartItems) === 0) {
        $conn->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Cart is empty']);
        exit;
    }

    $qtyByProduct = [];
    foreach ($cartItems as $item) {
        $pid = (int) $item['product_id'];
        $qtyByProduct[$pid] = ($qtyByProduct[$pid] ?? 0) + (int) $item['quantity'];
    }

    foreach ($qtyByProduct as $productId => $neededQty) {
        $stock = getProductStock($conn, $productId);
        if ($neededQty > $stock) {
            $conn->rollBack();
            $name = '';
            foreach ($cartItems as $item) {
                if ((int) $item['product_id'] === $productId) {
                    $name = $item['name'];
                    break;
                }
            }
            echo json_encode([
                'status' => 'error',
                'message' => ($name ? "{$name} " : '') . 'does not have enough stock',
            ]);
            exit;
        }
    }

    $total = 0;
    foreach ($cartItems as $item) {
        $total += $item['price'] * $item['quantity'];
    }

    $orderSql = $conn->prepare("
        INSERT INTO orders (user_id, total, status, shipping_address, payment_method)
        VALUES (?, ?, 'pending', ?, ?)
    ");
    $orderSql->execute([$user_id, $total, $shipping_address, $payment_method]);
    $order_id = $conn->lastInsertId();

    $itemSql = $conn->prepare("
        INSERT INTO order_items (order_id, product_id, variant_id, quantity, price)
        VALUES (?, ?, ?, ?, ?)
    ");

    $decrementSql = $conn->prepare("
        UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?
    ");

    foreach ($cartItems as $item) {
        $itemSql->execute([
            $order_id,
            $item['product_id'],
            $item['variant_id'],
            $item['quantity'],
            $item['price'],
        ]);
    }

    foreach ($qtyByProduct as $productId => $neededQty) {
        $decrementSql->execute([$neededQty, $productId, $neededQty]);
        if ($decrementSql->rowCount() === 0) {
            $conn->rollBack();
            echo json_encode(['status' => 'error', 'message' => 'Stock update failed. Please try again.']);
            exit;
        }
    }

    $clearSql = $conn->prepare('DELETE FROM cart WHERE user_id = ?');
    $clearSql->execute([$user_id]);

    $conn->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'Order placed successfully',
        'order_id' => $order_id,
        'total' => $total,
    ]);
} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
