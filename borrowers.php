<?php
require_once 'config.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get all borrowers with loan counts
$sql = "SELECT b.*, 
        COUNT(l.id) as loan_count, 
        COALESCE(SUM(l.loan_amount), 0) as total_borrowed
        FROM borrowers b
        LEFT JOIN loans l ON b.id = l.borrower_id
        GROUP BY b.id
        ORDER BY b.created_at DESC";
$borrowers = $pdo->query($sql)->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borrowers - Loan Manager</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="page-header">
    <h1>👥 Borrowers</h1>
    <a href="add_borrower.php" class="btn-primary">➕ Add New Borrower</a>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">Borrower saved successfully!</div>
<?php endif; ?>

<?php if (isset($_GET['deleted'])): ?>
    <div class="alert alert-success">Borrower deleted successfully!</div>
<?php endif; ?>

<div class="search-box">
    <input type="text" id="searchInput" placeholder="Search by name, email, or phone..." class="search-input">
</div>

<div class="loans-card">
    <div class="table-responsive">
        <table class="table" id="borrowersTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Contact</th>
                    <th>Email</th>
                    <th>Loans</th>
                    <th>Total Borrowed</th>
                    <th>Status</th>
                    <th>Registered</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($borrowers as $borrower): ?>
                <tr>
                    <td><?php echo $borrower['id']; ?></td>
                    <td>
                        <strong><?php echo htmlspecialchars($borrower['first_name'] . ' ' . $borrower['last_name']); ?></strong>
                    </td>
                    <td><?php echo htmlspecialchars($borrower['phone'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($borrower['email'] ?? 'N/A'); ?></td>
                    <td><?php echo $borrower['loan_count']; ?></td>
                    <td>$<?php echo number_format($borrower['total_borrowed'], 2); ?></td>
                    <td>
                        <span class="status-badge status-<?php echo $borrower['status']; ?>">
                            <?php echo ucfirst($borrower['status']); ?>
                        </span>
                    </td>
                    <td><?php echo date('M d, Y', strtotime($borrower['created_at'])); ?></td>
                    <td class="actions">
                        <a href="view_borrower.php?id=<?php echo $borrower['id']; ?>" class="btn-view">👁️</a>
                        <a href="edit_borrower.php?id=<?php echo $borrower['id']; ?>" class="btn-edit">✏️</a>
                        <a href="delete_borrower.php?id=<?php echo $borrower['id']; ?>" 
                           class="btn-delete" 
                           onclick="return confirm('Delete this borrower?')">🗑️</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.getElementById('searchInput').addEventListener('keyup', function() {
    let filter = this.value.toLowerCase();
    let rows = document.querySelectorAll('#borrowersTable tbody tr');
    rows.forEach(row => {
        let text = row.textContent.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
    });
});
</script>

</body>
</html>