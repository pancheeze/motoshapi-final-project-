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
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="row g-0">
                        <div class="col-md-5 d-flex align-items-center justify-content-center bg-light">
                            <?php if ($product['image_url']): ?>
                                <img src="<?php echo htmlspecialchars($product['image_url']); ?>" class="img-fluid p-3" alt="<?php echo htmlspecialchars($product['name']); ?>">
                            <?php else: ?>
                                <div class="bg-secondary text-white d-flex align-items-center justify-content-center" style="width:100%;height:300px;">
                                    <span>No Image</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-7">
                            <div class="card-body">
                                <h2 class="card-title mb-3"><?php echo htmlspecialchars($product['name']); ?></h2>
                                <h4 class="text-primary mb-3"><?php echo format_price($product['price']); ?></h4>
                                <p class="mb-2"><strong>Category:</strong> <?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></p>
                                <p class="mb-2"><strong>Stock:</strong> <?php echo $product['stock']; ?></p>
                                <p class="card-text mt-3"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                                <div class="d-flex align-items-center gap-2 mt-3 flex-wrap">
                                    <a href="index.php" class="btn btn-secondary" style="height:48px; display:flex; align-items:center; white-space:nowrap;">Back to Home</a>
                                    <form id="product-action-form" class="d-flex align-items-center gap-2" method="POST" action="cart.php" style="margin-bottom:0;">
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                        <?php if (!empty($variations)): ?>
                                            <select name="variation_id" id="variation_id" class="form-control" style="width:auto; height:48px;" required>
                                                <option value="">Select Variation</option>
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
                                        <?php endif; ?>
                                        <input type="number" name="quantity" id="quantity" class="form-control" value="1" min="1" max="<?php echo $product['stock']; ?>" style="width:80px; height:48px;" required>
                                        <button type="submit" name="add_to_cart" class="btn btn-warning" style="height:48px; display:flex; align-items:center;">Add to Cart</button>
                                        <button type="button" id="buyNowBtn" class="btn btn-success" style="height:48px; display:flex; align-items:center;">Buy Now</button>
                                    </form>
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