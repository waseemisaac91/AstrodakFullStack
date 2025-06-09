<?php
class Auth {
    private $pdo;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }

    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    public function login($username, $password) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['lang'] = $user['language'] ?? 'en';
            return true;
        }
        return false;
    }

    public function logout() {
        session_unset();
        session_destroy();
        header('Location: login.php');
        exit();
    }

    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }

        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function hasRole($role) {
        $user = $this->getCurrentUser();
        return $user && $user['role'] === $role;
    }

    public function requireRole($role) {
        $this->requireLogin();
        if (!$this->hasRole($role)) {
            header('Location: unauthorized.php');
            exit();
        }
    }



    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: login.php');
            exit();
        }
    }

    public function registerAdmin($username, $email, $password, $role = 'admin') {
        // Validate inputs
        if (empty($username) || empty($email) || empty($password)) {
            throw new Exception('All fields are required');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format');
        }

        if (strlen($password) < 12) {
            throw new Exception('Password must be at least 12 characters');
        }

        // Check if username/email exists
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            throw new Exception('Username or email already exists');
        }

        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Insert new admin
        $stmt = $this->pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
        $success = $stmt->execute([$username, $email, $hashedPassword, $role]);

        if (!$success) {
            throw new Exception('Registration failed. Please try again.');
        }

        return $this->pdo->lastInsertId();
    }

    public function generateStrongPassword($length = 12) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+-=[]{}|;:,.<>?';
        $password = '';
        $charsLength = strlen($chars) - 1;
        
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, $charsLength)];
        }
        
        return $password;
    }
// Add to your existing Auth class
public function checkPermission($requiredPermission) {
    $user = $this->getCurrentUser();
    
    if (!$user) {
        return false;
    }
    
    // Simple role-based permissions
    $permissions = [
        'admin' => ['add_project', 'edit_project', 'delete_project', 'moderate_reviews'],
        'editor' => ['add_project', 'edit_project']
    ];
    
    return isset($permissions[$user['role']]) && 
           in_array($requiredPermission, $permissions[$user['role']]);
}
// Add to your Auth class
public function requireEditorOrHigher() {
    $this->requireLogin();
    
    if (!$this->hasRole('admin') && !$this->hasRole('editor')) {
        header('Location: /unauthorized.php');
        exit();
    }
}
}
?>