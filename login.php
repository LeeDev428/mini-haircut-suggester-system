<?php
require_once 'config/database.php';

$pdo = getDatabaseConnection();

$error = '';

if ($_POST) {
    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        // Check user credentials
        $query = "SELECT id, username, email, password, role, first_name, last_name FROM users WHERE username = :username OR email = :username";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                    'first_name' => $user['first_name'],
                    'last_name' => $user['last_name']
                ];

                // Update last login
                $update_query = "UPDATE users SET last_login = NOW() WHERE id = :id";
                $update_stmt = $pdo->prepare($update_query);
                $update_stmt->bindParam(':id', $user['id']);
                $update_stmt->execute();

                // Set remember me cookie if checked
                if ($remember) {
                    $token = bin2hex(random_bytes(32));
                    setcookie('remember_token', $token, time() + (86400 * 30), '/'); // 30 days
                }

                // Redirect based on role
                if ($user['role'] === 'admin') {
                    redirect('admin/dashboard.php');
                } else {
                    redirect('user/dashboard.php');
                }
            } else {
                $error = 'Invalid username or password.';
            }
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - HairCut Suggester</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/auth.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="auth-body">
    <div class="auth-container">
        <div class="auth-wrapper">
            <!-- Left Side - Image/Info -->
            <div class="auth-info">
                <div class="auth-info-content">
                    <h2><i class="fas fa-cut"></i> HairCut Suggester</h2>
                    <h3>Welcome back! Ready to discover more amazing hairstyles?</h3>
                    <div class="auth-features">
                        <div class="auth-feature">
                            <i class="fas fa-history"></i>
                            <span>Access your saved styles</span>
                        </div>
                        <div class="auth-feature">
                            <i class="fas fa-chart-line"></i>
                            <span>Track your style journey</span>
                        </div>
                        <div class="auth-feature">
                            <i class="fas fa-bookmark"></i>
                            <span>Get personalized recommendations</span>
                        </div>
                        <div class="auth-feature">
                            <i class="fas fa-comments"></i>
                            <span>Connect with style experts</span>
                        </div>
                    </div>
                    
                </div>
            </div>

            <!-- Right Side - Form -->
            <div class="auth-form-container">
                <form class="auth-form" method="POST" action="">
                    <div class="auth-header">
                        <h2>Welcome Back</h2>
                        <p>Sign in to continue your style journey</p>
                    </div>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-error">
                            <i class="fas fa-exclamation-circle"></i>
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="username">
                            <i class="fas fa-user"></i>
                            Username or Email
                        </label>
                        <input type="text" id="username" name="username" 
                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" 
                               required placeholder="Enter username or email" autofocus>
                    </div>

                    <div class="form-group">
                        <label for="password">
                            <i class="fas fa-lock"></i>
                            Password
                        </label>
                        <div class="password-input">
                            <input type="password" id="password" name="password" required 
                                   placeholder="Enter your password">
                            <button type="button" class="toggle-password password-toggle" aria-label="Toggle password visibility">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-options">
                        <label class="checkbox-label">
                            <input type="checkbox" name="remember">
                            <span class="checkmark"></span>
                            Remember me
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary btn-full">
                        <i class="fas fa-sign-in-alt"></i>
                        Sign In
                    </button>

                    

                    <div class="auth-links">
                        <p>Don't have an account? <a href="register.php">Create one here</a></p>
                        <p><a href="index.php"><i class="fas fa-arrow-left"></i> Back to Home</a></p>
                    </div>
                    
                </form>
            </div>
        </div>
    </div>

    <script src="assets/js/auth.js"></script>
</body>
</html>
