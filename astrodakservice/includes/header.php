<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? $pageTitle . ' | ' : '' ?><?= ADMIN_TITLE ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>
    <?php if (isset($_SESSION['user_id'])): ?>
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2><?= ADMIN_TITLE ?></h2>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li>
                        <a href="dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="../projects/index.php" class="<?= strpos($_SERVER['PHP_SELF'], 'projects/') !== false ? 'active' : '' ?>">
                            <i class="fas fa-project-diagram"></i> Projects
                        </a>
                    </li>
                    <li>
                        <a href="../reviews/index.php" class="<?= strpos($_SERVER['PHP_SELF'], 'reviews/') !== false ? 'active' : '' ?>">
                            <i class="fas fa-star"></i> Reviews
                        </a>
                    </li>
                    <li>
                        <a href="../categories/index.php" class="<?= strpos($_SERVER['PHP_SELF'], 'categories/') !== false ? 'active' : '' ?>">
                            <i class="fas fa-tags"></i> Categories
                        </a>
                    </li>
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                    <li>
                        <a href="../users/index.php" class="<?= strpos($_SERVER['PHP_SELF'], 'users/') !== false ? 'active' : '' ?>">
                            <i class="fas fa-users"></i> Users
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <div class="sidebar-footer">
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="content-header">
                <div class="header-left">
                    <button class="sidebar-toggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1><?= $pageTitle ?? 'Dashboard' ?></h1>
                </div>
                <div class="header-right">
                    <span class="welcome-msg">Welcome, <?= htmlspecialchars($_SESSION['username']) ?></span>
                    <span class="user-role-badge"><?= ucfirst($_SESSION['role']) ?></span>
                </div>
            </header>
            <div class="content-container">
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>
<?php endif; ?>