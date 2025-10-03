<aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <i class="fas fa-chart-line"></i> Income ERP
                </div>
            </div>
            
            <div class="sidebar-menu">
                <div class="sidebar-menu-title">MAIN MENU</div>
                
                <a href="index.php" class="sidebar-menu-item active">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                
                <?php if(hasDepartment('IT/E-Business') || hasDepartment('Accounts')): ?>
                <a href="remittance.php" class="sidebar-menu-item">
                    <i class="fas fa-money-bill-wave"></i> Remittances
                </a>
                <?php endif; ?>
                
                <?php if(hasDepartment('Wealth Creation') || hasDepartment('IT/E-Business') ): ?>
                <a href="post_collection.php" class="sidebar-menu-item">
                    <i class="fas fa-receipt"></i> Post Collections
                </a>
                <?php endif; ?>
                
                <?php if(hasDepartment('Accounts') || hasDepartment('IT/E-Business')): ?>
                <a href="approve_posts.php" class="sidebar-menu-item">
                    <i class="fas fa-check-circle"></i> Approve Posts
                </a>
                <?php endif; ?>
                
                <?php if(hasDepartment('IT/E-Business')): ?>
                <div class="sidebar-menu-title">ADMINISTRATION</div>
                
                <a href="accounts.php" class="sidebar-menu-item">
                    <i class="fas fa-chart-pie"></i> Chart of Accounts
                </a>
                
                <a href="users.php" class="sidebar-menu-item">
                    <i class="fas fa-users"></i> User Management
                </a>
                
                <a href="reports.php" class="sidebar-menu-item">
                    <i class="fas fa-file-alt"></i> Reports
                </a>
                
                <a href="settings.php" class="sidebar-menu-item">
                    <i class="fas fa-cog"></i> Settings
                </a>
                <?php endif; ?>

                 <!-- Add the logout button at the bottom -->
                <div style="margin-top: auto; padding: 1rem;">
                    <a href="logout.php" class="btn btn-danger w-100">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </aside>