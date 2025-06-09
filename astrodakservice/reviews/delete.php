<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/review.php';

$auth = new Auth();

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    header('Location: ../login.php');
    exit();
}

// Check permissions - only allow admin and editor roles
if (!$auth->hasRole('admin') && !$auth->hasRole('editor')) {
    header('Location: ../unauthorized.php');
    exit();
}

$review_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($review_id === 0) {
    $_SESSION['error'] = "Invalid review ID";
    header("Location: index.php");
    exit();
}

try {
    $stmt = $pdo->prepare("DELETE FROM reviews WHERE id = ?");
    $stmt->execute([$review_id]);
    
    $_SESSION['success'] = "Review deleted successfully!";
} catch (PDOException $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
}

// Redirect back to the appropriate page
$status = isset($_GET['status']) ? $_GET['status'] : 'pending';
header("Location: index.php?status=$status");
exit();
?>