
<?php
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'models/User.php';
require_once 'helpers/session_helper.php';

// Check if user is already logged in
if(isLoggedIn()) {
    redirect('index.php');
}

// Initialize variables
$username = $password = $confirm_password = $full_name = $email = $phone = '';
$username_err = $password_err = $confirm_password_err = $full_name_err = $email_err = $phone_err = '';
$register_err = '';

// Process form data when form is submitted
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user = new User();
    
    // Validate username
    if(empty(trim($_POST['username']))) {
        $username_err = 'Please enter a username.';
    } else {
        $username = trim($_POST['username']);
        if($user->findUserByUsername($username)) {
            $username_err = 'This username is already taken.';
        }
    }
    
    // Validate email
    if(empty(trim($_POST['email']))) {
        $email_err = 'Please enter an email.';
    } else {
        $email = trim($_POST['email']);
        if($user->findUserByEmail($email)) {
            $email_err = 'This email is already registered.';
        }
    }
    
    // Validate password
    if(empty(trim($_POST['password']))) {
        $password_err = 'Please enter a password.';     
    } elseif(strlen(trim($_POST['password'])) < 6) {
        $password_err = 'Password must have at least 6 characters.';
    } else {
        $password = trim($_POST['password']);
    }
    
    // Validate confirm password
    if(empty(trim($_POST['confirm_password']))) {
        $confirm_password_err = 'Please confirm password.';     
    } else {
        $confirm_password = trim($_POST['confirm_password']);
        if($password != $confirm_password) {
            $confirm_password_err = 'Passwords do not match.';
        }
    }
    
    // Validate full name
    if(empty(trim($_POST['full_name']))) {
        $full_name_err = 'Please enter full name.';
    } else {
        $full_name = trim($_POST['full_name']);
    }
    
    // Validate phone (optional)
    $phone = !empty($_POST['phone']) ? trim($_POST['phone']) : '';
    
    // Check input errors before inserting in database
    if(empty($username_err) && empty($password_err) && empty($confirm_password_err) && 
       empty($full_name_err) && empty($email_err)) {
        
        $data = [
            'username' => $username,
            'password' => $password,
            'full_name' => $full_name,
            'role' => 'leasing_officer', // Default role
            'email' => $email,
            'phone' => $phone
        ];
        
        if($user->register($data)) {
            // Redirect to login page with success message
            redirect('login.php');
        } else {
            $register_err = 'Something went wrong. Please try again later.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Income ERP System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-logo">
                    <i class="fas fa-chart-line"></i> Income ERP
                </div>
                <p>Create your account</p>
            </div>
            <div class="auth-body">
                <?php if(!empty($register_err)) : ?>
                    <div class="alert alert-danger"><?php echo $register_err; ?></div>
                <?php endif; ?>
                
                <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
                    <div class="form-group">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" name="username" id="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $username; ?>">
                        <?php if(!empty($username_err)) : ?>
                            <div class="form-text text-danger"><?php echo $username_err; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="full_name" class="form-label">Full Name</label>
                        <input type="text" name="full_name" id="full_name" class="form-control <?php echo (!empty($full_name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $full_name; ?>">
                        <?php if(!empty($full_name_err)) : ?>
                            <div class="form-text text-danger"><?php echo $full_name_err; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" name="email" id="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $email; ?>">
                        <?php if(!empty($email_err)) : ?>
                            <div class="form-text text-danger"><?php echo $email_err; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone" class="form-label">Phone (Optional)</label>
                        <input type="text" name="phone" id="phone" class="form-control" value="<?php echo $phone; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" name="password" id="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>">
                        <?php if(!empty($password_err)) : ?>
                            <div class="form-text text-danger"><?php echo $password_err; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>">
                        <?php if(!empty($confirm_password_err)) : ?>
                            <div class="form-text text-danger"><?php echo $confirm_password_err; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary btn-block">Register</button>
                    </div>
                </form>
                
                <div class="mt-3 text-center">
                    <p>Already have an account? <a href="login.php">Login here</a></p>
                </div>
            </div>
        </div>
    </div>
    
    <script src="assets/js/main.js"></script>
</body>
</html>
