<?php
// pages/categories/add-category.php
require_once __DIR__ . '/../../includes/header.php';
requireInventoryManagerOrAdmin();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        $error = "Error de validación.";
    } else {
        $name = sanitizeInput($_POST['name']);
        $description = sanitizeInput($_POST['description']);
        
        if (empty($name)) {
            $error = "El nombre es obligatorio.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
            if ($stmt->execute([$name, $description])) {
                header("Location: index.php?success=Categoría añadida correctamente.");
                exit;
            } else {
                $error = "Error al guardar en la base de datos.";
            }
        }
    }
}
?>

<div class="max-w-xl mx-auto">
    <div class="flex items-center mb-6">
        <a href="index.php" class="text-gray-500 hover:text-primary mr-4"><i class="fa-solid fa-arrow-left text-xl"></i></a>
        <h1 class="text-2xl font-bold text-gray-800">Nueva Categoría</h1>
    </div>

    <?php if ($error): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-lg shadow-md p-6">
        <form action="" method="POST">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            
            <div class="mb-4">
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nombre de la Categoría *</label>
                <input type="text" name="name" id="name" required class="w-full border border-gray-300 rounded-md px-4 py-2 focus:outline-none focus:ring-primary focus:border-primary">
            </div>
            
            <div class="mb-6">
                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                <textarea name="description" id="description" rows="3" class="w-full border border-gray-300 rounded-md px-4 py-2 focus:outline-none focus:ring-primary focus:border-primary"></textarea>
            </div>
            
            <div class="flex justify-end gap-3">
                <a href="index.php" class="bg-gray-200 text-gray-800 px-4 py-2 rounded hover:bg-gray-300 transition">Cancelar</a>
                <button type="submit" class="bg-primary text-white px-6 py-2 rounded hover:bg-blue-700 transition">Guardar</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
