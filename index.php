<?php
	ob_start();
	ini_set('session.cookie_lifetime', 60 * 60 * 24);
	ini_set('session.gc-maxlifetime', 60 * 60 * 24);
	session_set_cookie_params(86400);              
	session_start();
	require_once 'include/dbconfig.php';

//Generate a random string.
$token = openssl_random_pseudo_bytes(20);
 
//Convert the binary data into hexadecimal representation.
$token = bin2hex($token);
 

 // it will never let you open index(login) page if session is set
 if ( isset($_SESSION['admin'])!="" ) {
  header("Location: modules/staff/index.php");
  exit;
 }

 if ( isset($_SESSION['staff'])!="" ) {
  header("Location: modules/staff/index.php");
  exit;	
 }
 
 $error = false;
 
 if( isset($_POST['btn_login']) ) { 
  
  // prevent sql injections/ clear user invalid inputs
  $email = trim($_POST['email']);
  $email = strip_tags($email);
  $email = htmlspecialchars($email);
  
  $pass = trim($_POST['pass']);
  $pass = strip_tags($pass);
  $pass = htmlspecialchars($pass);
  // prevent sql injections / clear user invalid inputs
  
  if(empty($email)){
   $error = true;
   $emailError = "Please enter your email address.";
  } else if ( !filter_var($email,FILTER_VALIDATE_EMAIL) ) {
   $error = true;
   $emailError = "Please enter valid email address.";
  }
  
  if(empty($pass)){
   $error = true;
   $passError = "Please enter your password.";
  }
  
  
//Check the IP Address of the user
	function getUserIP() {
	$ipaddress = '';
	if (isset($_SERVER['HTTP_CLIENT_IP']))
		$ipaddress = $_SERVER['HTTP_CLIENT_IP'];
	else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
		$ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
	else if(isset($_SERVER['HTTP_X_FORWARDED']))
		$ipaddress = $_SERVER['HTTP_X_FORWARDED'];
	else if(isset($_SERVER['HTTP_X_CLUSTER_CLIENT_IP']))
		$ipaddress = $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
	else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
		$ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
	else if(isset($_SERVER['HTTP_FORWARDED']))
		$ipaddress = $_SERVER['HTTP_FORWARDED'];
	else if(isset($_SERVER['REMOTE_ADDR']))
		$ipaddress = $_SERVER['REMOTE_ADDR'];
	else
		$ipaddress = 'UNKNOWN';
	return $ipaddress;
	}
	$ip_address = getUserIP();

//Capture the browser name
function get_browser_name($user_agent)
{
    if (strpos($user_agent, 'Opera') || strpos($user_agent, 'OPR/')) return 'Opera';
    elseif (strpos($user_agent, 'Edge')) return 'Microsoft Edge';
    elseif (strpos($user_agent, 'Chrome')) return 'Google Chrome';
    elseif (strpos($user_agent, 'Safari')) return 'Safari';
    elseif (strpos($user_agent, 'Firefox')) return 'Mozilla Firefox';
    elseif (strpos($user_agent, 'MSIE') || strpos($user_agent, 'Trident/7')) return 'Internet Explorer';
    return 'Other';
}
$browser = get_browser_name($_SERVER['HTTP_USER_AGENT']);

//Capture the User Agent
$user_agent = $_SERVER['HTTP_USER_AGENT'];
$user_agent = htmlspecialchars(strip_tags($user_agent));

  // if there's no error, continue to login
  if (!$error) {
   
   $password = hash('sha256', $pass); // password hashing using SHA256
  
   $sqlquery = "SELECT id, user_level, full_name, password FROM users WHERE email='$email' AND status='active'";
   $result = mysqli_query($dbcon,$sqlquery);
   $user = mysqli_fetch_array($result, MYSQLI_ASSOC);
   $count = mysqli_num_rows($result); // if uname/pass correct it returns must be 1 row
   
   
	if( $count == 1 && $user['password']==$password && $user['user_level']== 1 ) {
		//$token = getToken(10);
		$_SESSION['token'] = $token;
		$_SESSION['admin'] = $user['id'];
		
		$staff_id = $user['id'];
		$staff_name = $user['full_name'];
		$action = "Login";
		date_default_timezone_set('Africa/Lagos'); // your reference timezone here
		$now = date('Y-m-d H:i:s');
		
		$sessionID = session_id();
		
		// Update user token
		$query = "SELECT COUNT(*) AS tokencount FROM users_logs WHERE user_id='$staff_id'";
		$result = mysqli_query($dbcon, $query);
		$row_token = mysqli_fetch_array($result, MYSQLI_ASSOC);
		
		if($row_token['tokencount'] > 0){
			$log_query = "UPDATE users_logs SET token='$token' WHERE user_id='$staff_id'";
			$result = mysqli_query($dbcon, $log_query);
		} else {
			$log_query = "INSERT INTO users_logs (id, user_id, staff_name, email, ip_address, browser, user_agent, token, timemodified) VALUES ('','$staff_id','$staff_name','$email','$ip_address','$browser','$user_agent','$token','$now')";
			$log_result = mysqli_query($dbcon,$log_query);
		}
		
		if ($log_query)
		{
			?>
			<script type="text/javascript">
			alert('<?php echo 'Welcome back, '.$staff_name.'!'; ?>');
			window.location.href='modules/staff/index.php';
			</script>
			<?php
		}
	}
	
	
	elseif($count==1 && $user['password']==$password && $user['user_level']==0) {
		//$token = getToken(10);
		$_SESSION['token'] = $token;
		$_SESSION['staff'] = $user['id'];
		
		$staff_id = $user['id'];
		$staff_name = $user['full_name'];
		$action = "Login";
		date_default_timezone_set('Africa/Lagos'); // your reference timezone here
		$now = date('Y-m-d H:i:s');
		
		$sessionID = session_id();


		// Update user token
		$query = "SELECT COUNT(*) AS tokencount FROM users_logs WHERE user_id='$staff_id'";
		$result = mysqli_query($dbcon, $query);
		$row_token = mysqli_fetch_array($result, MYSQLI_ASSOC);
		
		if($row_token['tokencount'] > 0){
			$log_query = "UPDATE users_logs SET token='$token' WHERE user_id='$staff_id'";
			$result = mysqli_query($dbcon, $log_query);
		} else {
			$log_query = "INSERT INTO users_logs (id, user_id, staff_name, email, ip_address, browser, user_agent, token, timemodified) VALUES ('','$staff_id','$staff_name','$email','$ip_address','$browser','$user_agent','$token','$now')";
			$log_result = mysqli_query($dbcon,$log_query);
		}
		
		if ($log_query)
		{
			?>
			<script type="text/javascript">
			alert('<?php echo 'Welcome back, '.$staff_name.'!'; ?>');
			window.location.href='modules/staff/index.php';
			</script>
			<?php
		}
	}
	else{
		$errMSG = "Incorrect Credentials, Try again...";
	}   
  }
  
 }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>Login | Wealth Creation ERP</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="css/tailwindcss.min.css">
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            'inter': ['Inter', 'sans-serif'],
          },
          colors: {
            primary: {
              50: '#f0f9ff',
              100: '#e0f2fe',
              200: '#bae6fd',
              300: '#7dd3fc',
              400: '#38bdf8',
              500: '#0ea5e9',
              600: '#0284c7',
              700: '#0369a1',
              800: '#075985',
              900: '#0c4a6e',
            },
            secondary: {
              50: '#fdf4ff',
              100: '#fae8ff',
              200: '#f5d0fe',
              300: '#f0abfc',
              400: '#e879f9',
              500: '#d946ef',
              600: '#c026d3',
              700: '#a21caf',
              800: '#86198f',
              900: '#701a75',
            }
          },
          animation: {
            'fade-in': 'fadeIn 0.6s ease-in-out',
            'slide-in': 'slideIn 0.8s ease-out',
            'float': 'float 6s ease-in-out infinite',
            'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
          },
          keyframes: {
            fadeIn: {
              '0%': { opacity: '0', transform: 'translateY(20px)' },
              '100%': { opacity: '1', transform: 'translateY(0)' }
            },
            slideIn: {
              '0%': { opacity: '0', transform: 'translateX(-30px)' },
              '100%': { opacity: '1', transform: 'translateX(0)' }
            },
            float: {
              '0%, 100%': { transform: 'translateY(0px)' },
              '50%': { transform: 'translateY(-20px)' }
            }
          },
          backdropBlur: {
            xs: '2px',
          }
        }
      }
    }
  </script>
  <style>
    .login-bg {
      background: linear-gradient(135deg, #0ea5e9 0%, #d946ef 50%, #0369a1 100%);
      background-size: 400% 400%;
      animation: gradientShift 15s ease infinite;
    }
    
    @keyframes gradientShift {
      0% { background-position: 0% 50%; }
      50% { background-position: 100% 50%; }
      100% { background-position: 0% 50%; }
    }
    
    .glass-effect {
      backdrop-filter: blur(20px) saturate(180%);
      -webkit-backdrop-filter: blur(20px) saturate(180%);
      background-color: rgba(255, 255, 255, 0.85);
      border: 1px solid rgba(255, 255, 255, 0.2);
    }
    
    .input-focus:focus {
      box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.1);
      border-color: #0ea5e9;
    }
    
    .btn-gradient {
      background: linear-gradient(135deg, #0ea5e9, #d946ef);
      background-size: 200% 200%;
      transition: all 0.3s ease;
    }
    
    .btn-gradient:hover {
      background-position: right center;
      transform: translateY(-2px);
      box-shadow: 0 10px 25px rgba(14, 165, 233, 0.3);
    }
    
    .floating-shapes {
      position: absolute;
      width: 100%;
      height: 100%;
      overflow: hidden;
      z-index: 1;
    }
    
    .floating-shapes::before,
    .floating-shapes::after {
      content: '';
      position: absolute;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.1);
      animation: float 6s ease-in-out infinite;
    }
    
    .floating-shapes::before {
      width: 200px;
      height: 200px;
      top: 20%;
      left: 10%;
      animation-delay: -2s;
    }
    
    .floating-shapes::after {
      width: 150px;
      height: 150px;
      bottom: 20%;
      right: 10%;
      animation-delay: -4s;
    }
    
    .brand-logo {
      background: linear-gradient(135deg, #0ea5e9, #d946ef);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }
    
    .error-shake {
      animation: shake 0.5s ease-in-out;
    }
    
    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      25% { transform: translateX(-5px); }
      75% { transform: translateX(5px); }
    }
  </style>
</head>
<body class="font-inter bg-gray-50 min-h-screen overflow-hidden">
  <!-- Background Pattern -->
  <div class="absolute inset-0 bg-gradient-to-br from-blue-50 via-white to-purple-50"></div>
  <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width="60" height="60" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg"%3E%3Cg fill="none" fill-rule="evenodd"%3E%3Cg fill="%230ea5e9" fill-opacity="0.03"%3E%3Ccircle cx="30" cy="30" r="2"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')] opacity-40"></div>

  <div class="relative max-h-screen flex">
    <!-- Left Side - Login Form -->
    <div class="flex-1 flex flex-col justify-center py-12 px-4 sm:px-6 lg:flex-none lg:px-20 xl:px-24 relative z-10">
      <div class="mx-auto w-full max-w-md lg:w-96 animate-slide-in">
        <!-- Logo/Brand Section -->
        <div class="mb-10">
          <div class="flex items-center justify-center lg:justify-start space-x-4">
            <div class="relative">
              <div class="w-16 h-16 bg-gradient-to-r from-primary-500 to-secondary-500 rounded-2xl flex items-center justify-center shadow-lg transform rotate-3 hover:rotate-0 transition-transform duration-300">
                <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H3m2 0h4M9 7h6m-6 4h6m-6 4h6"></path>
                </svg>
              </div>
              <div class="absolute -top-1 -right-1 w-6 h-6 bg-green-400 rounded-full flex items-center justify-center">
                <div class="w-2 h-2 bg-white rounded-full animate-pulse"></div>
              </div>
            </div>
            <div>
              <h2 class="text-3xl font-bold brand-logo">Wealth Creation</h2>
              <p class="text-sm text-gray-600 font-medium">Enterprise Resource Planning</p>
            </div>
          </div>
        </div>

        <!-- Welcome Section -->
        <div class="mb-8 text-center lg:text-left">
          <h1 class="text-4xl font-bold text-gray-900 mb-3">
            Welcome <span class="text-primary-600">Back</span>
          </h1>
          <p class="text-gray-600 text-lg leading-relaxed">
            Sign in to access your dashboard.
          </p>
        </div>

        <!-- Error Messages -->
        <?php if (isset($ipError)): ?>
        <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 rounded-xl animate-fade-in">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <svg class="w-5 h-5 text-emerald-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
              </svg>
            </div>
            <div class="ml-3">
              <p class="text-emerald-800 font-medium"><?php echo @$ipError; ?></p>
            </div>
          </div>
        </div>
        <?php endif; ?>

        <?php if (isset($logError)): ?>
        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl animate-fade-in error-shake">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <svg class="w-5 h-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
              </svg>
            </div>
            <div class="ml-3">
              <p class="text-red-800 font-medium"><?php echo @$logError; ?></p>
            </div>
          </div>
        </div>
        <?php endif; ?>

        <?php if (isset($errMSG)): ?>
        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl animate-fade-in error-shake">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <svg class="w-5 h-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
              </svg>
            </div>
            <div class="ml-3">
              <p class="text-red-800 font-medium"><?php echo @$errMSG; ?></p>
            </div>
          </div>
        </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="space-y-6">
          <div class="space-y-1">
            <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">
              Email Address
            </label>
            <div class="relative group">
              <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-gray-400 group-focus-within:text-primary-500 transition-colors duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path>
                </svg>
              </div>
              <input 
                type="email" 
                name="email" 
                id="email" 
                class="input-focus block w-full pl-12 pr-4 py-4 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all duration-200 bg-white text-gray-900 placeholder-gray-500 text-base" 
                placeholder="Enter your official email address"
                value="<?php if (isset($_POST['btn_login'])) echo $email; ?>"
                required
              >
            </div>
            <?php if (isset($emailError)): ?>
            <p class="mt-2 text-sm text-red-600 flex items-center">
              <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 000 2v4a1 1 0 102 0V7a1 1 0 00-1-1z" clip-rule="evenodd"></path>
              </svg>
              <?php echo @$emailError; ?>
            </p>
            <?php endif; ?>
          </div>

          <div class="space-y-1">
            <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">
              Password
            </label>
            <div class="relative group">
              <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-gray-400 group-focus-within:text-primary-500 transition-colors duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
              </div>
              <input 
                type="password" 
                name="pass" 
                id="password" 
                class="input-focus block w-full pl-12 pr-4 py-4 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all duration-200 bg-white text-gray-900 placeholder-gray-500 text-base" 
                placeholder="Enter your password"
                required
              >
            </div>
            <?php if (isset($passError)): ?>
            <p class="mt-2 text-sm text-red-600 flex items-center">
              <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 000 2v4a1 1 0 102 0V7a1 1 0 00-1-1z" clip-rule="evenodd"></path>
              </svg>
              <?php echo @$passError; ?>
            </p>
            <?php endif; ?>
          </div>

          <div class="pt-4">
            <button 
              type="submit" 
              name="btn_login"
              class="group relative w-full flex justify-center py-4 px-6 border border-transparent text-base font-semibold rounded-xl text-white btn-gradient focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-all duration-300 transform hover:scale-[1.02] active:scale-[0.98] shadow-lg"
            >
              <span class="absolute left-0 inset-y-0 flex items-center pl-4">
                <svg class="h-5 w-5 text-white/80 group-hover:text-white transition-colors duration-200" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path>
                </svg>
              </span>
              Sign In to Dashboard
            </button>
          </div>
        </form>

        <!-- Security Notice -->
        <div class="mt-8 p-4 bg-blue-50 border border-blue-200 rounded-xl">
          <div class="flex items-start">
            <div class="flex-shrink-0">
              <svg class="w-5 h-5 text-blue-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path>
              </svg>
            </div>
            <div class="ml-3">
              <p class="text-sm text-blue-800">
                <span class="font-semibold">Secure Login:</span> Your session is protected with advanced encryption and monitoring.
              </p>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="mt-8 text-center">
          <p class="text-xs text-gray-500">
            © <?php echo date('Y'); ?> Wealth Creation ERP Version 2.0. All rights reserved. | Powered by Woobs Resources IT
          </p>
        </div>
      </div>
    </div>

    <!-- Right Side - Visual -->
    <div class="hidden lg:block relative w-0 flex-1">
      <div class="absolute inset-0 login-bg">
        <div class="floating-shapes"></div>
        <div class="absolute inset-0 bg-black/20"></div>
        <img 
          class="absolute inset-0 h-full w-full object-cover mix-blend-overlay opacity-30" 
          src="assets/images/login.jpg" 
          alt="Wealth Creation ERP Dashboard"
        >
        <div class="absolute inset-0 flex items-center justify-center z-10">
          <div class="text-center text-white px-8 max-w-lg">
            <div class="glass-effect rounded-3xl p-10 animate-fade-in">
              <div class="mb-6">
                <div class="w-20 h-20 bg-white/20 rounded-2xl flex items-center justify-center mx-auto mb-4 animate-float">
                  <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                  </svg>
                </div>
              </div>
              <h3 class="text-3xl font-bold mb-6 text-gray-800">
                Enterprise Resource Planning
              </h3>
              <p class="text-gray-600 leading-relaxed mb-8 text-lg">
                Streamline business operations, manage resources, track performance, and drive growth with powerful analytics and real-time insights.               </p>
              <div class="grid grid-cols-3 gap-4 mb-6">
                <div class="text-center">
                  <div class="w-12 h-12 bg-primary-500/20 rounded-xl flex items-center justify-center mx-auto mb-2">
                    <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                  </div>
                  <p class="text-sm font-medium text-gray-700">Fast</p>
                </div>
                <div class="text-center">
                  <div class="w-12 h-12 bg-secondary-500/20 rounded-xl flex items-center justify-center mx-auto mb-2">
                    <svg class="w-6 h-6 text-secondary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                  </div>
                  <p class="text-sm font-medium text-gray-700">Secure</p>
                </div>
                <div class="text-center">
                  <div class="w-12 h-12 bg-emerald-500/20 rounded-xl flex items-center justify-center mx-auto mb-2">
                    <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path>
                    </svg>
                  </div>
                  <p class="text-sm font-medium text-gray-700">Smart</p>
                </div>
              </div>
              <div class="flex justify-center space-x-2">
                <div class="w-3 h-3 bg-primary-400 rounded-full animate-pulse"></div>
                <div class="w-3 h-3 bg-secondary-400 rounded-full animate-pulse" style="animation-delay: 0.2s;"></div>
                <div class="w-3 h-3 bg-emerald-400 rounded-full animate-pulse" style="animation-delay: 0.4s;"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Loading Overlay -->
  <div id="loading-overlay" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-2xl p-8 flex items-center space-x-4 shadow-2xl">
      <div class="relative">
        <div class="animate-spin rounded-full h-10 w-10 border-4 border-primary-200"></div>
        <div class="animate-spin rounded-full h-10 w-10 border-4 border-primary-600 border-t-transparent absolute top-0"></div>
      </div>
      <div>
        <p class="text-gray-900 font-semibold text-lg">Signing you in...</p>
        <p class="text-gray-600 text-sm">Please wait while we verify your credentials</p>
      </div>
    </div>
  </div>

  <script>
    // Show loading overlay on form submission
    document.querySelector('form').addEventListener('submit', function() {
      document.getElementById('loading-overlay').classList.remove('hidden');
    });

    // Enhanced form interactions
    document.addEventListener('DOMContentLoaded', function() {
      const inputs = document.querySelectorAll('input');
      
      inputs.forEach(input => {
        // Focus effects
        input.addEventListener('focus', function() {
          this.parentElement.classList.add('transform', 'scale-[1.02]');
          this.parentElement.style.transition = 'transform 0.2s ease';
        });
        
        input.addEventListener('blur', function() {
          this.parentElement.classList.remove('transform', 'scale-[1.02]');
        });

        // Real-time validation feedback
        input.addEventListener('input', function() {
          if (this.type === 'email' && this.value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (emailRegex.test(this.value)) {
              this.classList.remove('border-red-300');
              this.classList.add('border-green-300');
            } else {
              this.classList.remove('border-green-300');
              this.classList.add('border-red-300');
            }
          }
        });
      });

      // Auto-hide alerts after 6 seconds
      const alerts = document.querySelectorAll('.animate-fade-in');
      alerts.forEach(alert => {
        setTimeout(() => {
          alert.style.opacity = '0';
          alert.style.transform = 'translateY(-10px)';
          setTimeout(() => alert.remove(), 300);
        }, 6000);
      });

      // Add subtle parallax effect to floating shapes
      document.addEventListener('mousemove', function(e) {
        const shapes = document.querySelector('.floating-shapes');
        if (shapes) {
          const x = e.clientX / window.innerWidth;
          const y = e.clientY / window.innerHeight;
          shapes.style.transform = `translate(${x * 10}px, ${y * 10}px)`;
        }
      });
    });

    // Keyboard accessibility
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Enter' && e.target.tagName === 'INPUT') {
        const form = e.target.closest('form');
        if (form) {
          const submitBtn = form.querySelector('button[type="submit"]');
          if (submitBtn) {
            submitBtn.click();
          }
        }
      }
    });
  </script>
</body>
</html>