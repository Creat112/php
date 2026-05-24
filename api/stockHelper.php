<?php

function getProductStock(PDO $conn, int $productId): int
{
    $stmt = $conn->prepare('SELECT stock FROM products WHERE id = ?');
    $stmt->execute([$productId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? (int) $row['stock'] : 0;
}

/** Total units of a product already in the user's cart (all variants). */
function getCartQtyForProduct(PDO $conn, int $userId, int $productId): int
{
    $stmt = $conn->prepare(
        'SELECT COALESCE(SUM(quantity), 0) AS total FROM cart WHERE user_id = ? AND product_id = ?'
    );
    $stmt->execute([$userId, $productId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return (int) ($row['total'] ?? 0);
}

/** Qty for one cart line (user + product + variant). */
function getCartLineQty(PDO $conn, int $userId, int $productId, int $variantId): int
{
    $stmt = $conn->prepare(
        'SELECT COALESCE(SUM(quantity), 0) AS total FROM cart
         WHERE user_id = ? AND product_id = ? AND variant_id = ?'
    );
    $stmt->execute([$userId, $productId, $variantId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return (int) ($row['total'] ?? 0);
}

/**
 * Validate that the user can hold $newLineQty units on this variant line
 * without exceeding product stock (stock is per product, not per variant).
 */
function validateCartStock(PDO $conn, int $userId, int $productId, int $variantId, int $newLineQty): ?string
{
    if ($newLineQty < 1) {
        return 'Quantity must be at least 1';
    }

    $stock = getProductStock($conn, $productId);
    if ($stock <= 0) {
        return 'This product is out of stock';
    }

    $currentLineQty = getCartLineQty($conn, $userId, $productId, $variantId);
    $totalInCart = getCartQtyForProduct($conn, $userId, $productId);
    $otherLinesQty = $totalInCart - $currentLineQty;
    $needed = $otherLinesQty + $newLineQty;

    if ($needed > $stock) {
        $available = max(0, $stock - $otherLinesQty);
        return $available > 0
            ? "Only {$available} item(s) available in stock"
            : 'Not enough stock available';
    }

    return null;
}
