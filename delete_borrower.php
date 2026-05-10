<?php
require_once 'config.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$id = $_GET['id'] ?? 0;

if ($id) {
    // First update loans to remove borrower_id
    $pdo->prepare("UPDATE loans SET borrower_id = NULL WHERE borrower_id = ?")->execute([$id]);
    // Then delete borrower
    $pdo->prepare("DELETE FROM borrowers WHERE id = ?")->execute([$id]);
}

header('Location: borrowers.php?deleted=1');
exit;
?>