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

$errors = [];
$name_en = $category['name_en'];
$name_nl = $category['name_nl'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name_en = trim($_POST['name_en'] ?? '');
    $name_nl = trim($_POST['name_nl'] ?? '');
 
    
    // Validation
    if (empty($name_en)) {
        $errors[] = "English category name is required.";
    } elseif (strlen($name_en) > 100) {
        $errors[] = "English category name must be 100 characters or less.";
    }
    
    if (empty($name_nl)) {
        $errors[] = "Dutch category name is required.";
    } elseif (strlen($name_nl) > 100) {
        $errors[] = "Dutch category name must be 100 characters or less.";
    }
 
    
    // Check for duplicate names (excluding current category)
    if (empty($errors)) {
        try {
            // Check English name
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE name_en = ? AND id != ?");
            $stmt->execute([$name_en, $categoryId]);
            if ($stmt->fetchColumn() > 0) {
                $errors[] = "A category with this English name already exists.";
            }
            
            // Check Dutch name
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE name_nl = ? AND id != ?");
            $stmt->execute([$name_nl, $categoryId]);
            if ($stmt->fetchColumn() > 0) {
                $errors[] = "A category with this Dutch name already exists.";
            }
        } catch (Exception $e) {
            $errors[] = "Error checking for duplicate names: " . $e->getMessage();
        }
    }
    
    // Update category if no errors
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE categories SET name_en = ?, name_nl = ? WHERE id = ?");
            if ($stmt->execute([$name_en, $name_nl, $categoryId])) {
                $_SESSION['success'] = "Category '$name_en' updated successfully!";
                header('Location: view.php?id=' . $categoryId);
                exit();
            } else {
                $errors[] = "Failed to update category.";
            }
        } catch (Exception $e) {
            $errors[] = "Error updating category: " . $e->getMessage();
        }
    }
}

// Get projects using this category
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM projects WHERE category_id = ?");
    $stmt->execute([$categoryId]);
    $projectCount = $stmt->fetchColumn();
} catch (Exception $e) {
    $projectCount = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Category: <?= htmlspecialchars($category['name_en']) ?> - AstroDak Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .container {
            max-width: 800px;
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
        .btn-success {
            background-color: #28a745;
            color: white;
        }
        .btn-info {
            background-color: #17a2b8;
            color: white;
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
        .alert-info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .form-container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
        }
        .form-control:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 5px rgba(0,123,255,0.25);
        }
        .form-text {
            font-size: 12px;
            color: #6c757d;
            margin-top: 5px;
        }
        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }
        .info-box {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .info-box h4 {
            margin: 0 0 10px 0;
            color: #495057;
        }
        .info-item {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
        }
        .info-item:last-child {
            margin-bottom: 0;
        }
        .info-label {
            font-weight: bold;
            min-width: 80px;
            margin-right: 10px;
        }
        .badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            background-color: #007bff;
            color: white;
        }
        textarea.form-control {
            min-height: 80px;
            resize: vertical;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-actions">
            <div>
                <h1>Edit Category</h1>
                <p>Modify category details</p>
            </div>
            <div>
                <a href="view.php?id=<?= $categoryId ?>" class="btn btn-info">
                    <i class="fas fa-eye"></i> View Category
                </a>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Categories
                </a>
            </div>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Please fix the following errors:</strong>
                <ul style="margin: 10px 0 0 20px;">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <!-- Category Information -->
        <div class="info-box">
            <h4><i class="fas fa-info-circle"></i> Category Information</h4>
            <div class="info-item">
                <div class="info-label">ID:</div>
                <div>#<?= $category['id'] ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Projects:</div>
                <div>
                    <span class="badge">
                        <?= $projectCount ?> project(s) using this category
                    </span>
                </div>
            </div>
        </div>
        
        <?php if ($projectCount > 0): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <strong>Note:</strong> This category is currently being used by <?= $projectCount ?> project(s). 
                Changing the name will update the category name for all associated projects.
            </div>
        <?php endif; ?>
        
        <div class="form-container">
            <form method="POST" action="">
                <div class="form-group">
                    <label for="name_en">
                        <i class="fas fa-tag"></i> Category Name (English) *
                    </label>
                    <input 
                        type="text" 
                        id="name_en" 
                        name="name_en" 
                        class="form-control" 
                        value="<?= htmlspecialchars($name_en) ?>"
                        placeholder="Enter English category name..."
                        maxlength="100"
                        required
                        autofocus
                    >
                    <div class="form-text">
                        English category name must be unique and no more than 100 characters.
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="name_nl">
                        <i class="fas fa-tag"></i> Category Name (Dutch) *
                    </label>
                    <input 
                        type="text" 
                        id="name_nl" 
                        name="name_nl" 
                        class="form-control" 
                        value="<?= htmlspecialchars($name_nl) ?>"
                        placeholder="Enter Dutch category name..."
                        maxlength="100"
                        required
                    >
                    <div class="form-text">
                        Dutch category name must be unique and no more than 100 characters.
                    </div>
                </div>
                
               
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Update Category
                    </button>
                    <a href="view.php?id=<?= $categoryId ?>" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
        
        <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 4px; font-size: 14px; color: #6c757d;">
            <i class="fas fa-lightbulb"></i>
            <strong>Tip:</strong> Choose descriptive names that clearly represent the type of projects this category will contain. 
            The names will be displayed throughout the system.
        </div>
    </div>
</body>
</html>