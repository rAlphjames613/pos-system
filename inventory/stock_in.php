<?php
require_once __DIR__ . '/../functions/functions.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/CSRF.php';
require_once __DIR__ . '/../core/Validator.php';

session_start();
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::check();
    $productId = (int)($_POST['product_id'] ?? 0);
    $qty       = (int)($_POST['quantity'] ?? 0);
    $userId    = $_SESSION['user_id'];

    $v = new Validator();
    $v->positive('quantity', $qty);

    if ($v->fails()) {
        setFlash('danger', $v->firstError());
    } else {
        $db = Database::getInstance();
        $stmt = $db->prepare("CALL stock_in(?, ?, ?)");
        $stmt->execute([$productId, $userId, $qty]);
        // Mark product available
        Database::execute("UPDATE products SET status='available' WHERE id=?", [$productId]);
        setFlash('success', "Stock updated successfully.");
    }
    redirect('/inventory/stock_in.php');
}

$products = Database::fetchAll("SELECT * FROM products ORDER BY name");
include __DIR__ . '/../views/header.php';
?>
<div class="page-header">
    <h2><i class="fa-solid fa-warehouse"></i> Stock In</h2>
</div>

<?php $flash = getFlash(); if ($flash): ?>
<div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
<?php endif; ?>

<div class="card" style="max-width:500px;">
    <form method="POST" action="/inventory/stock_in.php">
        <?= CSRF::input() ?>
        <div class="form-group">
            <label>Product</label>
            <select name="product_id" required>
                <option value="">Select Product Item</option>
                <?php foreach ($products as $p): ?>
                    <option value="<?= $p['id'] ?>"><?= e($p['name']) ?> (Stock: <?= $p['stock'] ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Quantity to Add</label>
            <input type="number" name="quantity" min="1" required>
        </div>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Add Stock</button>
    </form>
</div>
<?php include __DIR__ . '/../views/footer.php'; ?>
