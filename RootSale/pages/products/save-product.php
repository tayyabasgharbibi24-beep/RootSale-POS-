<?php
// pages/products/save-product.php
require_once __DIR__ . '/../../includes/header.php';
requireInventoryManagerOrAdmin();

$id = $_GET['id'] ?? null;
$product = [
    'barcode' => '', 'name' => '', 'category_id' => '', 'size' => '', 
    'color' => '', 'brand' => '', 'cost_price' => '', 'selling_price' => '', 
    'stock' => 0, 'low_stock_threshold' => 5, 'description' => '', 'image' => ''
];

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $existing = $stmt->fetch();
    if ($existing) $product = $existing;
    else { header("Location: index.php?error=Producto no encontrado"); exit; }
} else {
    // Generate rand barcode if new
    $product['barcode'] = 'PROD' . time() . rand(10,99);
}

$cats = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        $error = "Error de validación.";
    } else {
        $barcode = sanitizeInput($_POST['barcode']);
        $name = sanitizeInput($_POST['name']);
        $category_id = (int)$_POST['category_id'];
        $size = sanitizeInput($_POST['size']);
        $color = sanitizeInput($_POST['color']);
        $brand = sanitizeInput($_POST['brand']);
        $cost_price = (float)$_POST['cost_price'];
        $selling_price = (float)$_POST['selling_price'];
        $stock = (int)$_POST['stock'];
        $low_stock_threshold = (int)$_POST['low_stock_threshold'];
        $description = sanitizeInput($_POST['description']);
        
        $image_path = $product['image'] ?? null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $new_name = 'prod_' . time() . '_' . rand(10,99) . '.' . $ext;
                if (move_uploaded_file($_FILES['image']['tmp_name'], __DIR__ . '/../../assets/img/products/' . $new_name)) {
                    $image_path = '/assets/img/products/' . $new_name;
                }
            }
        }
        
        if (empty($barcode) || empty($name) || empty($category_id) || $selling_price <= 0) {
            $error = "Por favor, complete todos los campos obligatorios (*).";
        } else {
            if ($id) {
                // Check if barcode belongs to another product
                $stmt = $pdo->prepare("SELECT id FROM products WHERE barcode = ? AND id != ?");
                $stmt->execute([$barcode, $id]);
                if ($stmt->fetch()) $error = "El código de barras ya existe.";
                else {
                    $stmt = $pdo->prepare("UPDATE products SET barcode=?, name=?, category_id=?, size=?, color=?, brand=?, cost_price=?, selling_price=?, stock=?, low_stock_threshold=?, description=?, image=? WHERE id=?");
                    if ($stmt->execute([$barcode, $name, $category_id, $size, $color, $brand, $cost_price, $selling_price, $stock, $low_stock_threshold, $description, $image_path, $id])) {
                        header("Location: index.php?success=Producto actualizado.");
                        exit;
                    } else {
                        $error = "Error al actualizar base de datos.";
                    }
                }
            } else {
                $stmt = $pdo->prepare("SELECT id FROM products WHERE barcode = ?");
                $stmt->execute([$barcode]);
                if ($stmt->fetch()) $error = "El código de barras ya existe.";
                else {
                    $stmt = $pdo->prepare("INSERT INTO products (barcode, name, category_id, size, color, brand, cost_price, selling_price, stock, low_stock_threshold, description, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    if ($stmt->execute([$barcode, $name, $category_id, $size, $color, $brand, $cost_price, $selling_price, $stock, $low_stock_threshold, $description, $image_path])) {
                        header("Location: index.php?success=Producto creado.");
                        exit;
                    } else {
                        $error = "Error al guardar en la base de datos.";
                    }
                }
            }
        }
        
        // Populate typed data back on error
        $product = $_POST;
    }
}
?>

<div class="max-w-4xl mx-auto pb-8">
    <div class="flex items-center mb-6">
        <a href="index.php" class="text-gray-500 hover:text-primary mr-4"><i class="fa-solid fa-arrow-left text-xl"></i></a>
        <h1 class="text-2xl font-bold text-gray-800"><?= $id ? 'Editar Producto' : 'Crear Producto' ?></h1>
    </div>

    <?php if ($error): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="bg-white rounded-lg shadow-md p-6">
        <form action="" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            <input type="hidden" name="image" value="<?= htmlspecialchars($product['image'] ?? '') ?>">
            
            <h3 class="text-lg font-semibold border-b pb-2 mb-4 text-gray-700">Información Básica</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del Producto *</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($product['name'] ?? '') ?>" required class="w-full border border-gray-300 rounded px-4 py-2 focus:ring-primary focus:border-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Código de Barras *</label>
                    <div class="flex gap-2">
                        <input type="text" name="barcode" id="barcode" value="<?= htmlspecialchars($product['barcode'] ?? '') ?>" required class="w-full border border-gray-300 rounded px-4 py-2 focus:ring-primary focus:border-primary font-mono text-sm">
                        <button type="button" onclick="document.getElementById('barcode').value = 'PROD' + Date.now().toString().slice(-6) + Math.floor(Math.random()*100)" class="bg-gray-200 px-3 py-2 rounded border border-gray-300 hover:bg-gray-300" title="Generar Aleatorio"><i class="fa-solid fa-random"></i></button>
                        <button type="button" onclick="startScanner()" class="bg-gray-800 text-white px-3 py-2 rounded hover:bg-gray-900 transition flex items-center justify-center shadow" title="Escanear Código"><i class="fa-solid fa-camera"></i></button>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Categoría *</label>
                    <select name="category_id" required class="w-full border border-gray-300 rounded px-4 py-2 focus:ring-primary focus:border-primary">
                        <option value="">Seleccione Categoría</option>
                        <?php foreach ($cats as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= ($product['category_id'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Marca</label>
                    <input type="text" name="brand" value="<?= htmlspecialchars($product['brand'] ?? '') ?>" class="w-full border border-gray-300 rounded px-4 py-2 focus:ring-primary focus:border-primary">
                </div>
            </div>

            <h3 class="text-lg font-semibold border-b pb-2 mb-4 mt-6 text-gray-700">Variantes</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Talla</label>
                    <input type="text" name="size" value="<?= htmlspecialchars($product['size'] ?? '') ?>" placeholder="Ej: S, M, L, XL, 32, 34" class="w-full border border-gray-300 rounded px-4 py-2 focus:ring-primary focus:border-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Color</label>
                    <input type="text" name="color" value="<?= htmlspecialchars($product['color'] ?? '') ?>" class="w-full border border-gray-300 rounded px-4 py-2 focus:ring-primary focus:border-primary">
                </div>
            </div>

            <h3 class="text-lg font-semibold border-b pb-2 mb-4 mt-6 text-gray-700">Precios e Inventario</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Precio de Costo</label>
                    <div class="flex">
                        <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500">€</span>
                        <input type="number" step="0.01" min="0" name="cost_price" value="<?= htmlspecialchars($product['cost_price'] ?? 0) ?>" class="w-full rounded-r-md border border-gray-300 px-3 py-2 focus:ring-primary focus:border-primary">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Precio de Venta *</label>
                    <div class="flex">
                        <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500">€</span>
                        <input type="number" step="0.01" min="0" name="selling_price" value="<?= htmlspecialchars($product['selling_price'] ?? '') ?>" required class="w-full rounded-r-md border border-gray-300 px-3 py-2 font-bold focus:ring-primary focus:border-primary">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Stock Actual</label>
                    <input type="number" name="stock" value="<?= htmlspecialchars($product['stock'] ?? 0) ?>" class="w-full border border-gray-300 rounded px-4 py-2 focus:ring-primary focus:border-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Alerta Stock Bajo</label>
                    <input type="number" name="low_stock_threshold" value="<?= htmlspecialchars($product['low_stock_threshold'] ?? 5) ?>" class="w-full border border-gray-300 rounded px-4 py-2 focus:ring-primary focus:border-primary">
                </div>
            </div>

            <div class="mb-6 mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                    <textarea name="description" rows="4" class="w-full border border-gray-300 rounded px-4 py-2 focus:ring-primary focus:border-primary"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Imagen del Producto</label>
                    <?php if (!empty($product['image'])): ?>
                        <div class="mb-2">
                            <img src="<?= BASE_URL . $product['image'] ?>" alt="Imagen Producto" class="h-24 object-contain rounded border border-gray-200">
                        </div>
                    <?php endif; ?>
                    <input type="file" name="image" accept="image/*" class="w-full border border-gray-300 rounded px-4 py-2 bg-gray-50 text-sm focus:ring-primary focus:border-primary">
                    <p class="text-xs text-gray-500 mt-1">Soporta JPG, PNG, GIF</p>
                </div>
            </div>
            
            <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
                <a href="index.php" class="bg-gray-200 text-gray-800 px-4 py-2 rounded hover:bg-gray-300 transition">Cancelar</a>
                <button type="submit" class="bg-primary text-white px-8 py-2 rounded hover:bg-blue-700 shadow transition font-bold"><?= $id ? 'Actualizar Producto' : 'Crear Producto' ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Scanner Modal -->
<div id="scannerModal" class="fixed inset-0 bg-black bg-opacity-75 z-50 hidden flex flex-col pt-10 px-4">
    <div class="flex justify-between items-center mb-4 max-w-lg mx-auto w-full">
        <h2 class="text-white text-xl font-bold">Escanear Código de Barras</h2>
        <button type="button" onclick="stopScanner()" class="text-white text-3xl"><i class="fa-solid fa-times"></i></button>
    </div>
    <div class="bg-black w-full max-w-lg mx-auto rounded overflow-hidden relative shadow-2xl" style="height: 300px;">
        <div id="interactive" class="viewport w-full h-full"></div>
        <div class="absolute inset-0 border-4 border-primary bg-transparent z-10 opacity-50 m-12 pointer-events-none rounded"></div>
    </div>
    <p class="text-center text-white mt-4 max-w-lg mx-auto text-sm">Alinee el código de barras dentro del marco para leer el código de forma automática.</p>
</div>

<script>
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
                if (typeof showToast !== 'undefined') {
                    showToast('Error al acceder a la cámara.', 'error');
                } else {
                    alert('Error al acceder a la cámara. Asegúrese de tener permisos.');
                }
                stopScanner();
                return;
            }
            Quagga.start();
            quaggaRunning = true;
        });

        Quagga.onDetected(function (result) {
            var code = result.codeResult.code;
            document.getElementById('barcode').value = code;
            stopScanner();
            if (typeof showToast !== 'undefined') {
               showToast('Código escaneado: ' + code, 'success');
            } else {
               alert('Código escaneado: ' + code);
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
