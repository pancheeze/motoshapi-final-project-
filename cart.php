<?php
$title = 'Your Cart - Motoshapi';
include 'includes/header.php';

// Initialize cart if not set
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle add to cart
if (isset($_POST['add_to_cart'])) {
    $product_id = intval($_POST['product_id']);
    $quantity = max(1, intval($_POST['quantity']));
    $variation_id = isset($_POST['variation_id']) ? intval($_POST['variation_id']) : null;

    // Check if product exists
    $stmt = $conn->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

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

    if ($product && $item_stock >= $quantity) {
        // Unique cart key for product+variation
        $cart_key = $product_id . ($variation_id ? ('_' . $variation_id) : '');
        if (isset($_SESSION['cart'][$cart_key])) {
            $_SESSION['cart'][$cart_key]['quantity'] += $quantity;
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
        $_SESSION['success'] = 'Product added to cart!';
    } else {
        $_SESSION['error'] = 'Product not available or insufficient stock.';
    }
    header('Location: cart.php');
    exit();
}

// Handle remove from cart
if (isset($_POST['remove_from_cart'])) {
    $product_id = intval($_POST['product_id']);
    $variation_id = isset($_POST['variation_id']) ? intval($_POST['variation_id']) : null;
    $cart_key = $product_id . ($variation_id ? ('_' . $variation_id) : '');
    unset($_SESSION['cart'][$cart_key]);
    $_SESSION['success'] = 'Product removed from cart.';
    header('Location: cart.php');
    exit();
}

// Handle update quantity
if (isset($_POST['update_cart'])) {
    foreach ($_POST['quantities'] as $product_id => $quantity) {
        $product_id = intval($product_id);
        $quantity = max(1, intval($quantity));
        if (isset($_SESSION['cart'][$product_id])) {
            // Check stock
            if ($quantity <= $_SESSION['cart'][$product_id]['stock']) {
                $_SESSION['cart'][$product_id]['quantity'] = $quantity;
            }
        }
    }
    $_SESSION['success'] = 'Cart updated.';
    header('Location: cart.php');
    exit();
}

// Handle update variation in cart
if (isset($_POST['update_variation'])) {
    $cart_key = $_POST['cart_key'];
    $new_variation_id = intval($_POST['variation_id']);
    $quantity = max(1, intval($_POST['quantity']));
    if (isset($_SESSION['cart'][$cart_key])) {
        $product_id = $_SESSION['cart'][$cart_key]['id'];
        
        // Fetch product information first
        $pstmt = $conn->prepare('SELECT * FROM products WHERE id = ?');
        $pstmt->execute([$product_id]);
        $product = $pstmt->fetch(PDO::FETCH_ASSOC);
        
        // Fetch new variation
        $vstmt = $conn->prepare('SELECT * FROM variations WHERE id = ? AND product_id = ?');
        $vstmt->execute([$new_variation_id, $product_id]);
        $variation = $vstmt->fetch(PDO::FETCH_ASSOC);
        
        if ($variation && $product) {
            // Remove old cart item
            unset($_SESSION['cart'][$cart_key]);
            // New cart key
            $new_cart_key = $product_id . '_' . $new_variation_id;
            $_SESSION['cart'][$new_cart_key] = [
                'id' => $product_id,
                'name' => $variation['variation'] ? $product['name'] . ' (' . $variation['variation'] . ')' : $product['name'],
                'price' => $variation['price'] !== null ? $variation['price'] : $product['price'],
                'image_url' => $product['image_url'],
                'quantity' => $quantity,
                'stock' => $variation['stock'],
                'variation_id' => $new_variation_id,
                'variation' => $variation['variation']
            ];
        }
    }
    header('Location: cart.php');
    exit();
}

// Calculate total
$total = 0;
foreach ($_SESSION['cart'] as $item) {
    $total += $item['price'] * $item['quantity'];
}
?>
    <div class="container mt-4">
        <h2>Your Cart</h2>
        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        <?php if(!isset($_SESSION['user_id'])): ?>
            <div class="alert alert-warning">
                You must <a href="login.php">login</a> or <a href="register.php">register</a> before checking out.
            </div>
        <?php endif; ?>
        <?php if(empty($_SESSION['cart'])): ?>
            <div class="alert alert-info">Your cart is empty.</div>
            <a href="products.php" class="btn btn-primary">Continue Shopping</a>
        <?php else: ?>
            <form method="POST">
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Variation</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Subtotal</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($_SESSION['cart'] as $cart_key => $item): ?>
                            <tr>
                                <td>
                                    <?php if($item['image_url']): ?>
                                        <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" style="width: 50px; height: 50px; object-fit: cover;">
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($item['name']); ?>
                                </td>
                                <td>
                                    <?php
                                    // Fetch all variations for this product
                                    $vstmt = $conn->prepare('SELECT * FROM variations WHERE product_id = ?');
                                    $vstmt->execute([$item['id']]);
                                    $all_variations = $vstmt->fetchAll(PDO::FETCH_ASSOC);
                                    if ($all_variations): ?>
                                        <form method="POST" style="display:inline-block;">
                                            <input type="hidden" name="cart_key" value="<?php echo htmlspecialchars($cart_key); ?>">
                                            <input type="hidden" name="quantity" value="<?php echo $item['quantity']; ?>">
                                            <select name="variation_id" class="form-select form-select-sm d-inline w-auto" onchange="this.form.submit()">
                                                <?php foreach($all_variations as $var): ?>
                                                    <option value="<?php echo $var['id']; ?>" <?php if(isset($item['variation_id']) && $item['variation_id'] == $var['id']) echo 'selected'; ?>>
                                                        <?php echo htmlspecialchars($var['variation']); ?><?php if($var['price'] !== null) echo ' (' . format_price($var['price']) . ')'; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <input type="hidden" name="update_variation" value="1">
                                        </form>
                                    <?php else:
                                        echo isset($item['variation']) && $item['variation'] ? htmlspecialchars($item['variation']) : '-';
                                    endif; ?>
                                </td>
                                <td><?php echo format_price($item['price']); ?></td>
                                <td>
                                    <input type="number" name="quantities[<?php echo $item['id']; ?><?php echo isset($item['variation_id']) && $item['variation_id'] ? '_' . $item['variation_id'] : ''; ?>]" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['stock']; ?>" class="form-control form-control-sm quantity-input" style="width: 70px;" data-price="<?php echo $item['price']; ?>" data-id="<?php echo $item['id']; ?>">
                                </td>
                                <td class="subtotal"><?php echo format_price($item['price'] * $item['quantity']); ?></td>
                                <td>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                        <?php if(isset($item['variation_id']) && $item['variation_id']): ?>
                                            <input type="hidden" name="variation_id" value="<?php echo $item['variation_id']; ?>">
                                        <?php endif; ?>
                                        <button type="submit" name="remove_from_cart" class="btn btn-sm btn-danger">Remove</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-end align-items-center mb-3">
                    <button type="submit" name="update_cart" class="btn btn-info me-3">Update Cart</button>
                    <h4 class="mb-0">Total: <?php echo format_price($total); ?></h4>
                </div>
                <div class="d-flex justify-content-end">
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <a href="checkout.php" class="btn btn-success">Proceed to Checkout</a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-success disabled">Login to Checkout</a>
                    <?php endif; ?>
                </div>
            </form>
        <?php endif; ?>
    </div>
<?php include 'includes/footer.php'; ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const quantityInputs = document.querySelectorAll('.quantity-input');
            
            function updateCart() {
                let total = 0;
                quantityInputs.forEach(input => {
                    const price = parseFloat(input.dataset.price);
                    const quantity = parseInt(input.value);
                    const subtotal = price * quantity;
                    const subtotalElement = input.closest('tr').querySelector('.subtotal');
                    subtotalElement.textContent = '₱ ' + subtotal.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
                    total += subtotal;
                });
                document.getElementById('cart-total').textContent = '₱ ' + total.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
            }

            quantityInputs.forEach(input => {
                input.addEventListener('change', function() {
                    const form = this.closest('form');
                    const formData = new FormData(form);
                    formData.append('update_cart', '1');

                    fetch('cart.php', {
                        method: 'POST',
                        body: formData
                    }).then(response => {
                        if (response.ok) {
                            updateCart();
                        }
                    });
                });
            });
        });
    </script>
</body>
</html>