<?php
// pages/products/update-stock.php
session_start();
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        die("Error de validación CSRF.");
    }
    
    $product_id = (int)$_POST['product_id'];
    $adjustment = (int)$_POST['adjustment'];
    $return_url = $_POST['return_url'] ?? 'index.php';
    
    if ($adjustment == 0) {
         header("Location: " . $return_url);
         exit;
    }

    // Direct update to stock limits race conditions slightly better than fetch+update, SQLite supports it
    $stmt = $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
    if ($stmt->execute([$adjustment, $product_id])) {
        // Ideally we chart this in an inventory log table but not in schema
        header("Location: " . $return_url . (strpos($return_url, '?') !== false ? '&' : '?') . "success=Stock actualizado.");
    } else {
        header("Location: " . $return_url . (strpos($return_url, '?') !== false ? '&' : '?') . "error=Error al actualizar.");
    }
    exit;
}
header("Location: index.php");
exit;
