<?php
session_start();
require 'db.php';
if(!isset($_GET['id'])) { header("Location: products.php"); exit; }
$stmt = $pdo->prepare("SELECT p.*, c.name as category FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE p.id=?");
$stmt->execute([$_GET['id']]);
$p = $stmt->fetch();
if(!$p) { header("Location: products.php"); exit; }
$cart_count = isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'],'qty')) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($p['name']) ?> — StyleHub</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<nav>
    <a class="nav-logo" href="index.php">StyleHub</a>
    <ul class="nav-links">
        <li><a href="index.php">Home</a></li>
        <li><a href="products.php">Shop</a></li>
        <li><a href="cart.php">Cart <span class="cart-badge"><?= $cart_count ?></span></a></li>
        <?php if(isset($_SESSION['user_id'])): ?>
            <li><a href="logout.php">Logout</a></li>
        <?php else: ?>
            <li><a href="login.php">Login</a></li>
        <?php endif; ?>
    </ul>
</nav>

<section>
    <div class="container" style="max-width:900px;">
        <a href="products.php" style="color:#e94560; text-decoration:none; font-size:0.9rem;">&larr; Back to Shop</a>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:3rem; margin-top:2rem; background:white; border-radius:16px; padding:2.5rem; box-shadow:0 2px 20px rgba(0,0,0,0.1);">
            <div style="background:linear-gradient(135deg,#667eea,#764ba2); border-radius:12px; display:flex; align-items:center; justify-content:center; min-height:300px; font-size:7rem;">
                👕
            </div>
            <div>
                <p style="color:#e94560; font-size:0.85rem; font-weight:600; text-transform:uppercase; letter-spacing:1px;"><?= htmlspecialchars($p['category']) ?></p>
                <h1 style="font-size:1.8rem; margin:0.5rem 0 1rem; color:#1a1a2e;"><?= htmlspecialchars($p['name']) ?></h1>
                <p style="font-size:2.2rem; font-weight:700; color:#e94560; margin-bottom:1rem;">$<?= number_format($p['price'],2) ?></p>
                <p style="color:#666; line-height:1.7; margin-bottom:1.5rem;"><?= htmlspecialchars($p['description']) ?></p>
                <p style="color:#888; margin-bottom:1.5rem; font-size:0.9rem;">
                    <?php if($p['stock'] > 0): ?>
                        <span style="color:#28a745;">&#10003; In Stock</span> (<?= $p['stock'] ?> available)
                    <?php else: ?>
                        <span style="color:#dc3545;">&#10007; Out of Stock</span>
                    <?php endif; ?>
                </p>
                <div style="display:flex; gap:1rem; flex-wrap:wrap;">
                    <a href="add_to_cart.php?id=<?= $p['id'] ?>" class="btn btn-primary" style="padding:0.8rem 2rem;">Add to Cart</a>
                    <a href="checkout.php" class="btn btn-dark" style="padding:0.8rem 2rem;">Buy Now</a>
                </div>
                <div style="margin-top:2rem; padding-top:1.5rem; border-top:1px solid #eee; font-size:0.85rem; color:#888;">
                    <p>&#128666; Free shipping on orders over $50</p>
                    <p style="margin-top:0.4rem;">&#8617; 30-day return policy</p>
                </div>
            </div>
        </div>
    </div>
</section>

<footer>
    <p>&copy; <?= date('Y') ?> <span>StyleHub</span> — All rights reserved.</p>
</footer>
</body>
</html>