<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
checkPermission('admin'); // Only admins can delete categories

$category_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($category_id === 0) {
    $_SESSION['error'] = "Invalid category ID";
    header("Location: index.php");
    exit();
}

try {
    // Check if category is used in projects
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM projects WHERE category_id = ?");
    $stmt->execute([$category_id]);
    $projectCount = $stmt->fetchColumn();
    
    if ($projectCount > 0) {
        $_SESSION['error'] = "Cannot delete category - it is being used by $projectCount project(s)";
    } else {
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$category_id]);
        
        if ($stmt->rowCount() > 0) {
            $_SESSION['success'] = "Category deleted successfully";
        } else {
            $_SESSION['error'] = "Category not found";
        }
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
}

header("Location: index.php");
exit();
?>