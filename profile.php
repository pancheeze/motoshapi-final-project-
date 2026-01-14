<?php
session_start();
require_once 'config/database.php';
require_once 'config/currency.php';

$title = 'Profile - Motoshapi';
include 'includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? '';

// Fetch user details
$stmt = $conn->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle profile update
$success = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $email = trim($_POST['email']);
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : null;
    
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Check if email is already used by another user
        $check_stmt = $conn->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
        $check_stmt->execute([$email, $user_id]);
        if ($check_stmt->fetch()) {
            $error = 'This email address is already in use by another account.';
        } else {
            try {
                $stmt = $conn->prepare('UPDATE users SET email = ?, phone = ? WHERE id = ?');
                $stmt->execute([$email, $phone, $user_id]);
                $success = 'Profile updated successfully!';
                $user['email'] = $email;
                $user['phone'] = $phone;
            } catch (PDOException $e) {
                $error = 'Error updating profile. Please try again.';
            }
        }
    } else {
        $error = 'Please enter a valid email address.';
    }
}
?>
    <div class="modern-container modern-section">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="modern-card" style="padding: var(--spacing-2xl);">
                    <h2 class="modern-section-title" style="text-align: left; margin-bottom: var(--spacing-xl);">My <span class="modern-accent-text">Profile</span></h2>
                    
                    <?php if($success): ?>
                        <div style="background: rgba(45, 122, 79, 0.1); border: 1px solid var(--success); color: var(--success); padding: var(--spacing-md); border-radius: var(--radius-md); margin-bottom: var(--spacing-lg);">
                            <?php echo $success; ?>
                        </div>
                    <?php endif; ?>
                    <?php if($error): ?>
                        <div style="background: rgba(193, 39, 32, 0.1); border: 1px solid var(--danger); color: var(--danger); padding: var(--spacing-md); border-radius: var(--radius-md); margin-bottom: var(--spacing-lg);">
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div style="margin-bottom: var(--spacing-lg);">
                            <label for="username" class="form-label" style="display: block; margin-bottom: var(--spacing-sm); font-weight: 600; color: var(--text-primary);">Username</label>
                            <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($username); ?>" readonly style="background: var(--bg-tertiary); border: 1px solid var(--border-primary); color: var(--text-muted); padding: 0.75rem; border-radius: var(--radius-md); width: 100%; cursor: not-allowed;">
                        </div>
                        <div style="margin-bottom: var(--spacing-lg);">
                            <label for="email" class="form-label" style="display: block; margin-bottom: var(--spacing-sm); font-weight: 600; color: var(--text-primary);">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required style="background: var(--bg-primary); border: 2px solid #000; color: var(--text-primary); padding: 0.75rem; border-radius: var(--radius-md); width: 100%;">
                        </div>
                        <div style="margin-bottom: var(--spacing-lg);">
                            <label for="phone" class="form-label" style="display: block; margin-bottom: var(--spacing-sm); font-weight: 600; color: var(--text-primary);">Phone Number <span style="color: var(--text-muted); font-size: 0.875rem;">(Optional)</span></label>
                            <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="+63 912 345 6789" style="background: var(--bg-primary); border: 2px solid #000; color: var(--text-primary); padding: 0.75rem; border-radius: var(--radius-md); width: 100%;">
                        </div>
                        <div class="d-flex justify-content-between align-items-center" style="gap: var(--spacing-md);">
                            <button type="submit" name="update_profile" class="modern-btn modern-btn-primary">Update Profile</button>
                            <a href="logout.php" class="modern-btn" style="background: var(--danger); color: #fff; text-decoration: none;">Logout</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

<style>
    .form-control.is-valid {
        border: 2px solid #28a745 !important;
    }
    
    .form-control.is-invalid {
        border: 2px solid #dc3545 !important;
    }
    
    .form-control {
        border: 2px solid #000 !important;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const emailInput = document.getElementById('email');
        const phoneInput = document.getElementById('phone');
        
        // Email validation
        emailInput.addEventListener('input', function() {
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (emailInput.value.trim() === '') {
                emailInput.classList.remove('is-valid', 'is-invalid');
            } else if (emailPattern.test(emailInput.value)) {
                emailInput.classList.remove('is-invalid');
                emailInput.classList.add('is-valid');
            } else {
                emailInput.classList.remove('is-valid');
                emailInput.classList.add('is-invalid');
            }
        });
        
        // Phone validation (optional field)
        phoneInput.addEventListener('input', function() {
            // Philippine phone format: +63 followed by 10 digits, or 09xx format
            const phonePattern = /^(\+63|0)?[0-9]{10,11}$/;
            const phoneValue = phoneInput.value.replace(/\s+/g, ''); // Remove spaces
            
            if (phoneInput.value.trim() === '') {
                // Optional field - remove validation classes if empty
                phoneInput.classList.remove('is-valid', 'is-invalid');
            } else if (phonePattern.test(phoneValue)) {
                phoneInput.classList.remove('is-invalid');
                phoneInput.classList.add('is-valid');
            } else {
                phoneInput.classList.remove('is-valid');
                phoneInput.classList.add('is-invalid');
            }
        });
    });
</script>

<?php include 'includes/footer.php'; ?> 