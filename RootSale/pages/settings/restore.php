<?php
// pages/settings/restore.php
require_once __DIR__ . '/../../includes/header.php';
requireAdmin();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['backup_file'])) {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        $error = "Error de validación CSRF.";
    } else {
        $file = $_FILES['backup_file'];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error = "Error al subir el archivo.";
        } else {
            // Check extension
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if ($ext !== 'db' && $ext !== 'sqlite' && $ext !== 'sqlite3') {
                $error = "El archivo debe ser una base de datos SQLite (.db).";
            } else {
                $target_path = DB_PATH;
                $backup_old = $target_path . '.' . time() . '.bak'; // Keep old temporarily
                
                rename($target_path, $backup_old);
                
                if (move_uploaded_file($file['tmp_name'], $target_path)) {
                    $success = "Base de datos restaurada correctamente. Sesiones en vivo pueden requerir re-login.";
                } else {
                    rename($backup_old, $target_path); // Restore on error
                    $error = "Error al reemplazar la base de datos.";
                }
            }
        }
    }
}
?>

<div class="max-w-2xl mx-auto pb-8">
    <div class="flex items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800"><i class="fa-solid fa-database mr-2 text-primary"></i>Respaldos del Sistema</h1>
    </div>

    <?php if ($error): ?><div class="bg-red-100 text-red-700 p-4 mb-4 rounded border-l-4 border-red-500"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="bg-green-100 text-green-700 p-4 mb-4 rounded border-l-4 border-green-500"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6 border-l-4 border-primary">
        <div class="p-6">
            <h2 class="text-lg font-bold text-gray-800 mb-2">Crear Copia de Seguridad</h2>
            <p class="text-gray-600 mb-4 text-sm">Descarga la base de datos completa con todo tu inventario, ventas y configuraciones actuales.</p>
            <a href="backup.php" class="inline-flex items-center gap-2 bg-primary hover:bg-blue-700 text-white font-bold py-2 px-6 rounded shadow transition">
                <i class="fa-solid fa-download"></i> Descargar Localmente (.db)
            </a>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-md overflow-hidden border-l-4 border-orange-500">
        <div class="p-6">
            <h2 class="text-lg font-bold text-gray-800 mb-2 text-red-600"><i class="fa-solid fa-triangle-exclamation mr-2"></i>Restaurar Copia</h2>
            <p class="text-gray-600 mb-4 text-sm font-medium">ADVERTENCIA: Restaurar reemplazará TODA la información actual por el archivo subido. Esta acción no se puede deshacer de manera convencional.</p>
            
            <form action="" method="POST" enctype="multipart/form-data" class="bg-orange-50 p-4 rounded border border-orange-200">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <div class="mb-4">
                    <label class="block text-sm font-bold text-gray-800 mb-2">Seleccione archivo .db</label>
                    <input type="file" name="backup_file" accept=".db,.sqlite" required class="w-full bg-white border border-gray-300 rounded px-3 py-2 text-sm">
                </div>
                <button type="submit" onclick="return confirm('ATENCIÓN: Se sobrescribirá toda la base de datos. ¿Deseas continuar?')" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-6 rounded shadow transition items-center gap-2 flex">
                    <i class="fa-solid fa-upload"></i> Confirmar y Restaurar
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
