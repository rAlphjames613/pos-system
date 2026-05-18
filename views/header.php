<?php
require_once __DIR__ . '/../functions/functions.php';
require_once __DIR__ . '/../core/CSRF.php';

if (session_status() === PHP_SESSION_NONE) session_start();
requireLogin();

$user   = currentUser();
$flash  = getFlash();
$config = require __DIR__ . '/../config/pusher.php';
$appCfg = require __DIR__ . '/../config/app.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($appCfg['name']) ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="wrapper">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <div class="main-content">
        <header class="topbar">
            <button class="sidebar-toggle" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></button>
            <div class="topbar-right">
                <span><i class="fa-solid fa-user-circle"></i> <?= e($user['first_name']) ?> (<?= e($user['role']) ?>)</span>
                <a href="/auth/logout.php" class="btn btn-sm btn-danger"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
            </div>
        </header>
        <div class="content-area">
            <?php if ($flash): ?>
                <div class="alert alert-<?= e($flash['type']) ?>">
                    <?= e($flash['message']) ?>
                </div>
            <?php endif; ?>
