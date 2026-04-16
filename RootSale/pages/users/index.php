<?php
// pages/users/index.php
require_once __DIR__ . '/../../includes/header.php';
requireAdmin();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        $error = "Petición inválida.";
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'delete') {
            $id = (int)$_POST['id'];
            if ($id == $_SESSION['user_id']) {
                $error = "No puedes eliminar tu propio usuario.";
            } else {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$id]);
                $success = "Usuario eliminado.";
            }
        } elseif ($action === 'toggle_status') {
             $id = (int)$_POST['id'];
             if ($id != $_SESSION['user_id']) {
                 $stmt = $pdo->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ?");
                 $stmt->execute([$id]);
                 $success = "Estado del usuario actualizado.";
             } else {
                 $error = "No puedes desactivar tu propio usuario.";
             }
        }
    }
}

$stmt = $pdo->query("SELECT * FROM users ORDER BY role, full_name");
$users = $stmt->fetchAll();
?>

<div class="max-w-6xl mx-auto pb-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800"><i class="fa-solid fa-users-cog mr-2 text-primary"></i>Gestión de Usuarios</h1>
        <a href="save-user.php" class="bg-primary hover:bg-blue-700 text-white font-bold py-2 px-4 rounded shadow transition flex items-center gap-2">
            <i class="fa-solid fa-user-plus"></i> Añadir Usuario
        </a>
    </div>

    <?php if ($error): ?><div class="bg-red-100 text-red-700 p-4 mb-4 rounded border-l-4 border-red-500"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="bg-green-100 text-green-700 p-4 mb-4 rounded border-l-4 border-green-500"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Usuario</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Rol</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Estado</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($users as $u): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="font-medium text-gray-900"><?= htmlspecialchars($u['full_name']) ?></div>
                            <div class="text-sm text-gray-500">@<?= htmlspecialchars($u['username']) ?></div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            <?= htmlspecialchars($u['email'] ?? 'N/A') ?>
                        </td>
                        <td class="px-6 py-4 text-center text-sm">
                            <?php if($u['role'] == 'admin'): ?>
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-indigo-100 text-indigo-800 uppercase">Admin</span>
                            <?php elseif($u['role'] == 'inventory'): ?>
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800 uppercase">Inventario</span>
                            <?php elseif($u['role'] == 'customer_display'): ?>
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800 uppercase">Pantalla</span>
                            <?php else: ?>
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 uppercase">Vendedor</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-center text-sm">
                            <?php if($u['is_active']): ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Activo</span>
                            <?php else: ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Inactivo</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-right text-sm font-medium flex justify-end gap-3 items-center">
                            
                            <a href="save-user.php?id=<?= $u['id'] ?>" class="text-blue-600 hover:text-blue-900" title="Editar"><i class="fa-solid fa-edit"></i></a>
                            
                            <?php if($u['id'] != $_SESSION['user_id']): ?>
                            <form action="" method="POST" class="inline" onsubmit="return confirm('¿Cambiar el estado de este usuario?');">
                                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                <input type="hidden" name="action" value="toggle_status">
                                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                <button type="submit" class="text-yellow-600 hover:text-yellow-900" title="Alternar Estado"><i class="fa-solid fa-power-off"></i></button>
                            </form>

                            <form action="" method="POST" class="inline" onsubmit="return confirm('¿Borrar permanentemente este usuario?');">
                                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                <button type="submit" class="text-red-500 hover:text-red-700" title="Eliminar"><i class="fa-solid fa-trash"></i></button>
                            </form>
                            <?php else: ?>
                                <span class="text-gray-400 text-xs italic">(Actual)</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
