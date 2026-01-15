<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/connect.php';
require_once 'config/currency.php';

$uiTheme = isset($uiTheme) && $uiTheme !== '' ? $uiTheme : 'spare';

$bodyClass = isset($bodyClass) && $bodyClass !== '' ? trim($bodyClass) : 'bg-light';
$bodyClass = $uiTheme === 'spare' ? trim($bodyClass . ' sp-body') : $bodyClass;
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
    <link rel="stylesheet" href="assets/css/modern-theme.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/chatbot-widget.css">
    <?php if ($uiTheme === 'spare'): ?>
        <link rel="stylesheet" href="assets/css/spare-parts-theme.css">
    <?php endif; ?>
</head>
<body class="<?php echo htmlspecialchars($bodyClass, ENT_QUOTES); ?>">
    <div class="d-flex flex-column min-vh-100">
        <?php if ($uiTheme === 'spare'): ?>
            <header class="sp-header">
                <nav class="navbar navbar-expand-lg sp-navbar">
                    <div class="sp-container container-fluid">
                        <a href="index.php" class="navbar-brand sp-brand">
                            <img src="uploads/logo/motologo.svg" alt="Motoshapi logo">
                            <span class="sp-brand-text">motoshapi</span>
                        </a>

                        <div class="collapse navbar-collapse" id="spMobileNav">
                            <ul class="navbar-nav ms-lg-auto sp-nav-list">
                                <li class="nav-item">
                                    <a href="index.php" class="nav-link sp-nav-link <?php echo ($activePage ?? '') === 'home' ? 'active' : ''; ?>">Home</a>
                                </li>
                                <li class="nav-item">
                                    <a href="products.php" class="nav-link sp-nav-link <?php echo ($activePage ?? '') === 'products' ? 'active' : ''; ?>">Shop</a>
                                </li>
                                <li class="nav-item">
                                    <a href="orders.php" class="nav-link sp-nav-link <?php echo ($activePage ?? '') === 'orders' ? 'active' : ''; ?>">My Orders</a>
                                </li>
                            </ul>
                        </div>

                        <div class="sp-actions ms-lg-3">
                            <a href="cart.php" class="sp-icon-btn" title="Cart">
                                <i class="bi bi-cart" style="font-size: 1.1rem;"></i>
                                <span class="sp-badge" id="cart-count-badge" style="<?php echo $cart_count == 0 ? 'display: none;' : ''; ?>"><?php echo $cart_count; ?></span>
                            </a>
                            <?php if(isset($_SESSION['user_id'])): ?>
                                <a href="profile.php" class="sp-icon-btn" title="Profile"><i class="bi bi-person" style="font-size: 1.1rem;"></i></a>
                            <?php else: ?>
                                <a href="login.php" class="sp-icon-btn" title="Login"><i class="bi bi-person" style="font-size: 1.1rem;"></i></a>
                            <?php endif; ?>
                            <button class="navbar-toggler sp-navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#spMobileNav" aria-controls="spMobileNav" aria-expanded="false" aria-label="Toggle navigation">
                                <span class="navbar-toggler-icon"></span>
                            </button>
                        </div>
                    </div>
                </nav>
            </header>
        <?php else: ?>
            <!-- Modern Navigation -->
            <nav class="navbar navbar-expand-lg modern-navbar">
                <div class="modern-navbar-container container-fluid">
                    <!-- Logo -->
                    <a href="index.php" class="navbar-brand modern-logo">
                        <img src="uploads/logo/motologo.svg" alt="Motoshapi logo" class="modern-logo-img" />
                        <span>MOTOSHAPI</span>
                    </a>

                    <div class="collapse navbar-collapse" id="modernMobileNav">
                        <ul class="navbar-nav ms-lg-auto modern-nav-links">
                            <li class="nav-item"><a href="index.php" class="nav-link modern-nav-link<?php echo ($activePage ?? '') === 'home' ? ' active' : ''; ?>">Home</a></li>
                            <li class="nav-item"><a href="products.php" class="nav-link modern-nav-link<?php echo ($activePage ?? '') === 'products' ? ' active' : ''; ?>">Shop</a></li>
                            <li class="nav-item"><a href="orders.php" class="nav-link modern-nav-link<?php echo ($activePage ?? '') === 'orders' ? ' active' : ''; ?>">My Orders</a></li>
                        </ul>
                    </div>

                    <div class="modern-nav-actions ms-lg-3">
                        <!-- Cart -->
                        <a href="cart.php" class="modern-icon-btn">
                            <i class="bi bi-cart" style="font-size: 1.25rem;"></i>
                            <span class="modern-cart-badge" id="cart-count-badge" style="<?php echo $cart_count == 0 ? 'display: none;' : ''; ?>"><?php echo $cart_count; ?></span>
                        </a>

                        <!-- User Menu -->
                        <?php if(isset($_SESSION['user_id'])): ?>
                            <a href="profile.php" class="modern-icon-btn">
                                <i class="bi bi-person" style="font-size: 1.25rem;"></i>
                            </a>
                            <a href="logout.php" class="modern-icon-btn" title="Logout">
                                <i class="bi bi-box-arrow-right" style="font-size: 1.25rem;"></i>
                            </a>
                        <?php else: ?>
                            <a href="login.php" class="modern-icon-btn" title="Login">
                                <i class="bi bi-person" style="font-size: 1.25rem;"></i>
                            </a>
                        <?php endif; ?>
                        <button class="navbar-toggler modern-navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#modernMobileNav" aria-controls="modernMobileNav" aria-expanded="false" aria-label="Toggle navigation">
                            <span class="navbar-toggler-icon"></span>
                        </button>
                    </div>
                </div>
            </nav>
        <?php endif; ?>
        <main<?php echo $mainClassAttr; ?>>