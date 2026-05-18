<?php
require_once __DIR__ . '/../functions/functions.php';
require_once __DIR__ . '/../core/Database.php';

session_start();
requireLogin();

$id   = (int)($_GET['id'] ?? 0);
$sale = Database::fetch("SELECT s.*, CONCAT(u.first_name,' ',u.last_name) AS cashier FROM sales s JOIN users u ON u.id=s.user_id WHERE s.id=?", [$id]);

if (!$sale) { setFlash('danger', 'Sale not found.'); redirect('/dashboard.php'); }

$items = Database::fetchAll("SELECT si.*, p.name FROM sales_items si JOIN products p ON p.id=si.product_id WHERE si.sale_id=?", [$id]);

include __DIR__ . '/../views/header.php';
?>
<div class="page-header">
    <h2><i class="fa-solid fa-receipt"></i> Receipt #<?= $id ?></h2>
    <button class="btn btn-primary" onclick="window.print()"><i class="fa-solid fa-print"></i> Print</button>
</div>
<div class="receipt card" style="max-width:500px;margin:auto;">
    <div class="receipt-header text-center">
        <h3>POS System</h3>
        <p>Sari-Sari Store Manager</p>
        <p>Receipt #<?= $id ?></p>
        <p>Cashier: <?= e($sale['cashier']) ?></p>
        <p><?= formatDate($sale['created_at']) ?></p>
    </div>
    <table class="table">
        <thead><tr><th>Item</th><th>Qty</th><th>Price</th><th>Sub</th></tr></thead>
        <tbody>
        <?php foreach ($items as $i): ?>
            <tr>
                <td><?= e($i['name']) ?></td>
                <td><?= $i['quantity'] ?></td>
                <td><?= peso((float)$i['price']) ?></td>
                <td><?= peso($i['price'] * $i['quantity']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <div class="receipt-totals">
        <p>Total: <strong><?= peso((float)$sale['total']) ?></strong></p>
        <p>Amount Paid: <strong><?= peso((float)$sale['amount_paid']) ?></strong></p>
        <p>Change: <strong><?= peso((float)$sale['change_amount']) ?></strong></p>
    </div>
    <p class="text-center">Thank you for your purchase!</p>
</div>
<?php include __DIR__ . '/../views/footer.php'; ?>
