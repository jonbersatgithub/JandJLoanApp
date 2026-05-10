<?php
namespace Core;

class Autoloader {
    private static $baseDir = null;
    
    public static function register() {
        spl_autoload_register([__CLASS__, 'load']);
        self::$baseDir = dirname(__DIR__) . '/';
    }
    
    public static function load($className) {
        // Remove leading backslash
        $className = ltrim($className, '\\');
        
        // Convert namespace to file path
        $filePath = '';
        $lastNsPos = strrpos($className, '\\');
        
        if ($lastNsPos !== false) {
            $namespace = substr($className, 0, $lastNsPos);
            $className = substr($className, $lastNsPos + 1);
            $filePath = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
        }
        
        $filePath .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';
        
        // Full path to file
        $fullPath = self::$baseDir . $filePath;
        
        // Debug: Uncomment to see what's being loaded
        // error_log("Attempting to load: " . $fullPath);
        
        if (file_exists($fullPath)) {
            require_once $fullPath;
            return true;
        }
        
        return false;
    }
}
?>