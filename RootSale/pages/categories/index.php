<?php
// pages/categories/index.php
require_once __DIR__ . '/../../includes/header.php';
requireInventoryManagerOrAdmin();

$stmt = $pdo->query("SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) as product_count FROM categories c ORDER BY c.name ASC");
$categories = $stmt->fetchAll();
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Categorías</h1>
    <a href="add-category.php" class="bg-primary hover:bg-blue-700 text-white px-4 py-2 rounded shadow transition flex items-center gap-2">
        <i class="fa-solid fa-plus"></i> Nueva Categoría
    </a>
</div>

<div class="bg-white rounded-lg shadow-md overflow-hidden content-container">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descripción</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Productos</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($categories as $cat): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= $cat['id'] ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= htmlspecialchars($cat['name']) ?></td>
                    <td class="px-6 py-4 text-sm text-gray-500"><?= htmlspecialchars($cat['description']) ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                            <?= $cat['product_count'] ?> artículos
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <a href="edit-category.php?id=<?= $cat['id'] ?>" class="text-indigo-600 hover:text-indigo-900 mr-3" title="Editar"><i class="fa-solid fa-edit"></i></a>
                        <?php if ($cat['product_count'] == 0): ?>
                            <a href="delete-category.php?id=<?= $cat['id'] ?>&token=<?= generateCSRFToken() ?>" onclick="return confirm('¿Seguro que desea eliminar esta categoría?');" class="text-red-600 hover:text-red-900" title="Eliminar"><i class="fa-solid fa-trash"></i></a>
                        <?php else: ?>
                            <span class="text-gray-400 cursor-not-allowed" title="No se puede eliminar (contiene productos)"><i class="fa-solid fa-trash"></i></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (count($categories) === 0): ?>
                <tr>
                    <td colspan="5" class="px-6 py-8 text-center text-gray-500 border-t border-gray-200">No hay categorías registradas.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
if(isset($_GET['success'])) {
    echo "<script>window.onload = function() { showToast('".htmlspecialchars($_GET['success'])."', 'success'); }</script>";
}
if(isset($_GET['error'])) {
    echo "<script>window.onload = function() { showToast('".htmlspecialchars($_GET['error'])."', 'error'); }</script>";
}
require_once __DIR__ . '/../../includes/footer.php'; 
?>
