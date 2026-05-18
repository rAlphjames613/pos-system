<?php
require_once __DIR__ . '/../functions/functions.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/CSRF.php';

session_start();
requireAdmin();

$search    = $_GET['search'] ?? '';
$category  = $_GET['category'] ?? '';
$params    = [];
$where     = "WHERE 1=1";

if ($search) {
    $where .= " AND (p.name LIKE ? OR p.category LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($category) {
    $where .= " AND p.category = ?";
    $params[] = $category;
}

$products   = Database::fetchAll("SELECT * FROM products p $where ORDER BY p.name ASC", $params);
$categories = Database::fetchAll("SELECT DISTINCT category FROM products ORDER BY category");

include __DIR__ . '/../views/header.php';
?>
<div class="page-header">
    <h2><i class="fa-solid fa-box-open"></i> Products</h2>
    <button class="btn btn-primary" onclick="openModal('addModal')">
        <i class="fa-solid fa-plus"></i> Add Product
    </button>
</div>

<div class="filters">
    <form method="GET" class="filter-form">
        <input type="text" name="search" placeholder="Search products..." value="<?= e($search) ?>">
        <select name="category" onchange="this.form.submit()">
            <option value="">All Categories</option>
            <?php foreach ($categories as $c): ?>
                <option value="<?= e($c['category']) ?>" <?= $category === $c['category'] ? 'selected' : '' ?>><?= e($c['category']) ?></option>
            <?php endforeach; ?>
        </select>

    </form>
</div>

<div class="card">
    <table class="table">
        <thead>
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Category</th>
                <th>Price</th>
                <th>Stock</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($products): ?>
            <?php foreach ($products as $i => $p): ?>
            <tr>
                <td><?= $i+1 ?></td>
                <td><?= e($p['name']) ?></td>
                <td><?= e($p['category']) ?></td>
                <td><?= peso((float)$p['price']) ?></td>
                <td class="<?= $p['stock'] <= 5 ? 'text-danger fw-bold' : '' ?>"><?= (int)$p['stock'] ?></td>
                <td><span class="badge badge-<?= $p['status'] === 'available' ? 'success' : 'danger' ?>"><?= e($p['status']) ?></span></td>
                <td>
                    <button class="btn btn-sm btn-warning" onclick="editProduct(<?= htmlspecialchars(json_encode($p), ENT_QUOTES) ?>)">
                        <i class="fa-solid fa-pen"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="deleteProduct(<?= (int)$p['id'] ?>, '<?= e($p['name']) ?>')">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="7" class="text-center text-muted">No products found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Add Modal -->
<div class="modal" id="addModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3><i class="fa-solid fa-plus"></i> Add Product</h3>
            <button onclick="closeModal('addModal')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST" action="/products/save.php">
            <?= CSRF::input() ?>
            <input type="hidden" name="action" value="add">
            <div class="form-group"><label>Product Name</label><input type="text" name="name" required></div>
            <div class="form-group"><label>Category</label><input type="text" name="category" value="General" required></div>
            <div class="form-row">
                <div class="form-group"><label>Price (₱)</label><input type="number" name="price" step="0.01" min="0" required></div>
                <div class="form-group"><label>Stock</label><input type="number" name="stock" min="0" required></div>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status"><option value="available">Available</option><option value="unavailable">Unavailable</option></select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal" id="editModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3><i class="fa-solid fa-pen"></i> Edit Product</h3>
            <button onclick="closeModal('editModal')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST" action="/products/save.php">
            <?= CSRF::input() ?>
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">
            <div class="form-group"><label>Product Name</label><input type="text" name="name" id="edit_name" required></div>
            <div class="form-group"><label>Category</label><input type="text" name="category" id="edit_category" required></div>
            <div class="form-row">
                <div class="form-group"><label>Price (₱)</label><input type="number" name="price" id="edit_price" step="0.01" min="0" required></div>
                <div class="form-group"><label>Stock</label><input type="number" name="stock" id="edit_stock" min="0" required></div>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status" id="edit_status">
                    <option value="available">Available</option>
                    <option value="unavailable">Unavailable</option>
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Update</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Form -->
<form method="POST" action="/products/delete.php" id="deleteForm">
    <?= CSRF::input() ?>
    <input type="hidden" name="id" id="delete_id">
</form>

<script>
function editProduct(p) {
    document.getElementById('edit_id').value     = p.id;
    document.getElementById('edit_name').value   = p.name;
    document.getElementById('edit_category').value = p.category;
    document.getElementById('edit_price').value  = p.price;
    document.getElementById('edit_stock').value  = p.stock;
    document.getElementById('edit_status').value = p.status;
    openModal('editModal');
}

function deleteProduct(id, name) {
    if (!confirm('Delete product: ' + name + '?')) return;
    document.getElementById('delete_id').value = id;
    document.getElementById('deleteForm').submit();
}
</script>
<?php include __DIR__ . '/../views/footer.php'; ?>