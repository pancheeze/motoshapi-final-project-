# Motoshapi - AI Coding Agent Instructions

## Project Overview
**Motoshapi** is a motorcycle parts e-commerce platform built with traditional PHP/MySQL architecture. The system runs on XAMPP (Apache + MySQL) and integrates multiple external services for payments, notifications, and AI assistance.

**Tech Stack**: PHP 8.2, MySQL, Bootstrap 5, vanilla JavaScript, PDO for database access
**Deployment**: Local XAMPP environment (`http://localhost/motoshapi/`)

## Architecture & Code Organization

### Directory Structure
- `/` - Customer-facing pages (index, products, cart, checkout, orders)
- `/admin/` - Admin dashboard and management pages
- `/config/` - Configuration files (database, PayPal, SMS, currency)
- `/includes/` - Shared PHP components (header, footer, helpers)
- `/email/` - Email service with PHPMailer integration
- `/assets/css/` - Multiple theme files (spare-parts-theme, modern-theme, dark-mode)
- `/assets/js/` - Client-side JavaScript
- `/database/` - SQL schema (`motoshapi_db.sql`) and migration scripts
- `/uploads/` - Product images, logos, hero media

### Database Schema
Key tables: `users`, `admin`, `products`, `categories`, `orders`, `order_items`, `payment_modes`, `sms_logs`, `about_us`

**Session Management**: Uses PHP `$_SESSION` for user auth (`user_id`, `username`), admin auth (`admin_id`), cart data, and PayPal temporary state.

## Critical Patterns & Conventions

### 1. Page Structure Pattern
Every page follows this structure:
```php
<?php
session_start();  // or check: if (session_status() === PHP_SESSION_NONE)
require_once 'config/database.php';  // Provides global $conn (PDO)
require_once 'config/currency.php';  // Provides formatCurrency() function

$title = 'Page Title';
$activePage = 'home|products|orders';  // For nav highlighting
$uiTheme = 'spare';  // 'spare' or leave unset for modern theme
include 'includes/header.php';  // Outputs HTML head + navigation
?>
<!-- Page content -->
<?php include 'includes/footer.php'; ?>
```

**Admin pages** use `admin/includes/header.php` with similar pattern but check `$_SESSION['admin_id']`.

### 2. Database Connection
- **Global PDO instance**: `$conn` from `config/database.php`
- **Credentials**: DB_HOST='localhost', DB_USER='root', DB_PASS='', DB_NAME='motoshapi_db'
- **Error mode**: `PDO::ERRMODE_EXCEPTION`
- Always use prepared statements: `$stmt = $conn->prepare("SELECT...");`

### 3. Currency Handling
- Currency defined in `config/currency.php` (default: PHP - Philippine Peso)
- Use `formatCurrency($amount)` function for display (adds ₱ symbol)
- PayPal integration uses `PAYPAL_CURRENCY_CODE` from config

### 4. Authentication Patterns
**Customer**: Check `isset($_SESSION['user_id'])` - redirect to `login.php` if not set
**Admin**: Check `isset($_SESSION['admin_id'])` - redirect to `admin/login.php` if not set

### 5. Cart Management
- Cart stored in `$_SESSION['cart']` as array: `[product_id => ['quantity' => int, 'price' => float, ...]]`
- Cart count calculated by summing quantities: `foreach($_SESSION['cart'] as $item) { $count += $item['quantity']; }`

## Integration Points

### PayPal Payment Flow
1. **Create order**: `paypal_create_order.php` → `paypal_helper.php` → PayPal API
2. **User approval**: Redirect to PayPal sandbox
3. **Capture payment**: `paypal_capture_order.php` → Store in DB
4. **Helper functions**: `includes/paypal_helper.php` (paypal_get_access_token, paypal_create_order, paypal_capture_order)
5. **Config**: `config/paypal_config.php` (CLIENT_ID, CLIENT_SECRET, sandbox/live mode)

### SMS Integration (SMSGate Android)
- **Gateway**: Android phone running SMSGate app on local network
- **Config**: `config/sms_config.php` (gateway URL, username, password)
- **Helper**: `includes/sms_helper.php` - `sendSMS($recipients, $message)` function
- **Logging**: All SMS stored in `sms_logs` table with status tracking
- **Enable/Disable**: `SMS_ENABLED` constant (default: true)
- **Phone format**: Auto-converts 0-prefixed to +63 format

### Email Integration (PHPMailer + Gmail SMTP)
- **Location**: `email/config/email.php`
- **Credentials**: Use `email/config/email.local.php` (git-ignored) or environment variables
- **Functions**: `sendEmail()`, `sendWelcomeEmail()`, `sendOrderConfirmation()`, etc.
- **SMTP**: smtp.gmail.com:587 with app passwords (no spaces in password)

### AI Chatbot (Ollama)
- **Endpoint**: `chatbot.php` (POST JSON: `{"message": "..."}`)
- **Scope filter**: `ms_is_site_related_message()` only responds to Motoshapi/ecommerce topics
- **Fallback**: Returns polite redirect for off-topic queries
- **Context**: Has access to product catalog, order info, site features

## Development Workflows

### Local Setup
1. Start XAMPP (Apache + MySQL)
2. Import `database/motoshapi_db.sql` via phpMyAdmin
3. Test connection: `http://localhost/motoshapi/test_connection.php`
4. Setup admin: `http://localhost/motoshapi/admin/setup_admin.php`
5. Visit site: `http://localhost/motoshapi/`

### Adding New Products
Use admin interface: `admin/add_product.php` or `admin/products.php`
- Product images go to `/uploads/products/`
- Featured products controlled by `featured` column (1=featured, 0=normal)

### Testing Integrations
- **Email**: `/email/pages/test_email.php`
- **SMS**: `/sms_dashboard.php`
- **PayPal**: Add to cart → checkout → select PayPal (sandbox)
- **Chatbot**: Click widget on homepage

### Theme System
Two UI themes controlled by `$uiTheme` variable:
- **spare** (default): Spare parts aesthetic (`spare-parts-theme.css`)
- **modern**: Clean e-commerce look (`modern-theme.css`)
- Both support dark mode (`dark-mode.css`)

## Common Tasks

### Query Products
```php
$stmt = $conn->prepare("SELECT * FROM products WHERE is_active = 1 AND category_id = ?");
$stmt->execute([$category_id]);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
```

### Create Order
```php
// Insert order
$stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, status, payment_mode_id) VALUES (?, ?, 'pending', ?)");
$stmt->execute([$_SESSION['user_id'], $total, $payment_mode_id]);
$order_id = $conn->lastInsertId();

// Insert order items
foreach ($cart_items as $item) {
    $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
    $stmt->execute([$order_id, $item['product_id'], $item['quantity'], $item['price']]);
}
```

### Send Notifications
```php
// SMS
require_once 'includes/sms_helper.php';
sendSMS($user_phone, sprintf(SMS_ORDER_PLACED, $order_id, formatCurrency($total)));

// Email
require_once 'email/config/email.php';
sendOrderConfirmation($user_email, $order_id, $total);
```

## Important Notes

- **No REST API**: Traditional PHP page-per-route architecture (not SPA)
- **Security**: Plain auth (no bcrypt in demo), sanitize all user inputs
- **File uploads**: Use `move_uploaded_file()` to `/uploads/` subdirectories
- **Error handling**: Try-catch for PDO, log errors with `error_log()`
- **Vendor dependencies**: PHPMailer via Composer (`composer.json`)
- **Payment modes**: COD and PayPal only (stored in `payment_modes` table)

## Documentation References

- Full setup: `docs/QUICK_START.md`
- Integration status: `docs/INTEGRATION_STATUS.md`
- Email setup: `EMAIL_SETUP.md`
- SMS setup: `SMS_SETUP_GUIDE.md`
- Project README: `README.md`
