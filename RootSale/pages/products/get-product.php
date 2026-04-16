<?php
// pages/products/get-product.php
session_start();
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_GET['search']) && !isset($_GET['barcode'])) {
    echo json_encode(['success' => false, 'message' => 'Query not provided.']);
    exit;
}

if (isset($_GET['search'])) {
    $search = "%" . $_GET['search'] . "%";
    $stmt = $pdo->prepare("SELECT * FROM products WHERE (name LIKE ? OR barcode LIKE ?) AND IFNULL(is_active, 1) = 1 ORDER BY name LIMIT 10");
    $stmt->execute([$search, $search]);
    $results = $stmt->fetchAll();
    echo json_encode(['success' => true, 'data' => $results]);
    exit;
}

if (isset($_GET['barcode'])) {
    $barcode = $_GET['barcode'];
    $stmt = $pdo->prepare("SELECT * FROM products WHERE barcode = ? AND IFNULL(is_active, 1) = 1 LIMIT 1");
    $stmt->execute([$barcode]);
    $result = $stmt->fetch();
    if ($result) {
        echo json_encode(['success' => true, 'data' => $result]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Producto no encontrado.']);
    }
    exit;
}
