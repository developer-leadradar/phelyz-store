<?php
define('PHELYZ_ACCESS', true);
require_once 'config.php';

// ═══════════════════════════════════════════════════════════════════
// LOCALHOST ONLY PROTECTION
// ═══════════════════════════════════════════════════════════════════
$isLocalhost = in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1']) 
               || strpos($_SERVER['HTTP_HOST'], 'localhost') !== false;

if (!$isLocalhost) {
    die('<h1>Access Denied</h1><p>Uninstall can only be run from localhost.</p>');
}

// ═══════════════════════════════════════════════════════════════════
// TWO-STEP CONFIRMATION
// ═══════════════════════════════════════════════════════════════════
$confirmed = isset($_POST['confirm']) && $_POST['confirm'] === 'DELETE';

if (!$confirmed) {
    // STEP 1: Show scary warning page
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>⚠️ Uninstall Phelyz Store</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: 'Inter', -apple-system, sans-serif;
                background: linear-gradient(135deg, #8B0000 0%, #DC143C 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            .container {
                background: #fff;
                border-radius: 20px;
                padding: 50px 40px;
                max-width: 600px;
                text-align: center;
                box-shadow: 0 20px 60px rgba(0,0,0,0.5);
                border: 4px solid #DC143C;
            }
            .warning-icon {
                font-size: 100px;
                margin-bottom: 20px;
                animation: pulse 2s infinite;
            }
            @keyframes pulse {
                0%, 100% { transform: scale(1); }
                50% { transform: scale(1.1); }
            }
            h1 {
                font-size: 42px;
                color: #DC143C;
                margin-bottom: 20px;
                font-weight: 800;
            }
            .warning-box {
                background: #FFF3CD;
                border: 2px solid #FFA500;
                border-radius: 12px;
                padding: 25px;
                margin: 30px 0;
                text-align: left;
            }
            .warning-box h3 {
                color: #FF6347;
                margin-bottom: 15px;
                font-size: 20px;
            }
            .warning-box ul {
                color: #333;
                padding-left: 20px;
                line-height: 1.8;
            }
            .warning-box li {
                margin: 10px 0;
                font-size: 15px;
            }
            .backup-reminder {
                background: #FFE4E4;
                border: 2px solid #DC143C;
                border-radius: 12px;
                padding: 20px;
                margin: 30px 0;
                font-weight: 600;
                color: #8B0000;
            }
            .confirm-section {
                margin: 40px 0;
            }
            .confirm-section p {
                font-size: 16px;
                color: #333;
                margin-bottom: 20px;
                font-weight: 600;
            }
            input[type="text"] {
                width: 100%;
                padding: 15px;
                border: 3px solid #DC143C;
                border-radius: 10px;
                font-size: 18px;
                text-align: center;
                font-weight: 700;
                margin-bottom: 25px;
                font-family: monospace;
            }
            input[type="text"]:focus {
                outline: none;
                box-shadow: 0 0 0 4px rgba(220,20,60,0.2);
            }
            .buttons {
                display: flex;
                gap: 15px;
                justify-content: center;
            }
            .btn {
                padding: 15px 40px;
                border: none;
                border-radius: 10px;
                font-size: 16px;
                font-weight: 700;
                cursor: pointer;
                transition: all 0.3s;
                text-decoration: none;
                display: inline-block;
            }
            .btn-danger {
                background: #DC143C;
                color: #fff;
            }
            .btn-danger:hover {
                background: #B22222;
                transform: translateY(-2px);
                box-shadow: 0 10px 30px rgba(220,20,60,0.4);
            }
            .btn-cancel {
                background: #6c757d;
                color: #fff;
            }
            .btn-cancel:hover {
                background: #5a6268;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="warning-icon">⚠️</div>
            <h1>DANGER ZONE</h1>
            
            <div class="warning-box">
                <h3>🔥 This Will PERMANENTLY Delete:</h3>
                <ul>
                    <li>✗ Entire database (all products, orders, users, reviews)</li>
                    <li>✗ All uploaded product images</li>
                    <li>✗ All customer data and accounts</li>
                    <li>✗ All order history</li>
                    <li>✗ Installation marker file</li>
                </ul>
            </div>

            <div class="backup-reminder">
                <strong>🛟 HAVE YOU BACKED UP YOUR DATA?</strong><br>
                This action is IRREVERSIBLE. There is NO undo.
            </div>

            <form method="POST" onsubmit="return confirm('Are you ABSOLUTELY SURE? This cannot be undone!');">
                <div class="confirm-section">
                    <p>Type <strong style="color: #DC143C; font-size: 22px;">DELETE</strong> to confirm uninstallation:</p>
                    <input type="text" 
                           name="confirm" 
                           placeholder="Type DELETE here" 
                           autocomplete="off" 
                           required
                           pattern="DELETE"
                           title="Must type DELETE exactly (all caps)">
                </div>

                <div class="buttons">
                    <a href="index.php" class="btn btn-cancel">← Cancel (Go Back)</a>
                    <button type="submit" class="btn btn-danger">🗑️ Uninstall Everything</button>
                </div>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// ═══════════════════════════════════════════════════════════════════
// STEP 2: EXECUTE UNINSTALLATION
// ═══════════════════════════════════════════════════════════════════

$errors = [];
$success = [];

try {
    // Connect to MySQL
    $pdo = new PDO(
        "mysql:host=" . DB_HOST,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // 1. DROP DATABASE
    try {
        $pdo->exec("DROP DATABASE IF EXISTS " . DB_NAME);
        $success[] = "✓ Database '" . DB_NAME . "' dropped successfully";
    } catch (PDOException $e) {
        $errors[] = "✗ Failed to drop database: " . $e->getMessage();
    }
    
    // 2. DELETE .installed FILE
    $installedFile = __DIR__ . '/.installed';
    if (file_exists($installedFile)) {
        if (unlink($installedFile)) {
            $success[] = "✓ Installation marker removed";
        } else {
            $errors[] = "✗ Failed to delete .installed file";
        }
    }
    
    // 3. CLEAR UPLOADED FILES
    $uploadDirs = [
        UPLOAD_PATH . 'products/',
        UPLOAD_PATH . 'categories/',
        UPLOAD_PATH . 'profiles/'
    ];
    
    $filesDeleted = 0;
    foreach ($uploadDirs as $dir) {
        if (is_dir($dir)) {
            $files = glob($dir . '*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    if (unlink($file)) {
                        $filesDeleted++;
                    }
                }
            }
        }
    }
    $success[] = "✓ Cleared $filesDeleted uploaded files";
    
    // 4. DESTROY SESSIONS
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    $success[] = "✓ Sessions cleared";
    
} catch (PDOException $e) {
    $errors[] = "✗ Database connection failed: " . $e->getMessage();
}

// ═══════════════════════════════════════════════════════════════════
// SHOW RESULTS
// ═══════════════════════════════════════════════════════════════════
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo empty($errors) ? '✓ Uninstall Complete' : '⚠️ Uninstall Issues'; ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background: <?php echo empty($errors) ? 'linear-gradient(135deg, #2E7D32 0%, #43A047 100%)' : 'linear-gradient(135deg, #E65100 0%, #FF6F00 100%)'; ?>;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: #fff;
            border-radius: 20px;
            padding: 50px 40px;
            max-width: 600px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .icon {
            font-size: 100px;
            margin-bottom: 20px;
        }
        h1 {
            font-size: 42px;
            color: <?php echo empty($errors) ? '#2E7D32' : '#E65100'; ?>;
            margin-bottom: 30px;
            font-weight: 800;
        }
        .results {
            text-align: left;
            margin: 30px 0;
        }
        .result-item {
            padding: 15px;
            margin: 10px 0;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
        }
        .success-item {
            background: #E8F5E9;
            color: #2E7D32;
            border-left: 4px solid #43A047;
        }
        .error-item {
            background: #FFEBEE;
            color: #C62828;
            border-left: 4px solid #E53935;
        }
        .next-steps {
            background: #F5F5F5;
            border-radius: 12px;
            padding: 25px;
            margin: 30px 0;
            text-align: left;
        }
        .next-steps h3 {
            color: #333;
            margin-bottom: 15px;
        }
        .next-steps ul {
            padding-left: 20px;
            color: #555;
            line-height: 1.8;
        }
        .btn {
            display: inline-block;
            padding: 15px 40px;
            background: <?php echo empty($errors) ? '#2E7D32' : '#E65100'; ?>;
            color: #fff;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 700;
            font-size: 16px;
            margin-top: 20px;
            transition: all 0.3s;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon"><?php echo empty($errors) ? '✅' : '⚠️'; ?></div>
        <h1><?php echo empty($errors) ? 'Uninstall Complete!' : 'Uninstall Issues'; ?></h1>
        
        <div class="results">
            <?php foreach ($success as $msg): ?>
                <div class="result-item success-item"><?php echo $msg; ?></div>
            <?php endforeach; ?>
            
            <?php foreach ($errors as $msg): ?>
                <div class="result-item error-item"><?php echo $msg; ?></div>
            <?php endforeach; ?>
        </div>
        
        <?php if (empty($errors)): ?>
            <div class="next-steps">
                <h3>🎯 What's Next?</h3>
                <ul>
                    <li>Run <strong>install.php</strong> to reinstall with fresh data</li>
                    <li>Your code files are intact — only data was cleared</li>
                    <li>config.php and database.sql are still in place</li>
                </ul>
            </div>
            
            <a href="install.php" class="btn">🚀 Run Fresh Install</a>
        <?php else: ?>
            <p style="color: #E65100; margin-top: 20px;">Some operations failed. Check the errors above and try again.</p>
            <a href="uninstall.php" class="btn">🔄 Try Again</a>
        <?php endif; ?>
    </div>
</body>
</html>