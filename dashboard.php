<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include autoloader
require_once __DIR__ . '/core/Autoloader.php';

// Use the autoloader
use Core\Autoloader;
Autoloader::register();

// Import necessary classes
use Config\Auth;
use Models\Loan;
use Models\User;

// Require login to access this page (NO OUTPUT BEFORE THIS)
Auth::requireLogin();

// Get user information
$userId = Auth::getUserId();
$userRole = Auth::getUserRole();
$userName = Auth::getFullName();
$userUsername = Auth::getUsername();

// Initialize models
$userModel = new User();
$loanModel = new Loan();

// Get statistics based on user role
if ($userRole === 'admin') {
    $statistics = $loanModel->getStatistics();
    $recentLoans = $loanModel->findAll('created_at DESC LIMIT 10');
} else {
    // For non-admin users, show only their loans
    $userLoans = $loanModel->where('user_id = :user_id', [':user_id' => $userId]);
    $statistics = [
        'total_loans' => count($userLoans),
        'active_loans' => count(array_filter($userLoans, function($loan) { 
            return $loan['status'] === 'active'; 
        })),
        'total_amount' => array_sum(array_column($userLoans, 'loan_amount')),
        'total_monthly_payment' => array_sum(array_column($userLoans, 'monthly_payment'))
    ];
    $recentLoans = array_slice($userLoans, 0, 10);
}

// NOW include sidebar - AFTER all PHP logic, BEFORE any HTML output
include 'sidebar.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Loan Management System</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <!-- Theme CSS -->
    <link rel="stylesheet" href="assets/css/themes.css">
    
    <style>
        /* Your existing styles here - they look good */
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
        }
        
        .status-active {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-paid {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .status-defaulted {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
        }
        
        .stat-label {
            color: #666;
            margin-top: 5px;
        }
        
        .loans-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .loans-card h3 {
            margin-bottom: 20px;
            color: #333;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background: #667eea;
            color: white;
            padding: 12px;
            text-align: left;
        }
        
        td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
        }
        
        tr:hover {
            background: #f5f5f5;
        }
        
        .actions {
            display: flex;
            gap: 5px;
        }
        
        .btn-view, .btn-edit, .btn-delete {
            padding: 4px 8px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 12px;
            cursor: pointer;
            border: none;
        }
        
        .btn-view { background: #17a2b8; color: white; }
        .btn-edit { background: #ffc107; color: #333; }
        .btn-delete { background: #dc3545; color: white; }
        
        .search-box {
            margin-bottom: 20px;
        }
        
        .search-input {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }
    </style>
</head>
<body>

<div class="page-header">
    <h1>📊 Dashboard</h1>
    <p>Welcome, <?php echo htmlspecialchars($userName); ?>!</p>
</div>

<!-- Statistics Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-value"><?php echo $statistics['total_loans']; ?></div>
        <div class="stat-label">Total Loans</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo $statistics['active_loans']; ?></div>
        <div class="stat-label">Active Loans</div>
    </div>
    <div class="stat-card">
        <div class="stat-value">₱<?php echo number_format($statistics['total_amount'], 2); ?></div>
        <div class="stat-label">Total Amount</div>
    </div>
    <div class="stat-card">
        <div class="stat-value">₱<?php echo number_format($statistics['total_monthly_payment'], 2); ?></div>
        <div class="stat-label">Monthly Collection</div>
    </div>
</div>

<!-- Search Box -->
<div class="search-box">
    <input type="text" id="searchInput" class="search-input" placeholder="Search by borrower name...">
</div>

<!-- Recent Loans Table -->
<div class="loans-card">
    <h3>Recent Loans</h3>
    <div class="table-responsive">
        <table id="loansTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Borrower</th>
                    <th>Amount</th>
                    <th>Interest Rate</th>
                    <th>Term</th>
                    <th>Monthly Payment</th>
                    <th>Status</th>
                    <th>Start Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($recentLoans as $loan): ?>
                <tr>
                    <td><?php echo $loan['id']; ?></td>
                    <td><strong><?php echo htmlspecialchars($loan['borrower_name']); ?></strong></td>
                    <td>₱<?php echo number_format($loan['loan_amount'], 2); ?></td>
                    <td><?php echo $loan['interest_rate']; ?>%</td>
                    <td><?php echo $loan['loan_term']; ?> mo</td>
                    <td>₱<?php echo number_format($loan['monthly_payment'], 2); ?></td>
                    <td><span class="status-badge status-<?php echo $loan['status']; ?>"><?php echo $loan['status']; ?></span></td>
                    <td><?php echo $loan['start_date']; ?></td>
                    <td class="actions">
                        <a href="view_loan.php?id=<?php echo $loan['id']; ?>" class="btn-view">👁️</a>
                        <a href="edit_loan.php?id=<?php echo $loan['id']; ?>" class="btn-edit">✏️</a>
                        <button class="btn-delete" onclick="deleteLoan(<?php echo $loan['id']; ?>)">🗑️</button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($recentLoans)): ?>
                <tr>
                    <td colspan="9" style="text-align: center;">No loans found. Add your first loan!</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script>
// Search functionality
document.getElementById('searchInput').addEventListener('keyup', function() {
    let filter = this.value.toLowerCase();
    let rows = document.querySelectorAll('#loansTable tbody tr');
    
    rows.forEach(row => {
        if (row.querySelector('td[colspan]')) return;
        let borrowerName = row.cells[1]?.textContent.toLowerCase() || '';
        row.style.display = borrowerName.includes(filter) ? '' : 'none';
    });
});

function deleteLoan(id) {
    if (confirm('Are you sure you want to delete this loan?')) {
        window.location.href = 'delete_loan.php?id=' + id;
    }
}
</script>

</div> <!-- Close main-content-inner -->
</div> <!-- Close main-content -->

</body>
</html>