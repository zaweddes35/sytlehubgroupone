<?php
session_start();
require 'db.php';

$where = "WHERE 1=1";
$params = [];

if(!empty($_GET['cat'])) {
    $where .= " AND p.category_id = ?";
    $params[] = (int)$_GET['cat'];
}
if(!empty($_GET['search'])) {
    $where .= " AND p.name LIKE ?";
    $params[] = "%" . $_GET['search'] . "%";
}

$sort = "ORDER BY p.id DESC";
if(!empty($_GET['sort'])) {
    if($_GET['sort'] == 'price_asc') $sort = "ORDER BY p.price ASC";
    if($_GET['sort'] == 'price_desc') $sort = "ORDER BY p.price DESC";
    if($_GET['sort'] == 'name') $sort = "ORDER BY p.name ASC";
}

$stmt = $pdo->prepare("SELECT p.*, c.name as category FROM products p LEFT JOIN categories c ON p.category_id=c.id $where $sort");
$stmt->execute($params);
$products = $stmt->fetchAll();

$cats = $pdo->query("SELECT * FROM categories")->fetchAll();
$cart_count = isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'qty')) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop — StyleHub</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<nav>
    <a class="nav-logo" href="index.php">StyleHub</a>
    <ul class="nav-links">
        <li><a href="index.php">Home</a></li>
        <li><a href="products.php">Shop</a></li>
        <?php if(isset($_SESSION['user_id'])): ?>
            <li><a href="cart.php">Cart <span class="cart-badge"><?= $cart_count ?></span></a></li>
            <?php if($_SESSION['role']=='admin'): ?><li><a href="admin.php">Admin</a></li><?php endif; ?>
            <li><a href="logout.php">Logout</a></li>
        <?php else: ?>
            <li><a href="cart.php">Cart <span class="cart-badge"><?= $cart_count ?></span></a></li>
            <li><a href="login.php">Login</a></li>
        <?php endif; ?>
    </ul>
</nav>

<section>
    <h2 class="section-title">Our Collection</h2>
    <p class="section-sub"><?= count($products) ?> items found</p>

    <div class="container">
        <form method="GET" class="filter-bar">
            <input type="text" name="search" placeholder="Search products..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
            <select name="cat">
                <option value="">All Categories</option>
                <?php foreach($cats as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= (isset($_GET['cat']) && $_GET['cat']==$c['id'])? 'selected':'' ?>>
                        <?= htmlspecialchars($c['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="sort">
                <option value="">Sort By</option>
                <option value="price_asc" <?= (isset($_GET['sort']) && $_GET['sort']=='price_asc')?'selected':'' ?>>Price: Low to High</option>
                <option value="price_desc" <?= (isset($_GET['sort']) && $_GET['sort']=='price_desc')?'selected':'' ?>>Price: High to Low</option>
                <option value="name" <?= (isset($_GET['sort']) && $_GET['sort']=='name')?'selected':'' ?>>Name A-Z</option>
            </select>
            <button type="submit" class="btn btn-primary" style="padding:0.5rem 1.5rem;">Filter</button>
            <a href="products.php" class="btn btn-outline" style="padding:0.5rem 1.5rem;">Clear</a>
        </form>

        <?php if(empty($products)): ?>
            <div class="alert alert-info">No products found. <a href="products.php">View all products</a></div>
        <?php else: ?>
        <div class="products-grid">
            <?php foreach($products as $p): ?>
            <div class="product-card">
                <div class="product-img">👕</div>
                <div class="product-info">
                    <p class="product-name"><?= htmlspecialchars($p['name']) ?></p>
                    <p class="product-desc"><?= htmlspecialchars(substr($p['description'],0,70)) ?>...</p>
                    <p class="product-price">$<?= number_format($p['price'],2) ?></p>
                    <small style="color:#aaa; font-size:0.8rem;">Stock: <?= $p['stock'] ?></small>
                    <div class="product-footer" style="margin-top:0.8rem;">
                        <a href="add_to_cart.php?id=<?= $p['id'] ?>" class="add-cart-btn">Add to Cart</a>
                        <a href="product_detail.php?id=<?= $p['id'] ?>" class="view-btn">View</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<footer>
    <p>&copy; <?= date('Y') ?> <span>StyleHub</span> — All rights reserved.</p>
</footer>
</body>
</html>