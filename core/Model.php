<?php
namespace Core;

use PDO;
use PDOException;

abstract class Model {
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    
    public function __construct() {
        $database = new \Config\Database();
        $this->db = $database->getConnection();
    }
    
    // Find record by ID
    public function find($id) {
        $query = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Find all records
    public function findAll($orderBy = 'created_at DESC') {
        $query = "SELECT * FROM {$this->table} ORDER BY {$orderBy}";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Find with conditions
    public function where($conditions, $params = []) {
        $query = "SELECT * FROM {$this->table} WHERE {$conditions}";
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Create record
    public function create($data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $query = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";

        // echo $query;
        // exit;

        $stmt = $this->db->prepare($query);
        
        if($stmt->execute($data)) {
            return $this->db->lastInsertId();
        }
        return false;
    }
    
    // Update record
    public function update($id, $data) {
        $setClause = '';
        foreach($data as $key => $value) {
            $setClause .= "{$key} = :{$key}, ";
        }
        $setClause = rtrim($setClause, ', ');
        
        $query = "UPDATE {$this->table} SET {$setClause} WHERE {$this->primaryKey} = :id";
        $data['id'] = $id;
        $stmt = $this->db->prepare($query);
        
        return $stmt->execute($data);
    }
    
    // Delete record
    public function delete($id) {
        $query = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([':id' => $id]);
    }
    
    // Count records
    public function count($conditions = '', $params = []) {
        $query = "SELECT COUNT(*) as count FROM {$this->table}";
        if($conditions) {
            $query .= " WHERE {$conditions}";
        }
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }
    
    // Begin transaction
    public function beginTransaction() {
        return $this->db->beginTransaction();
    }
    
    // Commit transaction
    public function commit() {
        return $this->db->commit();
    }
    
    // Rollback transaction
    public function rollback() {
        return $this->db->rollBack();
    }
    
    // Calculate monthly payment
    protected function calculateMonthlyPayment($principal, $annualRate, $months) {
        $monthlyRate = ($annualRate / 100) / 12;
        if ($monthlyRate == 0) return $principal / $months;
        $payment = $principal * ($monthlyRate * pow(1 + $monthlyRate, $months)) / 
                   (pow(1 + $monthlyRate, $months) - 1);
        return round($payment, 2);
    }
}
?>