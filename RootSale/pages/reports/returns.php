<?php
// pages/reports/returns.php
require_once __DIR__ . '/../../includes/header.php';
requireAdmin();

$start_date = $_GET['start'] ?? date('Y-m-01');
$end_date = $_GET['end'] ?? date('Y-m-d');

$stmt = $pdo->prepare("SELECT SUM(refund_amount) as total FROM returns WHERE date(created_at) >= ? AND date(created_at) <= ?");
$stmt->execute([$start_date, $end_date]);
$summary = $stmt->fetch();

$stmt = $pdo->prepare("SELECT r.*, s.bill_number, c.name as customer_name FROM returns r JOIN sales s ON r.sale_id = s.id LEFT JOIN customers c ON r.customer_id = c.id WHERE date(r.created_at) >= ? AND date(r.created_at) <= ? ORDER BY r.created_at DESC");
$stmt->execute([$start_date, $end_date]);
$returns = $stmt->fetchAll();
?>

<div class="pb-8">
    <div class="flex items-center mb-6">
        <a href="index.php" class="text-gray-500 hover:text-primary mr-4"><i class="fa-solid fa-arrow-left text-xl"></i></a>
        <h1 class="text-2xl font-bold text-gray-800">Reporte de Devoluciones</h1>
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

    <div class="bg-red-50 p-6 rounded-lg shadow-md border-l-4 border-red-500 mb-6 flex justify-between items-center" id="printable-area-1">
        <div>
            <p class="text-red-800 text-sm font-bold uppercase tracking-wide">Total Reembolsado</p>
            <h3 class="text-3xl font-black text-red-600 mt-1">- <?= formatCurrency(number_format($summary['total'] ?? 0, 2)) ?></h3>
        </div>
        <i class="fa-solid fa-undo text-4xl text-red-200"></i>
    </div>

    <div class="bg-white rounded-lg shadow-md overflow-hidden" id="printable-area-2">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Devolución</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Factura Orig.</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cliente</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Reembolso</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase no-print">Acción</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($returns as $r): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-red-600"><?= htmlspecialchars($r['return_number']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">#<?= htmlspecialchars($r['bill_number']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($r['customer_name'] ?? 'Final') ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-bold text-gray-900">-<?= formatCurrency(number_format($r['refund_amount'], 2)) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm no-print">
                            <a href="../returns/print-return.php?id=<?= $r['id'] ?>" class="text-indigo-600 hover:text-indigo-900">Ver Recibo</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (count($returns) === 0): ?>
                    <tr><td colspan="6" class="px-6 py-8 text-center text-gray-500">No hay devoluciones en este período.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    @media print {
        body * { visibility: hidden; }
        #printable-area-1, #printable-area-2, #printable-area-1 *, #printable-area-2 * { visibility: visible; }
        #printable-area-1 { position: absolute; left: 0; top: 0; width: 100%; margin-top:20px;}
        #printable-area-2 { position: absolute; left: 0; top: 120px; width: 100%; }
        .no-print { display: none !important; }
    }
</style>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
