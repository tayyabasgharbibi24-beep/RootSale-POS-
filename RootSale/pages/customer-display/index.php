<?php
// pages/customer-display/index.php
session_start();
require_once __DIR__ . '/../../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/login.php");
    exit;
}

// Fetch display settings
$disp = ['welcome_text' => 'Bienvenido', 'show_cart' => 1];
try {
    $d = $pdo->query("SELECT * FROM customer_display_settings LIMIT 1")->fetch();
    if($d) { $disp = $d; }
} catch(Exception $e) {}

$shop = $pdo->query("SELECT shop_name, logo_path FROM shop_settings LIMIT 1")->fetch();

// Terminal Selection Mode
$cashier_id = $_GET['cashier_id'] ?? null;
if (!$cashier_id) {
    $cashiers = $pdo->query("SELECT id, full_name, username FROM users WHERE role IN ('admin', 'seller') AND is_active = 1")->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pantalla Cliente - <?= htmlspecialchars($shop['shop_name'] ?? 'RootSale') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1d4ed8', // Same primary color
                        secondary: '#eff6ff',
                        displayBg: '#ffffff',
                        displayCard: '#f8fafc',
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800;900&display=swap');
        body { font-family: 'Outfit', sans-serif; overflow: hidden; }
        .fade-in { animation: fadeIn 0.3s ease-in; }
        .slide-up { animation: slideUp 0.3s ease-out; }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        /* Micro-animations */
        .cart-item {
            transition: all 0.3s ease;
        }
        .cart-item-enter {
            animation: bounceInLeft 0.5s cubic-bezier(0.215, 0.610, 0.355, 1.000);
        }
        
        @keyframes bounceInLeft {
            0% { opacity: 0; transform: translate3d(-3000px, 0, 0); }
            60% { opacity: 1; transform: translate3d(25px, 0, 0); }
            75% { transform: translate3d(-10px, 0, 0); }
            90% { transform: translate3d(5px, 0, 0); }
            100% { transform: none; }
        }
    </style>
</head>
<body class="bg-gray-100 flex flex-col h-screen select-none">

    <!-- Header -->
    <?php if ($cashier_id): ?>
    <header class="bg-primary text-white p-6 shadow-md flex justify-between items-center z-10 relative">
        <div class="flex items-center gap-4">
            <?php if(!empty($shop['logo_path'])): ?>
                <img src="<?= BASE_URL . $shop['logo_path'] ?>" class="h-12 bg-white rounded p-1 object-contain">
            <?php else: ?>
                <i class="fa-solid fa-store text-4xl"></i>
            <?php endif; ?>
            <h1 class="text-3xl font-black tracking-tight"><?= htmlspecialchars($shop['shop_name'] ?? 'RootSale') ?></h1>
        </div>
        <div class="text-right flex items-center gap-4">
            <h2 class="text-2xl font-semibold opacity-90"><?= htmlspecialchars($disp['welcome_text']) ?></h2>
            <a href="<?= BASE_URL ?>/logout.php" class="bg-white text-primary hover:bg-blue-50 px-4 py-2 rounded shadow font-bold text-sm transition">
                <i class="fa-solid fa-sign-out-alt mr-1"></i> Salir
            </a>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 flex overflow-hidden relative">
        
        <?php if ($disp['show_cart']): ?>
        <!-- Cart Section -->
        <div class="w-full md:w-1/2 lg:w-2/5 xl:w-[35%] bg-white shadow-xl z-20 flex flex-col border-r border-gray-200">
            <div class="bg-gray-50 p-4 border-b border-gray-200">
                <h3 class="text-xl font-bold text-gray-800 flex items-center gap-2"><i class="fa-solid fa-shopping-basket text-primary"></i> Su Compra</h3>
            </div>
            
            <div id="cartItemsContainer" class="flex-1 overflow-y-auto p-4 space-y-3 bg-white">
                <!-- Idle state initially -->
                <div id="idleState" class="h-full flex flex-col items-center justify-center text-gray-400 opacity-60">
                    <i class="fa-solid fa-cash-register text-8xl mb-6 text-gray-300"></i>
                    <p class="text-xl font-medium">La caja está abierta</p>
                    <p class="text-sm mt-2">Esperando productos...</p>
                </div>
                <ul id="itemsList" class="divide-y divide-gray-100 hidden"></ul>
            </div>
            
            <div class="p-6 bg-gray-50 border-t border-gray-200 shadow-[0_-10px_20px_-15px_rgba(0,0,0,0.1)]">
                <div class="flex justify-between items-center mb-2">
                    <span class="text-lg text-gray-600 font-medium">Subtotal</span>
                    <span id="subtotalDisplay" class="text-xl font-bold text-gray-800">0.00 €</span>
                </div>
                <div class="flex justify-between items-center mb-3">
                    <span class="text-md text-gray-500">Descuento</span>
                    <span id="discountDisplay" class="text-lg font-bold text-green-600">-0.00 €</span>
                </div>
                <div class="border-t-2 border-dashed border-gray-300 pt-4 mt-2 mb-2 flex justify-between items-end">
                    <span class="text-2xl font-black text-gray-700 uppercase tracking-wide">Total a Pagar</span>
                    <span id="totalDisplay" class="text-5xl font-black text-primary transition-all duration-300">0.00 €</span>
                </div>
            </div>
        </div>
        
        <!-- Media/Ads Section -->
        <div class="hidden md:flex flex-1 bg-displayCard flex-col items-center justify-center p-12 relative overflow-hidden">
            <!-- Background pattern for premium feel -->
            <div class="absolute inset-0 opacity-5 pointer-events-none" style="background-image: radial-gradient(#1d4ed8 1px, transparent 1px); background-size: 30px 30px;"></div>
            
            <div id="adArea" class="text-center z-10 slide-up max-w-2xl">
                <div class="relative w-48 h-48 bg-blue-100 text-primary rounded-full flex items-center justify-center mx-auto mb-8 shadow-inner shadow-blue-200">
                    <i class="fa-solid fa-gift text-8xl transform -rotate-12 hover:rotate-12 transition duration-500 animate-bounce"></i>
                    <!-- Sparkles -->
                    <i class="fa-solid fa-sparkles text-yellow-400 absolute top-4 right-4 text-3xl animate-pulse"></i>
                    <i class="fa-solid fa-star text-yellow-400 absolute bottom-4 left-4 text-xl animate-ping"></i>
                </div>
                <h2 class="text-6xl font-black text-gray-800 mb-6 leading-tight bg-clip-text text-transparent bg-gradient-to-r from-primary to-blue-400 animate-pulse">
                    ¡Gracias!
                </h2>
                <h3 class="text-3xl font-bold text-gray-700 mb-4">Por visitarnos</h3>
                <p class="text-xl text-gray-500 mb-10 font-light">Escanee su código QR en el recibo para ver la versión digital.</p>
            </div>
        </div>
        
        <?php else: ?>
        <!-- Full Screen Media If Cart is Hide -->
        <div class="flex-1 bg-displayCard flex flex-col items-center justify-center p-12 relative overflow-hidden bg-gradient-to-br from-white to-blue-50">
            <div class="relative w-64 h-64 bg-blue-100 text-primary rounded-full flex items-center justify-center mx-auto mb-12 shadow-2xl shadow-blue-200/50">
                <i class="fa-solid fa-store text-9xl transform transition duration-500 animate-bounce"></i>
            </div>
            <h2 class="text-8xl font-black mb-6 bg-clip-text text-transparent bg-gradient-to-r from-primary to-blue-500 animate-pulse">¡Gracias!</h2>
            <p class="text-4xl text-gray-600 font-light">La caja está lista para su compra.</p>
        </div>
        <?php endif; ?>
    </main>

    <script>
        const currencySymbol = '<?= CURRENCY_SYMBOL ?>';
        const cashierKey = 'pos_state_<?= htmlspecialchars($cashier_id) ?>';
        
        function formatMoney(num) {
            return num.toFixed(2) + ' ' + currencySymbol;
        }

        function renderCart(data) {
            const idle = document.getElementById('idleState');
            const list = document.getElementById('itemsList');
            
            if (!data || !data.items || data.items.length === 0) {
                idle.classList.remove('hidden');
                list.classList.add('hidden');
                document.getElementById('subtotalDisplay').innerText = formatMoney(0);
                document.getElementById('discountDisplay').innerText = '- ' + formatMoney(0);
                document.getElementById('totalDisplay').innerText = formatMoney(0);
                return;
            }

            idle.classList.add('hidden');
            list.classList.remove('hidden');
            
            // Build items
            list.innerHTML = '';
            let isNewItemAdded = false;
            
            // Note: In a real advanced app we would diff the cart, but for this simpler vanilla JS approach 
            // we will just re-render and play animation on the very last item if it just appeared
            
            data.items.forEach((item, index) => {
                const li = document.createElement('li');
                li.className = 'py-4 flex justify-between items-center cart-item';
                
                // Add enter animation to the most recenly modified items if we want, 
                // but for simplicity, we let CSS transitions handle resizing
                
                li.innerHTML = `
                    <div class="flex-1 pr-4">
                        <h4 class="text-xl font-bold text-gray-800 leading-tight mb-1">${item.name}</h4>
                        <p class="text-sm text-gray-500">${formatMoney(item.price)} C/U</p>
                    </div>
                    <div class="flex items-center gap-4 text-right">
                        <div class="bg-gray-100 rounded px-3 py-1 font-bold text-gray-600 text-lg">x${item.qty}</div>
                        <div class="w-24 font-black text-xl text-primary">${formatMoney(item.price * item.qty)}</div>
                    </div>
                `;
                list.appendChild(li);
            });
            
            // Ensure the list scrolls to the bottom
            const container = document.getElementById('cartItemsContainer');
            container.scrollTop = container.scrollHeight;

            // Totals
            let subtotal = 0;
            data.items.forEach(i => subtotal += (i.price * i.qty));
            let discountAmount = 0;
            
            if (data.discount_type === 'percentage') {
                discountAmount = subtotal * ((data.discount_value || 0) / 100);
            } else {
                discountAmount = data.discount_value || 0;
            }
            if (discountAmount > subtotal) discountAmount = subtotal;
            
            const total = subtotal - discountAmount;
            
            document.getElementById('subtotalDisplay').innerText = formatMoney(subtotal);
            document.getElementById('discountDisplay').innerText = '- ' + formatMoney(discountAmount);
            
            const totalEl = document.getElementById('totalDisplay');
            if(totalEl.innerText !== formatMoney(total)) {
                totalEl.classList.remove('text-primary');
                totalEl.classList.add('text-green-500');
                totalEl.style.transform = 'scale(1.1)';
                setTimeout(() => {
                    totalEl.classList.remove('text-green-500');
                    totalEl.classList.add('text-primary');
                    totalEl.style.transform = 'scale(1)';
                }, 300);
            }
            totalEl.innerText = formatMoney(total);
        }

        // Network Polling
        async function fetchState() {
            try {
                const res = await fetch(`get-state.php?cashier=<?= htmlspecialchars($cashier_id) ?>`);
                if(res.ok) {
                    const data = await res.json();
                    renderCart(data);
                }
            } catch(e) {
                console.error("Network sync error", e);
            }
        }
        
        // Start polling every 1 second
        setInterval(fetchState, 1000);
        
        // Initial fetch
        fetchState();

    </script>
    
    <?php else: // IF NO CASHIER ID SELECTED ?>
    
    <div class="flex-1 flex items-center justify-center bg-gray-100 p-6">
        <div class="bg-white rounded-xl shadow-2xl overflow-hidden w-full max-w-2xl text-center flex flex-col">
            <div class="bg-primary p-8 text-white">
                <i class="fa-solid fa-desktop text-6xl mb-4"></i>
                <h2 class="text-3xl font-black tracking-wide">Vincular Terminal</h2>
                <p class="text-blue-100 mt-2 text-lg">Seleccione el Cajero asignado a esta pantalla</p>
            </div>
            <div class="p-8">
                <p class="text-gray-600 mb-6 text-lg">Esta pantalla solo mostrará las ventas del usuario seleccionado para evitar cruzar información.</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <?php foreach($cashiers as $c): ?>
                    <a href="?cashier_id=<?= $c['id'] ?>" class="flex items-center p-4 border-2 border-gray-200 rounded-lg hover:border-primary hover:bg-blue-50 transition text-left group">
                        <div class="w-12 h-12 rounded-full bg-blue-100 text-primary flex items-center justify-center font-bold text-xl mr-4 group-hover:bg-primary group-hover:text-white transition">
                            <?= strtoupper(substr($c['full_name'], 0, 1)) ?>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-800 text-lg"><?= htmlspecialchars($c['full_name']) ?></h3>
                            <p class="text-sm text-gray-500">@<?= htmlspecialchars($c['username']) ?></p>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
                
                <?php if(empty($cashiers)): ?>
                    <div class="p-6 bg-red-50 text-red-600 rounded mb-4">
                        No hay cajeros o administradores activos. Add usuarios primero.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php endif; ?>
</body>
</html>
