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

// Fetch project data
$stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
$stmt->execute([$project_id]);
$project = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$project) {
    $_SESSION['error'] = "Project not found";
    header("Location: index.php");
    exit();
}

// Handle form submission
// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title_nl = trim($_POST['title_nl']);
    $title_en = trim($_POST['title_en']);
    $category_id = (int)$_POST['category_id'];
    $description_nl = trim($_POST['description_nl']);
    $description_en = trim($_POST['description_en']);
    $current_image = $_POST['current_image'] ?? '';
    $remove_image = isset($_POST['remove_image']) ? true : false;
    
    // Handle image upload/removal
    $image = $current_image;
    
    // If user wants to remove the image
    if ($remove_image) {
        if (!empty($current_image) && file_exists('../' . $current_image)) {
            unlink('../' . $current_image);
        }
        $image = '';
    }
    
    // Handle image upload
   // If new image is uploaded
    elseif (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $fileType = $_FILES['image']['type'];
        
        if (!in_array($fileType, $allowedTypes)) {
            $error = "Invalid file type. Only JPEG, PNG, GIF, and WebP images are allowed.";
        } else {
            // Delete old image if exists
            if (!empty($current_image) && file_exists('../' . $current_image)) {
                unlink('../' . $current_image);
            }
            
            // Upload new image
            $uploadDir = '../uploads/projects/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $fileExtension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $fileName = uniqid() . '.' . $fileExtension;
            $uploadPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
                $image = 'uploads/projects/' . $fileName;
            } else {
                $error = "Failed to upload image.";
            }
        }
    }
    
  if (!isset($error)) {
        try {
            $stmt = $pdo->prepare("UPDATE projects SET 
                                 title_nl = ?, 
                                 title_en = ?, 
                                 category_id = ?, 
                                 description_nl = ?, 
                                 description_en = ?, 
                                 image = ?
                                 WHERE id = ?");
            
            $stmt->execute([
                $title_nl,
                $title_en,
                $category_id,
                $description_nl,
                $description_en,
                $image,
                $project_id
            ]);
            
            $_SESSION['success'] = "Project updated successfully!";
            header("Location: index.php");
            exit();
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// Fetch categories
try {
    $categories = $pdo->query("SELECT * FROM categories ORDER BY name_en")->fetchAll(PDO::FETCH_ASSOC);
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
    <title>Edit Project - AstroDak Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
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
            transition: all 0.3s ease;
        }
        .btn-primary {
            background-color: #007bff;
            color: white;
        }
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        .btn-back {
            background-color: #f8f9fa;
            color: #495057;
            border: 1px solid #dee2e6;
        }
        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            border-left: 4px solid;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border-left-color: #dc3545;
        }
        .project-form {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #495057;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 14px;
            transition: border-color 0.3s ease;
            box-sizing: border-box;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
        }
        .current-image {
            margin-bottom: 15px;
            padding: 15px;
            border: 1px dashed #dee2e6;
            border-radius: 4px;
            text-align: center;
        }
        .current-image img {
            max-width: 200px;
            max-height: 200px;
            object-fit: cover;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        .remove-image {
            color: #dc3545;
            text-decoration: none;
            font-size: 12px;
        }
        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
        }
        .current-image label {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    font-size: 13px;
    color: #dc3545;
}

.current-image input[type="checkbox"] {
    width: auto;
    margin: 0;
}
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1>Edit Project</h1>
                <p>Update project information</p>
            </div>
            <div>
                <a href="index.php" class="btn btn-back">
                    <i class="fas fa-arrow-left"></i> Back to Projects
                </a>
            </div>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data" class="project-form">
            <div class="form-row">
                <div class="form-group">
                    <label for="title_nl">Dutch Title *</label>
                    <input type="text" id="title_nl" name="title_nl" value="<?= htmlspecialchars($project['title_nl'] ?? '') ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="title_en">English Title *</label>
                    <input type="text" id="title_en" name="title_en" value="<?= htmlspecialchars($project['title_en'] ?? '') ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="category_id">Category *</label>
                <select id="category_id" name="category_id" required>
                    <option value="">Select a category</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= $category['id'] ?>" <?= $category['id'] == $project['category_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($category['name_en']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="description_nl">Dutch Description</label>
                    <textarea id="description_nl" name="description_nl" rows="5"><?= htmlspecialchars($project['description_nl'] ?? '') ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="description_en">English Description</label>
                    <textarea id="description_en" name="description_en" rows="5"><?= htmlspecialchars($project['description_en'] ?? '') ?></textarea>
                </div>
            </div>
            
            <div class="form-group">
                <label for="image">Project Image</label>
                <?php if (!empty($project['image'])): ?>
                    <div class="current-image">
                        <img src="../<?= htmlspecialchars($project['image']) ?>" alt="Current project image">
                        <br>
                        <span style="color: #6c757d; font-size: 12px;">Current Image</span>
                        <input type="hidden" name="current_image" value="<?= htmlspecialchars($project['image']) ?>">
                    </div>
                <?php endif; ?>
                <input type="file" id="image" name="image" accept="image/*">
                <small style="color: #6c757d;">Leave empty to keep current image</small>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Project
                </button>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</body>
</html>