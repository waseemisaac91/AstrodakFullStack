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

// Fetch project data with category information
$stmt = $pdo->prepare("
    SELECT p.*, c.name_en as category_name 
    FROM projects p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE p.id = ?
");
$stmt->execute([$project_id]);
$project = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$project) {
    $_SESSION['error'] = "Project not found";
    header("Location: index.php");
    exit();
}

$currentLanguage = $_SESSION['lang'] ?? 'en';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Project - <?= htmlspecialchars($project['title_' . $currentLanguage] ?? $project['title_en']) ?></title>
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
            max-width: 1000px;
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
        .btn-danger {
            background-color: #dc3545;
            color: white;
        }
        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .project-details {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .project-hero {
            position: relative;
            height: 300px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        .project-hero img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            position: absolute;
            top: 0;
            left: 0;
        }
        .project-hero .overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.3);
            z-index: 1;
        }
        .project-hero .content {
            position: relative;
            z-index: 2;
            text-align: center;
        }
        .project-hero h1 {
            font-size: 2.5rem;
            margin: 0;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        .project-info {
            padding: 30px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        .info-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #007bff;
        }
        .info-section h3 {
            margin: 0 0 15px 0;
            color: #495057;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .info-section p {
            margin: 0;
            color: #6c757d;
            line-height: 1.6;
        }
        .badge {
            display: inline-block;
            padding: 6px 12px;
            background-color: #007bff;
            color: white;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        .meta-info {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .meta-item {
            background: #e9ecef;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 14px;
            color: #495057;
        }
        .meta-item i {
            margin-right: 5px;
            color: #6c757d;
        }
        .language-toggle {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .lang-btn {
            padding: 6px 12px;
            background: #e9ecef;
            color: #495057;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            text-transform: uppercase;
        }
        .lang-btn.active {
            background: #007bff;
            color: white;
        }
        .no-image {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            gap: 10px;
        }
        .no-image i {
            font-size: 3rem;
            color: rgba(255,255,255,0.7);
        }
        @media (max-width: 768px) {
            .info-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            .project-hero h1 {
                font-size: 2rem;
            }
            .meta-info {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1>Project Details</h1>
                <p>View project information</p>
            </div>
            <div>
                <a href="index.php" class="btn btn-back">
                    <i class="fas fa-arrow-left"></i> Back to Projects
                </a>
                <a href="edit.php?id=<?= $project['id'] ?>" class="btn btn-primary">
                    <i class="fas fa-edit"></i> Edit Project
                </a>
                <?php if ($auth->hasRole('admin')): ?>
                    <a href="?action=delete&id=<?= $project['id'] ?>" 
                       class="btn btn-danger" 
                       onclick="return confirm('Are you sure you want to delete this project? This action cannot be undone.')">
                        <i class="fas fa-trash"></i> Delete
                    </a>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="project-details">
         <div class="project-hero">
    <?php if (!empty($project['image']) && file_exists('../' . $project['image'])): ?>
        <img src="../<?= htmlspecialchars($project['image']) ?>" alt="Project Image">
        <div class="overlay"></div>
    <?php else: ?>
        <div class="no-image">
            <i class="fas fa-image"></i>
            <span>No Image Available</span>
        </div>
    <?php endif; ?>
    <div class="content">
        <h1 id="project-title"><?= htmlspecialchars($project['title_' . $currentLanguage] ?? $project['title_en'] ?? 'Untitled Project') ?></h1>
    </div>
</div>
            
            <div class="project-info">
                <div class="language-toggle">
                    <button class="lang-btn <?= $currentLanguage === 'en' ? 'active' : '' ?>" onclick="switchLanguage('en')">EN</button>
                    <button class="lang-btn <?= $currentLanguage === 'nl' ? 'active' : '' ?>" onclick="switchLanguage('nl')">NL</button>
                </div>
                
                <div class="meta-info">
                    <div class="meta-item">
                        <i class="fas fa-tag"></i>
                        <span class="badge"><?= htmlspecialchars($project['category_name'] ?? 'Uncategorized') ?></span>
                    </div>
              
                
                <div class="info-grid">
                    <div class="info-section">
                        <h3>
                            <i class="fas fa-heading"></i>
                            Project Title
                        </h3>
                        <p id="title-content"><?= htmlspecialchars($project['title_' . $currentLanguage] ?? $project['title_en'] ?? 'No title available') ?></p>
                    </div>
                    
                    <div class="info-section">
                        <h3>
                            <i class="fas fa-align-left"></i>
                            Description
                        </h3>
                        <p id="description-content">
                            <?php 
                            $description = $project['description_' . $currentLanguage] ?? $project['description_en'] ?? '';
                            echo !empty($description) ? nl2br(htmlspecialchars($description)) : 'No description available';
                            ?>
                        </p>
                    </div>
                </div>
                
              <?php if (!empty($project['image'])): ?>
    <div class="info-section">
        <h3>
            <i class="fas fa-image"></i>
            Image Information
        </h3>
        <p>
            <strong>Path:</strong> <?= htmlspecialchars($project['image']) ?><br>
            <strong>Status:</strong> 
            <?php if (file_exists('../' . $project['image'])): ?>
                <span style="color: #28a745;">✓ File exists</span>
            <?php else: ?>
                <span style="color: #dc3545;">✗ File not found</span>
            <?php endif; ?>
        </p>
    </div>
<?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function switchLanguage(lang) {
            const projectData = {
                'en': {
                    title: <?= json_encode($project['title_en'] ?? 'Untitled Project') ?>,
                    description: <?= json_encode($project['description_en'] ?? 'No description available') ?>
                },
                'nl': {
                    title: <?= json_encode($project['title_nl'] ?? 'Naamloos Project') ?>,
                    description: <?= json_encode($project['description_nl'] ?? 'Geen beschrijving beschikbaar') ?>
                }
            };
            
            // Update content
            document.getElementById('project-title').textContent = projectData[lang].title;
            document.getElementById('title-content').textContent = projectData[lang].title;
            document.getElementById('description-content').innerHTML = projectData[lang].description.replace(/\n/g, '<br>');
            
            // Update active button
            document.querySelectorAll('.lang-btn').forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            
            // Store preference
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'lang=' + lang
            });
        }
    </script>
</body>
</html>