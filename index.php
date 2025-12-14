<?php
session_start();
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_unset();
    session_destroy();
    header('Location: index.php');
    exit;
}
require __DIR__ . '/config.php';
$user = isset($_SESSION['user']) ? $_SESSION['user'] : null;
$products = json_decode(file_get_contents('products.json'), true) ?: [
    ['id'=>'1','name'=>'Nash3D','price'=>'By Ernyzas','img'=>'https://via.placeholder.com/400x250?text=nash3d']
];

// Handle product edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_product']) && $user === 'admin') {
    $id = $_POST['id'];
    foreach ($products as &$p) {
        if ($p['id'] == $id) {
            $p['title'] = $_POST['title'];
            $p['desc'] = $_POST['desc'];
            $p['price'] = $_POST['price'];
            $p['img'] = $_POST['img'];
            break;
        }
    }
    file_put_contents('products.json', json_encode($products, JSON_PRETTY_PRINT));
    header('Location: index.php');
    exit;
}
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Ernyzas Home Page</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
<header class="site-header">
    <nav class="nav-left">
        <div class="menu-wrap left">
            <button id="menuBtn" class="menu-button" aria-label="Menu">⋮</button>
            <div id="menuDropdown" class="menu-dropdown" aria-hidden="true">
                <a href="index.php">Home</a>
                <?php if (!$user): ?>
                    <a href="signup.php">Register</a>
                    <a href="login.php">Log In</a>
                <?php else: ?>
                    <a href="#">My Account (<?php echo htmlspecialchars($user); ?>)</a>
                    <a href="my_orders.php">My Orders</a>
                    <a href="?action=logout">Log Out</a>
                <?php endif; ?>
                <a href="https://t.me/nijonico" target="_blank" rel="noopener">Telegram</a>
            </div>
        </div>
    </nav>
    <div class="nav-right">
        <?php if ($user === 'admin'): ?>
            <a href="notifications.php" style="margin-right:10px;"><img src="notifications.png" alt="Notifications" style="width:24px;height:24px;"></a>
        <?php endif; ?>
    </div>
</header>

<main class="container">
    <h2>Product</h2>
    <div class="products single">
        <?php foreach ($products as $p): ?>
            <div class="card big">
                <div class="prod-header">
                    <div class="prod-marquee" aria-hidden="false"><span class="marquee-text"></span></div>
                    <h3 class="prod-name"><?php echo htmlspecialchars($p['name']); ?></h3>
                </div>
                <p class="bykey"><?php echo htmlspecialchars($p['price']); ?></p>
                <div class="actions">
                    <button id="showFeaturesBtn" class="show-features">Select Features</button>
                    <?php if ($user === 'admin'): ?>
                        <button onclick="editProduct('<?php echo $p['id']; ?>')" class="show-features">Edit Product</button>
                    <?php endif; ?>
                </div>
                <div id="featureDetails" class="feature-details" aria-hidden="true" style="display:none;">
                    <h4>Cheat Features (select to add)</h4>
                    <div class="features">
                        <label class="select-full"><input type="checkbox" id="selectFullPackage" checked> Select all</label>
                        <label class="feature-item"><input type="checkbox" data-name="Aimbot" data-price="9"> Aimbot <span class="feat-price">$9</span></label>
                        <label class="feature-item"><input type="checkbox" data-name="Wallhack / ESP" data-price="7"> Wallhack / ESP <span class="feat-price">$7</span></label>
                        <label class="feature-item"><input type="checkbox" data-name="No Recoil" data-price="2"> No Recoil <span class="feat-price">$2</span></label>
                        <label class="feature-item"><input type="checkbox" data-name="Speedhack" data-price="2"> Speedhack <span class="feat-price">$2</span></label>
                        <label class="feature-item"><input type="checkbox" data-name="Triggerbot" data-price="1"> Triggerbot <span class="feat-price">$1</span></label>
                        <label class="feature-item"><input type="checkbox" data-name="Bunnyhop" data-price="2"> Bunnyhop <span class="feat-price">$2</span></label>
                        <label class="feature-item"><input type="checkbox" data-name="Radarhack" data-price="1"> Radarhack <span class="feat-price">$1</span></label>
                        <label class="feature-item"><input type="checkbox" data-name="Silent Aim" data-price="1"> Silent Aim <span class="feat-price">$1</span></label>
                        <label class="feature-item"><input type="checkbox" data-name="Spinbot" data-price="2"> Spinbot <span class="feat-price">$2</span></label>
                        <label class="feature-item"><input type="checkbox" data-name="No Flash / No Smoke" data-price="2"> No Flash / No Smoke <span class="feat-price">$1</span></label>
                        <label class="feature-item"><input type="checkbox" data-name="Auto Shoot / Auto Fire" data-price="1"> Auto Shoot / Auto Fire <span class="feat-price">$1</span></label>
                        <label class="feature-item"><input type="checkbox" data-name="Backtrack" data-price="3"> Backtrack <span class="feat-price">$3</span></label>
                    </div>
                    <div id="capNotice" class="cap-notice" style="display:none;margin-top:8px;color:#ffcccb">Maximum total $35 — some features were deselected.</div>
                    <div id="minNotice" class="min-notice" style="display:none;margin-top:8px;color:#ffdca8">Please select items which cost higher than $5.</div>
                </div>

                <div class="order-summary">
                    <span class="base-price" data-base="0">Base: $0</span>
                    <span class="selected-price">Selected: $0</span>
                    <span class="total-price">Total: $0</span>
                    <?php if ($user): ?>
                        <button id="placeOrderBtn" class="buy-btn">Place Order</button>
                    <?php else: ?>
                        <a href="login.php" class="buy-btn" style="display:inline-block;text-decoration:none;">Login to Order</a>
                    <?php endif; ?>
                </div>

                <div class="payment-options">
                    <h4>Payment Options</h4>
                    <div class="payments">
                        <span class="payment-link" data-src="master.png" title="Mastercard logo"></span>
                        <span class="payment-link" data-src="enpddY.png" title="Visa logo"></span>
                        <span class="payment-link" data-src="paypal.jpg" title="PayPal logo"></span>
                        <span class="payment-link" data-src="btc.jpg" title="Bitcoin logo"></span>
                        <span class="payment-link" data-src="litecoin.jpg" title="Litecoin logo"></span>
                        <span class="payment-link" data-src="toncoin.png" title="Toncoin logo"></span>
                    </div>
                </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Trending section removed per request; products are user-added via modal and shown in products.json -->

    <!-- Add Product feature removed -->
</main>

<!-- Order Modal -->
<div id="orderModal" class="modal" aria-hidden="true">
    <div class="modal-content">
        <button class="modal-close" id="closeOrderModal">&times;</button>
        <h3>Place Your Order</h3>
        <form id="orderForm">
            <label for="contactType">Contact Platform:</label>
            <select id="contactType" name="contactType" required>
                <option value="">Select...</option>
                <option value="telegram">Telegram</option>
                <option value="discord">Discord</option>
                <option value="whatsapp">WhatsApp</option>
            </select>
            <label for="contactValue">Profile Link / Number:</label>
            <input type="text" id="contactValue" name="contactValue" placeholder="e.g. @username or +1234567890" required>
            <input type="hidden" id="orderFeatures" name="features">
            <input type="hidden" id="orderTotal" name="total">
            <input type="hidden" id="orderProductId" name="productId" value="1">
            <button type="submit" class="buy-btn">Submit Order</button>
        </form>
        <div id="orderResult" class="add-result"></div>
    </div>
</div>

<!-- Edit Product Modal -->
<div id="editModal" class="modal" aria-hidden="true">
    <div class="modal-content">
        <button class="modal-close" id="closeEditModal">&times;</button>
        <h3>Edit Product</h3>
        <form method="post">
            <input type="hidden" name="id" id="editId">
            <label>Title: <input type="text" name="title" id="editTitle" required></label>
            <label>Description: <textarea name="desc" id="editDesc"></textarea></label>
            <label>Price: <input type="text" name="price" id="editPrice"></label>
            <label>Image URL: <input type="text" name="img" id="editImg"></label>
            <button type="submit" name="edit_product">Save</button>
        </form>
    </div>
</div>

<footer class="site-footer">
</footer>

<script>
// Expose login state to JS
window.APP = {};
window.APP.isLoggedIn = <?php echo $user ? 'true' : 'false'; ?>;
window.APP.username = <?php echo $user ? json_encode($user) : 'null'; ?>;

// Dropdown menu toggle and entrance animations
document.addEventListener('click', function(e){
    var btn = document.getElementById('menuBtn');
    var dd = document.getElementById('menuDropdown');
    if (!btn) return;
    if (btn.contains(e.target)) {
        var hidden = dd.getAttribute('aria-hidden') === 'true';
        dd.style.display = hidden ? 'block' : 'none';
        dd.setAttribute('aria-hidden', hidden ? 'false' : 'true');
    } else {
        if (!dd.contains(e.target)) {
            dd.style.display = 'none';
            dd.setAttribute('aria-hidden','true');
        }
    }
});

// Add loaded class to trigger CSS animations
document.addEventListener('DOMContentLoaded', function(){
    setTimeout(function(){ document.body.classList.add('is-loaded'); }, 60);
});
// Add-product JS removed (feature disabled)

// Initialize payment logos: if href points to an image file, inject an <img>, otherwise show the data-name
document.addEventListener('DOMContentLoaded', function(){
    // Initialize payment logos (non-clickable): use data-src on spans
    document.querySelectorAll('.payment-link').forEach(function(el){
        var src = el.getAttribute('data-src') || '';
        var title = el.getAttribute('title') || '';
        if (src && /\.(png|jpg|jpeg|svg|gif)(\?|$)/i.test(src)) {
            var img = document.createElement('img');
            img.src = src;
            img.alt = title;
            el.textContent = '';
            el.appendChild(img);
            el.classList.add('has-img');
        } else {
            el.textContent = title || '';
        }
    });

    // Custom features: update totals and order link
    document.querySelectorAll('.products.single').forEach(function(container){
        var baseTextEl = container.querySelector('.base-price');
        var selectedEl = container.querySelector('.selected-price');
        var totalEl = container.querySelector('.total-price');
        var placeBtn = container.querySelector('#placeOrderBtn');
        if (!baseTextEl || !totalEl || !placeBtn) return;
        // use data-base if provided (we set data-base="0" so full-cheat pricing comes from features)
        var baseNum = parseFloat(baseTextEl.getAttribute('data-base')) || (function(){ var t=baseTextEl.textContent||''; return parseFloat(t.replace(/[^0-9\.\-]/g,''))||0; })();

        function recalc(){
            var sum = 0;
            var sel = [];
            var checks = Array.from(container.querySelectorAll('.feature-item input[type=checkbox]'));
            checks.forEach(function(ch){
                if (ch.checked) {
                    var p = parseFloat(ch.getAttribute('data-price')) || 0;
                    sum += p;
                    sel.push({el: ch, name: ch.getAttribute('data-name') || '', price: p});
                }
            });

            var capTotal = 35; // maximum total (USD)
            var madeAdjustments = false;
            if ((baseNum + sum) > capTotal) {
                // if over cap, deselect expensive features first until within cap
                sel.sort(function(a,b){ return b.price - a.price; });
                for (var i=0; i<sel.length && (baseNum + sum) > capTotal; i++){
                    sel[i].el.checked = false;
                    sum -= sel[i].price;
                    madeAdjustments = true;
                }
            }

            // recompute selected names after any adjustments
            var names = checks.filter(function(c){ return c.checked; }).map(function(c){ return c.getAttribute('data-name') || ''; });
            selectedEl.textContent = 'Selected: $' + sum;
            var total = baseNum + sum;
            if (total > capTotal) total = capTotal;
            totalEl.textContent = 'Total: $' + total;

            // update place order message
            var prodName = container.querySelector('.prod-name') ? container.querySelector('.prod-name').textContent : 'Product';
            var msg = prodName + ' - Total: $' + total + '\nFeatures: ' + (names.length?names.join(', '):'None');
            var tg = 'https://t.me/nijonico?text=' + encodeURIComponent(msg);

            // enforce minimum purchase $7 and require at least one selected
            var minPurchase = 5;
            var allowPurchase = (names.length > 0 && sum >= minPurchase);
            var minNotice = container.querySelector('#minNotice');
            if (allowPurchase) {
                placeBtn.setAttribute('href', tg);
                placeBtn.classList.remove('disabled');
                placeBtn.removeAttribute('aria-disabled');
                if (minNotice) minNotice.style.display = 'none';
            } else {
                placeBtn.setAttribute('href', '#');
                placeBtn.classList.add('disabled');
                placeBtn.setAttribute('aria-disabled', 'true');
                if (minNotice) minNotice.style.display = 'block';
            }

            var capNotice = container.querySelector('#capNotice');
            if (capNotice) {
                capNotice.style.display = madeAdjustments ? 'block' : 'none';
                if (madeAdjustments) setTimeout(function(){ capNotice.style.display = 'none'; }, 4000);
            }
        }

        // wire check change
        container.querySelectorAll('.feature-item input[type=checkbox]').forEach(function(ch){ ch.addEventListener('change', function(){
            recalc();
            // if any feature unchecked, uncheck full-package; if all checked, check full-package
            var all = Array.from(container.querySelectorAll('.feature-item input[type=checkbox]')).every(function(c){ return c.checked; });
            var full = container.querySelector('#selectFullPackage'); if (full) full.checked = all;
        }); });

        // full-package checkbox: select/deselect all features
        var fullChk = container.querySelector('#selectFullPackage');
        if (fullChk) {
            fullChk.addEventListener('change', function(){
                var checks = Array.from(container.querySelectorAll('.feature-item input[type=checkbox]'));
                checks.forEach(function(c){ c.checked = fullChk.checked; });
                recalc();
            });
            // if full-package is checked on load, select all features
            if (fullChk.checked) {
                var checks2 = Array.from(container.querySelectorAll('.feature-item input[type=checkbox]'));
                checks2.forEach(function(c){ c.checked = true; });
            }
        }

        recalc();
    });
});

// Product marquee: build phrases and scroll infinitely
document.addEventListener('DOMContentLoaded', function(){
    document.querySelectorAll('.card').forEach(function(card){
        var marqueeEl = card.querySelector('.marquee-text');
        if (!marqueeEl) return;
        // gather feature names (don't repeat product name)
        var feats = Array.from(card.querySelectorAll('.feature-item input[data-name]')).map(function(i){ return i.getAttribute('data-name'); });
        var phrases = [];
        if (feats.length) phrases.push('Available features: ' + feats.join(' · '));
        phrases.push('The best hack ever');
        phrases.push('Unlimited performance');
        // build long text by joining phrases with separators so marquee has content
        var long = Array(6).fill(phrases.join('  •  ')).join('   ---   ');
        marqueeEl.textContent = long;
    });
});


// Show features toggle
(function(){
    var btn = document.getElementById('showFeaturesBtn');
    var details = document.getElementById('featureDetails');
    if (!btn || !details) return;
    btn.addEventListener('click', function(){
        var hidden = details.getAttribute('aria-hidden') === 'true';
        details.setAttribute('aria-hidden', hidden ? 'false' : 'true');
        details.style.display = hidden ? 'block' : 'none';
        btn.textContent = hidden ? 'Hide Features' : 'Select Features';
        if (hidden) details.scrollIntoView({behavior:'smooth', block:'center'});
    });
})();

// Order Modal
(function(){
    var modal = document.getElementById('orderModal');
    var btn = document.getElementById('placeOrderBtn');
    var closeBtn = document.getElementById('closeOrderModal');
    var form = document.getElementById('orderForm');
    var result = document.getElementById('orderResult');

    if (!modal || !btn || !closeBtn || !form) return;

    btn.addEventListener('click', function(){
        if (btn.disabled) return;
        // Populate hidden fields
        var features = Array.from(document.querySelectorAll('.feature-item input:checked')).map(function(c){ return c.getAttribute('data-name'); }).join(', ');
        var total = document.querySelector('.total-price').textContent.replace('Total: $', '');
        document.getElementById('orderFeatures').value = features;
        document.getElementById('orderTotal').value = total;
        modal.setAttribute('aria-hidden', 'false');
    });

    closeBtn.addEventListener('click', function(){
        modal.setAttribute('aria-hidden', 'true');
    });

    form.addEventListener('submit', function(e){
        e.preventDefault();
        var formData = new FormData(form);
        fetch('place_order.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            result.textContent = data.message || data.error;
            if (data.success) {
                result.textContent = 'Thank you for your order! Our admin team will contact you soon.';
                setTimeout(function(){ modal.setAttribute('aria-hidden', 'true'); form.reset(); }, 3000);
            }
        })
        .catch(error => {
            result.textContent = 'Error: ' + error.message;
        });
    });
})();

// Edit Product
(function(){
    var editModal = document.getElementById('editModal');
    var closeEditBtn = document.getElementById('closeEditModal');

    window.editProduct = function(id) {
        var products = <?php echo json_encode($products); ?>;
        var product = products.find(p => p.id == id);
        if (product) {
            document.getElementById('editId').value = product.id;
            document.getElementById('editTitle').value = product.title || product.name;
            document.getElementById('editDesc').value = product.desc || '';
            document.getElementById('editPrice').value = product.price;
            document.getElementById('editImg').value = product.img;
            editModal.setAttribute('aria-hidden', 'false');
        }
    };

    closeEditBtn.addEventListener('click', function(){
        editModal.setAttribute('aria-hidden', 'true');
    });
})();
</script>
</body>
</html>
