<?php
namespace Core;

abstract class Controller {
    protected $model;
    
    /**
     * Send JSON response
     * @param mixed $data
     * @param int $statusCode
     */
    protected function jsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        echo json_encode($data);
        exit;
    }
    
    /**
     * Send success response
     * @param string $message
     * @param mixed $data
     * @return void
     */
    protected function success($message = 'Success', $data = null) {
        return $this->jsonResponse([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Send error response
     * @param string $message
     * @param int $statusCode
     * @return void
     */
    protected function error($message = 'Error', $statusCode = 400) {
        return $this->jsonResponse([
            'success' => false,
            'message' => $message,
            'error_code' => $statusCode,
            'timestamp' => date('Y-m-d H:i:s')
        ], $statusCode);
    }
    
    /**
     * Validate required fields
     * @param array $data
     * @param array $fields
     * @return bool|string
     */
    protected function validateRequired($data, $fields) {
        foreach($fields as $field) {
            if(!isset($data[$field]) || empty($data[$field])) {
                return "Field '{$field}' is required";
            }
        }
        return true;
    }
    
    /**
     * Validate numeric fields
     * @param array $data
     * @param array $fields
     * @return bool|string
     */
    protected function validateNumeric($data, $fields) {
        foreach($fields as $field) {
            if(isset($data[$field]) && !is_numeric($data[$field])) {
                return "Field '{$field}' must be numeric";
            }
        }
        return true;
    }
    
    /**
     * Validate email format
     * @param string $email
     * @return bool
     */
    protected function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Sanitize input data
     * @param mixed $data
     * @return mixed
     */
    protected function sanitize($data) {
        if(is_array($data)) {
            return array_map([$this, 'sanitize'], $data);
        }
        return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Get JSON input from request body
     * @return array|null
     */
    protected function getJsonInput() {
        $input = file_get_contents('php://input');
        if(empty($input)) {
            return null;
        }
        return json_decode($input, true);
    }
    
    /**
     * Get request data (POST, GET, or JSON)
     * @return array
     */
    protected function getRequestData() {
        $data = [];
        
        // Get from POST
        if(!empty($_POST)) {
            $data = $_POST;
        }
        
        // Get from JSON body
        $jsonInput = $this->getJsonInput();
        if($jsonInput) {
            $data = array_merge($data, $jsonInput);
        }
        
        // Get from GET parameters
        if(!empty($_GET)) {
            $data = array_merge($data, $_GET);
        }
        
        return $this->sanitize($data);
    }
    
    /**
     * Get specific parameter from request
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function getParam($key, $default = null) {
        $data = $this->getRequestData();
        return isset($data[$key]) ? $data[$key] : $default;
    }
    
    /**
     * Check if request method matches
     * @param string|array $methods
     * @return bool
     */
    protected function isMethod($methods) {
        $currentMethod = $_SERVER['REQUEST_METHOD'];
        if(is_array($methods)) {
            return in_array($currentMethod, $methods);
        }
        return $currentMethod === $methods;
    }
    
    /**
     * Require specific request method
     * @param string|array $methods
     * @throws \Exception
     */
    protected function requireMethod($methods) {
        if(!$this->isMethod($methods)) {
            $allowed = is_array($methods) ? implode(', ', $methods) : $methods;
            throw new \Exception("Method not allowed. Allowed methods: {$allowed}", 405);
        }
    }
    
    /**
     * Paginate results
     * @param array $items
     * @param int $page
     * @param int $perPage
     * @return array
     */
    protected function paginate($items, $page = 1, $perPage = 10) {
        $total = count($items);
        $totalPages = ceil($total / $perPage);
        $offset = ($page - 1) * $perPage;
        $paginatedItems = array_slice($items, $offset, $perPage);
        
        return [
            'data' => $paginatedItems,
            'pagination' => [
                'current_page' => (int)$page,
                'per_page' => (int)$perPage,
                'total' => $total,
                'total_pages' => $totalPages,
                'has_next' => $page < $totalPages,
                'has_previous' => $page > 1
            ]
        ];
    }
    
    /**
     * Log error message
     * @param string $message
     * @param array $context
     */
    protected function logError($message, $context = []) {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'message' => $message,
            'context' => $context,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown'
        ];
        
        error_log(json_encode($logEntry) . PHP_EOL, 3, __DIR__ . '/../logs/error.log');
    }
    
    /**
     * Log info message
     * @param string $message
     * @param array $context
     */
    protected function logInfo($message, $context = []) {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'message' => $message,
            'context' => $context
        ];
        
        error_log(json_encode($logEntry) . PHP_EOL, 3, __DIR__ . '/../logs/app.log');
    }
    
    /**
     * Generate UUID v4
     * @return string
     */
    protected function generateUUID() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
    
    /**
     * Get client IP address
     * @return string
     */
    protected function getClientIP() {
        $ip = $_SERVER['REMOTE_ADDR'];
        
        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED'];
        } elseif (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_FORWARDED'])) {
            $ip = $_SERVER['HTTP_FORWARDED'];
        }
        
        return $ip;
    }
    
    /**
     * Set response headers for download
     * @param string $filename
     * @param string $content
     */
    protected function downloadFile($filename, $content) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($content));
        echo $content;
        exit;
    }
    
    /**
     * Export data as CSV
     * @param array $data
     * @param string $filename
     */
    protected function exportCSV($data, $filename = 'export.csv') {
        if(empty($data)) {
            $this->error('No data to export');
            return;
        }
        
        $output = fopen('php://temp', 'w');
        
        // Add headers
        fputcsv($output, array_keys($data[0]));
        
        // Add data rows
        foreach($data as $row) {
            fputcsv($output, $row);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        $this->downloadFile($filename, $csv);
    }
    
    /**
     * Validate date format
     * @param string $date
     * @param string $format
     * @return bool
     */
    protected function validateDate($date, $format = 'Y-m-d') {
        $d = \DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }
    
    /**
     * Calculate age from date of birth
     * @param string $dob
     * @return int
     */
    protected function calculateAge($dob) {
        $birthDate = new \DateTime($dob);
        $today = new \DateTime();
        $age = $today->diff($birthDate)->y;
        return $age;
    }
}
?>