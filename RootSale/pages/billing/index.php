<?php
// pages/billing/index.php
require_once __DIR__ . '/../../includes/header.php';
requireSellerOrAdmin();

// Prepare Categories
$cats = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();

$inv = null;
try {
    $inv = $pdo->query("SELECT receipt_type FROM invoice_settings LIMIT 1")->fetch();
} catch (Exception $e) {
}
$receipt_type = $inv ? $inv['receipt_type'] : 'thermal';
?>

<!-- Scanner Modal -->
<div id="scannerModal" class="fixed inset-0 bg-black bg-opacity-75 z-50 hidden flex flex-col pt-10 px-4">
    <div class="flex justify-between items-center mb-4 max-w-lg mx-auto w-full">
        <h2 class="text-white text-xl font-bold">Escanear Código de Barras</h2>
        <button onclick="stopScanner()" class="text-white text-3xl"><i class="fa-solid fa-times"></i></button>
    </div>
    <div class="bg-black w-full max-w-lg mx-auto rounded overflow-hidden relative shadow-2xl" style="height: 300px;">
        <div id="interactive" class="viewport w-full h-full"></div>
        <div
            class="absolute inset-0 border-4 border-primary bg-transparent z-10 opacity-50 m-12 pointer-events-none rounded">
        </div>
    </div>
    <p class="text-center text-white mt-4 max-w-lg mx-auto text-sm">Alinee el código de barras dentro del marco.</p>
</div>

<div class="flex flex-col lg:flex-row gap-6 lg:h-[calc(100vh-140px)] pb-20 lg:pb-0">

    <!-- Left Side: Products Grid -->
    <div class="flex-1 flex flex-col bg-white rounded-lg shadow-md overflow-hidden h-[60vh] lg:h-full">
        <!-- Search and Filter Bar -->
        <div class="p-4 border-b border-gray-200 bg-gray-50 flex flex-col sm:flex-row gap-3">
            <div class="relative flex-1">
                <input type="text" id="searchInput" placeholder="Buscar producto o escanear código..."
                    class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary shadow-sm text-lg">
                <i class="fa-solid fa-search absolute left-4 top-4 text-gray-400"></i>
            </div>
            <button onclick="startScanner()"
                class="bg-gray-800 hover:bg-gray-900 text-white px-5 py-3 rounded-lg flex items-center gap-2 shadow transition whitespace-nowrap">
                <i class="fa-solid fa-camera"></i> Escanear
            </button>
        </div>

        <!-- Category Chips -->
        <div class="p-3 border-b border-gray-100 flex gap-2 overflow-x-auto no-scrollbar">
            <button onclick="filterCategory('')"
                class="cat-btn bg-primary text-white px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap transition-colors"
                data-cat="">Todos</button>
            <?php foreach ($cats as $c): ?>
                <button onclick="filterCategory('<?= $c['id'] ?>')"
                    class="cat-btn bg-gray-100 text-gray-700 hover:bg-gray-200 px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap transition-colors"
                    data-cat="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></button>
            <?php endforeach; ?>
        </div>

        <!-- Products Grid Container -->
        <div class="flex-1 overflow-y-auto p-4 bg-gray-50" id="productsGrid">
            <div class="flex items-center justify-center h-full text-gray-400">
                <i class="fa-solid fa-spinner fa-spin text-4xl mr-3"></i> Cargando productos...
            </div>
        </div>
    </div>

    <!-- Right Side: Cart -->
    <div class="w-full lg:w-96 bg-white rounded-lg shadow-md flex flex-col h-auto lg:h-full border-t-8 border-primary">

        <div class="p-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
            <h2 class="text-xl font-bold text-gray-800 flex items-center gap-2"><i
                    class="fa-solid fa-shopping-cart text-primary"></i> Venta Actual</h2>
            <button onclick="clearCart()" class="text-red-500 hover:text-red-700 text-sm font-medium"><i
                    class="fa-solid fa-trash-alt mr-1"></i>Vacíar</button>
        </div>

        <!-- Cart Items Area -->
        <div class="max-h-[40vh] overflow-y-auto lg:max-h-none lg:flex-1 p-2" id="cartItems">
            <div class="flex flex-col items-center justify-center h-full text-gray-400 opacity-50">
                <i class="fa-solid fa-cart-arrow-down text-6xl mb-4"></i>
                <p>Carrito vacío</p>
                <p class="text-sm">Añada productos para comenzar</p>
            </div>
        </div>

        <!-- Cart Summary -->
        <div class="border-t border-gray-200 bg-gray-50 p-4">
            <div class="flex justify-between items-center mb-2">
                <span class="text-gray-600">Subtotal:</span>
                <span class="font-bold text-gray-900" id="cartSubtotal">0.00 €</span>
            </div>
            <div class="flex justify-between items-center mb-2">
                <span class="text-gray-600 cursor-pointer text-sm text-primary hover:underline"
                    onclick="toggleDiscount()"><i class="fa-solid fa-percent mr-1"></i>Añadir Descuento</span>
                <span class="text-green-600 font-bold" id="cartDiscount">-0.00 €</span>
            </div>

            <div id="discountBox" class="hidden mb-3 p-3 bg-white border border-gray-200 rounded text-sm">
                <label class="block text-gray-700 mb-1 font-medium">Tipo de descuento:</label>
                <select id="discountType" onchange="updateCartTotal()"
                    class="w-full mb-2 border-gray-300 rounded focus:ring-primary focus:border-primary px-2 py-1">
                    <option value="amount">Cantidad Fija (€)</option>
                    <option value="percentage">Porcentaje (%)</option>
                </select>
                <label class="block text-gray-700 mb-1 font-medium">Valor:</label>
                <input type="number" id="discountValue" value="0" min="0" step="0.01" onchange="updateCartTotal()"
                    class="w-full border-gray-300 rounded focus:ring-primary focus:border-primary px-2 py-1">
            </div>

            <div class="flex justify-between items-center mb-4">
                <span class="text-gray-600">Impuestos (Incluidos):</span>
                <span class="font-medium text-gray-500" id="cartTax">0.00 €</span>
            </div>
            <div class="border-t border-gray-300 pt-3 mb-4 flex justify-between items-end">
                <span class="text-lg font-bold text-gray-800">Total:</span>
                <span class="text-3xl font-black text-primary" id="cartTotal">0.00 €</span>
            </div>

            <button onclick="checkout()" id="btnCheckout"
                class="w-full bg-green-500 hover:bg-green-600 text-white font-bold py-4 rounded-lg shadow-lg text-lg flex items-center justify-center gap-2 transition-transform transform active:scale-95 disabled:opacity-50">
                <i class="fa-solid fa-wallet"></i> PROCEDER AL PAGO
            </button>
        </div>
    </div>
</div>

<!-- Checkout Modal -->
<div id="checkoutModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md overflow-hidden">
        <div class="bg-primary p-4 flex justify-between items-center text-white">
            <h3 class="font-bold text-lg"><i class="fa-solid fa-money-bill-wave mr-2"></i> Procesar Pago</h3>
            <button onclick="document.getElementById('checkoutModal').classList.add('hidden')"
                class="text-white hover:text-gray-200"><i class="fa-solid fa-times text-xl"></i></button>
        </div>

        <form id="checkoutForm" action="save-bill.php" method="POST" class="p-6">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            <input type="hidden" name="cart_data" id="checkoutCartData">

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Total a Cobrar</label>
                <input type="text" id="checkoutTotalDisplay" readonly
                    class="w-full bg-gray-100 text-right text-2xl font-black text-primary border border-gray-300 rounded px-4 py-3">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Método de Pago</label>
                <select name="payment_method" id="paymentMethod" onchange="toggleCashFields()" required
                    class="w-full border border-gray-300 rounded px-4 py-3 focus:ring-primary focus:border-primary font-medium">
                    <option value="Efectivo">Efectivo 💵</option>
                    <option value="Tarjeta">Tarjeta de Crédito/Débito 💳</option>
                    <option value="UPI">Transferencia (Bizum/UPI) 📱</option>
                </select>
            </div>

            <div id="cashFields" class="mb-4 bg-gray-50 p-4 border border-gray-200 rounded">
                <label class="block text-sm font-medium text-gray-700 mb-1">Efectivo Recibido</label>
                <div class="flex">
                    <span
                        class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-100 font-bold text-gray-600">€</span>
                    <input type="number" step="0.01" min="0" name="paid_amount" id="paidAmount"
                        oninput="calculateChange()"
                        class="w-full rounded-r-md border border-gray-300 px-4 py-3 text-right text-xl focus:ring-primary focus:border-primary font-bold">
                </div>

                <div class="flex justify-between items-center mt-3 pt-3 border-t border-gray-200">
                    <span class="text-gray-600 font-medium">Cambio a devolver:</span>
                    <span id="changeAmountDisplay" class="text-xl font-bold text-red-500">0.00 €</span>
                </div>
            </div>

            <?php if ($receipt_type === 'a4'): ?>
                <div class="mb-6 bg-blue-50 p-4 border border-blue-200 rounded">
                    <h4 class="font-bold text-gray-700 mb-2 border-b border-blue-200 pb-1">Detalles del Cliente (Factura A4)
                    </h4>
                    <div class="grid grid-cols-2 gap-3 mb-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600">Nombre Completo *</label>
                            <input type="text" name="customer_name" required
                                class="w-full border-gray-300 rounded px-3 py-1 text-sm focus:ring-primary">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600">Teléfono (Recomendado)</label>
                            <input type="text" name="customer_phone"
                                class="w-full border-gray-300 rounded px-3 py-1 text-sm focus:ring-primary">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3 mb-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600">Email (Recomendado)</label>
                            <input type="email" name="customer_email"
                                class="w-full border-gray-300 rounded px-3 py-1 text-sm focus:ring-primary">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600">NIF / GST</label>
                            <input type="text" name="customer_gst"
                                class="w-full border-gray-300 rounded px-3 py-1 text-sm focus:ring-primary">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600">Dirección</label>
                        <input type="text" name="customer_address"
                            class="w-full border-gray-300 rounded px-3 py-1 text-sm focus:ring-primary">
                    </div>
                </div>
            <?php else: ?>
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cliente (Opcional)</label>
                    <select name="customer_id" id="customerId"
                        class="w-full border border-gray-300 rounded px-4 py-2 focus:ring-primary focus:border-primary">
                        <option value="">Consumidor Final</option>
                        <?php
                        $custs = $pdo->query("SELECT id, name, phone FROM customers ORDER BY name ASC")->fetchAll();
                        foreach ($custs as $cu) {
                            echo "<option value='{$cu['id']}'>" . htmlspecialchars($cu['name']) . " - " . htmlspecialchars($cu['phone']) . "</option>";
                        }
                        ?>
                    </select>
                </div>
            <?php endif; ?>

            <button type="submit" id="finalPayBtn" disabled
                class="w-full bg-green-500 hover:bg-green-600 text-white font-bold py-3 rounded-lg shadow disabled:opacity-50 transition-colors">
                CONFIRMAR PAGO <i class="fa-solid fa-check ml-2"></i>
            </button>
        </form>
    </div>
</div>

<script>
    // Application State
    let cart = [];
    let allProducts = [];
    let currentCategory = '';
    let currentTotal = 0;

    // On load
    document.addEventListener('DOMContentLoaded', () => {
        loadProducts();
        updateCartUI();

        document.getElementById('searchInput').addEventListener('input', (e) => {
            renderProductsGrid(e.target.value);
        });

        document.getElementById('searchInput').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                const val = e.target.value.trim();
                if (val) {
                    // Check if it's an exact barcode match
                    const prod = allProducts.find(p => p.barcode === val);
                    if (prod) {
                        addToCart(prod);
                        e.target.value = '';
                        renderProductsGrid();
                        showToast('Producto escaneado y añadido', 'success');
                    }
                }
            }
        });

        document.getElementById('paidAmount').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                if (!document.getElementById('finalPayBtn').disabled) {
                    document.getElementById('checkoutForm').submit();
                }
            }
        });
    });

    async function loadProducts() {
        try {
            const response = await fetch('../products/get-product.php?search=');
            const json = await response.json();
            if (json.success) {
                allProducts = json.data;
                renderProductsGrid();
            }
        } catch (err) {
            console.error(err);
            showToast('Error cargando catálogo', 'error');
        }
    }

    function filterCategory(id) {
        currentCategory = id;
        document.querySelectorAll('.cat-btn').forEach(b => {
            b.classList.remove('bg-primary', 'text-white');
            b.classList.add('bg-gray-100', 'text-gray-700');
        });
        const activeBtn = document.querySelector(`.cat-btn[data-cat="${id}"]`);
        if (activeBtn) {
            activeBtn.classList.remove('bg-gray-100', 'text-gray-700');
            activeBtn.classList.add('bg-primary', 'text-white');
        }
        renderProductsGrid(document.getElementById('searchInput').value);
    }

    function renderProductsGrid(search = '') {
        const grid = document.getElementById('productsGrid');
        grid.innerHTML = '';
        search = search.toLowerCase();

        const filtered = allProducts.filter(p => {
            const matchSearch = p.name.toLowerCase().includes(search) || p.barcode.includes(search) || (p.brand && p.brand.toLowerCase().includes(search));
            const matchCat = currentCategory ? p.category_id == currentCategory : true;
            return matchSearch && matchCat;
        });

        if (filtered.length === 0) {
            grid.innerHTML = '<div class="col-span-full py-10 text-center text-gray-500">No se encontraron productos.</div>';
            return;
        }

        const gridLayout = document.createElement('div');
        gridLayout.className = 'grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-3 xl:grid-cols-4 gap-4 pb-10';

        filtered.forEach(p => {
            const div = document.createElement('div');
            div.className = `bg-white rounded-lg shadow-sm border ${p.stock <= 0 ? 'border-red-300 opacity-50' : 'border-gray-200 hover:-translate-y-1 hover:shadow-md cursor-pointer'} transition-all overflow-hidden flex flex-col items-center p-3 text-center`;
            if (p.stock > 0) div.onclick = () => addToCart(p);

            const imgHTML = p.image && p.image.trim() !== '' 
                ? `<div class="h-16 w-full mb-3 flex items-center justify-center"><img src="<?= BASE_URL ?>${p.image}" class="h-full object-contain rounded"></div>`
                : `<div class="h-16 w-16 bg-blue-50 text-primary rounded-full flex items-center justify-center mb-3 mx-auto"><i class="fa-solid fa-box text-2xl"></i></div>`;
                
            div.innerHTML = `
            ${imgHTML}
            <h3 class="font-bold text-gray-800 text-sm mb-1 leading-tight line-clamp-2" title="${p.name}">${p.name}</h3>
            <p class="text-primary font-black text-lg mt-auto">${parseFloat(p.selling_price).toFixed(2)} €</p>
            <p class="text-xs text-gray-500 mt-1">Stock: ${p.stock <= 0 ? '<span class="text-red-500 font-bold">Agotado</span>' : p.stock}</p>
        `;
            gridLayout.appendChild(div);
        });

        grid.appendChild(gridLayout);
    }

    function addToCart(product) {
        if (product.stock <= 0) {
            showToast('Producto agotado', 'error');
            return;
        }

        const existing = cart.find(item => item.id === product.id);
        if (existing) {
            if (existing.qty >= product.stock) {
                showToast('Stock insuficiente para añadir más', 'error');
                return;
            }
            existing.qty++;
        } else {
            cart.push({
                id: product.id,
                name: product.name,
                price: parseFloat(product.selling_price),
                qty: 1,
                max: product.stock,
                barcode: product.barcode
            });
        }
        updateCartUI();
        // Beep sound
        try {
            const actx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = actx.createOscillator();
            const gain = actx.createGain();
            osc.connect(gain);
            gain.connect(actx.destination);
            osc.frequency.value = 800;
            gain.gain.value = 0.1;
            osc.start();
            osc.stop(actx.currentTime + 0.1);
        } catch (e) { }
    }

    function updateCartQty(id, change) {
        const item = cart.find(i => i.id === id);
        if (item) {
            let newQty = item.qty + change;
            if (newQty <= 0) {
                cart = cart.filter(i => i.id !== id);
            } else if (newQty > item.max) {
                showToast('Límite de stock alcanzado', 'error');
            } else {
                item.qty = newQty;
            }
            updateCartUI();
        }
    }

    function removeFromCart(id) {
        cart = cart.filter(i => i.id !== id);
        updateCartUI();
    }

    function clearCart() {
        if (cart.length === 0) return;
        if (confirm('¿Eliminar todos los productos de la venta actual?')) {
            cart = [];
            updateCartUI();
        }
    }

    function toggleDiscount() {
        const box = document.getElementById('discountBox');
        box.classList.toggle('hidden');
    }

    function updateCartTotal() {
        let subtotal = 0;
        cart.forEach(item => subtotal += (item.price * item.qty));

        const discType = document.getElementById('discountType').value;
        let discVal = parseFloat(document.getElementById('discountValue').value) || 0;
        let discountAmount = 0;

        if (discType === 'percentage') {
            discountAmount = subtotal * (discVal / 100);
        } else {
            discountAmount = discVal;
        }

        if (discountAmount > subtotal) discountAmount = subtotal;

        currentTotal = subtotal - discountAmount;

        // Tax calculation (assume prices include tax, just show breakdown, e.g. 21% IVA)
        const taxRate = 0.21;
        const preTax = currentTotal / (1 + taxRate);
        const taxAmount = currentTotal - preTax;

        document.getElementById('cartSubtotal').textContent = subtotal.toFixed(2) + ' €';
        document.getElementById('cartDiscount').textContent = '-' + discountAmount.toFixed(2) + ' €';
        document.getElementById('cartTax').textContent = taxAmount.toFixed(2) + ' € (21%)';
        document.getElementById('cartTotal').textContent = currentTotal.toFixed(2) + ' €';

        document.getElementById('btnCheckout').disabled = cart.length === 0;
        
        // Broadcast to customer display via Network AJAX instead of LocalStorage
        const payload = JSON.stringify({
            items: cart,
            discount_type: discType,
            discount_value: discVal,
            total: currentTotal
        });
        
        fetch('../customer-display/update-state.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: payload
        }).catch(err => console.error("Error updating display", err));
    }

    function updateCartUI() {
        const container = document.getElementById('cartItems');

        if (cart.length === 0) {
            container.innerHTML = `
            <div class="flex flex-col items-center justify-center h-full text-gray-400 opacity-50">
                <i class="fa-solid fa-cart-arrow-down text-6xl mb-4"></i>
                <p>Carrito vacío</p>
                <p class="text-sm">Añada productos para comenzar</p>
            </div>
        `;
        } else {
            container.innerHTML = `<ul class="divide-y divide-gray-200">`;
            cart.forEach(item => {
                container.innerHTML += `
                <li class="py-3 flex justify-between bg-white px-2 py-3 rounded mb-2 shadow-sm border border-gray-100">
                    <div class="flex-1 pr-2">
                        <p class="text-sm font-bold text-gray-800 leading-tight">${item.name}</p>
                        <p class="text-xs text-gray-500">${item.price.toFixed(2)} €</p>
                    </div>
                    <div class="flex flex-col items-end gap-2">
                        <p class="font-bold text-gray-900">${(item.price * item.qty).toFixed(2)} €</p>
                        <div class="flex items-center text-sm border border-gray-300 rounded overflow-hidden shadow-sm">
                            <button onclick="updateCartQty(${item.id}, -1)" class="px-2 py-1 bg-gray-100 hover:bg-gray-200 text-gray-600 transition"><i class="fa-solid fa-minus"></i></button>
                            <span class="px-3 py-1 font-bold bg-white w-8 text-center">${item.qty}</span>
                            <button onclick="updateCartQty(${item.id}, 1)" class="px-2 py-1 bg-gray-100 hover:bg-gray-200 text-gray-600 transition"><i class="fa-solid fa-plus"></i></button>
                        </div>
                    </div>
                    <button onclick="removeFromCart(${item.id})" class="ml-2 text-red-400 hover:text-red-600 flex items-center p-2"><i class="fa-solid fa-trash"></i></button>
                </li>
            `;
            });
            container.innerHTML += `</ul>`;
        }
        updateCartTotal();
    }

    function checkout() {
        if (cart.length === 0) return;

        // Prepare data
        const discType = document.getElementById('discountType').value;
        const discVal = parseFloat(document.getElementById('discountValue').value) || 0;

        const checkoutData = {
            items: cart,
            discount_type: discType,
            discount_value: discVal,
            total: currentTotal
        };

        document.getElementById('checkoutCartData').value = JSON.stringify(checkoutData);
        document.getElementById('checkoutTotalDisplay').value = currentTotal.toFixed(2) + ' €';

        // reset cash
        document.getElementById('paidAmount').value = currentTotal.toFixed(2);
        document.getElementById('paymentMethod').value = 'Efectivo';
        toggleCashFields();

        document.getElementById('checkoutModal').classList.remove('hidden');
        setTimeout(() => {
            document.getElementById('paidAmount').focus();
            document.getElementById('paidAmount').select();
        }, 100);
    }

    function toggleCashFields() {
        const method = document.getElementById('paymentMethod').value;
        const cashDiv = document.getElementById('cashFields');
        if (method === 'Efectivo') {
            cashDiv.classList.remove('hidden');
            calculateChange();
        } else {
            cashDiv.classList.add('hidden');
            document.getElementById('finalPayBtn').disabled = false;
        }
    }

    function calculateChange() {
        const paid = parseFloat(document.getElementById('paidAmount').value) || 0;
        const change = paid - currentTotal;
        const changeDisplay = document.getElementById('changeAmountDisplay');
        const payBtn = document.getElementById('finalPayBtn');

        changeDisplay.textContent = change.toFixed(2) + ' €';

        if (change < 0) {
            changeDisplay.classList.remove('text-green-500');
            changeDisplay.classList.add('text-red-500');
            payBtn.disabled = true;
        } else {
            changeDisplay.classList.remove('text-red-500');
            changeDisplay.classList.add('text-green-500');
            payBtn.disabled = false;
        }
    }

    // Scanner Logic
    let quaggaRunning = false;
    function startScanner() {
        document.getElementById('scannerModal').classList.remove('hidden');
        if (quaggaRunning) return;

        Quagga.init({
            inputStream: {
                name: "Live",
                type: "LiveStream",
                target: document.querySelector('#interactive'),
                constraints: { width: 640, height: 480, facingMode: "environment" }
            },
            decoder: { readers: ["code_128_reader", "ean_reader", "ean_8_reader", "code_39_reader"] }
        }, function (err) {
            if (err) {
                console.error(err);
                showToast('Error al acceder a la cámara. Asegúrese de tener permisos.', 'error');
                stopScanner();
                return;
            }
            Quagga.start();
            quaggaRunning = true;
        });

        Quagga.onDetected(function (result) {
            var code = result.codeResult.code;
            const prod = allProducts.find(p => p.barcode === code);
            if (prod) {
                addToCart(prod);
                stopScanner();
                showToast('Producto escaneado y añadido', 'success');
            }
        });
    }

    function stopScanner() {
        document.getElementById('scannerModal').classList.add('hidden');
        if (quaggaRunning) {
            Quagga.stop();
            quaggaRunning = false;
        }
    }
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>