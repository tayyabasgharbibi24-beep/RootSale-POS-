<?php
// pages/settings/users.php
require_once __DIR__ . '/../../includes/header.php';
requireAdmin();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        $error = "Error de CSRF.";
    } else {
        $action = $_POST['action'] ?? '';

        if($action === 'add') {
            $username = sanitizeInput($_POST['username']);
            $password = $_POST['password'];
            $full_name = sanitizeInput($_POST['full_name']);
            $role = sanitizeInput($_POST['role']);

            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $error = "Ese nombre de usuario ya existe.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)");
                $hash = password_hash($password, PASSWORD_DEFAULT);
                if ($stmt->execute([$username, $hash, $full_name, $role])) {
                    $success = "Usuario creado.";
                } else {
                    $error = "Error guardando usuario.";
                }
            }
        } elseif ($action === 'delete') {
            $id = (int)$_POST['id'];
            if ($id == $_SESSION['user_id']) {
                $error = "No puedes eliminar tu propio usuario.";
            } else {
                $stmt = $pdo->prepare("UPDATE users SET is_active = 0 WHERE id = ?"); // Soft delete
                $stmt->execute([$id]);
                $success = "Usuario desactivado.";
            }
        }
    }
}

$stmt = $pdo->query("SELECT * FROM users WHERE is_active = 1 ORDER BY role, full_name");
$users = $stmt->fetchAll();
?>

<div class="max-w-6xl mx-auto pb-8">
    <div class="flex items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800"><i class="fa-solid fa-users-cog mr-2 text-primary"></i>Gestión de Usuarios</h1>
    </div>

    <?php if ($error): ?><div class="bg-red-100 text-red-700 p-4 mb-4 rounded border-l-4 border-red-500"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="bg-green-100 text-green-700 p-4 mb-4 rounded border-l-4 border-green-500"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-md overflow-hidden content-container">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nombre / Usuario</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Rol</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($users as $u): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900"><?= htmlspecialchars($u['full_name']) ?></div>
                                <div class="text-xs text-gray-500">@<?= htmlspecialchars($u['username']) ?></div>
                            </td>
                            <td class="px-4 py-3 text-center text-sm">
                                <?php if($u['role'] == 'admin'): ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-indigo-100 text-indigo-800">Admin</span>
                                <?php else: ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Cajero</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <?php if($u['id'] != $_SESSION['user_id']): ?>
                                <form action="" method="POST" class="inline" onsubmit="return confirm('¿Desactivar este usuario?');">
                                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                    <button type="submit" class="text-red-500 hover:text-red-700"><i class="fa-solid fa-user-minus"></i></button>
                                </form>
                                <?php else: ?>
                                    <span class="text-gray-400 text-xs">(Actual)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div>
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">Crear Usuario</h2>
                <form action="" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre Real</label>
                        <input type="text" name="full_name" required class="w-full border border-gray-300 rounded px-4 py-2 text-sm focus:ring-primary focus:border-primary">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Usuario de Login</label>
                        <input type="text" name="username" required class="w-full border border-gray-300 rounded px-4 py-2 text-sm focus:ring-primary focus:border-primary" pattern="[a-zA-Z0-9_]+" title="Letras y números sin espacios">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Contraseña</label>
                        <input type="password" name="password" required minlength="6" class="w-full border border-gray-300 rounded px-4 py-2 text-sm focus:ring-primary focus:border-primary">
                    </div>
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Rol</label>
                        <select name="role" required class="w-full border border-gray-300 rounded px-4 py-2 text-sm focus:ring-primary focus:border-primary">
                            <option value="cashier">Cajero (Ventas)</option>
                            <option value="admin">Administrador (Total)</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="w-full bg-primary hover:bg-blue-700 text-white font-bold py-2 rounded shadow transition">
                        Registrar Usuario
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
