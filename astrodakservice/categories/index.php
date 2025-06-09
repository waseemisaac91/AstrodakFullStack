<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Initialize auth
$auth = new Auth();

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    header('Location: ../login.php');
    exit();
}

// Check permissions - only allow admin and editor roles
$currentUser = $auth->getCurrentUser();
if (!$auth->hasRole('admin') && !$auth->hasRole('editor')) {
    header('Location: ../unauthorized.php');
    exit();
}

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    if ($auth->hasRole('admin')) {
        try {
            $categoryId = (int)$_GET['id'];
            
            // Check if category is being used by any projects
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM projects WHERE category_id = ?");
            $checkStmt->execute([$categoryId]);
            $projectCount = $checkStmt->fetchColumn();
            
            if ($projectCount > 0) {
                $_SESSION['error'] = "Cannot delete category. It is being used by $projectCount project(s).";
            } else {
                $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
                if ($stmt->execute([$categoryId])) {
                    $_SESSION['success'] = "Category deleted successfully!";
                } else {
                    $_SESSION['error'] = "Failed to delete category.";
                }
            }
        } catch (Exception $e) {
            $_SESSION['error'] = "Error deleting category: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "You don't have permission to delete categories.";
    }
    header('Location: index.php');
    exit();
}

// Get all categories
try {
    $stmt = $pdo->query("SELECT c.*, COUNT(p.id) as project_count 
                        FROM categories c 
                        LEFT JOIN projects p ON c.id = p.category_id 
                        GROUP BY c.id 
                        ORDER BY c.id ASC");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $categories = [];
    $error = "Error loading categories: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories - AstroDak Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
            margin-right: 5px;
        }
        .btn-primary {
            background-color: #007bff;
            color: white;
        }
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        .btn-danger {
            background-color: #dc3545;
            color: white;
        }
        .btn-info {
            background-color: #17a2b8;
            color: white;
        }
        .btn-sm {
            padding: 4px 8px;
            font-size: 12px;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .table th,
        .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        .table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .table tr:hover {
            background-color: #f8f9fa;
        }
        .badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            background-color: #007bff;
            color: white;
        }
        .badge-secondary {
            background-color: #6c757d;
        }
        .actions {
            white-space: nowrap;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }
        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            color: #dee2e6;
        }
        .category-names {
            margin-bottom: 5px;
        }
        .category-name {
            display: block;
            margin-bottom: 2px;
        }
        .category-name.en {
            font-weight: bold;
        }
        .category-name.nl {
            font-size: 0.9em;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-actions">
            <div>
                <h1>Categories Management</h1>
                <p>Manage project categories</p>
            </div>
            <div>
                <a href="../dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
                <a href="add.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add New Category
                </a>
            </div>
        </div>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($_SESSION['success']) ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($_SESSION['error']) ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($categories)): ?>
            <div style="background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Category Name</th>
                            <th>Projects Using</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $category): ?>
                        <tr>
                            <td>#<?= $category['id'] ?></td>
                            <td>
                                <div class="category-names">
                                    <span class="category-name en">
                                        <?= htmlspecialchars($category['name_en']) ?>
                                    </span>
                                    <span class="category-name nl">
                                        <?= htmlspecialchars($category['name_nl']) ?>
                                    </span>
                                </div>
                            </td>
                            <td>
                                <?php if ($category['project_count'] > 0): ?>
                                    <span class="badge">
                                        <?= $category['project_count'] ?> project(s)
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">
                                        No projects
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="actions">
                                <a href="view.php?id=<?= $category['id'] ?>" class="btn btn-sm btn-info" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="edit.php?id=<?= $category['id'] ?>" class="btn btn-sm btn-secondary" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php if ($auth->hasRole('admin')): ?>
                                    <a href="?action=delete&id=<?= $category['id'] ?>" 
                                       class="btn btn-sm btn-danger" 
                                       title="Delete"
                                       onclick="return confirm('Are you sure you want to delete this category? <?= $category['project_count'] > 0 ? 'This category is used by ' . $category['project_count'] . ' project(s) and cannot be deleted.' : 'This action cannot be undone.' ?>')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div style="margin-top: 20px; text-align: center; color: #6c757d;">
                Total: <?= count($categories) ?> categor<?= count($categories) == 1 ? 'y' : 'ies' ?>
            </div>
            
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-tags"></i>
                <h3>No Categories Found</h3>
                <p>You haven't created any categories yet. Get started by adding your first category!</p>
                <a href="add.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add Your First Category
                </a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>