<?php
require_once __DIR__ . '/../functions/functions.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/CSRF.php';

session_start();
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/products/index.php');

CSRF::check();

$id = (int)($_POST['id'] ?? 0);
if ($id) {
    Database::execute("DELETE FROM products WHERE id=?", [$id]);
    setFlash('success', 'Product deleted.');
}

redirect('/products/index.php');
