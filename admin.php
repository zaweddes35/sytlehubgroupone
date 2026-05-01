<?php
session_start();
require 'db.php';
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}

// Add product
if($_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['add_product'])) {
    $n = trim($_POST['name']);
    $d = trim($_POST['description']);
    $pr = (float)$_POST['price'];
    $cat = (int)$_POST['category_id'];
    $st = (int)$_POST['stock'];
    $pdo->prepare("INSERT INTO products (name,description,price,category_id,stock) VALUES (?,?,?,?,?)")->execute([$n,$d,$pr,$cat,$st]);
    $success = "Product added successfully!";
}
// Delete product
if(isset($_GET['del_product'])) {
    $pdo->prepare("DELETE FROM products WHERE id=?")->execute([(int)$_GET['del_product']]);
    header("Location: admin.php?tab=products"); exit;
}
// Update order status
if($_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['update_status'])) {
    $pdo->prepare("UPDATE orders SET status=? WHERE id=?")->execute([$_POST['status'],$_POST['order_id']]);
    $success = "Order status updated!";
}

$tab = $_GET['tab'] ?? 'dashboard';
$products = $pdo->query("SELECT p.*, c.name as category FROM products p LEFT JOIN categories c ON p.category_id=c.id ORDER BY p.id DESC")->fetchAll();
$orders = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC")->fetchAll();
$users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
$categories = $pdo->query("SELECT * FROM categories")->fetchAll();
$total_sales = $pdo->query("SELECT SUM(total) FROM orders")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — StyleHub</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .admin-layout { display:grid; grid-template-columns:220px 1fr; min-height:100vh; }
        .sidebar { background:#1a1a2e; padding:2rem 0; }
        .sidebar-logo { color:#e94560; font-size:1.3rem; font-weight:700; padding:0 1.5rem 2rem; display:block; }
        .sidebar a { display:block; color:#aaa; text-decoration:none; padding:0.75rem 1.5rem; font-size:0.9rem; transition:all 0.2s; }
        .sidebar a:hover, .sidebar a.active { background:#e94560; color:white; }
        .admin-content { padding:2rem; background:#f8f8f8; }
        .stats-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:1rem; margin-bottom:2rem; }
        .stat-card { background:white; border-radius:12px; padding:1.5rem; box-shadow:0 2px 8px rgba(0,0,0,0.08); }
        .stat-num { font-size:2rem; font-weight:700; color:#e94560; }
        .stat-label { color:#888; font-size:0.85rem; margin-top:0.3rem; }
    </style>
</head>
<body>
<div class="admin-layout">
    <div class="sidebar">
        <span class="sidebar-logo">&#9881; StyleHub Admin</span>
        <a href="admin.php" class="<?= $tab=='dashboard'?'active':'' ?>">&#128200; Dashboard</a>
        <a href="admin.php?tab=products" class="<?= $tab=='products'?'active':'' ?>">&#128722; Products</a>
        <a href="admin.php?tab=orders" class="<?= $tab=='orders'?'active':'' ?>">&#128230; Orders</a>
        <a href="admin.php?tab=users" class="<?= $tab=='users'?'active':'' ?>">&#128100; Customers</a>
        <a href="admin.php?tab=add_product" class="<?= $tab=='add_product'?'active':'' ?>">&#43; Add Product</a>
        <a href="index.php" style="margin-top:2rem;">&#8592; Back to Store</a>
        <a href="logout.php">&#128682; Logout</a>
    </div>

    <div class="admin-content">
        <?php if(isset($success)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if($tab=='dashboard'): ?>
            <h2 style="margin-bottom:1.5rem; color:#1a1a2e;">Dashboard Overview</h2>
            <div class="stats-grid">
                <div class="stat-card"><div class="stat-num"><?= count($products) ?></div><div class="stat-label">Total Products</div></div>
                <div class="stat-card"><div class="stat-num"><?= count($orders) ?></div><div class="stat-label">Total Orders</div></div>
                <div class="stat-card"><div class="stat-num"><?= count($users) ?></div><div class="stat-label">Customers</div></div>
                <div class="stat-card"><div class="stat-num">$<?= number_format($total_sales ?? 0, 2) ?></div><div class="stat-label">Total Revenue</div></div>
            </div>
            <h3 style="margin-bottom:1rem;">Recent Orders</h3>
            <table class="admin-table">
                <thead><tr><th>Order #</th><th>Customer</th><th>Total</th><th>Status</th><th>Date</th></tr></thead>
                <tbody>
                <?php foreach(array_slice($orders,0,5) as $o): ?>
                    <tr>
                        <td>#<?= $o['id'] ?></td>
                        <td><?= htmlspecialchars($o['full_name']) ?></td>
                        <td>$<?= number_format($o['total'],2) ?></td>
                        <td><span class="badge-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
                        <td><?= date('M d, Y', strtotime($o['created_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

        <?php elseif($tab=='products'): ?>
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
                <h2 style="color:#1a1a2e;">Products (<?= count($products) ?>)</h2>
                <a href="admin.php?tab=add_product" class="btn btn-primary">+ Add Product</a>
            </div>
            <table class="admin-table">
                <thead><tr><th>ID</th><th>Name</th><th>Category</th><th>Price</th><th>Stock</th><th>Action</th></tr></thead>
                <tbody>
                <?php foreach($products as $p): ?>
                    <tr>
                        <td><?= $p['id'] ?></td>
                        <td><?= htmlspecialchars($p['name']) ?></td>
                        <td><?= htmlspecialchars($p['category']) ?></td>
                        <td>$<?= number_format($p['price'],2) ?></td>
                        <td><?= $p['stock'] ?></td>
                        <td><a href="admin.php?del_product=<?= $p['id'] ?>" onclick="return confirm('Delete this product?')" style="color:#e94560; text-decoration:none; font-size:0.85rem;">Delete</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

        <?php elseif($tab=='orders'): ?>
            <h2 style="margin-bottom:1.5rem; color:#1a1a2e;">All Orders</h2>
            <table class="admin-table">
                <thead><tr><th>#</th><th>Customer</th><th>Email</th><th>Total</th><th>Status</th><th>Update</th></tr></thead>
                <tbody>
                <?php foreach($orders as $o): ?>
                    <tr>
                        <td><?= $o['id'] ?></td>
                        <td><?= htmlspecialchars($o['full_name']) ?></td>
                        <td><?= htmlspecialchars($o['email']) ?></td>
                        <td>$<?= number_format($o['total'],2) ?></td>
                        <td><span class="badge-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
                        <td>
                            <form method="POST" style="display:flex; gap:0.5rem;">
                                <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                <select name="status" style="padding:4px 8px; border-radius:6px; border:1px solid #ddd; font-size:0.85rem;">
                                    <option <?= $o['status']=='pending'?'selected':'' ?>>pending</option>
                                    <option <?= $o['status']=='processing'?'selected':'' ?>>processing</option>
                                    <option <?= $o['status']=='shipped'?'selected':'' ?>>shipped</option>
                                    <option <?= $o['status']=='delivered'?'selected':'' ?>>delivered</option>
                                </select>
                                <button type="submit" name="update_status" class="btn btn-primary" style="padding:4px 10px; font-size:0.8rem;">Save</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

        <?php elseif($tab=='users'): ?>
            <h2 style="margin-bottom:1.5rem; color:#1a1a2e;">Customers (<?= count($users) ?>)</h2>
            <table class="admin-table">
                <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Joined</th></tr></thead>
                <tbody>
                <?php foreach($users as $u): ?>
                    <tr>
                        <td><?= $u['id'] ?></td>
                        <td><?= htmlspecialchars($u['name']) ?></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td><span style="background:<?= $u['role']=='admin'?'#e94560':'#d4edda' ?>; color:<?= $u['role']=='admin'?'white':'#155724' ?>; padding:3px 10px; border-radius:20px; font-size:0.8rem;"><?= ucfirst($u['role']) ?></span></td>
                        <td><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

        <?php elseif($tab=='add_product'): ?>
            <h2 style="margin-bottom:1.5rem; color:#1a1a2e;">Add New Product</h2>
            <div class="form-box" style="max-width:600px; margin:0;">
                <form method="POST">
                    <div class="form-group"><label>Product Name</label><input type="text" name="name" required placeholder="e.g. Classic White T-Shirt"></div>
                    <div class="form-group"><label>Description</label><textarea name="description" required placeholder="Describe the product..."></textarea></div>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                        <div class="form-group"><label>Price ($)</label><input type="number" name="price" step="0.01" min="0" required placeholder="0.00"></div>
                        <div class="form-group"><label>Stock Quantity</label><input type="number" name="stock" min="0" value="10" required></div>
                    </div>
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category_id" required>
                            <?php foreach($categories as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" name="add_product" class="btn btn-primary" style="width:100%; padding:1rem;">Add Product</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>