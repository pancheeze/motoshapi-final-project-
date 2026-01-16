<?php
session_start();
require_once '../config/connect.php';
require_once '../config/currency.php';

// Check if admin is logged in
if(!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Get statistics
$stmt = $conn->query("SELECT COUNT(*) as total_products FROM products");
$total_products = $stmt->fetch()['total_products'];

$stmt = $conn->query("SELECT COUNT(*) as total_categories FROM categories");
$total_categories = $stmt->fetch()['total_categories'];

$stmt = $conn->query("SELECT COUNT(*) as total_orders FROM orders");
$total_orders = $stmt->fetch()['total_orders'];

$stmt = $conn->query("SELECT COUNT(*) as total_users FROM users");
$total_users = $stmt->fetch()['total_users'];

// Check User Sync Status
$pizzeriaIP = 'Not configured';
$syncEnabled = false;
$testFile = '../tools/test_pizzeria_integration.php';
if (file_exists($testFile)) {
    $content = file_get_contents($testFile);
    if (preg_match('/\$PIZZERIA_SERVER_IP\s*=\s*[\'"]([^\'"]*)[\'"];/', $content, $matches)) {
        $pizzeriaIP = $matches[1];
        $syncEnabled = ($pizzeriaIP !== 'localhost' && $pizzeriaIP !== '192.168.1.100');
    }
}

// Payment settings page is informational; supported methods are COD + PayPal

$title = 'Admin Dashboard - Motoshapi';
$activeAdminPage = 'dashboard';
$mainClass = 'flex-grow-1 py-4 container-xxl';
include 'includes/header.php';
?>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="row g-3">
                <?php if (isset($success)): ?>
                    <div class="col-12">
                        <div class="alert alert-success mb-0"><?php echo $success; ?></div>
                    </div>
                <?php endif; ?>
                <?php if (isset($error)): ?>
                    <div class="col-12">
                        <div class="alert alert-danger mb-0"><?php echo $error; ?></div>
                    </div>
                <?php endif; ?>
                <div class="col-12">
                    <h2 class="mb-0">Dashboard</h2>
                    <p class="text-muted">Quick overview of sales performance and system activity.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- User Sync Status Card -->
        <div class="col-12">
            <div class="card shadow-sm border-start border-4 <?php echo $syncEnabled ? 'border-success' : 'border-warning'; ?>">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <div class="rounded-circle bg-<?php echo $syncEnabled ? 'success' : 'warning'; ?> bg-opacity-10 p-3">
                                <i class="bi bi-arrow-left-right fs-1 text-<?php echo $syncEnabled ? 'success' : 'warning'; ?>"></i>
                            </div>
                        </div>
                        <div class="col">
                            <h5 class="mb-1">
                                User Account Synchronization
                                <?php if ($syncEnabled): ?>
                                    <span class="badge bg-success ms-2">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark ms-2">Not Configured</span>
                                <?php endif; ?>
                            </h5>
                            <p class="text-muted mb-2">
                                <?php if ($syncEnabled): ?>
                                    Customer accounts are automatically shared with Pizzeria (<?php echo htmlspecialchars($pizzeriaIP); ?>)
                                <?php else: ?>
                                    Configure network settings to enable automatic user sync with Pizzeria
                                <?php endif; ?>
                            </p>
                            <div class="d-flex gap-2 flex-wrap">
                                <?php if ($syncEnabled): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                                        <i class="bi bi-check-circle me-1"></i>Login Sync Active
                                    </span>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                                        <i class="bi bi-check-circle me-1"></i>Registration Sync Active
                                    </span>
                                    <span class="badge bg-info-subtle text-info border border-info-subtle">
                                        <i class="bi bi-people me-1"></i><?php echo $total_users; ?> Users
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <?php if ($syncEnabled): ?>
                                <a href="network_settings.php" class="btn btn-outline-success">
                                    <i class="bi bi-gear me-1"></i>Settings
                                </a>
                            <?php else: ?>
                                <a href="network_settings.php" class="btn btn-warning">
                                    <i class="bi bi-gear me-1"></i>Configure Now
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if ($syncEnabled): ?>
                    <hr class="my-3">
                    <div class="row g-3 text-center">
                        <div class="col-6 col-md-3">
                            <div class="small text-muted">Sync Direction</div>
                            <div class="fw-semibold">Bidirectional</div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="small text-muted">Pizzeria IP</div>
                            <div class="fw-semibold"><?php echo htmlspecialchars($pizzeriaIP); ?></div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="small text-muted">Data Synced</div>
                            <div class="fw-semibold">Customer Accounts Only</div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="small text-muted">Status</div>
                            <div class="text-success fw-semibold"><i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i>Online</div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card text-white bg-primary h-100 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <h5 class="card-title">Total Products</h5>
                        <i class="bi bi-box-seam fs-4"></i>
                    </div>
                    <p class="display-5 fw-bold mb-2"><?php echo $total_products; ?></p>
                    <a href="products.php" class="link-light text-decoration-none">View products <i class="bi bi-arrow-right-short"></i></a>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card text-white bg-success h-100 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <h5 class="card-title">Total Categories</h5>
                        <i class="bi bi-tags fs-4"></i>
                    </div>
                    <p class="display-5 fw-bold mb-2"><?php echo $total_categories; ?></p>
                    <a href="categories.php" class="link-light text-decoration-none">View categories <i class="bi bi-arrow-right-short"></i></a>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card text-white bg-warning h-100 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <h5 class="card-title">Total Orders</h5>
                        <i class="bi bi-receipt fs-4"></i>
                    </div>
                    <p class="display-5 fw-bold mb-2"><?php echo $total_orders; ?></p>
                    <a href="orders.php" class="link-light text-decoration-none">View orders <i class="bi bi-arrow-right-short"></i></a>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card text-white bg-info h-100 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <h5 class="card-title">Total Users</h5>
                        <i class="bi bi-people fs-4"></i>
                    </div>
                    <p class="display-5 fw-bold mb-2"><?php echo $total_users; ?></p>
                    <a href="users.php" class="link-light text-decoration-none">View users <i class="bi bi-arrow-right-short"></i></a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-xl-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white border-0">
                    <h5 class="card-title mb-0">Recent Orders</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Type</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stmt = $conn->query("SELECT o.*, u.username FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT 5");
                                while($order = $stmt->fetch(PDO::FETCH_ASSOC)):
                                ?>
                                <tr>
                                    <td>#<?php echo $order['id']; ?></td>
                                    <td><?php echo htmlspecialchars($order['username']); ?></td>
                                    <td><?php echo format_price($order['total_amount']); ?></td>
                                    <td><span class="badge bg-<?php echo $order['status'] === 'pending' ? 'warning text-dark' : 'success'; ?>"><?php echo ucfirst($order['status']); ?></span></td>
                                    <td><span class="badge bg-<?php echo $order['transaction_type'] === 'online' ? 'primary' : 'info text-dark'; ?>"><?php echo ucfirst($order['transaction_type']); ?></span></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white border-0">
                    <h5 class="card-title mb-0">Low Stock Products</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Product</th>
                                    <th>Stock</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stmt = $conn->query("SELECT * FROM products WHERE stock < 10 ORDER BY stock ASC LIMIT 5");
                                while($product = $stmt->fetch(PDO::FETCH_ASSOC)):
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                                    <td><span class="badge bg-danger"><?php echo $product['stock']; ?></span></td>
                                    <td><a href="edit_product.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-primary">Update Stock</a></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php include 'includes/footer.php'; ?>