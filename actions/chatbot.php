<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once dirname(__DIR__) . '/config/connect.php';
require_once dirname(__DIR__) . '/config/currency.php';

header('Content-Type: application/json; charset=utf-8');

$NO_DATA_RESPONSE = "I do not have enough information to answer that.";
$OLLAMA_API_URL = 'http://127.0.0.1:11434/api/chat';
$MODEL = 'llama3.2:3b';
$OLLAMA_TIMEOUT = 30;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'reply' => 'Method not allowed']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

$userMsg = trim((string)($data['message'] ?? $_POST['prompt'] ?? $_POST['message'] ?? ''));
if ($userMsg === '') {
    echo json_encode(['ok' => false, 'reply' => 'Empty message']);
    exit;
}

if ($userMsg === '__FAQ_LIST__') {
    $faqTable = null;
    if (ms_table_exists($conn, 'faqs')) {
        $faqTable = 'faqs';
    } elseif (ms_table_exists($conn, 'faq')) {
        $faqTable = 'faq';
    }

    $faqs = [];
    if ($faqTable !== null) {
        $rows = $conn->query("SELECT question FROM {$faqTable} ORDER BY id ASC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $r) {
            $q = trim((string)($r['question'] ?? ''));
            if ($q !== '') {
                $faqs[] = $q;
            }
        }
    }

    echo json_encode(['ok' => true, 'faqs' => $faqs]);
    exit;
}

$msgNorm = strtolower($userMsg);
$msgNorm = preg_replace('/[^a-z0-9\s]/', '', $msgNorm);
$msgNorm = trim(preg_replace('/\s+/', ' ', $msgNorm));

function ms_terms_from_message(string $msgNorm): array {
    $terms = preg_split('/\s+/', $msgNorm, -1, PREG_SPLIT_NO_EMPTY);
    return array_values(array_filter($terms, function ($t) {
        return strlen($t) >= 3;
    }));
}

function ms_add_section(array &$sections, string $title, array $lines): void {
    if (!empty($lines)) {
        $sections[] = $title . "\n" . implode("\n", $lines);
    }
}

function ms_table_exists(PDO $conn, string $table): bool {
    try {
        $stmt = $conn->prepare("SHOW TABLES LIKE :t");
        $stmt->execute([':t' => $table]);
        return (bool)$stmt->fetchColumn();
    } catch (Throwable $e) {
        return false;
    }
}

$sections = [];
$terms = ms_terms_from_message($msgNorm);

$isCategoryQuery = preg_match('/\bcategory\b|\bcategories\b|\bcatalog\b/', $msgNorm) === 1;
$isFeaturedQuery = preg_match('/\bfeatured\b|\bbest seller\b|\brecommended\b/', $msgNorm) === 1;
$isPaymentQuery = preg_match('/\bpayment\b|\bpay\b|\bcod\b|\bpaypal\b/', $msgNorm) === 1;
$isAboutQuery = preg_match('/\babout\b|\bteam\b|\bcreators\b/', $msgNorm) === 1;
$isVariationQuery = preg_match('/\bvariation\b|\bsize\b|\bcolor\b|\boption\b/', $msgNorm) === 1;
$isFaqQuery = preg_match('/\bfaq\b|\bfaqs\b|\bhelp\b|\bquestions\b|\bhow\b|\bwhat\b|\bwhere\b|\bwhen\b|\bwho\b/', $msgNorm) === 1;

if ($isCategoryQuery || empty($terms)) {
    $rows = $conn->query("SELECT name, description FROM categories ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
    $lines = [];
    foreach ($rows as $r) {
        $name = trim((string)($r['name'] ?? ''));
        $desc = trim((string)($r['description'] ?? ''));
        if ($name !== '') {
            $lines[] = $desc !== '' ? ($name . ' — ' . $desc) : $name;
        }
    }
    ms_add_section($sections, 'Categories:', $lines);
}

if ($isFeaturedQuery) {
    $rows = $conn->query("SELECT name, price, stock FROM products WHERE is_active = 1 AND featured = 1 ORDER BY updated_at DESC, created_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    $lines = [];
    foreach ($rows as $r) {
        $name = trim((string)($r['name'] ?? ''));
        $price = $r['price'] ?? null;
        $stock = (int)($r['stock'] ?? 0);
        if ($name !== '' && $price !== null) {
            $lines[] = $name . ' — ' . format_price($price) . ' • Stock: ' . $stock;
        }
    }
    ms_add_section($sections, 'Featured products:', $lines);
}

if ($isPaymentQuery || empty($terms)) {
    $rows = $conn->query("SELECT mode_name, mode_code FROM payment_modes WHERE is_active = 1 ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
    $lines = [];
    foreach ($rows as $r) {
        $name = trim((string)($r['mode_name'] ?? ''));
        $code = trim((string)($r['mode_code'] ?? ''));
        $label = $name !== '' ? $name : ($code !== '' ? strtoupper($code) : '');
        if ($label !== '') {
            $lines[] = $label;
        }
    }
    ms_add_section($sections, 'Payment modes:', $lines);
}

if (empty($terms)) {
    $rows = $conn->query("SELECT name, price, stock FROM products WHERE is_active = 1 ORDER BY featured DESC, created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    $lines = [];
    foreach ($rows as $r) {
        $name = trim((string)($r['name'] ?? ''));
        $price = $r['price'] ?? null;
        $stock = (int)($r['stock'] ?? 0);
        if ($name !== '' && $price !== null) {
            $lines[] = $name . ' — ' . format_price($price) . ' • Stock: ' . $stock;
        }
    }
    ms_add_section($sections, 'Products:', $lines);
}

if ($isAboutQuery) {
    $rows = $conn->query("SELECT name FROM about_us ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
    $lines = [];
    foreach ($rows as $r) {
        $name = trim((string)($r['name'] ?? ''));
        if ($name !== '') {
            $lines[] = $name;
        }
    }
    ms_add_section($sections, 'Team:', $lines);
}

if ($isFaqQuery) {
    $faqTable = null;
    if (ms_table_exists($conn, 'faqs')) {
        $faqTable = 'faqs';
    } elseif (ms_table_exists($conn, 'faq')) {
        $faqTable = 'faq';
    }

    if ($faqTable !== null) {
        $stmt = $conn->prepare("SELECT question, answer FROM {$faqTable} WHERE LOWER(question) = LOWER(:q) LIMIT 1");
        $stmt->execute([':q' => $userMsg]);
        $exact = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($exact && !empty($exact['answer'])) {
            echo json_encode(['ok' => true, 'reply' => trim((string)$exact['answer'])]);
            exit;
        }

        $faqLines = [];

        if (!empty($terms)) {
            $stmt = $conn->prepare("SELECT question, answer FROM {$faqTable} WHERE question LIKE :q OR answer LIKE :q ORDER BY id ASC LIMIT 10");
            $stmt->execute([':q' => '%' . $msgNorm . '%']);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $rows = $conn->query("SELECT question, answer FROM {$faqTable} ORDER BY id ASC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
        }

        foreach ($rows as $r) {
            $q = trim((string)($r['question'] ?? ''));
            $a = trim((string)($r['answer'] ?? ''));
            if ($q !== '' && $a !== '') {
                $faqLines[] = 'Q: ' . $q . "\nA: " . $a;
            }
        }

        ms_add_section($sections, 'FAQs:', $faqLines);
    }
}

if (!empty($terms)) {
    $sql = "SELECT id, name, description, price, stock FROM products WHERE is_active = 1";
    $params = [];
    $orParts = [];
    foreach ($terms as $i => $term) {
        $key = ':t' . $i;
        $orParts[] = "(name LIKE $key OR description LIKE $key)";
        $params[$key] = '%' . $term . '%';
    }
    if (!empty($orParts)) {
        $sql .= " AND (" . implode(' OR ', $orParts) . ")";
    }
    $sql .= " ORDER BY featured DESC, created_at DESC LIMIT 10";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $matchedProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $lines = [];
    foreach ($matchedProducts as $p) {
        $id = (int)($p['id'] ?? 0);
        $name = trim((string)($p['name'] ?? ''));
        $price = $p['price'] ?? null;
        $stock = (int)($p['stock'] ?? 0);
        $desc = trim((string)($p['description'] ?? ''));
        if ($id <= 0 || $name === '' || $price === null) {
            continue;
        }
        $line = $name . "\nPrice: " . format_price($price) . "\nStock: " . $stock;
        if ($desc !== '') {
            $line .= "\nDescription: " . $desc;
        }

        if ($isVariationQuery) {
            $vStmt = $conn->prepare("SELECT variation, stock FROM variations WHERE product_id = :pid AND is_active = 1 ORDER BY id ASC");
            $vStmt->execute([':pid' => $id]);
            $vars = $vStmt->fetchAll(PDO::FETCH_ASSOC);
            if ($vars) {
                $vParts = [];
                foreach ($vars as $v) {
                    $vName = trim((string)($v['variation'] ?? ''));
                    $vStock = (int)($v['stock'] ?? 0);
                    if ($vName !== '') {
                        $vParts[] = $vName . ' (' . $vStock . ')';
                    }
                }
                if (!empty($vParts)) {
                    $line .= "\nVariations: " . implode(', ', $vParts);
                }
            }
        }

        $lines[] = $line;
    }
    ms_add_section($sections, 'Products:', $lines);
}

if ($isVariationQuery && !empty($terms)) {
    $sql = "SELECT v.variation, v.stock, p.name AS product_name FROM variations v INNER JOIN products p ON p.id = v.product_id WHERE v.is_active = 1";
    $params = [];
    foreach ($terms as $i => $term) {
        $key = ':v' . $i;
        $sql .= " AND (v.variation LIKE $key OR p.name LIKE $key)";
        $params[$key] = '%' . $term . '%';
    }
    $sql .= " ORDER BY p.name ASC, v.variation ASC LIMIT 10";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $lines = [];
    foreach ($rows as $r) {
        $product = trim((string)($r['product_name'] ?? ''));
        $variation = trim((string)($r['variation'] ?? ''));
        $stock = (int)($r['stock'] ?? 0);
        if ($product !== '' && $variation !== '') {
            $lines[] = $product . ' — ' . $variation . ' (Stock: ' . $stock . ')';
        }
    }
    ms_add_section($sections, 'Variations:', $lines);
}

$dbOutput = implode("\n\n", $sections);

if (trim($dbOutput) === '') {
    $rows = $conn->query("SELECT name, price, stock FROM products WHERE is_active = 1 ORDER BY featured DESC, created_at DESC LIMIT 8")->fetchAll(PDO::FETCH_ASSOC);
    $lines = [];
    foreach ($rows as $r) {
        $name = trim((string)($r['name'] ?? ''));
        $price = $r['price'] ?? null;
        $stock = (int)($r['stock'] ?? 0);
        if ($name !== '' && $price !== null) {
            $lines[] = $name . ' — ' . format_price($price) . ' • Stock: ' . $stock;
        }
    }
    if (!empty($lines)) {
        $dbOutput = "Products:\n" . implode("\n", $lines);
    }
}

if (trim($dbOutput) === '') {
    echo json_encode(['ok' => true, 'reply' => $NO_DATA_RESPONSE]);
    exit;
}

$systemPrompt = [
    'role' => 'system',
    'content' =>
        "You are the Motoshapi assistant. Answer ONLY using the data below.\n" .
        "RULES:\n" .
        "- Use ONLY the data provided.\n" .
        "- Paraphrase or summarize if asked.\n" .
        "- Do NOT add any information not in the data.\n" .
        "- Only answer about Motoshapi and its data.\n\n" .
        "DATA:\n$dbOutput"
];

$payload = json_encode([
    'model' => $MODEL,
    'messages' => [
        $systemPrompt,
        ['role' => 'user', 'content' => $userMsg]
    ]
]);

$ch = curl_init($OLLAMA_API_URL);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_TIMEOUT => $OLLAMA_TIMEOUT
]);

$result = curl_exec($ch);
curl_close($ch);

$aiReply = '';
if ($result) {
    foreach (explode("\n", $result) as $line) {
        $json = json_decode($line, true);
        if (isset($json['message']['content'])) {
            $aiReply .= $json['message']['content'];
        }
    }
}

echo json_encode(['ok' => true, 'reply' => trim($aiReply)]);
