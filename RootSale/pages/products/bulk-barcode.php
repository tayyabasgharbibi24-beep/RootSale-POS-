<?php
// pages/products/bulk-barcode.php
require_once __DIR__ . '/../../includes/header.php';

$stmt = $pdo->query("SELECT id, name, barcode, selling_price FROM products WHERE IFNULL(is_active, 1) = 1 ORDER BY name ASC");
$products = $stmt->fetchAll();
?>

<div class="max-w-6xl mx-auto pb-8">
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center">
            <a href="index.php" class="text-gray-500 hover:text-primary mr-4"><i class="fa-solid fa-arrow-left text-xl"></i></a>
            <h1 class="text-2xl font-bold text-gray-800">Catálogo de Códigos de Barras</h1>
        </div>
        <button onclick="window.print()" class="bg-primary hover:bg-blue-700 text-white px-4 py-2 rounded shadow transition flex items-center gap-2">
            <i class="fa-solid fa-print"></i> Imprimir Todos
        </button>
    </div>

    <div class="bg-white p-8 rounded shadow" id="printable-area">
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            <?php foreach ($products as $p): ?>
                <div class="border border-gray-200 p-4 rounded text-center flex flex-col items-center justify-center break-inside-avoid">
                    <p class="font-bold text-sm text-gray-800 truncate w-full" title="<?= htmlspecialchars($p['name']) ?>"><?= htmlspecialchars($p['name']) ?></p>
                    <p class="text-xs text-gray-500 mb-2"><?= formatCurrency(number_format($p['selling_price'], 2)) ?></p>
                    <svg class="barcode" data-value="<?= $p['barcode'] ?>"></svg>
                </div>
            <?php endforeach; ?>
        </div>
        <?php if(count($products) == 0): ?>
            <p class="text-center text-gray-500">No hay productos en el sistema.</p>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.0/dist/JsBarcode.all.min.js"></script>
<script>
    document.querySelectorAll('.barcode').forEach(function(svg) {
        JsBarcode(svg, svg.getAttribute('data-value'), {
            format: "CODE128",
            lineColor: "#000",
            width: 1.5,
            height: 60,
            displayValue: true,
            fontSize: 14
        });
    });
</script>

<style>
    @media print {
        body * {
            visibility: hidden;
        }
        #printable-area, #printable-area * {
            visibility: visible;
        }
        #printable-area {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            margin: 0;
            padding: 0;
            box-shadow: none;
        }
        .break-inside-avoid {
            break-inside: avoid;
        }
    }
</style>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
