<?php
require_once __DIR__ . '/../functions/functions.php';
require_once __DIR__ . '/../core/Database.php';

session_start();
requireAdmin();

// Fetch all products grouped by category
$products = Database::fetchAll("SELECT * FROM products ORDER BY category ASC, name ASC");

// Group by category
$grouped = [];
foreach ($products as $p) {
    $grouped[$p['category']][] = $p;
}

include __DIR__ . '/../views/header.php';
?>
<div class="page-header">
    <h2><i class="fa-solid fa-chart-bar"></i> Inventory Report</h2>
</div>

<!-- Search Bar -->
<div class="card" style="margin-bottom:1rem;">
    <div class="pos-search">
        <input type="text" id="inventorySearch" placeholder="🔍  Search product name or category..." oninput="filterInventory()">
    </div>
</div>

<!-- Grouped Tables -->
<div id="inventoryContainer">
<?php foreach ($grouped as $category => $items): ?>
    <div class="category-group" data-category="<?= e($category) ?>">
        <div class="page-header" style="margin-top:1rem; margin-bottom:.5rem;">
            <h3 style="font-size:1rem; color:var(--primary);">
                <i class="fa-solid fa-tag"></i> <?= e($category) ?>
                <span style="font-size:.8rem; font-weight:400; color:var(--text-muted); margin-left:.5rem;">
                    (<?= count($items) ?> item<?= count($items) > 1 ? 's' : '' ?>)
                </span>
            </h3>
        </div>
        <div class="card" style="margin-bottom:1rem;">
            <table class="table inventory-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $p): ?>
                <tr class="<?= $p['stock'] == 0 ? 'row-danger' : ($p['stock'] <= 5 ? 'row-warning' : '') ?>"
                    data-name="<?= strtolower(e($p['name'])) ?>"
                    data-category="<?= strtolower(e($p['category'])) ?>">
                    <td><?= e($p['name']) ?></td>
                    <td><?= peso((float)$p['price']) ?></td>
                    <td><?= (int)$p['stock'] ?></td>
                    <td><span class="badge badge-<?= $p['status'] === 'available' ? 'success' : 'danger' ?>"><?= e($p['status']) ?></span></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endforeach; ?>
</div>

<p id="noResults" style="display:none; text-align:center; color:var(--text-muted); margin-top:2rem;">
    <i class="fa-solid fa-circle-xmark"></i> No products found.
</p>

<script>
function filterInventory() {
    const query = document.getElementById('inventorySearch').value.toLowerCase().trim();
    const groups = document.querySelectorAll('.category-group');
    let totalVisible = 0;

    groups.forEach(group => {
        const rows = group.querySelectorAll('tbody tr');
        let groupVisible = 0;

        rows.forEach(row => {
            const name     = row.dataset.name     || '';
            const category = row.dataset.category || '';
            const match    = name.includes(query) || category.includes(query);
            row.style.display = match ? '' : 'none';
            if (match) groupVisible++;
        });

        group.style.display = groupVisible > 0 ? '' : 'none';
        totalVisible += groupVisible;
    });

    document.getElementById('noResults').style.display = totalVisible === 0 ? 'block' : 'none';
}
</script>

<?php include __DIR__ . '/../views/footer.php'; ?>