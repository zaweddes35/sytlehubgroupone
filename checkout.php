<?php
session_start();
require 'db.php';
if(empty($_SESSION['cart'])) { header("Location: cart.php"); exit; }

$total = 0;
foreach($_SESSION['cart'] as $item) $total += $item['price'] * $item['qty'];

$success = false;
$errors = [];

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if(empty($name)) $errors[] = "Full name is required.";
    if(empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required.";
    if(empty($phone)) $errors[] = "Phone number is required.";
    if(empty($address)) $errors[] = "Delivery address is required.";

    if(empty($errors)) {
        $user_id = $_SESSION['user_id'] ?? null;
        $stmt = $pdo->prepare("INSERT INTO orders (user_id,full_name,email,phone,address,total) VALUES (?,?,?,?,?,?)");
        $stmt->execute([$user_id,$name,$email,$phone,$address,$total]);
        $order_id = $pdo->lastInsertId();
        foreach($_SESSION['cart'] as $item) {
            $s2 = $pdo->prepare("INSERT INTO order_items (order_id,product_id,quantity,price) VALUES (?,?,?,?)");
            $s2->execute([$order_id,$item['id'],$item['qty'],$item['price']]);
            $pdo->prepare("UPDATE products SET stock=stock-? WHERE id=?")->execute([$item['qty'],$item['id']]);
        }
        $_SESSION['cart'] = [];
        $success = true;
    }
}
$cart_count = isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'],'qty')) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout — StyleHub</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<nav>
    <a class="nav-logo" href="index.php">StyleHub</a>
    <ul class="nav-links">
        <li><a href="index.php">Home</a></li>
        <li><a href="products.php">Shop</a></li>
        <li><a href="cart.php">Cart</a></li>
    </ul>
</nav>

<section>
    <div class="container" style="max-width:650px;">
        <?php if($success): ?>
            <div style="text-align:center; padding:4rem; background:white; border-radius:16px; box-shadow:0 2px 20px rgba(0,0,0,0.1);">
                <div style="font-size:4rem; margin-bottom:1rem;">&#127881;</div>
                <h2 style="color:#28a745; font-size:2rem; margin-bottom:1rem;">Order Placed!</h2>
                <p style="color:#666; margin-bottom:2rem;">Thank you for shopping with StyleHub. We will contact you shortly to confirm your order.</p>
                <a href="products.php" class="btn btn-primary">Continue Shopping</a>
            </div>
        <?php else: ?>
            <h2 class="section-title">Checkout</h2>
            <p class="section-sub">Complete your order below</p>

            <?php foreach($errors as $e): ?>
                <div class="alert alert-error"><?= htmlspecialchars($e) ?></div>
            <?php endforeach; ?>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:2rem;">
                <div class="form-box" style="margin:0;">
                    <h3 style="margin-bottom:1.5rem; color:#1a1a2e;">Delivery Information</h3>
                    <form method="POST">
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="full_name" value="<?= htmlspecialchars($_SESSION['user_name'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" value="<?= htmlspecialchars($_SESSION['user_email'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="text" name="phone" placeholder="+256..." required>
                        </div>
                        <div class="form-group">
                            <label>Delivery Address</label>
                            <textarea name="address" placeholder="Street, City, District..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary" style="width:100%; padding:1rem;">Place Order — $<?= number_format($total,2) ?></button>
                    </form>
                </div>

                <div>
                    <div style="background:white; border-radius:16px; padding:1.5rem; box-shadow:0 2px 10px rgba(0,0,0,0.08);">
                        <h3 style="margin-bottom:1rem; color:#1a1a2e;">Order Summary</h3>
                        <?php foreach($_SESSION['cart'] as $item): ?>
                        <div style="display:flex; justify-content:space-between; padding:0.5rem 0; border-bottom:1px solid #f0f0f0; font-size:0.9rem;">
                            <span><?= htmlspecialchars($item['name']) ?> x<?= $item['qty'] ?></span>
                            <span>$<?= number_format($item['price'] * $item['qty'],2) ?></span>
                        </div>
                        <?php endforeach; ?>
                        <div style="display:flex; justify-content:space-between; padding-top:1rem; font-size:1.1rem; font-weight:700; color:#e94560;">
                            <span>Total</span>
                            <span>$<?= number_format($total,2) ?></span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<footer>
    <p>&copy; <?= date('Y') ?> <span>StyleHub</span> — All rights reserved.</p>
</footer>
</body>
</html>