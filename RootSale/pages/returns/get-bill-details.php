<?php
// pages/returns/get-bill-details.php
session_start();
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

header('Content-Type: application/json');

if (!isset($_GET['bill'])) {
    echo json_encode(['success' => false, 'message' => 'Número de factura requerido']);
    exit;
}

$bill_number = sanitizeInput($_GET['bill']);

// Get sale
$stmt = $pdo->prepare("SELECT s.*, c.name as customer_name FROM sales s LEFT JOIN customers c ON s.customer_id = c.id WHERE s.bill_number = ? LIMIT 1");
$stmt->execute([$bill_number]);
$sale = $stmt->fetch();

if (!$sale) {
    echo json_encode(['success' => false, 'message' => 'Factura no encontrada en el sistema.']);
    exit;
}

// Get sale items
$stmt2 = $pdo->prepare("
    SELECT si.id as sale_item_id, si.product_id, si.quantity, si.unit_price, p.name as product_name,
    (SELECT COALESCE(SUM(quantity), 0) FROM return_items ri WHERE ri.sale_item_id = si.id) as returned_qty
    FROM sale_items si 
    JOIN products p ON si.product_id = p.id 
    WHERE si.sale_id = ?
");
$stmt2->execute([$sale['id']]);
$items = $stmt2->fetchAll();

echo json_encode([
    'success' => true,
    'data' => [
        'sale' => $sale,
        'items' => $items
    ]
]);
exit;
