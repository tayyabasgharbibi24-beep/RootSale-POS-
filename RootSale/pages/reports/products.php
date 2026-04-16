<?php
// pages/reports/products.php
require_once __DIR__ . '/../../includes/header.php';
requireAdmin();

// Most Sold Products
$stmt = $pdo->query("
    SELECT p.name, p.barcode, SUM(si.quantity) as total_sold, SUM(si.total_price) as revenue
    FROM sale_items si
    JOIN products p ON si.product_id = p.id
    GROUP BY p.id
    ORDER BY total_sold DESC
    LIMIT 20
");
$top_products = $stmt->fetchAll();

// Low Stock Products
$stmt = $pdo->query("SELECT * FROM products WHERE stock <= low_stock_threshold ORDER BY stock ASC");
$low_stock = $stmt->fetchAll();
?>

<div class="pb-8" id="report-print-area">
    
    <!-- Print Header with Logo -->
    <div class="hidden print:block mb-8 border-b-2 border-gray-800 pb-4 text-center">
        <?php if(!empty($globalSettings['logo_path'])): ?>
            <img src="<?= BASE_URL . $globalSettings['logo_path'] ?>" class="max-h-20 mx-auto mb-2 grayscale">
        <?php endif; ?>
        <h1 class="text-3xl font-black"><?= htmlspecialchars($globalSettings['shop_name']) ?></h1>
        <p class="text-xl font-bold mt-2">Reporte de Detalles y Stock</p>
        <p class="text-sm text-gray-600">Generado el: <?= date('d/m/Y H:i') ?></p>
    </div>

    <div class="flex flex-wrap items-center justify-between mb-6 no-print">
        <div class="flex items-center">
            <a href="index.php" class="text-gray-500 hover:text-primary mr-4"><i class="fa-solid fa-arrow-left text-xl"></i></a>
            <h1 class="text-2xl font-bold text-gray-800">Reporte de Productos y Stock</h1>
        </div>
        <button onclick="window.print()" class="bg-primary text-white px-6 py-2 rounded shadow font-bold hover:bg-blue-700 transition"><i class="fa-solid fa-print mr-2"></i>Imprimir Reporte (A4)</button>
    </div>

    <!-- Low Stock -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6 border-l-4 border-red-500">
        <div class="bg-red-50 px-6 py-4 border-b border-red-100 flex justify-between items-center">
            <h2 class="text-lg font-bold text-red-800"><i class="fa-solid fa-exclamation-triangle mr-2"></i> Alerta: Stock Bajo o Agotado</h2>
        </div>
        <div class="overflow-x-auto p-4">
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Código</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Producto</th>
                        <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Stock Actual</th>
                        <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Mínimo</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($low_stock as $p): ?>
                        <tr class="hover:bg-red-50">
                            <td class="px-4 py-2 text-sm font-mono"><?= htmlspecialchars($p['barcode']) ?></td>
                            <td class="px-4 py-2 text-sm font-medium"><?= htmlspecialchars($p['name']) ?></td>
                            <td class="px-4 py-2 text-center font-bold <?= $p['stock'] == 0 ? 'text-red-600' : 'text-orange-500' ?>"><?= $p['stock'] ?></td>
                            <td class="px-4 py-2 text-center text-sm text-gray-500"><?= $p['low_stock_threshold'] ?></td>
                            <td class="px-4 py-2 text-right">
                                <a href="../products/save-product.php?id=<?= $p['id'] ?>" class="text-primary hover:underline text-sm font-bold">Actualizar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (count($low_stock) === 0): ?><tr><td colspan="5" class="px-4 py-6 text-center text-green-600 font-bold"><i class="fa-solid fa-check-circle mr-2"></i>Todos los productos tienen stock suficiente.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Top Selling -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden border-l-4 border-green-500">
        <div class="bg-green-50 px-6 py-4 border-b border-green-100">
            <h2 class="text-lg font-bold text-green-800"><i class="fa-solid fa-trophy mr-2"></i> Top 20 - Productos Más Vendidos</h2>
        </div>
        <div class="overflow-x-auto p-4">
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Código</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Producto</th>
                        <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Unidades Vendidas</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Ingresos Generados</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php $i = 1; foreach ($top_products as $p): ?>
                        <tr class="hover:bg-green-50">
                            <td class="px-4 py-2 text-sm font-mono text-gray-500">
                                <?php if($i<=3): ?><i class="fa-solid fa-medal text-<?= $i==1?'yellow-500':($i==2?'gray-400':'amber-600') ?> mr-1"></i><?php endif; ?>
                                <?= htmlspecialchars($p['barcode']) ?>
                            </td>
                            <td class="px-4 py-2 text-sm font-medium text-gray-800"><?= htmlspecialchars($p['name']) ?></td>
                            <td class="px-4 py-2 text-center font-bold text-green-600"><?= $p['total_sold'] ?></td>
                            <td class="px-4 py-2 text-right font-medium text-gray-900"><?= formatCurrency(number_format($p['revenue'], 2)) ?></td>
                        </tr>
                    <?php $i++; endforeach; ?>
                    <?php if (count($top_products) === 0): ?><tr><td colspan="4" class="px-4 py-6 text-center text-gray-500">No hay ventas registradas aún.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    @media print {
        body * { visibility: hidden; }
        #report-print-area, #report-print-area * { visibility: visible; }
        #report-print-area { position: absolute; left: 0; top: 0; width: 100%; margin: 0; padding: 20px;}
        .no-print, a { display: none !important; }
        @page { size: A4 portrait; margin: 1cm; }
    }
</style>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
