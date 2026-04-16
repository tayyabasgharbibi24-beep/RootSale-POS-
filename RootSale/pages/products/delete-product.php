<?php
// pages/products/delete-product.php
session_start();
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

if (!isset($_GET['id']) || !isset($_GET['token']) || !verifyCSRFToken($_GET['token'])) {
    header("Location: index.php?error=Petición inválida.");
    exit;
}

$id = $_GET['id'];

// Eliminar de forma segura (Soft Delete) si ha sido vendido, para no dañar facturas antiguas
try {
    $pdo->exec("ALTER TABLE products ADD COLUMN is_active INTEGER DEFAULT 1");
} catch(Exception $e) {}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM sale_items WHERE product_id = ?");
$stmt->execute([$id]);
$has_sales = ($stmt->fetchColumn() > 0);

if ($has_sales) {
    // Soft Delete
    $stmt = $pdo->prepare("UPDATE products SET is_active = 0, barcode = barcode || '-del-' || id WHERE id = ?");
    if ($stmt->execute([$id])) {
        header("Location: index.php?success=Producto eliminado (archivado por tener histórico de ventas).");
    } else {
        header("Location: index.php?error=Error al archivar el producto.");
    }
} else {
    // Hard Delete
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    if ($stmt->execute([$id])) {
        header("Location: index.php?success=Producto eliminado.");
    } else {
        header("Location: index.php?error=Error al eliminar el producto.");
    }
}
exit;
