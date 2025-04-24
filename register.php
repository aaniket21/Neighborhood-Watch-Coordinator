<?php
require_once "config.php";

$username = $password = $confirm_password = $email = "";
$username_err = $password_err = $confirm_password_err = $email_err = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validate username
    if(empty(trim($_POST["username"]))){
        $username_err = "Please enter a username.";
    } elseif(!preg_match('/^[a-zA-Z0-9_]+$/', trim($_POST["username"]))){
        $username_err = "Username can only contain letters, numbers, and underscores.";
    } else{
        $sql = "SELECT id FROM users WHERE username = ?";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "s", $param_username);
            $param_username = trim($_POST["username"]);
            
            if(mysqli_stmt_execute($stmt)){
                mysqli_stmt_store_result($stmt);
                
                if(mysqli_stmt_num_rows($stmt) == 1){
                    $username_err = "This username is already taken.";
                } else{
                    $username = trim($_POST["username"]);
                }
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }

            mysqli_stmt_close($stmt);
        }
    }
    
    // Validate email
    if(empty(trim($_POST["email"]))){
        $email_err = "Please enter an email.";
    } elseif(!filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL)){
        $email_err = "Please enter a valid email address.";
    } else{
        $sql = "SELECT id FROM users WHERE email = ?";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "s", $param_email);
            $param_email = trim($_POST["email"]);
            
            if(mysqli_stmt_execute($stmt)){
                mysqli_stmt_store_result($stmt);
                
                if(mysqli_stmt_num_rows($stmt) == 1){
                    $email_err = "This email is already registered.";
                } else{
                    $email = trim($_POST["email"]);
                }
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }

            mysqli_stmt_close($stmt);
        }
    }
    
    // Validate password
    if(empty(trim($_POST["password"]))){
        $password_err = "Please enter a password.";     
    } elseif(strlen(trim($_POST["password"])) < 6){
        $password_err = "Password must have at least 6 characters.";
    } else{
        $password = trim($_POST["password"]);
    }
    
    // Validate confirm password
    if(empty(trim($_POST["confirm_password"]))){
        $confirm_password_err = "Please confirm password.";     
    } else{
        $confirm_password = trim($_POST["confirm_password"]);
        if(empty($password_err) && ($password != $confirm_password)){
            $confirm_password_err = "Password did not match.";
        }
    }
    
    // Check input errors before inserting in database
    if(empty($username_err) && empty($password_err) && empty($confirm_password_err) && empty($email_err)){
        $sql = "INSERT INTO users (username, password, email) VALUES (?, ?, ?)";
         
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "sss", $param_username, $param_password, $param_email);
            
            $param_username = $username;
            $param_password = password_hash($password, PASSWORD_DEFAULT);
            $param_email = $email;
            
            if(mysqli_stmt_execute($stmt)){
                header("location: index.php");
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }

            mysqli_stmt_close($stmt);
        }
    }
    
    mysqli_close($conn);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Neighborhood Watch | Secure Registration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #6C63FF;
            --secondary: #4D44DB;
            --accent: #FF6584;
            --dark: #2D3748;
            --light: #F7FAFC;
            --success: #48BB78;
        }
        
        body {
            background: linear-gradient(135deg, #F5F7FA 0%, #E4E7EB 100%);
            min-height: 100vh;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            overflow-x: hidden;
        }
        
        .auth-container {
            max-width: 480px;
            background: white;
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transform: translateY(0);
            transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            border: 1px solid rgba(255, 255, 255, 0.4);
        }
        
        .auth-container:hover {
            transform: translateY(-8px);
            box-shadow: 0 30px 60px -12px rgba(0, 0, 0, 0.15);
        }
        
        .auth-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            padding: 2.5rem;
            color: white;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .auth-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 70%);
            animation: pulse 8s infinite linear;
        }
        
        .logo {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            display: inline-block;
            background: rgba(255,255,255,0.1);
            width: 80px;
            height: 80px;
            border-radius: 50%;
            line-height: 80px;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .auth-body {
            padding: 2.5rem;
        }
        
        .form-floating label {
            color: #718096;
            transition: all 0.3s ease;
        }
        
        .form-control {
            height: 56px;
            border-radius: 12px;
            border: 2px solid #E2E8F0;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(108, 99, 255, 0.2);
        }
        
        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #A0AEC0;
            transition: all 0.3s;
        }
        
        .password-toggle:hover {
            color: var(--primary);
        }
        
        .btn-auth {
            background: linear-gradient(to right, var(--primary), var(--secondary));
            border: none;
            height: 56px;
            border-radius: 12px;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        
        .btn-auth::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(to right, var(--secondary), var(--primary));
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .btn-auth:hover::after {
            opacity: 1;
        }
        
        .btn-auth:active {
            transform: translateY(2px);
        }
        
        .auth-footer {
            text-align: center;
            margin-top: 1.5rem;
            color: #718096;
        }
        
        .auth-link {
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
            position: relative;
        }
        
        .auth-link::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--primary);
            transition: width 0.3s;
        }
        
        .auth-link:hover::after {
            width: 100%;
        }
        
        .floating-shapes {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
        }
        
        .shape {
            position: absolute;
            border-radius: 50%;
            opacity: 0.1;
            filter: blur(40px);
            animation: float 15s infinite linear;
        }
        
        .shape-1 {
            width: 300px;
            height: 300px;
            background: var(--primary);
            top: 20%;
            left: 10%;
            animation-delay: 0s;
        }
        
        .shape-2 {
            width: 400px;
            height: 400px;
            background: var(--accent);
            bottom: 10%;
            right: 10%;
            animation-delay: 3s;
        }
        
        .shape-3 {
            width: 200px;
            height: 200px;
            background: var(--secondary);
            top: 50%;
            right: 20%;
            animation-delay: 6s;
        }
        
        @keyframes float {
            0%, 100% {
                transform: translate(0, 0) rotate(0deg);
            }
            25% {
                transform: translate(50px, 50px) rotate(10deg);
            }
            50% {
                transform: translate(0, 100px) rotate(0deg);
            }
            75% {
                transform: translate(-50px, 50px) rotate(-10deg);
            }
        }
        
        @keyframes pulse {
            0% {
                transform: rotate(0deg);
            }
            100% {
                transform: rotate(360deg);
            }
        }
        
        .error-shake {
            animation: shake 0.5s;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }
        
        .progress-indicator {
            height: 4px;
            background: #E2E8F0;
            border-radius: 2px;
            margin-bottom: 1rem;
            overflow: hidden;
        }
        
        .progress-bar {
            height: 100%;
            width: 0;
            background: linear-gradient(to right, var(--primary), var(--accent));
            transition: width 0.4s ease;
        }
        
        .strength-meter {
            display: flex;
            gap: 4px;
            margin-top: 0.5rem;
        }
        
        .strength-section {
            height: 4px;
            flex-grow: 1;
            background: #E2E8F0;
            border-radius: 2px;
            transition: all 0.3s;
        }
        
        .password-strength {
            margin-top: 0.5rem;
            font-size: 0.875rem;
            color: #718096;
        }
        
        .strength-weak {
            color: #E53E3E;
        }
        
        .strength-medium {
            color: #DD6B20;
        }
        
        .strength-strong {
            color: #38A169;
        }
        
        .terms-check {
            display: flex;
            align-items: flex-start;
            margin-bottom: 1.5rem;
        }
        
        .terms-check input {
            margin-top: 0.3rem;
        }
        
        .terms-check label {
            margin-left: 0.5rem;
            text-align: left;
        }
        
        .terms-link {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }
        
        .divider {
            display: flex;
            align-items: center;
            margin: 1.5rem 0;
            color: #A0AEC0;
        }
        
        .divider::before, .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #E2E8F0;
            margin: 0 1rem;
        }
        
        .social-options {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            margin: 1.5rem 0;
        }
        
        .social-btn {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: white;
            border: 1px solid #E2E8F0;
            color: var(--dark);
            font-size: 1.5rem;
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .social-btn:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            color: var(--primary);
            border-color: var(--primary);
        }
        
        .tooltip-custom {
            position: relative;
        }
        
        .tooltip-custom:hover::after {
            content: attr(data-tooltip);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: var(--dark);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.875rem;
            white-space: nowrap;
            margin-bottom: 10px;
            z-index: 10;
        }
        
        .tooltip-custom:hover::before {
            content: '';
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%) translateY(10px);
            border-width: 6px;
            border-style: solid;
            border-color: var(--dark) transparent transparent transparent;
            margin-bottom: -2px;
            z-index: 11;
        }
    </style>
</head>
<body>
    <div class="floating-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
    </div>
    
    <div class="container d-flex align-items-center justify-content-center min-vh-100 py-5">
        <div class="auth-container animate__animated animate__fadeIn">
            <div class="auth-header">
                <div class="logo">
                    <i class="fas fa-user-plus"></i>
                </div>
                <h2 class="mb-1">Join Your Community</h2>
                <p>Create your secure account</p>
            </div>
            
            <div class="auth-body">
                <form id="registerForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="mb-4">
                        <div class="form-floating">
                            <input type="text" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" 
                                   id="username" name="username" placeholder="Username" value="<?php echo $username; ?>">
                            <label for="username">Username</label>
                            <div class="invalid-feedback"><?php echo $username_err; ?></div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <div class="form-floating">
                            <input type="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" 
                                   id="email" name="email" placeholder="Email" value="<?php echo $email; ?>">
                            <label for="email">Email</label>
                            <div class="invalid-feedback"><?php echo $email_err; ?></div>
                        </div>
                    </div>
                    
                    <div class="mb-4 position-relative">
                        <div class="form-floating">
                            <input type="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" 
                                   id="password" name="password" placeholder="Password" oninput="checkPasswordStrength(this.value)">
                            <label for="password">Password</label>
                            <div class="invalid-feedback"><?php echo $password_err; ?></div>
                        </div>
                        <span class="password-toggle" onclick="togglePassword('password')">
                            <i class="fas fa-eye"></i>
                        </span>
                        <div class="progress-indicator mt-2">
                            <div class="progress-bar" id="passwordStrengthBar"></div>
                        </div>
                        <div class="password-strength" id="passwordStrengthText">
                            Password strength: <span id="strengthLevel">none</span>
                        </div>
                    </div>
                    
                    <div class="mb-4 position-relative">
                        <div class="form-floating">
                            <input type="password" class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>" 
                                   id="confirm_password" name="confirm_password" placeholder="Confirm Password" oninput="checkPasswordMatch()">
                            <label for="confirm_password">Confirm Password</label>
                            <div class="invalid-feedback"><?php echo $confirm_password_err; ?></div>
                        </div>
                        <span class="password-toggle" onclick="togglePassword('confirm_password')">
                            <i class="fas fa-eye"></i>
                        </span>
                        <div id="passwordMatchIndicator" class="password-strength"></div>
                    </div>
                    
                    <div class="terms-check">
                        <input type="checkbox" class="form-check-input" id="agreeTerms" required>
                        <label for="agreeTerms" class="form-check-label">
                            I agree to the <a href="#" class="terms-link">Terms of Service</a> and <a href="#" class="terms-link">Privacy Policy</a>
                        </label>
                    </div>
                    
                    <button type="submit" class="btn btn-auth w-100 mb-3">
                        <span class="position-relative z-index-1">Create Account</span>
                    </button>
                    
                    <!-- <div class="divider">or sign up with</div>
                    
                    <div class="social-options">
                        <div class="social-btn tooltip-custom" data-tooltip="Google">
                            <i class="fab fa-google"></i>
                        </div>
                        <div class="social-btn tooltip-custom" data-tooltip="Apple">
                            <i class="fab fa-apple"></i>
                        </div>
                        <div class="social-btn tooltip-custom" data-tooltip="Facebook">
                            <i class="fab fa-facebook-f"></i>
                        </div>
                    </div> -->
                </form>
            </div>
            
            <div class="auth-footer pb-4">
                <p>Already have an account? <a href="index.php" class="auth-link">Sign in</a></p>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        function togglePassword(id) {
            const input = document.getElementById(id);
            const icon = document.querySelector(`[onclick="togglePassword('${id}')"] i`);
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        
        // Password strength checker
        function checkPasswordStrength(password) {
            const strengthBar = document.getElementById('passwordStrengthBar');
            const strengthText = document.getElementById('strengthLevel');
            let strength = 0;
            
            // Length check
            if (password.length >= 6) strength += 1;
            if (password.length >= 8) strength += 1;
            if (password.length >= 12) strength += 1;
            
            // Complexity checks
            if (password.match(/[a-z]/)) strength += 1; // lowercase
            if (password.match(/[A-Z]/)) strength += 1; // uppercase
            if (password.match(/[0-9]/)) strength += 1; // numbers
            if (password.match(/[^a-zA-Z0-9]/)) strength += 1; // special chars
            
            // Update UI
            let strengthPercent = 0;
            let strengthClass = '';
            let strengthDescription = '';
            
            if (strength <= 2) {
                strengthPercent = 33;
                strengthClass = 'strength-weak';
                strengthDescription = 'Weak';
            } else if (strength <= 4) {
                strengthPercent = 66;
                strengthClass = 'strength-medium';
                strengthDescription = 'Medium';
            } else {
                strengthPercent = 100;
                strengthClass = 'strength-strong';
                strengthDescription = 'Strong';
            }
            
            strengthBar.style.width = strengthPercent + '%';
            strengthText.textContent = strengthDescription;
            strengthText.className = strengthClass;
        }
        
        // Check password match
        function checkPasswordMatch() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const matchIndicator = document.getElementById('passwordMatchIndicator');
            
            if (confirmPassword.length === 0) {
                matchIndicator.textContent = '';
                return;
            }
            
            if (password === confirmPassword) {
                matchIndicator.innerHTML = '<i class="fas fa-check-circle text-success me-2"></i>Passwords match';
            } else {
                matchIndicator.innerHTML = '<i class="fas fa-times-circle text-danger me-2"></i>Passwords do not match';
            }
        }
        
        // Form submission animation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const btn = this.querySelector('button[type="submit"]');
            btn.innerHTML = '<span class="position-relative z-index-1"><span class="spinner-border spinner-border-sm me-2" role="status"></span>Creating account...</span>';
            btn.disabled = true;
        });
        
        // Social login simulation
        document.querySelectorAll('.social-options .social-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const originalContent = this.innerHTML;
                this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';
                
                setTimeout(() => {
                    this.innerHTML = originalContent;
                    alert('Social registration would be implemented here in a production environment');
                }, 1500);
            });
        });
        
        // Animate shapes on scroll
        window.addEventListener('scroll', function() {
            const scrollY = window.scrollY;
            const shapes = document.querySelectorAll('.shape');
            
            shapes.forEach((shape, index) => {
                const speed = (index + 1) * 0.2;
                shape.style.transform = `translate(${scrollY * speed * 0.1}px, ${scrollY * speed * 0.1}px) rotate(${scrollY * speed * 0.2}deg)`;
            });
        });
    </script>
</body>
    <?php include 'chatbot.php'; ?>
</html>