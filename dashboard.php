<?php
require_once __DIR__ . '/functions/functions.php';
require_once __DIR__ . '/core/Database.php';

session_start();
requireLogin();

// Dashboard stats
$todaySales   = Database::fetch("SELECT COALESCE(SUM(total),0) AS total, COUNT(*) AS count FROM sales WHERE DATE(created_at) = CURDATE()");
$totalProducts = Database::fetch("SELECT COUNT(*) AS total FROM products WHERE status='available'");
$lowStock     = Database::fetchAll("SELECT * FROM products WHERE stock <= 5 AND status='available' ORDER BY stock ASC LIMIT 10");
$topProducts  = Database::fetchAll("SELECT p.name, SUM(si.quantity) AS sold FROM sales_items si JOIN products p ON p.id=si.product_id JOIN sales s ON s.id=si.sale_id WHERE DATE(s.created_at) = CURDATE() GROUP BY si.product_id ORDER BY sold DESC LIMIT 5");
$weekSales    = Database::fetchAll("SELECT DATE(created_at) AS day, SUM(total) AS total FROM sales WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) GROUP BY DATE(created_at) ORDER BY day ASC");

$config = require __DIR__ . '/config/pusher.php';

include __DIR__ . '/views/header.php';
?>
<div class="page-header">
    <h2><i class="fa-solid fa-gauge-high"></i> Dashboard</h2>
    <small>Real-time updates active <i class="fa-solid fa-circle text-success" id="pusher-dot"></i></small>
</div>

<div class="stats-grid">
    <div class="stat-card stat-blue">
        <div class="stat-icon"><i class="fa-solid fa-peso-sign"></i></div>
        <div class="stat-info">
            <p>Today's Sales</p>
            <h3 id="today-sales"><?= peso((float)$todaySales['total']) ?></h3>
            <small><?= (int)$todaySales['count'] ?> transactions</small>
        </div>
    </div>
    <div class="stat-card stat-green">
        <div class="stat-icon"><i class="fa-solid fa-box"></i></div>
        <div class="stat-info">
            <p>Available Products</p>
            <h3><?= (int)$totalProducts['total'] ?></h3>
        </div>
    </div>
    <div class="stat-card stat-orange">
        <div class="stat-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
        <div class="stat-info">
            <p>Low Stock Items</p>
            <h3><?= count($lowStock) ?></h3>
            <small>5 or fewer units</small>
        </div>
    </div>
</div>

<div class="dashboard-grid">
    <div class="card">
        <div class="card-header"><i class="fa-solid fa-chart-line"></i> Weekly Sales</div>
        <canvas id="salesChart" height="120"></canvas>
    </div>
    <div class="card">
        <div class="card-header"><i class="fa-solid fa-trophy"></i> Top Products Today</div>
        <?php if ($topProducts): ?>
        <table class="table">
            <thead><tr><th>Product</th><th>Sold</th></tr></thead>
            <tbody>
            <?php foreach ($topProducts as $p): ?>
                <tr><td><?= e($p['name']) ?></td><td><?= (int)$p['sold'] ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
            <p class="text-muted">No sales today yet.</p>
        <?php endif; ?>
    </div>
    <div class="card">
        <div class="card-header"><i class="fa-solid fa-triangle-exclamation"></i> Low Stock Alerts</div>
        <?php if ($lowStock): ?>
        <table class="table">
            <thead><tr><th>Product</th><th>Stock</th></tr></thead>
            <tbody>
            <?php foreach ($lowStock as $p): ?>
                <tr class="<?= $p['stock'] == 0 ? 'row-danger' : 'row-warning' ?>">
                    <td><?= e($p['name']) ?></td>
                    <td id="stock-<?= $p['id'] ?>"><?= (int)$p['stock'] ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
            <p class="text-muted">All stocks are sufficient.</p>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script>
const weekData = <?= json_encode($weekSales) ?>;
const labels = weekData.map(d => d.day);
const values = weekData.map(d => parseFloat(d.total));

const ctx = document.getElementById('salesChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels,
        datasets: [{
            label: 'Sales (₱)',
            data: values,
            backgroundColor: 'rgba(139,94,60,.30)',
            borderColor: '#8B5E3C',
            borderWidth: 2,
            borderRadius: 6,
        }]
    },
    options: { responsive: true, plugins: { legend: { display: false } } }
});

// Pusher real-time
const pusher = new Pusher('<?= e($config['key']) ?>', { cluster: '<?= e($config['cluster']) ?>' });
const channel = pusher.subscribe('pos-channel');

channel.bind('pusher:subscription_succeeded', () => {
    document.getElementById('pusher-dot').style.color = 'limegreen';
});

channel.bind('sale-made', function(data) {
    const el = document.getElementById('stock-' + data.product_id);
    if (el) el.innerText = data.new_stock;

    const todayEl = document.getElementById('today-sales');
    if (data.today_total !== undefined) {
        todayEl.innerText = '₱ ' + parseFloat(data.today_total).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
    }
    showToast('Sale recorded! Stock updated.', 'success');
});
</script>
<?php include __DIR__ . '/views/footer.php'; ?>
