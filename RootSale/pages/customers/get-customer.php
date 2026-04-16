<?php
// pages/customers/get-customer.php
session_start();
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID no proporcionado']);
    exit;
}

$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$id]);
$customer = $stmt->fetch();

if ($customer) {
    echo json_encode(['success' => true, 'data' => $customer]);
} else {
    echo json_encode(['success' => false, 'message' => 'Cliente no encontrado']);
}
exit;
