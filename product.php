<?php
session_start();
require_once 'config/database.php';
require_once 'config/currency.php';

$product_id_php = isset($_GET['id']) ? $_GET['id'] : null;
$product_php = null;
$title = 'Product Details - Motoshapi';
$activePage = 'products';

if ($product_id_php) {
    // Fetch product details for the title
    $stmt_title = $conn->prepare('SELECT name FROM products WHERE id = ?');
    $stmt_title->execute([$product_id_php]);
    $product_php = $stmt_title->fetch(PDO::FETCH_ASSOC);
    if ($product_php) {
        $title = htmlspecialchars($product_php['name']) . ' - Motoshapi';
    }
}

include 'includes/header.php';

// Get product ID from URL
if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit();
}
$product_id = $_GET['id'];

// Fetch product details
$stmt = $conn->prepare('SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = ?');
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header('Location: index.php');
    exit();
}

// Fetch variations for this product
$var_stmt = $conn->prepare('SELECT * FROM variations WHERE product_id = ? ORDER BY id ASC');
$var_stmt->execute([$product_id]);
$variations = $var_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
    <div class="modern-container modern-section">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="modern-card">
                    <div class="row g-0">
                        <div class="col-md-5">
                            <div style="padding: var(--spacing-xl); background: var(--bg-primary); display: flex; align-items: center; justify-content: center; min-height: 400px; border-radius: var(--radius-lg) 0 0 var(--radius-lg);">
                                <?php if ($product['image_url']): ?>
                                    <img src="<?php echo htmlspecialchars($product['image_url']); ?>" class="img-fluid" alt="<?php echo htmlspecialchars($product['name']); ?>" style="max-height: 400px; object-fit: contain;">
                                <?php else: ?>
                                    <div style="display: flex; align-items: center; justify-content: center; width: 100%; height: 300px; color: var(--text-muted);">
                                        <i class="bi bi-image" style="font-size: 5rem;"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-7">
                            <div style="padding: var(--spacing-2xl);">
                                <h1 style="font-size: 2rem; font-weight: 700; color: var(--text-primary); margin-bottom: var(--spacing-md);"><?php echo htmlspecialchars($product['name']); ?></h1>
                                <h2 style="font-size: 2.5rem; font-weight: 700; color: var(--accent-primary); margin-bottom: var(--spacing-xl);"><?php echo format_price($product['price']); ?></h2>
                                
                                <div style="display: flex; gap: var(--spacing-lg); margin-bottom: var(--spacing-lg); padding-bottom: var(--spacing-lg); border-bottom: 1px solid var(--border-primary);">
                                    <div>
                                        <p style="color: var(--text-muted); font-size: 0.875rem; margin-bottom: 0.25rem;">Category</p>
                                        <p style="color: var(--text-primary); font-weight: 600; margin: 0;"><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></p>
                                    </div>
                                    <div>
                                        <p style="color: var(--text-muted); font-size: 0.875rem; margin-bottom: 0.25rem;">Stock</p>
                                        <p style="color: <?php echo $product['stock'] > 0 ? 'var(--success)' : 'var(--danger)'; ?>; font-weight: 600; margin: 0;"><?php echo $product['stock'] > 0 ? $product['stock'] . ' Available' : 'Out of Stock'; ?></p>
                                    </div>
                                </div>
                                
                                <div style="margin-bottom: var(--spacing-xl);">
                                    <h3 style="font-size: 1rem; font-weight: 600; color: var(--text-primary); margin-bottom: var(--spacing-md);">Description</h3>
                                    <p style="color: var(--text-secondary); line-height: 1.7;"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                                </div>
                                
                                <form id="product-action-form" method="POST" action="cart.php" style="margin-bottom: var(--spacing-lg);">
                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                    <?php if (!empty($variations)): ?>
                                        <div style="margin-bottom: var(--spacing-md);">
                                            <label style="display: block; margin-bottom: var(--spacing-sm); font-weight: 600; color: var(--text-primary);">Select Variation</label>
                                            <select name="variation_id" id="variation_id" class="form-control" required style="background: var(--bg-primary); border: 1px solid var(--border-primary); color: var(--text-primary); padding: 0.75rem; border-radius: var(--radius-md); width: 100%;">
                                                <option value="">Choose an option</option>
                                                <?php foreach($variations as $var): ?>
                                                    <option value="<?php echo $var['id']; ?>">
                                                        <?php echo htmlspecialchars($var['variation']); ?>
                                                        <?php if($var['price'] !== null): ?>
                                                            (<?php echo format_price($var['price']); ?>)
                                                        <?php endif; ?>
                                                        - Stock: <?php echo $var['stock']; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    <?php endif; ?>
                                    <div style="margin-bottom: var(--spacing-lg);">
                                        <label style="display: block; margin-bottom: var(--spacing-sm); font-weight: 600; color: var(--text-primary);">Quantity</label>
                                        <input type="number" name="quantity" id="quantity" class="form-control" value="1" min="1" max="<?php echo $product['stock']; ?>" required style="background: var(--bg-primary); border: 1px solid var(--border-primary); color: var(--text-primary); padding: 0.75rem; border-radius: var(--radius-md); width: 120px;">
                                    </div>
                                    <div style="display: flex; gap: var(--spacing-md); flex-wrap: wrap;">
                                        <button type="submit" name="add_to_cart" class="modern-btn modern-btn-secondary" style="flex: 1; min-width: 150px;"><i class="bi bi-cart-plus me-2"></i>Add to Cart</button>
                                        <button type="button" id="buyNowBtn" class="modern-btn modern-btn-primary" style="flex: 1; min-width: 150px;">Buy Now</button>
                                    </div>
                                </form>
                                
                                <a href="products.php" style="color: var(--text-secondary); text-decoration: none; font-size: 0.875rem; display: inline-flex; align-items: center; gap: 0.5rem; transition: color var(--transition-fast);" onmouseover="this.style.color='var(--accent-primary)'" onmouseout="this.style.color='var(--text-secondary)'"><i class="bi bi-arrow-left"></i> Back to Products</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
                                <script>
                                document.getElementById('buyNowBtn').addEventListener('click', function() {
                                    var form = document.getElementById('product-action-form');
                                    var variation = form.variation_id ? form.variation_id.value : '';
                                    var quantity = form.quantity.value;
                                    var url = 'buy_now.php?product_id=<?php echo $product['id']; ?>';
                                    if (variation) url += '&variation_id=' + encodeURIComponent(variation);
                                    url += '&quantity=' + encodeURIComponent(quantity);
                                    window.location.href = url;
                                });
                                </script>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Buy Now Modal -->
        <div class="modal fade" id="buyNowModal" tabindex="-1" aria-labelledby="buyNowModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="cart.php">
                        <div class="modal-header">
                            <h5 class="modal-title" id="buyNowModalLabel">Buy Now - <?php echo htmlspecialchars($product['name']); ?></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                            <?php if (!empty($variations)): ?>
                                <div class="mb-3">
                                    <label for="variation_id" class="form-label">Choose Variation</label>
                                    <select class="form-select" id="variation_id" name="variation_id" required>
                                        <option value="">Select a variation</option>
                                        <?php foreach($variations as $var): ?>
                                            <option value="<?php echo $var['id']; ?>">
                                                <?php echo htmlspecialchars($var['variation']); ?>
                                                <?php if($var['price'] !== null): ?>
                                                    (<?php echo format_price($var['price']); ?>)
                                                <?php endif; ?>
                                                - Stock: <?php echo $var['stock']; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            <?php endif; ?>
                            <div class="mb-3">
                                <label for="quantity" class="form-label">Quantity</label>
                                <input type="number" class="form-control" id="quantity" name="quantity" value="1" min="1" max="<?php echo $product['stock']; ?>" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="add_to_cart" class="btn btn-success">Add to Cart</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php include 'includes/footer.php'; ?>