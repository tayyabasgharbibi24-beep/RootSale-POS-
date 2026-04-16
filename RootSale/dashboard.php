<?php
require_once __DIR__ . '/includes/header.php';

if (isCustomerDisplay()) {
    header("Location: pages/customer-display/index.php");
    exit;
}

// Prepare data for dashboard
$today = date('Y-m-d');

// Today's Sales
$stmt = $pdo->prepare("SELECT SUM(grand_total) as total_sales FROM sales WHERE date(created_at) = ?");
$stmt->execute([$today]);
$todays_sales = $stmt->fetch()['total_sales'] ?? 0;

// Items Sold Today
$stmt = $pdo->prepare("SELECT SUM(quantity) as items_sold FROM sale_items JOIN sales ON sale_items.sale_id = sales.id WHERE date(sales.created_at) = ?");
$stmt->execute([$today]);
$items_sold = $stmt->fetch()['items_sold'] ?? 0;

// Total Products
$stmt = $pdo->query("SELECT COUNT(*) as total_products FROM products WHERE IFNULL(is_active, 1) = 1");
$total_products = $stmt->fetch()['total_products'] ?? 0;

// Recent Transactions
$stmt = $pdo->query("
    SELECT s.bill_number, s.grand_total, s.payment_method, s.created_at, c.name as customer_name
    FROM sales s
    LEFT JOIN customers c ON s.customer_id = c.id
    ORDER BY s.created_at DESC
    LIMIT 5
");
$recent_transactions = $stmt->fetchAll();
?>

<div class="mb-6">
    <h1 class="text-3xl font-bold text-gray-800">Panel de Control</h1>
    <p class="text-gray-600">Bienvenido de nuevo, <?= htmlspecialchars($_SESSION['user_name']) ?>!</p>
</div>

<?php if (isAdmin()): ?>
    <!-- Quick Actions -->
    <div class="flex gap-4 mb-8 overflow-x-auto pb-2 no-scrollbar">
        <a href="<?php echo BASE_URL; ?>/pages/billing/index.php" class="bg-primary hover:bg-blue-700 text-white px-6 py-3 rounded-lg shadow whitespace-nowrap transition-colors flex items-center gap-2">
            <i class="fa-solid fa-plus"></i> Nueva Venta
        </a>
        <a href="<?php echo BASE_URL; ?>/pages/products/save-product.php" class="bg-white hover:bg-gray-50 text-primary border border-primary px-6 py-3 rounded-lg shadow whitespace-nowrap transition-colors flex items-center gap-2">
            <i class="fa-solid fa-box"></i> Añadir Producto
        </a>
        <a href="<?php echo BASE_URL; ?>/pages/returns/index.php" class="bg-white hover:bg-gray-50 text-gray-700 border border-gray-300 px-6 py-3 rounded-lg shadow whitespace-nowrap transition-colors flex items-center gap-2">
            <i class="fa-solid fa-undo"></i> Procesar Devolución
        </a>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white p-6 rounded-lg shadow-md border-l-4 border-primary hover:shadow-lg transition">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-sm font-semibold uppercase tracking-wide">Ventas de Hoy</p>
                    <h3 class="text-3xl font-bold text-gray-800 mt-1"><?= formatCurrency(number_format($todays_sales, 2)) ?></h3>
                </div>
                <div class="p-3 bg-blue-100 text-primary rounded-full">
                    <i class="fa-solid fa-euro-sign text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-md border-l-4 border-green-500 hover:shadow-lg transition">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-sm font-semibold uppercase tracking-wide">Artículos Vendidos (Hoy)</p>
                    <h3 class="text-3xl font-bold text-gray-800 mt-1"><?= $items_sold ?></h3>
                </div>
                <div class="p-3 bg-green-100 text-green-600 rounded-full">
                    <i class="fa-solid fa-shopping-bag text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-md border-l-4 border-indigo-500 hover:shadow-lg transition">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-sm font-semibold uppercase tracking-wide">Total de Productos</p>
                    <h3 class="text-3xl font-bold text-gray-800 mt-1"><?= $total_products ?></h3>
                </div>
                <div class="p-3 bg-indigo-100 text-indigo-600 rounded-full">
                    <i class="fa-solid fa-boxes-stacked text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
            <h2 class="text-xl font-bold text-gray-800">Transacciones Recientes</h2>
            <a href="<?php echo BASE_URL; ?>/pages/reports/sales.php" class="text-primary hover:underline text-sm font-medium">Ver Todas</a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Factura</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha/Hora</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Método</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (count($recent_transactions) > 0): ?>
                        <?php foreach ($recent_transactions as $txn): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-primary">#<?= htmlspecialchars($txn['bill_number']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= date('d/m/Y H:i', strtotime($txn['created_at'])) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($txn['customer_name'] ?? 'Consumidor Final') ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 capitalize"><?= htmlspecialchars($txn['payment_method']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 text-right"><?= formatCurrency(number_format($txn['grand_total'], 2)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-gray-500">No hay transacciones recientes.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php elseif (isSeller()): ?>
    <!-- Seller Dashboard -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 my-10 max-w-4xl mx-auto mt-20">
        <a href="<?php echo BASE_URL; ?>/pages/billing/index.php" class="bg-primary hover:bg-blue-800 text-white rounded-xl shadow-xl p-10 flex flex-col items-center justify-center transition duration-300 transform hover:-translate-y-2">
            <i class="fa-solid fa-cash-register text-7xl mb-6"></i>
            <h2 class="text-3xl font-bold mb-2">POS (Ventas)</h2>
            <p class="text-blue-200 text-lg">Hacer una venta nueva</p>
        </a>
        <a href="<?php echo BASE_URL; ?>/pages/returns/index.php" class="bg-gray-800 hover:bg-gray-900 text-white rounded-xl shadow-xl p-10 flex flex-col items-center justify-center transition duration-300 transform hover:-translate-y-2">
            <i class="fa-solid fa-undo text-7xl mb-6"></i>
            <h2 class="text-3xl font-bold mb-2">Devoluciones</h2>
            <p class="text-gray-300 text-lg">Procesar una devolución</p>
        </a>
    </div>
<?php elseif (isInventoryManager()): ?>
    <!-- Inventory Manager Dashboard -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8 my-10 max-w-5xl mx-auto mt-20">
        <a href="<?php echo BASE_URL; ?>/pages/products/index.php" class="bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl shadow-xl p-8 flex flex-col items-center justify-center transition duration-300 transform hover:-translate-y-2">
            <i class="fa-solid fa-box text-6xl mb-4"></i>
            <h2 class="text-2xl font-bold mb-2">Productos</h2>
            <p class="text-indigo-200 text-center">Gestión de inventario y stock</p>
        </a>
        <a href="<?php echo BASE_URL; ?>/pages/categories/index.php" class="bg-purple-600 hover:bg-purple-700 text-white rounded-xl shadow-xl p-8 flex flex-col items-center justify-center transition duration-300 transform hover:-translate-y-2">
            <i class="fa-solid fa-tags text-6xl mb-4"></i>
            <h2 class="text-2xl font-bold mb-2">Categorías</h2>
            <p class="text-purple-200 text-center">Organizar productos</p>
        </a>
        <a href="<?php echo BASE_URL; ?>/pages/products/barcodes.php" class="bg-gray-700 hover:bg-gray-800 text-white rounded-xl shadow-xl p-8 flex flex-col items-center justify-center transition duration-300 transform hover:-translate-y-2">
            <i class="fa-solid fa-barcode text-6xl mb-4"></i>
            <h2 class="text-2xl font-bold mb-2">Códigos de Barras</h2>
            <p class="text-gray-300 text-center">Impresión y generación</p>
        </a>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
