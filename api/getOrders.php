<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

include('DBHelper.php');
include('Response.php');

try {

    // =========================
    // GET ORDERS
    // =========================

    $sql = DBHelper::getConnection()->prepare("
        SELECT 
            orders.id,
            orders.user_id,
            orders.total,
            orders.status,
            orders.shipping_address,
            orders.payment_method,
            orders.created_at,
            users.name AS user_name,
            users.email AS user_email
        FROM orders
        INNER JOIN users ON orders.user_id = users.id
        ORDER BY orders.id DESC
    ");

    $sql->execute();

    $orders = $sql->fetchAll(PDO::FETCH_ASSOC);

    // =========================
    // GET ITEMS AND FORMAT SHIPPING FOR EACH ORDER
    // =========================

    foreach ($orders as &$order) {
        // Set order number format
        $order['orderNumber'] = 'AMBR-' . $order['id'];
        $order['date'] = $order['created_at'];

        // Parse JSON shipping details
        $shipping = json_decode($order['shipping_address'], true);
        if ($shipping) {
            $order['customer'] = [
                'fullName' => $shipping['fullName'] ?? 'Guest',
                'email' => $order['user_email'] ?? '',
                'phone' => $shipping['phone'] ?? ''
            ];
            $order['shipping'] = [
                'address' => $shipping['address'] ?? '',
                'city' => $shipping['city'] ?? '',
                'governorate' => $shipping['governorate'] ?? ''
            ];
        } else {
            $order['customer'] = [
                'fullName' => $order['user_name'] ?? 'Guest',
                'email' => $order['user_email'] ?? '',
                'phone' => ''
            ];
            $order['shipping'] = [
                'address' => $order['shipping_address'] ?? '',
                'city' => '',
                'governorate' => ''
            ];
        }

        // Fetch order items with matching column names for admin view
        $itemsSql = DBHelper::getConnection()->prepare("
            SELECT 
                order_items.quantity,
                order_items.price,

                products.name AS name,
                products.image AS productImage,

                product_variants.color_name AS colorName,
                product_variants.color_code AS color_code,
                product_variants.image AS variantImage

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

    Response::success('Orders retrieved', $orders);

} catch (PDOException $e) {

    Response::error($e->getMessage(), 500);
}
?>