<?php
header("Access-Control-Allow-Origin: https://astradakservices.nl");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-Requested-With");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../includes/config.php';

$response = [
    'success' => false,
    'data' => null,
    'message' => '',
    'error' => null
];

try {
    // GET all categories
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $lang = $_GET['lang'] ?? 'en';
       
        // Base query
        $sql = "SELECT id, name_en, name_nl FROM categories ORDER BY id";
       
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
       
        // Format response
        $response['success'] = true;
        $response['data'] = $categories;
        $response['message'] = count($categories) . ' categories found';
    }
   
    // POST - Create new category
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
       
        if (!isset($input['name_en']) || empty(trim($input['name_en']))) {
            throw new Exception('Category English name is required');
        }
        if (!isset($input['name_nl']) || empty(trim($input['name_nl']))) {
            throw new Exception('Category Dutch name is required');
        }
        
        $name_en = trim($input['name_en']);
        $name_nl = trim($input['name_nl']);
        
        // Check if category already exists (check both language versions)
        $checkStmt = $pdo->prepare("SELECT id FROM categories WHERE name_en = ? OR name_nl = ?");
        $checkStmt->execute([$name_en, $name_nl]);
       
        if ($checkStmt->fetch()) {
            throw new Exception('Category already exists');
        }
       
        // Insert new category
        $stmt = $pdo->prepare("INSERT INTO categories (name_en, name_nl) VALUES (?, ?)");
        $stmt->execute([$name_en, $name_nl]);
       
        $categoryId = $pdo->lastInsertId();
       
        $response['success'] = true;
        $response['data'] = [
            'id' => $categoryId,
            'name_en' => $name_en,
            'name_nl' => $name_nl
        ];
        $response['message'] = 'Category created successfully';
    }
   
    else {
        throw new Exception('Method not allowed');
    }
   
} catch (PDOException $e) {
    $response['error'] = 'Database error: ' . $e->getMessage();
    http_response_code(500);
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
    http_response_code(400);
}

echo json_encode($response, JSON_PRETTY_PRINT);
?>