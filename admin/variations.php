<?php
session_start();
require_once '../config/connect.php';
include 'includes/header.php';

// Check if admin is logged in
if(!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Get product ID
if(!isset($_GET['product_id'])) {
    $_SESSION['error'] = 'No product selected.';
    header('Location: products.php');
    exit();
}
$product_id = $_GET['product_id'];

// Fetch product
$stmt = $conn->prepare('SELECT * FROM products WHERE id = ?');
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$product) {
    $_SESSION['error'] = 'Product not found.';
    header('Location: products.php');
    exit();
}

// Handle add/edit/delete
$error = '';
$success = '';

// Add variation
if(isset($_POST['add_variation'])) {
    $variation = $_POST['variation'];
    $stock = $_POST['stock'];
    $price = $_POST['price'] !== '' ? $_POST['price'] : null;
    if($variation) {
        $stmt = $conn->prepare('INSERT INTO variations (product_id, variation, stock, price) VALUES (?, ?, ?, ?)');
        $stmt->execute([$product_id, $variation, $stock, $price]);
        $success = 'Variation added!';
    } else {
        $error = 'Variation is required.';
    }
}

// Edit variation
if(isset($_POST['edit_variation'])) {
    $var_id = $_POST['var_id'];
    $variation = $_POST['variation'];
    $stock = $_POST['stock'];
    $price = $_POST['price'] !== '' ? $_POST['price'] : null;
    if($variation) {
        $stmt = $conn->prepare('UPDATE variations SET variation=?, stock=?, price=? WHERE id=? AND product_id=?');
        $stmt->execute([$variation, $stock, $price, $var_id, $product_id]);
        $success = 'Variation updated!';
    } else {
        $error = 'Variation is required.';
    }
}

// Delete variation
if(isset($_POST['delete_variation'])) {
    $var_id = $_POST['var_id'];
    $stmt = $conn->prepare('DELETE FROM variations WHERE id=? AND product_id=?');
    $stmt->execute([$var_id, $product_id]);
    $success = 'Variation deleted!';
}

// Fetch all variations for this product
$stmt = $conn->prepare('SELECT * FROM variations WHERE product_id = ? ORDER BY id DESC');
$stmt->execute([$product_id]);
$variations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// If editing, fetch the variation
$edit_var = null;
if(isset($_GET['edit_var'])) {
    $stmt = $conn->prepare('SELECT * FROM variations WHERE id = ? AND product_id = ?');
    $stmt->execute([$_GET['edit_var'], $product_id]);
    $edit_var = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Variations - Motoshapi Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2>Manage Variations for: <?php echo htmlspecialchars($product['name']); ?></h2>
        <a href="products.php" class="btn btn-secondary mb-3">Back to Products</a>
        <?php if($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <?php echo $edit_var ? 'Edit Variation' : 'Add Variation'; ?>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <?php if($edit_var): ?>
                                <input type="hidden" name="var_id" value="<?php echo $edit_var['id']; ?>">
                            <?php endif; ?>
                            <div class="mb-3">
                                <label class="form-label">Variation</label>
                                <input type="text" name="variation" class="form-control" value="<?php echo htmlspecialchars($edit_var['variation'] ?? ''); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Stock</label>
                                <input type="number" name="stock" class="form-control" min="0" value="<?php echo htmlspecialchars($edit_var['stock'] ?? 0); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Price (optional)</label>
                                <input type="number" name="price" class="form-control" step="0.01" min="0" value="<?php echo htmlspecialchars($edit_var['price'] ?? ''); ?>">
                            </div>
                            <button type="submit" name="<?php echo $edit_var ? 'edit_variation' : 'add_variation'; ?>" class="btn btn-primary">
                                <?php echo $edit_var ? 'Update Variation' : 'Add Variation'; ?>
                            </button>
                            <?php if($edit_var): ?>
                                <a href="variations.php?product_id=<?php echo $product_id; ?>" class="btn btn-secondary ms-2">Cancel</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">Variations List</div>
                    <div class="card-body p-0">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th>Variation</th>
                                    <th>Stock</th>
                                    <th>Price</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($variations as $var): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($var['variation']); ?></td>
                                    <td><?php echo $var['stock']; ?></td>
                                    <td><?php echo $var['price'] !== null ? '₱ ' . number_format($var['price'], 2) : '-'; ?></td>
                                    <td>
                                        <a href="variations.php?product_id=<?php echo $product_id; ?>&edit_var=<?php echo $var['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Delete this variation?');">
                                            <input type="hidden" name="var_id" value="<?php echo $var['id']; ?>">
                                            <button type="submit" name="delete_variation" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(empty($variations)): ?>
                                <tr><td colspan="4" class="text-center">No variations found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 