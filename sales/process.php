<?php
require_once __DIR__ . '/../functions/functions.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/CSRF.php';

session_start();
requireLogin();
header('Content-Type: application/json');

// Special route: return a fresh CSRF token only (for JS refresh after sale)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['csrf_only'])) {
    echo json_encode(['token' => CSRF::generate()]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request.']); exit;
}

// Verify CSRF manually WITHOUT rotating (so page stays valid)
$token = $_POST['_csrf_token'] ?? '';
if (!CSRF::verify($token)) {
    http_response_code(403);
    echo json_encode(['error' => 'Security token invalid. I-refresh ang page at subukan ulit.']);
    exit;
}

$userId     = $_SESSION['user_id'];
$amountPaid = (float)($_POST['amount_paid'] ?? 0);
$itemsRaw   = $_POST['items'] ?? '[]';
$items      = json_decode($itemsRaw, true);

if (!$items || !is_array($items) || count($items) === 0) {
    echo json_encode(['error' => 'Walang items sa cart.']); exit;
}

// Validate each item
$total = 0;
foreach ($items as $item) {
    $product = Database::fetch(
        "SELECT * FROM products WHERE id=? AND status='available'",
        [(int)$item['product_id']]
    );
    if (!$product) {
        echo json_encode(['error' => 'Product not found.']); exit;
    }
    if ($product['stock'] < (int)$item['quantity']) {
        echo json_encode(['error' => "Kulang ang stock ng {$product['name']}."]); exit;
    }
    $total += (float)$item['price'] * (int)$item['quantity'];
}

if ($amountPaid < $total) {
    echo json_encode(['error' => 'Hindi sapat ang bayad.']); exit;
}

$change = $amountPaid - $total;
$db     = Database::getInstance();

try {
    $db->beginTransaction();

    $stmt = $db->prepare(
        "INSERT INTO sales (user_id, total, payment, amount_paid, change_amount)
         VALUES (?, ?, 'cash', ?, ?)"
    );
    $stmt->execute([$userId, $total, $amountPaid, $change]);
    $saleId = (int)$db->lastInsertId();

    $stmtItem = $db->prepare(
        "INSERT INTO sales_items (sale_id, product_id, quantity, price)
         VALUES (?, ?, ?, ?)"
    );
    foreach ($items as $item) {
        $stmtItem->execute([
            $saleId,
            (int)$item['product_id'],
            (int)$item['quantity'],
            (float)$item['price']
        ]);
    }

    $db->commit();

    // Pusher — optional, hindi mag-fail ang sale kahit walang credentials
    try {
        $pusherConfig = require __DIR__ . '/../config/pusher.php';
        if (
            !empty($pusherConfig['app_id']) &&
            $pusherConfig['app_id'] !== 'your_app_id' &&
            !empty($pusherConfig['key']) &&
            !empty($pusherConfig['secret'])
        ) {
            foreach ($items as $item) {
                $newStock = Database::fetch(
                    "SELECT stock FROM products WHERE id=?",
                    [(int)$item['product_id']]
                );
                $pusherPayload = json_encode([
                    'name'     => 'sale-made',
                    'data'     => json_encode([
                        'product_id' => (int)$item['product_id'],
                        'new_stock'  => (int)($newStock['stock'] ?? 0),
                        'sale_id'    => $saleId,
                    ]),
                    'channels' => ['pos-channel'],
                ]);
                $timestamp = time();
                $bodyMd5   = md5($pusherPayload);
                $strToSign = "POST\n/apps/{$pusherConfig['app_id']}/events\n" .
                    "auth_key={$pusherConfig['key']}&auth_timestamp={$timestamp}&auth_version=1.0&body_md5={$bodyMd5}&channel=pos-channel&name=sale-made";
                $authSig   = hash_hmac('sha256', $strToSign, $pusherConfig['secret']);
                $url       = "https://api-{$pusherConfig['cluster']}.pusher.com/apps/{$pusherConfig['app_id']}/events"
                           . "?auth_key={$pusherConfig['key']}&auth_timestamp={$timestamp}&auth_version=1.0&body_md5={$bodyMd5}&auth_signature={$authSig}";
                @file_get_contents($url, false, stream_context_create([
                    'http' => [
                        'method'        => 'POST',
                        'header'        => "Content-Type: application/json\r\n",
                        'content'       => $pusherPayload,
                        'ignore_errors' => true,
                    ]
                ]));
            }
        }
    } catch (Exception $pusherEx) {
        error_log('Pusher error: ' . $pusherEx->getMessage());
    }

    echo json_encode([
        'success'       => true,
        'sale_id'       => $saleId,
        'total'         => $total,
        'amount_paid'   => $amountPaid,
        'change_amount' => $change,
    ]);

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo json_encode(['error' => 'Transaction failed: ' . $e->getMessage()]);
}