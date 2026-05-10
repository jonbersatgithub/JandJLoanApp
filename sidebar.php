<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>

<style>
/* Sidebar Styles */
.sidebar {
    position: fixed;
    top: 0;
    left: 0;
    width: 280px;
    height: 100vh;
    background: linear-gradient(135deg, #2c3e50 0%, #1a1a2e 100%);
    color: white;
    transition: all 0.3s ease;
    z-index: 1000;
    overflow-y: auto;
}

.sidebar-header {
    padding: 25px 20px;
    text-align: center;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.sidebar-header h3 {
    margin: 0;
    font-size: 20px;
}

.sidebar-header p {
    margin: 5px 0 0;
    font-size: 12px;
    opacity: 0.7;
}

.sidebar-menu {
    padding: 20px 0;
}

.menu-item {
    padding: 12px 25px;
    display: flex;
    align-items: center;
    gap: 12px;
    color: rgba(255,255,255,0.8);
    text-decoration: none;
    transition: all 0.3s ease;
    border-left: 3px solid transparent;
}

.menu-item:hover {
    background: rgba(255,255,255,0.1);
    color: white;
    padding-left: 30px;
}

.menu-item.active {
    background: rgba(102, 126, 234, 0.2);
    border-left-color: #667eea;
    color: white;
}

.menu-item i {
    width: 22px;
    font-size: 18px;
}

.menu-item span {
    font-size: 14px;
}

.menu-divider {
    height: 1px;
    background: rgba(255,255,255,0.1);
    margin: 10px 20px;
}

.sidebar-footer {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 20px;
    border-top: 1px solid rgba(255,255,255,0.1);
    font-size: 12px;
    text-align: center;
    opacity: 0.6;
}

/* Toggle Button */
.sidebar-toggle {
    position: fixed;
    top: 20px;
    left: 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    color: white;
    width: 45px;
    height: 45px;
    border-radius: 8px;
    cursor: pointer;
    z-index: 1001;
    display: none;
    font-size: 20px;
}

/* Main Content Adjustment */
.main-content {
    margin-left: 280px;
    min-height: 100vh;
    background: #f5f7fa;
    transition: all 0.3s ease;
}

.main-content-inner {
    padding: 30px;
}

/* Page Header */
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    flex-wrap: wrap;
    gap: 15px;
}

.page-header h1 {
    font-size: 28px;
    color: #333;
    margin: 0;
}

.page-header h1 i {
    color: #667eea;
    margin-right: 10px;
}

/* Buttons */
.btn-primary, .btn-secondary {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    text-decoration: none;
    font-size: 14px;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

/* Search Box */
.search-box {
    margin-bottom: 25px;
}

.search-input {
    width: 100%;
    padding: 12px 15px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 16px;
}

.search-input:focus {
    outline: none;
    border-color: #667eea;
}

/* Responsive */
@media (max-width: 768px) {
    .sidebar {
        transform: translateX(-100%);
    }
    
    .sidebar.mobile-open {
        transform: translateX(0);
    }
    
    .sidebar-toggle {
        display: block;
    }
    
    .main-content {
        margin-left: 0;
    }
    
    .main-content-inner {
        padding: 70px 15px 20px;
    }
}
</style>

<button class="sidebar-toggle" id="sidebarToggle" onclick="toggleSidebar()">
    ☰
</button>

<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h3>Loan Manager</h3>
        <p>Version 2.0</p>
    </div>
    
    <div class="sidebar-menu">
        <a href="dashboard.php" class="menu-item <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
            <i>📊</i>
            <span>Dashboard</span>
        </a>
        
        <a href="borrowers.php" class="menu-item <?php echo $current_page == 'borrowers.php' ? 'active' : ''; ?>">
            <i>👥</i>
            <span>Borrowers</span>
        </a>
        
        <a href="add_borrower.php" class="menu-item">
            <i>➕</i>
            <span>Add Borrower</span>
        </a>
        
        <div class="menu-divider"></div>
        
        <a href="loans.php" class="menu-item">
            <i>💰</i>
            <span>Loans</span>
        </a>
        
        <a href="add_loan.php" class="menu-item">
            <i>➕</i>
            <span>Add Loan</span>
        </a>
        
        <div class="menu-divider"></div>
        
        <a href="logout.php" class="menu-item">
            <i>🚪</i>
            <span>Logout</span>
        </a>
    </div>
    
    <div class="sidebar-footer">
        Loan Management System
    </div>
</div>

<div class="main-content" id="mainContent">
    <div class="main-content-inner">

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    if (window.innerWidth > 768) {
        sidebar.classList.toggle('collapsed');
        document.querySelector('.main-content').classList.toggle('expanded');
    } else {
        sidebar.classList.toggle('mobile-open');
    }
}

// Close sidebar on mobile when clicking outside
document.addEventListener('click', function(event) {
    const sidebar = document.getElementById('sidebar');
    const toggleBtn = document.getElementById('sidebarToggle');
    
    if (window.innerWidth <= 768 && sidebar.classList.contains('mobile-open')) {
        if (!sidebar.contains(event.target) && !toggleBtn.contains(event.target)) {
            sidebar.classList.remove('mobile-open');
        }
    }
});
</script>