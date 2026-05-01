<?php
session_start();
require 'db.php';

if(!isset($_GET['id'])) { header("Location: products.php"); exit; }
$id = (int)$_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM products WHERE id=?");
$stmt->execute([$id]);
$product = $stmt->fetch();
if(!$product) { header("Location: products.php"); exit; }

if(!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

if(isset($_SESSION['cart'][$id])) {
    $_SESSION['cart'][$id]['qty']++;
} else {
    $_SESSION['cart'][$id] = [
        'id' => $product['id'],
        'name' => $product['name'],
        'price' => $product['price'],
        'qty' => 1
    ];
}

header("Location: cart.php?added=1");
exit;
?>