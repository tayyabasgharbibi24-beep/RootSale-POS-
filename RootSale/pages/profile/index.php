<?php
require_once __DIR__ . '/../../includes/header.php';

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Check and insert profile row if it doesn't exist
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_profiles WHERE user_id = ?");
    $stmt->execute([$user_id]);
    if ($stmt->fetchColumn() == 0) {
        $pdo->prepare("INSERT INTO user_profiles (user_id) VALUES (?)")->execute([$user_id]);
    }
} catch(Exception $e) {} // Ignore if missing table from partial migration

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);

        try {
            $pdo->beginTransaction();
            // Update users table
            $stmt1 = $pdo->prepare("UPDATE users SET full_name = ?, email = ? WHERE id = ?");
            $stmt1->execute([$full_name, $email, $user_id]);

            // Update user_profiles table if exists
            try {
                $stmt2 = $pdo->prepare("UPDATE user_profiles SET email = ?, phone = ? WHERE user_id = ?");
                $stmt2->execute([$email, $phone, $user_id]);
            } catch(PDOException $e) {}
            
            $pdo->commit();
            $success = "Perfil actualizado correctamente.";
            $_SESSION['user_name'] = $full_name;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Error al actualizar perfil: " . $e->getMessage();
        }
    } elseif (isset($_POST['update_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if (password_verify($current_password, $user['password'])) {
            if ($new_password === $confirm_password) {
                if (strlen($new_password) >= 6) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    if ($stmt->execute([$hashed_password, $user_id])) {
                        try {
                            $stmt2 = $pdo->prepare("UPDATE user_profiles SET last_password_change = CURRENT_TIMESTAMP WHERE user_id = ?");
                            $stmt2->execute([$user_id]);
                        } catch(Exception $e) {}
                        $success = "Contraseña actualizada correctamente.";
                    } else {
                        $error = "Error al actualizar contraseña.";
                    }
                } else {
                    $error = "La nueva contraseña debe tener al menos 6 caracteres.";
                }
            } else {
                $error = "Las contraseñas nuevas no coinciden.";
            }
        } else {
            $error = "La contraseña actual es incorrecta.";
        }
    }
}

// Fetch current user data
try {
    $stmt = $pdo->prepare("
        SELECT u.full_name, u.email as auth_email, p.phone, p.email as profile_email 
        FROM users u 
        LEFT JOIN user_profiles p ON u.id = p.user_id 
        WHERE u.id = ?
    ");
    $stmt->execute([$user_id]);
    $userData = $stmt->fetch();
} catch (Exception $e) {
    // Fallback if user_profiles table doesn't exist yet
    $stmt = $pdo->prepare("SELECT full_name, email as auth_email FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $userData = $stmt->fetch();
    $userData['profile_email'] = '';
    $userData['phone'] = '';
}

$current_name = $userData['full_name'] ?? '';
$current_email = $userData['profile_email'] ? $userData['profile_email'] : ($userData['auth_email'] ?? '');
$current_phone = $userData['phone'] ?? '';

?>

<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Mi Perfil</h1>
        <p class="text-gray-600">Administra tu información personal y contraseña.</p>
    </div>

    <?php if ($success): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <!-- Profile Info Form -->
        <div class="bg-white rounded-lg shadow-md p-6 border-t-4 border-primary">
            <h2 class="text-xl font-bold mb-4 flex items-center gap-2"><i class="fa-solid fa-user text-primary"></i> Información Personal</h2>
            <form method="POST">
                <input type="hidden" name="update_profile" value="1">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Nombre Completo</label>
                    <input type="text" name="full_name" value="<?= htmlspecialchars($current_name) ?>" required class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:border-primary">
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Correo Electrónico</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($current_email) ?>" required class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:border-primary">
                </div>
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Teléfono</label>
                    <input type="text" name="phone" value="<?= htmlspecialchars($current_phone) ?>" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:border-primary">
                </div>
                <button type="submit" class="w-full bg-primary hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition">
                    Guardar Cambios
                </button>
            </form>
        </div>

        <!-- Password Change Form -->
        <div class="bg-white rounded-lg shadow-md p-6 border-t-4 border-gray-800">
            <h2 class="text-xl font-bold mb-4 flex items-center gap-2"><i class="fa-solid fa-lock text-gray-800"></i> Cambiar Contraseña</h2>
            <form method="POST">
                <input type="hidden" name="update_password" value="1">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Contraseña Actual</label>
                    <input type="password" name="current_password" required class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:border-primary">
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Nueva Contraseña</label>
                    <input type="password" name="new_password" required class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:border-primary">
                </div>
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Confirmar Nueva Contraseña</label>
                    <input type="password" name="confirm_password" required class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:border-primary">
                </div>
                <button type="submit" class="w-full bg-gray-800 hover:bg-gray-900 text-white font-bold py-2 px-4 rounded transition">
                    Actualizar Contraseña
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
