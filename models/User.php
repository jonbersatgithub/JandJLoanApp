<?php
namespace Models;

use Core\Model;
use Config\Auth;

class User extends Model {
    protected $table = 'users';
    protected $primaryKey = 'id';
    
    // Authenticate user
    public function authenticate($username, $password) {


        $query = "SELECT * FROM {$this->table} WHERE (username = :username OR email = :username) AND is_active = 1";
        // echo "Executing query: " . $query . " with username: " . $username; // Debug statement

        $stmt = $this->db->prepare($query);
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($user && Auth::verifyPassword($password, $user['password_hash'])) {
            // Update last login
            $this->update($user['id'], ['last_login' => date('Y-m-d H:i:s')]);
            return $user;
        }
        
        return false;
    }
    
    // Register new user
    public function register($data) {
        // Validate unique username and email
        if ($this->findByUsername($data['username'])) {
            throw new \Exception('Username already exists');
        }
        
        if ($this->findByEmail($data['email'])) {
            throw new \Exception('Email already exists');
        }
        
        $userData = [
            'username' => $data['username'],
            'email' => $data['email'],
            'password_hash' => Auth::hashPassword($data['password']),
            'full_name' => $data['full_name'],
            'role' => $data['role'] ?? 'staff',
            'is_active' => true
        ];
        
        return $this->create($userData);
    }
    
    // Find user by username
    public function findByUsername($username) {
        $query = "SELECT * FROM {$this->table} WHERE username = :username";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':username' => $username]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }
    
    // Find user by email
    public function findByEmail($email) {
        $query = "SELECT * FROM {$this->table} WHERE email = :email";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':email' => $email]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }
    
    // Change password
    public function changePassword($userId, $oldPassword, $newPassword) {
        $user = $this->find($userId);
        
        if (!Auth::verifyPassword($oldPassword, $user['password_hash'])) {
            throw new \Exception('Current password is incorrect');
        }
        
        return $this->update($userId, [
            'password_hash' => Auth::hashPassword($newPassword)
        ]);
    }
    
    // Get user's loans statistics
    public function getUserStatistics($userId) {
        $query = "SELECT 
                    COUNT(*) as total_loans,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_loans,
                    SUM(loan_amount) as total_amount,
                    SUM(CASE WHEN status = 'active' THEN monthly_payment ELSE 0 END) as monthly_collection
                  FROM loans 
                  WHERE user_id = :user_id";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }
    
    // Log activity
    public function logActivity($userId, $action, $description = null) {
        $query = "INSERT INTO activity_logs (user_id, action, description, ip_address, user_agent) 
                  VALUES (:user_id, :action, :description, :ip_address, :user_agent)";
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            ':user_id' => $userId,
            ':action' => $action,
            ':description' => $description,
            ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
        return $this->db->lastInsertId();
    }
    
    // Get user activity logs
    public function getActivityLogs($userId, $limit = 50) {
        $query = "SELECT * FROM activity_logs WHERE user_id = :user_id ORDER BY created_at DESC LIMIT :limit";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    // Get all users (for admin)
    public function getAllUsers() {
        $query = "SELECT id, username, email, full_name, role, is_active, last_login, created_at 
                  FROM {$this->table} ORDER BY created_at DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    // Update user role (admin only)
    public function updateRole($userId, $role) {
        $allowedRoles = ['admin', 'manager', 'staff'];
        if (!in_array($role, $allowedRoles)) {
            throw new \Exception('Invalid role');
        }
        return $this->update($userId, ['role' => $role]);
    }
    
    // Toggle user status
    public function toggleStatus($userId) {
        $user = $this->find($userId);
        return $this->update($userId, ['is_active' => !$user['is_active']]);
    }
}
?>