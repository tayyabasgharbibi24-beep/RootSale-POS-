<?php
// pages/billing/save-bill.php
session_start();
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
requireSellerOrAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCSRFToken($_POST['csrf_token'])) {
    die("Petición inválida o token expirado.");
}

$cart_json = $_POST['cart_data'] ?? '';
$cart_data = json_decode($cart_json, true);

if (!$cart_data || empty($cart_data['items'])) {
    header("Location: index.php?error=El carrito está vacío.");
    exit;
}

$payment_method = sanitizeInput($_POST['payment_method']);
$customer_id = !empty($_POST['customer_id']) ? (int) $_POST['customer_id'] : null;

$customer_name = $_POST['customer_name'] ?? '';
$customer_phone = $_POST['customer_phone'] ?? '';
$customer_email = $_POST['customer_email'] ?? '';
$customer_gst = $_POST['customer_gst'] ?? '';
$customer_address = $_POST['customer_address'] ?? '';

// Crear cliente si se proporcionan detalles
if (empty($customer_id) && !empty(trim($customer_name))) {
    $full_address = trim($customer_address);
    if (!empty(trim($customer_gst))) {
        $full_address .= ($full_address ? " " : "") . "[GST: " . trim($customer_gst) . "]";
    }

    $stmt = $pdo->prepare("INSERT INTO customers (name, phone, email, address) VALUES (?, ?, ?, ?)");
    $stmt->execute([trim($customer_name), trim($customer_phone), trim($customer_email), $full_address]);
    $customer_id = $pdo->lastInsertId();
}

$paid_amount = (float) ($_POST['paid_amount'] ?? $cart_data['total']);
$change_amount = $paid_amount - $cart_data['total'];
$created_by = $_SESSION['user_id'];

// Recalcular en el servidor por integridad
$subtotal = 0;
foreach ($cart_data['items'] as $item) {
    // Deberíamos estrictamente obtener el último precio, pero las apps POS usualmente respetan el precio solicitado del carrito. Aquí recalculamos por precisión.
    $stmt = $pdo->prepare("SELECT selling_price, stock FROM products WHERE id = ?");
    $stmt->execute([$item['id']]);
    $prod = $stmt->fetch();
    if (!$prod || $prod['stock'] < $item['qty']) {
        header("Location: index.php?error=Stock insuficiente para uno de los productos.");
        exit;
    }
    $subtotal += $prod['selling_price'] * $item['qty'];
}

$discount_type = sanitizeInput($cart_data['discount_type']);
$discount_value = (float) $cart_data['discount_value'];
$discount_amount = 0;

if ($discount_type === 'percentage') {
    $discount_amount = $subtotal * ($discount_value / 100);
} else {
    $discount_amount = $discount_value;
}
if ($discount_amount > $subtotal)
    $discount_amount = $subtotal;

$grand_total = $subtotal - $discount_amount;

// Obtener configuración de factura
$inv = null;
try {
    $inv = $pdo->query("SELECT * FROM invoice_settings LIMIT 1")->fetch();
} catch (Exception $e) {
}

$receipt_type = $inv ? $inv['receipt_type'] : 'thermal';
$tax_type = $inv ? $inv['tax_type'] : 'iva';
$iva_rate = $inv ? (float) ($inv['iva_rate'] ?? 21.0) : 21.0;

// Cálculo de impuestos
$total_tax_rate = $iva_rate / 100;
// Nota: asumiendo que los precios incluyen impuestos
$tax_amount = $grand_total - ($grand_total / (1 + $total_tax_rate));

// Generar número de factura
$prefix = ($receipt_type === 'a4') ? 'INV-A4-' : 'INV-';
$stmt = $pdo->query("SELECT id FROM sales ORDER BY id DESC LIMIT 1");
$last_sale = $stmt->fetch();
$last_id = $last_sale ? $last_sale['id'] + 1 : 1;
$bill_number = $prefix . date('Ymd') . '-' . str_pad($last_id, 4, '0', STR_PAD_LEFT);

try {
    $pdo->beginTransaction();

    // 1. Insertar Venta
    $stmt = $pdo->prepare("INSERT INTO sales (bill_number, customer_id, subtotal, discount_type, discount_value, discount_amount, tax_amount, grand_total, paid_amount, change_amount, payment_method, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $bill_number,
        $customer_id,
        $subtotal,
        $discount_type,
        $discount_value,
        $discount_amount,
        $tax_amount,
        $grand_total,
        $paid_amount,
        max(0, $change_amount),
        $payment_method,
        $created_by
    ]);

    $sale_id = $pdo->lastInsertId();

    // 2. Insertar Artículos y Actualizar Stock
    foreach ($cart_data['items'] as $item) {
        $stmt = $pdo->prepare("SELECT selling_price FROM products WHERE id = ?");
        $stmt->execute([$item['id']]);
        $prod = $stmt->fetch();
        $unit_price = $prod['selling_price'];
        $total_price = $unit_price * $item['qty'];

        $stmt2 = $pdo->prepare("INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?)");
        $stmt2->execute([$sale_id, $item['id'], $item['qty'], $unit_price, $total_price]);

        $stmt3 = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
        $stmt3->execute([$item['qty'], $item['id']]);
    }

    // 3. Actualizar gasto total del cliente
    if ($customer_id) {
        $stmt4 = $pdo->prepare("UPDATE customers SET total_spent = total_spent + ? WHERE id = ?");
        $stmt4->execute([$grand_total, $customer_id]);
    }

    $pdo->commit();

    // Redirigir para imprimir recibo
    header("Location: print-bill.php?id=" . $sale_id . "&autoprint=1");
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    header("Location: index.php?error=Error procesando la venta: " . urlencode($e->getMessage()));
    exit;
}
