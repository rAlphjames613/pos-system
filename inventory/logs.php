<?php
require_once __DIR__ . '/../functions/functions.php';
require_once __DIR__ . '/../core/Database.php';

session_start();
requireLogin();

$logs = Database::fetchAll("SELECT il.*, p.name AS product, CONCAT(u.first_name,' ',u.last_name) AS user FROM inventory_logs il JOIN products p ON p.id=il.product_id LEFT JOIN users u ON u.id=il.user_id ORDER BY il.created_at DESC LIMIT 200");

include __DIR__ . '/../views/header.php';
?>
<div class="page-header">
    <h2><i class="fa-solid fa-list-check"></i> Inventory Logs</h2>
</div>
<div class="card">
    <table class="table">
        <thead>
            <tr><th>Date</th><th>Product</th><th>Type</th><th>Qty</th><th>User</th><th>Reference</th></tr>
        </thead>
        <tbody>
        <?php foreach ($logs as $l): ?>
            <tr>
                <td><?= formatDate($l['created_at']) ?></td>
                <td><?= e($l['product']) ?></td>
                <td><span class="badge badge-<?= $l['type']==='IN' ? 'success' : 'danger' ?>"><?= $l['type'] ?></span></td>
                <td><?= $l['quantity'] ?></td>
                <td><?= e($l['user'] ?? 'System') ?></td>
                <td><?= $l['reference_id'] ? 'Sale #'.$l['reference_id'] : '-' ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php include __DIR__ . '/../views/footer.php'; ?>
