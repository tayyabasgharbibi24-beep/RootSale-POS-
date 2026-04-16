<?php
// pages/products/index.php
require_once __DIR__ . '/../../includes/header.php';
requireInventoryManagerOrAdmin();

// Pagination and Search
$search = $_GET['search'] ?? '';
$cat_filter = $_GET['category'] ?? '';

try {
    $pdo->exec("ALTER TABLE products ADD COLUMN is_active INTEGER DEFAULT 1");
} catch(Exception $e) {}

$query = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE IFNULL(p.is_active, 1) = 1";
$params = [];

if ($search) {
    $query .= " AND (p.name LIKE ? OR p.barcode LIKE ? OR p.brand LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($cat_filter) {
    $query .= " AND p.category_id = ?";
    $params[] = $cat_filter;
}

$query .= " ORDER BY p.id DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Get categories for filter
$cats = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();
?>

<div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
    <h1 class="text-2xl font-bold text-gray-800">Productos</h1>
    <div class="flex gap-2 w-full md:w-auto">
        <a href="bulk-barcode.php"
            class="bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 rounded shadow transition flex items-center gap-2">
            <i class="fa-solid fa-barcode"></i> Ver Códigos
        </a>
        <a href="save-product.php"
            class="bg-primary hover:bg-blue-700 text-white px-4 py-2 rounded shadow transition flex items-center gap-2">
            <i class="fa-solid fa-plus"></i> Nuevo Producto
        </a>
    </div>
</div>

<!-- Filters -->
<div class="bg-white p-4 rounded-lg shadow-md mb-6">
    <form action="" method="GET" class="flex flex-col md:flex-row gap-4">
        <div class="flex-1 flex gap-2">
            <input type="text" name="search" id="searchInput" value="<?= htmlspecialchars($search) ?>"
                placeholder="Buscar por código, nombre o marca..."
                class="w-full border border-gray-300 rounded-md px-4 py-2 focus:outline-none focus:ring-primary focus:border-primary">
            <button type="button" onclick="startScanner()" class="bg-gray-800 text-white px-4 py-2 rounded hover:bg-gray-900 transition flex items-center justify-center shadow whitespace-nowrap" title="Escanear Código"><i class="fa-solid fa-camera mr-2"></i> Escanear</button>
        </div>
        <div class="w-full md:w-64">
            <select name="category"
                class="w-full border border-gray-300 rounded-md px-4 py-2 focus:outline-none focus:ring-primary focus:border-primary">
                <option value="">Todas las Categorías</option>
                <?php foreach ($cats as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $cat_filter == $c['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit"
            class="bg-gray-200 text-gray-800 px-6 py-2 rounded hover:bg-gray-300 transition">Filtrar</button>
        <?php if ($search || $cat_filter): ?>
            <a href="index.php" class="bg-red-100 text-red-700 px-4 py-2 rounded hover:bg-red-200 transition">Limpiar</a>
        <?php endif; ?>
    </form>
</div>

<div class="bg-white rounded-lg shadow-md overflow-hidden content-container">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Código
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Producto
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Categoría
                        / Marca</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Precio
                    </th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Stock
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($products as $p): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-600">
                            <?= htmlspecialchars($p['barcode']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10">
                                    <?php if (!empty($p['image'])): ?>
                                        <img class="h-10 w-10 rounded object-contain border border-gray-200" src="<?= BASE_URL . $p['image'] ?>" alt="">
                                    <?php else: ?>
                                        <div class="h-10 w-10 bg-gray-100 rounded flex items-center justify-center text-gray-400">
                                            <i class="fa-solid fa-box"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($p['name']) ?></div>
                                    <div class="text-xs text-gray-500">Talla: <?= htmlspecialchars($p['size']) ?> | Color: <?= htmlspecialchars($p['color']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900"><?= htmlspecialchars($p['category_name'] ?? 'N/A') ?></div>
                            <div class="text-xs text-gray-500"><?= htmlspecialchars($p['brand']) ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right font-bold">
                            <?= formatCurrency(number_format($p['selling_price'], 2)) ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <?php if ($p['stock'] <= $p['low_stock_threshold']): ?>
                                <span
                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800"
                                    title="Stock Bajo">
                                    <?= $p['stock'] ?>
                                </span>
                            <?php else: ?>
                                <span
                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    <?= $p['stock'] ?>
                                </span>
                            <?php endif; ?>
                            <button
                                onclick="updateStock(<?= $p['id'] ?>, '<?= htmlspecialchars($p['name']) ?>', <?= $p['stock'] ?>)"
                                class="ml-2 text-indigo-500 hover:text-indigo-800"><i
                                    class="fa-solid fa-sync text-xs"></i></button>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="generate-barcode.php?id=<?= $p['id'] ?>" class="text-gray-600 hover:text-gray-900 mr-3"
                                title="Imprimir Código"><i class="fa-solid fa-print"></i></a>
                            <a href="save-product.php?id=<?= $p['id'] ?>" class="text-indigo-600 hover:text-indigo-900 mr-3"
                                title="Editar"><i class="fa-solid fa-edit"></i></a>
                            <a href="delete-product.php?id=<?= $p['id'] ?>&token=<?= generateCSRFToken() ?>"
                                onclick="return confirm('¿Seguro que desea eliminar este producto?');"
                                class="text-red-600 hover:text-red-900" title="Eliminar"><i
                                    class="fa-solid fa-trash"></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (count($products) === 0): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500 border-t border-gray-200">No hay
                            productos registrados que coincidan con la búsqueda.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Stock Update Modal -->
<div id="stockModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-sm w-full p-6">
        <h3 class="text-lg font-bold mb-2">Actualizar Stock</h3>
        <p class="text-sm text-gray-600 mb-4" id="modalProductName"></p>

        <form id="stockForm" action="update-stock.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            <input type="hidden" name="product_id" id="modalProductId">
            <input type="hidden" name="return_url" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Ajuste de Cantidad (+ o -)*</label>
                <div class="flex items-center">
                    <button type="button" onclick="adjustInput(-1)"
                        class="bg-gray-200 px-3 py-2 rounded-l border border-gray-300">-</button>
                    <input type="number" name="adjustment" id="stockAdjustment" required
                        class="w-full border-y border-gray-300 px-4 py-2 text-center focus:outline-none" value="0">
                    <button type="button" onclick="adjustInput(1)"
                        class="bg-gray-200 px-3 py-2 rounded-r border border-gray-300">+</button>
                </div>
                <p class="text-xs text-gray-500 mt-1">Stock actual: <span id="currentStockDisplay"></span></p>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Razón del Ajuste</label>
                <input type="text" name="reason" class="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                    placeholder="Ej. Inventario físico, Producto dañado...">
            </div>

            <div class="flex justify-end gap-2 mt-6">
                <button type="button" onclick="document.getElementById('stockModal').classList.add('hidden')"
                    class="bg-gray-200 text-gray-800 px-4 py-2 rounded hover:bg-gray-300 text-sm">Cancelar</button>
                <button type="submit" class="bg-primary text-white px-4 py-2 rounded hover:bg-blue-700 text-sm">Guardar
                    Ajuste</button>
            </div>
        </form>
    </div>
</div>

<!-- Scanner Modal -->
<div id="scannerModal" class="fixed inset-0 bg-black bg-opacity-75 z-50 hidden flex flex-col pt-10 px-4">
    <div class="flex justify-between items-center mb-4 max-w-lg mx-auto w-full">
        <h2 class="text-white text-xl font-bold">Escanear Código de Barras</h2>
        <button type="button" onclick="stopScanner()" class="text-white text-3xl"><i class="fa-solid fa-times"></i></button>
    </div>
    <div class="bg-black w-full max-w-lg mx-auto rounded overflow-hidden relative shadow-2xl" style="height: 300px;">
        <div id="interactive" class="viewport w-full h-full"></div>
        <div class="absolute inset-0 border-4 border-primary bg-transparent z-10 opacity-50 m-12 pointer-events-none rounded"></div>
    </div>
    <p class="text-center text-white mt-4 max-w-lg mx-auto text-sm">Alinee el código de barras dentro del marco para leer el código.</p>
</div>

<script>
    let quaggaRunning = false;
    function startScanner() {
        document.getElementById('scannerModal').classList.remove('hidden');
        if (quaggaRunning) return;

        Quagga.init({
            inputStream: {
                name: "Live",
                type: "LiveStream",
                target: document.querySelector('#interactive'),
                constraints: { width: 640, height: 480, facingMode: "environment" }
            },
            decoder: { readers: ["code_128_reader", "ean_reader", "ean_8_reader", "code_39_reader"] }
        }, function (err) {
            if (err) {
                console.error(err);
                if (typeof showToast !== 'undefined') {
                    showToast('Error al acceder a la cámara.', 'error');
                } else {
                    alert('Error al acceder a la cámara. Asegúrese de tener permisos.');
                }
                stopScanner();
                return;
            }
            Quagga.start();
            quaggaRunning = true;
        });

        Quagga.onDetected(function (result) {
            var code = result.codeResult.code;
            document.getElementById('searchInput').value = code;
            stopScanner();
            if (typeof showToast !== 'undefined') {
               showToast('Código escaneado. Buscando...', 'info');
            }
            // Auto-submit the form
            document.getElementById('searchInput').closest('form').submit();
        });
    }

    function stopScanner() {
        document.getElementById('scannerModal').classList.add('hidden');
        if (quaggaRunning) {
            Quagga.stop();
            quaggaRunning = false;
        }
    }

    function updateStock(id, name, currentStock) {
        document.getElementById('modalProductId').value = id;
        document.getElementById('modalProductName').textContent = name;
        document.getElementById('currentStockDisplay').textContent = currentStock;
        document.getElementById('stockAdjustment').value = 0;
        document.getElementById('stockModal').classList.remove('hidden');
    }

    function adjustInput(amount) {
        const input = document.getElementById('stockAdjustment');
        input.value = parseInt(input.value || 0) + amount;
    }

    <?php if (isset($_GET['success'])): ?>
        showToast('<?= htmlspecialchars($_GET['success']) ?>', 'success');
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        showToast('<?= htmlspecialchars($_GET['error']) ?>', 'error');
    <?php endif; ?>
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>