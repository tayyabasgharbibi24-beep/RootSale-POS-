<?php
// pages/customers/add-customer.php
require_once __DIR__ . '/../../includes/header.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        $error = "Petición no válida.";
    } else {
        $name = sanitizeInput($_POST['name']);
        $phone = sanitizeInput($_POST['phone']);
        $email = sanitizeInput($_POST['email']);
        $address = sanitizeInput($_POST['address']);
        
        if (empty($name)) {
            $error = "El nombre es obligatorio.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO customers (name, phone, email, address) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$name, $phone, $email, $address])) {
                $return_url = $_GET['return'] ?? 'index.php';
                header("Location: $return_url?success=Cliente añadido correctamente.");
                exit;
            } else {
                $error = "Error al guardar en la base de datos.";
            }
        }
    }
}
?>

<div class="max-w-xl mx-auto pb-8">
    <div class="flex items-center mb-6">
        <a href="index.php" class="text-gray-500 hover:text-primary mr-4"><i class="fa-solid fa-arrow-left text-xl"></i></a>
        <h1 class="text-2xl font-bold text-gray-800">Nuevo Cliente</h1>
    </div>

    <?php if ($error): ?>
        <div class="bg-red-100 text-red-700 p-4 mb-4 rounded border-l-4 border-red-500"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="bg-white rounded-lg shadow-md p-6">
        <form action="" method="POST">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre Completo *</label>
                <input type="text" name="name" required class="w-full border border-gray-300 rounded px-4 py-2 focus:ring-primary focus:border-primary">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                <input type="text" name="phone" class="w-full border border-gray-300 rounded px-4 py-2 focus:ring-primary focus:border-primary">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" name="email" class="w-full border border-gray-300 rounded px-4 py-2 focus:ring-primary focus:border-primary">
            </div>
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">Dirección</label>
                <textarea name="address" rows="3" class="w-full border border-gray-300 rounded px-4 py-2 focus:ring-primary focus:border-primary"></textarea>
            </div>
            <div class="flex justify-end gap-3 divide-x divide-transparent">
                <a href="<?= htmlspecialchars($_GET['return'] ?? 'index.php') ?>" class="bg-gray-200 text-gray-800 px-4 py-2 rounded hover:bg-gray-300 transition">Cancelar</a>
                <button type="submit" class="bg-primary text-white px-6 py-2 rounded hover:bg-blue-700 shadow font-bold">Guardar Cliente</button>
            </div>
        </form>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
