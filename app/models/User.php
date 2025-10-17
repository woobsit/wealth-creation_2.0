<?php
class User {
    private $db;
    
    public function __construct($databaseObj) {
        $this->db = $databaseObj;
    }
    
    // Register a new user
    public function register($data) {
        // Prepare query
        $this->db->query('INSERT INTO _users (username, password, full_name, role, email, phone) VALUES (:username, :password, :full_name, :role, :email, :phone)');
        
        // Hash password
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // Bind values
        $this->db->bind(':username', $data['username']);
        $this->db->bind(':password', $data['password']);
        $this->db->bind(':full_name', $data['full_name']);
        $this->db->bind(':role', $data['role']);
        $this->db->bind(':email', $data['email']);
        $this->db->bind(':phone', $data['phone']);
        
        // Execute
        if($this->db->execute()) {
            return true;
        } else {
            return false;
        }
    }
    
    // Login user
    // public function login($email, $password) {
    //     // Prepare query
    //     $this->db->query('SELECT * FROM _users WHERE email = :email AND status = "active"');
        
    //     // Bind value
    //     $this->db->bind(':email', $email);
        
    //     // Get single record
    //     $user = $this->db->single();
        
    //     if($user) {
    //         // Verify password
    //         $hashed_password = $user['password'];
    //         if ($password = hash('sha256', $hashed_password)) {
    //             //if(password_verify($password, $hashed_password)) {
    //             return $user;
    //         }
    //     }
        
    //     return false;
    // }

    public function login($email, $password) {
        $this->db->query('SELECT * FROM _users WHERE email = :email AND status = "active"');
        $this->db->bind(':email', $email);
        // Get single record
        $user = $this->db->single();
        
        if ($user) {
            $hashed_password = $user['password'];

            // Try new PHP native password hashing
            if (password_verify($password, $hashed_password)) {
                return $user; // Password verified with password_hash()
            }

            // Fallback for legacy SHA-256 users ---
            if (hash('sha256', $password) == $hashed_password) {
                return $user; // Password matched legacy hash
            }
        }

        return false; 
    }


    public function loginwithrole($email, $password) {
        // Step 1: Check user by email and active status
        $this->db->query('SELECT * FROM _users WHERE email = :email AND status = "active"');
        $this->db->bind(':email', $email);
        
        $user = $this->db->single();
    
        if ($user) {
            // Step 2: Verify password correctly using password_verify
            $hashed_password = $user['password'];
            if ($password = hash('sha256', $hashed_password)) {
                //if (password_verify($password, $hashed_password)) {
    
                // Step 3: Get department from staff table
                $this->db->query('SELECT department FROM staffs WHERE user_id = :user_id');
                $this->db->bind(':user_id', $user['id']);
                $staff = $this->db->single();
    
                if ($staff) {
                    $department = strtolower($staff['department']);
    
                    // Step 4: Determine role based on department
                    switch ($department) {
                        case 'accounts':
                            $user['role'] = 'accounting_officer';
                            break;
                        case 'leasing':
                            $user['role'] = 'leasing_officer';
                            break;
                        case 'audit/inspections':
                            $user['role'] = 'auditor';
                            break;    
                        case 'it/e-business':
                            $user['role'] = 'admin';
                            break;
                        case 'wealth creation':
                            $user['role'] = 'wealth_creation';
                            break;
                        default:
                            $user['role'] = $department . '_officer';
                            break;
                    }
                } else {
                    $user['role'] = 'unknown';
                }
    
                return $user;
            }
        }
    
        return false;
    }
    
    
    // Find user by username
    public function findUserByUsername($username) {
        // Prepare query
        $this->db->query('SELECT * FROM _users WHERE username = :username');
        
        // Bind value
        $this->db->bind(':username', $username);
        
        // Get single record
        $user = $this->db->single();
        
        // Check if user exists
        if($this->db->rowCount() > 0) {
            return true;
        } else {
            return false;
        }
    }
    
    // Find user by email
    public function findUserByEmail($email) {
        // Prepare query
        $this->db->query('SELECT * FROM _users WHERE email = :email');
        
        // Bind value
        $this->db->bind(':email', $email);
        
        // Get single record
        $user = $this->db->single();
        
        // Check if user exists
        if($this->db->rowCount() > 0) {
            return true;
        } else {
            return false;
        }
    }
    
    // Get user by ID
    public function getUserById($id) {
        // Prepare query
        $this->db->query('SELECT * FROM _users WHERE id = :id');
        
        // Bind value
        $this->db->bind(':id', $id);
        
        // Get single record
        return $this->db->single();
    }
    
    // Get all users
    public function getUsers() {
        // Prepare query
        $this->db->query('SELECT * FROM _users ORDER BY full_name ASC');
        
        // Get result set
        return $this->db->resultSet();
    }
    
    // Get users by role
    public function getUsersByRole($role) {
        // Prepare query
        $this->db->query('SELECT * FROM _users WHERE role = :role AND status = "active" ORDER BY full_name ASC');
        
        // Bind value
        $this->db->bind(':role', $role);
        
        // Get result set
        return $this->db->resultSet();
    }

    // Get users by department
    public function getUsersByDepartment($department) {
        // Prepare query
        $this->db->query('SELECT * FROM staffs WHERE department = :department ORDER BY full_name ASC');

        // Bind value
        $this->db->bind(':department', $department);
    
        // Get result set
        return $this->db->resultSet();
    }

    public function getDepartmentByUserIdarray($userId) {
        // Prepare query
        $this->db->query('SELECT department FROM staffs WHERE user_id = :userId LIMIT 1');
    
        // Bind value
        $this->db->bind(':userId', $userId);
    
        // Get single result
        return $this->db->single();
    }
    
    public function getDepartmentByUserIdstring($userId) {
        $this->db->query('SELECT department FROM staffs WHERE user_id = :userId LIMIT 1');
        $this->db->bind(':userId', $userId);
        $result = $this->db->single();
    
        if ($result && isset($result['department'])) {
            return $result['department'];
        } else {
            return null;
        }
    }

    public function getUserStaffDetail($userId) {
        $this->db->query('SELECT * FROM staffs WHERE user_id = :userId LIMIT 1');
        // Bind value
        $this->db->bind(':userId', $userId);
        // Get result set
        return $this->db->single();    
    }
    
    public function getUserAdminRole($userId) {
        $this->db->query('SELECT * FROM roles WHERE user_id = :userId LIMIT 1');
        $this->db->bind(':userId', $userId);
        // Get result set
        return $this->db->single();
    }
        
    // Update user
    public function updateUser($data) {
        // Prepare query
        $this->db->query('UPDATE _users SET full_name = :full_name, email = :email, phone = :phone, role = :role, status = :status WHERE id = :id');
        
        // Bind values
        $this->db->bind(':id', $data['id']);
        $this->db->bind(':full_name', $data['full_name']);
        $this->db->bind(':email', $data['email']);
        $this->db->bind(':phone', $data['phone']);
        $this->db->bind(':role', $data['role']);
        $this->db->bind(':status', $data['status']);
        
        // Execute
        if($this->db->execute()) {
            return true;
        } else {
            return false;
        }
    }
    
    // Change password
    public function changePassword($id, $new_password) {
        // Hash password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Prepare query
        $this->db->query('UPDATE _users SET password = :password WHERE id = :id');
        
        // Bind values
        $this->db->bind(':id', $id);
        $this->db->bind(':password', $hashed_password);
        
        // Execute
        if($this->db->execute()) {
            return true;
        } else {
            return false;
        }
    }

     public function logout() {
        // Unset all session variables
        $_SESSION = array();

        // Destroy the session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        // Destroy the session
        session_destroy();
    }

    // Require login and specific department access
    // public function requireAnyDepartment($departments = []) {
    //     if (!isLoggedIn()) {
    //         redirect('login.php');
    //     }

    //     $userId = getLoggedInUserId();
    //     $department = $this->getDepartmentByUserIdstring($userId);

    //     if (!in_array(strtolower($department), array_map('strtolower', $departments))) {
    //         redirect('unauthorized.php');
    //     }
    // }

}
?>
