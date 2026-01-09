<?php
session_start();
require_once 'config/database.php';
require_once 'config/currency.php';

$title = 'Products - Motoshapi';
$activePage = 'products';
include 'includes/header.php';

// Fetch categories for filter
$cat_stmt = $conn->query("SELECT * FROM categories ORDER BY name");
$categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle filter and search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : '';

$query = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE 1";
$params = [];
if ($search !== '') {
    // Only show products that match the search (starts with or contains)
    $query .= " AND (p.name LIKE ? OR p.description LIKE ? OR p.name LIKE ? OR p.description LIKE ?)";
    $params[] = "$search%"; // starts with
    $params[] = "$search%"; // starts with
    $params[] = "%$search%"; // contains
    $params[] = "%$search%"; // contains
}
if ($category_id) {
    $query .= " AND p.category_id = ?";
    $params[] = $category_id;
}
$query .= " ORDER BY ";
if ($search !== '') {
    $query .= "(CASE WHEN p.name LIKE ? OR p.description LIKE ? THEN 0 ELSE 1 END), ";
    $params[] = "$search%";
    $params[] = "$search%";
}
$query .= "p.created_at DESC";
$stmt = $conn->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">All Products</h2>
        </div>
        <form class="row g-3 mb-4" method="get">
            <div class="col-md-4">
                <input type="text" class="form-control" name="search" placeholder="Search products..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-4">
                <select class="form-select" name="category_id">
                    <option value="">All Categories</option>
                    <?php foreach($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php if($category_id == $cat['id']) echo 'selected'; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary">Filter</button>
            </div>
        </form>
        <?php if(empty($products)): ?>
            <div class="alert alert-info">No products found.</div>
        <?php else: ?>
            <div class="product-grid">
                <?php foreach($products as $product): ?>
                    <div class="card product-card">
                        <?php if($product['image_url']): ?>
                            <img src="<?php echo htmlspecialchars($product['image_url']); ?>" class="card-img-top product-img-fit" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <?php else: ?>
                            <div class="bg-secondary text-white d-flex align-items-center justify-content-center" style="height:200px;">
                                <span>No Image</span>
                            </div>
                        <?php endif; ?>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                            <span class="badge category-badge mb-2"><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></span>
                            <p class="price-tag mb-2"><?php echo format_price($product['price']); ?></p>
                            <p class="stock-status <?php echo $product['stock'] > 0 ? 'in-stock' : 'out-of-stock'; ?>">
                                <?php echo $product['stock'] > 0 ? 'In Stock: ' . $product['stock'] : 'Out of Stock'; ?>
                            </p>
                            <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-outline-primary">View Details</a>
                            <?php if($product['stock'] > 0): ?>
                            <form method="POST" action="cart.php" class="mt-2 d-flex align-items-center">
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                <input type="number" name="quantity" value="1" min="1" max="<?php echo $product['stock']; ?>" class="form-control form-control-sm me-2" style="width: 70px;">
                                <button type="submit" name="add_to_cart" class="btn btn-sm btn-success">Add to Cart</button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- About Us Modal -->
    <div class="modal fade" id="aboutUsModal" tabindex="-1" aria-labelledby="aboutUsModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h2 class="modal-title w-100 text-center" id="aboutUsModalLabel">About Us</h2>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="row justify-content-center gx-5">
              <?php
              $about_stmt = $conn->query("SELECT * FROM about_us ORDER BY id ASC");
              while($member = $about_stmt->fetch(PDO::FETCH_ASSOC)):
              ?>
              <div class="col-md-4 col-lg-3 mb-4 d-flex justify-content-center">
                <div class="card h-100 text-center shadow-sm" style="min-width:280px; padding:25px; margin: 0 15px; border-radius: 15px;">
                  <?php if($member['photo_url']): ?>
                    <img src="<?php echo htmlspecialchars($member['photo_url']); ?>" class="card-img-top mx-auto d-block rounded-circle shadow-sm" alt="<?php echo htmlspecialchars($member['name']); ?>" style="width:200px; height:200px; object-fit:cover; margin-bottom:20px;">
                  <?php else: ?>
                    <div class="bg-secondary text-white d-flex align-items-center justify-content-center mx-auto rounded-circle" style="width:200px; height:200px; margin-bottom:20px;">No Photo</div>
                  <?php endif; ?>
                  <div class="card-body px-3">
                    <h4 class="card-title fw-bold mb-3"><?php echo htmlspecialchars($member['name']); ?></h4>
                    <p class="card-text text-muted mb-2" style="font-size: 0.95rem; line-height: 1.6;"><?php echo htmlspecialchars($member['description']); ?></p>
                  </div>
                </div>
              </div>
              <?php endwhile; ?>
            </div>
          </div>
        </div>
      </div>
    </div>

    <script>
      document.addEventListener('DOMContentLoaded', function() {
        var aboutUsBtn = document.getElementById('aboutUsBtn');
        var aboutUsModal = new bootstrap.Modal(document.getElementById('aboutUsModal'));
        if (aboutUsBtn) {
            aboutUsBtn.addEventListener('click', function() {
              aboutUsModal.show();
            });
        }
      });
    </script>
<?php include 'includes/footer.php'; ?> 