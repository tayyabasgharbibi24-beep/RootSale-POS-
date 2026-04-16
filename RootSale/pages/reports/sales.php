<?php
// pages/reports/sales.php
require_once __DIR__ . '/../../includes/header.php';
requireAdmin();

$start_date = $_GET['start'] ?? date('Y-m-01');
$end_date = $_GET['end'] ?? date('Y-m-d');

// Get total sales
$stmt = $pdo->prepare("SELECT SUM(grand_total) as total, COUNT(*) as count FROM sales WHERE date(created_at) >= ? AND date(created_at) <= ?");
$stmt->execute([$start_date, $end_date]);
$summary = $stmt->fetch();

// Payment Methods
$stmt = $pdo->prepare("SELECT payment_method, SUM(grand_total) as total FROM sales WHERE date(created_at) >= ? AND date(created_at) <= ? GROUP BY payment_method");
$stmt->execute([$start_date, $end_date]);
$methods = $stmt->fetchAll();

// List
$stmt = $pdo->prepare("SELECT s.*, c.name as customer_name FROM sales s LEFT JOIN customers c ON s.customer_id = c.id WHERE date(s.created_at) >= ? AND date(s.created_at) <= ? ORDER BY s.created_at DESC");
$stmt->execute([$start_date, $end_date]);
$sales = $stmt->fetchAll();
?>

<div class="pb-8" id="report-print-area">
    
    <!-- Print Header with Logo -->
    <div class="hidden print:block mb-8 border-b-2 border-gray-800 pb-4 text-center">
        <?php if(!empty($globalSettings['logo_path'])): ?>
            <img src="<?= BASE_URL . $globalSettings['logo_path'] ?>" class="max-h-20 mx-auto mb-2 grayscale">
        <?php endif; ?>
        <h1 class="text-3xl font-black"><?= htmlspecialchars($globalSettings['shop_name']) ?></h1>
        <p class="text-xl font-bold mt-2">Reporte de Ventas</p>
        <p class="text-sm text-gray-600">Período: <?= date('d/m/Y', strtotime($start_date)) ?> al <?= date('d/m/Y', strtotime($end_date)) ?></p>
    </div>
    <div class="flex items-center mb-6">
        <a href="index.php" class="text-gray-500 hover:text-primary mr-4"><i class="fa-solid fa-arrow-left text-xl"></i></a>
        <h1 class="text-2xl font-bold text-gray-800">Reporte de Ventas</h1>
    </div>

    <div class="bg-white p-4 rounded-lg shadow-md mb-6">
        <form action="" method="GET" class="flex flex-wrap items-end gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Desde</label>
                <input type="date" name="start" value="<?= $start_date ?>" class="border border-gray-300 rounded-md px-4 py-2 focus:ring-primary focus:border-primary">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Hasta</label>
                <input type="date" name="end" value="<?= $end_date ?>" class="border border-gray-300 rounded-md px-4 py-2 focus:ring-primary focus:border-primary">
            </div>
            <button type="submit" class="bg-primary text-white px-6 py-2 rounded shadow hover:bg-blue-700 transition">Generar</button>
            <button type="button" onclick="window.print()" class="bg-gray-800 text-white px-6 py-2 rounded shadow hover:bg-gray-900 transition ml-auto"><i class="fa-solid fa-print"></i> Imprimir</button>
        </form>
    </div>

    <!-- Summary Widgets -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6" id="printable-area-1">
        <div class="bg-white p-4 rounded-lg shadow border-l-4 border-green-500">
            <p class="text-sm text-gray-500 uppercase tracking-wide">Total Ventas (<?= $summary['count'] ?>)</p>
            <h3 class="text-2xl font-bold text-gray-800 mt-1"><?= formatCurrency(number_format($summary['total'] ?? 0, 2)) ?></h3>
        </div>
        <?php foreach($methods as $m): ?>
        <div class="bg-white p-4 rounded-lg shadow border-l-4 border-blue-500">
            <p class="text-sm text-gray-500 uppercase tracking-wide"><?= htmlspecialchars($m['payment_method']) ?></p>
            <h3 class="text-xl font-bold text-gray-800 mt-1"><?= formatCurrency(number_format($m['total'], 2)) ?></h3>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- List -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden" id="printable-area-2">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Factura</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cliente</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Método</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase no-print">Acción</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($sales as $s): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= date('d/m/Y H:i', strtotime($s['created_at'])) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-primary"><?= htmlspecialchars($s['bill_number']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($s['customer_name'] ?? 'Final') ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($s['payment_method']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-bold text-gray-900"><?= formatCurrency(number_format($s['grand_total'], 2)) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm no-print">
                            <a href="../billing/print-bill.php?id=<?= $s['id'] ?>" class="text-indigo-600 hover:text-indigo-900">Ticket</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (count($sales) === 0): ?>
                    <tr><td colspan="6" class="px-6 py-8 text-center text-gray-500">No hay ventas en este período.</td></tr>
                    <?php endif; ?>
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
        .no-print, form { display: none !important; }
        @page { size: A4 portrait; margin: 1cm; }
    }
</style>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
