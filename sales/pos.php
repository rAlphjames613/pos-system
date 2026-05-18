<?php
require_once __DIR__ . '/../functions/functions.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/CSRF.php';

session_start();
requireLogin();

$products   = Database::fetchAll("SELECT * FROM products WHERE status='available' AND stock > 0 ORDER BY category, name");
$categories = Database::fetchAll("SELECT DISTINCT category FROM products WHERE status='available' ORDER BY category");
$config     = require __DIR__ . '/../config/pusher.php';

include __DIR__ . '/../views/header.php';
?>
<div class="page-header">
    <h2><i class="fa-solid fa-cash-register"></i> Point of Sale</h2>
</div>

<div class="pos-layout">
    <!-- Product Grid -->
    <div class="pos-products">
        <div class="pos-search">
            <div style="position:relative;">
                <i class="fa-solid fa-magnifying-glass" style="position:absolute; left:.75rem; top:50%; transform:translateY(-50%); color:var(--text-muted); pointer-events:none;"></i>
                <input type="text" id="productSearch" placeholder="Search product..." oninput="filterProducts()" style="padding-left:2.2rem;">
            </div>
            <select id="categoryFilter" onchange="filterProducts()">
                <option value="">All Categories</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= e($c['category']) ?>"><?= e($c['category']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="product-grid" id="productGrid">
            <?php foreach ($products as $p): ?>
            <div class="product-card" data-id="<?= $p['id'] ?>" data-name="<?= e($p['name']) ?>" data-price="<?= $p['price'] ?>" data-stock="<?= $p['stock'] ?>" data-category="<?= e($p['category']) ?>" onclick="addToCart(this)">
                <div class="product-icon"><i class="fa-solid <?= getProductIcon($p['name'], $p['category']) ?>"></i></div>
                <p class="product-name"><?= e($p['name']) ?></p>
                <p class="product-price"><?= peso((float)$p['price']) ?></p>
                <p class="product-stock" id="pstock-<?= $p['id'] ?>">Stock: <?= $p['stock'] ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Cart -->
    <div class="pos-cart">
        <div class="cart-header"><i class="fa-solid fa-cart-shopping"></i> Cart</div>
        <div class="cart-items" id="cartItems">
            <p class="text-muted text-center" id="emptyCart">Cart is empty.</p>
        </div>
        <div class="cart-totals">
            <div class="cart-row"><span>Subtotal:</span><span id="cartSubtotal">₱ 0.00</span></div>
            <div class="cart-row cart-total"><span>TOTAL:</span><span id="cartTotal">₱ 0.00</span></div>
        </div>
        <div class="payment-section">
            <label>Amount Paid (₱)</label>
            <input type="number" id="amountPaid" min="0" step="0.01" placeholder="0.00" oninput="computeChange()">
            <div class="cart-row">
                <span>Change:</span>
                <span id="changeAmount" class="text-success fw-bold">₱ 0.00</span>
            </div>
            <button class="btn btn-primary btn-block btn-lg" onclick="processSale()">
                <i class="fa-solid fa-money-bills"></i> Cash Payment
            </button>
            <button class="btn btn-secondary btn-block" onclick="clearCart()">
                <i class="fa-solid fa-broom"></i> Clear Cart
            </button>
        </div>
    </div>
</div>

<!-- Receipt Modal -->
<div class="modal" id="receiptModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3><i class="fa-solid fa-receipt"></i> Receipt</h3>
            <button onclick="closeModal('receiptModal')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div id="receiptContent"></div>
        <div class="modal-footer">
            <button class="btn btn-primary" onclick="window.print()"><i class="fa-solid fa-print"></i> Print</button>
            <button class="btn btn-secondary" onclick="closeModal('receiptModal'); clearCart()">New Sale</button>
        </div>
    </div>
</div>

<?= CSRF::input() ?>

<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script>
const PUSHER_KEY     = '<?= e($config['key']) ?>';
const PUSHER_CLUSTER = '<?= e($config['cluster']) ?>';
let cart = {};

function addToCart(el) {
    const id    = el.dataset.id;
    const name  = el.dataset.name;
    const price = parseFloat(el.dataset.price);
    const stock = parseInt(document.getElementById('pstock-' + id)?.innerText?.replace('Stock: ', '') || el.dataset.stock);

    if (cart[id] && cart[id].qty >= stock) {
        showToast('Insufficient stock!', 'danger'); return;
    }
    if (!cart[id]) {
        cart[id] = { id, name, price, qty: 0 };
    }
    cart[id].qty++;
    renderCart();
}

function renderCart() {
    const container = document.getElementById('cartItems');
    const empty     = document.getElementById('emptyCart');
    let total = 0;

    if (Object.keys(cart).length === 0) {
        container.innerHTML = '<p class="text-muted text-center">Cart is empty.</p>';
        document.getElementById('cartSubtotal').innerText = '₱ 0.00';
        document.getElementById('cartTotal').innerText    = '₱ 0.00';
        document.getElementById('changeAmount').innerText = '₱ 0.00';
        return;
    }

    let html = '';
    for (const [id, item] of Object.entries(cart)) {
        const sub = item.price * item.qty;
        total += sub;
        html += `<div class="cart-item">
            <div class="cart-item-info">
                <span class="cart-item-name">${item.name}</span>
                <span class="cart-item-price">₱ ${item.price.toFixed(2)}</span>
            </div>
            <div class="cart-item-controls">
                <button class="qty-btn" onclick="changeQty('${id}', -1)"><i class="fa-solid fa-minus"></i></button>
                <span>${item.qty}</span>
                <button class="qty-btn" onclick="changeQty('${id}', 1)"><i class="fa-solid fa-plus"></i></button>
                <button class="qty-btn btn-danger" onclick="removeItem('${id}')"><i class="fa-solid fa-trash"></i></button>
            </div>
            <span>₱ ${sub.toFixed(2)}</span>
        </div>`;
    }

    container.innerHTML = html;
    document.getElementById('cartSubtotal').innerText = '₱ ' + total.toFixed(2);
    document.getElementById('cartTotal').innerText    = '₱ ' + total.toFixed(2);
    computeChange();
}

function changeQty(id, delta) {
    if (!cart[id]) return;
    cart[id].qty += delta;
    if (cart[id].qty <= 0) delete cart[id];
    renderCart();
}

function removeItem(id) {
    delete cart[id];
    renderCart();
}

function clearCart() {
    cart = {};
    document.getElementById('amountPaid').value = '';
    renderCart();
}

function computeChange() {
    const total = Object.values(cart).reduce((s, i) => s + i.price * i.qty, 0);
    const paid  = parseFloat(document.getElementById('amountPaid').value) || 0;
    const change = paid - total;
    document.getElementById('changeAmount').innerText = '₱ ' + (change < 0 ? '0.00' : change.toFixed(2));
}

async function processSale() {
    if (Object.keys(cart).length === 0) { showToast('Cart is empty!', 'danger'); return; }

    const total = Object.values(cart).reduce((s, i) => s + i.price * i.qty, 0);
    const paid  = parseFloat(document.getElementById('amountPaid').value) || 0;

    if (paid < total) { showToast('Insufficient payment!', 'danger'); return; }

    const items = Object.values(cart).map(i => ({ product_id: i.id, quantity: i.qty, price: i.price }));

    const fd = new FormData();
    fd.append('_csrf_token', document.querySelector('input[name="_csrf_token"]').value);
    fd.append('items', JSON.stringify(items));
    fd.append('amount_paid', paid);

    try {
        const res  = await fetch('/sales/process.php', { method: 'POST', body: fd });
        const data = await res.json();

        if (data.success) {
            showReceipt(data.sale_id, items, total, paid, data.change_amount);
        } else {
            showToast(data.error || 'Sale failed.', 'danger');
        }
    } catch (e) {
        showToast('Network error.', 'danger');
    }
}

function showReceipt(saleId, items, total, paid, change) {
    // Capture cart snapshot so names are available even after cart is cleared
    const cartSnapshot = Object.assign({}, cart);
    let rows = '';
    for (const item of items) {
        const p = cartSnapshot[item.product_id];
        const name = p ? p.name : `Product #${item.product_id}`;
        rows += `<tr><td>${name}</td><td>${item.quantity}</td><td>₱ ${parseFloat(item.price).toFixed(2)}</td><td>₱ ${(item.price * item.quantity).toFixed(2)}</td></tr>`;
    }
    document.getElementById('receiptContent').innerHTML = `
        <div class="receipt">
            <div class="receipt-header">
                <h3>POS System</h3>
                <p>Sari-Sari Store</p>
                <p>Receipt #${saleId}</p>
                <p>${new Date().toLocaleString()}</p>
            </div>
            <table class="table">
                <thead><tr><th>Item</th><th>Qty</th><th>Price</th><th>Sub</th></tr></thead>
                <tbody>${rows}</tbody>
            </table>
            <div class="receipt-totals">
                <p>Total: <strong>₱ ${total.toFixed(2)}</strong></p>
                <p>Payment: <strong>Cash — ₱ ${parseFloat(paid).toFixed(2)}</strong></p>
                <p>Change: <strong>₱ ${parseFloat(change).toFixed(2)}</strong></p>
            </div>
            <p class="text-center">Thank you!</p>
        </div>`;
    openModal('receiptModal');
}

function filterProducts() {
    const q    = document.getElementById('productSearch').value.toLowerCase();
    const cat  = document.getElementById('categoryFilter').value.toLowerCase();
    document.querySelectorAll('.product-card').forEach(card => {
        const name = card.dataset.name.toLowerCase();
        const c    = card.dataset.category.toLowerCase();
        card.style.display = (name.includes(q) && (cat === '' || c === cat)) ? '' : 'none';
    });
}

// Pusher
const pusher  = new Pusher(PUSHER_KEY, { cluster: PUSHER_CLUSTER });
const channel = pusher.subscribe('pos-channel');
channel.bind('sale-made', function(data) {
    const el = document.getElementById('pstock-' + data.product_id);
    if (el) {
        el.innerText = 'Stock: ' + data.new_stock;
        const card = el.closest('.product-card');
        if (card && data.new_stock <= 0) card.style.display = 'none';
    }
});
</script>
<?php include __DIR__ . '/../views/footer.php'; ?>