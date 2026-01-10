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
    <div class="modern-container modern-section">
        <h2 class="modern-section-title">Your <span class="modern-accent-text">Cart</span></h2>
        <?php if(isset($_SESSION['error'])): ?>
            <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid var(--danger); color: var(--danger); padding: var(--spacing-md); border-radius: var(--radius-md); margin-bottom: var(--spacing-lg);">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        <?php if(isset($_SESSION['success'])): ?>
            <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid var(--success); color: var(--success); padding: var(--spacing-md); border-radius: var(--radius-md); margin-bottom: var(--spacing-lg);">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        <?php if(!isset($_SESSION['user_id'])): ?>
            <div style="background: rgba(251, 191, 36, 0.1); border: 1px solid #f59e0b; color: #fbbf24; padding: var(--spacing-md); border-radius: var(--radius-md); margin-bottom: var(--spacing-lg);">
                You must <a href="login.php" style="color: var(--accent-primary); text-decoration: underline;">login</a> or <a href="register.php" style="color: var(--accent-primary); text-decoration: underline;">register</a> before checking out.
            </div>
        <?php endif; ?>
        <?php if(empty($_SESSION['cart'])): ?>
            <div class="modern-card" style="text-align: center; padding: var(--spacing-2xl);">
                <i class="bi bi-cart-x" style="font-size: 4rem; color: var(--text-muted); display: block; margin-bottom: var(--spacing-lg);"></i>
                <p style="color: var(--text-secondary); font-size: 1.125rem; margin-bottom: var(--spacing-xl);">Your cart is empty.</p>
                <a href="products.php" class="modern-btn modern-btn-primary">Continue Shopping</a>
            </div>
        <?php else: ?>
            <form method="POST">
                <div class="table-responsive modern-card" style="overflow-x: auto;">
                    <table class="table align-middle" style="width: 100%; border-collapse: collapse; background: transparent;">
                        <thead style="background: var(--bg-primary); border-bottom: 2px solid var(--border-primary);">
                            <tr>
                                <th style="padding: var(--spacing-md); color: var(--text-primary); font-weight: 600;">Product</th>
                                <th style="padding: var(--spacing-md); color: var(--text-primary); font-weight: 600;">Variation</th>
                                <th style="padding: var(--spacing-md); color: var(--text-primary); font-weight: 600;">Price</th>
                                <th style="padding: var(--spacing-md); color: var(--text-primary); font-weight: 600;">Quantity</th>
                                <th style="padding: var(--spacing-md); color: var(--text-primary); font-weight: 600;">Subtotal</th>
                                <th style="padding: var(--spacing-md); color: var(--text-primary); font-weight: 600;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($_SESSION['cart'] as $cart_key => $item): ?>
                            <tr style="border-bottom: 1px solid var(--border-primary);">
                                <td style="padding: var(--spacing-md);">
                                    <div style="display: flex; align-items: center; gap: var(--spacing-md);">
                                        <?php if($item['image_url']): ?>
                                            <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" style="width: 60px; height: 60px; object-fit: cover; border-radius: var(--radius-md); border: 1px solid var(--border-primary);">
                                        <?php endif; ?>
                                        <span style="color: var(--text-primary); font-weight: 500;"><?php echo htmlspecialchars($item['name']); ?></span>
                                    </div>
                                </td>
                                <td style="padding: var(--spacing-md);">
                                    <?php
                                    // Fetch all variations for this product
                                    $vstmt = $conn->prepare('SELECT * FROM variations WHERE product_id = ?');
                                    $vstmt->execute([$item['id']]);
                                    $all_variations = $vstmt->fetchAll(PDO::FETCH_ASSOC);
                                    if ($all_variations): ?>
                                        <form method="POST" style="display:inline-block;">
                                            <input type="hidden" name="cart_key" value="<?php echo htmlspecialchars($cart_key); ?>">
                                            <input type="hidden" name="quantity" value="<?php echo $item['quantity']; ?>">
                                            <select name="variation_id" class="form-select form-select-sm d-inline w-auto" onchange="this.form.submit()" style="background: var(--bg-primary); border: 1px solid var(--border-primary); color: var(--text-primary); border-radius: var(--radius-md); padding: 0.5rem;">
                                                <?php foreach($all_variations as $var): ?>
                                                    <option value="<?php echo $var['id']; ?>" <?php if(isset($item['variation_id']) && $item['variation_id'] == $var['id']) echo 'selected'; ?>>
                                                        <?php echo htmlspecialchars($var['variation']); ?><?php if($var['price'] !== null) echo ' (' . format_price($var['price']) . ')'; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <input type="hidden" name="update_variation" value="1">
                                        </form>
                                    <?php else:
                                        echo '<span style="color: var(--text-secondary);">' . (isset($item['variation']) && $item['variation'] ? htmlspecialchars($item['variation']) : '-') . '</span>';
                                    endif; ?>
                                </td>
                                <td style="padding: var(--spacing-md); color: var(--accent-primary); font-weight: 600;"><?php echo format_price($item['price']); ?></td>
                                <td style="padding: var(--spacing-md);">
                                    <input type="number" name="quantities[<?php echo $item['id']; ?><?php echo isset($item['variation_id']) && $item['variation_id'] ? '_' . $item['variation_id'] : ''; ?>]" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['stock']; ?>" class="form-control form-control-sm quantity-input" style="width: 80px; background: var(--bg-primary); border: 1px solid var(--border-primary); color: var(--text-primary); border-radius: var(--radius-md); padding: 0.5rem;" data-price="<?php echo $item['price']; ?>" data-id="<?php echo $item['id']; ?>">
                                </td>
                                <td class="subtotal" style="padding: var(--spacing-md); color: var(--text-primary); font-weight: 600;"><?php echo format_price($item['price'] * $item['quantity']); ?></td>
                                <td style="padding: var(--spacing-md);">
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                        <?php if(isset($item['variation_id']) && $item['variation_id']): ?>
                                            <input type="hidden" name="variation_id" value="<?php echo $item['variation_id']; ?>">
                                        <?php endif; ?>
                                        <button type="submit" name="remove_from_cart" class="modern-btn" style="background: var(--danger); color: #fff; padding: 0.5rem 1rem; border: none; cursor: pointer; border-radius: var(--radius-md); transition: var(--transition-fast);" onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'">Remove</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-end align-items-center mb-3" style="gap: var(--spacing-lg); margin-top: var(--spacing-xl); padding: var(--spacing-lg); background: var(--bg-primary); border-radius: var(--radius-md);">
                    <button type="submit" name="update_cart" class="modern-btn modern-btn-secondary">Update Cart</button>
                    <h4 class="mb-0" style="color: var(--text-primary); font-size: 1.5rem;">Total: <span style="color: var(--accent-primary);" id="cart-total"><?php echo format_price($total); ?></span></h4>
                </div>
                <div class="d-flex justify-content-end" style="gap: var(--spacing-md);">
                    <a href="products.php" class="modern-btn modern-btn-secondary">Continue Shopping</a>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <a href="checkout.php" class="modern-btn modern-btn-primary modern-btn-lg">Proceed to Checkout</a>
                    <?php else: ?>
                        <button class="modern-btn modern-btn-primary modern-btn-lg" disabled style="opacity: 0.5; cursor: not-allowed;">Login to Checkout</button>
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