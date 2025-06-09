<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/project.php';

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

$projectModel = new Project();
$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate required fields
        if (empty($_POST['title_nl']) || empty($_POST['title_en']) || empty($_POST['category_id'])) {
            throw new Exception('Title (both languages) and category are required.');
        }

        $title_nl = trim($_POST['title_nl']);
        $title_en = trim($_POST['title_en']);
        $category_id = (int)$_POST['category_id'];
        $description_nl = trim($_POST['description_nl'] ?? '');
        $description_en = trim($_POST['description_en'] ?? '');
        
        // Handle file upload
        $image = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            // Basic file validation
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $maxSize = 5 * 1024 * 1024; // 5MB
            
            if (!in_array($_FILES['image']['type'], $allowedTypes)) {
                throw new Exception('Invalid image type. Only JPEG, PNG, GIF, and WebP are allowed.');
            }
            
            if ($_FILES['image']['size'] > $maxSize) {
                throw new Exception('File size too large. Maximum size is 5MB.');
            }
            
            // Create uploads directory if it doesn't exist
            $uploadDir = '../uploads/projects/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Generate unique filename
            $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = uniqid('project_') . '.' . $extension;
            $uploadPath = $uploadDir . $filename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
                $image = 'uploads/projects/' . $filename;
            } else {
                throw new Exception('Failed to upload image.');
            }
        }
        
        // Insert into database
        $stmt = $pdo->prepare("INSERT INTO projects (title_nl, title_en, category_id, description_nl, description_en, image) 
                              VALUES (?, ?, ?, ?, ?, ?)");
        $result = $stmt->execute([$title_nl, $title_en, $category_id, $description_nl, $description_en, $image]);
    
        if ($result) {
            $_SESSION['success'] = "Project added successfully!";
            header('Location: index.php');
            exit();
        } else {
            throw new Exception('Failed to save project to database.');
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get categories for the dropdown
try {
    $categories = $pdo->query("SELECT * FROM categories ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
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
    <title>Add New Project - AstroDak Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .form-control:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
            margin-right: 10px;
        }
        .btn-primary {
            background-color: #007bff;
            color: white;
        }
        .btn-secondary {
            background-color: #6c757d;
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
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <div style="margin-bottom: 20px;">
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Projects
            </a>
        </div>
        
        <h1>Add New Project</h1>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="title_nl">
                    <i class="fas fa-flag"></i> Dutch Title *
                </label>
                <input type="text" class="form-control" id="title_nl" name="title_nl" 
                       value="<?= htmlspecialchars($_POST['title_nl'] ?? '') ?>" required>
            </div>
            
            <div class="form-group">
                <label for="title_en">
                    <i class="fas fa-flag"></i> English Title *
                </label>
                <input type="text" class="form-control" id="title_en" name="title_en" 
                       value="<?= htmlspecialchars($_POST['title_en'] ?? '') ?>" required>
            </div>
            
            <div class="form-group">
                <label for="category_id">
                    <i class="fas fa-folder"></i> Category *
                </label>
                <select class="form-control" id="category_id" name="category_id" required>
                    <option value="">Select a category</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= $category['id'] ?>" 
                                <?= (isset($_POST['category_id']) && $_POST['category_id'] == $category['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($category['name_en']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="description_nl">
                    <i class="fas fa-align-left"></i> Dutch Description
                </label>
                <textarea class="form-control" id="description_nl" name="description_nl" 
                          rows="5"><?= htmlspecialchars($_POST['description_nl'] ?? '') ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="description_en">
                    <i class="fas fa-align-left"></i> English Description
                </label>
                <textarea class="form-control" id="description_en" name="description_en" 
                          rows="5"><?= htmlspecialchars($_POST['description_en'] ?? '') ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="image">
                    <i class="fas fa-image"></i> Project Image
                </label>
                <input type="file" class="form-control" id="image" name="image" 
                       accept="image/*">
                <small style="color: #666; font-size: 12px;">
                    Supported formats: JPEG, PNG, GIF, WebP. Maximum size: 5MB.
                </small>
            </div>
            
            <div style="margin-top: 30px;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Project
                </button>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</body>
</html>