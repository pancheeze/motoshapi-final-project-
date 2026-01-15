<?php
session_start();
require_once '../config/connect.php';

// Check if admin is logged in
if(!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$success = '';
$error = '';

// Handle email settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_email_settings'])) {
    $smtp_host = $_POST['smtp_host'];
    $smtp_port = $_POST['smtp_port'];
    $smtp_username = $_POST['smtp_username'];
    $smtp_password = $_POST['smtp_password'];
    $smtp_encryption = $_POST['smtp_encryption'];
    $from_email = $_POST['from_email'];
    $from_name = $_POST['from_name'];
    
    // Read current config file
    $config_file = '../email/config/email.php';
    $config_content = file_get_contents($config_file);
    
    // Update values
    $config_content = preg_replace(
        "/define\('SMTP_HOST',\s*'[^']*'\);/",
        "define('SMTP_HOST', '$smtp_host');",
        $config_content
    );
    $config_content = preg_replace(
        "/define\('SMTP_PORT',\s*\d+\);/",
        "define('SMTP_PORT', $smtp_port);",
        $config_content
    );
    $config_content = preg_replace(
        "/define\('SMTP_USERNAME',\s*getenv\('MOTOSHAPI_SMTP_USERNAME'\)\s*\?:\s*'[^']*'\);/",
        "define('SMTP_USERNAME', getenv('MOTOSHAPI_SMTP_USERNAME') ?: '$smtp_username');",
        $config_content
    );
    $config_content = preg_replace(
        "/define\('SMTP_ENCRYPTION',\s*'[^']*'\);/",
        "define('SMTP_ENCRYPTION', '$smtp_encryption');",
        $config_content
    );
    $config_content = preg_replace(
        "/define\('FROM_EMAIL',\s*getenv\('MOTOSHAPI_FROM_EMAIL'\)\s*\?:\s*[^;]+\);/",
        "define('FROM_EMAIL', getenv('MOTOSHAPI_FROM_EMAIL') ?: '$from_email');",
        $config_content
    );
    $config_content = preg_replace(
        "/define\('FROM_NAME',\s*getenv\('MOTOSHAPI_FROM_NAME'\)\s*\?:\s*'[^']*'\);/",
        "define('FROM_NAME', getenv('MOTOSHAPI_FROM_NAME') ?: '$from_name');",
        $config_content
    );
    
    if (file_put_contents($config_file, $config_content)) {
        // Create or update email.local.php for password
        $local_config = "../email/config/email.local.php";
        $local_content = "<?php\n// Local email configuration (not in version control)\n";
        $local_content .= "if (!defined('SMTP_PASSWORD')) {\n";
        $local_content .= "    define('SMTP_PASSWORD', '$smtp_password');\n";
        $local_content .= "}\n";
        
        file_put_contents($local_config, $local_content);
        
        $success = 'Email settings updated successfully!';
    } else {
        $error = 'Failed to update email settings. Check file permissions.';
    }
}

// Load current settings
require_once '../email/vendor/autoload.php';
require_once '../email/config/email.php';

$current_smtp_host = defined('SMTP_HOST') ? SMTP_HOST : 'smtp.gmail.com';
$current_smtp_port = defined('SMTP_PORT') ? SMTP_PORT : 587;
$current_smtp_username = defined('SMTP_USERNAME') ? SMTP_USERNAME : '';
$current_smtp_encryption = defined('SMTP_ENCRYPTION') ? SMTP_ENCRYPTION : 'tls';
$current_from_email = defined('FROM_EMAIL') ? FROM_EMAIL : '';
$current_from_name = defined('FROM_NAME') ? FROM_NAME : 'Motoshapi';

include 'includes/header.php';
?>

<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Email Settings</h2>
        <a href="index.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle me-2"></i><?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle me-2"></i><?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-envelope-gear me-2"></i>SMTP Configuration</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="row mb-3">
                            <div class="col-md-8">
                                <label class="form-label">SMTP Host</label>
                                <input type="text" name="smtp_host" class="form-control" value="<?php echo htmlspecialchars($current_smtp_host); ?>" required>
                                <small class="text-muted">Example: smtp.gmail.com</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">SMTP Port</label>
                                <input type="number" name="smtp_port" class="form-control" value="<?php echo $current_smtp_port; ?>" required>
                                <small class="text-muted">587 for TLS</small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">SMTP Username (Email)</label>
                            <input type="email" name="smtp_username" class="form-control" value="<?php echo htmlspecialchars($current_smtp_username); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">SMTP Password (App Password)</label>
                            <input type="password" name="smtp_password" class="form-control" placeholder="Enter Gmail App Password">
                            <small class="text-muted">For Gmail: Create an App Password in your Google Account settings</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Encryption</label>
                            <select name="smtp_encryption" class="form-select">
                                <option value="tls" <?php echo $current_smtp_encryption == 'tls' ? 'selected' : ''; ?>>TLS (Port 587)</option>
                                <option value="ssl" <?php echo $current_smtp_encryption == 'ssl' ? 'selected' : ''; ?>>SSL (Port 465)</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">From Email</label>
                            <input type="email" name="from_email" class="form-control" value="<?php echo htmlspecialchars($current_from_email); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">From Name</label>
                            <input type="text" name="from_name" class="form-control" value="<?php echo htmlspecialchars($current_from_name); ?>" required>
                        </div>

                        <button type="submit" name="update_email_settings" class="btn btn-primary">
                            <i class="bi bi-save me-2"></i>Save Settings
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm mb-4 border-info">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Setup Guide</h5>
                </div>
                <div class="card-body">
                    <h6 class="mb-3">Gmail Setup Instructions:</h6>
                    <ol class="small">
                        <li>Go to <a href="https://myaccount.google.com/apppasswords" target="_blank">Google App Passwords</a></li>
                        <li>Sign in to your Google Account</li>
                        <li>Click "Select app" → Choose "Mail"</li>
                        <li>Click "Select device" → Choose "Other"</li>
                        <li>Type "MOTOSHAPI" and click Generate</li>
                        <li>Copy the 16-character password</li>
                        <li>Paste it in the SMTP Password field above</li>
                    </ol>

                    <div class="alert alert-warning mt-3">
                        <strong>⚠️ Important:</strong>
                        <ul class="small mb-0">
                            <li>Use App Password, not your regular Gmail password</li>
                            <li>2-Step Verification must be enabled</li>
                            <li>Password is stored in email.local.php</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-success">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-check-circle me-2"></i>Current Status</h5>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <strong>Host:</strong> <?php echo htmlspecialchars($current_smtp_host); ?>
                    </div>
                    <div class="mb-2">
                        <strong>Port:</strong> <?php echo $current_smtp_port; ?>
                    </div>
                    <div class="mb-2">
                        <strong>Username:</strong> <?php echo htmlspecialchars($current_smtp_username); ?>
                    </div>
                    <div class="mb-2">
                        <strong>From:</strong> <?php echo htmlspecialchars($current_from_name); ?> &lt;<?php echo htmlspecialchars($current_from_email); ?>&gt;
                    </div>
                    <hr>
                    <a href="email_dashboard.php?test=1" class="btn btn-success btn-sm w-100">
                        <i class="bi bi-send me-2"></i>Test Email Configuration
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
