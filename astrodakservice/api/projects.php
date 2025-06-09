<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// More permissive CORS headers for debugging
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Handle preflight requests first
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Initialize response
$response = ['success' => false, 'data' => []];

try {
    // Include files and check if they exist
    if (!file_exists('../includes/config.php')) {
        throw new Exception('Config file not found');
    }
    if (!file_exists('../includes/functions.php')) {
        throw new Exception('Functions file not found');
    }
    
    require_once '../includes/config.php';
    require_once '../includes/functions.php';
    
    // Check if PDO connection exists
    if (!isset($pdo)) {
        throw new Exception('Database connection not established');
    }
    
    // Test database connection
    $pdo->query('SELECT 1');
    
    // Handle GET requests
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : null;
        $language = isset($_GET['lang']) ? ($_GET['lang'] === 'nl' ? 'nl' : 'en') : 'en';
        
        // Validate language columns exist (optional safety check)
        $sql = "SELECT p.id, 
                       p.title_$language as title, 
                       p.description_$language as description, 
                       p.image, 
                       c.name_$language as category_name
                FROM projects p
                JOIN categories c ON p.category_id = c.id";
        
        $params = [];
        
        if ($category_id) {
            $sql .= " WHERE p.category_id = ?";
            $params[] = $category_id;
        }
        
        $sql .= " ORDER BY p.id DESC";
        
        $stmt = $pdo->prepare($sql);
        
        if (!$stmt) {
            throw new Exception('Failed to prepare statement: ' . implode(', ', $pdo->errorInfo()));
        }
        
        $result = $stmt->execute($params);
        
        if (!$result) {
            throw new Exception('Failed to execute statement: ' . implode(', ', $stmt->errorInfo()));
        }
        
        $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get additional images for each project
        foreach ($projects as &$project) {
            $stmt = $pdo->prepare("SELECT image FROM project_images WHERE project_id = ?");
            if ($stmt) {
                $stmt->execute([$project['id']]);
                $project['images'] = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
            } else {
                $project['images'] = [];
            }
        }
        
        $response['success'] = true;
        $response['data'] = $projects;
        $response['count'] = count($projects);
    }
    else {
        http_response_code(405); // Method Not Allowed
        throw new Exception('Invalid request method: ' . $_SERVER['REQUEST_METHOD']);
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    $response['error'] = "Database error: " . $e->getMessage();
    $response['error_code'] = $e->getCode();
} catch (Exception $e) {
    http_response_code(500);
    $response['error'] = $e->getMessage();
} catch (Error $e) {
    http_response_code(500);
    $response['error'] = "Fatal error: " . $e->getMessage();
}

// Always output JSON response
echo json_encode($response, JSON_PRETTY_PRINT);
?>