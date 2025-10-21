<aside id="sidebar" class="fixed inset-y-0 left-0 z-30 w-64 bg-white shadow-lg transform transition-transform duration-300 ease-in-out lg:translate-x-0 -translate-x-full pt-16">
    <div class="flex items-center justify-between h-16 px-6 border-b border-gray-200">
        <div class="flex items-center">
            <i class="fas fa-file-invoice text-blue-600 text-2xl"></i>
            <span class="ml-2 text-lg font-semibold text-gray-900">ReqSystem</span>
        </div>
        <button id="sidebar-close" class="p-2 rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-100 lg:hidden">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <nav class="mt-6 px-3">
        <div class="space-y-1">
            <a href="index.php" class="nav-link group flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors duration-200 <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-700' : 'text-gray-700 hover:text-gray-900 hover:bg-gray-50'; ?>">
                <i class="fas fa-home mr-3 text-lg flex-shrink-0"></i>
                Dashboard
            </a>
            
            <a href="create-requisition.php" class="nav-link group flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors duration-200 <?php echo basename($_SERVER['PHP_SELF']) == 'create-requisition.php' ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-700' : 'text-gray-700 hover:text-gray-900 hover:bg-gray-50'; ?>">
                <i class="fas fa-plus mr-3 text-lg flex-shrink-0"></i>
                Create Requisition
            </a>
            
            <a href="requisitions.php" class="nav-link group flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors duration-200 <?php echo basename($_SERVER['PHP_SELF']) == 'requisitions.php' ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-700' : 'text-gray-700 hover:text-gray-900 hover:bg-gray-50'; ?>">
                <i class="fas fa-file-text mr-3 text-lg flex-shrink-0"></i>
                My Requisitions
            </a>
            
            <a href="approvals.php" class="nav-link group flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors duration-200 <?php echo basename($_SERVER['PHP_SELF']) == 'approvals.php' ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-700' : 'text-gray-700 hover:text-gray-900 hover:bg-gray-50'; ?>">
                <i class="fas fa-clock mr-3 text-lg flex-shrink-0"></i>
                Pending Approvals
            </a>
            
            <?php if ($_SESSION['level'] >= 4 || strpos($_SESSION['department'], 'IT/E-Business') !== false): ?>
            <a href="users.php" class="nav-link group flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors duration-200 <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-700' : 'text-gray-700 hover:text-gray-900 hover:bg-gray-50'; ?>">
                <i class="fas fa-users mr-3 text-lg flex-shrink-0"></i>
                User Management
            </a>
            <?php endif; ?>
            
            <a href="reports.php" class="nav-link group flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors duration-200 <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-700' : 'text-gray-700 hover:text-gray-900 hover:bg-gray-50'; ?>">
                <i class="fas fa-chart-bar mr-3 text-lg flex-shrink-0"></i>
                Reports
            </a>
        </div>
    </nav>

    <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-gray-200">
        <div class="text-xs text-gray-500 text-center">
            <p><?php echo APP_NAME; ?></p>
            <p>Version <?php echo APP_VERSION; ?></p>
        </div>
    </div>
</aside>

<!-- Mobile backdrop -->
<div id="sidebar-backdrop" class="fixed inset-0 bg-gray-600 bg-opacity-75 z-20 lg:hidden hidden"></div>

<script>
// Sidebar toggle functionality
const sidebarToggle = document.getElementById('sidebar-toggle');
const sidebarClose = document.getElementById('sidebar-close');
const sidebar = document.getElementById('sidebar');
const backdrop = document.getElementById('sidebar-backdrop');

function openSidebar() {
    sidebar.classList.remove('-translate-x-full');
    backdrop.classList.remove('hidden');
}

function closeSidebar() {
    sidebar.classList.add('-translate-x-full');
    backdrop.classList.add('hidden');
}

if (sidebarToggle) {
    sidebarToggle.addEventListener('click', openSidebar);
}

if (sidebarClose) {
    sidebarClose.addEventListener('click', closeSidebar);
}

if (backdrop) {
    backdrop.addEventListener('click', closeSidebar);
}
</script>