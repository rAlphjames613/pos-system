<?php
require_once __DIR__ . '/../functions/functions.php';
require_once __DIR__ . '/../core/Database.php';

session_start();
requireAdmin();

$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to']   ?? date('Y-m-d');

$sales = Database::fetchAll(
    "SELECT s.*, CONCAT(u.first_name,' ',u.last_name) AS cashier FROM sales s JOIN users u ON u.id=s.user_id WHERE DATE(s.created_at) BETWEEN ? AND ? ORDER BY s.created_at DESC",
    [$from, $to]
);

$summary = Database::fetch(
    "SELECT COALESCE(SUM(total),0) AS total, COUNT(*) AS count FROM sales WHERE DATE(created_at) BETWEEN ? AND ?",
    [$from, $to]
);

include __DIR__ . '/../views/header.php';
?>
<div class="page-header">
    <h2><i class="fa-solid fa-chart-line"></i> Sales Report</h2>
</div>

<div class="filters">
    <form method="GET" class="filter-form">
        <label>From <input type="date" name="from" value="<?= e($from) ?>"></label>
        <label>To <input type="date" name="to" value="<?= e($to) ?>"></label>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter"></i> Filter</button>
    </form>
</div>

<div class="stats-grid" style="margin-bottom:1rem;">
    <div class="stat-card stat-blue">
        <div class="stat-icon"><i class="fa-solid fa-peso-sign"></i></div>
        <div class="stat-info"><p>Total Sales</p><h3><?= peso((float)$summary['total']) ?></h3></div>
    </div>
    <div class="stat-card stat-green">
        <div class="stat-icon"><i class="fa-solid fa-receipt"></i></div>
        <div class="stat-info"><p>Transactions</p><h3><?= (int)$summary['count'] ?></h3></div>
    </div>
</div>

<div class="card">
    <table class="table">
        <thead><tr><th>Date</th><th>Cashier</th><th>Total</th><th>Amount Paid</th><th>Change</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($sales as $s): ?>
        <tr>
            <td><?= formatDate($s['created_at']) ?></td>
            <td><?= e($s['cashier']) ?></td>
            <td><?= peso((float)$s['total']) ?></td>
            <td><?= peso((float)$s['amount_paid']) ?></td>
            <td><?= peso((float)$s['change_amount']) ?></td>
            <td><a href="/sales/receipt.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-secondary"><i class="fa-solid fa-receipt"></i></a></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$sales): ?><tr><td colspan="6" class="text-center text-muted">No sales found.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php include __DIR__ . '/../views/footer.php'; ?>
