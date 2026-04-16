<?php if (isLoggedIn()): ?>
            </div> <!-- container -->
        </main>
    </div> <!-- Flex wrapper -->

    <!-- Mobile Navigation Bottom Bar -->
    <nav class="md:hidden fixed bottom-0 w-full bg-white border-t border-gray-200 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.1)] z-50">
        <div class="flex justify-around py-3 text-sm text-gray-500">
            <a href="<?php echo BASE_URL; ?>/dashboard.php" class="flex flex-col items-center <?= $current_page == 'dashboard.php' ? 'text-primary' : '' ?>">
                <i class="fa-solid fa-chart-line text-lg mb-1"></i>
                <span>Inicio</span>
            </a>
            <a href="<?php echo BASE_URL; ?>/pages/billing/index.php" class="flex flex-col items-center <?= strpos($_SERVER['REQUEST_URI'], '/billing/') !== false ? 'text-primary' : '' ?>">
                <i class="fa-solid fa-cash-register text-lg mb-1"></i>
                <span>POS</span>
            </a>
            <a href="<?php echo BASE_URL; ?>/pages/products/index.php" class="flex flex-col items-center <?= strpos($_SERVER['REQUEST_URI'], '/products/') !== false ? 'text-primary' : '' ?>">
                <i class="fa-solid fa-box text-lg mb-1"></i>
                <span>Prods</span>
            </a>
            <a href="<?php echo BASE_URL; ?>/pages/returns/index.php" class="flex flex-col items-center <?= strpos($_SERVER['REQUEST_URI'], '/returns/') !== false ? 'text-primary' : '' ?>">
                <i class="fa-solid fa-undo text-lg mb-1"></i>
                <span>Dev.</span>
            </a>
            <a href="<?php echo BASE_URL; ?>/logout.php" class="flex flex-col items-center text-red-500">
                <i class="fa-solid fa-sign-out-alt text-lg mb-1"></i>
                <span>Salir</span>
            </a>
        </div>
    </nav>

    <!-- Mobile Slide Over Menu (Optional full menu) -->
    <div id="mobileMenu" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden transition-opacity">
        <div class="w-64 h-full bg-primary text-white p-6 transform -translate-x-full transition-transform duration-300" id="mobileMenuSidebar">
             <!-- Cloned links could go here if more are needed -->
             <div class="flex justify-between items-center mb-8">
                 <h2 class="text-xl font-bold">Menú</h2>
                 <button id="closeMobileMenuBtn" class="text-2xl"><i class="fa-solid fa-times"></i></button>
             </div>
             <ul class="space-y-4 text-lg">
                <li><a href="<?php echo BASE_URL; ?>/dashboard.php"><i class="fa-solid fa-chart-line w-6"></i> Panel de Control</a></li>
                <li><a href="<?php echo BASE_URL; ?>/pages/billing/index.php"><i class="fa-solid fa-cash-register w-6"></i> Ventas</a></li>
                <li><a href="<?php echo BASE_URL; ?>/pages/returns/index.php"><i class="fa-solid fa-undo w-6"></i> Devoluciones</a></li>
                <li><a href="<?php echo BASE_URL; ?>/pages/products/index.php"><i class="fa-solid fa-box w-6"></i> Productos</a></li>
                <li><a href="<?php echo BASE_URL; ?>/pages/categories/index.php"><i class="fa-solid fa-tags w-6"></i> Categorías</a></li>
                <li><a href="<?php echo BASE_URL; ?>/pages/customers/index.php"><i class="fa-solid fa-users w-6"></i> Clientes</a></li>
             </ul>
        </div>
    </div>
<?php endif; ?>

<!-- Notification Toast -->
<div id="toast" class="fixed top-4 right-4 z-50 hidden transform transition-transform duration-300 translate-y-[-100%]">
    <div id="toastContent" class="bg-white px-6 py-4 rounded shadow-lg border-l-4 flex items-center gap-3">
        <i id="toastIcon" class="fa-solid fa-info-circle text-lg"></i>
        <span id="toastMessage" class="font-medium"></span>
    </div>
</div>

<script src="<?php echo BASE_URL; ?>/assets/js/main.js"></script>
</body>
</html>
