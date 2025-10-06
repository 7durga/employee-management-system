<?php
// Include config file
require_once '../config/config.php';

// Check if the user is already logged in, if yes then redirect to the dashboard
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    header("location: ../index.php");
    exit;
}

$username = $password = "";
$username_err = $password_err = "";

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){

    // Check if username is empty
    if(empty(trim($_POST["username"]))){ 
        $username_err = "Please enter username.";
    } else{
        $username = trim($_POST["username"]);
    }

    // Check if password is empty
    if(empty(trim($_POST["password"]))){ 
        $password_err = "Please enter your password.";
    } else{
        $password = trim($_POST["password"]);
    }

    // Validate credentials
    if(empty($username_err) && empty($password_err)){
        // Prepare a select statement to get user data including employee_id
        $sql = "SELECT u.id, u.username, u.password, u.role, u.employee_id 
                FROM users u 
                LEFT JOIN employees e ON u.employee_id = e.employee_id 
                WHERE u.username = :username";

        if($stmt = $pdo->prepare($sql)){
            // Bind variables to the prepared statement as parameters
            $stmt->bindParam(":username", $param_username, PDO::PARAM_STR);

            // Set parameters
            $param_username = $username;

            // Attempt to execute the prepared statement
            if($stmt->execute()){
                // Check if username exists, if yes then verify password
                if($stmt->rowCount() == 1){
                    if($row = $stmt->fetch()){
                        $id = $row["id"];
                        $hashed_password = $row["password"];
                        if(password_verify($password, $hashed_password)){
                            // Password is correct, so start a new session
                            session_start();

                            // Store data in session variables
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $id;
                            $_SESSION["username"] = $username;
                            $_SESSION["role"] = $row["role"];
                            $_SESSION["employee_id"] = $row["employee_id"];

                            // Redirect user to the appropriate dashboard
                            header("location: ../index.php");
                        } else{
                            // Display an error message if password is not valid
                            $password_err = "The password you entered was not valid.";
                        }
                    }
                } else{
                    // Display an error message if username doesn't exist
                    $username_err = "No account found with that username.";
                }
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            unset($stmt);
        }
    }

    // Close connection
    unset($pdo);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Employee Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4cc9f0;
            --dark-color: #1a1a2e;
            --light-color: #f8f9fa;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            perspective: 1000px;
            overflow: hidden;
        }

        .login-container {
            width: 100%;
            max-width: 400px;
            padding: 2rem;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 25px 45px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transform-style: preserve-3d;
            transition: transform 0.5s ease, box-shadow 0.5s ease;
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0) rotateX(0deg) rotateY(0deg); }
            50% { transform: translateY(-20px) rotateX(5deg) rotateY(5deg); }
        }

        .login-container:hover {
            transform: translateY(-10px) rotateX(5deg) rotateY(5deg);
            box-shadow: 0 35px 60px rgba(0, 0, 0, 0.4);
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
            color: #fff;
            transform: translateZ(40px);
        }

        .login-header h2 {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            background: linear-gradient(45deg, var(--accent-color), #f72585);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
        }

        .login-header p {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.95rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
            transform: translateZ(30px);
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: rgba(255, 255, 255, 0.9);
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .form-control {
            width: 100%;
            padding: 0.8rem 1rem;
            font-size: 1rem;
            border: none;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            color: #fff;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .form-control:focus {
            outline: none;
            background: rgba(255, 255, 255, 0.15);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.3);
            transform: translateY(-2px);
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }

        .btn-login {
            width: 100%;
            padding: 0.8rem;
            font-size: 1rem;
            font-weight: 600;
            color: #fff;
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            z-index: 1;
            transform: translateZ(20px);
        }

        .btn-login:before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, var(--accent-color), #f72585);
            transition: all 0.6s ease;
            z-index: -1;
        }

        .btn-login:hover {
            transform: translateY(-3px) translateZ(30px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }

        .btn-login:hover:before {
            left: 0;
        }

        .help-block {
            color: #ff6b6b;
            font-size: 0.85rem;
            margin-top: 0.3rem;
            display: block;
            transform: translateZ(10px);
        }

        .floating-shapes {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
        }

        .shape {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(5px);
            border-radius: 10px;
            animation: float-shapes 15s infinite linear;
        }

        @keyframes float-shapes {
            0% { transform: translateY(0) rotate(0deg); opacity: 0; }
            10% { opacity: 0.5; }
            90% { opacity: 0.5; }
            100% { transform: translateY(-1000px) rotate(720deg); opacity: 0; }
        }

        .logo {
            width: 80px;
            height: 80px;
            margin: 0 auto 1.5rem;
            display: block;
            animation: pulse 4s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        @media (max-width: 576px) {
            .login-container {
                margin: 1rem;
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="floating-shapes">
        <?php for($i = 0; $i < 15; $i++): ?>
            <div class="shape" style="
                width: <?php echo rand(30, 100); ?>px;
                height: <?php echo rand(30, 100); ?>px;
                top: <?php echo rand(0, 100); ?>%;
                left: <?php echo rand(0, 100); ?>%;
                animation-duration: <?php echo rand(10, 30); ?>s;
                animation-delay: -<?php echo rand(0, 15); ?>s;
                background: rgba(<?php echo rand(50, 200); ?>, <?php echo rand(50, 200); ?>, <?php echo rand(50, 200); ?>, 0.1);
            "></div>
        <?php endfor; ?>
    </div>

    <div class="login-container">
        <div class="login-header">
            <svg class="logo" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <linearGradient id="gradient" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" stop-color="#4cc9f0" />
                        <stop offset="100%" stop-color="#4361ee" />
                    </linearGradient>
                </defs>
                <circle cx="50" cy="50" r="45" fill="none" stroke="url(#gradient)" stroke-width="4" />
                <path d="M50 25 L75 50 L50 75 L25 50 Z" fill="none" stroke="url(#gradient)" stroke-width="4" stroke-linejoin="round" />
                <circle cx="50" cy="50" r="10" fill="url(#gradient)" />
            </svg>
            <h2>Welcome Back</h2>
            <p>Sign in to access your dashboard</p>
        </div>
        
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label for="username"><i class="fas fa-user me-2"></i>Username</label>
                <input type="text" id="username" name="username" class="form-control" value="<?php echo htmlspecialchars($username); ?>" placeholder="Enter your username">
                <?php if(!empty($username_err)): ?>
                    <span class="help-block"><i class="fas fa-exclamation-circle me-1"></i><?php echo $username_err; ?></span>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="password"><i class="fas fa-lock me-2"></i>Password</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password">
                <?php if(!empty($password_err)): ?>
                    <span class="help-block"><i class="fas fa-exclamation-circle me-1"></i><?php echo $password_err; ?></span>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt me-2"></i>Sign In
                </button>
            </div>
        </form>
    </div>

    <script>
        // Add 3D tilt effect
        document.querySelector('.login-container').addEventListener('mousemove', (e) => {
            const container = e.currentTarget;
            const rect = container.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            const centerX = rect.width / 2;
            const centerY = rect.height / 2;
            
            const angleX = (y - centerY) / 20;
            const angleY = (centerX - x) / 20;
            
            container.style.transform = `perspective(1000px) rotateX(${angleX}deg) rotateY(${angleY}deg) translateZ(20px)`;
        });
        
        document.querySelector('.login-container').addEventListener('mouseleave', (e) => {
            e.currentTarget.style.transform = 'perspective(1000px) rotateX(0) rotateY(0)';
        });
    </script>
</body>
</html>
