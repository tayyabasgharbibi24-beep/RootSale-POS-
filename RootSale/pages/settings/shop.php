<?php
// pages/settings/shop.php
require_once __DIR__ . '/../../includes/header.php';
requireAdmin();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        $error = "Petición no válida.";
    } else {
        $shop_name = sanitizeInput($_POST['shop_name']);
        $shop_address = sanitizeInput($_POST['shop_address']);
        $shop_phone = sanitizeInput($_POST['shop_phone']);
        $shop_email = sanitizeInput($_POST['shop_email']);
        $gst_number = sanitizeInput($_POST['gst_number']);
        $tax_rate = (float)$_POST['tax_rate'];
        $currency_symbol = sanitizeInput($_POST['currency_symbol'] ?: '₹');
        $receipt_footer = sanitizeInput($_POST['receipt_footer']);
        
        // Handle File Upload
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $tmp_name = $_FILES['logo']['tmp_name'];
            $name = basename($_FILES['logo']['name']);
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $new_name = 'logo.' . $ext;
                $destination = __DIR__ . '/../../assets/img/logos/' . $new_name;
                if (move_uploaded_file($tmp_name, $destination)) {
                    $pdo->prepare("UPDATE shop_settings SET logo_path = ? WHERE id = 1")->execute(['/assets/img/logos/' . $new_name]);
                }
            }
        }
        
        // Invoice settings
        $receipt_type = sanitizeInput($_POST['receipt_type']);
        $a4_footer_text = sanitizeInput($_POST['a4_footer_text']);
        $a4_terms_conditions = sanitizeInput($_POST['a4_terms_conditions']);
        $tax_name = sanitizeInput($_POST['tax_name'] ?? 'IVA');
        $iva_rate = (float)($_POST['iva_rate'] ?? 21);
        $show_qr = isset($_POST['show_qr']) ? 1 : 0;
        
        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("UPDATE shop_settings SET shop_name=?, shop_address=?, shop_phone=?, shop_email=?, gst_number=?, tax_rate=?, currency_symbol=?, receipt_footer=? WHERE id=1");
            $stmt->execute([$shop_name, $shop_address, $shop_phone, $shop_email, $gst_number, $tax_rate, $currency_symbol, $receipt_footer]);
            
            try {
                // Auto-patch column if missing
                try { $pdo->exec("ALTER TABLE invoice_settings ADD COLUMN show_qr INTEGER DEFAULT 1"); } catch(Exception $e) {}
                try { $pdo->exec("ALTER TABLE invoice_settings ADD COLUMN iva_rate DECIMAL(5,2) DEFAULT 21"); } catch(Exception $e) {}
                try { $pdo->exec("ALTER TABLE invoice_settings ADD COLUMN tax_name TEXT DEFAULT 'IVA'"); } catch(Exception $e) {}

                $stmt2 = $pdo->prepare("UPDATE invoice_settings SET receipt_type=?, a4_footer_text=?, a4_terms_conditions=?, iva_rate=?, tax_name=?, show_qr=? WHERE id=1");
                $stmt2->execute([$receipt_type, $a4_footer_text, $a4_terms_conditions, $iva_rate, $tax_name, $show_qr]);
            } catch(PDOException $e) {}

            $pdo->commit();
            $success = "Configuraciones actualizadas correctamente.";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Error al actualizar la configuración: " . $e->getMessage();
        }
    }
}

$settings = $pdo->query("SELECT * FROM shop_settings LIMIT 1")->fetch();

$invoice_settings = [
    'receipt_type' => 'thermal',
    'a4_footer_text' => '',
    'a4_terms_conditions' => '',
    'tax_name' => 'IVA',
    'iva_rate' => 21,
    'show_qr' => 1
];
try {
    $inv = $pdo->query("SELECT * FROM invoice_settings LIMIT 1")->fetch();
    if ($inv) {
        $invoice_settings = $inv;
    }
} catch (Exception $e) {
    // missing table fallback
}
?>

<div class="max-w-4xl mx-auto pb-8">
    <div class="flex items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800"><i class="fa-solid fa-store mr-2 text-primary"></i>Configuración de la Tienda</h1>
    </div>

    <?php if ($error): ?>
        <div class="bg-red-100 text-red-700 p-4 mb-4 rounded border-l-4 border-red-500"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="bg-green-100 text-green-700 p-4 mb-4 rounded border-l-4 border-green-500"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form action="" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
        
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-lg font-bold border-b pb-2 mb-4 text-gray-700">Información General</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre de la Tienda *</label>
                    <input type="text" name="shop_name" required value="<?= htmlspecialchars($settings['shop_name']) ?>" class="w-full border border-gray-300 rounded px-4 py-2 focus:ring-primary focus:border-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">NIF/CIF / GST *</label>
                    <input type="text" name="gst_number" value="<?= htmlspecialchars($settings['gst_number']) ?>" class="w-full border border-gray-300 rounded px-4 py-2 focus:ring-primary focus:border-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                    <input type="text" name="shop_phone" value="<?= htmlspecialchars($settings['shop_phone']) ?>" class="w-full border border-gray-300 rounded px-4 py-2 focus:ring-primary focus:border-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="shop_email" value="<?= htmlspecialchars($settings['shop_email']) ?>" class="w-full border border-gray-300 rounded px-4 py-2 focus:ring-primary focus:border-primary">
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Dirección Física</label>
                <textarea name="shop_address" rows="2" class="w-full border border-gray-300 rounded px-4 py-2 focus:ring-primary focus:border-primary"><?= htmlspecialchars($settings['shop_address']) ?></textarea>
            </div>
            
            <div class="mb-4 mt-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Logo de la Tienda (Global)</label>
                <?php if (!empty($settings['logo_path'])): ?>
                    <div class="mb-2">
                        <img src="<?= BASE_URL . $settings['logo_path'] ?>" alt="Logo" class="h-16 object-contain">
                    </div>
                <?php endif; ?>
                <input type="file" name="logo" accept="image/*" class="w-full border border-gray-300 rounded px-4 py-2 text-sm focus:ring-primary focus:border-primary bg-gray-50">
                <p class="text-xs text-gray-500 mt-1">Formatos soportados: JPG, PNG, GIF</p>
            </div>
            
            <h2 class="text-lg font-bold border-b pb-2 mb-4 mt-8 text-gray-700">Formatos y Facturación</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Símbolo de Moneda</label>
                    <select name="currency_symbol" id="currencySelect" class="w-full border border-gray-300 rounded px-4 py-2 focus:ring-primary focus:border-primary">
                        <option value="€" <?= $settings['currency_symbol'] === '€' ? 'selected' : '' ?>>€ (EUR)</option>
                        <option value="$" <?= $settings['currency_symbol'] === '$' ? 'selected' : '' ?>>$ (USD / Latino)</option>
                        <option value="₹" <?= $settings['currency_symbol'] === '₹' ? 'selected' : '' ?>>₹ (INR)</option>
                        <option value="Rs" <?= $settings['currency_symbol'] === 'Rs' ? 'selected' : '' ?>>Rs (PKR)</option>
                        <option value="£" <?= $settings['currency_symbol'] === '£' ? 'selected' : '' ?>>£ (GBP)</option>
                        <option value="¥" <?= $settings['currency_symbol'] === '¥' ? 'selected' : '' ?>>¥ (CNY)</option>
                        <option value="د.إ" <?= $settings['currency_symbol'] === 'د.إ' ? 'selected' : '' ?>>د.إ (AED)</option>
                        <option value="S/" <?= $settings['currency_symbol'] === 'S/' ? 'selected' : '' ?>>S/ (PEN)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Impuesto General por Defecto (%)</label>
                    <input type="number" step="0.01" name="tax_rate" value="<?= htmlspecialchars($settings['tax_rate']) ?>" class="w-full border border-gray-300 rounded px-4 py-2 focus:ring-primary focus:border-primary">
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">Mensaje pie de página (Ticket Básico)</label>
                <textarea name="receipt_footer" rows="2" class="w-full border border-gray-300 rounded px-4 py-2 focus:ring-primary focus:border-primary"><?= htmlspecialchars($settings['receipt_footer']) ?></textarea>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6 border-t-4 border-primary mb-6">
            <h2 class="text-xl font-bold mb-4 flex items-center gap-2"><i class="fa-solid fa-file-invoice text-primary"></i> Configuración Avanzada de Facturas (A4)</h2>
            
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Recibo por Defecto</label>
                <div class="flex items-center gap-6">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="receipt_type" value="thermal" <?= $invoice_settings['receipt_type'] === 'thermal' ? 'checked' : '' ?> class="w-5 h-5 text-primary">
                        <span class="text-gray-800">Ticket Térmico (Pequeño)</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="receipt_type" value="a4" <?= $invoice_settings['receipt_type'] === 'a4' ? 'checked' : '' ?> class="w-5 h-5 text-primary">
                        <span class="text-gray-800">Factura A4 (Página Completa)</span>
                    </label>
                </div>
            </div>

            <!-- QR Code Settings -->
            <div class="mb-6 p-4 border border-dashed border-gray-300 rounded-lg bg-gray-50">
                <label class="flex items-center cursor-pointer mb-2">
                    <div class="relative">
                        <input type="checkbox" name="show_qr" value="1" <?= ($invoice_settings['show_qr'] ?? 1) ? 'checked' : '' ?> class="sr-only">
                        <div class="block bg-gray-600 w-14 h-8 rounded-full"></div>
                        <div class="dot absolute left-1 top-1 bg-white w-6 h-6 rounded-full transition"></div>
                    </div>
                    <div class="ml-3 text-gray-700 font-bold">
                        Habilitar Código QR en los Recibos/Facturas
                    </div>
                </label>
                <div class="ml-16">
                    <p class="text-sm text-gray-500 mb-1">
                        <i class="fa-solid fa-qrcode mr-1"></i> Muestra un código QR en los recibos impresos que enlaza a la versión web (recibo digital).
                    </p>
                    <p class="text-xs text-orange-600 bg-orange-100 p-2 rounded inline-block mt-1 border border-orange-200">
                        <i class="fa-solid fa-triangle-exclamation mr-1"></i> <b>Nota Importante:</b> El escaneo del QR <u>solo funcionará</u> si este sistema está alojado en un servidor público o hosting de internet. Si está en 'localhost', los teléfonos de los clientes no podrán acceder a la URL local.
                    </p>
                </div>
                <style>
                    input:checked ~ .dot { transform: translateX(100%); background-color: #22c55e; }
                    input:checked ~ .block { background-color: #dcfce3; border: 1px solid #16a34a; }
                </style>
            </div>

            <div class="mb-6 p-4 border border-blue-200 bg-blue-50 rounded-lg">
                <label class="block text-sm font-bold text-gray-700 mb-2"><i class="fa-solid fa-globe text-primary mr-1"></i> Asistente de País (Autocompletar)</label>
                <select id="countryAutoFill" class="w-full md:w-1/2 border border-gray-300 rounded px-4 py-2 focus:ring-primary focus:border-primary">
                    <option value="">-- Selecciona tu país para autocompletar --</option>
                    <option value='{"tax":"IVA", "curr":"€"}'>España</option>
                    <option value='{"tax":"IVA", "curr":"$"}'>México, Argentina, Colombia, Chile...</option>
                    <option value='{"tax":"IGV", "curr":"S/"}'>Perú</option>
                    <option value='{"tax":"ITBMS", "curr":"$"}'>Panamá</option>
                    <option value='{"tax":"GST", "curr":"₹"}'>India</option>
                    <option value='{"tax":"Sales Tax", "curr":"Rs"}'>Pakistan</option>
                    <option value='{"tax":"VAT", "curr":"د.إ"}'>UAE (Emiratos Árabes)</option>
                    <option value='{"tax":"VAT", "curr":"£"}'>Reino Unido (UK)</option>
                    <option value='{"tax":"TAX", "curr":"$"}'>Estados Unidos (USA)</option>
                </select>
                <p class="text-xs text-gray-500 mt-1">Al seleccionar un país, el "Nombre del Impuesto" y la "Moneda" se ajustarán automáticamente.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del Impuesto (Ej. IVA, GST)</label>
                    <input type="text" name="tax_name" id="taxNameInput" value="<?= htmlspecialchars($invoice_settings['tax_name'] ?? 'IVA') ?>" class="w-full border border-gray-300 rounded px-4 py-2 focus:ring-primary focus:border-primary font-bold">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Porcentaje de Impuesto (%)</label>
                    <input type="number" step="0.01" name="iva_rate" value="<?= htmlspecialchars($invoice_settings['iva_rate'] ?? 21) ?>" class="w-full border border-gray-300 rounded px-4 py-2 focus:ring-primary focus:border-primary">
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Mensaje de pie de página (Factura A4)</label>
                <input type="text" name="a4_footer_text" value="<?= htmlspecialchars($invoice_settings['a4_footer_text']) ?>" class="w-full border border-gray-300 rounded px-4 py-2 focus:ring-primary focus:border-primary">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Términos y Condiciones (Factura A4)</label>
                <textarea name="a4_terms_conditions" rows="3" class="w-full border border-gray-300 rounded px-4 py-2 focus:ring-primary focus:border-primary"><?= htmlspecialchars($invoice_settings['a4_terms_conditions']) ?></textarea>
            </div>
        </div>

        <div class="flex justify-end mb-10">
            <button type="submit" class="bg-primary hover:bg-blue-700 text-white font-bold py-3 px-8 rounded shadow-lg flex items-center gap-2 transition transform hover:-translate-y-1">
                <i class="fa-solid fa-save"></i> Guardar Todo
            </button>
        </div>
    </form>
</div>

<script>
document.getElementById('countryAutoFill').addEventListener('change', function() {
    if (!this.value) return;
    try {
        const data = JSON.parse(this.value);
        document.getElementById('taxNameInput').value = data.tax;
        
        const currSelect = document.getElementById('currencySelect');
        for (let i = 0; i < currSelect.options.length; i++) {
            if (currSelect.options[i].value === data.curr) {
                currSelect.selectedIndex = i;
                break;
            }
        }
    } catch(e) {}
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
