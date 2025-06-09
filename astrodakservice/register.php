<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

$auth = new Auth($pdo);

$errors = [];
$success = false;

// Generate a strong password suggestion
$passwordSuggestion = $auth->generateStrongPassword();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $role = $_POST['role'];

        // Verify password match
        if ($password !== $confirm_password) {
            throw new Exception('Passwords do not match');
        }

        // Register the admin
        $adminId = $auth->registerAdmin($username, $email, $password, $role);
        $success = true;
    } catch (Exception $e) {
        $errors[] = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Registration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .registration-container {
            max-width: 600px;
            margin: 5rem auto;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .password-strength {
            height: 5px;
            margin-top: 5px;
            background: #e9ecef;
        }
        .password-strength-bar {
            height: 100%;
            width: 0%;
            transition: width 0.3s;
        }
        .password-criteria {
            font-size: 0.85rem;
            color: #6c757d;
        }
        .password-criteria.valid {
            color: #28a745;
        }
        .password-toggle {
            cursor: pointer;
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="registration-container">
            <h2 class="text-center mb-4">Admin Registration</h2>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    Registration successful! <a href="login.php">Login here</a>.
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $error): ?>
                        <p class="mb-1"><?= htmlspecialchars($error) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <form method="post" id="registrationForm">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" 
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" 
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="role" class="form-label">Role</label>
                    <select class="form-select" id="role" name="role" required>
                        <option value="admin" <?= ($_POST['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
                        <option value="editor" <?= ($_POST['role'] ?? '') === 'editor' ? 'selected' : '' ?>>Editor</option>
                    </select>
                </div>
                
                <div class="mb-3 position-relative">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                    <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                    <div class="password-strength">
                        <div class="password-strength-bar" id="passwordStrengthBar"></div>
                    </div>
                    <div class="password-criteria">
                        <small id="length" class="text-muted">• At least 12 characters</small><br>
                        <small id="uppercase" class="text-muted">• At least 1 uppercase letter</small><br>
                        <small id="lowercase" class="text-muted">• At least 1 lowercase letter</small><br>
                        <small id="number" class="text-muted">• At least 1 number</small><br>
                        <small id="special" class="text-muted">• At least 1 special character</small>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary mt-2" id="generatePassword">
                        <i class="fas fa-random"></i> Generate Strong Password
                    </button>
                </div>
                
                <div class="mb-4 position-relative">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    <i class="fas fa-eye password-toggle" id="toggleConfirmPassword"></i>
                    <div class="invalid-feedback" id="passwordMatchFeedback">
                        Passwords do not match
                    </div>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary" id="registerBtn">Register</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/zxcvbn/4.4.2/zxcvbn.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Password toggle functionality
            const togglePassword = document.querySelector('#togglePassword');
            const toggleConfirmPassword = document.querySelector('#toggleConfirmPassword');
            const password = document.querySelector('#password');
            const confirmPassword = document.querySelector('#confirm_password');
            
            togglePassword.addEventListener('click', function() {
                const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                password.setAttribute('type', type);
                this.classList.toggle('fa-eye-slash');
            });
            
            toggleConfirmPassword.addEventListener('click', function() {
                const type = confirmPassword.getAttribute('type') === 'password' ? 'text' : 'password';
                confirmPassword.setAttribute('type', type);
                this.classList.toggle('fa-eye-slash');
            });
            
            // Password strength meter
            password.addEventListener('input', function() {
                const strength = zxcvbn(this.value);
                const strengthBar = document.querySelector('#passwordStrengthBar');
                
                // Update strength bar
                strengthBar.style.width = `${strength.score * 25}%`;
                
                // Update color based on strength
                switch(strength.score) {
                    case 0:
                    case 1:
                        strengthBar.style.backgroundColor = '#dc3545';
                        break;
                    case 2:
                        strengthBar.style.backgroundColor = '#fd7e14';
                        break;
                    case 3:
                        strengthBar.style.backgroundColor = '#ffc107';
                        break;
                    case 4:
                        strengthBar.style.backgroundColor = '#28a745';
                        break;
                }
                
                // Validate password criteria
                const hasLength = this.value.length >= 12;
                const hasUpper = /[A-Z]/.test(this.value);
                const hasLower = /[a-z]/.test(this.value);
                const hasNumber = /[0-9]/.test(this.value);
                const hasSpecial = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(this.value);
                
                document.querySelector('#length').classList.toggle('valid', hasLength);
                document.querySelector('#uppercase').classList.toggle('valid', hasUpper);
                document.querySelector('#lowercase').classList.toggle('valid', hasLower);
                document.querySelector('#number').classList.toggle('valid', hasNumber);
                document.querySelector('#special').classList.toggle('valid', hasSpecial);
            });
            
            // Password match validation
            confirmPassword.addEventListener('input', function() {
                const match = password.value === this.value;
                this.classList.toggle('is-invalid', !match && this.value.length > 0);
                document.querySelector('#passwordMatchFeedback').style.display = match ? 'none' : 'block';
            });
            
            // Generate password button
            document.querySelector('#generatePassword').addEventListener('click', function() {
                const generatedPassword = generatePassword();
                password.value = generatedPassword;
                confirmPassword.value = generatedPassword;
                
                // Trigger input events to update UI
                password.dispatchEvent(new Event('input'));
                confirmPassword.dispatchEvent(new Event('input'));
            });
            
            // Form validation
            document.querySelector('#registrationForm').addEventListener('submit', function(e) {
                if (password.value !== confirmPassword.value) {
                    e.preventDefault();
                    confirmPassword.classList.add('is-invalid');
                    document.querySelector('#passwordMatchFeedback').style.display = 'block';
                }
            });
            
            function generatePassword() {
                const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+-=[]{}|;:,.<>?';
                let password = '';
                
                // Ensure at least one character from each category
                password += getRandomChar('abcdefghijklmnopqrstuvwxyz');
                password += getRandomChar('ABCDEFGHIJKLMNOPQRSTUVWXYZ');
                password += getRandomChar('0123456789');
                password += getRandomChar('!@#$%^&*()_+-=[]{}|;:,.<>?');
                
                // Fill the rest randomly
                for (let i = 0; i < 8; i++) {
                    password += chars.charAt(Math.floor(Math.random() * chars.length));
                }
                
                // Shuffle the password
                return password.split('').sort(() => 0.5 - Math.random()).join('');
            }
            
            function getRandomChar(charSet) {
                return charSet.charAt(Math.floor(Math.random() * charSet.length));
            }
        });
    </script>
</body>
</html>