<?php
$title = 'My Orders - Motoshapi';
include 'includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle order received action
if (isset($_POST['order_received']) && isset($_POST['order_id'])) {
    $order_id = intval($_POST['order_id']);
    $stmt = $conn->prepare('UPDATE orders SET status = "delivered" WHERE id = ? AND user_id = ?');
    $stmt->execute([$order_id, $user_id]);
}

// Fetch user orders
$stmt = $conn->prepare('SELECT o.*, s.first_name, s.last_name, s.city, s.province, s.postal_code, s.phone FROM orders o LEFT JOIN shipping_information s ON o.id = s.order_id WHERE o.user_id = ? ORDER BY o.created_at DESC');
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

function getOrderItems($conn, $order_id) {
    $stmt = $conn->prepare('SELECT oi.*, p.name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?');
    $stmt->execute([$order_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$fixed_shipping_time = '3-5 business days after order date';
?>
    <div class="container mt-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">My Orders</h5>
            </div>
            <div class="card-body">
                <?php if (empty($orders)): ?>
                    <div class="alert alert-info">You have no orders yet.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Order ID</th>
                                    <th>Order Date</th>
                                    <th>Total Amount</th>
                                    <th>Status</th>
                                    <th>Shipping</th>
                                    <th>Details</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td>#<?php echo $order['id']; ?></td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($order['created_at'])); ?></td>
                                    <td><?php echo format_price($order['total_amount']); ?></td>
                                    <td><span class="badge bg-<?php echo $order['status'] == 'pending' ? 'warning' : ($order['status'] == 'delivered' ? 'success' : 'info'); ?>"><?php echo ucfirst($order['status']); ?></span></td>
                                    <td>
                                        <?php echo htmlspecialchars($order['city'] . ', ' . $order['province'] . ' ' . $order['postal_code']); ?><br>
                                        <small><?php echo $fixed_shipping_time; ?></small>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-info" type="button" data-bs-toggle="collapse" data-bs-target="#orderItems<?php echo $order['id']; ?>">View</button>
                                    </td>
                                    <td>
                                        <?php if ($order['status'] != 'delivered'): ?>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                <button type="submit" name="order_received" class="btn btn-sm btn-success">
                                                    Order Received
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-success">Received</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr class="collapse" id="orderItems<?php echo $order['id']; ?>">
                                    <td colspan="7">
                                        <strong>Order Items:</strong>
                                        <ul>
                                            <?php foreach(getOrderItems($conn, $order['id']) as $item): ?>
                                                <li><?php echo htmlspecialchars($item['name']); ?> x <?php echo $item['quantity']; ?> (<?php echo format_price($item['price']); ?> each)</li>
                                            <?php endforeach; ?>
                                        </ul>
                                        <strong>Contact:</strong> <?php echo htmlspecialchars($order['phone']); ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php include 'includes/footer.php'; ?> 