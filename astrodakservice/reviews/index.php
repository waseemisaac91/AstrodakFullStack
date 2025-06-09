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
if (!$auth->hasRole('admin') && !$auth->hasRole('editor')) {
    header('Location: ../unauthorized.php');
    exit();
}

$status = isset($_GET['status']) ? $_GET['status'] : 'pending';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 6; // Show 6 reviews per page
$offset = ($page - 1) * $limit;

// Get reviews with pagination
$stmt = $pdo->prepare("SELECT * FROM reviews 
                      WHERE approved = :approved 
                      ORDER BY rating DESC
                      LIMIT :limit OFFSET :offset");
                      
$stmt->bindValue(':approved', $status === 'approved' ? 1 : 0, PDO::PARAM_INT);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$reviews = $stmt->fetchAll();

// Count total reviews for pagination
$totalStmt = $pdo->prepare("SELECT COUNT(*) FROM reviews WHERE approved = :approved");
$totalStmt->bindValue(':approved', $status === 'approved' ? 1 : 0, PDO::PARAM_INT);
$totalStmt->execute();
$totalReviews = $totalStmt->fetchColumn();
$totalPages = ceil($totalReviews / $limit);

// Get counts for badges
$pendingCount = $pdo->query("SELECT COUNT(*) FROM reviews WHERE approved = 0")->fetchColumn();
$approvedCount = $pdo->query("SELECT COUNT(*) FROM reviews WHERE approved = 1")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reviews Management - AstroDak Admin</title>
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
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0 0 10px 0;
            color: #495057;
            font-size: 2.5rem;
        }
        .header p {
            margin: 0;
            color: #6c757d;
            font-size: 1.1rem;
        }
        .review-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            justify-content: center;
        }
        .review-tabs a {
            padding: 12px 24px;
            background: white;
            color: #495057;
            text-decoration: none;
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
        }
        .review-tabs a:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .review-tabs a.active {
            background: #007bff;
            color: white;
        }
        .badge {
            background: rgba(255,255,255,0.2);
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .active .badge {
            background: rgba(255,255,255,0.3);
        }
        .alert {
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            border-left: 4px solid;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .alert-success {
            border-left-color: #28a745;
            color: #155724;
        }
        .alert-error {
            border-left-color: #dc3545;
            color: #721c24;
        }
        .reviews-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .review-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: all 0.3s ease;
            border-left: 4px solid #007bff;
        }
        .review-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.15);
        }
        .review-card.pending {
            border-left-color: #ffc107;
        }
        .review-card.approved {
            border-left-color: #28a745;
        }
        .review-header {
            padding: 20px 20px 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        .reviewer-info h3 {
            margin: 0 0 5px 0;
            color: #495057;
            font-size: 1.2rem;
        }
        .reviewer-info .rating {
            color: #ffc107;
            font-size: 1.1rem;
        }
        .review-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        .status-approved {
            background: #d4edda;
            color: #155724;
        }
        .review-content {
            padding: 20px;
        }
        .review-text {
            margin-bottom: 15px;
        }
        .review-text h4 {
            margin: 0 0 8px 0;
            font-size: 14px;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .review-text p {
            margin: 0;
            color: #495057;
            line-height: 1.6;
            max-height: 60px;
            overflow: hidden;
            position: relative;
        }
        .review-text p::after {
            content: '...';
            position: absolute;
            bottom: 0;
            right: 0;
            background: white;
            padding-left: 20px;
        }
        .review-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 1px solid #dee2e6;
            font-size: 14px;
            color: #6c757d;
        }
        .review-actions {
            display: flex;
            gap: 8px;
        }
        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 12px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .btn-sm {
            padding: 4px 8px;
            font-size: 11px;
        }
        .btn-success {
            background: #28a745;
            color: white;
        }
        .btn-success:hover {
            background: #218838;
        }
        .btn-primary {
            background: #007bff;
            color: white;
        }
        .btn-primary:hover {
            background: #0056b3;
        }
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        .btn-danger:hover {
            background: #c82333;
        }
        .pagination {
            display: flex;
            justify-content: center;
            gap: 5px;
            margin-top: 30px;
        }
        .pagination a {
            padding: 8px 12px;
            background: white;
            color: #495057;
            text-decoration: none;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        .pagination a:hover,
        .pagination a.active {
            background: #007bff;
            color: white;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .empty-state i {
            font-size: 4rem;
            color: #dee2e6;
            margin-bottom: 20px;
        }
        .empty-state h3 {
            margin: 0 0 10px 0;
            color: #495057;
        }
        .empty-state p {
            margin: 0;
            color: #6c757d;
        }
        @media (max-width: 768px) {
            .reviews-grid {
                grid-template-columns: 1fr;
            }
            .review-tabs {
                flex-direction: column;
            }
            .review-header {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-star"></i> Reviews Management</h1>
            <p>Manage customer reviews and testimonials</p>
        </div>
        <div>
             <a href="../dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
         </div>
        <div class="review-tabs">
            <a href="?status=pending" class="<?= $status === 'pending' ? 'active' : '' ?>">
                <i class="fas fa-clock"></i>
                Pending Approval
                <span class="badge"><?= $pendingCount ?></span>
            </a>
            <a href="?status=approved" class="<?= $status === 'approved' ? 'active' : '' ?>">
                <i class="fas fa-check-circle"></i>
                Approved Reviews
                <span class="badge"><?= $approvedCount ?></span>
            </a>
        </div>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= $_SESSION['success'] ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i> <?= $_SESSION['error'] ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <?php if (empty($reviews)): ?>
            <div class="empty-state">
                <i class="fas fa-star"></i>
                <h3>No <?= $status ?> reviews found</h3>
                <p>There are currently no reviews with this status.</p>
            </div>
        <?php else: ?>
            <div class="reviews-grid">
                <?php foreach ($reviews as $review): ?>
                    <div class="review-card <?= $review['approved'] ? 'approved' : 'pending' ?>">
                        <div class="review-header">
                            <div class="reviewer-info">
                                <h3><?= htmlspecialchars($review['name']) ?></h3>
                                <div class="rating">
                                    <?= str_repeat('★', $review['rating']) . str_repeat('☆', 5 - $review['rating']) ?>
                                </div>
                            </div>
                            <span class="review-status <?= $review['approved'] ? 'status-approved' : 'status-pending' ?>">
                                <?= $review['approved'] ? 'Approved' : 'Pending' ?>
                            </span>
                        </div>
                        
                        <div class="review-content">
                          <!--   <div class="review-text">
                                <h4>English Review</h4>
                                <p><?= htmlspecialchars($review['review_en']) ?></p>
                            </div> -->
                            
                            <div class="review-text">
                                <h4>Dutch Review</h4>
                                <p><?= htmlspecialchars($review['review_nl']) ?></p>
                            </div>
                            
                            <div class="review-meta">
                              
                                <div class="review-actions">
                                    <a href="view.php?id=<?= $review['id'] ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <?php if (!$review['approved']): ?>
                                        <a href="approve.php?id=<?= $review['id'] ?>&status=<?= $status ?>" class="btn btn-sm btn-success">
                                            <i class="fas fa-check"></i> Approve
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($auth->hasRole('admin')): ?>
                                        <a href="delete.php?id=<?= $review['id'] ?>&status=<?= $status ?>" 
                                           class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Are you sure you want to delete this review?')">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?status=<?= $status ?>&page=<?= $page - 1 ?>">
                        <i class="fas fa-chevron-left"></i> Previous
                    </a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?status=<?= $status ?>&page=<?= $i ?>" class="<?= $i === $page ? 'active' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                    <a href="?status=<?= $status ?>&page=<?= $page + 1 ?>">
                        Next <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>