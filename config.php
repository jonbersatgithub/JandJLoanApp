<?php
    session_start();

    class Database {
        private $host = "localhost";
        private $db_name = "loan_management";
        private $username = "root";
        private $password = "";
        public $conn;

        public function getConnection() {
            $this->conn = null;
            try {
                $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, 
                                    $this->username, $this->password);
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch(PDOException $exception) {
                echo "Connection error: " . $exception->getMessage();
            }
            return $this->conn;
        }
    }
    // Calculate monthly payment
    function calculateMonthlyPayment($principal, $annualRate, $months) {
        $monthlyRate = ($annualRate / 100) / 12;
        if ($monthlyRate == 0) return $principal / $months;
        $payment = $principal * ($monthlyRate * pow(1 + $monthlyRate, $months)) / 
                (pow(1 + $monthlyRate, $months) - 1);
        return round($payment, 2);
    }
?>