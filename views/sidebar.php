<?php $current = basename($_SERVER['PHP_SELF']); ?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <i class="fa-solid fa-store"></i>
        <span>PRIME POS</span>
    </div>
    <nav class="sidebar-nav">
        <a href="/dashboard.php" class="<?= $current === 'dashboard.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-gauge-high"></i> Dashboard
        </a>
        <a href="/sales/pos.php" class="<?= $current === 'pos.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-cash-register"></i> POS / Sales
        </a>
        <?php if (isAdmin()): ?>
        <a href="/products/index.php" class="<?= $current === 'index.php' && str_contains($_SERVER['PHP_SELF'], 'products') ? 'active' : '' ?>">
            <i class="fa-solid fa-box-open"></i> Products
        </a>
        <?php endif; ?>
        <a href="/inventory/stock_in.php" class="<?= $current === 'stock_in.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-warehouse"></i> Inventory
        </a>
        <a href="/inventory/logs.php" class="<?= $current === 'logs.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-list-check"></i> Inventory Logs
        </a>
        <?php if (isAdmin()): ?>
        <div class="nav-section">Reports</div>
        <a href="/reports/sales.php" class="<?= $current === 'sales.php' && str_contains($_SERVER['PHP_SELF'], 'reports') ? 'active' : '' ?>">
            <i class="fa-solid fa-chart-line"></i> Sales Report
        </a>
        <a href="/reports/inventory.php" class="<?= $current === 'inventory.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-chart-bar"></i> Inventory Report
        </a>
        <a href="/reports/adhoc.php" class="<?= $current === 'adhoc.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-filter"></i>Sales Analytics Report
        </a>
        <div class="nav-section">Admin</div>
        <a href="/auth/register.php" class="<?= $current === 'register.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-users"></i> Manage Users
        </a>
        <?php endif; ?>
    </nav>
</aside>
