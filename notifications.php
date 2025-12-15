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
$unreadCount = 0;
if ($user) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM chat WHERE receiver_username = ? AND is_read = FALSE");
    $stmt->execute([$user]);
    $unreadCount = (int)$stmt->fetchColumn();
}

// Handle order actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $id = intval($_POST['id']);
    $action = $_POST['action'];
    $status = '';
    if ($action === 'accept') $status = 'preparing';
    elseif ($action === 'decline') $status = 'declined';
    elseif ($action === 'cancel') $status = 'cancelled';
    elseif ($action === 'pause') $status = 'paused';
    elseif ($action === 'success') $status = 'successful';
    if ($status) {
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
    }
    header('Location: notifications.php');
    exit;
}

$orders = $pdo->query("SELECT o.*, u.username FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC")->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Notifications</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
<header class="site-header">
    <nav class="nav-left">
        <div class="menu-wrap left">
            <button id="menuBtn" class="menu-button" aria-label="Menu">⋮</button>
            <div id="menuDropdown" class="menu-dropdown" aria-hidden="true">
                <a href="index.php">Home</a>
                <a href="admin.php">Admin Panel</a>
                <a href="?action=logout">Log Out</a>
            </div>
        </div>
    </nav>
    <div class="nav-right">
        <?php if ($user): ?>
            <a href="notifications.php" class="notif-link" style="margin-right:10px;"><img src="notifications.png" alt="Notifications" style="width:24px;height:24px;">
                <?php if ($unreadCount > 0): ?><span class="notif-badge"><?php echo $unreadCount > 9 ? '9+' : $unreadCount; ?></span><?php endif; ?>
            </a>
        <?php endif; ?>
        <?php if ($user): ?>
            <a href="my_orders.php" style="margin-right:10px;"><img src="orders.png" alt="My Orders" style="width:24px;height:24px;"></a>
        <?php endif; ?>
    </div>
</header>

<script>
(function(){
  function updateBadge(){
    fetch('get_unread_count.php?_='+Date.now())
      .then(r=>r.json())
      .then(data=>{
        var n = data.unread || 0;
        var link = document.querySelector('.notif-link');
        if (!link) return;
        var badge = link.querySelector('.notif-badge');
        if (n > 0) {
          if (!badge) { badge = document.createElement('span'); badge.className = 'notif-badge'; link.appendChild(badge); }
          badge.textContent = n > 9 ? '9+' : n;
        } else {
          if (badge) badge.remove();
        }
      }).catch(()=>{});
  }
  setInterval(updateBadge, 4000);
  document.addEventListener('visibilitychange', function(){ if (!document.hidden) updateBadge(); });
  updateBadge();
})();
</script>

<main class="container">
    <h2>New Orders</h2>
    <?php if (empty($orders)): ?>
        <p>No new orders.</p>
    <?php else: ?>
        <div class="products">
            <?php foreach ($orders as $o): ?>
                <div class="card">
                    <p>User: <?php echo htmlspecialchars($o['username']); ?></p>
                    <p>Product: <?php echo htmlspecialchars($o['product_id']); ?></p>
                    <p>Features: <?php echo htmlspecialchars($o['features']); ?></p>
                    <p>Total: $<?php echo htmlspecialchars($o['total_price']); ?></p>
                    <p>Contact: <?php echo htmlspecialchars($o['contact_type']); ?> - <?php echo htmlspecialchars($o['contact_value']); ?></p>
                    <p>Status: <?php echo htmlspecialchars($o['status']); ?></p>
                    <p>Time: <?php echo htmlspecialchars($o['created_at']); ?></p>
                    <div>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="id" value="<?php echo $o['id']; ?>">
                            <button type="submit" name="action" value="accept">Accept</button>
                            <button type="submit" name="action" value="decline">Decline</button>
                            <button type="submit" name="action" value="cancel">Cancel</button>
                            <button type="submit" name="action" value="pause">Pause</button>
                            <button type="submit" name="action" value="success">Mark Successful</button>
                        </form>
                    </div>
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