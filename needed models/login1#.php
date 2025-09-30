
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
$email = '';
$password = '';
$email_err = '';
$password_err = '';
$login_err = '';

// Process form data when form is submitted
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Check if email is empty
    if(empty(trim($_POST['email']))) {
        $email_err = 'Please enter email.';
    } else {
        $email = trim($_POST['email']);
    }
    
    // Check if password is empty
    if(empty(trim($_POST['password']))) {
        $password_err = 'Please enter your password.';
    } else {
        $password = trim($_POST['password']);
    }
    
    // Validate credentials
    if(empty($email_err) && empty($password_err)) {
        // Create user object
        $user = new User();
        
        // Attempt to login
        $loggedInUser = $user->login($email, $password);
        
        if($loggedInUser) {
            // Password is correct, start a new session
            session_start();
            
            // Store data in session variables
            $_SESSION['user_id'] = $loggedInUser['id'];
            $_SESSION['username'] = $loggedInUser['username'];
            $_SESSION['user_role'] = $loggedInUser['role'];
            $_SESSION['user_name'] = $loggedInUser['full_name'];
            
            // Redirect user to welcome page
            redirect('index.php');
        } else {
            // Display an error message if password is not valid
            $login_err = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Income ERP System</title>
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
                <p>Sign in to your account</p>
            </div>
            <div class="auth-body">
                <?php if(!empty($login_err)) : ?>
                    <div class="alert alert-danger"><?php echo $login_err; ?></div>
                <?php endif; ?>
                
                <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
                    <div class="form-group">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" name="email" id="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $email; ?>">
                        <?php if(!empty($email_err)) : ?>
                            <div class="form-text text-danger"><?php echo $email_err; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" name="password" id="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>">
                        <?php if(!empty($password_err)) : ?>
                            <div class="form-text text-danger"><?php echo $password_err; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary btn-block">Sign In</button>
                    </div>
                </form>
                
                <div class="mt-3 text-center">
                    <p class="text-muted">Use john@example.com / admin123 for demo</p>
                    <p>Don't have an account? <a href="register.php">Register here</a></p>
                </div>
            </div>
        </div>
    </div>
    
    <script src="assets/js/main.js"></script>
</body>
</html>
