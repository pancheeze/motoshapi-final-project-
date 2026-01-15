<?php
$title = 'Login - Motoshapi';
$activePage = 'login';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/connect.php';
require_once 'config/currency.php';
require_once 'includes/PizzeriaAPIClient.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email_or_username = trim($_POST['username']);
    $password = $_POST['password'];

    try {
        // Try local login first
        $stmt = $conn->prepare('SELECT * FROM users WHERE email = ? OR username = ?');
        $stmt->execute([$email_or_username, $email_or_username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            // Login successful
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            
            // Sync user to Pizzeria in background
            syncUserToPizzeria($user, $password);
            
            header('Location: index.php');
            exit();
        } else {
            $error = 'Invalid username/email or password.';
        }
    } catch (PDOException $e) {
        error_log('Login error: ' . $e->getMessage());
        $error = 'An error occurred. Please try again.';
    }
}

function syncUserToPizzeria($user, $password) {
    try {
        // Read Pizzeria IP from test file
        $testFile = __DIR__ . '/test_pizzeria_integration.php';
        if (file_exists($testFile)) {
            $content = file_get_contents($testFile);
            if (preg_match('/\$PIZZERIA_SERVER_IP\s*=\s*[\'"]([^\'"]*)[\'"];/', $content, $matches)) {
                $pizzeriaIP = $matches[1];
                if ($pizzeriaIP !== 'localhost' && $pizzeriaIP !== '192.168.1.100') {
                    $pizzeriaClient = new PizzeriaAPIClient("http://$pizzeriaIP/pizzeria");
                    
                    // Sync user data to Pizzeria
                    $pizzeriaClient->syncUser([
                        'name' => $user['full_name'] ?? $user['username'],
                        'email' => $user['email'],
                        'password' => $password, // Send plain password for Pizzeria to hash
                        'phone' => $user['phone'] ?? '',
                        'address' => $user['address'] ?? ''
                    ]);
                }
            }
        }
    } catch (Exception $e) {
        // Sync failed, but don't block login
        error_log('Pizzeria sync failed: ' . $e->getMessage());
    }
}
?>
<?php include 'includes/header.php'; ?>
    <style>
        .password-container {
            position: relative;
        }
        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--text-secondary);
            transition: color var(--transition-fast);
        }
        .password-toggle:hover {
            color: var(--accent-primary);
        }
        .login-logo {
            max-width: 80px;
            height: auto;
            display: block;
            margin: 0 auto var(--spacing-md);
            filter: brightness(0) saturate(100%);
        }
        .form-control {
            border: 2px solid #000 !important;
        }
        .form-control:focus {
            border-color: #000 !important;
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.1);
        }
        .form-control.is-valid {
            border-color: #28a745 !important;
        }
        .form-control.is-invalid {
            border-color: #dc3545 !important;
        }
    </style>
    <div class="modern-container" style="padding-top: 4rem; padding-bottom: 4rem;">
        <div class="modern-card" style="max-width: 450px; margin: 0 auto; padding: var(--spacing-2xl);">
            <div style="text-align: center; margin-bottom: var(--spacing-xl);">
                <img src="uploads/logo/motologo.svg" alt="Motoshapi logo" class="login-logo">
                <h2 style="font-size: 2rem; font-weight: 700; margin-bottom: var(--spacing-sm);">Welcome Back</h2>
                <p style="color: var(--text-secondary);">Sign in to your account</p>
            </div>
            
            <?php if(isset($_SESSION['error'])): ?>
                <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid var(--danger); color: var(--danger); padding: var(--spacing-md); border-radius: var(--radius-md); margin-bottom: var(--spacing-lg);">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>
            <?php if($error): ?>
                <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid var(--danger); color: var(--danger); padding: var(--spacing-md); border-radius: var(--radius-md); margin-bottom: var(--spacing-lg);">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div style="margin-bottom: var(--spacing-lg);">
                    <label for="username" style="display: block; margin-bottom: var(--spacing-sm); font-weight: 600; color: var(--text-primary);">Username or Email</label>
                    <input type="text" class="form-control" id="username" name="username" required autofocus style="background: var(--bg-primary); border: 1px solid var(--border-primary); color: var(--text-primary); padding: 0.75rem; border-radius: var(--radius-md); width: 100%; font-size: 1rem;">
                </div>
                <div style="margin-bottom: var(--spacing-lg);">
                    <label for="password" style="display: block; margin-bottom: var(--spacing-sm); font-weight: 600; color: var(--text-primary);">Password</label>
                    <div class="password-container">
                        <input type="password" class="form-control" id="password" name="password" required style="background: var(--bg-primary); border: 1px solid var(--border-primary); color: var(--text-primary); padding: 0.75rem; border-radius: var(--radius-md); width: 100%; font-size: 1rem;">
                        <i class="bi bi-eye password-toggle" onclick="togglePassword()"></i>
                    </div>
                </div>
                <div style="text-align: right; margin-bottom: var(--spacing-lg);">
                    <a href="forgot_password.php" style="color: var(--accent-primary); text-decoration: none; font-size: 0.875rem;">Forgot Password?</a>
                </div>
                <button type="submit" class="modern-btn modern-btn-primary" style="width: 100%; padding: 0.875rem; font-size: 1.0625rem;">Sign In</button>
            </form>
            
            <div style="text-align: center; margin-top: var(--spacing-xl); padding-top: var(--spacing-lg); border-top: 1px solid var(--border-primary);">
                <span style="color: var(--text-secondary);">Don't have an account? </span>
                <a href="register.php" style="color: var(--accent-primary); text-decoration: none; font-weight: 600;">Create Account</a>
            </div>
        </div>
    </div>
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.querySelector('.password-toggle');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('bi-eye');
                toggleIcon.classList.add('bi-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('bi-eye-slash');
                toggleIcon.classList.add('bi-eye');
            }
        }
    </script>
<?php include 'includes/footer.php'; ?> 