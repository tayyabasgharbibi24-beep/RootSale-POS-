<?php
// pages/returns/index.php
require_once __DIR__ . '/../../includes/header.php';
requireSellerOrAdmin();
?>

<!-- Scanner Modal -->
<div id="scannerModal" class="fixed inset-0 bg-black bg-opacity-75 z-50 hidden flex flex-col pt-10 px-4">
    <div class="flex justify-between items-center mb-4 max-w-lg mx-auto w-full">
        <h2 class="text-white text-xl font-bold">Escanear Código de Barras</h2>
        <button onclick="stopScanner()" class="text-white text-3xl"><i class="fa-solid fa-times"></i></button>
    </div>
    <div class="bg-black w-full max-w-lg mx-auto rounded overflow-hidden relative shadow-2xl" style="height: 300px;">
        <div id="interactive" class="viewport w-full h-full"></div>
        <div class="absolute inset-0 border-4 border-primary bg-transparent z-10 opacity-50 m-12 pointer-events-none rounded"></div>
    </div>
    <p class="text-center text-white mt-4 max-w-lg mx-auto text-sm">Alinee el código de barras dentro del marco para buscar la factura.</p>
</div>

<div class="max-w-4xl mx-auto pb-10">
    <div class="flex items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Procesar Devolución</h1>
    </div>

    <!-- Search Bill -->
    <div class="bg-white p-6 rounded-lg shadow-md mb-6">
        <h2 class="text-lg font-bold mb-4 text-gray-700 border-b pb-2">Buscar Factura de Venta</h2>
        <div class="flex flex-col sm:flex-row gap-4">
            <div class="relative flex-1">
                <input type="text" id="billNumber" placeholder="Ingrese número de factura (Ej: INV-[FECHA]-[ID])" class="w-full border border-gray-300 rounded pl-4 pr-10 py-2 focus:ring-primary focus:border-primary font-mono text-lg uppercase">
                <button onclick="startScanner()" class="absolute right-2 top-2 text-gray-500 hover:text-primary"><i class="fa-solid fa-camera text-xl"></i></button>
            </div>
            <button onclick="searchBill()" class="bg-primary hover:bg-blue-700 text-white px-6 py-2 rounded shadow transition font-bold" id="btnSearch">
                <i class="fa-solid fa-search mr-2"></i> Buscar
            </button>
        </div>
        <p id="searchMessage" class="text-red-500 text-sm mt-2 hidden"></p>
    </div>

    <!-- Return Details (Hidden until search) -->
    <div id="returnDetails" class="bg-white rounded-lg shadow-md overflow-hidden hidden transition-all">
         <div class="bg-gray-50 border-b border-gray-200 p-4 flex justify-between items-center">
             <div>
                 <h2 class="font-bold text-gray-800" id="lblBillNumber">Factura: </h2>
                 <p class="text-sm text-gray-500" id="lblBillDate">Fecha: </p>
             </div>
             <div class="text-right">
                 <p class="text-sm text-gray-500" id="lblCustomer">Cliente: </p>
                 <p class="font-bold text-primary" id="lblTotalSpent">Total Original: </p>
             </div>
         </div>
         
         <form id="returnForm" action="process-return.php" method="POST">
             <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
             <input type="hidden" name="sale_id" id="hiddenSaleId">
             
             <div class="p-4 overflow-x-auto">
                 <table class="min-w-full divide-y divide-gray-200">
                     <thead>
                         <tr>
                             <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Producto</th>
                             <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Comprado</th>
                             <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Precio Unit.</th>
                             <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase bg-blue-50 border-l border-blue-100">Devolver</th>
                         </tr>
                     </thead>
                     <tbody id="itemsList" class="divide-y divide-gray-200">
                         <!-- Injected by JS -->
                     </tbody>
                 </table>
             </div>
             
             <div class="p-6 bg-gray-50 border-t border-gray-200">
                 <h3 class="font-bold text-gray-700 border-b pb-2 mb-4">Información de Reembolso</h3>
                 
                 <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                     <div>
                         <label class="block text-sm font-medium text-gray-700 mb-1">Método de Reembolso</label>
                         <select name="refund_method" required class="w-full border border-gray-300 rounded px-4 py-2 focus:ring-primary focus:border-primary">
                             <option value="Efectivo">Efectivo 💵</option>
                             <option value="Tarjeta">Tarjeta 💳</option>
                             <option value="UPI">Transferencia (Bizum) 📱</option>
                         </select>
                     </div>
                     <div>
                         <label class="block text-sm font-medium text-gray-700 mb-1">Motivo / Razón</label>
                         <input type="text" name="reason" required class="w-full border border-gray-300 rounded px-4 py-2 focus:ring-primary focus:border-primary" placeholder="Ej. Talla incorrecta, Defecto...">
                     </div>
                 </div>
                 
                 <div class="flex justify-between items-center bg-white p-4 border border-gray-200 rounded text-lg border-l-4 border-l-red-500">
                     <span class="font-bold text-gray-700">Total a Reembolsar:</span>
                     <span class="font-black text-red-600" id="lblRefundTotal">0.00 €</span>
                     <input type="hidden" name="total_refund" id="hiddenRefundTotal" value="0">
                 </div>
                 
                 <div class="mt-6 flex justify-end">
                     <button type="submit" id="btnProcessReturn" class="bg-red-500 hover:bg-red-600 text-white font-bold py-3 px-8 rounded shadow transition disabled:opacity-50">
                         <i class="fa-solid fa-undo mr-2"></i> Procesar Devolución
                     </button>
                 </div>
             </div>
         </form>
    </div>
</div>

<script>
let currentItems = [];

async function searchBill() {
    const input = document.getElementById('billNumber');
    const val = input.value.trim();
    if(!val) return;
    
    document.getElementById('btnSearch').disabled = true;
    document.getElementById('searchMessage').classList.add('hidden');
    document.getElementById('returnDetails').classList.add('hidden');
    
    try {
        const res = await fetch(`get-bill-details.php?bill=${encodeURIComponent(val)}`);
        const data = await res.json();
        
        if(data.success) {
            populateDetails(data.data);
            document.getElementById('returnDetails').classList.remove('hidden');
        } else {
            showError(data.message || 'Factura no encontrada.');
        }
    } catch(e) {
        showError('Error de red. Intenta de nuevo.');
    }
    document.getElementById('btnSearch').disabled = false;
}

function showError(msg) {
    const el = document.getElementById('searchMessage');
    el.textContent = msg;
    el.classList.remove('hidden');
}

function populateDetails(data) {
    const sale = data.sale;
    currentItems = data.items;
    
    document.getElementById('lblBillNumber').textContent = 'Factura: ' + sale.bill_number;
    document.getElementById('lblBillDate').textContent = 'Fecha: ' + sale.created_at;
    document.getElementById('lblCustomer').textContent = 'Cliente: ' + (sale.customer_name || 'Final');
    document.getElementById('lblTotalSpent').textContent = 'Total Original: ' + parseFloat(sale.grand_total).toFixed(2) + ' €';
    document.getElementById('hiddenSaleId').value = sale.id;
    
    const tbody = document.getElementById('itemsList');
    tbody.innerHTML = '';
    
    currentItems.forEach((item, index) => {
        // Calculate amount eligible for return
        const maxReturn = item.quantity - (item.returned_qty || 0);
        
        let row = document.createElement('tr');
        row.innerHTML = `
            <td class="px-4 py-3 text-sm font-medium text-gray-900">${item.product_name}</td>
            <td class="px-4 py-3 text-sm text-center">${item.quantity} ${item.returned_qty > 0 ? `(<span class="text-red-500">-${item.returned_qty} dev.</span>)` : ''}</td>
            <td class="px-4 py-3 text-sm text-right">${parseFloat(item.unit_price).toFixed(2)} €</td>
            <td class="px-4 py-3 text-center bg-blue-50 border-l border-blue-100">
                <input type="number" name="return_items[${item.sale_item_id}]" 
                       class="w-20 border border-gray-300 rounded px-2 py-1 text-center font-bold" 
                       min="0" max="${maxReturn}" value="0" 
                       data-price="${item.unit_price}"
                       onchange="calculateRefund()">
                <span class="text-xs text-gray-500 block">Máx: ${maxReturn}</span>
            </td>
        `;
        tbody.appendChild(row);
    });
    
    calculateRefund();
}

function calculateRefund() {
    const inputs = document.querySelectorAll('input[name^="return_items"]');
    let total = 0;
    inputs.forEach(inp => {
        const qty = parseInt(inp.value) || 0;
        const price = parseFloat(inp.getAttribute('data-price')) || 0;
        if(qty > 0) total += (qty * price);
    });
    
    document.getElementById('lblRefundTotal').textContent = total.toFixed(2) + ' €';
    document.getElementById('hiddenRefundTotal').value = total.toFixed(2);
    
    document.getElementById('btnProcessReturn').disabled = (total <= 0);
}

// Support hitting enter
// Support hitting enter
document.getElementById('billNumber').addEventListener('keypress', function(e){
    if(e.key === 'Enter'){ searchBill(); }
});

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
            alert('Error al acceder a la cámara. Asegúrese de tener permisos.');
            stopScanner();
            return;
        }
        Quagga.start();
        quaggaRunning = true;
    });

    Quagga.onDetected(function (result) {
        var code = result.codeResult.code;
        document.getElementById('billNumber').value = code;
        stopScanner();
        searchBill();
    });
}

function stopScanner() {
    document.getElementById('scannerModal').classList.add('hidden');
    if (quaggaRunning) {
        Quagga.stop();
        quaggaRunning = false;
    }
}

<?php if(isset($_GET['success'])): ?>
    showToast('<?= htmlspecialchars($_GET['success']) ?>', 'success');
<?php endif; ?>
<?php if(isset($_GET['error'])): ?>
    showToast('<?= htmlspecialchars($_GET['error']) ?>', 'error');
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
