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

$errors = [];
$name_en = '';
$name_nl = '';


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
    
   
    
    // Check for duplicate names
    if (empty($errors)) {
        try {
            // Check English name
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE name_en = ?");
            $stmt->execute([$name_en]);
            if ($stmt->fetchColumn() > 0) {
                $errors[] = "A category with this English name already exists.";
            }
            
            // Check Dutch name
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE name_nl = ?");
            $stmt->execute([$name_nl]);
            if ($stmt->fetchColumn() > 0) {
                $errors[] = "A category with this Dutch name already exists.";
            }
        } catch (Exception $e) {
            $errors[] = "Error checking for duplicate names: " . $e->getMessage();
        }
    }
    
    // Insert category if no errors
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO categories (name_en, name_nl) VALUES (?, ?)");
            if ($stmt->execute([$name_en, $name_nl])) {
                $_SESSION['success'] = "Category '$name_en' created successfully!";
                header('Location: index.php');
                exit();
            } else {
                $errors[] = "Failed to create category.";
            }
        } catch (Exception $e) {
            $errors[] = "Error creating category: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Category - AstroDak Admin</title>
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
                <h1>Add New Category</h1>
                <p>Create a new project category</p>
            </div>
            <div>
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
                        <i class="fas fa-save"></i> Create Category
                    </button>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
        
        <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 4px; font-size: 14px; color: #6c757d;">
            <i class="fas fa-info-circle"></i>
            <strong>Note:</strong> Once created, this category can be assigned to projects. Make sure to choose descriptive names that clearly represent the type of projects it will contain.
        </div>
    </div>
</body>
</html>