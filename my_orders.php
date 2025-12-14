<?php
session_start();
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_unset();
    session_destroy();
    header('Location: index.php');
    exit;
}
require __DIR__ . '/config.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order'])) {
    $id = intval($_POST['id']);
    $stmt = $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ? AND user_id = (SELECT id FROM users WHERE username = ?)");
    $stmt->execute([$id, $user]);
    header('Location: my_orders.php');
    exit;
}

$userId = $pdo->query("SELECT id FROM users WHERE username = '$user'")->fetch()['id'];
$orders = $pdo->query("SELECT * FROM orders WHERE user_id = $userId ORDER BY created_at DESC")->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>My Orders</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .status-preparing { color: #ffdd00; animation: blink 1s infinite; }
        @keyframes blink { 0%, 50% { opacity: 1; } 51%, 100% { opacity: 0.5; } }
        .status-successful { color: #00ff00; }
        .status-pending { color: #ffa500; }
        .status-cancelled { color: #ff0000; }
    </style>
</head>
<body>
<header class="site-header">
    <nav class="nav-left">
        <div class="menu-wrap left">
            <button id="menuBtn" class="menu-button" aria-label="Menu">⋮</button>
            <div id="menuDropdown" class="menu-dropdown" aria-hidden="true">
                <a href="index.php">Home</a>
                <a href="my_orders.php">My Orders</a>
                <a href="?action=logout">Log Out</a>
            </div>
        </div>
    </nav>
    <div class="nav-right">
        <?php if ($user === 'admin'): ?>
            <a href="notifications.php" style="margin-right:10px;"><img src="notifications.png" alt="Notifications" style="width:24px;height:24px;"></a>
        <?php endif; ?>
        <?php if ($user): ?>
            <a href="my_orders.php" style="margin-right:10px;"><img src="orders.png" alt="My Orders" style="width:24px;height:24px;"></a>
        <?php endif; ?>
    </div>
</header>

<main class="container">
    <h2>My Orders</h2>
    <?php if (empty($orders)): ?>
        <p>No orders yet.</p>
    <?php else: ?>
        <div class="products">
            <?php foreach ($orders as $o): ?>
                <div class="card">
                    <p>Product: <?php echo htmlspecialchars($o['product_id']); ?></p>
                    <p>Features: <?php echo htmlspecialchars($o['features']); ?></p>
                    <p>Total: $<?php echo htmlspecialchars($o['total_price']); ?></p>
                    <p>Contact: <?php echo htmlspecialchars($o['contact_type']); ?> - <?php echo htmlspecialchars($o['contact_value']); ?></p>
                    <p>Status: <span class="status-<?php echo $o['status']; ?>"><?php echo ucfirst($o['status']); ?></span></p>
                    <p>Time: <?php echo htmlspecialchars($o['created_at']); ?></p>
                    <?php if ($o['status'] === 'pending'): ?>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="id" value="<?php echo $o['id']; ?>">
                            <button type="submit" name="cancel_order" onclick="return confirm('Cancel this order?')">Cancel Order</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<script>
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
</script>
</body>
</html>