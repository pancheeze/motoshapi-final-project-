<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../config/database.php';
require_once '../config/currency.php';

// Check if admin is logged in
if(!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Check if product ID is provided
if(!isset($_GET['id'])) {
    header("Location: products.php");
    exit();
}

$product_id = $_GET['id'];

try {
    // Get product details with category information
    $stmt = $conn->prepare("SELECT p.*, c.name as category_name 
                           FROM products p 
                           LEFT JOIN categories c ON p.category_id = c.id 
                           WHERE p.id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    // If product not found, redirect to products page
    if(!$product) {
        $_SESSION['error'] = "Product not found.";
        header("Location: products.php");
        exit();
    }
} catch(PDOException $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
    header("Location: products.php");
    exit();
}

include 'includes/header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Details - Motoshapi</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Product Details</h2>
                    <div>
                        <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-primary">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <a href="products.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Back to Products
                        </a>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <?php if($product['image_url']): ?>
                                    <img src="../<?php echo htmlspecialchars($product['image_url']); ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                         class="img-fluid rounded">
                                <?php else: ?>
                                    <div class="bg-secondary text-white d-flex align-items-center justify-content-center rounded" 
                                         style="height: 300px;">
                                        <i class="bi bi-image" style="font-size: 4rem;"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-8">
                                <h3 class="mb-3"><?php echo htmlspecialchars($product['name']); ?></h3>
                                
                                <div class="mb-4">
                                    <h5>Price</h5>
                                    <p class="h4 text-primary"><?php echo format_price($product['price']); ?></p>
                                </div>

                                <div class="mb-4">
                                    <h5>Stock</h5>
                                    <p class="h4">
                                        <span class="badge bg-<?php echo $product['stock'] < 10 ? 'danger' : 'success'; ?>">
                                            <?php echo $product['stock']; ?> units
                                        </span>
                                    </p>
                                </div>

                                <div class="mb-4">
                                    <h5>Category</h5>
                                    <p><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></p>
                                </div>

                                <div class="mb-4">
                                    <h5>Description</h5>
                                    <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                                </div>

                                <div class="mb-4">
                                    <h5>Product ID</h5>
                                    <p><?php echo $product['id']; ?></p>
                                </div>

                                <div class="mb-4">
                                    <h5>Added On</h5>
                                    <p><?php echo date('F j, Y g:i A', strtotime($product['created_at'])); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 