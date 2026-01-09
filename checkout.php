<?php
// Test comment for schema sync - $(date)
session_start();
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "You must be logged in to checkout.";
    header("Location: login.php");
    exit();
}
require_once 'config/database.php';
require_once 'config/currency.php';

$buy_now = isset($_GET['buy_now']) && $_GET['buy_now'] == 1;

if ($buy_now) {
    // Single product checkout (Buy Now)
    $product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
    $variation_id = isset($_GET['variation_id']) ? intval($_GET['variation_id']) : null;
    $quantity = isset($_GET['quantity']) ? max(1, intval($_GET['quantity'])) : 1;

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

    if (!$product || $item_stock < $quantity) {
        $_SESSION['error'] = 'Product not available or insufficient stock.';
        header('Location: product.php?id=' . $product_id);
        exit();
    }

    $checkout_items = [[
        'id' => $product['id'],
        'name' => $item_name,
        'price' => $item_price,
        'image_url' => $product['image_url'],
        'quantity' => $quantity,
        'stock' => $item_stock,
        'variation_id' => $variation_id,
        'variation' => $variation ? $variation['variation'] : null
    ]];
    $total = $item_price * $quantity;
} else {
    $checkout_items = $_SESSION['cart'] ?? [];
    $total = 0;
    foreach ($checkout_items as $item) {
        $total += $item['price'] * $item['quantity'];
    }
}

if (empty($checkout_items)) {
    $_SESSION['error'] = "Your cart is empty. Please add items before checking out.";
    header("Location: cart.php");
    exit();
}

// Online checkout only - COD is the only available payment method
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    try {
        $conn->beginTransaction();

        // Only COD payment is supported
        $payment_mode = 'cod';
        
        // Get payment_mode_id from payment_modes table
        $stmt = $conn->prepare('SELECT id FROM payment_modes WHERE mode_code = ?');
        $stmt->execute([$payment_mode]);
        $payment_mode_id = $stmt->fetchColumn();
        if (!$payment_mode_id) {
            throw new Exception('Payment mode not found.');
        }

        // Create order
        $stmt = $conn->prepare('INSERT INTO orders (user_id, first_name, last_name, total_amount, status, transaction_type, payment_mode_id, payment_details) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $_SESSION['user_id'],
            $_POST['first_name'],
            $_POST['last_name'],
            $total,
            'pending',
            'online',
            $payment_mode_id,
            json_encode([])
        ]);
        $order_id = $conn->lastInsertId();

        // Insert order items
        $stmt = $conn->prepare('INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)');
        foreach ($checkout_items as $item) {
            $stmt->execute([$order_id, $item['id'], $item['quantity'], $item['price']]);
        }

        // Insert shipping information
        $stmt = $conn->prepare('INSERT INTO shipping_information (order_id, first_name, last_name, email, phone, house_number, street, barangay, city, province, postal_code, payment_mode_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $order_id,
            $_POST['first_name'],
            $_POST['last_name'],
            $_POST['email'],
            $_POST['phone'],
            $_POST['house_number'],
            $_POST['street'],
            $_POST['barangay'],
            $_POST['city'],
            $_POST['province'],
            $_POST['postal_code'],
            $payment_mode_id
        ]);

        $conn->commit();
        if (!$buy_now) {
            unset($_SESSION['cart']);
        }

        // Show success message
        $title = 'Order Placed - Motoshapi';
        include 'includes/header.php';
        ?>
        <div class="container mt-5">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card shadow">
                        <div class="card-body text-center">
                            <h1 class="text-success mb-4">Order Placed Successfully!</h1>
                            <p class="lead">Thank you for your order. Your order number is: #<?php echo $order_id; ?></p>
                            <p>We will process your order and send you an email confirmation shortly.</p>
                            <div class="mt-4">
                                <a href="products.php" class="btn btn-primary">Continue Shopping</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        include 'includes/footer.php';
        exit;
    } catch (Exception $e) {
        $conn->rollBack();
        $error = 'Error: ' . $e->getMessage();
    }
}

$title = 'Online Checkout - Motoshapi';
include 'includes/header.php';
?>
<div class="container mt-4">
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-body">
                    <h2>Shipping Information</h2>
                    <?php if(isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="house_number" class="form-label">House/Building Number</label>
                                <input type="text" class="form-control" id="house_number" name="house_number" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="street" class="form-label">Street Name</label>
                                <input type="text" class="form-control" id="street" name="street" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="barangay" class="form-label">Barangay</label>
                                <input type="text" class="form-control" id="barangay" name="barangay" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="city" class="form-label">City/Municipality</label>
                                <input type="text" class="form-control" id="city" name="city" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="province" class="form-label">Province</label>
                                <input type="text" class="form-control" id="province" name="province" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="postal_code" class="form-label">Postal Code</label>
                                <input type="text" class="form-control" id="postal_code" name="postal_code" required>
                            </div>
                        </div>

                        <h2 class="mt-4 mb-3">Mode of Payment</h2>
                        <div class="mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_mode" id="cod" value="cod" required checked>
                                <label class="form-check-label" for="cod">
                                    Cash on Delivery (COD)
                                </label>
                            </div>
                            <small class="form-text text-muted">Pay when your order arrives</small>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="cart.php" class="btn btn-secondary">Back to Cart</a>
                            <button type="submit" name="place_order" class="btn btn-success">Place Order</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h3>Order Summary</h3>
                    <table class="table">
                        <tbody>
                            <?php foreach ($checkout_items as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['name']); ?> x <?php echo $item['quantity']; ?></td>
                                <td class="text-end"><?php echo format_price($item['price'] * $item['quantity']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th>Total</th>
                                <th class="text-end"><?php echo format_price($total); ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>