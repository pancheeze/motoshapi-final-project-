<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../config/database.php';
include 'includes/header.php';

// Check if admin is logged in
if(!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Handle featured status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['featured_ids'])) {
    // First, set all products to not featured
    $conn->query("UPDATE products SET featured = 0");
    // Then, set selected products as featured
    $featured_ids = $_POST['featured_ids'];
    if (!empty($featured_ids)) {
        $in = str_repeat('?,', count($featured_ids) - 1) . '?';
        $stmt = $conn->prepare("UPDATE products SET featured = 1 WHERE id IN ($in)");
        $stmt->execute($featured_ids);
    }
    $_SESSION['success'] = 'Featured products updated!';
    header('Location: featured_products.php');
    exit();
}

// Fetch all products
$stmt = $conn->query("SELECT * FROM products WHERE stock > 0 ORDER BY created_at DESC");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Featured Products - Motoshapi Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2>Edit Featured Products</h2>
        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <form method="POST">
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>Featured</th>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Price</th>
                            <th>Stock</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($products as $product): ?>
                        <tr>
                            <td class="text-center">
                                <input type="checkbox" name="featured_ids[]" value="<?php echo $product['id']; ?>" <?php if(!empty($product['featured'])) echo 'checked'; ?>>
                            </td>
                            <td>
                                <?php if($product['image_url']): ?>
                                    <img src="../<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" style="width: 50px; height: 50px; object-fit: cover;">
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                            <td>₱ <?php echo number_format($product['price'], 2); ?></td>
                            <td><?php echo $product['stock']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <button type="submit" class="btn btn-primary">Save Featured Products</button>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 