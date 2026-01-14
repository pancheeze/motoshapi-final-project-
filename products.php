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
        <h2 class="modern-section-title">All Products</h2>
        
        <!-- Active Filter Display -->
        <?php if($category_id > 0 || $search !== ''): ?>
            <div class="sp-filterbar">
                <div class="sp-filterbar-left">
                    <i class="bi bi-funnel-fill sp-filterbar-icon"></i>
                    <strong>Active Filters:</strong>
                    <?php if($search !== ''): ?>
                        <span class="sp-chip">Search: "<?php echo htmlspecialchars($search); ?>"</span>
                    <?php endif; ?>
                    <?php if($category_id > 0): ?>
                        <?php 
                        $selected_cat = array_filter($categories, function($cat) use ($category_id) {
                            return $cat['id'] == $category_id;
                        });
                        $selected_cat_name = !empty($selected_cat) ? reset($selected_cat)['name'] : 'Unknown';
                        ?>
                        <span class="sp-chip">Category: <?php echo htmlspecialchars($selected_cat_name); ?></span>
                    <?php endif; ?>
                </div>
                <a href="products.php" class="modern-btn modern-btn-secondary sp-btn-sm">Clear Filters</a>
            </div>
        <?php endif; ?>
        
        <!-- Search & Filter -->
        <form class="row g-3 mb-4 sp-filter-form" method="get" action="products.php">
            <div class="col-md-5">
                <input type="text" class="form-control sp-input" name="search" placeholder="Search products..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-4">
                <select class="form-select sp-input" name="category_id" id="categoryFilter">
                    <option value="">All Categories</option>
                    <?php foreach($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php if($category_id == $cat['id']) echo 'selected'; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="modern-btn modern-btn-primary sp-btn-block"><i class="bi bi-funnel me-2"></i>Filter</button>
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
                            <form class="add-to-cart-form d-flex gap-2" data-product-id="<?php echo $product['id']; ?>" data-product-name="<?php echo htmlspecialchars($product['name']); ?>">
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                <input type="number" name="quantity" value="1" min="1" max="<?php echo $product['stock']; ?>" class="form-control" style="width: 70px; background: var(--bg-primary); border: 1px solid var(--border-primary); color: var(--text-primary); border-radius: var(--radius-md); padding: 0.5rem;">
                                <button type="submit" class="modern-btn modern-btn-primary" style="flex: 1;"><i class="bi bi-cart-plus"></i></button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Cart Notification Popup -->
    <div id="cartNotification" class="cart-notification">
        <div class="cart-notification-content">
            <i class="bi bi-check-circle-fill" style="font-size: 1.5rem; color: var(--success);"></i>
            <div class="cart-notification-text">
                <strong id="cartNotificationTitle">Added to Cart!</strong>
                <p id="cartNotificationMessage">Product added successfully</p>
            </div>
        </div>
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
                        <div class="row justify-content-center g-4">
              <?php
              $about_stmt = $conn->query("SELECT * FROM about_us ORDER BY id ASC");
              while($member = $about_stmt->fetch(PDO::FETCH_ASSOC)):
              ?>
                            <div class="col-12 col-sm-6 col-lg-4 col-xl-3 d-flex">
                                <div class="card sp-team-card w-100 h-100 text-center shadow-sm">
                  <?php if($member['photo_url']): ?>
                                        <img src="<?php echo htmlspecialchars($member['photo_url']); ?>" class="sp-team-avatar mx-auto d-block rounded-circle shadow-sm" alt="<?php echo htmlspecialchars($member['name']); ?>">
                  <?php else: ?>
                                        <div class="sp-team-avatar sp-team-avatar--empty bg-secondary text-white d-flex align-items-center justify-content-center mx-auto rounded-circle">No Photo</div>
                  <?php endif; ?>
                  <div class="card-body px-3">
                    <h4 class="card-title fw-bold mb-3"><?php echo htmlspecialchars($member['name']); ?></h4>
                                        <?php if (!empty($member['description'])): ?>
                                            <div class="sp-team-role"><?php echo htmlspecialchars($member['description']); ?></div>
                                        <?php endif; ?>
                  </div>
                </div>
              </div>
              <?php endwhile; ?>
            </div>
          </div>
        </div>
      </div>
    </div>

    <style>
        .cart-notification {
            position: fixed;
            top: 80px;
            right: 20px;
            background: var(--bg-secondary);
            border: 2px solid var(--success);
            border-radius: var(--radius-lg);
            padding: 1rem 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            z-index: 9999;
            opacity: 0;
            transform: translateX(400px);
            transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            pointer-events: none;
            max-width: 350px;
        }

        .cart-notification.show {
            opacity: 1;
            transform: translateX(0);
            pointer-events: auto;
        }

        .cart-notification-content {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .cart-notification-text {
            flex: 1;
        }

        .cart-notification-text strong {
            display: block;
            color: var(--text-primary);
            font-size: 1rem;
            margin-bottom: 0.25rem;
        }

        .cart-notification-text p {
            color: var(--text-secondary);
            font-size: 0.875rem;
            margin: 0;
        }

        .cart-notification.error {
            border-color: var(--danger);
        }

        .cart-notification.error .bi-check-circle-fill {
            color: var(--danger);
        }

        .cart-notification.error .bi-check-circle-fill::before {
            content: "\f623"; /* bi-x-circle-fill */
        }

        @keyframes cartBounce {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.2); }
        }

        .cart-bounce {
            animation: cartBounce 0.3s ease-in-out;
        }
    </style>

    <script>
      // AJAX Add to Cart functionality
      document.addEventListener('DOMContentLoaded', function() {
        const cartForms = document.querySelectorAll('.add-to-cart-form');
        const cartNotification = document.getElementById('cartNotification');
        const cartNotificationTitle = document.getElementById('cartNotificationTitle');
        const cartNotificationMessage = document.getElementById('cartNotificationMessage');
        const cartCountElement = document.getElementById('cart-count-badge');

        cartForms.forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(form);
                const productName = form.dataset.productName;
                const submitButton = form.querySelector('button[type="submit"]');
                const originalContent = submitButton.innerHTML;

                // Disable button and show loading
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="bi bi-hourglass-split"></i>';

                fetch('ajax_add_to_cart.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    // Re-enable button
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalContent;

                    if (data.success) {
                        // Update cart count with animation
                        if (cartCountElement) {
                            cartCountElement.textContent = data.cart_count;
                            cartCountElement.style.display = data.cart_count > 0 ? '' : 'none';
                            cartCountElement.classList.add('cart-bounce');
                            setTimeout(() => {
                                cartCountElement.classList.remove('cart-bounce');
                            }, 300);
                        }

                        // Show success notification
                        cartNotification.classList.remove('error');
                        cartNotificationTitle.textContent = 'Added to Cart!';
                        cartNotificationMessage.textContent = productName;
                        showNotification();
                    } else {
                        // Show error notification
                        cartNotification.classList.add('error');
                        cartNotificationTitle.textContent = 'Error';
                        cartNotificationMessage.textContent = data.message || 'Failed to add to cart';
                        showNotification();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalContent;
                    
                    // Show error notification
                    cartNotification.classList.add('error');
                    cartNotificationTitle.textContent = 'Error';
                    cartNotificationMessage.textContent = 'Something went wrong';
                    showNotification();
                });
            });
        });

        function showNotification() {
            cartNotification.classList.add('show');
            setTimeout(() => {
                cartNotification.classList.remove('show');
                setTimeout(() => {
                    cartNotification.classList.remove('error');
                }, 400);
            }, 3000);
        }

        // About Us Modal
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