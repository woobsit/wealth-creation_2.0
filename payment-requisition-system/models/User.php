<?php
class User {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function getAll($filters = []) {
        try {
            $sql = 'SELECT * FROM users WHERE 1=1';
            $params = [];
            
            if (!empty($filters['status'])) {
                $sql .= ' AND status = :status';
                $params[':status'] = $filters['status'];
            }
            
            if (!empty($filters['department'])) {
                $sql .= ' AND department = :department';
                $params[':department'] = $filters['department'];
            }
            
            if (!empty($filters['search'])) {
                $sql .= ' AND (full_name LIKE :search OR email LIKE :search)';
                $params[':search'] = '%' . $filters['search'] . '%';
            }
            
            $sql .= ' ORDER BY full_name ASC';
            
            $this->db->query($sql);
            
            foreach ($params as $key => $value) {
                $this->db->bind($key, $value);
            }
            
            return $this->db->resultSet();
            
        } catch (Exception $e) {
            error_log("Get users error: " . $e->getMessage());
            return [];
        }
    }
    
    public function getById($id) {
        try {
            $this->db->query('SELECT * FROM users WHERE id = :id');
            $this->db->bind(':id', $id);
            return $this->db->single();
        } catch (Exception $e) {
            error_log("Get user error: " . $e->getMessage());
            return null;
        }
    }
    
    public function create($data) {
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
            $this->db->query('INSERT INTO users (full_name, email, password, user_level, department, has_roles, status) 
                             VALUES (:full_name, :email, :password, :user_level, :department, :has_roles, :status)');
            
            $this->db->bind(':full_name', $data['full_name']);
            $this->db->bind(':email', $data['email']);
            $this->db->bind(':password', $hashedPassword);
            $this->db->bind(':user_level', isset($data['user_level']) ? $data['user_level'] : 1);
            $this->db->bind(':department', isset($data['department']) ? $data['department'] : null);
            $this->db->bind(':has_roles', isset($data['has_roles']) ? $data['has_roles'] : 'user');
            $this->db->bind(':status', isset($data['status']) ? $data['status'] : 'active');
   
            if ($this->db->execute()) {
                return ['success' => true, 'message' => 'User created successfully'];
            }
            
            return ['success' => false, 'message' => 'Failed to create user'];
            
        } catch (Exception $e) {
            error_log("Create user error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to create user'];
        }
    }
    
    public function update($id, $data) {
        try {
            $sql = 'UPDATE users SET full_name = :full_name, email = :email, user_level = :user_level, 
                    department = :department, has_roles = :has_roles, status = :status';
            
            $params = [
                ':full_name' => $data['full_name'],
                ':email' => $data['email'],
                ':user_level' => $data['user_level'],
                ':department' => $data['department'],
                ':has_roles' => $data['has_roles'],
                ':status' => $data['status'],
                ':id' => $id
            ];
            
            // Update password if provided
            if (!empty($data['password'])) {
                $sql .= ', password = :password';
                $params[':password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }
            
            $sql .= ' WHERE id = :id';
            
            $this->db->query($sql);
            
            foreach ($params as $key => $value) {
                $this->db->bind($key, $value);
            }
            
            if ($this->db->execute()) {
                return ['success' => true, 'message' => 'User updated successfully'];
            }
            
            return ['success' => false, 'message' => 'Failed to update user'];
            
        } catch (Exception $e) {
            error_log("Update user error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to update user'];
        }
    }
    
    public function delete($id) {
        try {
            $this->db->query('DELETE FROM users WHERE id = :id');
            $this->db->bind(':id', $id);
            
            if ($this->db->execute()) {
                return ['success' => true, 'message' => 'User deleted successfully'];
            }
            
            return ['success' => false, 'message' => 'Failed to delete user'];
            
        } catch (Exception $e) {
            error_log("Delete user error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to delete user'];
        }
    }
    
    public function getApprovers($level) {
        try {
            $this->db->query('SELECT * FROM users WHERE user_level >= :level AND has_roles LIKE :role AND status = :status');
            $this->db->bind(':level', $level);
            $this->db->bind(':role', '%approver%');
            $this->db->bind(':status', 'active');
            return $this->db->resultSet();
        } catch (Exception $e) {
            error_log("Get approvers error: " . $e->getMessage());
            return [];
        }
    }
}
?>