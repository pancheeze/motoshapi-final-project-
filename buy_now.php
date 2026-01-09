<?php
session_start();
require_once 'config/database.php';

$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
$variation_id = isset($_GET['variation_id']) ? intval($_GET['variation_id']) : null;
$quantity = isset($_GET['quantity']) ? max(1, intval($_GET['quantity'])) : 1;

// Redirect to checkout.php with buy_now=1 and product details
$query = 'checkout.php?buy_now=1&product_id=' . $product_id . '&quantity=' . $quantity;
if ($variation_id) {
    $query .= '&variation_id=' . $variation_id;
}
header('Location: ' . $query);
exit();

// Fetch product and variation info
$stmt = $conn->prepare('SELECT * FROM products WHERE id = ?');
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

$variation = null;
if ($variation_id) {
    $vstmt = $conn->prepare('SELECT * FROM variations WHERE id = ? AND product_id = ?');
    $vstmt->execute([$variation_id, $product_id]);
    $variation = $vstmt->fetch(PDO::FETCH_ASSOC);
}

$item_price = $variation && $variation['price'] !== null ? $variation['price'] : ($product['price'] ?? 0);
$item_stock = $variation ? $variation['stock'] : ($product['stock'] ?? 0);
$item_name = $product['name'] . ($variation ? ' (' . $variation['variation'] . ')' : '');

if ($product && $item_stock >= $quantity) {
    $cart_key = $product_id . ($variation_id ? ('_' . $variation_id) : '');
    $_SESSION['cart'] = []; // Clear cart for Buy Now
    $_SESSION['cart'][$cart_key] = [
        'id' => $product['id'],
        'name' => $item_name,
        'price' => $item_price,
        'image_url' => $product['image_url'],
        'quantity' => $quantity,
        'stock' => $item_stock,
        'variation_id' => $variation_id,
        'variation' => $variation ? $variation['variation'] : null
    ];
    header('Location: checkout.php');
    exit();
} else {
    $_SESSION['error'] = 'Product not available or insufficient stock.';
    header('Location: product.php?id=' . $product_id);
    exit();
} 