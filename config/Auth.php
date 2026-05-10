<?php
namespace Config;

class Auth {
    private static $sessionKey = 'user_logged_in';
    
    // Start session if not started
    public static function init() {
        if (session_status() === PHP_SESSION_NONE) {
            // session_start();
        }
    }
    
    // Login user
    public static function login($user) {
        self::init();
        $_SESSION[self::$sessionKey] = true;
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['login_time'] = time();
    }
    
    // Check if user is logged in
    public static function isLoggedIn() {
        self::init();
        return isset($_SESSION[self::$sessionKey]) && $_SESSION[self::$sessionKey] === true;
    }
    
    // Get current user ID
    public static function getUserId() {
        self::init();
        return $_SESSION['user_id'] ?? null;
    }
    
    // Get current user role
    public static function getUserRole() {
        self::init();
        return $_SESSION['role'] ?? null;
    }
    
    // Get current username
    public static function getUsername() {
        self::init();
        return $_SESSION['username'] ?? null;
    }
    
    // Get current user full name
    public static function getFullName() {
        self::init();
        return $_SESSION['full_name'] ?? null;
    }
    
    // Logout user
    public static function logout() {
        self::init();
        session_destroy();
    }
    
    // Require login - redirect if not logged in
    public static function requireLogin() {
        self::init();
        if (!self::isLoggedIn()) {
            header('Location: /JandJLoanApp/login.php');
            exit;
        }
    }
    
    // Require specific role
    public static function requireRole($role) {
        self::requireLogin();
        if ($_SESSION['role'] !== $role && $_SESSION['role'] !== 'admin') {
            header('Location: /JandJLoanApp/dashboard.php?error=unauthorized');
            exit;
        }
    }
    
    // Hash password
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }
    
    // Verify password
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    // Generate CSRF token
    public static function generateCSRFToken() {
        self::init();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    // Verify CSRF token
    public static function verifyCSRFToken($token) {
        self::init();
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}
?>