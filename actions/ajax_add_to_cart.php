<?php
session_start();
require_once dirname(__DIR__) . '/config/connect.php';

header('Content-Type: application/json');

// Initialize cart if not set
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Validate request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
$quantity = isset($_POST['quantity']) ? max(1, intval($_POST['quantity'])) : 1;
$variation_id = isset($_POST['variation_id']) ? intval($_POST['variation_id']) : null;

if ($product_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product']);
    exit();
}

try {
    // Check if product exists
    $stmt = $conn->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit();
    }

    $variation = null;
    if ($variation_id) {
        $vstmt = $conn->prepare('SELECT * FROM variations WHERE id = ? AND product_id = ?');
        $vstmt->execute([$variation_id, $product_id]);
        $variation = $vstmt->fetch(PDO::FETCH_ASSOC);
    }

    // Determine price and stock
    $item_price = $variation && $variation['price'] !== null ? $variation['price'] : $product['price'];
    $item_stock = $variation ? $variation['stock'] : $product['stock'];
    $item_name = $product['name'] . ($variation ? ' (' . $variation['variation'] . ')' : '');

    // Check stock availability
    if ($item_stock < $quantity) {
        echo json_encode(['success' => false, 'message' => 'Insufficient stock available']);
        exit();
    }

    // Unique cart key for product+variation
    $cart_key = $product_id . ($variation_id ? ('_' . $variation_id) : '');
    
    if (isset($_SESSION['cart'][$cart_key])) {
        // Check if adding more would exceed stock
        $new_quantity = $_SESSION['cart'][$cart_key]['quantity'] + $quantity;
        if ($new_quantity > $item_stock) {
            echo json_encode(['success' => false, 'message' => 'Cannot add more, stock limit reached']);
            exit();
        }
        $_SESSION['cart'][$cart_key]['quantity'] = $new_quantity;
    } else {
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
    }

    // Calculate cart count
    $cart_count = 0;
    foreach ($_SESSION['cart'] as $item) {
        $cart_count += $item['quantity'];
    }

    echo json_encode([
        'success' => true,
        'message' => 'Product added to cart!',
        'product_name' => $item_name,
        'cart_count' => $cart_count
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error adding to cart']);
}
