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
$category_id = isset($_GET['category_id']) && $_GET['category_id'] !== '' ? intval($_GET['category_id']) : 0;

$query = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE 1=1";
$params = [];
if ($search !== '') {
    // Only show products that match the search (starts with or contains)
    $query .= " AND (p.name LIKE ? OR p.description LIKE ? OR p.name LIKE ? OR p.description LIKE ?)";
    $params[] = "$search%"; // starts with
    $params[] = "$search%"; // starts with
    $params[] = "%$search%"; // contains
    $params[] = "%$search%"; // contains
}
if ($category_id > 0) {
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

    <div class="modern-container modern-section">
        <h2 class="modern-section-title">All <span class="modern-accent-text">Products</span></h2>
        
        <!-- Active Filter Display -->
        <?php if($category_id > 0 || $search !== ''): ?>
            <div style="background: var(--bg-secondary); border: 1px solid var(--border-primary); padding: var(--spacing-md); border-radius: var(--radius-md); margin-bottom: var(--spacing-lg); display: flex; align-items: center; justify-content: space-between;">
                <div style="color: var(--text-secondary);">
                    <i class="bi bi-funnel-fill" style="color: var(--accent-primary); margin-right: 0.5rem;"></i>
                    <strong>Active Filters:</strong>
                    <?php if($search !== ''): ?>
                        <span style="display: inline-block; background: var(--accent-primary); color: #fff; padding: 0.25rem 0.75rem; border-radius: var(--radius-md); margin-left: 0.5rem; font-size: 0.875rem;">Search: "<?php echo htmlspecialchars($search); ?>"</span>
                    <?php endif; ?>
                    <?php if($category_id > 0): ?>
                        <?php 
                        $selected_cat = array_filter($categories, function($cat) use ($category_id) {
                            return $cat['id'] == $category_id;
                        });
                        $selected_cat_name = !empty($selected_cat) ? reset($selected_cat)['name'] : 'Unknown';
                        ?>
                        <span style="display: inline-block; background: var(--accent-primary); color: #fff; padding: 0.25rem 0.75rem; border-radius: var(--radius-md); margin-left: 0.5rem; font-size: 0.875rem;">Category: <?php echo htmlspecialchars($selected_cat_name); ?></span>
                    <?php endif; ?>
                </div>
                <a href="products.php" class="modern-btn modern-btn-secondary" style="padding: 0.5rem 1rem; font-size: 0.875rem;">Clear Filters</a>
            </div>
        <?php endif; ?>
        
        <!-- Search & Filter -->
        <form class="row g-3 mb-4" method="get" action="products.php" style="max-width: 900px; margin: 0 auto var(--spacing-xl);">
            <div class="col-md-5">
                <input type="text" class="form-control" name="search" placeholder="Search products..." value="<?php echo htmlspecialchars($search); ?>" style="background: var(--bg-secondary); border: 1px solid var(--border-primary); color: var(--text-primary); padding: 0.75rem; border-radius: var(--radius-md);">
            </div>
            <div class="col-md-4">
                <select class="form-select" name="category_id" id="categoryFilter" style="background: var(--bg-secondary); border: 1px solid var(--border-primary); color: var(--text-primary); padding: 0.75rem; border-radius: var(--radius-md);">
                    <option value="">All Categories</option>
                    <?php foreach($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php if($category_id == $cat['id']) echo 'selected'; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="modern-btn modern-btn-primary" style="width: 100%; padding: 0.75rem;"><i class="bi bi-funnel me-2"></i>Filter</button>
            </div>
        </form>
        
        <?php if(empty($products)): ?>
            <div style="background: var(--bg-secondary); border: 1px solid var(--border-primary); padding: var(--spacing-lg); border-radius: var(--radius-md); text-align: center; color: var(--text-secondary);">
                <i class="bi bi-inbox" style="font-size: 3rem; display: block; margin-bottom: var(--spacing-md); opacity: 0.5;"></i>
                <p class="mb-0">No products found.</p>
            </div>
        <?php else: ?>
            <div class="modern-grid modern-grid-4">
                <?php foreach($products as $product): ?>
                    <div class="modern-card">
                        <div style="position: relative; overflow: hidden;">
                            <?php if($product['image_url']): ?>
                                <img src="<?php echo htmlspecialchars($product['image_url']); ?>" class="modern-card-img" alt="<?php echo htmlspecialchars($product['name']); ?>">
                            <?php else: ?>
                                <div class="modern-card-img" style="background: var(--bg-tertiary); display: flex; align-items: center; justify-content: center; color: var(--text-muted);">
                                    <i class="bi bi-image" style="font-size: 4rem;"></i>
                                </div>
                            <?php endif; ?>
                            <span class="modern-card-badge" style="position: absolute; top: 1rem; right: 1rem;"><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></span>
                        </div>
                        <div class="modern-card-body">
                            <h3 class="modern-card-title"><a href="product.php?id=<?php echo $product['id']; ?>"><?php echo htmlspecialchars($product['name']); ?></a></h3>
                            <p class="modern-card-price"><?php echo format_price($product['price']); ?></p>
                            <p style="color: <?php echo $product['stock'] > 0 ? 'var(--success)' : 'var(--danger)'; ?>; font-size: 0.875rem; font-weight: 600; margin-bottom: var(--spacing-md);">
                                <i class="bi <?php echo $product['stock'] > 0 ? 'bi-check-circle' : 'bi-x-circle'; ?>"></i>
                                <?php echo $product['stock'] > 0 ? 'In Stock: ' . $product['stock'] : 'Out of Stock'; ?>
                            </p>
                            <a href="product.php?id=<?php echo $product['id']; ?>" class="modern-btn modern-btn-secondary" style="width: 100%; margin-bottom: var(--spacing-sm);">View Details</a>
                            <?php if($product['stock'] > 0): ?>
                            <form method="POST" action="cart.php" class="d-flex gap-2">
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                <input type="number" name="quantity" value="1" min="1" max="<?php echo $product['stock']; ?>" class="form-control" style="width: 70px; background: var(--bg-primary); border: 1px solid var(--border-primary); color: var(--text-primary); border-radius: var(--radius-md); padding: 0.5rem;">
                                <button type="submit" name="add_to_cart" class="modern-btn modern-btn-primary" style="flex: 1;"><i class="bi bi-cart-plus"></i></button>
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