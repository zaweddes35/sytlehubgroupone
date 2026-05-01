<?php
session_start();
require 'db.php';
$stmt = $pdo->query("SELECT p.*, c.name as category FROM products p LEFT JOIN categories c ON p.category_id=c.id ORDER BY p.id DESC LIMIT 8");
$featured = $stmt->fetchAll();
$cart_count = isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'qty')) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StyleHub — Premium Fashion Store</title>
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
            <?php if($_SESSION['role'] == 'admin'): ?>
                <li><a href="admin.php">Admin</a></li>
            <?php endif; ?>
            <li><a href="logout.php">Logout</a></li>
        <?php else: ?>
            <li><a href="cart.php">Cart <span class="cart-badge"><?= $cart_count ?></span></a></li>
            <li><a href="login.php">Login</a></li>
        <?php endif; ?>
    </ul>
</nav>

<!-- HERO -->
<section class="hero">
    <h1>Dress to <span>Impress</span></h1>
    <p>Discover the latest trends in fashion — Men, Women & Kids collections</p>
    <a href="products.php" class="btn btn-primary">Shop Now</a>
    &nbsp;
    <a href="#featured" class="btn btn-outline">View Featured</a>
</section>

<!-- CATEGORIES -->
<section style="background:#fff;">
    <h2 class="section-title">Browse Categories</h2>
    <p class="section-sub">Find exactly what you're looking for</p>
    <div class="categories-grid">
        <a href="products.php?cat=1" class="category-card"><span class="category-icon">👔</span>Men</a>
        <a href="products.php?cat=2" class="category-card"><span class="category-icon">👗</span>Women</a>
        <a href="products.php?cat=3" class="category-card"><span class="category-icon">🧒</span>Kids</a>
        <a href="products.php?cat=4" class="category-card"><span class="category-icon">👜</span>Accessories</a>
    </div>
</section>

<!-- FEATURED PRODUCTS -->
<section id="featured">
    <h2 class="section-title">Featured Products</h2>
    <p class="section-sub">Handpicked just for you</p>
    <div class="products-grid">
        <?php foreach($featured as $p): ?>
        <div class="product-card">
            <div class="product-img">👕</div>
            <div class="product-info">
                <p class="product-name"><?= htmlspecialchars($p['name']) ?></p>
                <p class="product-desc"><?= htmlspecialchars(substr($p['description'],0,70)) ?>...</p>
                <p class="product-price">$<?= number_format($p['price'],2) ?></p>
                <div class="product-footer">
                    <a href="add_to_cart.php?id=<?= $p['id'] ?>" class="add-cart-btn">Add to Cart</a>
                    <a href="product_detail.php?id=<?= $p['id'] ?>" class="view-btn">View</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- PROMO BANNER -->
<section style="background:#e94560; color:white; text-align:center; padding:3rem 2rem;">
    <h2 style="font-size:2rem; margin-bottom:0.5rem;">Get 20% Off Your First Order!</h2>
    <p style="font-size:1.1rem; margin-bottom:1.5rem; opacity:0.9;">Sign up today and use code <strong>STYLE20</strong> at checkout</p>
    <a href="login.php" class="btn" style="background:white; color:#e94560;">Create Account</a>
</section>

<footer>
    <p>&copy; <?= date('Y') ?> <span>StyleHub</span> — All rights reserved. Built with passion for fashion.</p>
</footer>

</body>
</html>