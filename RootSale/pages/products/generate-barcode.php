<?php
// pages/products/generate-barcode.php
require_once __DIR__ . '/../../includes/header.php';

if (!isset($_GET['id'])) {
    echo "ID no proporcionado.";
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$_GET['id']]);
$product = $stmt->fetch();

if (!$product) {
    echo "Producto no encontrado.";
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}
?>

<div class="max-w-2xl mx-auto pb-8">
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center">
            <a href="index.php" class="text-gray-500 hover:text-primary mr-4"><i class="fa-solid fa-arrow-left text-xl"></i></a>
            <h1 class="text-2xl font-bold text-gray-800">Imprimir Código</h1>
        </div>
        <button onclick="window.print()" class="bg-primary hover:bg-blue-700 text-white px-4 py-2 rounded shadow transition flex items-center gap-2">
            <i class="fa-solid fa-print"></i> Imprimir
        </button>
    </div>

    <div class="bg-white p-8 rounded shadow text-center" id="printable-area">
        <h2 class="text-xl font-bold text-gray-800 mb-2"><?= htmlspecialchars($product['name']) ?></h2>
        <p class="text-gray-500 mb-6"><?= htmlspecialchars($product['brand']) ?> - <?= formatCurrency(number_format($product['selling_price'], 2)) ?></p>
        
        <div class="flex justify-center flex-col items-center">
            <!-- SVG will be generated here -->
            <svg id="barcode"></svg>
        </div>
        <p class="mt-4 text-xs text-gray-400">Escanee este código en el punto de venta</p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.0/dist/JsBarcode.all.min.js"></script>
<script>
    JsBarcode("#barcode", "<?= $product['barcode'] ?>", {
        format: "CODE128",
        lineColor: "#000",
        width: 2,
        height: 100,
        displayValue: true
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
            padding: 20px;
            box-shadow: none;
        }
    }
</style>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
