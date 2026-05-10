<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include autoloader
require_once __DIR__ . '/core/Autoloader.php';
include_once 'sidebar.php';

// Use the autoloader
use Core\Autoloader;
Autoloader::register();

// Import necessary classes
use Config\Auth;
use Models\Loan;
use Models\User;

// Require login to access this page
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
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
    <!-- Theme CSS -->
    <link rel="stylesheet" href="assets/css/themes.css">
    
    <style>
        .navbar-brand {
            font-weight: bold;
        }
        
        .welcome-section {
            margin-bottom: 2rem;
        }
        
        .action-buttons .btn {
            margin: 0 2px;
            padding: 4px 8px;
            font-size: 12px;
        }
        
        .stat-card {
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        @media (max-width: 768px) {
            .stat-card {
                margin-bottom: 1rem;
            }
        }
        
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
    </style>
</head>

<!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="assets/js/theme-manager.js"></script>
    <script src="assets/js/api.js"></script>
    
    <script>
        let dataTable;
        
        $(document).ready(function() {
            // Check if table has data rows (not just the no-data row with colspan)
            const hasDataRows = $('#loansTable tbody tr').length > 1 || 
                               ($('#loansTable tbody tr').length === 1 && !$('#loansTable tbody tr td').attr('colspan'));
            
            if (hasDataRows) {
                // Initialize DataTable
                dataTable = $('#loansTable').DataTable({
                    pageLength: 10,
                    order: [[0, 'desc']],
                    language: {
                        search: "Search:",
                        lengthMenu: "Show _MENU_ entries",
                        info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    }
                });
            }
            
            // Calculate monthly payment preview
            $('#loanAmount, #interestRate, #loanTerm, #interestType').on('input', calculateMonthlyPreview);
            
            // Form submissions
            $('#loanForm').on('submit', handleLoanSubmit);
            $('#paymentForm').on('submit', handlePaymentSubmit);
            $('#changePasswordForm').on('submit', handlePasswordChange);
            
            // Search functionality - only if DataTable is initialized
            if (hasDataRows) {
                $('#searchInput').on('keyup', function() {
                    dataTable.search(this.value).draw();
                });
            }
        });
        
        function calculateMonthlyPreview() {
            const amount = parseFloat($('#loanAmount').val()) || 0;
            const rate = parseFloat($('#interestRate').val()) || 0;
            const term = parseInt($('#loanTerm').val()) || 0;

            const interestType = $('#interestType').val();

            
            if (amount > 0 && term > 0) {

                let payment = 0;

               switch (interestType) {
                    case 'flat':
                        // const totalInterest = amount * (rate / 100) * (term / 12);
                        // const totalPayment = amount + totalInterest;
                        // payment = totalPayment / term;

                        const totalInterest = amount * (rate / 100);
                        const totalPayment = amount + totalInterest;
                        payment = totalPayment / term;

                        break;
                    case 'add_on': 
                        const addOnInterest = amount * (rate / 100);
                        const addOnPayment = (amount + addOnInterest) / term;
                        payment = addOnPayment;

                    break;                  
                    default:
                        payment = 0;           
                }
                $('#hideMonthlyAmt').val(payment.toFixed(2));
                $('#monthlyPaymentPreview').text(payment.toFixed(2));
            } else {
                $('#hideMonthlyAmt').val('0.00');
                $('#monthlyPaymentPreview').text('0.00');
            }
        }
        
        function showAddLoanModal() {
            $('#modalTitle').text('Add New Loan');
            $('#loanForm')[0].reset();
            $('#loanId').val('');
            $('#startDate').val(new Date().toISOString().split('T')[0]);
            calculateMonthlyPreview();
            $('#loanModal').modal('show');
        }
        
        function editLoan(id) {
            $('#modalTitle').text('Edit Loan');
            $('#loanId').val(id);
            // Fetch loan data and populate form
            $.ajax({
                url: `api/get_loans.php?id=${id}`,
                method: 'GET',
                success: function(response) {
                    const data = JSON.parse(response);
                    if(data.success) {
                        $('#borrowerName').val(data.loan.borrower_name);
                        $('#loanAmount').val(data.loan.loan_amount);
                        $('#interestRate').val(data.loan.interest_rate);
                        $('#loanTerm').val(data.loan.loan_term);
                        $('#startDate').val(data.loan.start_date);
                        $('#status').val(data.loan.status);
                        $('#interestType').val(data.loan.interest_type);
                        calculateMonthlyPreview();
                        $('#loanModal').modal('show');
                    }
                }
            });
        }
        
        async function handleLoanSubmit(e) {
            
          
            e.preventDefault();
            const loanId = $('#loanId').val();
            const formData = {
                borrower_name: $('#borrowerName').val(),
                loan_amount: parseFloat($('#loanAmount').val()),
                interest_rate: parseFloat($('#interestRate').val()),
                loan_term: parseInt($('#loanTerm').val()),
                start_date: $('#startDate').val(),
                status: $('#status').val(),
                interest_type: $('#interestType').val(),
                monthly_payment: parseFloat($('#hideMonthlyAmt').val())
            };

            
            try {
                let response;
                if (loanId) {
                    response = await LoanAPI.updateLoan(loanId, formData);
                } else {

                    // alert(JSON.stringify(formData));
                    response = await LoanAPI.createLoan(formData);
                }
                
                if (response.success) {
                    $('#loanModal').modal('hide');
                    location.reload();
                } else {
                    alert('Error saving loan');
                }
            } catch (error) {
                alert('Error: ' + error.message);
            }
        }
        
        function deleteLoan(id) {
            if (confirm('Are you sure you want to delete this loan?')) {
                $.ajax({
                    url: 'delete_loan.php',
                    method: 'POST',
                    data: { id: id },
                    success: function(response) {
                        const data = JSON.parse(response);
                        if(data.success) {
                            location.reload();
                        } else {
                            alert('Error deleting loan');
                        }
                    }
                });
            }
        }
        
        function recordPayment(loanId) {
            $('#paymentLoanId').val(loanId);
            $('#paymentModal').modal('show');
        }
        
        async function handlePaymentSubmit(e) {
            e.preventDefault();
            const loanId = $('#paymentLoanId').val();
            const paymentData = {
                amount: parseFloat($('#paymentAmount').val()),
                payment_date: $('#paymentDate').val()
            };
            
            try {
                const response = await LoanAPI.makePayment(loanId, paymentData);
                if (response.success) {
                    $('#paymentModal').modal('hide');
                    location.reload();
                } else {
                    alert('Error recording payment');
                }
            } catch (error) {
                alert('Error: ' + error.message);
            }
        }
        
        function viewLoan(id) {
            window.open(`view_loan.php?id=${id}`, '_blank');
        }
        
        function showProfile() {
            alert('Profile: <?php echo htmlspecialchars($userName); ?>\nRole: <?php echo ucfirst($userRole); ?>');
        }
        
        function changePassword() {
            $('#changePasswordModal').modal('show');
        }
        
        async function handlePasswordChange(e) {
            e.preventDefault();
            const currentPassword = $('#currentPassword').val();
            const newPassword = $('#newPassword').val();
            const confirmPassword = $('#confirmNewPassword').val();
            
            if (newPassword !== confirmPassword) {
                alert('New passwords do not match');
                return;
            }
            
            if (newPassword.length < 6) {
                alert('Password must be at least 6 characters');
                return;
            }
            
            try {
                const response = await fetch('/api/auth/change-password', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        current_password: currentPassword,
                        new_password: newPassword
                    })
                });
                const data = await response.json();
                
                if (data.success) {
                    alert('Password changed successfully!');
                    $('#changePasswordModal').modal('hide');
                    $('#changePasswordForm')[0].reset();
                } else {
                    alert(data.message || 'Error changing password');
                }
            } catch (error) {
                alert('Error: ' + error.message);
            }
        }
        
        function exportToCSV() {
            window.location.href = 'export_loans.php';
        }
    </script>

<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-bank2"></i> Loan Management System
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" onclick="showAddLoanModal()">
                            <i class="bi bi-plus-circle"></i> Add Loan
                        </a>
                    </li>
                    <?php if ($userRole === 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="settings.php">
                            <i class="bi bi-gear"></i> Settings
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($userName); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#" onclick="showProfile()">
                                <i class="bi bi-person"></i> Profile
                            </a></li>
                            <li><a class="dropdown-item" href="#" onclick="changePassword()">
                                <i class="bi bi-key"></i> Change Password
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Welcome Section -->
        <div class="alert alert-info welcome-section">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h4>Welcome back, <?php echo htmlspecialchars($userName); ?>!</h4>
                    <p class="mb-0">Here's what's happening with your loan portfolio today.</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <span class="badge bg-secondary">
                        <i class="bi bi-calendar"></i> <?php echo date('l, F j, Y'); ?>
                    </span>
                    <span class="badge bg-info ms-2">
                        <i class="bi bi-person-badge"></i> <?php echo ucfirst($userRole); ?>
                    </span>
                </div>
            </div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="row mb-4" id="statsGrid">
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card stat-card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted small">Total Loans</div>
                                <h3 class="mt-2 mb-0" id="totalLoans"><?php echo $statistics['total_loans']; ?></h3>
                            </div>
                            <i class="bi bi-folder2-open fs-1 text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card stat-card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted small">Active Loans</div>
                                <h3 class="mt-2 mb-0" id="activeLoans"><?php echo $statistics['active_loans']; ?></h3>
                            </div>
                            <i class="bi bi-check-circle fs-1 text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card stat-card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted small">Total Amount</div>
                                <h3 class="mt-2 mb-0" id="totalAmount">$<?php echo number_format($statistics['total_amount'], 2); ?></h3>
                            </div>
                            <i class="bi bi-currency-dollar fs-1 text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card stat-card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted small">Monthly Collection</div>
                                <h3 class="mt-2 mb-0" id="monthlyCollection">$<?php echo number_format($statistics['total_monthly_payment'], 2); ?></h3>
                            </div>
                            <i class="bi bi-cash-stack fs-1 text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Search and Actions -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text bg-transparent">
                                <i class="bi bi-search"></i>
                            </span>
                            <input type="text" id="searchInput" class="form-control" 
                                   placeholder="Search by borrower name or loan ID...">
                        </div>
                    </div>
                    <div class="col-md-6 text-md-end mt-3 mt-md-0">
                        <button class="btn btn-primary" onclick="showAddLoanModal()">
                            <i class="bi bi-plus-circle"></i> Add New Loan
                        </button>
                        <button class="btn btn-success" onclick="exportToCSV()">
                            <i class="bi bi-download"></i> Export
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Loans Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-table"></i> All Loans</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="loansTable" class="table table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Borrower</th>
                                <th>Loan Amount</th>
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
                                <td>$<?php echo number_format($loan['loan_amount'], 2); ?></td>
                                <td><?php echo $loan['interest_rate']; ?>%</td>
                                <td><?php echo $loan['loan_term']; ?> mo</td>
                                <td>$<?php echo number_format($loan['monthly_payment'], 2); ?></td>
                                <td><span class="status-badge status-<?php echo $loan['status']; ?>"><?php echo $loan['status']; ?></span></td>
                                <td><?php echo $loan['start_date']; ?></td>
                                <td class="action-buttons">
                                    <button class="btn btn-sm btn-info" onclick="viewLoan(<?php echo $loan['id']; ?>)">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-warning" onclick="editLoan(<?php echo $loan['id']; ?>)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-success" onclick="recordPayment(<?php echo $loan['id']; ?>)">
                                        <i class="bi bi-cash"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="deleteLoan(<?php echo $loan['id']; ?>)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($recentLoans)): ?>
                            <tr>
                                <td colspan="9" class="text-center">No loans found. Click "Add New Loan" to create one.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Loan Modal -->
    <div class="modal fade" id="loanModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add New Loan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="loanForm">
                        <input type="hidden" id="loanId">
                        <div class="mb-3">
                            <label class="form-label">Borrower Name *</label>
                            <input type="text" class="form-control" id="borrowerName" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Loan Amount (₱) *</label>
                                <input type="number" class="form-control" id="loanAmount" step="0.01" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Interest Rate (%) *</label>
                                <input type="number" class="form-control" id="interestRate" step="0.01" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Interest Type *</label>
                            <select class="form-select" id="interestType" required>
                                <option value="add_on">Add-On</option>
                                <option value="flat">Flat Rate</option>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Loan Term (months) *</label>
                                <input type="number" class="form-control" id="loanTerm" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Start Date *</label>
                                <input type="date" class="form-control" id="startDate" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" id="status">
                                <option value="active">Active</option>
                                <option value="paid">Paid</option>
                                <option value="defaulted">Defaulted</option>
                            </select>
                        </div>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            Monthly Payment: <strong>₱<span id="monthlyPaymentPreview">0.00</span></strong>
                            <input type="hidden" id="hideMonthlyAmt" value="">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Save Loan</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Record Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="paymentForm">
                        <input type="hidden" id="paymentLoanId">
                        <div class="mb-3">
                            <label class="form-label">Payment Amount ($) *</label>
                            <input type="number" class="form-control" id="paymentAmount" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Payment Date</label>
                            <input type="date" class="form-control" id="paymentDate" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <button type="submit" class="btn btn-success w-100">Record Payment</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Change Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="changePasswordForm">
                        <div class="mb-3">
                            <label class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="currentPassword" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" class="form-control" id="newPassword" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirmNewPassword" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Update Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    
</body>
</html>