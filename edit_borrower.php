<?php
require_once 'config.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM borrowers WHERE id = ?");
$stmt->execute([$id]);
$borrower = $stmt->fetch();

if (!$borrower) {
    header('Location: borrowers.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';
    $city = $_POST['city'] ?? '';
    $state = $_POST['state'] ?? '';
    $zip_code = $_POST['zip_code'] ?? '';
    $occupation = $_POST['occupation'] ?? '';
    $monthly_income = $_POST['monthly_income'] ?? 0;
    $id_number = $_POST['id_number'] ?? '';
    $status = $_POST['status'] ?? 'active';
    
    if (empty($first_name) || empty($last_name)) {
        $error = 'First name and last name are required';
    } else {
        $stmt = $pdo->prepare("UPDATE borrowers SET 
                first_name = ?, last_name = ?, email = ?, phone = ?, address = ?, 
                city = ?, state = ?, zip_code = ?, occupation = ?, monthly_income = ?, 
                id_number = ?, status = ? WHERE id = ?");
        
        $result = $stmt->execute([
            $first_name, $last_name, $email, $phone, $address, 
            $city, $state, $zip_code, $occupation, $monthly_income, 
            $id_number, $status, $id
        ]);
        
        if ($result) {
            header('Location: borrowers.php?updated=1');
            exit;
        } else {
            $error = 'Failed to update borrower';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Borrower - Loan Manager</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .form-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            padding: 30px;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .form-group.full-width {
            grid-column: span 2;
        }
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            .form-group.full-width {
                grid-column: span 1;
            }
        }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="page-header">
    <h1>✏️ Edit Borrower</h1>
    <a href="borrowers.php" class="btn-secondary">← Back</a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="form-container">
    <form method="POST">
        <div class="form-grid">
            <div class="form-group">
                <label>First Name *</label>
                <input type="text" name="first_name" value="<?php echo htmlspecialchars($borrower['first_name']); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Last Name *</label>
                <input type="text" name="last_name" value="<?php echo htmlspecialchars($borrower['last_name']); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($borrower['email']); ?>">
            </div>
            
            <div class="form-group">
                <label>Phone</label>
                <input type="text" name="phone" value="<?php echo htmlspecialchars($borrower['phone']); ?>">
            </div>
            
            <div class="form-group full-width">
                <label>Address</label>
                <textarea name="address" rows="2"><?php echo htmlspecialchars($borrower['address']); ?></textarea>
            </div>
            
            <div class="form-group">
                <label>City</label>
                <input type="text" name="city" value="<?php echo htmlspecialchars($borrower['city']); ?>">
            </div>
            
            <div class="form-group">
                <label>State/Province</label>
                <input type="text" name="state" value="<?php echo htmlspecialchars($borrower['state']); ?>">
            </div>
            
            <div class="form-group">
                <label>ZIP Code</label>
                <input type="text" name="zip_code" value="<?php echo htmlspecialchars($borrower['zip_code']); ?>">
            </div>
            
            <div class="form-group">
                <label>ID Number</label>
                <input type="text" name="id_number" value="<?php echo htmlspecialchars($borrower['id_number']); ?>">
            </div>
            
            <div class="form-group">
                <label>Occupation</label>
                <input type="text" name="occupation" value="<?php echo htmlspecialchars($borrower['occupation']); ?>">
            </div>
            
            <div class="form-group">
                <label>Monthly Income ($)</label>
                <input type="number" name="monthly_income" step="0.01" value="<?php echo $borrower['monthly_income']; ?>">
            </div>
            
            <div class="form-group">
                <label>Status</label>
                <select name="status">
                    <option value="active" <?php echo $borrower['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $borrower['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
        </div>
        
        <button type="submit" class="btn-primary" style="width: 100%; justify-content: center; margin-top: 20px;">Update Borrower</button>
    </form>
</div>

</body>
</html>