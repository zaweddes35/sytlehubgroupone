<?php
session_start();
require 'db.php';
if(isset($_SESSION['user_id'])) { header("Location: index.php"); exit; }

$errors = [];
$mode = $_POST['mode'] ?? 'login';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if($mode == 'register') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $pass = $_POST['password'] ?? '';
        $pass2 = $_POST['password2'] ?? '';
        if(empty($name)) $errors[] = "Name is required.";
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email required.";
        if(strlen($pass) < 6) $errors[] = "Password must be at least 6 characters.";
        if($pass !== $pass2) $errors[] = "Passwords do not match.";
        if(empty($errors)) {
            $check = $pdo->prepare("SELECT id FROM users WHERE email=?");
            $check->execute([$email]);
            if($check->fetch()) {
                $errors[] = "Email already registered. Please login.";
            } else {
                $hash = password_hash($pass, PASSWORD_DEFAULT);
                $pdo->prepare("INSERT INTO users (name,email,password) VALUES (?,?,?)")->execute([$name,$email,$hash]);
                $errors[] = ""; // force clear
                $success_msg = "Account created! You can now login.";
            }
        }
    } else {
        $email = trim($_POST['email'] ?? '');
        $pass = $_POST['password'] ?? '';
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email=?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if($user && password_verify($pass, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            header("Location: index.php");
            exit;
        } else {
            $errors[] = "Invalid email or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — StyleHub</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<nav>
    <a class="nav-logo" href="index.php">StyleHub</a>
    <ul class="nav-links">
        <li><a href="index.php">Home</a></li>
        <li><a href="products.php">Shop</a></li>
    </ul>
</nav>

<section style="min-height:70vh; display:flex; align-items:center; justify-content:center;">
    <div style="width:100%; max-width:440px;">
        <!-- TABS -->
        <div style="display:flex; background:white; border-radius:12px 12px 0 0; overflow:hidden; border:0.5px solid #eee; margin-bottom:-1px;">
            <a href="?tab=login" style="flex:1; text-align:center; padding:1rem; text-decoration:none; font-weight:600; font-size:0.95rem; <?= ($mode!='register')?'background:#e94560;color:white':'color:#888' ?>;">Login</a>
            <a href="?tab=register" style="flex:1; text-align:center; padding:1rem; text-decoration:none; font-weight:600; font-size:0.95rem; <?= ($mode=='register')?'background:#e94560;color:white':'color:#888' ?>;">Register</a>
        </div>

        <div class="form-box" style="border-radius:0 0 16px 16px; margin:0;">
            <?php if(isset($success_msg)): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success_msg) ?></div>
            <?php endif; ?>
            <?php foreach(array_filter($errors) as $e): ?>
                <div class="alert alert-error"><?= htmlspecialchars($e) ?></div>
            <?php endforeach; ?>

            <?php if(isset($_GET['tab']) && $_GET['tab']=='register'): ?>
            <form method="POST">
                <input type="hidden" name="mode" value="register">
                <div class="form-group"><label>Full Name</label><input type="text" name="name" required placeholder="Your name"></div>
                <div class="form-group"><label>Email</label><input type="email" name="email" required placeholder="you@email.com"></div>
                <div class="form-group"><label>Password</label><input type="password" name="password" required placeholder="Min 6 characters"></div>
                <div class="form-group"><label>Confirm Password</label><input type="password" name="password2" required placeholder="Repeat password"></div>
                <button type="submit" class="btn btn-primary" style="width:100%; padding:1rem; margin-top:0.5rem;">Create Account</button>
            </form>
            <?php else: ?>
            <form method="POST">
                <input type="hidden" name="mode" value="login">
                <div class="form-group"><label>Email</label><input type="email" name="email" required placeholder="you@email.com"></div>
                <div class="form-group"><label>Password</label><input type="password" name="password" required placeholder="Your password"></div>
                <button type="submit" class="btn btn-primary" style="width:100%; padding:1rem; margin-top:0.5rem;">Login</button>
            </form>
            <p style="text-align:center; margin-top:1rem; font-size:0.9rem; color:#888;">
                Don't have an account? <a href="?tab=register" style="color:#e94560;">Register here</a>
            </p>
            <?php endif; ?>
        </div>
    </div>
</section>

<footer>
    <p>&copy; <?= date('Y') ?> <span>StyleHub</span> — All rights reserved.</p>
</footer>
</body>
</html>