<?php
define('PHELYZ_ACCESS', true);
require_once '../config.php';

session_destroy();
$_SESSION = [];

header('Location: login.php');
exit;
?>