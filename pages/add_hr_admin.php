<?php
// Include config file
require_once '../config/config.php';

// Check if the user is logged in and has the correct role, otherwise redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'Super Admin'){
    header("location: login.php");
    exit;
}

// Initialize variables
$username = $password = $first_name = $last_name = $email = $phone = $department = $position = "";
$username_err = $password_err = $first_name_err = $last_name_err = $email_err = $phone_err = "";

// Process form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validate username
    if(empty(trim($_POST["username"]))){
        $username_err = "Please enter a username.";
    } else{
        // Check if username already exists
        $sql = "SELECT id FROM users WHERE username = :username";
        if($stmt = $pdo->prepare($sql)){
            $stmt->bindParam(":username", $param_username, PDO::PARAM_STR);
            $param_username = trim($_POST["username"]);
            if($stmt->execute()){
                if($stmt->rowCount() == 1){
                    $username_err = "This username is already taken.";
                } else{
                    $username = trim($_POST["username"]);
                }
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }
            unset($stmt);
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

    // Validate first name
    if(empty(trim($_POST["first_name"]))){
        $first_name_err = "Please enter first name.";
    } else{
        $first_name = trim($_POST["first_name"]);
        // Check if name only contains letters and whitespace
        if(!preg_match("/^[a-zA-Z ]+$/", $first_name)){
            $first_name_err = "Only letters and white space allowed.";
        }
    }

    // Validate last name
    if(empty(trim($_POST["last_name"]))){
        $last_name_err = "Please enter last name.";
    } else{
        $last_name = trim($_POST["last_name"]);
        // Check if name only contains letters and whitespace
        if(!preg_match("/^[a-zA-Z ]+$/", $last_name)){
            $last_name_err = "Only letters and white space allowed.";
        }
    }

    // Validate email
    if(empty(trim($_POST["email"]))){
        $email_err = "Please enter an email address.";
    } else{
        $email = trim($_POST["email"]);
        // Check if email is valid
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
            $email_err = "Please enter a valid email address.";
        }
    }

    // Validate phone (optional but should be valid if provided)
    if(!empty(trim($_POST["phone"]))) {
        $phone = trim($_POST["phone"]);
        // Basic phone number validation (modify as needed)
        if(!preg_match("/^[0-9\-\+\(\)\s]*$/", $phone)) {
            $phone_err = "Please enter a valid phone number.";
        }
    }
    
    // Get department and position (optional)
    $department = !empty($_POST["department"]) ? trim($_POST["department"]) : "";
    $position = !empty($_POST["position"]) ? trim($_POST["position"]) : "HR Admin";

    // Check input errors before inserting in database
    if(empty($username_err) && empty($password_err) && empty($first_name_err) && empty($last_name_err) && empty($email_err) && empty($phone_err)){
        // Start transaction
        $pdo->beginTransaction();
        
        try {
            // Generate a unique employee ID
            $employee_id = 'HR' . strtoupper(substr($first_name, 0, 1)) . strtoupper(substr($last_name, 0, 1)) . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
            
            // Insert into employees table first
            $sql1 = "INSERT INTO employees (employee_id, first_name, last_name, email, phone, department, position, hire_date) 
                    VALUES (:employee_id, :first_name, :last_name, :email, :phone, :department, :position, CURDATE())";
                    
            $stmt1 = $pdo->prepare($sql1);
            $stmt1->bindParam(":employee_id", $employee_id, PDO::PARAM_STR);
            $stmt1->bindParam(":first_name", $first_name, PDO::PARAM_STR);
            $stmt1->bindParam(":last_name", $last_name, PDO::PARAM_STR);
            $stmt1->bindParam(":email", $email, PDO::PARAM_STR);
            $stmt1->bindParam(":phone", $phone, PDO::PARAM_STR);
            $stmt1->bindParam(":department", $department, PDO::PARAM_STR);
            $stmt1->bindParam(":position", $position, PDO::PARAM_STR);
            $stmt1->execute();
            
            // Then insert into users table with the same employee_id
            $sql2 = "INSERT INTO users (username, password, role, employee_id) 
                    VALUES (:username, :password, 'HR Admin', :employee_id)";
                    
            $stmt2 = $pdo->prepare($sql2);
            $stmt2->bindParam(":username", $username, PDO::PARAM_STR);
            $stmt2->bindParam(":password", password_hash($password, PASSWORD_DEFAULT), PDO::PARAM_STR);
            $stmt2->bindParam(":employee_id", $employee_id, PDO::PARAM_STR);
            $stmt2->execute();
            
            // Commit the transaction
            $pdo->commit();
            
            // Set success message and redirect
            $_SESSION['success'] = "HR Admin added successfully!";
            header("location: manage_hr_admins.php");
            exit();
            
        } catch (Exception $e) {
            // Rollback the transaction if something went wrong
            $pdo->rollBack();
            $error = "Error: " . $e->getMessage();
        }
        
        // Close statements
        unset($stmt1);
        unset($stmt2);
    }
}
unset($pdo);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add HR Admin</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
</head>
<body>
    <div class="wrapper">
        <?php include 'templates/sidebar.php'; ?>

        <div id="content">
            <nav class="navbar navbar-expand-lg navbar-light bg-light">
                <div class="container-fluid">
                    <button type="button" id="sidebarCollapse" class="btn btn-info">
                        <i class="fas fa-align-left"></i>
                        <span>Toggle Sidebar</span>
                    </button>
                </div>
            </nav>

            <h1 class="page-title">Add New HR Admin</h1>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>First Name</label>
                        <input type="text" name="first_name" class="form-control <?php echo (!empty($first_name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $first_name; ?>">
                        <span class="invalid-feedback"><?php echo $first_name_err; ?></span>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Last Name</label>
                        <input type="text" name="last_name" class="form-control <?php echo (!empty($last_name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $last_name; ?>">
                        <span class="invalid-feedback"><?php echo $last_name_err; ?></span>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Username</label>
                        <input type="text" name="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $username; ?>">
                        <span class="invalid-feedback"><?php echo $username_err; ?></span>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $email; ?>">
                        <span class="invalid-feedback"><?php echo $email_err; ?></span>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Password</label>
                        <input type="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $password; ?>">
                        <span class="invalid-feedback"><?php echo $password_err; ?></span>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Phone</label>
                        <input type="text" name="phone" class="form-control" value="<?php echo $phone; ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Department</label>
                        <input type="text" name="department" class="form-control" value="<?php echo $department; ?>">
                    </div>
                    <div class="form-group col-md-6">
                        <label>Position</label>
                        <input type="text" name="position" class="form-control" value="<?php echo $position; ?>">
                    </div>
                </div>
                <div class="form-group">
                    <input type="submit" class="btn btn-primary" value="Submit">
                    <a href="manage_hr_admins.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        $(document).ready(function () {
            $('#sidebarCollapse').on('click', function () {
                $('#sidebar').toggleClass('active');
            });
        });
    </script>
</body>
</html>
