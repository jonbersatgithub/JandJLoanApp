<?php
require_once 'config.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$id = $_GET['id'] ?? 0;

// Get borrower details
$stmt = $pdo->prepare("SELECT * FROM borrowers WHERE id = ?");
$stmt->execute([$id]);
$borrower = $stmt->fetch();

if (!$borrower) {
    header('Location: borrowers.php');
    exit;
}

// Get borrower's loans
$stmt = $pdo->prepare("SELECT * FROM loans WHERE borrower_id = ? ORDER BY created_at DESC");
$stmt->execute([$id]);
$loans = $stmt->fetchAll();

$total_loans = count($loans);
$total_amount = array_sum(array_column($loans, 'loan_amount'));
$active_loans = count(array_filter($loans, function($l) { return $l['status'] == 'active'; }));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($borrower['first_name'] . ' ' . $borrower['last_name']); ?> - Loan Manager</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .profile-header {
            background: white;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 30px;
        }
        .profile-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .info-group {
            margin-bottom: 15px;
        }
        .info-label {
            font-weight: bold;
            color: #666;
            margin-bottom: 5px;
        }
        .stats-mini {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        .stat-mini {
            background: #f0f7ff;
            padding: 10px 20px;
            border-radius: 10px;
            text-align: center;
        }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="page-header">
    <h1>👤 Borrower Profile</h1>
    <div>
        <a href="edit_borrower.php?id=<?php echo $borrower['id']; ?>" class="btn-primary">✏️ Edit</a>
        <a href="borrowers.php" class="btn-secondary">← Back</a>
    </div>
</div>

<div class="profile-header">
    <h2><?php echo htmlspecialchars($borrower['first_name'] . ' ' . $borrower['last_name']); ?></h2>
    
    <div class="stats-mini">
        <div class="stat-mini">
            <strong><?php echo $total_loans; ?></strong> Total Loans
        </div>
        <div class="stat-mini">
            <strong><?php echo $active_loans; ?></strong> Active Loans
        </div>
        <div class="stat-mini">
            <strong>$<?php echo number_format($total_amount, 2); ?></strong> Total Borrowed
        </div>
    </div>
    
    <div class="profile-info">
        <div>
            <div class="info-group">
                <div class="info-label">Email</div>
                <div class="info-value"><?php echo htmlspecialchars($borrower['email'] ?? 'N/A'); ?></div>
            </div>
            <div class="info-group">
                <div class="info-label">Phone</div>
                <div class="info-value"><?php echo htmlspecialchars($borrower['phone'] ?? 'N/A'); ?></div>
            </div>
        </div>
        <div>
            <div class="info-group">
                <div class="info-label">Occupation</div>
                <div class="info-value"><?php echo htmlspecialchars($borrower['occupation'] ?? 'N/A'); ?></div>
            </div>
            <div class="info-group">
                <div class="info-label">Monthly Income</div>
                <div class="info-value">$<?php echo number_format($borrower['monthly_income'] ?? 0, 2); ?></div>
            </div>
        </div>
        <div>
            <div class="info-group">
                <div class="info-label">Address</div>
                <div class="info-value"><?php echo nl2br(htmlspecialchars($borrower['address'] ?? 'N/A')); ?></div>
            </div>
            <div class="info-group">
                <div class="info-label">Member Since</div>
                <div class="info-value"><?php echo date('F d, Y', strtotime($borrower['created_at'])); ?></div>
            </div>
        </div>
    </div>
</div>

<div class="loans-card">
    <h3>Loan History</h3>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Loan ID</th>
                    <th>Amount</th>
                    <th>Interest Rate</th>
                    <th>Term</th>
                    <th>Monthly</th>
                    <th>Status</th>
                    <th>Start Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($loans) > 0): ?>
                    <?php foreach ($loans as $loan): ?>
                    <tr>
                        <td><?php echo $loan['id']; ?></td>
                        <td>$<?php echo number_format($loan['loan_amount'], 2); ?></td>
                        <td><?php echo $loan['interest_rate']; ?>%</td>
                        <td><?php echo $loan['loan_term']; ?> mo</td>
                        <td>$<?php echo number_format($loan['monthly_payment'], 2); ?></td>
                        <td><span class="status-badge status-<?php echo $loan['status']; ?>"><?php echo $loan['status']; ?></span></td>
                        <td><?php echo $loan['start_date']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7">No loans found for this borrower</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>