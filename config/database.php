<?php
namespace Config;

use PDO;
use PDOException;

class Database {
    private $host = "localhost";
    private $db_name = "JJloan_APP";
    private $username = "root";
    private $password = "";
    private $conn;
    
    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8");
        } catch(PDOException $exception) {
            error_log("Connection error: " . $exception->getMessage());
            throw new \Exception("Database connection failed");
        }
        
        return $this->conn;
    }
}
?>