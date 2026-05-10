<?php
header('Content-Type: application/json');
require_once '../config/database.php';

try {
    $stmt = $pdo->query("SELECT 
        COUNT(*) as total_loans,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_loans,
        COALESCE(SUM(loan_amount), 0) as total_amount,
        COALESCE(SUM(CASE WHEN status = 'active' THEN monthly_payment ELSE 0 END), 0) as total_monthly_payment
        FROM loans");
    
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $stats
    ]);
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>