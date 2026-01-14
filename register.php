<?php
session_start();
require_once 'config/database.php';
require_once 'config/currency.php';

// If already logged in, redirect to home
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$title = 'Register - Motoshapi';
$activePage = 'login';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : null;
    
    // Validation
    if (empty($username) || empty($email) || empty($password)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        try {
            // Check if username already exists
            $stmt = $conn->prepare('SELECT id FROM users WHERE username = ?');
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $error = 'Username already exists. Please choose another one.';
            } else {
                // Check if email already exists
                $stmt = $conn->prepare('SELECT id FROM users WHERE email = ?');
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $error = 'Email already registered. Please <a href="login.php" style="color: var(--accent-primary);">login</a> instead.';
                } else {
                    // Create new user
                    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                    $stmt = $conn->prepare(
                        'INSERT INTO users (username, password, email, phone, status, created_at) 
                         VALUES (?, ?, ?, ?, ?, NOW())'
                    );
                    
                    if ($stmt->execute([$username, $hashed_password, $email, $phone, 'active'])) {
                        $user_id = $conn->lastInsertId();
                        
                        // Send welcome email (if email system is configured)
                        try {
                            if (file_exists('email/vendor/autoload.php') && file_exists('email/config/email.php')) {
                                require_once 'email/vendor/autoload.php';
                                require_once 'email/config/email.php';
                                sendWelcomeEmail($email, $username);
                            }
                        } catch (Exception $e) {
                            // Email sending failed, but registration succeeded
                            error_log('Welcome email failed: ' . $e->getMessage());
                        }
                        
                        // Auto login after registration
                        $_SESSION['user_id'] = $user_id;
                        $_SESSION['username'] = $username;
                        $_SESSION['email'] = $email;
                        
                        // Redirect to home page
                        $_SESSION['success'] = 'Registration successful! Welcome to Motoshapi.';
                        header('Location: index.php');
                        exit();
                    } else {
                        $error = 'Registration failed. Please try again.';
                    }
                }
            }
        } catch (PDOException $e) {
            $error = 'An error occurred. Please try again later.';
            error_log('Registration error: ' . $e->getMessage());
        }
    }
}

include 'includes/header.php';
?>
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
        .password-requirements {
            font-size: 0.875rem;
            color: var(--text-secondary);
            margin-top: 0.25rem;
        }
        .password-requirements.valid {
            color: var(--success);
        }
        .auth-logo {
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
        <div class="modern-card" style="max-width: 500px; margin: 0 auto; padding: var(--spacing-2xl);">
            <div style="text-align: center; margin-bottom: var(--spacing-xl);">
                <img src="uploads/logo/motologo.svg" alt="Motoshapi logo" class="auth-logo">
                <h2 style="font-size: 2rem; font-weight: 700; margin-bottom: var(--spacing-sm);">Create Account</h2>
                <p style="color: var(--text-secondary);">Join Motoshapi today</p>
            </div>
            
            <?php if($error): ?>
                <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid var(--danger); color: var(--danger); padding: var(--spacing-md); border-radius: var(--radius-md); margin-bottom: var(--spacing-lg);">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid var(--success); color: var(--success); padding: var(--spacing-md); border-radius: var(--radius-md); margin-bottom: var(--spacing-lg);">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" id="registerForm">
                <div style="margin-bottom: var(--spacing-lg);">
                    <label for="username" style="display: block; margin-bottom: var(--spacing-sm); font-weight: 600; color: var(--text-primary);">Username <span style="color: var(--danger);">*</span></label>
                    <input type="text" class="form-control" id="username" name="username" required autofocus value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" style="background: var(--bg-primary); border: 1px solid var(--border-primary); color: var(--text-primary); padding: 0.75rem; border-radius: var(--radius-md); width: 100%; font-size: 1rem;">
                </div>
                
                <div style="margin-bottom: var(--spacing-lg);">
                    <label for="email" style="display: block; margin-bottom: var(--spacing-sm); font-weight: 600; color: var(--text-primary);">Email Address <span style="color: var(--danger);">*</span></label>
                    <input type="email" class="form-control" id="email" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" style="background: var(--bg-primary); border: 1px solid var(--border-primary); color: var(--text-primary); padding: 0.75rem; border-radius: var(--radius-md); width: 100%; font-size: 1rem;">
                </div>
                
                <div style="margin-bottom: var(--spacing-lg);">
                    <label for="phone" style="display: block; margin-bottom: var(--spacing-sm); font-weight: 600; color: var(--text-primary);">Phone Number <span style="color: var(--text-secondary); font-weight: 400;">(Optional)</span></label>
                    <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" placeholder="+63 XXX XXX XXXX" style="background: var(--bg-primary); border: 1px solid var(--border-primary); color: var(--text-primary); padding: 0.75rem; border-radius: var(--radius-md); width: 100%; font-size: 1rem;">
                </div>
                
                <div style="margin-bottom: var(--spacing-lg);">
                    <label for="password" style="display: block; margin-bottom: var(--spacing-sm); font-weight: 600; color: var(--text-primary);">Password <span style="color: var(--danger);">*</span></label>
                    <div class="password-container">
                        <input type="password" class="form-control" id="password" name="password" required style="background: var(--bg-primary); border: 1px solid var(--border-primary); color: var(--text-primary); padding: 0.75rem; border-radius: var(--radius-md); width: 100%; font-size: 1rem;">
                        <i class="bi bi-eye password-toggle" onclick="togglePassword('password')"></i>
                    </div>
                    <div class="password-requirements" id="passwordHelp">Minimum 6 characters</div>
                </div>
                
                <div style="margin-bottom: var(--spacing-xl);">
                    <label for="confirm_password" style="display: block; margin-bottom: var(--spacing-sm); font-weight: 600; color: var(--text-primary);">Confirm Password <span style="color: var(--danger);">*</span></label>
                    <div class="password-container">
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required style="background: var(--bg-primary); border: 1px solid var(--border-primary); color: var(--text-primary); padding: 0.75rem; border-radius: var(--radius-md); width: 100%; font-size: 1rem;">
                        <i class="bi bi-eye password-toggle" onclick="togglePassword('confirm_password')"></i>
                    </div>
                    <div class="password-requirements" id="confirmHelp"></div>
                </div>
                
                <button type="submit" class="modern-btn modern-btn-primary" style="width: 100%; padding: 0.875rem; font-size: 1.0625rem;">Create Account</button>
            </form>
            
            <div style="text-align: center; margin-top: var(--spacing-xl); padding-top: var(--spacing-lg); border-top: 1px solid var(--border-primary);">
                <span style="color: var(--text-secondary);">Already have an account? </span>
                <a href="login.php" style="color: var(--accent-primary); text-decoration: none; font-weight: 600;">Sign In</a>
            </div>
        </div>
    </div>
    
    <script>
        function togglePassword(fieldId) {
            const passwordInput = document.getElementById(fieldId);
            const toggleIcon = passwordInput.parentElement.querySelector('.password-toggle');
            
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
        
        // Password validation
        const passwordInput = document.getElementById('password');
        const confirmInput = document.getElementById('confirm_password');
        const passwordHelp = document.getElementById('passwordHelp');
        const confirmHelp = document.getElementById('confirmHelp');
        
        passwordInput.addEventListener('input', function() {
            if (this.value.length >= 6) {
                passwordHelp.textContent = '✓ Password meets requirements';
                passwordHelp.classList.add('valid');
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            } else {
                passwordHelp.textContent = 'Minimum 6 characters';
                passwordHelp.classList.remove('valid');
                this.classList.remove('is-valid');
                if (this.value.length > 0) {
                    this.classList.add('is-invalid');
                } else {
                    this.classList.remove('is-invalid');
                }
            }
            checkPasswordMatch();
        });
        
        confirmInput.addEventListener('input', checkPasswordMatch);
        
        function checkPasswordMatch() {
            if (confirmInput.value === '') {
                confirmHelp.textContent = '';
                confirmHelp.classList.remove('valid');
                confirmHelp.style.color = '';
                confirmInput.classList.remove('is-valid', 'is-invalid');
                return;
            }
            
            if (passwordInput.value === confirmInput.value && passwordInput.value.length >= 6) {
                confirmHelp.textContent = '✓ Passwords match';
                confirmHelp.classList.add('valid');
                confirmHelp.style.color = 'var(--success)';
                confirmInput.classList.remove('is-invalid');
                confirmInput.classList.add('is-valid');
            } else {
                confirmHelp.textContent = '✗ Passwords do not match';
                confirmHelp.classList.remove('valid');
                confirmHelp.style.color = 'var(--danger)';
                confirmInput.classList.remove('is-valid');
                confirmInput.classList.add('is-invalid');
            }
        }
        
        // Email validation
        const emailInput = document.getElementById('email');
        emailInput.addEventListener('input', function() {
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (emailPattern.test(this.value)) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            } else if (this.value.length > 0) {
                this.classList.remove('is-valid');
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-valid', 'is-invalid');
            }
        });
        
        // Username validation
        const usernameInput = document.getElementById('username');
        usernameInput.addEventListener('input', function() {
            if (this.value.length >= 3) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            } else if (this.value.length > 0) {
                this.classList.remove('is-valid');
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-valid', 'is-invalid');
            }
        });
    </script>
<?php include 'includes/footer.php'; ?>
