<?php
// pages/returns/process-return.php
session_start();
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCSRFToken($_POST['csrf_token'])) {
    die("Petición no válida.");
}

$sale_id = (int)$_POST['sale_id'];
$return_items = $_POST['return_items'] ?? [];
$refund_method = sanitizeInput($_POST['refund_method']);
$reason = sanitizeInput($_POST['reason']);
$total_refund = (float)$_POST['total_refund'];

if (empty($return_items) || $total_refund <= 0) {
    header("Location: index.php?error=No se seleccionaron artículos para devolver.");
    exit;
}

// Generate return number
$stmt = $pdo->query("SELECT id FROM returns ORDER BY id DESC LIMIT 1");
$last_ret = $stmt->fetch();
$last_id = $last_ret ? $last_ret['id'] + 1 : 1;
$return_number = 'RET-' . date('Ymd') . '-' . str_pad($last_id, 4, '0', STR_PAD_LEFT);

// Get original sale
$stmt = $pdo->prepare("SELECT customer_id FROM sales WHERE id = ?");
$stmt->execute([$sale_id]);
$sale = $stmt->fetch();

if (!$sale) {
    header("Location: index.php?error=Venta no encontrada.");
    exit;
}

$customer_id = $sale['customer_id'];
$created_by = $_SESSION['user_id'];

try {
    $pdo->beginTransaction();

    // 1. Create Return
    $stmt = $pdo->prepare("INSERT INTO returns (return_number, sale_id, customer_id, total_return_amount, refund_amount, refund_method, reason, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$return_number, $sale_id, $customer_id, $total_refund, $total_refund, $refund_method, $reason, $created_by]);
    $return_id = $pdo->lastInsertId();

    foreach ($return_items as $sale_item_id => $qty) {
        $qty = (int)$qty;
        if ($qty > 0) {
            // Get original unit price and product ID
            $stmt2 = $pdo->prepare("SELECT product_id, unit_price FROM sale_items WHERE id = ? AND sale_id = ?");
            $stmt2->execute([$sale_item_id, $sale_id]);
            $si = $stmt2->fetch();
            
            if ($si) {
                // Insert return item
                $stmt3 = $pdo->prepare("INSERT INTO return_items (return_id, sale_item_id, product_id, quantity, refund_price, reason) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt3->execute([$return_id, $sale_item_id, $si['product_id'], $qty, $qty * $si['unit_price'], $reason]);
                
                // Put stock back
                $stmt4 = $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
                $stmt4->execute([$qty, $si['product_id']]);
            }
        }
    }
    
    // Adjust total spent for customer
    if ($customer_id) {
        $stmt5 = $pdo->prepare("UPDATE customers SET total_spent = total_spent - ? WHERE id = ?");
        $stmt5->execute([$total_refund, $customer_id]);
    }

    $pdo->commit();
    header("Location: print-return.php?id=" . $return_id);
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    header("Location: index.php?error=Error al procesar devolución: " . urlencode($e->getMessage()));
    exit;
}
