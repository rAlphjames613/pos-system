<?php
require_once __DIR__ . '/../functions/functions.php';
require_once __DIR__ . '/../core/Database.php';

session_start();
requireAdmin();

$from     = $_GET['from']     ?? '';
$to       = $_GET['to']       ?? '';
$product  = $_GET['product']  ?? '';
$category = $_GET['category'] ?? '';
$results  = null;
$summary  = null;

$products   = Database::fetchAll("SELECT id, name FROM products ORDER BY name");
$categories = Database::fetchAll("SELECT DISTINCT category FROM products ORDER BY category");

if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($from || $to || $product || $category)) {
    // Dynamic query (adhoc) with prepared statements
    $sql    = "SELECT s.id, s.created_at, s.total, s.amount_paid, s.change_amount,
                      p.name AS product, p.category, si.quantity, si.price,
                      CONCAT(u.first_name,' ',u.last_name) AS cashier
               FROM sales s
               JOIN sales_items si ON si.sale_id = s.id
               JOIN products p    ON p.id = si.product_id
               JOIN users u       ON u.id = s.user_id
               WHERE 1=1";
    $params = [];

    if ($from) {
        $sql .= " AND DATE(s.created_at) >= ?";
        $params[] = $from;
    }
    if ($to) {
        $sql .= " AND DATE(s.created_at) <= ?";
        $params[] = $to;
    }
    if ($product) {
        $sql .= " AND si.product_id = ?";
        $params[] = (int)$product;
    }
    if ($category) {
        $sql .= " AND p.category = ?";
        $params[] = $category;
    }

    $sql .= " ORDER BY s.created_at DESC LIMIT 500";

    $results = Database::fetchAll($sql, $params);

    // Summary
    $sumSql = "SELECT COALESCE(SUM(s.total),0) AS grand_total, COUNT(DISTINCT s.id) AS txn_count, SUM(si.quantity) AS units_sold
               FROM sales s JOIN sales_items si ON si.sale_id=s.id JOIN products p ON p.id=si.product_id WHERE 1=1";
    $sumParams = [];
    if ($from) { $sumSql .= " AND DATE(s.created_at) >= ?"; $sumParams[] = $from; }
    if ($to)   { $sumSql .= " AND DATE(s.created_at) <= ?"; $sumParams[] = $to; }
    if ($product)  { $sumSql .= " AND si.product_id = ?"; $sumParams[] = (int)$product; }
    if ($category) { $sumSql .= " AND p.category = ?"; $sumParams[] = $category; }

    $summary = Database::fetch($sumSql, $sumParams);
}

include __DIR__ . '/../views/header.php';
?>
<div class="page-header">
    <h2><i class="fa-solid fa-filter"></i>Sales Analytics Report</h2>
</div>

<div class="card">
    <form method="GET" action="/reports/adhoc.php" class="adhoc-form">
        <div class="form-row">
            <div class="form-group">
                <label>From Date</label>
                <input type="date" name="from" value="<?= e($from) ?>">
            </div>
            <div class="form-group">
                <label>To Date</label>
                <input type="date" name="to" value="<?= e($to) ?>">
            </div>
            <div class="form-group">
                <label>Product</label>
                <select name="product">
                    <option value="">All Products</option>
                    <?php foreach ($products as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= $product == $p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Category</label>
                <select name="category">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= e($c['category']) ?>" <?= $category === $c['category'] ? 'selected' : '' ?>><?= e($c['category']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-magnifying-glass"></i> Generate Report</button>
        <a href="/reports/adhoc.php" class="btn btn-secondary"><i class="fa-solid fa-rotate-left"></i> Reset</a>
    </form>
</div>

<?php if ($summary): ?>
<div class="stats-grid" style="margin:1rem 0;">
    <div class="stat-card stat-blue">
        <div class="stat-icon"><i class="fa-solid fa-peso-sign"></i></div>
        <div class="stat-info"><p>Grand Total</p><h3><?= peso((float)$summary['grand_total']) ?></h3></div>
    </div>
    <div class="stat-card stat-green">
        <div class="stat-icon"><i class="fa-solid fa-receipt"></i></div>
        <div class="stat-info"><p>Transactions</p><h3><?= (int)$summary['txn_count'] ?></h3></div>
    </div>
    <div class="stat-card stat-orange">
        <div class="stat-icon"><i class="fa-solid fa-boxes-stacked"></i></div>
        <div class="stat-info"><p>Units Sold</p><h3><?= (int)$summary['units_sold'] ?></h3></div>
    </div>
</div>
<?php endif; ?>

<?php if ($results !== null): ?>
<div class="card">
    <table class="table">
        <thead><tr><th>Date</th><th>Sale#</th><th>Product</th><th>Category</th><th>Qty</th><th>Unit Price</th><th>Subtotal</th><th>Cashier</th></tr></thead>
        <tbody>
        <?php if ($results): ?>
            <?php foreach ($results as $r): ?>
            <tr>
                <td><?= formatDate($r['created_at']) ?></td>
                <td>#<?= $r['id'] ?></td>
                <td><?= e($r['product']) ?></td>
                <td><?= e($r['category']) ?></td>
                <td><?= $r['quantity'] ?></td>
                <td><?= peso((float)$r['price']) ?></td>
                <td><?= peso($r['price'] * $r['quantity']) ?></td>
                <td><?= e($r['cashier']) ?></td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="8" class="text-center text-muted">No results found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../views/footer.php'; ?>
