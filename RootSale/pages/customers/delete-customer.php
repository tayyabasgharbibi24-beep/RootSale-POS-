<?php
// pages/customers/delete-customer.php
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

$stmt = $pdo->prepare("SELECT COUNT(*) FROM sales WHERE customer_id = ?");
$stmt->execute([$id]);
if ($stmt->fetchColumn() > 0) {
    header("Location: index.php?error=No se puede eliminar el cliente porque tiene ventas asociadas.");
    exit;
}

$stmt = $pdo->prepare("DELETE FROM customers WHERE id = ?");
if ($stmt->execute([$id])) {
    header("Location: index.php?success=Cliente eliminado.");
} else {
    header("Location: index.php?error=Error al eliminar el cliente.");
}
exit;
