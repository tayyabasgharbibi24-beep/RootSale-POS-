<?php
// pages/reports/profit.php
require_once __DIR__ . '/../../includes/header.php';
requireAdmin();

$start_date = $_GET['start'] ?? date('Y-m-01');
$end_date = $_GET['end'] ?? date('Y-m-d');

// Income (Sales revenue)
$stmt = $pdo->prepare("SELECT SUM(grand_total) as total_revenue FROM sales WHERE date(created_at) >= ? AND date(created_at) <= ?");
$stmt->execute([$start_date, $end_date]);
$revenue = $stmt->fetch()['total_revenue'] ?? 0;

// Cost of Goods Sold (COGS)
// Join sale_items with products to get cost_price at current execution. Ideally COGS belongs to sale_item itself.
$stmt = $pdo->prepare("
    SELECT SUM(si.quantity * p.cost_price) as total_cogs 
    FROM sale_items si
    JOIN sales s ON si.sale_id = s.id
    JOIN products p ON si.product_id = p.id
    WHERE date(s.created_at) >= ? AND date(s.created_at) <= ?
");
$stmt->execute([$start_date, $end_date]);
$cogs = $stmt->fetch()['total_cogs'] ?? 0;

// Returns (Refunded amount)
$stmt = $pdo->prepare("SELECT SUM(refund_amount) as total_refunds FROM returns WHERE date(created_at) >= ? AND date(created_at) <= ?");
$stmt->execute([$start_date, $end_date]);
$refunds = $stmt->fetch()['total_refunds'] ?? 0;

// Expenses
$stmt = $pdo->prepare("SELECT SUM(amount) as total_expenses FROM expenses WHERE expense_date >= ? AND expense_date <= ?");
$stmt->execute([$start_date, $end_date]);
$expenses = $stmt->fetch()['total_expenses'] ?? 0;

// Gross Profit = Revenue - Refunds - COGS
$gross_profit = $revenue - $refunds - $cogs;
// Net Profit = Gross Profit - Expenses
$net_profit = $gross_profit - $expenses;

?>

<div class="pb-8">
    <div class="flex items-center mb-6">
        <a href="index.php" class="text-gray-500 hover:text-primary mr-4"><i class="fa-solid fa-arrow-left text-xl"></i></a>
        <h1 class="text-2xl font-bold text-gray-800">Cálculo de Ganancias</h1>
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
            <button type="submit" class="bg-primary text-white px-6 py-2 rounded shadow hover:bg-blue-700 transition">Calcular</button>
            <button type="button" onclick="window.print()" class="bg-gray-800 text-white px-6 py-2 rounded shadow hover:bg-gray-900 transition ml-auto"><i class="fa-solid fa-print"></i> Imprimir</button>
        </form>
    </div>

    <!-- P&L Statement -->
    <div class="bg-white rounded-lg shadow-xl overflow-hidden max-w-2xl mx-auto" id="printable-area">
        <div class="bg-gray-800 text-white p-6 text-center">
            <h2 class="text-xl font-bold">Estado de Resultados</h2>
            <p class="text-sm opacity-75">Periodo: <?= date('d/m/Y', strtotime($start_date)) ?> al <?= date('d/m/Y', strtotime($end_date)) ?></p>
        </div>
        
        <div class="p-6">
            <div class="flex justify-between py-2 border-b border-gray-100">
                <span class="text-gray-600">Total Ingresos por Ventas</span>
                <span class="font-bold text-gray-800"><?= formatCurrency(number_format($revenue, 2)) ?></span>
            </div>
            <div class="flex justify-between py-2 border-b border-gray-100">
                <span class="text-gray-600">Devoluciones (Reembolsos)</span>
                <span class="font-bold text-red-500">- <?= formatCurrency(number_format($refunds, 2)) ?></span>
            </div>
            <div class="flex justify-between py-2 border-b-2 border-gray-800 mb-2 bg-gray-50 font-bold px-2">
                <span class="text-gray-800">VEINTAS NETAS</span>
                <span class="text-gray-800"><?= formatCurrency(number_format($revenue - $refunds, 2)) ?></span>
            </div>
            
            <div class="flex justify-between py-2 border-b border-gray-100">
                <span class="text-gray-600">Costo de Bienes Vendidos (COGS)</span>
                <span class="font-bold text-red-500">- <?= formatCurrency(number_format($cogs, 2)) ?></span>
            </div>
            <div class="flex justify-between py-2 border-b border-gray-200 mb-2 font-bold px-2 text-primary">
                <span>BENEFICIO BRUTO (Gross Profit)</span>
                <span><?= formatCurrency(number_format($gross_profit, 2)) ?></span>
            </div>
            
            <div class="flex justify-between py-2 border-b border-gray-100 mt-4">
                <span class="text-gray-600">Gastos Operativos (Expenses)</span>
                <span class="font-bold text-red-500">- <?= formatCurrency(number_format($expenses, 2)) ?></span>
            </div>
            
            <div class="flex justify-between py-4 mt-4 border-t-4 border-gray-800 bg-<?= $net_profit >= 0 ? 'green' : 'red' ?>-50 px-4 rounded">
                <span class="text-xl font-black text-gray-800">GANANCIA NETA</span>
                <span class="text-2xl font-black text-<?= $net_profit >= 0 ? 'green-600' : 'red-600' ?>">
                    <?= formatCurrency(number_format($net_profit, 2)) ?>
                </span>
            </div>
        </div>
    </div>
</div>

<style>
    @media print {
        body * { visibility: hidden; }
        #printable-area, #printable-area * { visibility: visible; }
        #printable-area { position: absolute; left: 0; top: 0; width: 100%; box-shadow: none; border: 1px solid #ddd; }
        .no-print { display: none !important; }
    }
</style>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
