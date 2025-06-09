<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/project.php';
require_once 'includes/review.php';
require_once 'includes/category.php'; // Add category management

// Initialize auth
$auth = new Auth();
$auth->requireLogin();

// Initialize models
$projectModel = new Project();
$reviewModel = new Review();
$categoryModel = new Category();

// Get current language from session or default to 'en'
$currentLanguage = $_SESSION['lang'] ?? 'en';

// Handle category management actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'add_category':
            if (!empty($_POST['category_name'])) {
                $categoryModel->addCategory(trim($_POST['category_name']));
                $_SESSION['success_message'] = 'Category added successfully!';
            }
            break;
        case 'delete_category':
            if (!empty($_POST['category_id'])) {
                $categoryModel->deleteCategory($_POST['category_id']);
                $_SESSION['success_message'] = 'Category deleted successfully!';
            }
            break;
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Get counts for dashboard
$projectCount = count($projectModel->getAllProjects($currentLanguage));
$reviewCount = count($reviewModel->getAllReviews(true, $currentLanguage));
$pendingReviewCount = count($reviewModel->getUnmoderatedReviews());
$categoryCount = count($categoryModel->getAllCategories());

// Get current user
$currentUser = $auth->getCurrentUser();

// Get recent data
$recentProjects = array_slice($projectModel->getAllProjects($currentLanguage), 0, 5);
$recentReviews = array_slice($reviewModel->getAllReviews(false, $currentLanguage), 0, 5);
$categories = $categoryModel->getAllCategories();

// Calculate statistics
//$avgRating = $reviewModel->getAverageRating();
//$monthlyStats = $projectModel->getMonthlyStats();
?>
<!DOCTYPE html>
<html lang="<?= $currentLanguage ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AstroDak Admin - Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/admin.css">
    <style>
        /* Enhanced Dashboard Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f8fafc;
            line-height: 1.6;
        }

        .admin-layout {
            display: grid;
            grid-template-columns: 280px 1fr;
            grid-template-rows: 70px 1fr;
            grid-template-areas: 
                "sidebar header"
                "sidebar main";
            min-height: 100vh;
            background: #f8fafc;
        }

        .admin-header {
            grid-area: header;
            background: white;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 2rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .admin-main {
            grid-area: main;
            padding: 2rem;
            overflow-y: auto;
        }

        .sidebar {
            grid-area: sidebar;
            background: #1e293b;
            color: white;
            padding: 1rem 0;
        }

        .sidebar-brand {
            padding: 1rem 1.5rem;
            font-size: 1.25rem;
            font-weight: bold;
            border-bottom: 1px solid #334155;
            margin-bottom: 1rem;
        }

        .sidebar-nav {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-nav li a {
            display: flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            color: #cbd5e1;
            text-decoration: none;
            transition: all 0.2s;
        }

        .sidebar-nav li a:hover {
            background: #334155;
            color: white;
        }

        .sidebar-nav li a.active {
            background: #3b82f6;
            color: white;
        }

        .sidebar-nav li a i {
            margin-right: 0.75rem;
            width: 1.25rem;
        }

        .mobile-menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #1f2937;
            cursor: pointer;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }

        .stat-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .stat-icon.projects { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .stat-icon.reviews { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .stat-icon.pending { background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%); color: #8b5cf6; }
        .stat-icon.categories { background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); color: #10b981; }

        .stat-value {
            font-size: 2.5rem;
            font-weight: bold;
            color: #1f2937;
            margin: 0.5rem 0;
        }

        .stat-label {
            color: #6b7280;
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }

        .stat-link {
            color: #3b82f6;
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .stat-link:hover {
            text-decoration: underline;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }

        .card-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #f8fafc;
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1f2937;
            margin: 0;
        }

        .card-body {
            padding: 1.5rem;
        }

        .table-responsive {
            overflow-x: auto;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            min-width: 600px;
        }

        .table th,
        .table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
            white-space: nowrap;
        }

        .table th {
            background: #f8fafc;
            font-weight: 600;
            color: #374151;
        }

        .table td.review-text {
            max-width: 200px;
            white-space: normal;
            word-wrap: break-word;
        }

        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            font-size: 0.75rem;
            font-weight: 500;
            border-radius: 9999px;
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }

        .badge-success { background: #dcfce7; color: #166534; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-info { background: #dbeafe; color: #1e40af; }

        .btn {
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.75rem;
        }

        .btn-primary { background: #3b82f6; color: white; }
        .btn-primary:hover { background: #2563eb; }
        .btn-success { background: #10b981; color: white; }
        .btn-success:hover { background: #059669; }
        .btn-danger { background: #ef4444; color: white; }
        .btn-danger:hover { background: #dc2626; }
        .btn-secondary { background: #6b7280; color: white; }
        .btn-secondary:hover { background: #4b5563; }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .alert-info { background: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe; }

        .actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .content-header {
            margin-bottom: 2rem;
        }

        .header-user {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .header-user-avatar {
            width: 40px;
            height: 40px;
            background: #3b82f6;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        /* Mobile Responsive Styles */
        @media (max-width: 1024px) {
            .admin-layout {
                grid-template-columns: 250px 1fr;
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .admin-layout {
                grid-template-columns: 1fr;
                grid-template-areas: 
                    "header"
                    "main";
            }
            
            .sidebar {
                position: fixed;
                left: -100%;
                top: 0;
                width: 280px;
                height: 100vh;
                z-index: 1000;
                transition: left 0.3s ease;
            }
            
            .sidebar.open {
                left: 0;
            }
            
            .mobile-menu-toggle {
                display: block;
            }
            
            .admin-header {
                padding: 0 1rem;
            }
            
            .admin-main {
                padding: 1rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .card-header {
                padding: 1rem;
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
            
            .card-body {
                padding: 1rem;
            }
            
            .table {
                min-width: auto;
                font-size: 0.875rem;
            }
            
            .table th,
            .table td {
                padding: 0.5rem;
            }
            
            .actions {
                flex-direction: column;
                gap: 0.25rem;
            }
            
            .btn-sm {
                padding: 0.25rem 0.5rem;
                font-size: 0.7rem;
            }
            
            .header-user {
                flex-direction: column;
                align-items: flex-end;
                gap: 0.25rem;
                font-size: 0.875rem;
            }
            
            .header-user-avatar {
                width: 35px;
                height: 35px;
            }
        }

        @media (max-width: 480px) {
            .stat-value {
                font-size: 2rem;
            }
            
            .card-title {
                font-size: 1.1rem;
            }
            
            .table-responsive {
                font-size: 0.8rem;
            }
            
            .table th,
            .table td {
                padding: 0.4rem;
            }
            
            .table td.review-text {
                max-width: 150px;
            }
            
            .admin-main {
                padding: 0.5rem;
            }
        }

        /* Overlay for mobile menu */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }

        .sidebar-overlay.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar Overlay -->
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-brand">
                <i class="fas fa-rocket"></i> AstroDak
            </div>
            <nav>
                <ul class="sidebar-nav">
                    <li><a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="projects/index.php"><i class="fas fa-project-diagram"></i> Projects</a></li>
                    <li><a href="reviews/index.php"><i class="fas fa-comments"></i> Reviews</a></li>
                    <li><a href="categories/index.php"><i class="fas fa-tags"></i> Categories</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </aside>
        
        <!-- Header -->
        <header class="admin-header">
            <div class="header-title" style="display: flex; align-items: center; gap: 1rem;">
                <button class="mobile-menu-toggle" id="mobileMenuToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 style="margin: 0; color: #1f2937;">Dashboard</h1>
            </div>
            
            <div class="header-actions">
                <div class="header-user">
                    <div>
                        <div style="font-weight: 600; color: #1f2937;"><?= htmlspecialchars($currentUser['username']) ?></div>
                        <div style="font-size: 0.875rem; color: #6b7280;"><?= ucfirst($currentUser['role']) ?></div>
                    </div>
                    <div class="header-user-avatar">
                        <?= strtoupper(substr($currentUser['username'], 0, 1)) ?>
                    </div>
                </div>
            </div>
        </header>
        
        <!-- Main Content -->
        <main class="admin-main">
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?= $_SESSION['success_message'] ?>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>

            <div class="content-header">
                <h2 style="margin: 0; color: #1f2937;">Welcome back, <?= htmlspecialchars($currentUser['username']) ?>!</h2>
                <p style="color: #6b7280; margin: 0.5rem 0 0 0;">Here's what's happening with your projects today.</p>
            </div>
            
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-icon projects">
                            <i class="fas fa-project-diagram"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?= $projectCount ?></div>
                    <div class="stat-label">Total Projects</div>
                    <a href="projects/index.php" class="stat-link">View All Projects</a>
                </div>
                
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-icon reviews">
                            <i class="fas fa-comments"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?= $reviewCount ?></div>
                    <div class="stat-label">Approved Reviews</div>
                    <a href="reviews/index.php" class="stat-link">View All Reviews</a>
                </div>
                
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-icon pending">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?= $pendingReviewCount ?></div>
                    <div class="stat-label">Pending Reviews</div>
                    <a href="reviews/index.php?filter=pending" class="stat-link">Moderate Now</a>
                </div>
                
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-icon categories">
                            <i class="fas fa-tags"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?= $categoryCount ?></div>
                    <div class="stat-label">Categories</div>
                    <a href="categories/index.php" class="stat-link">Manage Categories</a>
                </div>
            </div>
            
            <div class="dashboard-grid">
                <!-- Recent Projects -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Recent Projects</h3>
                        <a href="projects/add.php" class="btn btn-sm btn-primary">
                            <i class="fas fa-plus"></i> Add New
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($recentProjects)): ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Title</th>
                                            <th>Category</th>
                                            <th>Description</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentProjects as $project): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($project['title']) ?></strong></td>
                                            <td><span class="badge badge-info"><?= htmlspecialchars($project['category_name']) ?></span></td>
                                            <td><?= htmlspecialchars(substr($project['description'], 0, 60)) ?>...</td>
                                            <td class="actions">
                                                <a href="projects/edit.php?id=<?= $project['id'] ?>" class="btn btn-sm btn-secondary" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="projects/view.php?id=<?= $project['id'] ?>" class="btn btn-sm btn-primary" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                No projects found. <a href="projects/add.php">Add your first project</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Reviews -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Recent Reviews</h3>
                        <?php if ($pendingReviewCount > 0): ?>
                            <span class="badge badge-warning"><?= $pendingReviewCount ?> pending</span>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($recentReviews)): ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Review</th>
                                            <th>Rating</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentReviews as $review): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($review['name']) ?></strong></td>
                                            <td class="review-text">
                                                <?php 
                                                    // Check for review_nl first, then fallback to review_en or review
                                                    $reviewText = '';
                                                    if (!empty($review['review_nl'])) {
                                                        $reviewText = $review['review_nl'];
                                                    } elseif (!empty($review['review_en'])) {
                                                        $reviewText = $review['review_en'];
                                                    } elseif (!empty($review['review'])) {
                                                        $reviewText = $review['review'];
                                                    } else {
                                                        $reviewText = 'No review text';
                                                    }
                                                    echo htmlspecialchars(substr($reviewText, 0, 20)) . (strlen($reviewText) > 20 ? '...' : '');
                                                ?>
                                            </td>
                                            <td>
                                                <div style="color: #fbbf24;">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <?= $i <= $review['rating'] ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>' ?>
                                                    <?php endfor; ?>
                                                </div>
                                            </td>
                                            <td class="actions">
                                                <?php if (!$review['approved']): ?>
                                                    <a href="reviews/approve.php?id=<?= $review['id'] ?>" class="btn btn-sm btn-success" title="Approve">
                                                        <i class="fas fa-check"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <a href="reviews/view.php?id=<?= $review['id'] ?>" class="btn btn-sm btn-secondary" title="Edit">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="reviews/delete.php?id=<?= $review['id'] ?>" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Are you sure?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                No reviews found.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        // Mobile menu functionality
        const mobileMenuToggle = document.getElementById('mobileMenuToggle');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        
        function toggleSidebar() {
            sidebar.classList.toggle('open');
            sidebarOverlay.classList.toggle('active');
            document.body.style.overflow = sidebar.classList.contains('open') ? 'hidden' : '';
        }
        
        function closeSidebar() {
            sidebar.classList.remove('open');
            sidebarOverlay.classList.remove('active');
            document.body.style.overflow = '';
        }
        
        mobileMenuToggle.addEventListener('click', toggleSidebar);
        sidebarOverlay.addEventListener('click', closeSidebar);
        
        // Close sidebar when clicking on a link (mobile)
        const sidebarLinks = document.querySelectorAll('.sidebar-nav a');
        sidebarLinks.forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth <= 768) {
                    closeSidebar();
                }
            });
        });
        
        // Handle window resize
        window.addEventListener('resize', () => {
            if (window.innerWidth > 768) {
                closeSidebar();
            }
        });
        
        // Auto-hide success messages
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert-success');
            alerts.forEach(function(alert) {
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.remove();
                }, 300);
            });
        }, 5000);
    </script>
</body>
</html>