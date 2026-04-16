<?php
// pages/returns/print-return.php
require_once __DIR__ . '/../../includes/header.php';

if (!isset($_GET['id'])) {
    echo "ID no proporcionado.";
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}

$return_id = (int)$_GET['id'];

// Get Return Details
$stmt = $pdo->prepare("
    SELECT r.*, s.bill_number, c.name as customer_name, c.phone as customer_phone, u.full_name as user_name
    FROM returns r
    JOIN sales s ON r.sale_id = s.id
    LEFT JOIN customers c ON r.customer_id = c.id
    LEFT JOIN users u ON r.created_by = u.id
    WHERE r.id = ?
");
$stmt->execute([$return_id]);
$ret = $stmt->fetch();

if (!$ret) {
    echo "Devolución no encontrada.";
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}

// Get Items
$stmt = $pdo->prepare("SELECT ri.*, p.name as product_name FROM return_items ri JOIN products p ON ri.product_id = p.id WHERE ri.return_id = ?");
$stmt->execute([$return_id]);
$items = $stmt->fetchAll();

// Get Shop Settings
$settings = $pdo->query("SELECT * FROM shop_settings LIMIT 1")->fetch();

// Copies configuration
$copies = [
    ['type' => 'SHOP COPY', 'title' => 'COPIA DEL ESTABLECIMIENTO', 'show_reason' => true, 'signature' => true],
    ['type' => 'CUSTOMER COPY', 'title' => 'RECIBO DE CLIENTE', 'show_reason' => false, 'signature' => false]
];
?>

<div class="max-w-md mx-auto pb-10">
    <div class="flex justify-between items-center mb-6 no-print mt-6 px-4">
         <a href="index.php" class="bg-gray-200 text-gray-800 px-4 py-2 rounded shadow hover:bg-gray-300 transition"><i class="fa-solid fa-arrow-left"></i> Otra Devolución</a>
         <button onclick="window.print()" class="bg-primary text-white px-6 py-2 rounded shadow font-bold hover:bg-blue-700 flex items-center gap-2 transition"><i class="fa-solid fa-print"></i> Imprimir Recibos (x2)</button>
    </div>

    <!-- Printable Area Container -->
    <div id="printable-area">
        <?php foreach($copies as $index => $copy): ?>
        
        <div class="bg-white p-6 shadow-xl w-[80mm] mx-auto text-sm font-mono border border-gray-100 mb-6 thermal-layout text-gray-900 receipt-box relative">
            
            <div class="absolute top-2 right-2 border border-gray-400 text-gray-400 text-[9px] px-1 font-bold">
                <?= $copy['type'] ?>
            </div>

            <!-- Header -->
            <div class="text-center mb-4 border-b border-dashed border-gray-500 pb-4 flex flex-col items-center">
                <?php if(!empty($settings['logo_path'])): ?>
                    <img src="<?= BASE_URL . $settings['logo_path'] ?>" alt="Logo" class="max-h-16 object-contain mb-3 grayscale">
                <?php endif; ?>
                <h1 class="text-2xl font-black mb-1 leading-tight"><?= htmlspecialchars($settings['shop_name']) ?></h1>
                <p class="text-[11px] leading-tight font-bold"><?= $copy['title'] ?></p>
            </div>
            
            <!-- Meta -->
            <div class="mb-4 text-[11px] border-b border-dashed border-gray-500 pb-4 space-y-1">
                <div class="flex justify-between"><span>Devolución:</span> <strong class="text-sm"><?= htmlspecialchars($ret['return_number']) ?></strong></div>
                <div class="flex justify-between"><span>Ref. Venta:</span> <span><?= htmlspecialchars($ret['bill_number']) ?></span></div>
                <div class="flex justify-between"><span>Fecha:</span> <span><?= date('d/m/Y H:i', strtotime($ret['created_at'])) ?></span></div>
                <div class="flex justify-between"><span>Cajero:</span> <span><?= htmlspecialchars($ret['user_name']) ?></span></div>
                <div class="flex justify-between"><span>Cliente:</span> <span><?= htmlspecialchars($ret['customer_name'] ?? 'Final') ?></span></div>
            </div>
            
            <!-- Items -->
            <div class="mb-4 border-b border-dashed border-gray-500 pb-4">
                <table class="w-full text-[11px]">
                    <thead>
                        <tr class="border-b border-gray-900">
                            <th class="text-left font-bold pb-1 w-[55%]">Desc</th>
                            <th class="text-center font-bold pb-1 w-[15%]">Cant</th>
                            <th class="text-right font-bold pb-1 w-[30%]">Reemb.</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($items as $item): ?>
                        <tr class="align-top">
                            <td class="pt-2 pr-1 font-semibold"><?= htmlspecialchars($item['product_name']) ?></td>
                            <td class="pt-2 text-center text-red-600">-<?= $item['quantity'] ?></td>
                            <td class="pt-2 text-right font-bold text-red-600">-<?= formatCurrency(number_format($item['refund_price'], 2)) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Summary -->
            <div class="text-[11px] space-y-1 mb-6 border-b border-dashed border-gray-500 pb-4">
                <div class="flex justify-between font-black text-lg mt-2 pt-2 border-t border-gray-900 uppercase">
                    <span>Devolución:</span> <span class="text-red-600">-<?= formatCurrency(number_format($ret['refund_amount'], 2)) ?></span>
                </div>
                <div class="flex justify-between mt-2 text-gray-800 font-bold">
                    <span>Método Reembolso:</span> <span><?= htmlspecialchars($ret['refund_method']) ?></span>
                </div>
                <?php if($copy['show_reason']): ?>
                <div class="mt-2 text-gray-600 italic border border-gray-300 p-2 bg-gray-50">
                    <span class="font-bold">Motivo:</span> <?= htmlspecialchars($ret['reason']) ?>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if($copy['signature']): ?>
            <div class="text-[11px] mt-8 mb-4">
                <div class="flex justify-between px-2 gap-4">
                    <div class="w-1/2 text-center">
                        <div class="border-b border-gray-600 mb-1 h-6"></div>
                        <p class="text-[9px]">Firma Cajero</p>
                    </div>
                    <div class="w-1/2 text-center">
                        <div class="border-b border-gray-600 mb-1 h-6"></div>
                        <p class="text-[9px]">Firma Cliente</p>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="text-center text-[10px] mt-6">
                <p class="font-bold">El importe ha sido reembolsado o ajustado correctamente.</p>
                <p class="text-gray-500 mt-1"><?= htmlspecialchars($settings['receipt_footer']) ?></p>
            </div>
            <?php endif; ?>

        </div>
        
        <?php if($index === 0): ?>
            <!-- CSS Page Break for Print -->
            <div class="page-break no-print my-8 text-center border-t-2 border-dashed border-primary pt-2 text-primary font-bold">
                <i class="fa-solid fa-scissors mr-2"></i> Corte aquí para el Cliente <i class="fa-solid fa-scissors ml-2"></i>
            </div>
        <?php endif; ?>
        
        <?php endforeach; ?>
    </div>
</div>

<style>
    @media print {
        body * { visibility: hidden; }
        #printable-area, #printable-area * { visibility: visible; }
        .no-print { display: none !important; }
        .page-break { page-break-after: always; break-after: page; display: block !important; visibility: hidden; border: none; }
        
        @page { margin: 0; }
        #printable-area {
            position: absolute;
            left: 0; top: 0;
            width: 80mm;
            margin: 0; padding: 5mm; 
            box-shadow: none; border: none;
        }
        .receipt-box { margin-bottom: 0 !important; }
    }
</style>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
