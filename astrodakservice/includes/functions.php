<?php
require_once 'config.php';

/**
 * Sanitize input data
 */
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect with message
 */
function redirect($url, $message = null, $type = 'success') {
    if ($message) {
        $_SESSION[$type] = $message;
    }
    header("Location: $url");
    exit();
}

/**
 * Generate CSRF token
 */
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 */
function validateCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Upload image with validation
 */
function uploadImage($file) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('File upload error: ' . $file['error']);
    }

    // Check file type
    $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($fileExt, ALLOWED_IMAGE_TYPES)) {
        throw new Exception('Invalid file type. Allowed types: ' . implode(', ', ALLOWED_IMAGE_TYPES));
    }

    // Check file size (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new Exception('File too large. Maximum size is 5MB.');
    }

    // Create upload directory if it doesn't exist
    if (!file_exists(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }

    // Generate unique filename
    $filename = uniqid() . '.' . $fileExt;
    $destination = UPLOAD_DIR . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new Exception('Failed to move uploaded file.');
    }

    return 'uploads/' . $filename;
}

/**
 * Delete file from server
 */
function deleteFile($filepath) {
    $fullPath = __DIR__ . '/../' . $filepath;
    if (file_exists($fullPath)) {
        unlink($fullPath);
        return true;
    }
    return false;
}

/**
 * Get category name by ID
 */
function getCategoryName($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT name FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetchColumn();
}

/**
 * Get all categories for dropdown
 */
function getAllCategories() {
    global $pdo;
    return $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
}

/**
 * Check if category exists
 */
function categoryExists($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetchColumn() > 0;
}

/**
 * Check if category is used in projects
 */
function isCategoryUsed($category_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM projects WHERE category_id = ?");
    $stmt->execute([$category_id]);
    return $stmt->fetchColumn() > 0;
}

/**
 * Get category by ID
 */
function getCategoryById($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/**
 * Format date for display
 */
function formatDate($dateString, $format = 'd/m/Y') {
    if (empty($dateString)) return 'N/A';
    $date = new DateTime($dateString);
    return $date->format($format);
}

/**
 * Generate pagination links
 */
function paginationLinks($totalItems, $itemsPerPage, $currentPage, $baseUrl) {
    $totalPages = ceil($totalItems / $itemsPerPage);
    $links = '';
    
    // Previous link
    if ($currentPage > 1) {
        $links .= "<a href='{$baseUrl}&page=".($currentPage-1)."' class='page-link'>&laquo; Previous</a>";
    }
    
    // Page numbers
    $start = max(1, $currentPage - 2);
    $end = min($totalPages, $currentPage + 2);
    
    for ($i = $start; $i <= $end; $i++) {
        $active = $i == $currentPage ? 'active' : '';
        $links .= "<a href='{$baseUrl}&page={$i}' class='page-link {$active}'>{$i}</a>";
    }
    
    // Next link
    if ($currentPage < $totalPages) {
        $links .= "<a href='{$baseUrl}&page=".($currentPage+1)."' class='page-link'>Next &raquo;</a>";
    }
    
    return "<div class='pagination'>{$links}</div>";
}

/**
 * Generate star rating HTML
 */
function starRating($rating) {
    $stars = str_repeat('<i class="fas fa-star"></i>', $rating);
    $emptyStars = str_repeat('<i class="far fa-star"></i>', 5 - $rating);
    return "<div class='star-rating'>{$stars}{$emptyStars}</div>";
}

/**
 * Get project count by category
 */
function getProjectCountByCategory($category_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM projects WHERE category_id = ?");
    $stmt->execute([$category_id]);
    return $stmt->fetchColumn();
}

/**
 * Validate category data before save
 */
function validateCategoryData($name) {
    $errors = [];
    
    if (empty($name)) {
        $errors[] = "Category name is required";
    } elseif (strlen($name) > 100) {
        $errors[] = "Category name must be less than 100 characters";
    }
    
    return $errors;
}

/**
 * Check if category name already exists (for add/edit validation)
 */
function categoryNameExists($name, $exclude_id = null) {
    global $pdo;
    
    $sql = "SELECT COUNT(*) FROM categories WHERE name = ?";
    $params = [$name];
    
    if ($exclude_id) {
        $sql .= " AND id != ?";
        $params[] = $exclude_id;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetchColumn() > 0;
}