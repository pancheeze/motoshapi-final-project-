<?php
session_start();
require_once '../config/database.php';
require_once '../email/vendor/autoload.php';
require_once '../email/config/email.php';

// Check if admin is logged in
if(!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$success = '';
$error = '';

// Handle test email
if (isset($_GET['test']) || (isset($_POST['send_test']) && !empty($_POST['test_email']))) {
    $test_email = isset($_POST['test_email']) ? trim($_POST['test_email']) : '';
    
    if (!empty($test_email) && filter_var($test_email, FILTER_VALIDATE_EMAIL)) {
        $subject = 'MOTOSHAPI Email Test - ' . date('Y-m-d H:i:s');
        $body = "
        <html>
        <body style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
            <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;'>
                <h1 style='color: white; margin: 0;'>✅ Email Test Successful!</h1>
            </div>
            <div style='background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px;'>
                <p style='font-size: 16px; color: #333;'>Your MOTOSHAPI email configuration is working correctly!</p>
                <p style='font-size: 14px; color: #666;'><strong>Test Details:</strong></p>
                <ul style='color: #666;'>
                    <li>SMTP Host: " . SMTP_HOST . "</li>
                    <li>SMTP Port: " . SMTP_PORT . "</li>
                    <li>Encryption: " . SMTP_ENCRYPTION . "</li>
                    <li>From: " . FROM_EMAIL . "</li>
                    <li>Sent at: " . date('Y-m-d H:i:s') . "</li>
                </ul>
                <p style='margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #999; font-size: 12px;'>
                    This is an automated test email from MOTOSHAPI Admin Panel
                </p>
            </div>
        </body>
        </html>
        ";
        
        $result = sendEmail($test_email, $subject, $body);
        if ($result === true) {
            $success = "✅ Test email sent successfully to $test_email! Check your inbox.";
        } else {
            $error = "❌ Failed to send email: " . htmlspecialchars($result);
        }
    } else {
        $error = "Please provide a valid email address.";
    }
}

include 'includes/header.php';
?>

<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0"><i class="bi bi-envelope-at me-2"></i>Email Dashboard</h2>
        <div>
            <a href="email_settings.php" class="btn btn-primary me-2">
                <i class="bi bi-gear"></i> Email Settings
            </a>
            <a href="index.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4 mb-4">
        <!-- Configuration Status -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="rounded-circle bg-primary bg-opacity-10 p-3 me-3">
                            <i class="bi bi-gear-fill text-primary fs-4"></i>
                        </div>
                        <div>
                            <h6 class="mb-0 text-muted">SMTP Configuration</h6>
                            <h4 class="mb-0"><?php echo defined('SMTP_HOST') ? SMTP_HOST : 'Not Set'; ?></h4>
                        </div>
                    </div>
                    <small class="text-muted">
                        Port: <?php echo defined('SMTP_PORT') ? SMTP_PORT : 'N/A'; ?> | 
                        <?php echo defined('SMTP_ENCRYPTION') ? strtoupper(SMTP_ENCRYPTION) : 'N/A'; ?>
                    </small>
                </div>
            </div>
        </div>

        <!-- From Address -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="rounded-circle bg-success bg-opacity-10 p-3 me-3">
                            <i class="bi bi-envelope-fill text-success fs-4"></i>
                        </div>
                        <div>
                            <h6 class="mb-0 text-muted">From Address</h6>
                            <h6 class="mb-0"><?php echo defined('FROM_EMAIL') ? FROM_EMAIL : 'Not Set'; ?></h6>
                        </div>
                    </div>
                    <small class="text-muted">
                        Name: <?php echo defined('FROM_NAME') ? FROM_NAME : 'N/A'; ?>
                    </small>
                </div>
            </div>
        </div>

        <!-- Test Email -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100 border-primary">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="rounded-circle bg-info bg-opacity-10 p-3 me-3">
                            <i class="bi bi-send-fill text-info fs-4"></i>
                        </div>
                        <div>
                            <h6 class="mb-0 text-muted">Send Test Email</h6>
                        </div>
                    </div>
                    <form method="POST" class="d-flex gap-2">
                        <input type="email" name="test_email" class="form-control form-control-sm" placeholder="your@email.com" required>
                        <button type="submit" name="send_test" class="btn btn-primary btn-sm">
                            <i class="bi bi-send"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Email Features -->
    <div class="row g-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-check-circle me-2"></i>Active Email Features</h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-envelope-check text-success me-2"></i>Welcome Emails</span>
                            <span class="badge bg-success">Active</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-box-seam text-primary me-2"></i>Order Status Updates</span>
                            <span class="badge bg-success">Active</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-key text-warning me-2"></i>Password Reset</span>
                            <span class="badge bg-success">Active</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Email Templates</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">Email templates are configured in:</p>
                    <ul class="list-unstyled">
                        <li><i class="bi bi-file-earmark-code text-primary me-2"></i><code>email/config/email.php</code></li>
                    </ul>
                    <hr>
                    <h6 class="mb-3">Available Templates:</h6>
                    <div class="d-flex flex-column gap-2">
                        <div class="p-2 bg-light rounded">
                            <strong>Welcome Email</strong><br>
                            <small class="text-muted">Sent when new user registers</small>
                        </div>
                        <div class="p-2 bg-light rounded">
                            <strong>Order Status Update</strong><br>
                            <small class="text-muted">Sent when order status changes</small>
                        </div>
                        <div class="p-2 bg-light rounded">
                            <strong>Password Reset</strong><br>
                            <small class="text-muted">Sent when user requests password reset</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
