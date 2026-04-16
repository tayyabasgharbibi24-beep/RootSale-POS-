<?php
// pages/settings/hardware.php
require_once __DIR__ . '/../../includes/header.php';
requireAdmin();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        $error = "Petición no válida.";
    } else {
        $drawer_command = $_POST['drawer_command'];
        $connection_type = in_array($_POST['printer_connection_type'] ?? '', ['web_serial', 'web_usb', 'network']) ? $_POST['printer_connection_type'] : 'web_serial';
        $printer_ip = sanitizeInput($_POST['printer_ip'] ?? '');
        $welcome_text = sanitizeInput($_POST['welcome_text']);
        $show_cart = isset($_POST['show_cart']) ? 1 : 0;
        
        try {
            $pdo->beginTransaction();
            
            // Auto-patch hardware_settings table
            try { $pdo->exec("ALTER TABLE hardware_settings ADD COLUMN connection_type TEXT DEFAULT 'web_serial'"); } catch(Exception $e) {}
            try { $pdo->exec("ALTER TABLE hardware_settings ADD COLUMN printer_ip TEXT DEFAULT ''"); } catch(Exception $e) {}

            $stmt1 = $pdo->prepare("UPDATE hardware_settings SET connection_type=?, printer_ip=? WHERE id=1");
            $stmt1->execute([$connection_type, $printer_ip]);
            
            // display
            $stmt2 = $pdo->prepare("UPDATE customer_display_settings SET welcome_text=?, show_cart=? WHERE id=1");
            $stmt2->execute([$welcome_text, $show_cart]);

            $pdo->commit();
            $success = "Configuraciones actualizadas.";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Error al actualizar: " . $e->getMessage();
        }
    }
}

$hw = $pdo->query("SELECT * FROM hardware_settings LIMIT 1")->fetch();
$disp = $pdo->query("SELECT * FROM customer_display_settings LIMIT 1")->fetch();

if(!$hw) { $hw = ['drawer_command' => '\x1B\x70\x00\x19\xFA']; }
if(!$disp) { $disp = ['welcome_text' => 'Bienvenido', 'show_cart' => 1]; }
?>

<div class="max-w-4xl mx-auto pb-8">
    <div class="flex items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800"><i class="fa-solid fa-desktop mr-2 text-primary"></i>Hardware & Pantalla Cliente</h1>
    </div>

    <?php if ($error): ?>
        <div class="bg-red-100 text-red-700 p-4 mb-4 rounded border-l-4 border-red-500"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="bg-green-100 text-green-700 p-4 mb-4 rounded border-l-4 border-green-500"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form action="" method="POST">
        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
        
        <div class="bg-white rounded-lg shadow-md p-6 mb-6 border-t-4 border-primary">
            <h2 class="text-lg font-bold border-b pb-2 mb-4 text-gray-700">Impresora y Apertura de Cajón Automática</h2>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Método de Conexión de Impresora</label>
                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4 sm:gap-6 bg-gray-50 p-4 border border-gray-200 rounded flex-wrap">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="printer_connection_type" value="web_usb" <?= ($hw['connection_type'] ?? '') === 'web_usb' ? 'checked' : '' ?> class="w-5 h-5 text-primary peer">
                        <span class="text-gray-800 font-bold">USB Directo (WebUSB)</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="printer_connection_type" value="web_serial" <?= ($hw['connection_type'] ?? 'web_serial') === 'web_serial' ? 'checked' : '' ?> class="w-5 h-5 text-primary peer">
                        <span class="text-gray-800 font-bold">USB Virtual COM (WebSerial)</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="printer_connection_type" value="network" <?= ($hw['connection_type'] ?? '') === 'network' ? 'checked' : '' ?> class="w-5 h-5 text-primary peer">
                        <span class="text-gray-800 font-bold">Por Red / Cable Red (IP)</span>
                    </label>
                </div>
            </div>

            <!-- WebUSB Config -->
            <div id="webUsbConfigUI" class="<?= ($hw['connection_type'] ?? '') === 'web_usb' ? 'block' : 'hidden' ?>">
                <p class="text-sm text-gray-500 mb-3"><i class="fa-solid fa-info-circle text-blue-500"></i> Funciona con casi cualquier impresora térmica USB estándar directamente.</p>
                <div class="text-center md:text-left flex flex-col md:flex-row items-center gap-6 p-4 border border-gray-200 rounded-lg bg-white shadow-sm">
                    <div class="w-16 h-16 bg-green-100 text-green-600 flex items-center justify-center rounded-full text-2xl flex-shrink-0">
                        <i class="fa-brands fa-usb"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-bold text-gray-800 text-lg">Estado: <span id="webUsbStatus" class="text-yellow-600">Verificando...</span></h3>
                        <p class="text-sm text-gray-600 mt-1">Conecte su impresora térmica vía cable USB Normal.</p>
                    </div>
                    <div>
                        <button type="button" id="btnConnectWebUsb" class="bg-gray-800 text-white font-bold px-6 py-3 rounded shadow hover:bg-black transition whitespace-nowrap">
                            <i class="fa-brands fa-usb mr-2"></i> Conectar USB
                        </button>
                    </div>
                </div>
                <div class="mt-4 pt-4 border-t border-gray-200 flex justify-end">
                    <button type="button" id="btnTestWebUsbDrawer" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded text-sm hover:bg-gray-50 transition opacity-50 cursor-not-allowed">
                        <i class="fa-solid fa-cash-register mr-1"></i> Probar Cajón (WebUSB)
                    </button>
                </div>
            </div>

            <!-- USB Serial Config -->
            <div id="usbConfigUI" class="<?= ($hw['connection_type'] ?? 'web_serial') === 'web_serial' ? 'block' : 'hidden' ?>">
                <p class="text-sm text-gray-500 mb-3"><i class="fa-solid fa-info-circle text-blue-500"></i> Funciona solo con impresoras que emulan un Puerto COM Virtual en Windows.</p>
                <div class="text-center md:text-left flex flex-col md:flex-row items-center gap-6 p-4 border border-gray-200 rounded-lg bg-white shadow-sm">
                    <div class="w-16 h-16 bg-blue-100 text-primary flex items-center justify-center rounded-full text-2xl flex-shrink-0">
                        <i class="fa-solid fa-plug"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-bold text-gray-800 text-lg">Estado: <span id="printerStatus" class="text-yellow-600">Verificando...</span></h3>
                        <p class="text-sm text-gray-600 mt-1">Conecte su impresora térmica a través del emulador COM Serial.</p>
                    </div>
                    <div>
                        <button type="button" id="btnConnectPrinter" class="bg-gray-800 text-white font-bold px-6 py-3 rounded shadow hover:bg-black transition whitespace-nowrap">
                            <i class="fa-solid fa-plug mr-2"></i> Autorizar COM
                        </button>
                    </div>
                </div>
                
                <div class="mt-4 pt-4 border-t border-gray-200 flex justify-end">
                    <button type="button" id="btnTestDrawer" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded text-sm hover:bg-gray-50 transition opacity-50 cursor-not-allowed">
                        <i class="fa-solid fa-cash-register mr-1"></i> Probar Cajón (Serial)
                    </button>
                </div>
            </div>

            <!-- Network IP Config -->
            <div id="networkConfigUI" class="<?= ($hw['connection_type'] ?? 'web_serial') === 'network' ? 'block' : 'hidden' ?>">
                <p class="text-sm text-gray-500 mb-3"><i class="fa-solid fa-info-circle text-blue-500"></i> Funciona conectando directamente a la IP de red de la impresora inalámbrica o por cable Ethernet (Puerto 9100).</p>
                <div class="p-4 border border-blue-200 rounded-lg bg-blue-50 shadow-sm flex flex-col gap-4">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Dirección IP de la Impresora *</label>
                        <div class="flex items-center gap-4">
                            <input type="text" id="printer_ip_input" name="printer_ip" value="<?= htmlspecialchars($hw['printer_ip'] ?? '') ?>" placeholder="ej: 192.168.1.100" class="w-full md:w-1/2 border border-gray-300 rounded px-4 py-2 focus:ring-primary focus:border-primary font-mono text-lg">
                            <button type="button" id="btnTestNetworkDrawer" class="bg-emerald-600 text-white font-bold px-4 py-2 rounded shadow hover:bg-emerald-700 transition whitespace-nowrap">
                                <i class="fa-solid fa-network-wired mr-1"></i> Probar Cajón
                            </button>
                        </div>
                    </div>
                    <p class="text-xs text-gray-600">Al guardar con este método, el servidor POS enviará los comandos de apertura de cajón de forma invisible usando WebSockets al instante.</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6 mb-6 border-t-4 border-secondary">
            <h2 class="text-lg font-bold border-b pb-2 mb-4 text-gray-700">Configuración de Pantalla Cliente</h2>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Mensaje de Bienvenida</label>
                <input type="text" name="welcome_text" value="<?= htmlspecialchars($disp['welcome_text']) ?>" class="w-full border border-gray-300 rounded px-4 py-2 focus:ring-primary focus:border-primary">
            </div>

            <div class="mb-4">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="show_cart" <?= $disp['show_cart'] ? 'checked' : '' ?> class="w-5 h-5 text-primary rounded">
                    <span class="text-gray-800 font-medium">Mostrar el contenido del carrito en tiempo real</span>
                </label>
            </div>
            
            <div class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded">
                <p class="text-sm text-blue-800">
                    <strong>Tip:</strong> Puedes abrir la Pantalla Cliente copiando e ingresando a esta URL en la segunda pantalla:<br>
                    <a href="<?= BASE_URL ?>/pages/customer-display/" target="_blank" class="font-mono mt-2 inline-block font-bold hover:underline"><?= "http://" . $_SERVER['HTTP_HOST'] . BASE_URL ?>/pages/customer-display/</a>
                </p>
            </div>
        </div>

        <div class="flex justify-end mb-10">
            <button type="submit" class="bg-primary hover:bg-blue-700 text-white font-bold py-3 px-8 rounded shadow-lg flex items-center gap-2 transition transform hover:-translate-y-1">
                <i class="fa-solid fa-save"></i> Guardar Cambios
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', async () => {
    // UI Toggle Logic
    const radios = document.querySelectorAll('input[name="printer_connection_type"]');
    const usbUI = document.getElementById('usbConfigUI');
    const netUI = document.getElementById('networkConfigUI');
    const webUsbUI = document.getElementById('webUsbConfigUI');

    radios.forEach(r => {
        r.addEventListener('change', (e) => {
            usbUI.classList.add('hidden'); usbUI.classList.remove('block');
            netUI.classList.add('hidden'); netUI.classList.remove('block');
            webUsbUI.classList.add('hidden'); webUsbUI.classList.remove('block');
            
            if(e.target.value === 'web_serial') {
                usbUI.classList.remove('hidden'); usbUI.classList.add('block');
            } else if(e.target.value === 'network') {
                netUI.classList.remove('hidden'); netUI.classList.add('block');
            } else if(e.target.value === 'web_usb') {
                webUsbUI.classList.remove('hidden'); webUsbUI.classList.add('block');
            }
        });
    });

    // Test Network Drawer
    const btnTestNetwork = document.getElementById('btnTestNetworkDrawer');
    if (btnTestNetwork) {
        btnTestNetwork.addEventListener('click', async () => {
            const ip = document.getElementById('printer_ip_input').value.trim();
            if(!ip) return alert('Por favor ingrese la IP de la impresora.');
            
            btnTestNetwork.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i> Conectando...';
            btnTestNetwork.disabled = true;
            
            try {
                const fd = new FormData(); fd.append('ip', ip);
                const res = await fetch('<?= BASE_URL ?>/api/kick-drawer.php', { method: 'POST', body: fd });
                const json = await res.json();
                if(json.success) alert('¡Señal enviada a la IP ' + ip + '! El cajón debería abrirse.');
                else alert('Error: ' + json.message);
            } catch(e) {
                alert('Error de conexión.');
            }
            btnTestNetwork.innerHTML = '<i class="fa-solid fa-network-wired mr-1"></i> Probar Cajón';
            btnTestNetwork.disabled = false;
        });
    }

    // WebUSB Logic
    const webUsbStatusEl = document.getElementById('webUsbStatus');
    const btnConnectWebUsb = document.getElementById('btnConnectWebUsb');
    const btnTestWebUsbDrawer = document.getElementById('btnTestWebUsbDrawer');

    if (!navigator.usb) {
        webUsbStatusEl.textContent = '❌ WebUSB NO Soportado. Use Chrome/Edge.';
        webUsbStatusEl.className = 'text-red-500 font-bold';
        btnConnectWebUsb.disabled = true;
        btnConnectWebUsb.classList.add('opacity-50');
    } else {
        try {
            const devices = await navigator.usb.getDevices();
            if (devices.length > 0) {
                setWebUsbConnectedUI();
            } else {
                webUsbStatusEl.textContent = 'Desconectado';
                webUsbStatusEl.className = 'text-gray-500';
            }
        } catch(e) {}
    }

    btnConnectWebUsb?.addEventListener('click', async () => {
        try {
            await navigator.usb.requestDevice({ filters: [] });
            setWebUsbConnectedUI();
            alert('¡Impresora WebUSB conectada con éxito!');
        } catch (e) {
            console.warn(e);
        }
    });

    btnTestWebUsbDrawer?.addEventListener('click', async () => {
        try {
            const devices = await navigator.usb.getDevices();
            if (devices.length === 0) return alert('Conecte la impresora primero.');
            const device = devices[0];
            await device.open();
            if (device.configuration === null) await device.selectConfiguration(1);
            try { await device.claimInterface(0); } catch(ex) { console.warn(ex); }
            
            let endpointNum = null;
            for (const i of device.configuration.interfaces) {
                for (const a of i.alternates) {
                    for (const e of a.endpoints) {
                        if (e.direction === 'out') { endpointNum = e.endpointNumber; break; }
                    }
                }
            }
            if (!endpointNum) throw new Error("No se encontró Endpoint de salida");
            
            const data = new Uint8Array([0x1B, 0x70, 0x00, 0x19, 0xFA]);
            await device.transferOut(endpointNum, data);
            await device.close();
            alert('Señal enviada.');
        } catch(e) {
            console.error(e);
            alert('Error: ' + e.message);
        }
    });

    function setWebUsbConnectedUI() {
        webUsbStatusEl.innerHTML = '<i class="fa-solid fa-check-circle"></i> Conectado y Listo';
        webUsbStatusEl.className = 'text-green-600 font-bold';
        btnTestWebUsbDrawer.classList.remove('opacity-50', 'cursor-not-allowed');
        btnTestWebUsbDrawer.classList.add('hover:bg-gray-100');
    }

    const btnConnect = document.getElementById('btnConnectPrinter');
    const btnTest = document.getElementById('btnTestDrawer');
    const statusEl = document.getElementById('printerStatus');
    
    // Check if Serial supported
    if (!navigator.serial) {
        statusEl.textContent = '❌ Navegador NO Soportado. Use Chrome/Edge.';
        statusEl.className = 'text-red-500 font-bold';
        if (btnConnect) {
            btnConnect.disabled = true;
            btnConnect.classList.add('opacity-50');
        }
    } else {
        // Auto reconnect authorized ports
        try {
            const ports = await navigator.serial.getPorts();
            if (ports.length > 0) {
                setConnectedUI();
            } else {
                statusEl.textContent = 'Desconectado';
                statusEl.className = 'text-gray-500';
            }
        } catch (e) {
            console.error(e);
            statusEl.textContent = 'Error al verificar';
        }
    }

    // Connect manually
    btnConnect?.addEventListener('click', async () => {
        try {
            await navigator.serial.requestPort();
            setConnectedUI();
            alert('¡Impresora WebSerial autorizada!');
        } catch (e) {
            console.warn(e); // user cancelled or error
        }
    });

    // Test Drawer
    btnTest?.addEventListener('click', async () => {
        try {
            const ports = await navigator.serial.getPorts();
            if (ports.length === 0) return alert('Por favor conecte o autorice la impresora primero.');
            
            const port = ports[0];
            await port.open({ baudRate: 9600 });
            const writer = port.writable.getWriter();
            
            // Standard ESC/POS drawer kick command \x1b\x70\x00\x19\xFA
            const data = new Uint8Array([0x1B, 0x70, 0x00, 0x19, 0xFA]);
            await writer.write(data);
            
            await writer.close();
            await port.close();
            alert('Señal enviada.');
        } catch (e) {
            console.error(e);
            alert('Error: ' + e.message);
        }
    });

    function setConnectedUI() {
        statusEl.innerHTML = '<i class="fa-solid fa-check-circle"></i> Autorizado';
        statusEl.className = 'text-green-600 font-bold';
        btnTest.classList.remove('opacity-50', 'cursor-not-allowed');
        btnTest.classList.add('hover:bg-gray-100');
    }
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
