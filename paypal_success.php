<?php
session_start();
require_once 'config/connect.php';
require_once 'config/currency.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = 'You must be logged in.';
    header('Location: login.php');
    exit();
}

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

$title = 'Payment Successful - Motoshapi';
include 'includes/header.php';
?>
<div class="modern-container" style="padding-top: 4rem; padding-bottom: 4rem;">
    <div class="modern-card" style="max-width: 600px; margin: 0 auto; padding: var(--spacing-2xl); text-align: center;">
        <div style="width: 5rem; height: 5rem; background: linear-gradient(135deg, var(--accent-primary), var(--accent-hover)); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto var(--spacing-xl);">
            <i class="bi bi-check-lg" style="font-size: 3rem; color: #fff;"></i>
        </div>
        <h1 style="font-size: 2rem; font-weight: 700; color: var(--text-primary); margin-bottom: var(--spacing-md);">Payment Successful!</h1>
        <p style="font-size: 1.125rem; color: var(--text-secondary); margin-bottom: var(--spacing-sm);">Thank you for your PayPal payment.</p>
        <div style="background: var(--bg-primary); border: 2px solid #28a745; padding: var(--spacing-lg); border-radius: var(--radius-md); margin-bottom: var(--spacing-xl);">
            <?php if ($order_id > 0): ?>
                <p style="font-size: 1.125rem; color: var(--text-primary); margin-bottom: var(--spacing-md); font-weight: 600;">Order Number: <span style="color: #28a745; font-weight: 700; font-size: 1.5rem;">#<?php echo $order_id; ?></span></p>
            <?php endif; ?>
            <p style="color: var(--text-secondary); margin: 0; line-height: 1.6;">We will process your order shortly. You can track your order status from your account.</p>
        </div>
        <div style="display: flex; gap: var(--spacing-md); justify-content: center;">
            <a href="products.php" class="modern-btn modern-btn-primary">Continue Shopping</a>
            <a href="orders.php" class="modern-btn modern-btn-secondary">View Orders</a>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
