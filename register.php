<?php
require_once 'config/database.php';

$pdo = getDatabaseConnection();

$error = '';
$success = '';

if ($_POST) {
    $username = sanitizeInput($_POST['username']);
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $first_name = sanitizeInput($_POST['first_name']);
    $last_name = sanitizeInput($_POST['last_name']);
    $terms_accepted = isset($_POST['terms_accepted']);

    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($first_name) || empty($last_name)) {
        $error = 'All fields are required.';
    } elseif (!$terms_accepted) {
        $error = 'You must accept the terms and conditions.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($username) < 3) {
        $error = 'Username must be at least 3 characters long.';
    } else {
        // Check if username or email already exists
        $check_query = "SELECT id FROM users WHERE username = :username OR email = :email";
        $check_stmt = $pdo->prepare($check_query);
        $check_stmt->bindParam(':username', $username);
        $check_stmt->bindParam(':email', $email);
        $check_stmt->execute();

        if ($check_stmt->rowCount() > 0) {
            $error = 'Username or email already exists.';
        } else {
            // Insert new user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $insert_query = "INSERT INTO users (username, email, password, first_name, last_name, role) VALUES (:username, :email, :password, :first_name, :last_name, 'user')";
            $insert_stmt = $pdo->prepare($insert_query);
            
            $insert_stmt->bindParam(':username', $username);
            $insert_stmt->bindParam(':email', $email);
            $insert_stmt->bindParam(':password', $hashed_password);
            $insert_stmt->bindParam(':first_name', $first_name);
            $insert_stmt->bindParam(':last_name', $last_name);

            if ($insert_stmt->execute()) {
                $success = 'Registration successful! You can now log in.';
                // Auto-login the user
                $user_id = $pdo->lastInsertId();
                $_SESSION['user'] = [
                    'id' => $user_id,
                    'username' => $username,
                    'email' => $email,
                    'role' => 'user',
                    'first_name' => $first_name,
                    'last_name' => $last_name
                ];
                
                // Redirect to user dashboard
                header('Location: user/dashboard.php');
                exit();
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - HairCut Suggester</title>
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
                    <h3>Join thousands of users who found their perfect hairstyle</h3>
                    <div class="auth-features">
                        <div class="auth-feature">
                            <i class="fas fa-magic"></i>
                            <span>Personalized recommendations</span>
                        </div>
                        <div class="auth-feature">
                            <i class="fas fa-users"></i>
                            <span>Expert stylist advice</span>
                        </div>
                        <div class="auth-feature">
                            <i class="fas fa-mobile-alt"></i>
                            <span>Mobile-friendly design</span>
                        </div>
                        <div class="auth-feature">
                            <i class="fas fa-heart"></i>
                            <span>Save your favorite styles</span>
                        </div>
                    </div>
                    <div class="auth-testimonial">
                        <p>"This app helped me find the perfect haircut for my face shape. I've never been more confident!"</p>
                        <span>- Sarah M.</span>
                    </div>
                </div>
            </div>

            <!-- Right Side - Form -->
            <div class="auth-form-container">
                <form class="auth-form" method="POST" action="">
                    <div class="auth-header">
                        <h2>Create Your Account</h2>
                        <p>Start your journey to the perfect hairstyle</p>
                    </div>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-error">
                            <i class="fas fa-exclamation-circle"></i>
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            <?php echo htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name">
                                <i class="fas fa-user"></i>
                                First Name
                            </label>
                            <input type="text" id="first_name" name="first_name" 
                                   value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>" 
                                   required placeholder="Enter your first name">
                        </div>

                        <div class="form-group">
                            <label for="last_name">
                                <i class="fas fa-user"></i>
                                Last Name
                            </label>
                            <input type="text" id="last_name" name="last_name" 
                                   value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>" 
                                   required placeholder="Enter your last name">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="username">
                            <i class="fas fa-at"></i>
                            Username
                        </label>
                        <input type="text" id="username" name="username" 
                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" 
                               required placeholder="Choose a username">
                    </div>

                    <div class="form-group">
                        <label for="email">
                            <i class="fas fa-envelope"></i>
                            Email Address
                        </label>
                        <input type="email" id="email" name="email" 
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                               required placeholder="Enter your email">
                    </div>

                    <div class="form-group">
                        <label for="password">
                            <i class="fas fa-lock"></i>
                            Password
                        </label>
                        <div class="password-input">
                            <input type="password" id="password" name="password" required 
                                   placeholder="Choose a strong password">
                            <button type="button" class="password-toggle" onclick="togglePassword('password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="password-strength">
                            <div class="strength-meter">
                                <div class="strength-fill"></div>
                            </div>
                            <span class="strength-text">Password strength</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">
                            <i class="fas fa-lock"></i>
                            Confirm Password
                        </label>
                        <div class="password-input">
                            <input type="password" id="confirm_password" name="confirm_password" required 
                                   placeholder="Confirm your password">
                            <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-group checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="terms_accepted" required>
                            <span class="checkmark"></span>
                            I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary btn-full">
                        <i class="fas fa-user-plus"></i>
                        Create Account
                    </button>

                    <div class="auth-divider">
                        <span>or</span>
                    </div>

                  

                    <div class="auth-links">
                        <p>Already have an account? <a href="login.php">Log in here</a></p>
                        <p><a href="index.php"><i class="fas fa-arrow-left"></i> Back to Home</a></p>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="assets/js/auth.js"></script>
</body>
</html>
