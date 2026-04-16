<?php
// pages/billing/print-bill.php
require_once __DIR__ . '/../../includes/header.php';

if (!isset($_GET['id'])) {
    echo "<div class='p-6 flex justify-center text-red-500 font-bold'>ID no proporcionado.</div>";
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}

$sale_id = (int)$_GET['id'];

// Get Sale Details
$stmt = $pdo->prepare("
    SELECT s.*, c.name as customer_name, c.phone as customer_phone, c.email as customer_email, c.address as customer_address, u.full_name as cashier
    FROM sales s
    LEFT JOIN customers c ON s.customer_id = c.id
    LEFT JOIN users u ON s.created_by = u.id
    WHERE s.id = ?
");
$stmt->execute([$sale_id]);
$sale = $stmt->fetch();

if (!$sale) {
    echo "<div class='p-6 flex justify-center text-red-500 font-bold'>Venta no encontrada.</div>";
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}

// Get Items
$stmt = $pdo->prepare("SELECT si.*, p.name as product_name FROM sale_items si JOIN products p ON si.product_id = p.id WHERE si.sale_id = ?");
$stmt->execute([$sale_id]);
$items = $stmt->fetchAll();

// Get Settings
$settings = $pdo->query("SELECT * FROM shop_settings LIMIT 1")->fetch();

$invoice_settings = ['receipt_type' => 'thermal', 'iva_rate' => 21, 'a4_terms_conditions' => '', 'show_qr' => 1];
try {
    $inv = $pdo->query("SELECT * FROM invoice_settings LIMIT 1")->fetch();
    if ($inv) $invoice_settings = $inv;
} catch (Exception $e) {}

$hardware_settings = ['connection_type' => 'web_serial', 'printer_ip' => ''];
try {
    $hw = $pdo->query("SELECT * FROM hardware_settings LIMIT 1")->fetch();
    if ($hw) $hardware_settings = $hw;
} catch (Exception $e) {}

$receipt_url = "http://" . $_SERVER['HTTP_HOST'] . BASE_URL . "/pages/billing/print-bill.php?id=" . $sale_id;

$isA4 = ($invoice_settings['receipt_type'] === 'a4');
if (isset($_GET['format'])) {
    $isA4 = ($_GET['format'] === 'a4');
}
?>

<div class="max-w-4xl mx-auto pb-10">
    <div class="flex flex-col sm:flex-row justify-between items-center mb-6 no-print mt-6 px-4 gap-4">
         <a href="index.php" class="bg-gray-800 text-white px-4 py-2 rounded shadow hover:bg-black transition w-full sm:w-auto text-center"><i class="fa-solid fa-arrow-left"></i> Nueva Venta</a>
         
         <div class="flex bg-gray-200 p-1 rounded-lg">
             <a href="?id=<?= $sale_id ?>&format=thermal" class="px-4 py-2 rounded-md font-bold text-sm transition-colors <?= !$isA4 ? 'bg-white text-primary shadow' : 'text-gray-600 hover:text-gray-800' ?>">
                 <i class="fa-solid fa-receipt mr-1"></i> Ticket Térmico
             </a>
             <a href="?id=<?= $sale_id ?>&format=a4" class="px-4 py-2 rounded-md font-bold text-sm transition-colors <?= $isA4 ? 'bg-white text-primary shadow' : 'text-gray-600 hover:text-gray-800' ?>">
                 <i class="fa-solid fa-file-invoice mr-1"></i> Factura A4 (Normal)
             </a>
         </div>

         <button onclick="window.print()" class="bg-primary text-white px-6 py-2 rounded shadow font-bold hover:bg-blue-700 flex items-center justify-center gap-2 transition w-full sm:w-auto"><i class="fa-solid fa-print"></i> Imprimir <?= $isA4 ? 'A4' : 'Ticket' ?></button>
    </div>

    <?php if ($isA4): ?>
    <!-- ============================================ -->
    <!-- A4 INVOICE LAYOUT                            -->
    <!-- ============================================ -->
    <div class="bg-white px-10 py-12 shadow-xl mx-auto border border-gray-200 a4-layout text-gray-800" id="printable-area">
        <div class="flex justify-between items-start mb-6 border-b-2 border-primary pb-4">
            <div class="w-1/2 flex items-center">
                <?php if(!empty($settings['logo_path'])): ?>
                    <img src="<?= BASE_URL . $settings['logo_path'] ?>" alt="Logo" class="max-h-16 object-contain mr-4">
                <?php else: ?>
                    <div class="h-12 w-12 bg-blue-100 flex items-center justify-center rounded text-primary text-xl mr-4"><i class="fa-solid fa-store"></i></div>
                <?php endif; ?>
                <div>
                    <h1 class="text-[16px] font-black text-gray-900 mb-1 tracking-tight uppercase"><?= htmlspecialchars($settings['shop_name']) ?></h1>
                    <p class="text-[12px] text-gray-600 font-medium"><?= htmlspecialchars($settings['shop_address']) ?></p>
                    <p class="text-[12px] text-gray-600 font-medium whitespace-nowrap"><i class="fa-solid fa-phone text-[10px] mr-1"></i><?= htmlspecialchars($settings['shop_phone']) ?> | <i class="fa-solid fa-envelope text-[10px] mx-1"></i><?= htmlspecialchars($settings['shop_email'] ?? '') ?></p>
                    <?php if($settings['gst_number']): ?>
                        <p class="text-[12px] font-bold text-gray-700 mt-1">NIF/GST: <?= htmlspecialchars($settings['gst_number']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="w-1/2 text-right">
                <h2 class="text-[16px] font-black text-gray-400 uppercase tracking-widest mb-2 shadow-sm inline-block px-3 py-1 border border-gray-200 rounded">FACTURA</h2>
                <div class="inline-block bg-gray-50 border border-gray-200 p-2 rounded text-left min-w-[200px]">
                    <p class="text-[12px]"><span class="font-bold text-gray-600 inline-block w-20">Factura Nº:</span> <span class="font-mono text-gray-900 font-bold"><?= htmlspecialchars($sale['bill_number']) ?></span></p>
                    <p class="text-[12px]"><span class="font-bold text-gray-600 inline-block w-20">Fecha:</span> <span class="font-medium"><?= date('d M Y, H:i', strtotime($sale['created_at'])) ?></span></p>
                    <p class="text-[12px]"><span class="font-bold text-gray-600 inline-block w-20">Cajero:</span> <span class="font-medium"><?= htmlspecialchars($sale['cashier']) ?></span></p>
                </div>
            </div>
        </div>

        <div class="mb-6">
            <h3 class="text-[12px] font-bold text-gray-500 uppercase tracking-wider mb-2">Facturado a</h3>
            <div class="bg-gray-50 border-l-4 border-primary p-3 rounded-r">
                <p class="text-[14px] font-bold text-gray-800"><?= htmlspecialchars($sale['customer_name'] ?? 'Consumidor Final') ?></p>
                <?php if(!empty($sale['customer_phone'])): ?>
                    <p class="text-[12px] text-gray-600 mt-1">Tel: <?= htmlspecialchars($sale['customer_phone']) ?></p>
                <?php endif; ?>
                <?php if(!empty($sale['customer_email'])): ?>
                    <p class="text-[12px] text-gray-600">Email: <?= htmlspecialchars($sale['customer_email']) ?></p>
                <?php endif; ?>
                <?php if(!empty($sale['customer_address'])): ?>
                    <p class="text-[12px] text-gray-600">Dirección: <?= htmlspecialchars($sale['customer_address']) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <table class="w-full mb-6 text-[12px]">
            <thead>
                <tr class="bg-gray-100 text-gray-700">
                    <th class="py-2 px-3 text-left font-bold border-b border-gray-300 w-[50%]">Descripción del Artículo</th>
                    <th class="py-2 px-3 text-center font-bold border-b border-gray-300">Cantidad</th>
                    <th class="py-2 px-3 text-right font-bold border-b border-gray-300">Precio Unit.</th>
                    <th class="py-2 px-3 text-right font-bold border-b border-gray-300">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($items as $item): ?>
                <tr class="border-b border-gray-100 group">
                    <td class="py-2 px-3 font-medium text-[12px]"><?= htmlspecialchars($item['product_name']) ?></td>
                    <td class="py-2 px-3 text-center text-gray-600 text-[12px]"><?= $item['quantity'] ?></td>
                    <td class="py-2 px-3 text-right text-gray-600 text-[12px]"><?= formatCurrency(number_format($item['unit_price'], 2)) ?></td>
                    <td class="py-2 px-3 text-right font-bold text-gray-800 text-[12px]"><?= formatCurrency(number_format($item['total_price'], 2)) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="flex justify-between items-start mb-6">
            <div class="w-1/2 pr-6">
                <h3 class="text-[12px] font-bold text-gray-400 uppercase tracking-wider mb-2">Información de Pago</h3>
                <p class="text-[12px] text-gray-600 mb-1"><span class="font-medium">Método:</span> <?= htmlspecialchars($sale['payment_method']) ?></p>
                <p class="text-[12px] text-gray-600 mb-4"><span class="font-medium">Abonado:</span> <?= formatCurrency(number_format($sale['paid_amount'], 2)) ?> | <span class="font-medium">Cambio:</span> <?= formatCurrency(number_format($sale['change_amount'], 2)) ?></p>
                
                <?php if(!empty($invoice_settings['a4_terms_conditions'])): ?>
                <h3 class="text-[12px] font-bold text-gray-400 uppercase tracking-wider mb-1 mt-4">Términos y Condiciones</h3>
                <p class="text-[12px] text-gray-500 whitespace-pre-line leading-snug"><?= htmlspecialchars($invoice_settings['a4_terms_conditions']) ?></p>
                <?php endif; ?>
            </div>
            <div class="w-1/2">
                <div class="bg-gray-50 p-4 rounded border border-gray-200">
                    <div class="flex justify-between mb-1 text-[12px] text-gray-600">
                        <span>Subtotal:</span>
                        <span class="font-bold text-gray-800"><?= formatCurrency(number_format($sale['subtotal'], 2)) ?></span>
                    </div>
                    <?php if($sale['discount_amount'] > 0): ?>
                    <div class="flex justify-between mb-1 text-[12px] text-red-500 font-medium">
                        <span>Descuento:</span>
                        <span>-<?= formatCurrency(number_format($sale['discount_amount'], 2)) ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Tax Breakup -->
                    <div class="border-t border-b border-gray-200 py-2 my-2">
                        <p class="text-[12px] font-bold text-gray-500 mb-1 uppercase">Desglose Impuestos</p>
                        <div class="flex justify-between mb-1 text-[12px] text-gray-600">
                            <span><?= htmlspecialchars($invoice_settings['tax_name'] ?? 'IVA') ?> (<?= $invoice_settings['iva_rate'] ?? 21 ?>%):</span>
                            <span><?= formatCurrency(number_format($sale['tax_amount'], 2)) ?></span>
                        </div>
                        <div class="flex justify-between mt-1 text-[12px] text-gray-600 font-bold">
                            <span>Total Impuestos:</span>
                            <span><?= formatCurrency(number_format($sale['tax_amount'], 2)) ?></span>
                        </div>
                    </div>

                    <div class="flex justify-between items-end mt-3">
                        <span class="text-[14px] font-bold text-gray-800">TOTAL:</span>
                        <span class="text-[16px] font-black text-primary"><?= formatCurrency(number_format($sale['grand_total'], 2)) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="border-t border-primary pt-4 flex justify-between items-end pb-2">
            <div class="flex gap-4">
                <?php if (!isset($invoice_settings['show_qr']) || (bool)$invoice_settings['show_qr']): ?>
                <div class="w-20 h-20">
                    <div id="qrcode"></div>
                </div>
                <?php endif; ?>
                <div class="flex flex-col justify-end">
                    <svg id="barcode-a4"></svg>
                </div>
            </div>
            <div class="text-right">
                <div class="w-40 border-b border-gray-800 mb-2 inline-block"></div>
                <p class="text-[12px] font-bold text-gray-800">Firma Autorizada</p>
                <p class="text-[12px] text-gray-500 mt-1 font-medium tracking-wide"><?= htmlspecialchars($settings['receipt_footer']) ?></p>
            </div>
        </div>
    </div>


    <?php else: ?>
    <!-- ============================================ -->
    <!-- THERMAL TICKET LAYOUT (80mm)                 -->
    <!-- ============================================ -->
    <div class="bg-white p-6 shadow-xl w-[80mm] mx-auto text-sm font-mono border border-gray-100 thermal-layout text-gray-900" id="printable-area">
        <div class="text-center mb-4 border-b border-dashed border-gray-500 pb-4 flex flex-col items-center">
            <?php if(!empty($settings['logo_path'])): ?>
                <img src="<?= BASE_URL . $settings['logo_path'] ?>" alt="Logo" class="max-h-16 object-contain mb-3 grayscale">
            <?php endif; ?>
            <h1 class="text-2xl font-black mb-1 leading-tight"><?= htmlspecialchars($settings['shop_name']) ?></h1>
            <p class="text-[11px] leading-tight"><?= htmlspecialchars($settings['shop_address']) ?></p>
            <p class="text-[11px] leading-tight">Tel: <?= htmlspecialchars($settings['shop_phone']) ?></p>
            <?php if($settings['gst_number']): ?>
                <p class="text-[11px] leading-tight font-bold mt-1">NIF/GST: <?= htmlspecialchars($settings['gst_number']) ?></p>
            <?php endif; ?>
        </div>
        
        <div class="mb-4 text-[11px] border-b border-dashed border-gray-500 pb-4 space-y-1">
            <div class="flex justify-between"><span>TICKET:</span> <strong class="text-sm"><?= htmlspecialchars($sale['bill_number']) ?></strong></div>
            <div class="flex justify-between"><span>FECHA:</span> <span><?= date('d/m/Y H:i', strtotime($sale['created_at'])) ?></span></div>
            <div class="flex justify-between"><span>CAJERO:</span> <span><?= htmlspecialchars($sale['cashier']) ?></span></div>
            <div class="flex justify-between"><span>CLIENTE:</span> <span><?= htmlspecialchars($sale['customer_name'] ?? 'Final') ?></span></div>
        </div>
        
        <div class="mb-4 border-b border-dashed border-gray-500 pb-4">
            <table class="w-full text-[11px]">
                <thead>
                    <tr class="border-b border-gray-900">
                        <th class="text-left font-bold pb-1 w-[55%]">Desc</th>
                        <th class="text-center font-bold pb-1 w-[15%]">Cant</th>
                        <th class="text-right font-bold pb-1 w-[30%]">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($items as $item): ?>
                    <tr class="align-top">
                        <td class="pt-2 pr-1 font-semibold"><?= htmlspecialchars($item['product_name']) ?><br><span class="text-[9px] font-normal"><?= formatCurrency(number_format($item['unit_price'], 2)) ?> c/u</span></td>
                        <td class="pt-2 text-center"><?= $item['quantity'] ?></td>
                        <td class="pt-2 text-right font-bold"><?= formatCurrency(number_format($item['total_price'], 2)) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="text-[11px] space-y-1 mb-4 border-b border-dashed border-gray-500 pb-4">
            <div class="flex justify-between"><span>Subtotal:</span> <span><?= formatCurrency(number_format($sale['subtotal'], 2)) ?></span></div>
            <?php if($sale['discount_amount'] > 0): ?>
            <div class="flex justify-between font-bold"><span>Desc:</span> <span>-<?= formatCurrency(number_format($sale['discount_amount'], 2)) ?></span></div>
            <?php endif; ?>
            <div class="flex justify-between"><span>Impuestos:</span> <span><?= formatCurrency(number_format($sale['tax_amount'], 2)) ?></span></div>
            
            <div class="flex justify-between font-black text-lg mt-2 pt-2 border-t border-gray-900 uppercase">
                <span>TOTAL:</span> <span><?= formatCurrency(number_format($sale['grand_total'], 2)) ?></span>
            </div>
        </div>
        
        <div class="text-[11px] space-y-1 mb-6 border-b border-dashed border-gray-500 pb-4">
            <div class="flex justify-between"><span>Abonado (<?= htmlspecialchars($sale['payment_method']) ?>):</span> <span><?= formatCurrency(number_format($sale['paid_amount'], 2)) ?></span></div>
            <div class="flex justify-between font-bold"><span>Cambio:</span> <span><?= formatCurrency(number_format($sale['change_amount'], 2)) ?></span></div>
        </div>
        
        <div class="text-center text-[10px]">
            <div class="flex justify-center mb-2">
                <svg id="barcode-thermal"></svg>
            </div>
            <p class="mb-4 font-bold uppercase"><?= htmlspecialchars($settings['receipt_footer']) ?></p>
            <?php if (!isset($invoice_settings['show_qr']) || (bool)$invoice_settings['show_qr']): ?>
            <div class="flex justify-center mb-2 mt-4">
                <div id="qrcode"></div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
<?php
$public_receipt_url = "http://" . $_SERVER['HTTP_HOST'] . BASE_URL . "/receipt.php?bill=" . urlencode($sale['bill_number']);
?>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        try {
            <?php if (!isset($invoice_settings['show_qr']) || (bool)$invoice_settings['show_qr']): ?>
            new QRCode(document.getElementById("qrcode"), {
                text: "<?= $public_receipt_url ?>",
                width: <?= $isA4 ? '96' : '100' ?>,
                height: <?= $isA4 ? '96' : '100' ?>,
                colorDark : "#000000",
                colorLight : "#ffffff",
                correctLevel : QRCode.CorrectLevel.L
            });
            <?php endif; ?>
        } catch(e) { console.error("QR Error:", e); }

        try {
            const barcodeSelector = "<?= $isA4 ? '#barcode-a4' : '#barcode-thermal' ?>";
            JsBarcode(barcodeSelector, "<?= htmlspecialchars($sale['bill_number']) ?>", {
                format: "CODE128",
                width: <?= $isA4 ? '2' : '1' ?>,
                height: <?= $isA4 ? '40' : '30' ?>,
                displayValue: true,
                fontSize: <?= $isA4 ? '14' : '10' ?>,
                margin: 0
            });
        } catch(e) { console.error("Barcode Error:", e); }
    });
</script>

<style>
    @media print {
        body * { visibility: hidden; }
        #printable-area, #printable-area * { visibility: visible; }
        .no-print { display: none !important; }
        
        <?php if($isA4): ?>
        @page { size: A4; margin: 0; }
        #printable-area {
            position: absolute;
            left: 0; top: 0;
            width: 210mm;
            min-height: 297mm;
            padding: 10mm 15mm !important;
            box-shadow: none; border: none;
            box-sizing: border-box;
            background-color: white;
            font-size: 12px;
        }
        <?php else: ?>
        @page { margin: 0; }
        #printable-area {
            position: absolute;
            left: 0; top: 0;
            width: 80mm;
            margin: 0; padding: 5mm; 
            box-shadow: none; border: none;
        }
        <?php endif; ?>
    }
</style>

<script>
    // Auto kick cash drawer if printer is connected and authorized
    document.addEventListener('DOMContentLoaded', async () => {
        const connType = "<?= $hardware_settings['connection_type'] ?? 'web_serial' ?>";
        const printerIp = "<?= $hardware_settings['printer_ip'] ?? '' ?>";

        if (connType === 'network' && printerIp) {
            try {
                const fd = new FormData();
                fd.append('ip', printerIp);
                await fetch('<?= BASE_URL ?>/api/kick-drawer.php', { method: 'POST', body: fd, keepalive: true });
                console.log('Network cash drawer signal sent.');
            } catch (e) {
                console.warn('Silent network drawer open failed', e);
            }
        } else if (connType === 'web_serial') {
            if (navigator.serial) {
                try {
                    const ports = await navigator.serial.getPorts();
                    if (ports.length > 0) {
                        const port = ports[0];
                        await port.open({ baudRate: 9600 });
                        const writer = port.writable.getWriter();
                        const data = new Uint8Array([0x1B, 0x70, 0x00, 0x19, 0xFA]);
                        await writer.write(data);
                        await writer.close();
                        await port.close();
                        console.log('Cash drawer signal sent via WebSerial.');
                    }
                } catch (e) {
                    console.warn('Silent drawer open failed', e);
                }
            }
        } else if (connType === 'web_usb') {
            if (navigator.usb) {
                try {
                    const devices = await navigator.usb.getDevices();
                    if (devices.length > 0) {
                        const device = devices[0];
                        await device.open();
                        if (device.configuration === null) await device.selectConfiguration(1);
                        try { await device.claimInterface(0); } catch(ex) { console.warn('Could not claim interface', ex); }
                        
                        let endpointNum = null;
                        for (const i of device.configuration.interfaces) {
                            for (const a of i.alternates) {
                                for (const e of a.endpoints) {
                                    if (e.direction === 'out') { endpointNum = e.endpointNumber; break; }
                                }
                            }
                        }
                        if (endpointNum) {
                            const data = new Uint8Array([0x1B, 0x70, 0x00, 0x19, 0xFA]);
                            await device.transferOut(endpointNum, data);
                            console.log('Cash drawer signal sent via WebUSB.');
                        }
                        await device.close();
                    }
                } catch (e) {
                    console.warn('Silent drawer open failed via WebUSB', e);
                }
            }
        }
        
        <?php if(isset($_GET['autoprint']) && $_GET['autoprint'] == '1'): ?>
        setTimeout(() => {
            window.print();
        }, 800);
        <?php endif; ?>
    });
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
