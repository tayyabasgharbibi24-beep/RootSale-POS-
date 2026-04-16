<?php
// pages/categories/delete-category.php
require_once __DIR__ . '/../../includes/session.php'; // Will redirect if needed, but we do full load to use config securely
// Wait, we can just require config.php and auth.php
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

// Check if category has products
$stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
$stmt->execute([$id]);
if ($stmt->fetchColumn() > 0) {
    header("Location: index.php?error=No se puede eliminar la categoría porque contiene productos.");
    exit;
}

$stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
if ($stmt->execute([$id])) {
    header("Location: index.php?success=Categoría eliminada.");
} else {
    header("Location: index.php?error=Error al eliminar la categoría.");
}
exit;
