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

// Get category ID
$categoryId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($categoryId <= 0) {
    $_SESSION['error'] = "Invalid category ID.";
    header('Location: index.php');
    exit();
}

// Get category details
try {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$categoryId]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$category) {
        $_SESSION['error'] = "Category not found.";
        header('Location: index.php');
        exit();
    }
} catch (Exception $e) {
    $_SESSION['error'] = "Error loading category: " . $e->getMessage();
    header('Location: index.php');
    exit();
}

// Get projects using this category
try {
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE category_id = ? ORDER BY id ASC");
    $stmt->execute([$categoryId]);
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $projects = [];
    $projectsError = "Error loading projects: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Category: <?= htmlspecialchars($category['name_en']) ?> - AstroDak Admin</title>
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
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .info-card {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .info-item {
            display: flex;
            margin-bottom: 15px;
            align-items: center;
        }
        .info-label {
            font-weight: bold;
            color: #333;
            min-width: 120px;
            margin-right: 15px;
        }
        .info-value {
            color: #666;
        }
        .badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            background-color: #007bff;
            color: white;
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
        .project-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
        }
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #dee2e6;
        }
        .section-title {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            font-size: 20px;
            font-weight: bold;
            color: #333;
        }
        .section-title i {
            margin-right: 10px;
            color: #007bff;
        }
        .category-names {
            display: flex;
            flex-direction: column;
        }
        .category-name {
            margin-bottom: 3px;
        }
        .category-name.en {
            font-weight: bold;
            font-size: 1.1em;
        }
        .category-name.nl {
            color: #666;
            font-size: 0.95em;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-actions">
            <div>
                <h1><i class="fas fa-tag"></i> <?= htmlspecialchars($category['name_en']) ?></h1>
                <p>Category details and associated projects</p>
            </div>
            <div>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Categories
                </a>
                <a href="edit.php?id=<?= $category['id'] ?>" class="btn btn-primary">
                    <i class="fas fa-edit"></i> Edit Category
                </a>
            </div>
        </div>
        
        <?php if (isset($projectsError)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($projectsError) ?>
            </div>
        <?php endif; ?>
        
        <!-- Category Information -->
        <div class="info-card">
            <div class="section-title">
                <i class="fas fa-info-circle"></i>
                Category Information
            </div>
            
            <div class="info-item">
                <div class="info-label">
                    <i class="fas fa-hashtag"></i> ID:
                </div>
                <div class="info-value">
                    #<?= $category['id'] ?>
                </div>
            </div>
            
            <div class="info-item">
                <div class="info-label">
                    <i class="fas fa-tag"></i> Names:
                </div>
                <div class="info-value">
                    <div class="category-names">
                        <span class="category-name en">
                            <strong>EN:</strong> <?= htmlspecialchars($category['name_en']) ?>
                        </span>
                        <span class="category-name nl">
                            <strong>NL:</strong> <?= htmlspecialchars($category['name_nl']) ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="info-item">
                <div class="info-label">
                    <i class="fas fa-project-diagram"></i> Projects:
                </div>
                <div class="info-value">
                    <span class="badge">
                        <?= count($projects) ?> project(s)
                    </span>
                </div>
            </div>
        </div>
        
        <!-- Associated Projects -->
        <div class="section-title">
            <i class="fas fa-folder-open"></i>
            Projects Using This Category
        </div>
        
        <?php if (!empty($projects)): ?>
            <div style="background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Title</th>
                            <th>Description</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($projects as $project): ?>
                        <tr>
                            <td>
                                <?php if (!empty($project['image'])): ?>
                                    <img src="../<?= htmlspecialchars($project['image']) ?>" 
                                         alt="Project Image" class="project-image">
                                <?php else: ?>
                                    <div style="width: 50px; height: 50px; background: #f8f9fa; border-radius: 4px; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-image" style="color: #dee2e6;"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="category-names">
                                    <span class="category-name en">
                                        <strong><?= htmlspecialchars($project['title_en'] ?? 'N/A') ?></strong>
                                    </span>
                                    <?php if (!empty($project['title_nl'])): ?>
                                    <span class="category-name nl">
                                        <?= htmlspecialchars($project['title_nl']) ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <?php 
                                $description = $project['description_en'] ?? '';
                                echo htmlspecialchars(strlen($description) > 100 ? substr($description, 0, 100) . '...' : $description);
                                ?>
                            </td>
                            <td>
                                <a href="../projects/view.php?id=<?= $project['id'] ?>" class="btn btn-sm btn-info" title="View Project">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="../projects/edit.php?id=<?= $project['id'] ?>" class="btn btn-sm btn-secondary" title="Edit Project">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-folder-open"></i>
                <h3>No Projects Found</h3>
                <p>This category is not currently being used by any projects.</p>
                <a href="../projects/add.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Create New Project
                </a>
            </div>
        <?php endif; ?>
        
        <!-- Actions -->
        <div style="margin-top: 30px; text-align: center;">
            <a href="edit.php?id=<?= $category['id'] ?>" class="btn btn-primary">
                <i class="fas fa-edit"></i> Edit This Category
            </a>
            <?php if ($auth->hasRole('admin') && count($projects) == 0): ?>
                <a href="index.php?action=delete&id=<?= $category['id'] ?>" 
                   class="btn btn-danger"
                   onclick="return confirm('Are you sure you want to delete this category? This action cannot be undone.')">
                    <i class="fas fa-trash"></i> Delete Category
                </a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>