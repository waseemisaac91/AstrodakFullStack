<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// More permissive CORS headers for debugging
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header('Content-Type: application/json');

// Handle preflight OPTIONS requests first
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
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
    
    // GET approved reviews
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $approvedOnly = !isset($_GET['all']) || $_GET['all'] !== 'true';
        $language = isset($_GET['lang']) ? ($_GET['lang'] === 'nl' ? 'nl' : 'en') : 'en';
        
        // Build the SQL query
        $sql = "SELECT id, name, review_nl as review, rating, publish
                FROM reviews";
        
        $params = [];
        
        if ($approvedOnly) {
            $sql .= " WHERE approved = ?";
            $params[] = 1;
        }
        
        $sql .= " ORDER BY publish DESC";
        
        $stmt = $pdo->prepare($sql);
        
        if (!$stmt) {
            throw new Exception('Failed to prepare statement: ' . implode(', ', $pdo->errorInfo()));
        }
        
        $result = $stmt->execute($params);
        
        if (!$result) {
            throw new Exception('Failed to execute statement: ' . implode(', ', $stmt->errorInfo()));
        }
        
        $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $response['success'] = true;
        $response['data'] = $reviews;
        $response['count'] = count($reviews);
    }
    
    // POST new review (visitor submission) - Dutch only
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        error_log("--- Incoming POST request to reviews.php ---");
        $input = json_decode(file_get_contents('php://input'), true);
        error_log("Received input: " . print_r($input, true));

        // Validate input
        $required = ['name', 'review', 'rating'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                throw new Exception(
                    isset($_GET['lang']) && $_GET['lang'] === 'nl' 
                    ? "Vul alle verplichte velden in" 
                    : "Please fill all required fields"
                );
            }
        }
        
        if ($input['rating'] < 1 || $input['rating'] > 5) {
            throw new Exception(
                isset($_GET['lang']) && $_GET['lang'] === 'nl'
                ? "Beoordeling moet tussen 1 en 5 zijn"
                : "Rating must be between 1 and 5"
            );
        }
        
        // Insert only Dutch review (review_nl), leave review_en empty
        $stmt = $pdo->prepare("INSERT INTO reviews 
                              (name, review_nl, rating, approved, publish) 
                              VALUES (?, ?, ?, 1, NOW())");
        
        if (!$stmt) {
            throw new Exception('Failed to prepare insert statement');
        }
        
        $result = $stmt->execute([
            trim($input['name']),
            trim($input['review']),
            (int)$input['rating']
        ]);
        
        if ($result && $stmt->rowCount() > 0) {
            $response['success'] = true;
            $response['message'] = isset($_GET['lang']) && $_GET['lang'] === 'nl'
                ? "Bedankt voor je review! Deze wordt zichtbaar na goedkeuring."
                : "Thank you for your review! It will be visible after approval.";
            error_log("Review submitted successfully. Response: " . json_encode($response));
        } else {
            throw new Exception("Review submission failed: No row inserted");
        }
    }

    // PUT approve/reject review (admin only)
    elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        // Check if checkAdminAccess function exists
        if (function_exists('checkAdminAccess')) {
            checkAdminAccess();
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['id'])) {
            throw new Exception("Review ID is required");
        }
        
        if (!isset($input['approved'])) {
            throw new Exception("Approval status is required");
        }
        
        $approved = (bool)$input['approved'];
        $publish_date = $approved ? date('Y-m-d') : null;
        
        $stmt = $pdo->prepare("UPDATE reviews SET 
                              approved = ?, 
                              publish = ? 
                              WHERE id = ?");
        
        if (!$stmt) {
            throw new Exception('Failed to prepare update statement');
        }
        
        $result = $stmt->execute([$approved ? 1 : 0, $publish_date, $input['id']]);
        
        if ($result) {
            $response['success'] = true;
            $response['message'] = "Review " . ($approved ? "approved" : "rejected");
        } else {
            throw new Exception("Failed to update review");
        }
    }
    
    // DELETE review (admin only)
    elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        // Check if checkAdminAccess function exists
        if (function_exists('checkAdminAccess')) {
            checkAdminAccess();
        }
        
        $review_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
        
        if (!$review_id) {
            throw new Exception("Review ID is required");
        }
        
        $stmt = $pdo->prepare("DELETE FROM reviews WHERE id = ?");
        
        if (!$stmt) {
            throw new Exception('Failed to prepare delete statement');
        }
        
        $result = $stmt->execute([$review_id]);
        
        if ($result) {
            $response['success'] = true;
            $response['message'] = "Review deleted successfully";
        } else {
            throw new Exception("Failed to delete review");
        }
    }
    
    // Invalid method
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