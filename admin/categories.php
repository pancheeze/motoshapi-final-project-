<?php
session_start();
require_once '../config/connect.php';

// Check if admin is logged in
if(!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$error = '';
$success = '';

// Handle category deletion
if(isset($_POST['delete_category'])) {
    $category_id = $_POST['category_id'];
    
    // Check if category has products
    $stmt = $conn->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
    $stmt->execute([$category_id]);
    $product_count = $stmt->fetchColumn();
    
    if($product_count > 0) {
        $error = "Cannot delete category with associated products.";
    } else {
        $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$category_id]);
        $success = "Category deleted successfully!";
    }
}

// Handle category addition/editing
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['name'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $category_id = $_POST['category_id'] ?? null;
    
    if($category_id) {
        // Update existing category
        $stmt = $conn->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
        $stmt->execute([$name, $description, $category_id]);
        $success = "Category updated successfully!";
    } else {
        // Add new category
        $stmt = $conn->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
        $stmt->execute([$name, $description]);
        $success = "Category added successfully!";
    }
}

// Get all categories
$stmt = $conn->query("SELECT c.*, COUNT(p.id) as product_count 
                     FROM categories c 
                     LEFT JOIN products p ON c.id = p.category_id 
                     GROUP BY c.id 
                     ORDER BY c.name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

$title = 'Category Management - MOTOSHAPI';
$activeAdminPage = 'categories';
$mainClass = 'flex-grow-1 py-4 container-xxl';
include 'includes/header.php';
?>
    <div class="row g-4">
        <div class="col-12 col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header bg-white border-0">
                    <h3 class="card-title mb-0">Add / Edit Category</h3>
                    <small class="text-muted">Manage catalog groupings for easier browsing.</small>
                </div>
                <div class="card-body">
                    <?php if($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    <?php if($success): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $success; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" id="categoryForm" class="row g-3">
                        <input type="hidden" name="category_id" id="category_id">
                        <div class="col-12">
                            <label for="name" class="form-label">Category Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="col-12">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3" placeholder="Optional details"></textarea>
                        </div>
                        <div class="col-12 d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Save Category</button>
                            <button type="button" class="btn btn-outline-secondary" onclick="resetForm()">Clear Form</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-8">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">Categories</h3>
                    <span class="badge bg-primary-subtle text-primary"><?php echo count($categories); ?> total</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Products</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($categories as $category): ?>
                                <tr>
                                    <td class="fw-semibold"><?php echo htmlspecialchars($category['name']); ?></td>
                                    <td><?php echo htmlspecialchars($category['description']); ?></td>
                                    <td><span class="badge bg-secondary-subtle text-secondary"><?php echo $category['product_count']; ?></span></td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button type="button" class="btn btn-outline-primary" onclick="editCategory(<?php echo htmlspecialchars(json_encode($category)); ?>)" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <?php if($category['product_count'] == 0): ?>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this category?');">
                                                <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                                <button type="submit" name="delete_category" class="btn btn-outline-danger" title="Delete">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function editCategory(category) {
            document.getElementById('category_id').value = category.id;
            document.getElementById('name').value = category.name;
            document.getElementById('description').value = category.description || '';
        }

        function resetForm() {
            document.getElementById('categoryForm').reset();
            document.getElementById('category_id').value = '';
        }
    </script>

<?php include 'includes/footer.php'; ?>