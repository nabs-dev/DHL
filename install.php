<?php
/**
 * DHL Clone - Installation Script
 * 
 * This file handles the installation process for the DHL Clone application
 */

// Check if already installed
if (file_exists('installed.lock')) {
    die('Application is already installed. Delete the installed.lock file to reinstall.');
}

// Database connection parameters
$host = isset($_POST['db_host']) ? $_POST['db_host'] : 'localhost';
$username = isset($_POST['db_username']) ? $_POST['db_username'] : 'u8gr0sjr9p4p4';
$password = isset($_POST['db_password']) ? $_POST['db_password'] : '9yxuqyo3mt85';
$database = isset($_POST['db_name']) ? $_POST['db_name'] : 'dbtwrbnongvzqh';

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$error = '';
$success = '';

// Process installation steps
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($step) {
        case 1:
            // Check database connection
            $conn = @new mysqli($host, $username, $password);
            
            if ($conn->connect_error) {
                $error = 'Database connection failed: ' . $conn->connect_error;
            } else {
                // Check if database exists
                $result = $conn->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$database'");
                
                if ($result->num_rows === 0) {
                    // Create database
                    if (!$conn->query("CREATE DATABASE IF NOT EXISTS `$database`")) {
                        $error = 'Failed to create database: ' . $conn->error;
                    } else {
                        $success = 'Database connection successful and database created.';
                        $step = 2;
                    }
                } else {
                    $success = 'Database connection successful.';
                    $step = 2;
                }
                
                $conn->close();
            }
            break;
            
        case 2:
            // Create tables
            $conn = @new mysqli($host, $username, $password, $database);
            
            if ($conn->connect_error) {
                $error = 'Database connection failed: ' . $conn->connect_error;
                $step = 1;
            } else {
                // Read SQL file
                $sql = file_get_contents('create_tables.sql');
                
                // Execute SQL queries
                if ($conn->multi_query($sql)) {
                    do {
                        // Store first result set
                        if ($result = $conn->store_result()) {
                            $result->free();
                        }
                        // Check for more results
                    } while ($conn->more_results() && $conn->next_result());
                    
                    if ($conn->error) {
                        $error = 'Error creating tables: ' . $conn->error;
                    } else {
                        $success = 'Tables created successfully.';
                        $step = 3;
                    }
                } else {
                    $error = 'Error executing SQL: ' . $conn->error;
                }
                
                $conn->close();
            }
            break;
            
        case 3:
            // Create admin user
            $admin_username = isset($_POST['admin_username']) ? $_POST['admin_username'] : '';
            $admin_password = isset($_POST['admin_password']) ? $_POST['admin_password'] : '';
            $admin_email = isset($_POST['admin_email']) ? $_POST['admin_email'] : '';
            
            if (empty($admin_username) || empty($admin_password) || empty($admin_email)) {
                $error = 'All fields are required.';
            } else {
                $conn = @new mysqli($host, $username, $password, $database);
                
                if ($conn->connect_error) {
                    $error = 'Database connection failed: ' . $conn->connect_error;
                    $step = 1;
                } else {
                    // Hash password
                    $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
                    
                    // Insert admin user
                    $stmt = $conn->prepare("
                        INSERT INTO admin_users (username, password, email) 
                        VALUES (?, ?, ?)
                        ON DUPLICATE KEY UPDATE password = ?, email = ?
                    ");
                    $stmt->bind_param("sssss", $admin_username, $hashed_password, $admin_email, $hashed_password, $admin_email);
                    
                    if ($stmt->execute()) {
                        $success = 'Admin user created successfully.';
                        $step = 4;
                        
                        // Create db.php file
                        $db_config = "<?php
// Database connection parameters
\$host = '$host';
\$username = '$username';
\$password = '$password';
\$database = '$database';

// Create connection
\$conn = new mysqli(\$host, \$username, \$password, \$database);

// Check connection
if (\$conn->connect_error) {
    die('Connection failed: ' . \$conn->connect_error);
}

// Set character set
\$conn->set_charset('utf8mb4');
?>";
                        
                        file_put_contents('db.php', $db_config);
                        
                        // Create installed.lock file
                        file_put_contents('installed.lock', date('Y-m-d H:i:s'));
                    } else {
                        $error = 'Error creating admin user: ' . $stmt->error;
                    }
                    
                    $conn->close();
                }
            }
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DHL Clone - Installation</title>
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
            padding: 20px 0;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .logo {
            font-size: 32px;
            font-weight: bold;
            color: #d40511;
        }
        
        /* Main Content */
        .main-content {
            max-width: 800px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .page-title {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
        }
        
        /* Installation Container */
        .installation-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            padding: 30px;
        }
        
        /* Steps */
        .steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            position: relative;
        }
        
        .steps::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 0;
            right: 0;
            height: 2px;
            background-color: #ddd;
            z-index: 1;
        }
        
        .step {
            position: relative;
            z-index: 2;
            background-color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #ddd;
            font-weight: bold;
            color: #777;
        }
        
        .step.active {
            border-color: #d40511;
            color: #d40511;
        }
        
        .step.completed {
            border-color: #4caf50;
            background-color: #4caf50;
            color: white;
        }
        
        /* Step Content */
        .step-content {
            margin-bottom: 30px;
        }
        
        .step-title {
            margin-bottom: 20px;
            color: #333;
        }
        
        /* Forms */
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .form-submit {
            padding: 10px 20px;
            background-color: #d40511;
            color: white;
            border: none;
            border-radius: 4px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .form-submit:hover {
            background-color: #b00;
        }
        
        /* Messages */
        .message {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .success-message {
            background-color: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid #2e7d32;
        }
        
        .error-message {
            background-color: #ffebee;
            color: #c62828;
            border-left: 4px solid #c62828;
        }
        
        /* Completion */
        .completion {
            text-align: center;
        }
        
        .completion-icon {
            font-size: 60px;
            color: #4caf50;
            margin-bottom: 20px;
        }
        
        .completion-title {
            font-size: 24px;
            margin-bottom: 20px;
            color: #333;
        }
        
        .completion-message {
            margin-bottom: 30px;
        }
        
        .completion-button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #d40511;
            color: white;
            border-radius: 4px;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        
        .completion-button:hover {
            background-color: #b00;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="logo">DHL Clone Installation</div>
    </header>

    <!-- Main Content -->
    <div class="main-content">
        <h1 class="page-title">Installation Wizard</h1>
        
        <div class="installation-container">
            <!-- Steps -->
            <div class="steps">
                <div class="step <?php echo ($step >= 1) ? 'active' : ''; ?> <?php echo ($step > 1) ? 'completed' : ''; ?>">1</div>
                <div class="step <?php echo ($step >= 2) ? 'active' : ''; ?> <?php echo ($step > 2) ? 'completed' : ''; ?>">2</div>
                <div class="step <?php echo ($step >= 3) ? 'active' : ''; ?> <?php echo ($step > 3) ? 'completed' : ''; ?>">3</div>
                <div class="step <?php echo ($step >= 4) ? 'active' : ''; ?>">4</div>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="message error-message">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="message success-message">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($step === 1): ?>
                <!-- Step 1: Database Configuration -->
                <div class="step-content">
                    <h2 class="step-title">Step 1: Database Configuration</h2>
                    <p>Enter your database connection details below:</p>
                    
                    <form method="POST" action="install.php?step=1">
                        <div class="form-group">
                            <label for="db_host">Database Host</label>
                            <input type="text" id="db_host" name="db_host" value="<?php echo htmlspecialchars($host); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="db_username">Database Username</label>
                            <input type="text" id="db_username" name="db_username" value="<?php echo htmlspecialchars($username); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="db_password">Database Password</label>
                            <input type="password" id="db_password" name="db_password" value="<?php echo htmlspecialchars($password); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="db_name">Database Name</label>
                            <input type="text" id="db_name" name="db_name" value="<?php echo htmlspecialchars($database); ?>" required>
                        </div>
                        
                        <button type="submit" class="form-submit">Continue</button>
                    </form>
                </div>
            <?php elseif ($step === 2): ?>
                <!-- Step 2: Create Tables -->
                <div class="step-content">
                    <h2 class="step-title">Step 2: Create Database Tables</h2>
                    <p>Click the button below to create the necessary database tables:</p>
                    
                    <form method="POST" action="install.php?step=2">
                        <input type="hidden" name="db_host" value="<?php echo htmlspecialchars($host); ?>">
                        <input type="hidden" name="db_username" value="<?php echo htmlspecialchars($username); ?>">
                        <input type="hidden" name="db_password" value="<?php echo htmlspecialchars($password); ?>">
                        <input type="hidden" name="db_name" value="<?php echo htmlspecialchars($database); ?>">
                        
                        <button type="submit" class="form-submit">Create Tables</button>
                    </form>
                </div>
            <?php elseif ($step === 3): ?>
                <!-- Step 3: Create Admin User -->
                <div class="step-content">
                    <h2 class="step-title">Step 3: Create Admin User</h2>
                    <p>Create an administrator account to manage the system:</p>
                    
                    <form method="POST" action="install.php?step=3">
                        <input type="hidden" name="db_host" value="<?php echo htmlspecialchars($host); ?>">
                        <input type="hidden" name="db_username" value="<?php echo htmlspecialchars($username); ?>">
                        <input type="hidden" name="db_password" value="<?php echo htmlspecialchars($password); ?>">
                        <input type="hidden" name="db_name" value="<?php echo htmlspecialchars($database); ?>">
                        
                        <div class="form-group">
                            <label for="admin_username">Admin Username</label>
                            <input type="text" id="admin_username" name="admin_username" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="admin_password">Admin Password</label>
                            <input type="password" id="admin_password" name="admin_password" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="admin_email">Admin Email</label>
                            <input type="email" id="admin_email" name="admin_email" required>
                        </div>
                        
                        <button type="submit" class="form-submit">Create Admin</button>
                    </form>
                </div>
            <?php elseif ($step === 4): ?>
                <!-- Step 4: Installation Complete -->
                <div class="step-content completion">
                    <div class="completion-icon">✓</div>
                    <h2 class="completion-title">Installation Complete!</h2>
                    <p class="completion-message">The DHL Clone has been successfully installed. You can now start using the application.</p>
                    
                    <a href="index.php" class="completion-button">Go to Homepage</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
