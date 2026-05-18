<?php
require_once __DIR__ . '/../functions/functions.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/CSRF.php';
require_once __DIR__ . '/../core/Validator.php';

session_start();
requireAdmin();

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::check();
    $action = $_POST['action'] ?? 'register';

    // --- REGISTER ---
    if ($action === 'register') {
        $v = new Validator();
        $data = [
            'first_name' => $v->sanitize($_POST['first_name'] ?? ''),
            'last_name'  => $v->sanitize($_POST['last_name']  ?? ''),
            'username'   => $v->sanitize($_POST['username']   ?? ''),
            'password'   => $_POST['password'] ?? '',
            'role'       => in_array($_POST['role'] ?? '', ['admin','cashier']) ? $_POST['role'] : 'cashier',
        ];

        $v->required('first_name', $data['first_name'])
          ->required('last_name',  $data['last_name'])
          ->required('username',   $data['username'])
          ->required('password',   $data['password'])
          ->minLength('password',  $data['password'], 6);

        if ($v->fails()) {
            $error = $v->firstError();
        } elseif (!Auth::register($data)) {
            $error = 'Username already taken.';
        } else {
            $success = 'User registered successfully.';
        }

    // --- EDIT ---
    } elseif ($action === 'edit') {
        $id         = (int)($_POST['id'] ?? 0);
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name  = trim($_POST['last_name']  ?? '');
        $username   = trim($_POST['username']   ?? '');
        $role       = in_array($_POST['role']   ?? '', ['admin','cashier']) ? $_POST['role'] : 'cashier';
        $status     = in_array($_POST['status'] ?? '', ['active','inactive']) ? $_POST['status'] : 'active';
        $password   = $_POST['password'] ?? '';

        if (!$id || !$first_name || !$last_name || !$username) {
            $error = 'All fields are required.';
        } else {
            // Check if username is taken by another user
            $existing = Database::fetch("SELECT id FROM users WHERE username = ? AND id != ?", [$username, $id]);
            if ($existing) {
                $error = 'Username already taken by another user.';
            } else {
                if ($password !== '') {
                    if (strlen($password) < 6) {
                        $error = 'Password must be at least 6 characters.';
                    } else {
                        Database::execute(
                            "UPDATE users SET first_name=?, last_name=?, username=?, role=?, status=?, password=? WHERE id=?",
                            [$first_name, $last_name, $username, $role, $status, Auth::hashPassword($password), $id]
                        );
                        $success = 'User updated successfully.';
                    }
                } else {
                    Database::execute(
                        "UPDATE users SET first_name=?, last_name=?, username=?, role=?, status=? WHERE id=?",
                        [$first_name, $last_name, $username, $role, $status, $id]
                    );
                    $success = 'User updated successfully.';
                }
            }
        }

    // --- DELETE ---
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        // Prevent deleting yourself
        if ($id === (int)($_SESSION['user']['id'] ?? 0)) {
            $error = 'You cannot delete your own account.';
        } elseif ($id > 0) {
            Database::execute("DELETE FROM users WHERE id = ?", [$id]);
            $success = 'User deleted successfully.';
        }
    }
}

// Fetch all users for display
$users = Database::fetchAll("SELECT id, first_name, last_name, username, role, status, created_at FROM users ORDER BY created_at DESC");

include __DIR__ . '/../views/header.php';
?>
<div class="page-header">
    <h2><i class="fa-solid fa-user-plus"></i> Register New User</h2>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= e($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success"><?= e($success) ?></div>
<?php endif; ?>

<!-- Register Form -->
<div class="card" style="max-width:500px;">
    <form method="POST" action="/auth/register.php">
        <?= CSRF::input() ?>
        <input type="hidden" name="action" value="register">
        <div class="form-row">
            <div class="form-group">
                <label>First Name</label>
                <input type="text" name="first_name" value="<?= e($_POST['first_name'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Last Name</label>
                <input type="text" name="last_name" value="<?= e($_POST['last_name'] ?? '') ?>" required>
            </div>
        </div>
        <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" value="<?= e($_POST['username'] ?? '') ?>" required>
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required>
        </div>
        <div class="form-group">
            <label>Role</label>
            <select name="role">
                <option value="cashier">Cashier</option>
                <option value="admin">Admin</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">
            <i class="fa-solid fa-user-plus"></i> Register
        </button>
    </form>
</div>

<!-- Users List Table -->
<div class="card" style="margin-top:2rem;">
    <div class="page-header" style="margin-bottom:1rem;">
        <h3><i class="fa-solid fa-users"></i> Registered Users</h3>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>#</th>
                <th>Full Name</th>
                <th>Username</th>
                <th>Role</th>
                <th>Status</th>
                <th>Date Registered</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($users): ?>
            <?php foreach ($users as $u): ?>
            <tr>
                <td><?= $u['id'] ?></td>
                <td><?= e($u['first_name'] . ' ' . $u['last_name']) ?></td>
                <td><?= e($u['username']) ?></td>
                <td>
                    <span class="badge <?= $u['role'] === 'admin' ? 'badge-danger' : 'badge-success' ?>">
                        <?= ucfirst(e($u['role'])) ?>
                    </span>
                </td>
                <td>
                    <span class="badge <?= $u['status'] === 'active' ? 'badge-success' : 'badge-danger' ?>">
                        <?= ucfirst(e($u['status'])) ?>
                    </span>
                </td>
                <td><?= formatDate($u['created_at']) ?></td>
                <td>
                    <button class="btn btn-sm btn-warning"
                        onclick="openEditModal(<?= htmlspecialchars(json_encode($u), ENT_QUOTES) ?>)">
                        <i class="fa-solid fa-pen"></i>
                    </button>
                    <?php if ($u['id'] != ($_SESSION['user']['id'] ?? 0)): ?>
                    <button class="btn btn-sm btn-danger"
                        onclick="confirmDelete(<?= (int)$u['id'] ?>, '<?= e($u['first_name'].' '.$u['last_name']) ?>')">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="7" class="text-center text-muted">No users found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Edit Modal -->
<div class="modal" id="editModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3><i class="fa-solid fa-user-pen"></i> Edit User</h3>
            <button onclick="closeModal('editModal')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST" action="/auth/register.php">
            <?= CSRF::input() ?>
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">
            <div class="form-row">
                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" name="first_name" id="edit_first_name" required>
                </div>
                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" name="last_name" id="edit_last_name" required>
                </div>
            </div>
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" id="edit_username" required>
            </div>
            <div class="form-group">
                <label>New Password <small style="color:var(--text-muted); font-weight:400;">(leave blank to keep current)</small></label>
                <input type="password" name="password" id="edit_password" placeholder="••••••">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Role</label>
                    <select name="role" id="edit_role">
                        <option value="cashier">Cashier</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" id="edit_status">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Form -->
<form method="POST" action="/auth/register.php" id="deleteForm">
    <?= CSRF::input() ?>
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="delete_id">
</form>

<script>
function openEditModal(u) {
    document.getElementById('edit_id').value         = u.id;
    document.getElementById('edit_first_name').value = u.first_name;
    document.getElementById('edit_last_name').value  = u.last_name;
    document.getElementById('edit_username').value   = u.username;
    document.getElementById('edit_role').value       = u.role;
    document.getElementById('edit_status').value     = u.status;
    document.getElementById('edit_password').value   = '';
    openModal('editModal');
}

function confirmDelete(id, name) {
    if (!confirm('Delete user: ' + name + '?\nThis cannot be undone.')) return;
    document.getElementById('delete_id').value = id;
    document.getElementById('deleteForm').submit();
}
</script>

<?php include __DIR__ . '/../views/footer.php'; ?>