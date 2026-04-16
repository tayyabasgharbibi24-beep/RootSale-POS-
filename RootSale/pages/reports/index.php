<?php
// pages/reports/index.php
require_once __DIR__ . '/../../includes/header.php';
requireAdmin(); // Only admin can view reports
?>

<div class="max-w-6xl mx-auto pb-8">
    <h1 class="text-3xl font-bold text-gray-800 mb-8 mt-4"><i class="fa-solid fa-chart-pie text-primary mr-3"></i>Centro de Reportes</h1>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <a href="sales.php" class="bg-white p-6 rounded-lg shadow-md border-l-4 border-primary hover:shadow-lg transition group">
            <div class="flex items-center gap-4 mb-2">
                <div class="bg-blue-100 p-3 rounded-full text-primary group-hover:bg-primary group-hover:text-white transition">
                    <i class="fa-solid fa-chart-line text-xl w-6 text-center"></i>
                </div>
                <h2 class="text-lg font-bold text-gray-800">Ventas</h2>
            </div>
            <p class="text-sm text-gray-500 ml-16">Historial, filtros por fecha, total por métodos de pago.</p>
        </a>

        <a href="returns.php" class="bg-white p-6 rounded-lg shadow-md border-l-4 border-red-500 hover:shadow-lg transition group">
            <div class="flex items-center gap-4 mb-2">
                <div class="bg-red-100 p-3 rounded-full text-red-500 group-hover:bg-red-500 group-hover:text-white transition">
                    <i class="fa-solid fa-undo rotate-90 text-xl w-6 text-center"></i>
                </div>
                <h2 class="text-lg font-bold text-gray-800">Devoluciones</h2>
            </div>
            <p class="text-sm text-gray-500 ml-16">Historial de reembolsos y artículos devueltos.</p>
        </a>

        <a href="products.php" class="bg-white p-6 rounded-lg shadow-md border-l-4 border-green-500 hover:shadow-lg transition group">
            <div class="flex items-center gap-4 mb-2">
                <div class="bg-green-100 p-3 rounded-full text-green-600 group-hover:bg-green-500 group-hover:text-white transition">
                    <i class="fa-solid fa-box text-xl w-6 text-center"></i>
                </div>
                <h2 class="text-lg font-bold text-gray-800">Productos y Stock</h2>
            </div>
            <p class="text-sm text-gray-500 ml-16">Artículos más vendidos, alertas de stock bajo.</p>
        </a>

        <a href="expenses.php" class="bg-white p-6 rounded-lg shadow-md border-l-4 border-orange-500 hover:shadow-lg transition group">
            <div class="flex items-center gap-4 mb-2">
                <div class="bg-orange-100 p-3 rounded-full text-orange-500 group-hover:bg-orange-500 group-hover:text-white transition">
                    <i class="fa-solid fa-file-invoice-dollar text-xl w-6 text-center"></i>
                </div>
                <h2 class="text-lg font-bold text-gray-800">Gastos</h2>
            </div>
            <p class="text-sm text-gray-500 ml-16">Registro y seguimiento de gastos operativos del negocio.</p>
        </a>

        <a href="profit.php" class="bg-white p-6 rounded-lg shadow-md border-l-4 border-indigo-600 hover:shadow-lg transition group lg:col-span-2">
            <div class="flex items-center gap-4 mb-2">
                <div class="bg-indigo-100 p-3 rounded-full text-indigo-600 group-hover:bg-indigo-600 group-hover:text-white transition">
                    <i class="fa-solid fa-money-bill-trend-up text-xl w-6 text-center"></i>
                </div>
                <h2 class="text-lg font-bold text-gray-800">Reporte de Ganancias (Profit)</h2>
            </div>
            <p class="text-sm text-gray-500 ml-16">Análisis financiero: Ingresos - Costos = Ganancias netas.</p>
        </a>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
