<?php
// pages/users/save-user.php
require_once __DIR__ . '/../../includes/header.php';
requireAdmin();

$error = '';
$success = '';

// Default values
$user = [
    'id' => '',
    'username' => '',
    'full_name' => '',
    'email' => '',
    'role' => 'seller',
    'is_active' => 1
];

// If editing, load user
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $fetchedUser = $stmt->fetch();
    
    if ($fetchedUser) {
        $user = $fetchedUser;
    } else {
        $error = "Usuario no encontrado.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        $error = "Falló la verificación del token de seguridad.";
    } else {
        $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
        $username = sanitizeInput($_POST['username']);
        $full_name = sanitizeInput($_POST['full_name']);
        $email = sanitizeInput($_POST['email']);
        $role = sanitizeInput($_POST['role']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        $password = $_POST['password'] ?? '';
        
        // Check duplicate username
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->execute([$username, $id ?? 0]);
        if ($stmt->fetch()) {
            $error = "El nombre de usuario ya está en uso.";
            // Keep filled fields
            $user['username'] = $username;
            $user['full_name'] = $full_name;
            $user['email'] = $email;
            $user['role'] = $role;
            $user['is_active'] = $is_active;
        } else {
            if ($id) {
                // Update
                if (!empty($password)) {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET username=?, full_name=?, email=?, role=?, is_active=?, password=? WHERE id=?");
                    $stmt->execute([$username, $full_name, $email, $role, $is_active, $hash, $id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET username=?, full_name=?, email=?, role=?, is_active=? WHERE id=?");
                    $stmt->execute([$username, $full_name, $email, $role, $is_active, $id]);
                }
                $success = "Usuario actualizado exitosamente.";
                $user['username'] = $username;
                $user['full_name'] = $full_name;
                $user['email'] = $email;
                $user['role'] = $role;
                $user['is_active'] = $is_active;
            } else {
                // Insert
                if (empty($password)) {
                    $error = "La contraseña es obligatoria para un usuario nuevo.";
                } else {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, email, role, is_active) VALUES (?, ?, ?, ?, ?, ?)");
                    if ($stmt->execute([$username, $hash, $full_name, $email, $role, $is_active])) {
                        header("Location: index.php?success=created");
                        exit;
                    } else {
                        $error = "Error al crear el usuario.";
                    }
                }
            }
        }
    }
}
?>

<div class="max-w-2xl mx-auto pb-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">
            <i class="fa-solid <?= $user['id'] ? 'fa-user-edit' : 'fa-user-plus' ?> mr-2 text-primary"></i>
            <?= $user['id'] ? 'Editar Usuario' : 'Crear Usuario' ?>
        </h1>
        <a href="index.php" class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded shadow flex items-center gap-2">
            <i class="fa-solid fa-arrow-left"></i> Volver a la Lista
        </a>
    </div>

    <?php if ($error): ?><div class="bg-red-100 text-red-700 p-4 mb-4 rounded border-l-4 border-red-500"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="bg-green-100 text-green-700 p-4 mb-4 rounded border-l-4 border-green-500"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <div class="bg-white rounded-lg shadow-md p-6 border-t-4 border-primary">
        <form action="" method="POST">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            <?php if($user['id']): ?>
                <input type="hidden" name="id" value="<?= htmlspecialchars($user['id']) ?>">
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre Completo *</label>
                    <input type="text" name="full_name" required value="<?= htmlspecialchars($user['full_name']) ?>" class="w-full border border-gray-300 rounded px-4 py-2 focus:ring-primary focus:border-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Usuario de Acceso *</label>
                    <input type="text" name="username" required value="<?= htmlspecialchars($user['username']) ?>" class="w-full border border-gray-300 rounded px-4 py-2 focus:ring-primary focus:border-primary" pattern="[a-zA-Z0-9_]+" title="Letras y números sin espacios">
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Correo Electrónico</label>
                <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" class="w-full border border-gray-300 rounded px-4 py-2 focus:ring-primary focus:border-primary">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Contraseña <?= $user['id'] ? '(Dejar en blanco para mantener la actual)' : '*' ?>
                </label>
                <input type="password" name="password" <?= !$user['id'] ? 'required' : '' ?> minlength="6" class="w-full border border-gray-300 rounded px-4 py-2 focus:ring-primary focus:border-primary">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Rol del Sistema *</label>
                    <select name="role" required class="w-full border border-gray-300 rounded px-4 py-2 focus:ring-primary focus:border-primary">
                        <option value="seller" <?= $user['role'] === 'seller' ? 'selected' : '' ?>>Vendedor (POS + Devoluciones)</option>
                        <option value="inventory" <?= $user['role'] === 'inventory' ? 'selected' : '' ?>>Inventario (Productos + Cat)</option>
                        <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Administrador (Total)</option>
                        <option value="customer_display" <?= $user['role'] === 'customer_display' ? 'selected' : '' ?>>Pantalla de Cliente (Solo Vista)</option>
                    </select>
                </div>
                
                <div class="flex items-center mt-6">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" <?= $user['is_active'] ? 'checked' : '' ?> class="h-5 w-5 text-primary border-gray-300 rounded focus:ring-primary">
                        <span class="ml-2 text-gray-800 font-medium tracking-wide">Usuario Activo</span>
                    </label>
                </div>
            </div>
            
            <div class="border-t border-gray-200 mt-6 pt-6 flex justify-end">
                <button type="submit" class="bg-primary hover:bg-blue-700 text-white font-bold py-3 px-8 rounded shadow-lg flex items-center gap-2 transition transform hover:-translate-y-1">
                    <i class="fa-solid fa-save"></i> <?= $user['id'] ? 'Actualizar Usuario' : 'Crear Usuario' ?>
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
