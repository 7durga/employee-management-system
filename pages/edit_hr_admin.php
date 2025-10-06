<?php
// Include config file
require_once '../config/config.php';

// Check if the user is logged in and has the correct role, otherwise redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'Super Admin'){
    header("location: login.php");
    exit;
}

$username = $password = $first_name = $last_name = $email = $phone = $department = $position = "";
$username_err = $password_err = $first_name_err = $last_name_err = $email_err = "";
$id = $_GET["id"];

// Fetch user data with employee details
$sql = "SELECT u.id, u.username, u.employee_id, e.first_name, e.last_name, e.email, e.phone, e.address, e.hire_date 
        FROM users u 
        LEFT JOIN employees e ON u.employee_id = e.employee_id 
        WHERE u.id = :id AND u.role = 'HR Admin'";
        
if($stmt = $pdo->prepare($sql)){
    $stmt->bindParam(":id", $id, PDO::PARAM_INT);
    if($stmt->execute()){
        if($stmt->rowCount() == 1){
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $username = $row['username'];
            $employee_id = $row['employee_id'];
            $first_name = $row['first_name'];
            $last_name = $row['last_name'];
            $email = $row['email'];
            $phone = $row['phone'];
            $address = $row['address'];
            $hire_date = $row['hire_date'];
        } else{
            $_SESSION['error'] = "HR Admin not found.";
            header("location: manage_hr_admins.php");
            exit();
        }
    } else{
        $_SESSION['error'] = "Oops! Something went wrong. Please try again later.";
        header("location: manage_hr_admins.php");
        exit();
    }
    unset($stmt);
}

if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validate first name
    if(empty(trim($_POST["first_name"]))){ 
        $first_name_err = "Please enter first name.";
    } else {
        $first_name = trim($_POST["first_name"]);
        if(!preg_match("/^[a-zA-Z ]+$/", $first_name)){
            $first_name_err = "Only letters and white space allowed.";
        }
    }
    
    // Validate last name
    if(empty(trim($_POST["last_name"]))){ 
        $last_name_err = "Please enter last name.";
    } else {
        $last_name = trim($_POST["last_name"]);
        if(!preg_match("/^[a-zA-Z ]+$/", $last_name)){
            $last_name_err = "Only letters and white space allowed.";
        }
    }
    
    // Validate email
    if(empty(trim($_POST["email"]))){ 
        $email_err = "Please enter an email.";
    } else {
        $email = trim($_POST["email"]);
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
            $email_err = "Please enter a valid email address.";
        }
    }
    
    // Validate phone (optional)
    $phone = !empty($_POST["phone"]) ? trim($_POST["phone"]) : "";
    
    // Get address
    $address = !empty($_POST["address"]) ? trim($_POST["address"]) : "";
    
    // Validate username
    if(empty(trim($_POST["username"]))){ 
        $username_err = "Please enter a username.";
    } else {
        $new_username = trim($_POST["username"]);
        if($new_username != $username) {
            // Check if username already exists
            $sql = "SELECT id FROM users WHERE username = :username";
            if($stmt = $pdo->prepare($sql)){
                $stmt->bindParam(":username", $new_username, PDO::PARAM_STR);
                if($stmt->execute()){
                    if($stmt->rowCount() > 0){
                        $username_err = "This username is already taken.";
                    } else{
                        $username = $new_username;
                    }
                }
                unset($stmt);
            }
        }
    }

    // Validate password (only if a new password is entered)
    $password_update = "";
    if(!empty(trim($_POST["password"]))){ 
        if(strlen(trim($_POST["password"])) < 6){
            $password_err = "Password must have at least 6 characters.";
        } else{
            $password = trim($_POST["password"]);
        }
    }
    
    // Check input errors before updating the database
    if(empty($username_err) && empty($password_err) && empty($first_name_err) && empty($last_name_err) && empty($email_err)){
        // Start transaction
        $pdo->beginTransaction();
        
        try {
            // First update the employees table
            $sql1 = "UPDATE employees SET 
                    first_name = :first_name,
                    last_name = :last_name,
                    email = :email,
                    phone = :phone,
                    address = :address
                    WHERE employee_id = :employee_id";
            
            $stmt1 = $pdo->prepare($sql1);
            $stmt1->bindParam(":first_name", $first_name, PDO::PARAM_STR);
            $stmt1->bindParam(":last_name", $last_name, PDO::PARAM_STR);
            $stmt1->bindParam(":email", $email, PDO::PARAM_STR);
            $stmt1->bindParam(":phone", $phone, PDO::PARAM_STR);
            $stmt1->bindParam(":address", $address, PDO::PARAM_STR);
            $stmt1->bindParam(":employee_id", $employee_id, PDO::PARAM_STR);
            $stmt1->execute();
            
            // Then update the users table
            $sql2 = "UPDATE users SET username = :username" . 
                   (!empty(trim($_POST["password"])) ? ", password = :password" : "") . 
                   " WHERE id = :id";
            
            $stmt2 = $pdo->prepare($sql2);
            $stmt2->bindParam(":username", $username, PDO::PARAM_STR);
            if(!empty(trim($_POST["password"]))){
                $param_password = password_hash(trim($_POST["password"]), PASSWORD_DEFAULT);
                $stmt2->bindParam(":password", $param_password, PDO::PARAM_STR);
            }
            $stmt2->bindParam(":id", $id, PDO::PARAM_INT);
            $stmt2->execute();
            
            // Commit the transaction
            $pdo->commit();
            
            // Set success message and redirect
            $_SESSION['success'] = "HR Admin updated successfully!";
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
    unset($pdo);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit HR Admin</title>
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

            <h1 class="page-title">Edit HR Admin</h1>
            <form action="<?php echo htmlspecialchars(basename($_SERVER['REQUEST_URI'])); ?>" method="post">
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
                        <label>Password (leave blank to keep current password)</label>
                        <input type="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>">
                        <span class="invalid-feedback"><?php echo $password_err; ?></span>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Phone</label>
                        <input type="text" name="phone" class="form-control" value="<?php echo $phone; ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Address</label>
                        <textarea name="address" class="form-control" rows="3"><?php echo htmlspecialchars($address); ?></textarea>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Hire Date</label>
                        <input type="date" name="hire_date" class="form-control" value="<?php echo $hire_date; ?>">
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