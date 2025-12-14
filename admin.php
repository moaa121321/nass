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

if (!$isAdmin) {
    echo "Access denied.";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_product'])) {
    $products = json_decode(file_get_contents('products.json'), true) ?: [];
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
                <a href="chat.php">Chat</a>
                <a href="?action=logout">Log Out</a>
            </div>
        </div>
    </nav>
    <div class="nav-right">
        <span class="welcome">Admin Panel</span>
        <?php if ($user === 'admin'): ?>
            <a href="notifications.php" style="margin-left:10px;"><img src="notifications.png" alt="Notifications" style="width:24px;height:24px;"></a>
        <?php endif; ?>
        <?php if ($user): ?>
            <a href="my_orders.php" style="margin-left:10px;"><img src="orders.png" alt="My Orders" style="width:24px;height:24px;"></a>
        <?php endif; ?>
    </div>
</header>

<main class="container">
    <h2>Products</h2>
    <div class="products">
        <?php foreach ($products as $p): ?>
            <div class="card">
                <img src="<?php echo htmlspecialchars($p['img']); ?>" alt="<?php echo htmlspecialchars($p['title']); ?>">
                <h3><?php echo htmlspecialchars($p['title']); ?></h3>
                <p><?php echo htmlspecialchars($p['desc']); ?></p>
                <p>Price: <?php echo htmlspecialchars($p['price']); ?></p>
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
        document.getElementById('editTitle').value = product.title;
        document.getElementById('editDesc').value = product.desc;
        document.getElementById('editPrice').value = product.price;
        document.getElementById('editImg').value = product.img;
        document.getElementById('editModal').setAttribute('aria-hidden', 'false');
    }
}

document.getElementById('closeEditModal').addEventListener('click', function(){
    document.getElementById('editModal').setAttribute('aria-hidden', 'true');
});
</script>
</body>
</html>