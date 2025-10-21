<?php
class Auth {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function login($email, $password) {
        try {
            $this->db->query('SELECT * FROM users WHERE email = :email AND status = :status');
            $this->db->bind(':email', $email);
            $this->db->bind(':status', 'active');
            
            $user = $this->db->single();
            
            if ($user && $user['password'] == hash('sha256', $password)) {  //password_verify($password, $user['password'])
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['level'] = $user['level'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['department'] = $user['department'];
                $_SESSION['has_roles'] = $user['has_roles'];
                
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return false;
        }
    }
    
    public function logout() {
        session_destroy();
        return true;
    }
    
    public function register($data) {
        try {
            // Check if email already exists
            $this->db->query('SELECT id FROM users WHERE email = :email');
            $this->db->bind(':email', $data['email']);
            
            if ($this->db->single()) {
                return ['success' => false, 'message' => 'Email already exists'];
            }
            
            // Hash password
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            
            // Insert user
            $this->db->query('INSERT INTO users (full_name, email, password, user_level, department, has_roles) 
                             VALUES (:full_name, :email, :password, :user_level, :department, :has_roles)');
            
            $this->db->bind(':full_name', $data['full_name']);
            $this->db->bind(':email', $data['email']);
            $this->db->bind(':password', $hashedPassword);
            $this->db->bind(':user_level', isset($data['user_level']) ? $data['user_level'] : 1);
            $this->db->bind(':department', isset($data['department']) ? $data['department'] : null);
            $this->db->bind(':has_roles', isset($data['has_roles']) ? $data['has_roles'] : 'user');
            
            if ($this->db->execute()) {
                return ['success' => true, 'message' => 'User registered successfully'];
            }
            
            return ['success' => false, 'message' => 'Registration failed'];
            
        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Registration failed'];
        }
    }
    
    public function getCurrentUser() {
        if (!isLoggedIn()) {
            return null;
        }
        
        try {
            $this->db->query('SELECT * FROM users WHERE id = :id');
            $this->db->bind(':id', $_SESSION['user_id']);
            return $this->db->single();
        } catch (Exception $e) {
            error_log("Get current user error: " . $e->getMessage());
            return null;
        }
    }
    
    public function hasRole($role) {
        if (!isLoggedIn()) {
            return false;
        }
        
        $roles = explode(',', $_SESSION['has_roles']);
        return in_array($role, $roles);
    }
    
    public function canApprove($level) {
        if (!isLoggedIn()) {
            return false;
        }
        
        return $_SESSION['user_level'] >= $level && $this->hasRole('approver');
    }
}
?>