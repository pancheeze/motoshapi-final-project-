<?php
// Simple Ollama-powered chatbot endpoint for Motoshapi.
// POST JSON: {"message":"..."}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

$message = isset($data['message']) ? trim((string)$data['message']) : '';
if ($message === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Missing message']);
    exit;
}

function ms_is_site_related_message(string $text): bool {
    $t = mb_strtolower(trim($text), 'UTF-8');
    if ($t === '') {
        return false;
    }

    // Allow basic greetings / conversational openers.
    $greetings = [
        'hi', 'hello', 'hey', 'good morning', 'good afternoon', 'good evening',
        'help', 'support', 'thanks', 'thank you'
    ];
    foreach ($greetings as $g) {
        if ($t === $g || str_starts_with($t, $g . ' ') || str_starts_with($t, $g . '!')) {
            return true;
        }
    }

    // Allow Motoshapi/ecommerce-related topics.
    $keywords = [
        'motoshapi',
        'website', 'site',
        // Product terms / brands (helps avoid false rejections like "how much is Motul?")
        'motul', 'racingboy', 'pirelli', 'bridgestone', 'petron', 'shell',
        'mags', 'tires', 'tire', 'oil',
        'product', 'products', 'price', 'cost', 'php', 'peso', '₱',
        'how much', 'how much is',
        'cart', 'checkout', 'buy', 'buy now',
        'order', 'orders', 'track', 'status',
        'payment', 'pay', 'cod', 'cash on delivery', 'paypal',
        'delivery', 'shipping', 'address',
        'return', 'refund', 'exchange',
        'login', 'log in', 'signin', 'sign in', 'register', 'sign up', 'signup', 'profile', 'account',
        'password', 'forgot', 'reset',
        'stock', 'available', 'variation', 'size', 'color',
        'featured', 'category', 'categories',
        // FAQ related
        'faq', 'faqs', 'frequently asked', 'common questions', 'help', 'question', 'questions',
        // Admin + site features
        'admin', 'dashboard', 'settings',
        'sms', 'email', 'notification',
        'about us', 'about', 'team', 'contact',
        'who made', 'who created', 'creators'
    ];

    foreach ($keywords as $kw) {
        if ($kw !== '' && str_contains($t, $kw)) {
            return true;
        }
    }

    // Allow common site-scope phrasing even without explicit keywords.
    if (str_contains($t, 'website') && (str_contains($t, 'who') || str_contains($t, 'what') || str_contains($t, 'how') || str_contains($t, 'where'))) {
        return true;
    }

    return false;
}

if (!ms_is_site_related_message($message)) {
    echo json_encode([
        'reply' => "I can only help with Motoshapi store questions (products, prices, cart, checkout, COD payment, orders, and accounts). Please ask something related to our website.",
    ]);
    exit;
}

require_once dirname(__DIR__) . '/config/connect.php';
require_once dirname(__DIR__) . '/config/currency.php';

function ms_env_bool(string $key, bool $default = false): bool {
    $val = getenv($key);
    if ($val === false) return $default;
    $v = mb_strtolower(trim((string)$val), 'UTF-8');
    if ($v === '') return $default;
    return in_array($v, ['1', 'true', 'yes', 'y', 'on'], true);
}

function ms_is_categories_question(string $text): bool {
    $t = mb_strtolower(trim($text), 'UTF-8');
    if ($t === '') return false;
    $needles = ['category', 'categories', 'product category', 'catalog'];
    foreach ($needles as $n) {
        if (str_contains($t, $n)) return true;
    }
    return false;
}

function ms_get_categories_reply(PDO $conn): string {
    try {
        $stmt = $conn->query("SELECT name, description FROM categories ORDER BY id ASC");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!$rows) {
            return 'No categories found right now.';
        }
        $lines = [];
        foreach ($rows as $r) {
            $name = trim((string)($r['name'] ?? ''));
            if ($name === '') continue;
            $lines[] = '- ' . $name;
        }
        if (empty($lines)) {
            return 'No categories found right now.';
        }
        return "Here are our categories:\n" . implode("\n", $lines) . "\n\nYou can browse them in Products.";
    } catch (Throwable $e) {
        return 'I could not load categories right now.';
    }
}

function ms_is_featured_question(string $text): bool {
    $t = mb_strtolower(trim($text), 'UTF-8');
    if ($t === '') return false;
    $needles = ['featured', 'best seller', 'bestseller', 'recommended', 'top products'];
    foreach ($needles as $n) {
        if (str_contains($t, $n)) return true;
    }
    return false;
}

function ms_get_featured_products_reply(PDO $conn): string {
    try {
        $stmt = $conn->query("SELECT name, price, stock FROM products WHERE is_active = 1 AND featured = 1 ORDER BY updated_at DESC, created_at DESC LIMIT 10");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!$rows) {
            return 'No featured products are set right now.';
        }
        $lines = [];
        foreach ($rows as $r) {
            $name = trim((string)($r['name'] ?? ''));
            $price = $r['price'] ?? null;
            $stock = (int)($r['stock'] ?? 0);
            if ($name === '' || $price === null) continue;
            $lines[] = '- ' . $name . ' — ' . format_price($price) . ' • Stock: ' . $stock;
        }
        if (empty($lines)) {
            return 'No featured products are set right now.';
        }
        return "Here are our featured products:\n" . implode("\n", $lines);
    } catch (Throwable $e) {
        return 'I could not load featured products right now.';
    }
}

function ms_is_about_question(string $text): bool {
    $t = mb_strtolower(trim($text), 'UTF-8');
    if ($t === '') return false;

    // Be strict: avoid matching generic "about" (e.g., "how about RacingBoy mags").
    $needles = ['about us', 'team', 'creators', 'who made', 'who created', 'who built'];
    foreach ($needles as $n) {
        if (str_contains($t, $n)) return true;
    }

    // Allow "about" only when it's clearly about the site/team.
    if (str_contains($t, 'about') && (str_contains($t, 'motoshapi') || str_contains($t, 'website') || str_contains($t, 'site') || str_contains($t, 'team') || str_contains($t, 'us'))) {
        return true;
    }

    return false;
}

function ms_is_product_details_question(string $text): bool {
    $t = mb_strtolower(trim($text), 'UTF-8');
    if ($t === '') return false;

    // Common vague phrasing that should map to product details.
    $phrases = [
        'how about', 'what about', 'tell me about',
        'details', 'more info', 'more information',
        'price', 'how much', 'cost'
    ];
    foreach ($phrases as $p) {
        if (str_contains($t, $p)) return true;
    }

    // If it mentions product-ish terms, treat as product question.
    $productish = ['product', 'item', 'mags', 'tires', 'tire', 'oil', 'racingboy', 'motul', 'pirelli', 'bridgestone', 'petron', 'shell'];
    foreach ($productish as $p) {
        if (str_contains($t, $p)) return true;
    }

    return false;
}

function ms_extract_product_details_query(string $text): string {
    $t = trim($text);
    if ($t === '') return '';

    if (preg_match('/\"([^\"]{2,200})\"/u', $t, $m)) {
        return trim($m[1]);
    }

    $patterns = [
        '/\bhow\s+about\s+(.+)/iu',
        '/\bwhat\s+about\s+(.+)/iu',
        '/\btell\s+me\s+about\s+(.+)/iu',
        '/\bhow\s+much\s+(?:is|are)\s+(.+)/iu',
        '/\bprice\s+(?:of|for)\s+(.+)/iu',
        '/\bdetails\s+(?:for|of)?\s*(.+)/iu',
    ];
    foreach ($patterns as $p) {
        if (preg_match($p, $t, $m)) {
            $candidate = trim((string)($m[1] ?? ''));
            $candidate = preg_replace('/[\?\!\.,;:]+$/u', '', $candidate);
            return trim($candidate);
        }
    }

    return trim(preg_replace('/[\?\!\.,;:]+$/u', '', mb_strtolower($t, 'UTF-8')));
}

function ms_get_product_details_reply(PDO $conn, string $query): string {
    $query = trim($query);
    if ($query === '' || mb_strlen($query, 'UTF-8') < 2) {
        return 'Which product do you mean? You can say a name like: "RacingBoy mags" or "Motul oil".';
    }

    try {
        $stop = [
            'how', 'much', 'is', 'are', 'the', 'a', 'an', 'of', 'for', 'to',
            'price', 'cost', 'details', 'detail', 'about', 'what', 'which', 'tell', 'me',
            'please', 'show', 'info', 'information'
        ];

        $terms = preg_split('/\s+/u', $query, -1, PREG_SPLIT_NO_EMPTY);
        $terms = array_values(array_filter(array_map(function ($t) use ($stop) {
            $t = trim((string)$t);
            $t = preg_replace('/[^\p{L}\p{N}\-]+/u', '', $t);
            $tl = mb_strtolower($t, 'UTF-8');
            if ($tl !== '' && in_array($tl, $stop, true)) {
                return '';
            }
            return $t;
        }, $terms), function ($t) {
            return mb_strlen($t, 'UTF-8') >= 3;
        }));

        $sql = "SELECT id, name, description, price, stock FROM products WHERE is_active = 1";
        $params = [];
        if (!empty($terms)) {
            foreach ($terms as $i => $term) {
                $key = ':t' . $i;
                $sql .= " AND name LIKE $key";
                $params[$key] = '%' . $term . '%';
            }
        } else {
            $sql .= " AND name LIKE :q";
            $params[':q'] = '%' . $query . '%';
        }
        $sql .= " ORDER BY featured DESC, created_at DESC LIMIT 5";

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!$products) {
            return 'I could not find a product matching that. Try a shorter keyword (example: "RacingBoy mags" or "Motul").';
        }

        $parts = [];
        foreach ($products as $p) {
            $id = (int)($p['id'] ?? 0);
            $name = trim((string)($p['name'] ?? ''));
            $price = $p['price'] ?? null;
            $stock = (int)($p['stock'] ?? 0);
            if ($id <= 0 || $name === '' || $price === null) continue;

            $line = $name . "\nPrice: " . format_price($price) . "\nStock: " . $stock;

            $vStmt = $conn->prepare("SELECT variation, stock FROM variations WHERE product_id = :pid AND is_active = 1 ORDER BY id ASC");
            $vStmt->execute([':pid' => $id]);
            $vars = $vStmt->fetchAll(PDO::FETCH_ASSOC);
            if ($vars) {
                $vParts = [];
                foreach ($vars as $v) {
                    $vName = trim((string)($v['variation'] ?? ''));
                    $vStock = (int)($v['stock'] ?? 0);
                    if ($vName === '') continue;
                    $vParts[] = $vName . ' (' . $vStock . ')';
                }
                if (!empty($vParts)) {
                    $line .= "\nVariations: " . implode(', ', $vParts);
                }
            }

            $parts[] = $line;
        }

        if (empty($parts)) {
            return 'I found matches, but could not format product details.';
        }

        return implode("\n\n", $parts);
    } catch (Throwable $e) {
        return 'I could not load product details right now.';
    }
}

function ms_get_about_us_reply(PDO $conn): string {
    try {
        $stmt = $conn->query("SELECT name FROM about_us ORDER BY id ASC");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!$rows) {
            return 'Our About Us information is not available right now.';
        }
        $names = [];
        foreach ($rows as $r) {
            $name = trim((string)($r['name'] ?? ''));
            if ($name !== '') $names[] = $name;
        }
        if (empty($names)) {
            return 'Our About Us information is not available right now.';
        }
        return 'Motoshapi team: ' . implode(', ', $names) . '.';
    } catch (Throwable $e) {
        return 'Our About Us information is not available right now.';
    }
}

function ms_is_password_help_question(string $text): bool {
    $t = mb_strtolower(trim($text), 'UTF-8');
    if ($t === '') return false;
    $needles = ['forgot password', 'reset password', 'password reset', 'change password', 'cannot login', 'can\'t login', 'cant login'];
    foreach ($needles as $n) {
        if (str_contains($t, $n)) return true;
    }
    // also: any question containing both "password" and "reset"/"forgot"
    if (str_contains($t, 'password') && (str_contains($t, 'forgot') || str_contains($t, 'reset'))) {
        return true;
    }
    return false;
}

function ms_password_help_reply(): string {
    return "To reset your password:\n" .
        "1) Go to the Login page and click 'Forgot Password'\n" .
        "2) Enter your email address\n" .
        "3) Check your email for the reset link and follow it\n\n" .
        "If you don't receive an email, double-check the email address you registered with and try again.";
}

function ms_is_checkout_help_question(string $text): bool {
    $t = mb_strtolower(trim($text), 'UTF-8');
    if ($t === '') return false;
    $needles = ['checkout', 'place order', 'how to order', 'buy now', 'how to buy', 'how do i buy', 'how to checkout'];
    foreach ($needles as $n) {
        if (str_contains($t, $n)) return true;
    }
    return false;
}

function ms_checkout_help_reply(): string {
    return "How to place an order:\n" .
        "1) Browse Products and open an item\n" .
        "2) Add to Cart (or use Buy Now)\n" .
        "3) Go to Cart → Checkout\n" .
        "4) Fill in shipping information\n" .
        "5) Choose a payment method (COD or PayPal if enabled)\n" .
        "6) Confirm the order\n\n" .
        "If you tell me the product name, I can check stock before you checkout.";
}

function ms_is_order_help_question(string $text): bool {
    $t = mb_strtolower(trim($text), 'UTF-8');
    if ($t === '') return false;
    $needles = ['order status', 'track order', 'tracking', 'my order', 'my orders', 'where is my order', 'cancel order'];
    foreach ($needles as $n) {
        if (str_contains($t, $n)) return true;
    }
    return false;
}

function ms_order_help_reply(): string {
    return "To check your order status:\n" .
        "1) Log in to your account\n" .
        "2) Open 'My Orders' to see your latest orders and their status\n\n" .
        "If you have an order number, share it and I can guide you where to find it in the Orders page.";
}

// ===== FAQ Functions =====
function ms_is_faq_question(string $text): bool {
    $t = mb_strtolower(trim($text), 'UTF-8');
    if ($t === '') return false;
    
    $needles = [
        'faq', 'faqs', 'frequently asked', 'common questions',
        'what can you help', 'what can i ask', 'how can you help',
        'what do you do', 'what can you do', 'help me',
        'show me faq', 'show faq', 'list faq', 'questions'
    ];
    
    foreach ($needles as $n) {
        if (str_contains($t, $n)) return true;
    }
    
    // Match exact "help" standalone
    if ($t === 'help' || $t === '?' || $t === 'help me') {
        return true;
    }
    
    return false;
}

function ms_get_faq_list(): array {
    return [
        [
            'question' => 'How do I place an order?',
            'answer' => "To place an order:\n1. Browse products and click 'Add to Cart'\n2. Go to Cart and review items\n3. Click 'Checkout'\n4. Fill in shipping details\n5. Choose payment method (COD or PayPal)\n6. Confirm your order!"
        ],
        [
            'question' => 'What payment methods do you accept?',
            'answer' => "We accept:\n• Cash on Delivery (COD) - Pay when you receive\n• PayPal - Secure online payment\n\nPayment methods may vary. Check checkout for available options."
        ],
        [
            'question' => 'How can I track my order?',
            'answer' => "To track your order:\n1. Log in to your account\n2. Go to 'My Orders'\n3. View order status and details\n\nYou'll also receive SMS notifications about your order status."
        ],
        [
            'question' => 'How do I reset my password?',
            'answer' => "To reset your password:\n1. Go to the login page\n2. Click 'Forgot Password'\n3. Enter your email address\n4. Check your email for reset link\n5. Create a new password"
        ],
        [
            'question' => 'How do I create an account?',
            'answer' => "To create an account:\n1. Click 'Register' or the person icon\n2. Fill in your username, email, and password\n3. Add your phone number (optional)\n4. Click 'Sign Up'\n\nYou can now shop and track orders!"
        ],
        [
            'question' => 'What are featured products?',
            'answer' => "Featured products are our top picks and best sellers. They appear on the homepage and are handpicked for quality and popularity. Check them out for great deals!"
        ],
        [
            'question' => 'How do I check product stock?',
            'answer' => "You can check stock by:\n1. Viewing the product page (shows stock count)\n2. Asking me! Just say: 'stock of [product name]'\n\nExample: 'stock of Motul oil'"
        ],
        [
            'question' => 'Do you offer refunds or returns?',
            'answer' => "For returns and refunds:\n• Contact us within 7 days of delivery\n• Product must be unused and in original packaging\n• Defective items will be replaced\n\nContact our support for assistance."
        ],
        [
            'question' => 'How long does delivery take?',
            'answer' => "Delivery times vary by location:\n• Metro Manila: 2-3 business days\n• Provincial: 5-7 business days\n\nYou'll receive SMS updates on your delivery status."
        ],
        [
            'question' => 'How do I contact support?',
            'answer' => "You can reach us by:\n• Using this chatbot for quick answers\n• Checking the 'About Us' section\n• Sending us a message through SMS\n\nWe're here to help!"
        ]
    ];
}

function ms_get_faq_menu_reply(): string {
    $faqs = ms_get_faq_list();
    $lines = ["📋 **Frequently Asked Questions**\n\nHere are common questions I can help with:\n"];
    
    foreach ($faqs as $i => $faq) {
        $num = $i + 1;
        $lines[] = "{$num}. {$faq['question']}";
    }
    
    $lines[] = "\n💡 **Tip:** Ask me any of these questions, or type a number (1-" . count($faqs) . ") for the answer!";
    $lines[] = "\nYou can also ask about: products, prices, stock, categories, checkout, or orders.";
    
    return implode("\n", $lines);
}

function ms_is_faq_number_question(string $text): bool {
    $t = trim($text);
    // Check if it's just a number 1-10
    return preg_match('/^[1-9]$|^10$/', $t) === 1;
}

function ms_get_faq_by_number(int $num): ?string {
    $faqs = ms_get_faq_list();
    $index = $num - 1;
    
    if (isset($faqs[$index])) {
        return "**{$faqs[$index]['question']}**\n\n{$faqs[$index]['answer']}";
    }
    
    return null;
}

function ms_match_faq_question(string $text): ?string {
    $t = mb_strtolower(trim($text), 'UTF-8');
    $faqs = ms_get_faq_list();
    
    // Keywords to match each FAQ
    $matchers = [
        0 => ['place order', 'how to order', 'ordering', 'make order', 'buy something'],
        1 => ['payment method', 'payment options', 'how to pay', 'accept payment', 'cod', 'paypal', 'pay'],
        2 => ['track order', 'order status', 'where is my order', 'order tracking', 'my order'],
        3 => ['reset password', 'forgot password', 'change password', 'lost password'],
        4 => ['create account', 'sign up', 'register', 'new account', 'registration'],
        5 => ['featured product', 'best seller', 'recommended', 'popular'],
        6 => ['check stock', 'product stock', 'availability', 'in stock'],
        7 => ['refund', 'return', 'exchange', 'money back'],
        8 => ['delivery time', 'shipping time', 'how long', 'when will'],
        9 => ['contact', 'support', 'help', 'customer service', 'reach you']
    ];
    
    foreach ($matchers as $index => $keywords) {
        foreach ($keywords as $kw) {
            if (str_contains($t, $kw) && isset($faqs[$index])) {
                return "**{$faqs[$index]['question']}**\n\n{$faqs[$index]['answer']}";
            }
        }
    }
    
    return null;
}

function ms_get_site_map_text(): string {
    // Keep this short and non-sensitive; do not include any credentials.
    return "Site pages (quick guide):\n" .
        "- Home: index.php\n" .
        "- Products: products.php (browse catalog)\n" .
        "- Cart: cart.php\n" .
        "- Checkout: checkout.php\n" .
        "- Buy Now: buy_now.php\n" .
        "- Account: login.php / register.php / profile.php\n" .
        "- Password reset: forgot_password.php\n" .
        "- My Orders: orders.php\n" .
        "- Admin: admin/login.php (inventory, orders, settings)";
}

function ms_local_fallback_reply(PDO $conn, string $message): string {
    // This runs only when Ollama is disabled/unavailable. Keep it accurate and site-scoped.
    $t = trim($message);
    if ($t === '') {
        return 'Ask me about products, prices, stock, categories, payment methods, checkout, or your orders.';
    }

    // If message looks like a product question, try to return product details.
    if (ms_is_product_details_question($t) || str_contains(mb_strtolower($t, 'UTF-8'), 'how much') || str_contains(mb_strtolower($t, 'UTF-8'), 'price')) {
        $q = ms_extract_product_details_query($t);
        return ms_get_product_details_reply($conn, $q);
    }

    // If they didn't use the right keywords, give a helpful, non-blocking menu.
    $modes = ms_get_active_payment_modes($conn);
    $pay = ms_format_payment_modes_reply($modes);

    return "I can help with Motoshapi questions like:\n" .
        "- Price/stock of a product (example: how much is Motul? / stock of RacingBoy mags)\n" .
        "- Categories and featured products\n" .
        "- Checkout steps and payment methods\n" .
        "- Password reset and order status\n\n" .
        $pay;
}

function ms_get_active_payment_modes(PDO $conn): array {
    try {
        $stmt = $conn->query("SELECT mode_name, mode_code FROM payment_modes WHERE is_active = 1 ORDER BY id ASC");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $modes = [];
        foreach ($rows as $row) {
            $name = trim((string)($row['mode_name'] ?? ''));
            $code = trim((string)($row['mode_code'] ?? ''));
            if ($name === '' && $code !== '') {
                $name = strtoupper($code);
            }
            if ($name !== '' || $code !== '') {
                $modes[] = ['mode_name' => $name, 'mode_code' => $code];
            }
        }
        return $modes;
    } catch (Throwable $e) {
        return [];
    }
}

function ms_is_payment_question(string $text): bool {
    $t = mb_strtolower(trim($text), 'UTF-8');
    if ($t === '') return false;
    $needles = [
        'payment', 'payment mode', 'payment methods', 'pay', 'how to pay',
        'cod', 'cash on delivery', 'paypal'
    ];
    foreach ($needles as $n) {
        if (str_contains($t, $n)) return true;
    }
    return false;
}

function ms_format_payment_modes_reply(array $modes): string {
    if (empty($modes)) {
        return "Payment methods are not available right now (not configured).";
    }

    $names = [];
    foreach ($modes as $m) {
        $name = trim((string)($m['mode_name'] ?? ''));
        $code = trim((string)($m['mode_code'] ?? ''));
        if ($name === '' && $code !== '') {
            $name = strtoupper($code);
        }
        if ($name !== '') {
            $names[] = $name;
        }
    }

    $names = array_values(array_unique($names));
    if (empty($names)) {
        return "Payment methods are not available right now (not configured).";
    }

    return 'Available payment methods: ' . implode(', ', $names) . '.';
}

function ms_is_stock_question(string $text): bool {
    $t = mb_strtolower(trim($text), 'UTF-8');
    if ($t === '') return false;
    $needles = [
        'stock', 'in stock', 'available', 'availability', 'stocks'
    ];
    foreach ($needles as $n) {
        if (str_contains($t, $n)) return true;
    }
    return false;
}

function ms_extract_product_query(string $text): string {
    $t = trim($text);
    if ($t === '') return '';

    // Prefer quoted product names: stock of "..."
    if (preg_match('/"([^"]{2,200})"/u', $t, $m)) {
        return trim($m[1]);
    }

    $lower = mb_strtolower($t, 'UTF-8');
    $patterns = [
        // "how many stocks of motul have?" / "how many stock of motul left?"
        '/\bhow\s+many\s+(?:stocks?|items?)\s+of\s+(.+?)(?:\s+(?:do|does)\s+you\s+have|\s+have|\s+left|\s+available)?\b/iu',
        '/\bhow\s+many\s+(?:stocks?|items?)\s+(?:does|do)\s+(.+?)\s+(?:have|left)\b/iu',
        '/\bstock\s+of\s+(.+)/iu',
        '/\bavailability\s+of\s+(.+)/iu',
        '/\bis\s+(.+)\s+available\b/iu',
        '/\bdo\s+you\s+have\s+(.+)/iu',
        '/\bavailable\s+(?:for|of)?\s*(.+)/iu',
    ];
    foreach ($patterns as $p) {
        if (preg_match($p, $t, $m)) {
            $candidate = trim((string)($m[1] ?? ''));
            $candidate = preg_replace('/[\?\!\.,;:]+$/u', '', $candidate);
            // Remove common trailing filler words that accidentally get captured.
            $candidate = preg_replace('/\s+\b(have|left|available|in\s+stock|stocks?)\b\s*$/iu', '', $candidate);
            $candidate = trim($candidate);
            return $candidate;
        }
    }

    // If they only asked a generic "stocks available" question, don't guess.
    if ($lower === 'stock' || $lower === 'stocks' || $lower === 'stocks available' || $lower === 'stock available') {
        return '';
    }

    // As a fallback, strip common filler words and use the remainder.
    $candidate = $lower;
    $candidate = preg_replace('/\b(stock|stocks|available|availability|in\s+stock|how\s+many|left|do\s+you\s+have|is|there|a|an|the|please|can\s+you|check)\b/iu', ' ', $candidate);
    $candidate = preg_replace('/\b(have|does|do|many|items?)\b/iu', ' ', $candidate);
    $candidate = preg_replace('/\s+/u', ' ', $candidate);
    $candidate = trim($candidate);
    if (mb_strlen($candidate, 'UTF-8') < 3) {
        return '';
    }
    return $candidate;
}

function ms_get_product_stock_reply(PDO $conn, string $query): string {
    $query = trim($query);

    // If no specific product was requested, return a helpful snapshot.
    if ($query === '') {
        try {
            $stmt = $conn->query("SELECT id, name, stock, price, featured, is_active FROM products WHERE is_active = 1 ORDER BY (stock > 0) DESC, featured DESC, created_at DESC LIMIT 10");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (!$rows) {
                return 'No active products found right now.';
            }

            $lines = [];
            foreach ($rows as $r) {
                $name = trim((string)($r['name'] ?? ''));
                $stock = (int)($r['stock'] ?? 0);
                $price = $r['price'] ?? null;
                if ($name === '') continue;
                $suffix = 'Stock: ' . $stock;
                if ($price !== null) {
                    $suffix .= ' • ' . format_price($price);
                }
                $lines[] = '- ' . $name . ' — ' . $suffix;
            }

            if (empty($lines)) {
                return 'No active products found right now.';
            }

            return "Here are some currently listed items and their base stock:\n" . implode("\n", $lines) . "\n\nTip: ask like: stock of \"Pirelli Diablo Rosso\"";
        } catch (Throwable $e) {
            return 'I could not load stock information right now.';
        }
    }

    // Product-specific lookup.
    try {
        $terms = preg_split('/\s+/u', $query, -1, PREG_SPLIT_NO_EMPTY);
        $terms = array_values(array_filter(array_map(function ($t) {
            $t = trim((string)$t);
            $t = preg_replace('/[^\p{L}\p{N}\-]+/u', '', $t);
            return $t;
        }, $terms), function ($t) {
            return mb_strlen($t, 'UTF-8') >= 3;
        }));

        // If we ended up with no good terms, fall back to a simple LIKE.
        $sql = "SELECT id, name, stock, price FROM products WHERE is_active = 1";
        $params = [];
        if (!empty($terms)) {
            foreach ($terms as $i => $term) {
                $key = ':t' . $i;
                $sql .= " AND name LIKE $key";
                $params[$key] = '%' . $term . '%';
            }
        } else {
            $sql .= " AND name LIKE :q";
            $params[':q'] = '%' . $query . '%';
        }
        $sql .= " ORDER BY featured DESC, created_at DESC LIMIT 5";

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$products) {
            return 'I could not find a product matching that name. Try quoting the product name (example: stock of "Motul").';
        }

        $parts = [];
        foreach ($products as $p) {
            $id = (int)($p['id'] ?? 0);
            $name = trim((string)($p['name'] ?? ''));
            $stock = (int)($p['stock'] ?? 0);
            $price = $p['price'] ?? null;
            if ($id <= 0 || $name === '') continue;

            $line = $name . ': base stock ' . $stock;
            if ($price !== null) {
                $line .= ' • ' . format_price($price);
            }

            // If the product has active variations, include them.
            $vStmt = $conn->prepare("SELECT variation, stock, price FROM variations WHERE product_id = :pid AND is_active = 1 ORDER BY id ASC");
            $vStmt->execute([':pid' => $id]);
            $vars = $vStmt->fetchAll(PDO::FETCH_ASSOC);
            if ($vars) {
                $vParts = [];
                foreach ($vars as $v) {
                    $vName = trim((string)($v['variation'] ?? ''));
                    $vStock = (int)($v['stock'] ?? 0);
                    if ($vName === '') continue;
                    $vParts[] = $vName . ' (' . $vStock . ')';
                }
                if (!empty($vParts)) {
                    $line .= "\nVariations: " . implode(', ', $vParts);
                }
            }
            $parts[] = $line;
        }

        if (empty($parts)) {
            return 'I found matches, but could not format stock details.';
        }

        return implode("\n\n", $parts);
    } catch (Throwable $e) {
        return 'I could not check stock right now.';
    }
}

if (!isset($_SESSION['chat_history']) || !is_array($_SESSION['chat_history'])) {
    $_SESSION['chat_history'] = [];
}

// FAQ handling - check first for FAQ menu or specific FAQ questions
if (ms_is_faq_question($message)) {
    $reply = ms_get_faq_menu_reply();
    $_SESSION['chat_history'][] = ['role' => 'user', 'content' => $message];
    $_SESSION['chat_history'][] = ['role' => 'assistant', 'content' => $reply];
    $_SESSION['chat_history'] = array_slice($_SESSION['chat_history'], -24);
    echo json_encode(['reply' => $reply]);
    exit;
}

// FAQ number selection (1-10)
if (ms_is_faq_number_question($message)) {
    $num = (int)$message;
    $reply = ms_get_faq_by_number($num);
    if ($reply === null) {
        $reply = "Invalid FAQ number. Type 'faq' to see the list of questions.";
    }
    $_SESSION['chat_history'][] = ['role' => 'user', 'content' => $message];
    $_SESSION['chat_history'][] = ['role' => 'assistant', 'content' => $reply];
    $_SESSION['chat_history'] = array_slice($_SESSION['chat_history'], -24);
    echo json_encode(['reply' => $reply]);
    exit;
}

// FAQ question match (e.g., clicking/typing the actual question)
$faqMatch = ms_match_faq_question($message);
if ($faqMatch !== null) {
    $reply = $faqMatch;
    $_SESSION['chat_history'][] = ['role' => 'user', 'content' => $message];
    $_SESSION['chat_history'][] = ['role' => 'assistant', 'content' => $reply];
    $_SESSION['chat_history'] = array_slice($_SESSION['chat_history'], -24);
    echo json_encode(['reply' => $reply]);
    exit;
}

// Deterministic answers for common site questions so details stay accurate.
// Product details: catch vague queries like "how about RacingBoy mags".
if (ms_is_product_details_question($message) && !ms_is_about_question($message) && !ms_is_payment_question($message) && !ms_is_stock_question($message)) {
    $q = ms_extract_product_details_query($message);
    $reply = ms_get_product_details_reply($conn, $q);
    $_SESSION['chat_history'][] = ['role' => 'user', 'content' => $message];
    $_SESSION['chat_history'][] = ['role' => 'assistant', 'content' => $reply];
    $_SESSION['chat_history'] = array_slice($_SESSION['chat_history'], -24);
    echo json_encode(['reply' => $reply]);
    exit;
}

if (ms_is_password_help_question($message)) {
    $reply = ms_password_help_reply();
    $_SESSION['chat_history'][] = ['role' => 'user', 'content' => $message];
    $_SESSION['chat_history'][] = ['role' => 'assistant', 'content' => $reply];
    $_SESSION['chat_history'] = array_slice($_SESSION['chat_history'], -24);
    echo json_encode(['reply' => $reply]);
    exit;
}

if (ms_is_checkout_help_question($message)) {
    $reply = ms_checkout_help_reply();
    $_SESSION['chat_history'][] = ['role' => 'user', 'content' => $message];
    $_SESSION['chat_history'][] = ['role' => 'assistant', 'content' => $reply];
    $_SESSION['chat_history'] = array_slice($_SESSION['chat_history'], -24);
    echo json_encode(['reply' => $reply]);
    exit;
}

if (ms_is_order_help_question($message)) {
    $reply = ms_order_help_reply();
    $_SESSION['chat_history'][] = ['role' => 'user', 'content' => $message];
    $_SESSION['chat_history'][] = ['role' => 'assistant', 'content' => $reply];
    $_SESSION['chat_history'] = array_slice($_SESSION['chat_history'], -24);
    echo json_encode(['reply' => $reply]);
    exit;
}

if (ms_is_categories_question($message)) {
    $reply = ms_get_categories_reply($conn);
    $_SESSION['chat_history'][] = ['role' => 'user', 'content' => $message];
    $_SESSION['chat_history'][] = ['role' => 'assistant', 'content' => $reply];
    $_SESSION['chat_history'] = array_slice($_SESSION['chat_history'], -24);
    echo json_encode(['reply' => $reply]);
    exit;
}

if (ms_is_featured_question($message)) {
    $reply = ms_get_featured_products_reply($conn);
    $_SESSION['chat_history'][] = ['role' => 'user', 'content' => $message];
    $_SESSION['chat_history'][] = ['role' => 'assistant', 'content' => $reply];
    $_SESSION['chat_history'] = array_slice($_SESSION['chat_history'], -24);
    echo json_encode(['reply' => $reply]);
    exit;
}

if (ms_is_about_question($message)) {
    $reply = ms_get_about_us_reply($conn);
    $_SESSION['chat_history'][] = ['role' => 'user', 'content' => $message];
    $_SESSION['chat_history'][] = ['role' => 'assistant', 'content' => $reply];
    $_SESSION['chat_history'] = array_slice($_SESSION['chat_history'], -24);
    echo json_encode(['reply' => $reply]);
    exit;
}

// Deterministic answers for payment + stock, so the chatbot stays accurate.
if (ms_is_payment_question($message)) {
    $modes = ms_get_active_payment_modes($conn);
    $reply = ms_format_payment_modes_reply($modes);
    $_SESSION['chat_history'][] = ['role' => 'user', 'content' => $message];
    $_SESSION['chat_history'][] = ['role' => 'assistant', 'content' => $reply];
    $_SESSION['chat_history'] = array_slice($_SESSION['chat_history'], -24);
    echo json_encode(['reply' => $reply]);
    exit;
}

if (ms_is_stock_question($message)) {
    $q = ms_extract_product_query($message);
    $reply = ms_get_product_stock_reply($conn, $q);
    $_SESSION['chat_history'][] = ['role' => 'user', 'content' => $message];
    $_SESSION['chat_history'][] = ['role' => 'assistant', 'content' => $reply];
    $_SESSION['chat_history'] = array_slice($_SESSION['chat_history'], -24);
    echo json_encode(['reply' => $reply]);
    exit;
}

$productLines = [];
try {
    $stmt = $conn->query("SELECT name, price FROM products ORDER BY featured DESC, created_at DESC LIMIT 10");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $name = $row['name'] ?? '';
        $price = $row['price'] ?? null;
        if ($name === '' || $price === null) {
            continue;
        }
        $productLines[] = '- ' . $name . ' — ' . format_price($price);
    }
} catch (Throwable $e) {
}

$paymentModes = ms_get_active_payment_modes($conn);
$paymentModesText = ms_format_payment_modes_reply($paymentModes);

$stockLines = [];
try {
    $stmt = $conn->query("SELECT name, stock FROM products WHERE is_active = 1 ORDER BY (stock > 0) DESC, featured DESC, created_at DESC LIMIT 8");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $name = trim((string)($row['name'] ?? ''));
        $stock = (int)($row['stock'] ?? 0);
        if ($name === '') {
            continue;
        }
        $stockLines[] = '- ' . $name . ' — Stock: ' . $stock;
    }
} catch (Throwable $e) {
}

$system = "You are the official ecommerce support assistant for the Motoshapi motorcycle parts website. " .
    "Only answer questions related to the Motoshapi site: products, prices, stock/variations, cart, checkout, COD payment, orders, and accounts. " .
    "If the user asks anything unrelated (general knowledge, coding help, politics, math, etc.), refuse briefly and ask them to ask a Motoshapi-related question. " .
    $paymentModesText . " " .
    ms_get_site_map_text() . " " .
    "If you are unsure, ask a brief clarifying question or suggest checking the Products page. " .
    "Do not invent policies (shipping, returns) that you don't know; say you don't have that information.";

if (!empty($productLines)) {
    $system .= "\n\nAvailable products (sample):\n" . implode("\n", $productLines);
}

if (!empty($stockLines)) {
    $system .= "\n\nStock snapshot (base stock, sample):\n" . implode("\n", $stockLines);
}

$messages = [];
$messages[] = ['role' => 'system', 'content' => $system];

$history = $_SESSION['chat_history'];
$history = array_slice($history, -12);
foreach ($history as $m) {
    if (!is_array($m) || !isset($m['role'], $m['content'])) {
        continue;
    }
    $role = (string)$m['role'];
    if ($role !== 'user' && $role !== 'assistant') {
        continue;
    }
    $messages[] = ['role' => $role, 'content' => (string)$m['content']];
}

$messages[] = ['role' => 'user', 'content' => $message];

$payload = [
    'model' => 'llama3.2:3b',
    'messages' => $messages,
    'stream' => false,
    'options' => [
        'temperature' => 0.3,
    ],
];

// Allow running without Ollama (for other devices / easier deployments).
// Set MOTOSHAPI_DISABLE_OLLAMA=true in the environment to force fallback mode.
$disableOllama = ms_env_bool('MOTOSHAPI_DISABLE_OLLAMA', false);
if ($disableOllama) {
    $reply = ms_local_fallback_reply($conn, $message);
    $_SESSION['chat_history'][] = ['role' => 'user', 'content' => $message];
    $_SESSION['chat_history'][] = ['role' => 'assistant', 'content' => $reply];
    $_SESSION['chat_history'] = array_slice($_SESSION['chat_history'], -24);
    echo json_encode(['reply' => $reply]);
    exit;
}

$ch = curl_init('http://127.0.0.1:11434/api/chat');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 2,
    CURLOPT_TIMEOUT => 20,
]);

$responseBody = curl_exec($ch);
$curlErr = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($responseBody === false || $httpCode < 200 || $httpCode >= 300) {
    // Ollama is optional; fall back to local deterministic replies.
    $reply = ms_local_fallback_reply($conn, $message);
    $_SESSION['chat_history'][] = ['role' => 'user', 'content' => $message];
    $_SESSION['chat_history'][] = ['role' => 'assistant', 'content' => $reply];
    $_SESSION['chat_history'] = array_slice($_SESSION['chat_history'], -24);
    echo json_encode(['reply' => $reply, 'note' => 'ai_offline']);
    exit;
}

$decoded = json_decode($responseBody, true);
$reply = $decoded['message']['content'] ?? null;
if (!is_string($reply) || trim($reply) === '') {
    $reply = ms_local_fallback_reply($conn, $message);
    $_SESSION['chat_history'][] = ['role' => 'user', 'content' => $message];
    $_SESSION['chat_history'][] = ['role' => 'assistant', 'content' => $reply];
    $_SESSION['chat_history'] = array_slice($_SESSION['chat_history'], -24);
    echo json_encode(['reply' => $reply, 'note' => 'ai_offline']);
    exit;
}

$_SESSION['chat_history'][] = ['role' => 'user', 'content' => $message];
$_SESSION['chat_history'][] = ['role' => 'assistant', 'content' => $reply];
$_SESSION['chat_history'] = array_slice($_SESSION['chat_history'], -24);

echo json_encode(['reply' => $reply]);
