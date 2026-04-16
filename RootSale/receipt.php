<?php
// receipt.php
require_once __DIR__ . '/includes/config.php';

$bill_number = $_GET['bill'] ?? '';

if (empty($bill_number)) {
    die("Número de recibo no proporcionado.");
}

$stmt = $pdo->prepare("SELECT * FROM sales WHERE bill_number = ?");
$stmt->execute([$bill_number]);
$sale = $stmt->fetch();

if (!$sale) {
    die("Recibo no encontrado.");
}

$stmt = $pdo->prepare("
    SELECT si.*, p.name as product_name
    FROM sale_items si
    JOIN products p ON si.product_id = p.id
    WHERE si.sale_id = ?
");
$stmt->execute([$sale['id']]);
$items = $stmt->fetchAll();

$shop = $pdo->query("SELECT * FROM shop_settings LIMIT 1")->fetch();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recibo <?= htmlspecialchars($sale['bill_number']) ?> - <?= htmlspecialchars($shop['shop_name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { primary: '#1d4ed8' }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap');
        body { font-family: 'Outfit', sans-serif; background-color: #f3f4f6; }
        .receipt-card {
            background-image: radial-gradient(circle at 10px 0, transparent 10px, #ffffff 11px);
            background-size: 20px 20px;
            background-position: -10px 0px;
            background-repeat: repeat-x;
            padding-top: 30px;
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-4">

    <div class="max-w-md w-full bg-white shadow-2xl rounded-2xl overflow-hidden relative pb-10">
        
        <!-- Header -->
        <div class="bg-primary text-white p-8 text-center rounded-b-3xl shadow-md z-10 relative">
            <?php if(!empty($shop['logo_path'])): ?>
                <img src="<?= BASE_URL . $shop['logo_path'] ?>" class="h-16 mx-auto bg-white p-2 rounded mb-4 object-contain">
            <?php else: ?>
                <div class="w-16 h-16 bg-white text-primary rounded-full flex items-center justify-center mx-auto text-3xl mb-4">
                    <i class="fa-solid fa-store"></i>
                </div>
            <?php endif; ?>
            <h1 class="text-2xl font-bold tracking-tight"><?= htmlspecialchars($shop['shop_name']) ?></h1>
            <p class="text-blue-200 text-sm mt-1"><?= htmlspecialchars($shop['shop_address']) ?></p>
            <p class="text-blue-200 text-sm">Tel: <?= htmlspecialchars($shop['shop_phone']) ?></p>
        </div>

        <!-- Receipt Info -->
        <div class="px-8 pt-8">
            <h2 class="text-xl font-bold text-gray-800 text-center mb-1">Recibo Digital</h2>
            <p class="text-center text-gray-500 font-mono text-sm mb-6">#<?= htmlspecialchars($sale['bill_number']) ?></p>
            
            <div class="flex justify-between items-center text-sm border-b border-dashed border-gray-300 pb-4 mb-4">
                <div class="text-gray-500">
                    <p>Fecha:</p>
                    <p>Cliente:</p>
                    <p>Método Pago:</p>
                </div>
                <div class="text-right font-semibold text-gray-800">
                    <p><?= date('d/m/Y h:i A', strtotime($sale['created_at'])) ?></p>
                    <p><?= htmlspecialchars($sale['customer_name'] ?: 'Cliente Final') ?></p>
                    <p><?= htmlspecialchars($sale['payment_mode']) ?></p>
                </div>
            </div>

            <!-- Items -->
            <div class="mb-6">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-gray-500 border-b border-gray-200">
                            <th class="pb-2 text-left font-medium">Cant.  Producto</th>
                            <th class="pb-2 text-right font-medium">Importe</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($items as $i): ?>
                        <tr class="border-b border-gray-100 last:border-0 hover:bg-gray-50">
                            <td class="py-3">
                                <span class="font-bold text-gray-800"><?= $i['quantity'] ?>x</span> 
                                <span class="text-gray-600"><?= htmlspecialchars($i['product_name']) ?></span>
                            </td>
                            <td class="py-3 text-right font-semibold text-gray-800">
                                <?= number_format($i['quantity'] * $i['unit_price'], 2) ?> <?= CURRENCY_SYMBOL ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Totals -->
            <div class="bg-gray-50 p-4 rounded-xl mb-6">
                <div class="flex justify-between text-sm text-gray-600 mb-2">
                    <span>Subtotal</span>
                    <span><?= number_format($sale['subtotal'], 2) ?> <?= CURRENCY_SYMBOL ?></span>
                </div>
                <?php if($sale['discount'] > 0): ?>
                <div class="flex justify-between text-sm text-green-600 mb-2">
                    <span>Descuento</span>
                    <span>- <?= number_format($sale['discount'], 2) ?> <?= CURRENCY_SYMBOL ?></span>
                </div>
                <?php endif; ?>
                <div class="flex justify-between text-xl font-bold text-gray-800 border-t border-gray-300 pt-3 mt-1">
                    <span>Total Pagado</span>
                    <span class="text-primary font-black"><?= number_format($sale['grand_total'], 2) ?> <?= CURRENCY_SYMBOL ?></span>
                </div>
            </div>
            
            <div class="text-center text-gray-500 text-sm px-4">
                <p class="font-bold mb-1">¡Gracias por su compra!</p>
                <p class="text-xs"><?= htmlspecialchars($shop['receipt_footer']) ?></p>
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="mt-8 px-8 pb-4">
            <button onclick="window.print()" class="w-full bg-gray-800 text-white font-bold py-3 px-4 rounded-lg hover:bg-black transition shadow">
                <i class="fa-solid fa-print mr-2"></i> Imprimir / Guardar PDF
            </button>
        </div>
        
    </div>

</body>
</html>
