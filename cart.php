<?php
session_start();
require 'db.php';
if(!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

// Remove item
if(isset($_GET['remove'])) {
    $rid = (int)$_GET['remove'];
    unset($_SESSION['cart'][$rid]);
    header("Location: cart.php");
    exit;
}
// Update quantity
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update'])) {
    foreach($_POST['qty'] as $pid => $qty) {
        $qty = (int)$qty;
        if($qty <= 0) unset($_SESSION['cart'][$pid]);
        else $_SESSION['cart'][$pid]['qty'] = $qty;
    }
    header("Location: cart.php");
    exit;
}

$total = 0;
foreach($_SESSION['cart'] as $item) { $total += $item['price'] * $item['qty']; }
$cart_count = array_sum(array_column($_SESSION['cart'],'qty'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart — StyleHub</title>
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
    <div class="container">
        <h2 class="section-title">Your Shopping Cart</h2>
        <?php if(isset($_GET['added'])): ?>
            <div class="alert alert-success">Item added to cart successfully!</div>
        <?php endif; ?>

        <?php if(empty($_SESSION['cart'])): ?>
            <div style="text-align:center; padding:4rem;">
                <p style="font-size:4rem;">🛒</p>
                <h3 style="color:#888; margin:1rem 0;">Your cart is empty</h3>
                <a href="products.php" class="btn btn-primary" style="margin-top:1rem;">Start Shopping</a>
            </div>
        <?php else: ?>
            <form method="POST">
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Subtotal</th>
                            <th>Remove</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($_SESSION['cart'] as $pid => $item): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($item['name']) ?></strong></td>
                            <td>$<?= number_format($item['price'],2) ?></td>
                            <td>
                                <input type="number" name="qty[<?= $pid ?>]" value="<?= $item['qty'] ?>"
                                    min="1" max="99" style="width:70px; padding:4px 8px; border:1.5px solid #ddd; border-radius:6px; font-size:1rem;">
                            </td>
                            <td><strong>$<?= number_format($item['price'] * $item['qty'],2) ?></strong></td>
                            <td><a href="cart.php?remove=<?= $pid ?>" style="color:#e94560; text-decoration:none; font-size:1.2rem;">&#10005;</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div style="display:flex; justify-content:space-between; align-items:center; margin-top:1.5rem; flex-wrap:wrap; gap:1rem;">
                    <button type="submit" name="update" class="btn btn-outline">Update Cart</button>
                    <div>
                        <p class="cart-total">Total: $<?= number_format($total,2) ?></p>
                        <a href="checkout.php" class="btn btn-primary" style="padding:0.8rem 2.5rem; font-size:1.1rem;">Proceed to Checkout</a>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </div>
</section>

<footer>
    <p>&copy; <?= date('Y') ?> <span>StyleHub</span> — All rights reserved.</p>
</footer>
</body>
</html>