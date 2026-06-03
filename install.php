<?php
define('PHELYZ_ACCESS', true);
require_once 'config.php';

// Check if already installed
$check_file = __DIR__ . '/.installed';
if (file_exists($check_file)) {
    die('<h1>Already Installed!</h1><p>Delete the .installed file to reinstall.</p>');
}

// Database connection
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
    $pdo->exec("USE " . DB_NAME);
    
    // Read SQL file
    $sql = file_get_contents(__DIR__ . '/database.sql');
    
    // Execute SQL
    $pdo->exec($sql);
    
    // Create uploads directory
    if (!is_dir(UPLOAD_PATH)) {
        mkdir(UPLOAD_PATH, 0755, true);
        mkdir(UPLOAD_PATH . 'products/', 0755, true);
        mkdir(UPLOAD_PATH . 'categories/', 0755, true);
        mkdir(UPLOAD_PATH . 'profiles/', 0755, true);
    }
    
    // Create .installed file
    file_put_contents($check_file, date('Y-m-d H:i:s'));
    
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Installation Successful - Phelyz Store</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: 'Inter', -apple-system, sans-serif;
                background: linear-gradient(135deg, #000 0%, #1a1a1a 100%);
                color: #fff;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            .container {
                background: #1a1a1a;
                border: 2px solid #FFD700;
                border-radius: 20px;
                padding: 60px 40px;
                max-width: 600px;
                text-align: center;
                box-shadow: 0 20px 60px rgba(255, 215, 0, 0.3);
            }
            h1 {
                font-size: 48px;
                color: #FFD700;
                margin-bottom: 20px;
                font-weight: 700;
            }
            .success-icon {
                font-size: 80px;
                margin-bottom: 30px;
            }
            p {
                font-size: 18px;
                line-height: 1.8;
                margin-bottom: 15px;
                color: #ccc;
            }
            .credentials {
                background: #000;
                border: 1px solid #FFD700;
                border-radius: 10px;
                padding: 30px;
                margin: 30px 0;
                text-align: left;
            }
            .credentials h3 {
                color: #FFD700;
                margin-bottom: 20px;
                font-size: 24px;
            }
            .cred-item {
                margin: 15px 0;
                padding: 15px;
                background: #1a1a1a;
                border-radius: 8px;
            }
            .cred-item strong {
                color: #FFD700;
                display: block;
                margin-bottom: 5px;
            }
            .cred-item code {
                background: #000;
                padding: 5px 10px;
                border-radius: 5px;
                color: #fff;
                font-family: 'Courier New', monospace;
            }
            .buttons {
                margin-top: 40px;
                display: flex;
                gap: 20px;
                justify-content: center;
                flex-wrap: wrap;
            }
            .btn {
                padding: 15px 40px;
                border: none;
                border-radius: 10px;
                font-size: 16px;
                font-weight: 600;
                cursor: pointer;
                text-decoration: none;
                display: inline-block;
                transition: all 0.3s;
            }
            .btn-primary {
                background: #FFD700;
                color: #000;
            }
            .btn-primary:hover {
                background: #FFC700;
                transform: translateY(-2px);
                box-shadow: 0 10px 30px rgba(255, 215, 0, 0.4);
            }
            .btn-secondary {
                background: transparent;
                color: #FFD700;
                border: 2px solid #FFD700;
            }
            .btn-secondary:hover {
                background: #FFD700;
                color: #000;
            }
            .warning {
                background: #ff4444;
                color: #fff;
                padding: 15px;
                border-radius: 8px;
                margin-top: 30px;
                font-size: 14px;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="success-icon">💎</div>
            <h1>Installation Successful!</h1>
            <p>Phelyz Diamond Store has been installed successfully.</p>
            <p>Database created with 15 sample products across 8 categories.</p>
            
            <div class="credentials">
                <h3>📝 Default Login Credentials</h3>
                
                <div class="cred-item">
                    <strong>🔐 Admin Account:</strong>
                    <code>admin@phelyz.com</code> / <code>admin123</code>
                </div>
                
                <div class="cred-item">
                    <strong>👤 Customer Account:</strong>
                    <code>customer@example.com</code> / <code>customer123</code>
                </div>
            </div>
            
            <div class="buttons">
                <a href="index.php" class="btn btn-primary">Visit Store 🛍️</a>
                <a href="admin/login.php" class="btn btn-secondary">Admin Panel ⚙️</a>
            </div>
            
            <div class="warning">
                <strong>⚠️ Security Warning:</strong> Delete or rename install.php and database.sql files after installation!
            </div>
        </div>
    </body>
    </html>
    <?php
    
} catch (PDOException $e) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Installation Failed</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                background: #f44336;
                color: #fff;
                padding: 50px;
                text-align: center;
            }
            .error-box {
                background: #fff;
                color: #f44336;
                padding: 40px;
                border-radius: 10px;
                max-width: 600px;
                margin: 0 auto;
            }
        </style>
    </head>
    <body>
        <div class="error-box">
            <h1>❌ Installation Failed</h1>
            <p><strong>Error:</strong> <?php echo $e->getMessage(); ?></p>
            <p>Please check your database configuration in config.php</p>
        </div>
    </body>
    </html>
    <?php
}
?>