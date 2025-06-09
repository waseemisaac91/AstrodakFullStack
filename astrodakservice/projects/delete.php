<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
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

$project_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($project_id === 0) {
    $_SESSION['error'] = "Invalid project ID";
    header("Location: index.php");
    exit();
}

// Fetch project to get image path
$stmt = $pdo->prepare("SELECT image FROM projects WHERE id = ?");
$stmt->execute([$project_id]);
$project = $stmt->fetch();

if ($project) {
    // Delete associated images
    if (!empty($project['image'])) {
        deleteFile($project['image']);
    }
    
    // Delete project images from project_images table
    $stmt = $pdo->prepare("SELECT image FROM project_images WHERE project_id = ?");
    $stmt->execute([$project_id]);
    $project_images = $stmt->fetchAll();
    
    foreach ($project_images as $image) {
        deleteFile($image['image']);
    }
    
    // Delete from database
    $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ?");
    $stmt->execute([$project_id]);
    
    $stmt = $pdo->prepare("DELETE FROM project_images WHERE project_id = ?");
    $stmt->execute([$project_id]);
    
    $_SESSION['success'] = "Project deleted successfully";
} else {
    $_SESSION['error'] = "Project not found";
}

header("Location: index.php");
exit();
?>