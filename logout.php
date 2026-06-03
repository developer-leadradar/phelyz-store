<?php
define('PHELYZ_ACCESS', true);
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Destroy session
session_destroy();

// Clear session variables
$_SESSION = [];

// Redirect to homepage
header('Location: index.php?logout=success');
exit;
?>