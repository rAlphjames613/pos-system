<?php
require_once __DIR__ . '/../functions/functions.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/CSRF.php';
require_once __DIR__ . '/../core/Validator.php';

session_start();
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/products/index.php');

CSRF::check();

$v      = new Validator();
$action = $_POST['action'] ?? '';
$id     = (int)($_POST['id'] ?? 0);
$name   = $v->sanitize($_POST['name'] ?? '');
$cat    = $v->sanitize($_POST['category'] ?? 'General');
$price  = (float)($_POST['price'] ?? 0);
$stock  = (int)($_POST['stock'] ?? 0);
$status = in_array($_POST['status'] ?? '', ['available','unavailable']) ? $_POST['status'] : 'available';

$v->required('name', $name)->required('category', $cat)->positive('price', $price);

if ($v->fails()) {
    setFlash('danger', $v->firstError());
    redirect('/products/index.php');
}

if ($action === 'add') {
    Database::insert(
        "INSERT INTO products (name, category, price, stock, status) VALUES (?, ?, ?, ?, ?)",
        [$name, $cat, $price, $stock, $status]
    );
    setFlash('success', 'Product added successfully.');
} elseif ($action === 'edit' && $id) {
    Database::execute(
        "UPDATE products SET name=?, category=?, price=?, stock=?, status=?, updated_at=NOW() WHERE id=?",
        [$name, $cat, $price, $stock, $status, $id]
    );
    setFlash('success', 'Product updated successfully.');
}

redirect('/products/index.php');
