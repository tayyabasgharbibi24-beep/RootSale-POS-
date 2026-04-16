<?php
// pages/customers/update-customer.php
require_once __DIR__ . '/../../includes/header.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$id]);
$customer = $stmt->fetch();

if (!$customer) {
    header("Location: index.php?error=Cliente no encontrado");
    exit;
}

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
            $stmt = $pdo->prepare("UPDATE customers SET name = ?, phone = ?, email = ?, address = ? WHERE id = ?");
            if ($stmt->execute([$name, $phone, $email, $address, $id])) {
                header("Location: index.php?success=Cliente actualizado.");
                exit;
            } else {
                $error = "Error al actualizar.";
            }
        }
    }
}
?>

<div class="max-w-xl mx-auto pb-8">
    <div class="flex items-center mb-6">
        <a href="index.php" class="text-gray-500 hover:text-primary mr-4"><i class="fa-solid fa-arrow-left text-xl"></i></a>
        <h1 class="text-2xl font-bold text-gray-800">Editar Cliente</h1>
    </div>

    <?php if ($error): ?>
        <div class="bg-red-100 text-red-700 p-4 mb-4 rounded border-l-4 border-red-500"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="bg-white rounded-lg shadow-md p-6">
        <form action="" method="POST">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre Completo *</label>
                <input type="text" name="name" value="<?= htmlspecialchars($customer['name']) ?>" required class="w-full border border-gray-300 rounded px-4 py-2 focus:ring-primary focus:border-primary">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                <input type="text" name="phone" value="<?= htmlspecialchars($customer['phone']) ?>" class="w-full border border-gray-300 rounded px-4 py-2 focus:ring-primary focus:border-primary">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($customer['email']) ?>" class="w-full border border-gray-300 rounded px-4 py-2 focus:ring-primary focus:border-primary">
            </div>
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">Dirección</label>
                <textarea name="address" rows="3" class="w-full border border-gray-300 rounded px-4 py-2 focus:ring-primary focus:border-primary"><?= htmlspecialchars($customer['address']) ?></textarea>
            </div>
            <div class="flex justify-end gap-3">
                <a href="index.php" class="bg-gray-200 text-gray-800 px-4 py-2 rounded hover:bg-gray-300 transition">Cancelar</a>
                <button type="submit" class="bg-primary text-white px-6 py-2 rounded hover:bg-blue-700 shadow font-bold">Actualizar Cliente</button>
            </div>
        </form>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
