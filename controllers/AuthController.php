<?php
namespace Controllers;

use Core\Controller;
use Models\User;
use Config\Auth;

class AuthController extends Controller {
    private $userModel;
    
    public function __construct() {
        $this->userModel = new User();
    }
    
    public function login() {
        $data = $this->getRequestData();
        
        $validation = $this->validateRequired($data, ['username', 'password']);
        if ($validation !== true) {
            return $this->error($validation);
        }
        
        $user = $this->userModel->authenticate($data['username'], $data['password']);
        
        if ($user) {
            Auth::login($user);
            $this->userModel->logActivity($user['id'], 'api_login', 'API login successful');
            return $this->success('Login successful', ['user' => $user]);
        }
        
        return $this->error('Invalid credentials');
    }
    
    public function register() {
        $data = $this->getRequestData();
        
        $validation = $this->validateRequired($data, ['username', 'email', 'password', 'full_name']);
        if ($validation !== true) {
            return $this->error($validation);
        }
        
        try {
            $userId = $this->userModel->register($data);
            return $this->success('Registration successful', ['user_id' => $userId]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
    
    public function changePassword() {
        if (!Auth::isLoggedIn()) {
            return $this->error('Unauthorized', 401);
        }
        
        $data = $this->getRequestData();
        $validation = $this->validateRequired($data, ['current_password', 'new_password']);
        
        if ($validation !== true) {
            return $this->error($validation);
        }
        
        try {
            $result = $this->userModel->changePassword(
                Auth::getUserId(),
                $data['current_password'],
                $data['new_password']
            );
            
            if ($result) {
                $this->userModel->logActivity(Auth::getUserId(), 'password_change', 'Password changed');
                return $this->success('Password changed successfully');
            }
            
            return $this->error('Failed to change password');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
    
    public function getUser() {
        if (!Auth::isLoggedIn()) {
            return $this->error('Unauthorized', 401);
        }
        
        $user = $this->userModel->find(Auth::getUserId());
        unset($user['password_hash']);
        
        return $this->success('User data retrieved', $user);
    }
}
?>