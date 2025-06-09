<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/review.php';
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

$review_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($review_id === 0) {
    $_SESSION['error'] = "Invalid review ID";
    header("Location: index.php");
    exit();
}

// Fetch review data
$stmt = $pdo->prepare("SELECT * FROM reviews WHERE id = ?");
$stmt->execute([$review_id]);
$review = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$review) {
    $_SESSION['error'] = "Review not found";
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Review - <?= htmlspecialchars($review['name']) ?></title>
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
        .btn-success {
            background-color: #28a745;
            color: white;
        }
        .btn-danger {
            background-color: #dc3545;
            color: white;
        }
        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .review-details {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .review-hero {
            position: relative;
            height: 200px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        .review-hero .content {
            text-align: center;
        }
        .review-hero h1 {
            font-size: 2.5rem;
            margin: 0 0 10px 0;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        .review-hero .rating {
            font-size: 2rem;
            color: #ffc107;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        .review-info {
            padding: 30px;
        }
        .info-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #007bff;
            margin-bottom: 20px;
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
            white-space: pre-wrap;
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
        .badge-success {
            background-color: #28a745;
        }
        .badge-warning {
            background-color: #ffc107;
            color: #212529;
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
        .review-text {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .review-text h4 {
            margin: 0 0 15px 0;
            color: #495057;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            .review-hero h1 {
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
                <h1>Review Details</h1>
                <p>View customer review information</p>
            </div>
            <div>
                <a href="index.php" class="btn btn-back">
                    <i class="fas fa-arrow-left"></i> Back to Reviews
                </a>
                <?php if (!$review['approved']): ?>
                    <a href="approve.php?id=<?= $review['id'] ?>" class="btn btn-success">
                        <i class="fas fa-check"></i> Approve Review
                    </a>
                <?php endif; ?>
                <?php if ($auth->hasRole('admin')): ?>
                    <a href="delete.php?id=<?= $review['id'] ?>" 
                       class="btn btn-danger" 
                       onclick="return confirm('Are you sure you want to delete this review? This action cannot be undone.')">
                        <i class="fas fa-trash"></i> Delete
                    </a>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="review-details">
            <div class="review-hero">
                <div class="content">
                    <h1><?= htmlspecialchars($review['name']) ?></h1>
                    <div class="rating">
                        <?= str_repeat('★', $review['rating']) . str_repeat('☆', 5 - $review['rating']) ?>
                    </div>
                </div>
            </div>
            
            <div class="review-info">
                <div class="meta-info">
                    <div class="meta-item">
                        <i class="fas fa-user"></i>
                        <strong>Reviewer:</strong> <?= htmlspecialchars($review['name']) ?>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-star"></i>
                        <strong>Rating:</strong> <?= $review['rating'] ?>/5
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-<?= $review['approved'] ? 'check-circle' : 'clock' ?>"></i>
                        <span class="badge <?= $review['approved'] ? 'badge-success' : 'badge-warning' ?>">
                            <?= $review['approved'] ? 'Approved' : 'Pending' ?>
                        </span>
                    </div>
                </div>
                
                <div class="review-text">
                    <h4>
                        <i class="fas fa-comment"></i>
                        Review
                    </h4>
                    <p><?= !empty($review['review_nl']) ? nl2br(htmlspecialchars($review['review_nl'])) : 'Geen Nederlandse review beschikbaar.' ?></p>
                </div>
                
                <?php if ($review['publish']): ?>
                    <div class="info-section">
                        <h3>
                            <i class="fas fa-calendar-alt"></i>
                            Publication Information
                        </h3>
                        <p><strong>Published:</strong> <?= date('d M Y', strtotime($review['publish'])) ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>