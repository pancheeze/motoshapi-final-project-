<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/database.php';
require_once 'config/currency.php';

$bodyClass = isset($bodyClass) && $bodyClass !== '' ? trim($bodyClass) : 'bg-light';
$mainClassAttr = ' class="flex-grow-1 py-4"';
if (isset($mainClass) && $mainClass !== '') {
    $mainClassAttr = ' class="' . htmlspecialchars($mainClass, ENT_QUOTES) . '"';
}

$cart_count = 0;
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cart_count += $item['quantity'] ?? 0;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? 'Motoshapi - Motorcycle Parts'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/dark-mode.css">
</head>
<body class="<?php echo htmlspecialchars($bodyClass, ENT_QUOTES); ?>">
    <div class="d-flex flex-column min-vh-100">
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
            <div class="container">
                <a class="navbar-brand d-flex align-items-center" href="index.php">
                    <img src="uploads/logo/jresing (1).png" class="me-2" alt="Motoshapi Logo" style="height:64px; width:auto; object-fit:contain;">
                    <span class="fw-semibold">Motoshapi</span>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <li class="nav-item">
                            <a class="nav-link<?php echo ($activePage ?? '') === 'home' ? ' active' : ''; ?>" href="index.php">Home</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link<?php echo ($activePage ?? '') === 'products' ? ' active' : ''; ?>" href="products.php">Products</a>
                        </li>
                    </ul>
                    <ul class="navbar-nav align-items-lg-center gap-lg-2">
                        <?php if(isset($_SESSION['user_id'])): ?>
                            <li class="nav-item">
                                <a class="nav-link<?php echo ($activePage ?? '') === 'profile' ? ' active' : ''; ?>" href="profile.php">Profile</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link<?php echo ($activePage ?? '') === 'orders' ? ' active' : ''; ?>" href="orders.php">My Orders</a>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link<?php echo ($activePage ?? '') === 'login' ? ' active' : ''; ?>" href="login.php">Login</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link<?php echo ($activePage ?? '') === 'register' ? ' active' : ''; ?>" href="register.php">Register</a>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a href="cart.php" class="nav-link position-relative d-flex align-items-center">
                                <i class="bi bi-cart" style="font-size: 1.3rem;"></i>
                                <span class="badge bg-success position-absolute top-0 start-100 translate-middle rounded-pill" style="font-size: 0.75rem;">
                                    <?php echo $cart_count; ?>
                                </span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <button class="btn btn-link nav-link p-0 dark-mode-toggle" id="theme-toggle" type="button" aria-label="Toggle dark mode">
                                <i class="bi bi-moon"></i>
                            </button>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
        <main<?php echo $mainClassAttr; ?>>