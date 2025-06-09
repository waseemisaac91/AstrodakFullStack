<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/auth.php';

$auth = new Auth();
$currentUser = $auth->getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied - AstroDak</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #333;
        }
        
        .error-container {
            background: white;
            padding: 60px 40px;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 500px;
            width: 90%;
        }
        
        .error-icon {
            font-size: 80px;
            color: #ff6b6b;
            margin-bottom: 30px;
        }
        
        .error-title {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 15px;
            color: #2c3e50;
        }
        
        .error-message {
            font-size: 16px;
            color: #7f8c8d;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        
        .user-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            border-left: 4px solid #3498db;
        }
        
        .user-info h4 {
            margin-bottom: 10px;
            color: #2c3e50;
        }
        
        .user-details {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .user-detail {
            background: white;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            margin: 0 10px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #7f8c8d;
            transform: translateY(-2px);
        }
        
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }
        
        .actions {
            margin-top: 20px;
        }
        
        @media (max-width: 600px) {
            .error-container {
                padding: 40px 20px;
            }
            
            .error-title {
                font-size: 24px;
            }
            
            .user-details {
                flex-direction: column;
            }
            
            .btn {
                display: block;
                margin: 10px 0;
            }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">
            <i class="fas fa-ban"></i>
        </div>
        
        <h1 class="error-title">Access Denied</h1>
        
        <p class="error-message">
            Sorry, you don't have the necessary permissions to access this resource. 
            Your current role doesn't allow you to perform this action.
        </p>
        
        <?php if ($currentUser): ?>
            <div class="user-info">
                <h4><i class="fas fa-user"></i> Your Account Information</h4>
                <div class="user-details">
                    <div class="user-detail">
                        <strong>Username:</strong> <?= htmlspecialchars($currentUser['username']) ?>
                    </div>
                    <div class="user-detail">
                        <strong>Role:</strong> <?= ucfirst($currentUser['role']) ?>
                    </div>
                    <div class="user-detail">
                        <strong>Status:</strong> 
                        <?= $currentUser['active'] ? '<span style="color: #27ae60;">Active</span>' : '<span style="color: #e74c3c;">Inactive</span>' ?>
                    </div>
                </div>
            </div>
            
            <div class="actions">
                <a href="dashboard.php" class="btn btn-primary">
                    <i class="fas fa-tachometer-alt"></i> Go to Dashboard
                </a>
                <a href="javascript:history.back()" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Go Back
                </a>
                <a href="logout.php" class="btn btn-danger">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        <?php else: ?>
            <div class="actions">
                <a href="login.php" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt"></i> Login
                </a>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-home"></i> Home
                </a>
            </div>
        <?php endif; ?>
        
        <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #ecf0f1; color: #bdc3c7; font-size: 12px;">
            If you believe this is an error, please contact your system administrator.
        </div>
    </div>
</body>
</html>