<?php
// Start session
session_start();

// Include database connection
require_once 'db.php';

// Check if admin signup is allowed (you might want to restrict this in production)
$allow_signup = true; // Set to false to disable signup

// Initialize variables
$error_message = '';
$success_message = '';

// Process signup form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $email = $_POST['email'] ?? '';
    
    // Validate input
    if (empty($username) || empty($password) || empty($confirm_password) || empty($email)) {
        $error_message = 'All fields are required.';
    } elseif ($password !== $confirm_password) {
        $error_message = 'Passwords do not match.';
    } elseif (strlen($password) < 8) {
        $error_message = 'Password must be at least 8 characters long.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address.';
    } else {
        // Check if username already exists
        $stmt = $conn->prepare("SELECT id FROM admin_users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error_message = 'Username already exists. Please choose another one.';
        } else {
            // Check if email already exists
            $stmt = $conn->prepare("SELECT id FROM admin_users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error_message = 'Email already in use. Please use another email address.';
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new admin user
                $stmt = $conn->prepare("INSERT INTO admin_users (username, password, email) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $username, $hashed_password, $email);
                
                if ($stmt->execute()) {
                    $success_message = 'Account created successfully. You can now log in.';
                    
                    // Log the activity
                    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
                    $log_message = "New admin account created: $username | IP: $ip";
                    file_put_contents('logs/admin_signup.log', date('Y-m-d H:i:s') . " - $log_message\n", FILE_APPEND);
                    
                    // Redirect to login page after 3 seconds
                    header("refresh:3;url=admin_login.php");
                } else {
                    $error_message = 'Error creating account: ' . $conn->error;
                }
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
    <title>DHL - Admin Signup</title>
    <style>
        /* Reset CSS */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }
        
        /* Global Styles */
        body {
            background-color: #f5f5f5;
            color: #333;
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        a {
            text-decoration: none;
            color: #d40511;
        }
        
        a:hover {
            text-decoration: underline;
        }
        
        /* Header Styles */
        header {
            background-color: #ffcc00;
            padding: 15px 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .logo {
            font-size: 32px;
            font-weight: bold;
            color: #d40511;
        }
        
        .logo span {
            color: #d40511;
        }
        
        nav ul {
            display: flex;
            list-style: none;
        }
        
        nav ul li {
            margin-left: 20px;
        }
        
        nav ul li a {
            color: #333;
            font-weight: bold;
            transition: color 0.3s;
        }
        
        nav ul li a:hover {
            color: #d40511;
            text-decoration: none;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 50px 20px;
        }
        
        /* Signup Form */
        .signup-container {
            max-width: 500px;
            width: 100%;
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .signup-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .signup-header h1 {
            color: #d40511;
            margin-bottom: 10px;
        }
        
        .signup-header p {
            color: #666;
        }
        
        .signup-form {
            display: flex;
            flex-direction: column;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #555;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            border-color: #d40511;
            outline: none;
        }
        
        .password-requirements {
            font-size: 12px;
            color: #777;
            margin-top: 5px;
        }
        
        .signup-button {
            padding: 12px;
            background-color: #d40511;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .signup-button:hover {
            background-color: #b00;
        }
        
        .login-link {
            text-align: center;
            margin-top: 20px;
        }
        
        /* Messages */
        .message {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .error-message {
            background-color: #ffebee;
            color: #d40511;
            border: 1px solid #ffcdd2;
        }
        
        .success-message {
            background-color: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
        }
        
        /* Footer */
        footer {
            background-color: #333;
            color: white;
            padding: 20px 0;
            margin-top: auto;
        }
        
        .footer-bottom {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            text-align: center;
            font-size: 14px;
            color: #ddd;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .header-container {
                flex-direction: column;
                text-align: center;
            }
            
            nav ul {
                margin-top: 15px;
                justify-content: center;
            }
            
            nav ul li {
                margin: 0 10px;
            }
            
            .signup-container {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="header-container">
            <div class="logo">DHL</div>
            <nav>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="#">Services</a></li>
                    <li><a href="#">About Us</a></li>
                    <li><a href="#">Contact</a></li>
                    <li><a href="admin_login.php">Admin Login</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <div class="main-content">
        <div class="signup-container">
            <div class="signup-header">
                <h1>Admin Signup</h1>
                <p>Create a new administrator account</p>
            </div>
            
            <?php if (!$allow_signup): ?>
                <div class="message error-message">
                    Admin signup is currently disabled. Please contact the system administrator.
                </div>
            <?php else: ?>
                <?php if (!empty($error_message)): ?>
                    <div class="message error-message">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($success_message)): ?>
                    <div class="message success-message">
                        <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>
                
                <form class="signup-form" method="POST" action="admin_signup.php">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                        <div class="password-requirements">
                            Password must be at least 8 characters long.
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <button type="submit" class="signup-button">Create Account</button>
                </form>
                
                <div class="login-link">
                    Already have an account? <a href="admin_login.php">Login here</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> DHL International GmbH. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // Client-side validation
        document.querySelector('.signup-form').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const email = document.getElementById('email').value;
            
            // Validate password length
            if (password.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long.');
                return;
            }
            
            // Validate password match
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match.');
                return;
            }
            
            // Basic email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                e.preventDefault();
                alert('Please enter a valid email address.');
                return;
            }
        });
    </script>
</body>
</html>
