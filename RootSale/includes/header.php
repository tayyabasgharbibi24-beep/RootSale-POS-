<?php
// includes/header.php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

// Requerir inicio de sesión solo si no está en la página de login
$current_page = basename($_SERVER['PHP_SELF']);
if ($current_page !== 'login.php') {
    requireLogin();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RootSale POS</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1d4ed8', // blue-700
                        secondary: '#eff6ff', // blue-50
                    }
                }
            }
        }
    </script>
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <!-- QuaggaJS for Barcodes -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/quagga/0.12.1/quagga.min.js"></script>
</head>
<body class="bg-gray-100 flex h-screen overflow-hidden text-gray-800">

<?php if (isLoggedIn()): ?>
    <!-- Barra lateral para Escritorio -->
    <aside class="hidden md:flex flex-col w-64 bg-primary text-white h-full shadow-lg">
        <div class="p-6 flex flex-col items-center justify-center border-b border-blue-600">
            <?php if(!empty($globalSettings['logo_path'])): ?>
                <img src="<?= BASE_URL . $globalSettings['logo_path'] ?>" alt="Logo" class="max-h-16 mb-2 bg-white rounded p-1">
            <?php else: ?>
                <i class="fa-solid fa-store text-3xl mb-2"></i>
            <?php endif; ?>
            <h1 class="text-2xl font-bold tracking-wider"><?= htmlspecialchars($globalSettings['shop_name'] ?? 'RootSale') ?></h1>
        </div>
        <nav class="flex-1 overflow-y-auto py-4">
            <ul class="space-y-1">
                <li>
                    <a href="<?php echo BASE_URL; ?>/dashboard.php" class="flex items-center px-6 py-3 hover:bg-blue-600 transition <?= $current_page == 'dashboard.php' ? 'bg-blue-600 border-l-4 border-white' : '' ?>">
                        <i class="fa-solid fa-chart-line w-6"></i>
                        <span>Panel de Control</span>
                    </a>
                </li>
                <?php if (isAdmin() || isSeller()): ?>
                <li>
                    <a href="<?php echo BASE_URL; ?>/pages/billing/index.php" class="flex items-center px-6 py-3 hover:bg-blue-600 transition <?= strpos($_SERVER['REQUEST_URI'], '/billing/') !== false ? 'bg-blue-600 border-l-4 border-white' : '' ?>">
                        <i class="fa-solid fa-cash-register w-6"></i>
                        <span>Ventas (POS)</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo BASE_URL; ?>/pages/returns/index.php" class="flex items-center px-6 py-3 hover:bg-blue-600 transition <?= strpos($_SERVER['REQUEST_URI'], '/returns/') !== false ? 'bg-blue-600 border-l-4 border-white' : '' ?>">
                        <i class="fa-solid fa-undo rotate-90 w-6"></i>
                        <span>Devoluciones</span>
                    </a>
                </li>
                <?php endif; ?>
                <?php if (isAdmin() || isInventoryManager()): ?>
                <li>
                    <a href="<?php echo BASE_URL; ?>/pages/products/index.php" class="flex items-center px-6 py-3 hover:bg-blue-600 transition <?= strpos($_SERVER['REQUEST_URI'], '/products/') !== false ? 'bg-blue-600 border-l-4 border-white' : '' ?>">
                        <i class="fa-solid fa-box w-6"></i>
                        <span>Productos</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo BASE_URL; ?>/pages/categories/index.php" class="flex items-center px-6 py-3 hover:bg-blue-600 transition <?= strpos($_SERVER['REQUEST_URI'], '/categories/') !== false ? 'bg-blue-600 border-l-4 border-white' : '' ?>">
                        <i class="fa-solid fa-tags w-6"></i>
                        <span>Categorías</span>
                    </a>
                </li>
                <?php endif; ?>
                <?php if (isAdmin()): ?>
                <li>
                    <a href="<?php echo BASE_URL; ?>/pages/customers/index.php" class="flex items-center px-6 py-3 hover:bg-blue-600 transition <?= strpos($_SERVER['REQUEST_URI'], '/customers/') !== false ? 'bg-blue-600 border-l-4 border-white' : '' ?>">
                        <i class="fa-solid fa-users w-6"></i>
                        <span>Clientes</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo BASE_URL; ?>/pages/reports/index.php" class="flex items-center px-6 py-3 hover:bg-blue-600 transition <?= strpos($_SERVER['REQUEST_URI'], '/reports/') !== false ? 'bg-blue-600 border-l-4 border-white' : '' ?>">
                        <i class="fa-solid fa-file-invoice-dollar w-6"></i>
                        <span>Reportes</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo BASE_URL; ?>/pages/users/index.php" class="flex items-center px-6 py-3 hover:bg-blue-600 transition <?= strpos($_SERVER['REQUEST_URI'], '/users/') !== false ? 'bg-blue-600 border-l-4 border-white' : '' ?>">
                        <i class="fa-solid fa-users-cog w-6"></i>
                        <span>Usuarios</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo BASE_URL; ?>/pages/settings/shop.php" class="flex items-center px-6 py-3 hover:bg-blue-600 transition <?= strpos($_SERVER['REQUEST_URI'], '/settings/shop') !== false ? 'bg-blue-600 border-l-4 border-white' : '' ?>">
                        <i class="fa-solid fa-cog w-6"></i>
                        <span>Configuración</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo BASE_URL; ?>/pages/settings/hardware.php" class="flex items-center px-6 py-3 hover:bg-blue-600 transition <?= strpos($_SERVER['REQUEST_URI'], '/settings/hardware') !== false ? 'bg-blue-600 border-l-4 border-white' : '' ?>">
                        <i class="fa-solid fa-print w-6"></i>
                        <span>Hardware & Pantalla</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo BASE_URL; ?>/pages/settings/restore.php" class="flex items-center px-6 py-3 hover:bg-blue-600 transition <?= strpos($_SERVER['REQUEST_URI'], '/settings/restore') !== false ? 'bg-blue-600 border-l-4 border-white' : '' ?>">
                        <i class="fa-solid fa-database w-6"></i>
                        <span>Respaldos (Backup)</span>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
        <div class="p-4 border-t border-blue-600">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-full bg-white text-primary flex items-center justify-center font-bold">
                    <?= substr($_SESSION['user_name'] ?? 'U', 0, 1) ?>
                </div>
                <div>
                    <p class="text-sm font-semibold truncate"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Usuario') ?></p>
                    <p class="text-xs text-blue-200 capitalize"><?= htmlspecialchars($_SESSION['user_role'] ?? 'cashier') ?></p>
                </div>
            </div>
            
            <a href="<?php echo BASE_URL; ?>/pages/profile/index.php" class="mt-4 block w-full text-center bg-blue-500 hover:bg-blue-600 text-white py-2 rounded transition">
                <i class="fa-solid fa-user mr-2"></i> Mi Perfil
            </a>
            
            <a href="<?php echo BASE_URL; ?>/logout.php" class="mt-2 block w-full text-center bg-red-500 hover:bg-red-600 text-white py-2 rounded transition">
                <i class="fa-solid fa-sign-out-alt mr-2"></i> Salir
            </a>
        </div>
    </aside>

    <!-- Área de Contenido Principal -->
    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        
        <!-- El encabezado móvil se solicita específicamente que esté oculto -->
        <header class="hidden">
            <div class="flex items-center">
                <i class="fa-solid fa-store text-xl mr-2"></i>
                <h1 class="font-bold">RootSale</h1>
            </div>
            <button id="mobileMenuBtn" class="text-2xl focus:outline-none">
                <i class="fa-solid fa-bars"></i>
            </button>
        </header>

        <!-- Contenido Principal -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 relative">
            <div class="container mx-auto px-4 py-6 pb-24 md:pb-6">
<?php endif; ?>
