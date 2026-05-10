<?php
require_once __DIR__ . '/core/Autoloader.php';
use Core\Autoloader;
use Config\Auth;
use Models\User;

Autoloader::register();

// Log the logout activity
if (Auth::isLoggedIn()) {
    $userModel = new User();
    $userModel->logActivity(Auth::getUserId(), 'logout', 'User logged out');
}

// Destroy session
Auth::logout();

// Redirect to login page
header('Location: login.php');
exit;
?>