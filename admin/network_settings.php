<?php
session_start();
require_once '../config/connect.php';

// Check if admin is logged in
if(!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$message = null;

// Get this computer's IP address
function getLocalIP() {
    $output = shell_exec('ipconfig');
    preg_match('/IPv4 Address[^\d]+([\d\.]+)/', $output, $matches);
    return $matches[1] ?? 'Not Found';
}

$myIP = getLocalIP();

// Handle configuration update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['configure'])) {
    $pizzeriaIP = trim($_POST['pizzeria_ip']);
    
    if (filter_var($pizzeriaIP, FILTER_VALIDATE_IP)) {
        $filesUpdated = [];
        $filesSkipped = [];
        
        // Files to update
        $filesToUpdate = [
            '../tools/test_pizzeria_integration.php' => [
                'pattern' => '/\$PIZZERIA_SERVER_IP\s*=\s*[\'"][^\'"]*[\'"];/',
                'replacement' => "\$PIZZERIA_SERVER_IP = '{$pizzeriaIP}';"
            ],
            '../tools/demo_user_sync.php' => [
                'pattern' => '/\$PIZZERIA_IP\s*=\s*[\'"][^\'"]*[\'"];/',
                'replacement' => "\$PIZZERIA_IP = '{$pizzeriaIP}';"
            ]
        ];
        
        foreach ($filesToUpdate as $file => $config) {
            if (file_exists($file)) {
                $content = file_get_contents($file);
                $content = preg_replace($config['pattern'], $config['replacement'], $content);
                if (file_put_contents($file, $content)) {
                    $filesUpdated[] = basename($file);
                } else {
                    $filesSkipped[] = basename($file) . ' (permission denied)';
                }
            } else {
                $filesSkipped[] = basename($file) . ' (not found)';
            }
        }
        
        if (count($filesUpdated) > 0) {
            $message = [
                'type' => 'success', 
                'text' => 'Network settings updated successfully! Files updated: ' . implode(', ', $filesUpdated)
            ];
            if (count($filesSkipped) > 0) {
                $message['text'] .= '<br>Skipped: ' . implode(', ', $filesSkipped);
            }
        } else {
            $message = [
                'type' => 'danger', 
                'text' => 'Failed to update configuration files. Check file permissions.'
            ];
        }
    } else {
        $message = [
            'type' => 'danger', 
            'text' => 'Invalid IP address format. Please enter a valid IPv4 address.'
        ];
    }
}

// Get current Pizzeria IP from test file
$currentPizzeriaIP = 'Not configured';
$testFile = '../tools/test_pizzeria_integration.php';
if (file_exists($testFile)) {
    $content = file_get_contents($testFile);
    if (preg_match('/\$PIZZERIA_SERVER_IP\s*=\s*[\'"]([^\'"]*)[\'"];/', $content, $matches)) {
        $currentPizzeriaIP = $matches[1];
    }
}

$title = 'Network Settings';
$activePage = 'network';
$activeAdminPage = 'network';
include 'includes/header.php';
?>

<style>
    .network-card {
        border-left: 4px solid #667eea;
    }
    .ip-display {
        background: #f8f9fa;
        border: 2px solid #667eea;
        border-radius: 8px;
        padding: 20px;
        font-size: 24px;
        font-weight: bold;
        text-align: center;
        color: #667eea;
        font-family: 'Courier New', monospace;
        margin: 15px 0;
    }
    .info-box {
        background: #e8f4f8;
        border-left: 4px solid #2196F3;
        padding: 15px;
        margin: 15px 0;
        border-radius: 4px;
    }
    .warning-box {
        background: #fff3cd;
        border-left: 4px solid #ffc107;
        padding: 15px;
        margin: 15px 0;
        border-radius: 4px;
    }
    .success-box {
        background: #d4edda;
        border-left: 4px solid #28a745;
        padding: 15px;
        margin: 15px 0;
        border-radius: 4px;
    }
    .step-section {
        background: white;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 20px;
        margin: 20px 0;
    }
    .step-number {
        background: #667eea;
        color: white;
        width: 35px;
        height: 35px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        margin-right: 10px;
    }
    .code-display {
        background: #263238;
        color: #aed581;
        padding: 15px;
        border-radius: 6px;
        font-family: 'Courier New', monospace;
        overflow-x: auto;
        margin: 10px 0;
    }
    .copy-btn {
        cursor: pointer;
        color: #667eea;
        margin-left: 10px;
    }
    .copy-btn:hover {
        color: #5568d3;
    }
</style>

<div class="container-fluid px-4">
    <h1 class="mt-4">
        <i class="fas fa-network-wired"></i> Network Settings
    </h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Network Settings</li>
    </ol>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message['type']; ?> alert-dismissible fade show" role="alert">
            <?php echo $message['text']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-xl-8">
            <div class="card network-card mb-4">
                <div class="card-header">
                    <i class="fas fa-server me-1"></i>
                    Multi-Device Configuration
                </div>
                <div class="card-body">
                    <div class="success-box">
                        <strong><i class="fas fa-check-circle"></i> This Computer's IP Address (Motoshapi):</strong>
                        <div class="ip-display">
                            <?php echo htmlspecialchars($myIP); ?>
                            <i class="fas fa-copy copy-btn" onclick="copyToClipboard('<?php echo $myIP; ?>')" title="Copy IP"></i>
                        </div>
                        <small class="text-muted">
                            <i class="fas fa-info-circle"></i> This is the IP address of the computer running Motoshapi. 
                            Share this with Computer B (Pizzeria) for configuration.
                        </small>
                    </div>

                    <div class="step-section">
                        <h5>
                            <span class="step-number">1</span>
                            Configure Pizzeria Connection
                        </h5>
                        <p>Enter the IP address of the computer running Pizzeria to enable API communication:</p>
                        
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="pizzeria_ip" class="form-label">
                                    <i class="fas fa-pizza-slice"></i> Pizzeria Server IP Address
                                </label>
                                <input type="text" 
                                       class="form-control form-control-lg" 
                                       id="pizzeria_ip" 
                                       name="pizzeria_ip" 
                                       placeholder="Example: 192.168.1.90" 
                                       value="<?php echo htmlspecialchars($currentPizzeriaIP); ?>"
                                       pattern="^(?:[0-9]{1,3}\.){3}[0-9]{1,3}$"
                                       required>
                                <small class="form-text text-muted">
                                    Run <code>ipconfig</code> on Computer B to find its IP address, or use 'localhost' if both systems are on the same computer.
                                </small>
                            </div>
                            
                            <button type="submit" name="configure" class="btn btn-primary btn-lg">
                                <i class="fas fa-save"></i> Save Configuration
                            </button>
                        </form>

                        <div class="info-box mt-3">
                            <strong><i class="fas fa-info-circle"></i> Current Configuration:</strong><br>
                            Pizzeria IP: <code><?php echo htmlspecialchars($currentPizzeriaIP); ?></code>
                        </div>
                    </div>

                    <div class="step-section">
                        <h5>
                            <span class="step-number">2</span>
                            Configure Computer B (Pizzeria)
                        </h5>
                        <p>On the computer running Pizzeria, update the Motoshapi IP address:</p>
                        
                        <div class="info-box">
                            <strong>Method 1: Use Setup Page (Recommended)</strong>
                            <div class="code-display">
                                http://[Computer B IP]/pizzeria/tools/setup_network.php
                            </div>
                            <p>Enter this computer's IP (<code><?php echo $myIP; ?></code>) in the form.</p>
                        </div>

                        <div class="info-box">
                            <strong>Method 2: Manual Configuration</strong>
                            <p>Edit: <code>pizzeria/test_motoshapi_integration.php</code></p>
                            <div class="code-display">
                                $MOTOSHAPI_SERVER_IP = '<?php echo $myIP; ?>';
                            </div>
                        </div>
                    </div>

                    <div class="step-section">
                        <h5>
                            <span class="step-number">3</span>
                            Test Connection
                        </h5>
                        <p>Verify that both systems can communicate:</p>
                        
                        <a href="../tools/test_pizzeria_integration.php" target="_blank" class="btn btn-success mb-2">
                            <i class="fas fa-vial"></i> Test Pizzeria Connection
                        </a>
                        <br>
                        <a href="../tools/demo_user_sync.php" target="_blank" class="btn btn-info">
                            <i class="fas fa-sync"></i> Test User Synchronization
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-question-circle me-1"></i>
                    Setup Instructions
                </div>
                <div class="card-body">
                    <h6><i class="fas fa-wifi"></i> Requirements</h6>
                    <ul>
                        <li>Both computers on same WiFi network</li>
                        <li>Windows Firewall allows port 80</li>
                        <li>XAMPP running on both computers</li>
                    </ul>

                    <hr>

                    <h6><i class="fas fa-network-wired"></i> Network Topology</h6>
                    <div class="code-display" style="font-size: 12px;">
WiFi Router (192.168.1.1)
  │
  ├─ Computer A: <?php echo $myIP; ?>

  │  └─ Motoshapi
  │
  └─ Computer B: [Configure above]
     └─ Pizzeria
                    </div>

                    <hr>

                    <h6><i class="fas fa-fire"></i> Firewall Configuration</h6>
                    <ol style="font-size: 14px;">
                        <li>Open Windows Defender Firewall</li>
                        <li>Advanced Settings → Inbound Rules</li>
                        <li>New Rule → Port → TCP 80</li>
                        <li>Allow the connection</li>
                    </ol>

                    <hr>

                    <h6><i class="fas fa-terminal"></i> Quick Commands</h6>
                    <div style="font-size: 13px;">
                        <strong>Find IP Address:</strong>
                        <div class="code-display" style="font-size: 12px;">
ipconfig
                        </div>

                        <strong>Test Connection:</strong>
                        <div class="code-display" style="font-size: 12px;">
ping [Computer B IP]
                        </div>

                        <strong>Test API:</strong>
                        <div class="code-display" style="font-size: 12px;">
curl http://[IP]/pizzeria/api/
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-book me-1"></i>
                    Documentation
                </div>
                <div class="card-body">
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <i class="fas fa-file-alt text-primary"></i>
                            <a href="../docs/MULTI_DEVICE_SETUP.md" target="_blank">Multi-Device Setup Guide</a>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-file-alt text-primary"></i>
                            <a href="../docs/API_INTEGRATION_GUIDE.md" target="_blank">API Integration Guide</a>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-file-alt text-primary"></i>
                            <a href="../QUICK_IP_SETUP.md" target="_blank">Quick Setup Instructions</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        // Show success message
        const icon = event.target;
        const originalClass = icon.className;
        icon.className = 'fas fa-check copy-btn';
        icon.style.color = '#28a745';
        
        setTimeout(() => {
            icon.className = originalClass;
            icon.style.color = '';
        }, 2000);
    }).catch(err => {
        alert('Failed to copy: ' + err);
    });
}
</script>

<?php include 'includes/footer.php'; ?>
