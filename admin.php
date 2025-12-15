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

$isAdmin = ($user === 'admin');

$unreadCount = 0;
if ($user) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM chat WHERE receiver_username = ? AND is_read = FALSE");
    $stmt->execute([$user]);
    $unreadCount = (int)$stmt->fetchColumn();
}
if (!$isAdmin) {
    echo "Access denied.";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_product'])) {
    $products = json_decode(file_get_contents('products.json'), true) ?: [];
    $id = $_POST['id'];
    foreach ($products as &$p) {
        if ($p['id'] == $id) {
            // store as `name` to match index usage; keep `title` if present for compatibility
            $p['name'] = $_POST['title'];
            $p['title'] = $_POST['title'];
            $p['desc'] = $_POST['desc'];
            $p['price'] = $_POST['price'];
            $p['img'] = $_POST['img'];
            break;
        }
    }
    file_put_contents('products.json', json_encode($products, JSON_PRETTY_PRINT));
    header('Location: admin.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_order'])) {
    $id = intval($_POST['id']);
    $status = $_POST['status'];
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$status, $id]);
    header('Location: admin.php');
    exit;
}

// Handle adding a new product with optional uploaded icon
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $products = json_decode(file_get_contents('products.json'), true) ?: [];
    $title = trim($_POST['title'] ?? '');
    $desc = trim($_POST['desc'] ?? '');
    $price = trim($_POST['price'] ?? '');

    // handle upload
    $imgPath = trim($_POST['img'] ?? '');
    if (isset($_FILES['icon']) && $_FILES['icon']['error'] === UPLOAD_ERR_OK) {
        $f = $_FILES['icon'];
        $allowed = ['image/png', 'image/jpeg', 'image/webp', 'image/gif'];
        $maxSize = 2 * 1024 * 1024; // 2MB
        if ($f['size'] > $maxSize) {
            $uploadError = 'Icon file is too large (max 2MB).';
        } elseif (!in_array(mime_content_type($f['tmp_name']), $allowed)) {
            $uploadError = 'Invalid icon file type.';
        } else {
            $uploadsDir = __DIR__ . '/uploads';
            if (!is_dir($uploadsDir)) mkdir($uploadsDir, 0755, true);
            $ext = pathinfo($f['name'], PATHINFO_EXTENSION);
            $base = preg_replace('/[^a-z0-9_\-]/i', '_', pathinfo($f['name'], PATHINFO_FILENAME));
            $targetName = $base . '_' . time() . '.' . $ext;
            $targetPath = $uploadsDir . '/' . $targetName;
            if (!move_uploaded_file($f['tmp_name'], $targetPath)) {
                $uploadError = 'Failed to move uploaded file.';
            } else {
                $imgPath = 'uploads/' . $targetName;
            }
        }
    }

    // require title and price
    if ($title === '' || $price === '') {
        $uploadError = $uploadError ?? 'Title and price are required.';
    }

    if (!isset($uploadError)) {
        // create new product id
        $id = uniqid();
        $products[] = [
            'id' => $id,
            'name' => $title,
            'title' => $title,
            'desc' => $desc,
            'price' => $price,
            'img' => $imgPath ?: ''
        ];
        file_put_contents('products.json', json_encode($products, JSON_PRETTY_PRINT));
        header('Location: admin.php');
        exit;
    }
}

$products = json_decode(file_get_contents('products.json'), true) ?: [];
$orders = $pdo->query("SELECT o.*, u.username FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC")->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
<header class="site-header">
    <nav class="nav-left">
        <div class="menu-wrap left">
            <button id="menuBtn" class="menu-button" aria-label="Menu">⋮</button>
            <div id="menuDropdown" class="menu-dropdown" aria-hidden="true">
                <a href="index.php">Home</a>
                <a href="admin.php?open=add">Add Product</a>
                <a href="chat.php">Chat</a>
                <a href="?action=logout">Log Out</a>
            </div>
        </div>
    </nav>
    <div class="nav-right">
        <span class="welcome">Admin Panel</span>
        <?php if ($user): ?>
            <a href="notifications.php" class="notif-link" style="margin-left:10px;"><img src="notifications.png" alt="Notifications" style="width:24px;height:24px;">
                <?php if ($unreadCount > 0): ?><span class="notif-badge"><?php echo $unreadCount > 9 ? '9+' : $unreadCount; ?></span><?php endif; ?>
            </a>
        <?php endif; ?>
        <?php if ($user): ?>
            <a href="my_orders.php" style="margin-left:10px;"><img src="orders.png" alt="My Orders" style="width:24px;height:24px;"></a>
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
    <h2>Products</h2>
    <div class="products">
        <?php foreach ($products as $p): ?>
            <div class="card">
                <?php if (!empty($p['img'])): ?>
                    <img src="<?php echo htmlspecialchars($p['img']); ?>" alt="<?php echo htmlspecialchars($p['title']); ?>">
                <?php else: ?>
                    <div style="width:100%;height:150px;background:#eee;display:flex;align-items:center;justify-content:center;color:#666;">No image</div>
                <?php endif; ?>
                <h3><?php echo htmlspecialchars($p['name'] ?? $p['title'] ?? ''); ?></h3>
                <p><?php echo htmlspecialchars($p['desc'] ?? ''); ?></p>
                <p>Price: <?php echo htmlspecialchars($p['price'] ?? ''); ?></p>
                <button onclick="editProduct('<?php echo $p['id']; ?>')">Edit</button>
            </div>
        <?php endforeach; ?>
    </div>

    <h2>Orders</h2>
    <div class="orders">
        <?php foreach ($orders as $o): ?>
            <div class="card">
                <p>User: <?php echo htmlspecialchars($o['username']); ?> (IP: <?php echo htmlspecialchars($o['ip_address']); ?>)</p>
                <p>Product: <?php echo htmlspecialchars($o['product_id']); ?></p>
                <p>Features: <?php echo htmlspecialchars($o['features']); ?></p>
                <p>Total: $<?php echo htmlspecialchars($o['total_price']); ?></p>
                <p>Contact: <?php echo htmlspecialchars($o['contact_type']); ?> - <?php echo htmlspecialchars($o['contact_value']); ?></p>
                <p>Status: <?php echo htmlspecialchars($o['status']); ?></p>
                <form method="post" style="display:inline;">
                    <input type="hidden" name="id" value="<?php echo $o['id']; ?>">
                    <select name="status">
                        <option value="pending" <?php if ($o['status'] == 'pending') echo 'selected'; ?>>Pending</option>
                        <option value="completed" <?php if ($o['status'] == 'completed') echo 'selected'; ?>>Completed</option>
                        <option value="cancelled" <?php if ($o['status'] == 'cancelled') echo 'selected'; ?>>Cancelled</option>
                    </select>
                    <button type="submit" name="update_order">Update</button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
</main>

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

<div id="addModal" class="modal" aria-hidden="true">
    <div class="modal-content">
        <button class="modal-close" id="closeAddModal">&times;</button>
        <h3>Add Product</h3>
        <?php if (!empty($uploadError)): ?><div class="errors"><?php echo htmlspecialchars($uploadError); ?></div><?php endif; ?>
        <form method="post" enctype="multipart/form-data">
            <label>Title: <input type="text" name="title" id="addTitle" required></label>
            <label>Description: <textarea name="desc" id="addDesc"></textarea></label>
            <label>Price: <input type="text" name="price" id="addPrice"></label>
            <label>Image URL (optional): <input type="text" name="img" id="addImg"></label>
            <label>Upload Icon (optional): <input type="file" name="icon" accept="image/*"></label>
            <button type="submit" name="add_product">Add Product</button>
        </form>
    </div>
</div>

<button id="openAddBtn" style="position:fixed;right:20px;bottom:20px;padding:10px 15px;">Add Product</button>

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

function editProduct(id) {
    var products = <?php echo json_encode($products); ?>;
    var product = products.find(p => p.id == id);
    if (product) {
        document.getElementById('editId').value = product.id;
        document.getElementById('editTitle').value = product.name || product.title || '';
        document.getElementById('editDesc').value = product.desc || '';
        document.getElementById('editPrice').value = product.price || '';
        document.getElementById('editImg').value = product.img || '';
        document.getElementById('editModal').setAttribute('aria-hidden', 'false');
    }
}

document.getElementById('closeEditModal').addEventListener('click', function(){
    document.getElementById('editModal').setAttribute('aria-hidden', 'true');
});

// Add product modal handlers
document.getElementById('openAddBtn').addEventListener('click', function(){
    document.getElementById('addModal').setAttribute('aria-hidden', 'false');
});

document.getElementById('closeAddModal').addEventListener('click', function(){
    document.getElementById('addModal').setAttribute('aria-hidden', 'true');
});

// Open add modal if requested via query param (admin.php?open=add)
try {
    if (new URLSearchParams(window.location.search).get('open') === 'add') {
        document.getElementById('addModal').setAttribute('aria-hidden', 'false');
    }
} catch (e) {
    // ignore in older browsers
}
</script>
</body>
</html>