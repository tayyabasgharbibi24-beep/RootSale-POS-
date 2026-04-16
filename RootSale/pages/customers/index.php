<?php
// pages/customers/index.php
require_once __DIR__ . '/../../includes/header.php';

$search = $_GET['search'] ?? '';
$query = "SELECT * FROM customers WHERE 1=1";
$params = [];

if ($search) {
    $query .= " AND (name LIKE ? OR phone LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$query .= " ORDER BY name ASC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$customers = $stmt->fetchAll();
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Clientes</h1>
    <a href="add-customer.php" class="bg-primary hover:bg-blue-700 text-white px-4 py-2 rounded shadow transition flex items-center gap-2">
        <i class="fa-solid fa-plus"></i> Nuevo Cliente
    </a>
</div>

<div class="bg-white p-4 rounded-lg shadow-md mb-6">
    <form action="" method="GET" class="flex flex-col md:flex-row gap-4">
        <div class="flex-1">
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Buscar por nombre, teléfono o email..." class="w-full border border-gray-300 rounded-md px-4 py-2 focus:outline-none focus:ring-primary focus:border-primary">
        </div>
        <button type="submit" class="bg-gray-200 text-gray-800 px-6 py-2 rounded hover:bg-gray-300 transition">Filtrar</button>
        <?php if($search): ?>
            <a href="index.php" class="bg-red-100 text-red-700 px-4 py-2 rounded hover:bg-red-200 transition">Limpiar</a>
        <?php endif; ?>
    </form>
</div>

<div class="bg-white rounded-lg shadow-md overflow-hidden content-container">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contacto</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dirección</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total Gastado</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($customers as $c): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900"><?= htmlspecialchars($c['name']) ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <div><i class="fa-solid fa-phone text-xs mr-1 text-gray-400"></i> <?= htmlspecialchars($c['phone'] ?: '-') ?></div>
                        <div><i class="fa-solid fa-envelope text-xs mr-1 text-gray-400"></i> <?= htmlspecialchars($c['email'] ?: '-') ?></div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500"><?= htmlspecialchars($c['address'] ?: 'No definida') ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-bold text-gray-900">
                        <?= formatCurrency(number_format($c['total_spent'], 2)) ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <a href="update-customer.php?id=<?= $c['id'] ?>" class="text-indigo-600 hover:text-indigo-900 mr-3" title="Editar"><i class="fa-solid fa-edit"></i></a>
                        <a href="delete-customer.php?id=<?= $c['id'] ?>&token=<?= generateCSRFToken() ?>" onclick="return confirm('¿Seguro que desea eliminar este cliente?');" class="text-red-600 hover:text-red-900" title="Eliminar"><i class="fa-solid fa-trash"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (count($customers) === 0): ?>
                <tr><td colspan="5" class="px-6 py-8 text-center text-gray-500">No se encontraron clientes.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
if(isset($_GET['success'])) echo "<script>window.onload = function() { showToast('".htmlspecialchars($_GET['success'])."', 'success'); }</script>";
if(isset($_GET['error'])) echo "<script>window.onload = function() { showToast('".htmlspecialchars($_GET['error'])."', 'error'); }</script>";
require_once __DIR__ . '/../../includes/footer.php'; 
?>
