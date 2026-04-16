<?php
// pages/reports/expenses.php
require_once __DIR__ . '/../../includes/header.php';
requireAdmin();

$start_date = $_GET['start'] ?? date('Y-m-01');
$end_date = $_GET['end'] ?? date('Y-m-d');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_expense') {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        $error = "Error CSRF.";
    } else {
        $category = sanitizeInput($_POST['category']);
        $description = sanitizeInput($_POST['description']);
        $amount = (float)$_POST['amount'];
        $exp_date = sanitizeInput($_POST['expense_date']);
        $created_by = $_SESSION['user_id'];
        
        if($amount > 0 && $category) {
            $stmt = $pdo->prepare("INSERT INTO expenses (category, description, amount, expense_date, created_by) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$category, $description, $amount, $exp_date, $created_by]);
            header("Location: expenses.php?start=$start_date&end=$end_date&success=Gasto añadido correctamente");
            exit;
        }
    }
}

// Summary
$stmt = $pdo->prepare("SELECT SUM(amount) as total FROM expenses WHERE expense_date >= ? AND expense_date <= ?");
$stmt->execute([$start_date, $end_date]);
$summary = $stmt->fetch();

// List
$stmt = $pdo->prepare("SELECT e.*, u.full_name FROM expenses e JOIN users u ON e.created_by = u.id WHERE expense_date >= ? AND expense_date <= ? ORDER BY expense_date DESC, id DESC");
$stmt->execute([$start_date, $end_date]);
$expenses = $stmt->fetchAll();
?>

<div class="pb-8">
    <div class="flex items-center mb-6">
        <a href="index.php" class="text-gray-500 hover:text-primary mr-4"><i class="fa-solid fa-arrow-left text-xl"></i></a>
        <h1 class="text-2xl font-bold text-gray-800">Reporte de Gastos</h1>
    </div>

    <?php if ($error): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <div class="lg:col-span-2">
            <div class="bg-white p-4 rounded-lg shadow-md mb-6">
                <form action="" method="GET" class="flex flex-wrap items-end gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Desde</label>
                        <input type="date" name="start" value="<?= $start_date ?>" class="border border-gray-300 rounded-md px-4 py-2 focus:ring-primary focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Hasta</label>
                        <input type="date" name="end" value="<?= $end_date ?>" class="border border-gray-300 rounded-md px-4 py-2 focus:ring-primary focus:border-primary">
                    </div>
                    <button type="submit" class="bg-primary text-white px-6 py-2 rounded shadow hover:bg-blue-700 transition">Generar</button>
                    <button type="button" onclick="window.print()" class="bg-gray-800 text-white px-6 py-2 rounded shadow hover:bg-gray-900 transition ml-auto"><i class="fa-solid fa-print"></i></button>
                </form>
            </div>
            
            <div class="bg-orange-50 p-6 rounded-lg shadow-md border-l-4 border-orange-500 mb-6 flex justify-between items-center" id="printable-area-1">
                <div>
                    <p class="text-orange-800 text-sm font-bold uppercase tracking-wide">Total de Gastos Periodo</p>
                    <h3 class="text-3xl font-black text-orange-600 mt-1"><?= formatCurrency(number_format($summary['total'] ?? 0, 2)) ?></h3>
                </div>
                <i class="fa-solid fa-money-bill-wave text-4xl text-orange-200"></i>
            </div>
            
            <div class="bg-white rounded-lg shadow-md overflow-hidden" id="printable-area-2">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Categoría</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Descripción</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Monto</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($expenses as $e): ?>
                            <tr>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500"><?= date('d/m/Y', strtotime($e['expense_date'])) ?></td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-800"><?= htmlspecialchars($e['category']) ?></td>
                                <td class="px-4 py-3 text-sm text-gray-500"><?= htmlspecialchars($e['description']) ?></td>
                                <td class="px-4 py-3 whitespace-nowrap text-right text-sm font-bold text-red-600">-<?= formatCurrency(number_format($e['amount'], 2)) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (count($expenses) === 0): ?>
                            <tr><td colspan="4" class="px-4 py-6 text-center text-gray-500">No hay gastos en este período.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-6 h-fit no-print">
            <h2 class="text-lg font-bold text-gray-800 border-b pb-2 mb-4">Añadir Gasto</h2>
            <form action="" method="POST">
                <input type="hidden" name="action" value="add_expense">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Categoría *</label>
                    <select name="category" required class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                        <option value="Servicios (Luz, Agua, Internet)">Servicios (Luz, Agua, Internet)</option>
                        <option value="Alquiler">Alquiler</option>
                        <option value="Nóminas / Salarios">Nóminas / Salarios</option>
                        <option value="Mantenimiento">Mantenimiento</option>
                        <option value="Marketing / Publicidad">Marketing / Publicidad</option>
                        <option value="Suministros Tienda">Suministros Tienda</option>
                        <option value="Otros Gastos">Otros Gastos</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Monto *</label>
                    <div class="flex">
                        <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500">€</span>
                        <input type="number" step="0.01" min="0" name="amount" required class="w-full rounded-r-md border border-gray-300 px-3 py-2 text-sm font-bold text-red-600 focus:outline-none focus:ring-1 focus:ring-red-500">
                    </div>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha del Gasto *</label>
                    <input type="date" name="expense_date" required value="<?= date('Y-m-d') ?>" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                </div>
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Descripción / Notas</label>
                    <textarea name="description" rows="2" class="w-full border border-gray-300 rounded px-3 py-2 text-sm"></textarea>
                </div>
                
                <button type="submit" class="w-full bg-orange-500 hover:bg-orange-600 border border-transparent rounded shadow px-4 py-2 font-bold text-white transition">
                    <i class="fa-solid fa-plus mr-1"></i> Registrar Gasto
                </button>
            </form>
        </div>
    </div>
</div>

<style>
    @media print {
        body * { visibility: hidden; }
        #printable-area-1, #printable-area-2, #printable-area-1 *, #printable-area-2 * { visibility: visible; }
        #printable-area-1 { position: absolute; left: 0; top: 0; width: 100%; margin-top:20px;}
        #printable-area-2 { position: absolute; left: 0; top: 120px; width: 100%; }
        .no-print { display: none !important; }
    }
</style>

<script>
<?php if(isset($_GET['success'])): ?> showToast('<?= htmlspecialchars($_GET['success']) ?>', 'success'); <?php endif; ?>
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
