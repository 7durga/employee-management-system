<?php
// Include config file
require_once '../config/config.php';

// Check if the user is logged in, otherwise redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Initialize variables
$username = $first_name = $last_name = $email = $phone = $address = '';
$username_err = $first_name_err = $last_name_err = $email_err = $password_err = '';
$employee_id = $_SESSION["employee_id"];

// Fetch user data
$sql = "SELECT u.username, e.first_name, e.last_name, e.email, e.phone, e.address 
        FROM users u 
        LEFT JOIN employees e ON u.employee_id = e.employee_id 
        WHERE u.employee_id = :employee_id";
        
if($stmt = $pdo->prepare($sql)){
    $stmt->bindParam(":employee_id", $employee_id, PDO::PARAM_STR);
    if($stmt->execute()){
        if($stmt->rowCount() == 1){
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $username = $row['username'];
            $first_name = $row['first_name'];
            $last_name = $row['last_name'];
            $email = $row['email'];
            $phone = $row['phone'];
            $address = $row['address'];
        } else {
            $_SESSION['error'] = "Profile not found.";
            header("location: profile.php");
            exit();
        }
    } else {
        $_SESSION['error'] = "Oops! Something went wrong. Please try again later.";
        header("location: profile.php");
        exit();
    }
    unset($stmt);
}

// Process form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validate first name
    if(empty(trim($_POST["first_name"]))){ 
        $first_name_err = "Please enter your first name.";
    } else {
        $first_name = trim($_POST["first_name"]);
        if(!preg_match("/^[a-zA-Z ]+$/", $first_name)){
            $first_name_err = "Only letters and white space allowed.";
        }
    }
    
    // Validate last name
    if(empty(trim($_POST["last_name"]))){ 
        $last_name_err = "Please enter your last name.";
    } else {
        $last_name = trim($_POST["last_name"]);
        if(!preg_match("/^[a-zA-Z ]+$/", $last_name)){
            $last_name_err = "Only letters and white space allowed.";
        }
    }
    
    // Validate email
    if(empty(trim($_POST["email"]))){ 
        $email_err = "Please enter your email.";
    } else {
        $email = trim($_POST["email"]);
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
            $email_err = "Please enter a valid email address.";
        }
    }
    
    // Get other fields
    $phone = !empty($_POST["phone"]) ? trim($_POST["phone"]) : "";
    $address = !empty($_POST["address"]) ? trim($_POST["address"]) : "";
    
    // Check input errors before updating the database
    if(empty($first_name_err) && empty($last_name_err) && empty($email_err)){
        // Start transaction
        $pdo->beginTransaction();
        
        try {
            // Update employees table
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
            
            // If password is provided, update it
            if(!empty(trim($_POST["password"]))){
                if(strlen(trim($_POST["password"])) < 6){
                    $password_err = "Password must have at least 6 characters.";
                    $pdo->rollBack();
                } else {
                    $sql2 = "UPDATE users SET password = :password WHERE employee_id = :employee_id";
                    $stmt2 = $pdo->prepare($sql2);
                    $param_password = password_hash(trim($_POST["password"]), PASSWORD_DEFAULT);
                    $stmt2->bindParam(":password", $param_password, PDO::PARAM_STR);
                    $stmt2->bindParam(":employee_id", $employee_id, PDO::PARAM_STR);
                    $stmt2->execute();
                }
            }
            
            // Commit the transaction
            $pdo->commit();
            
            $_SESSION['success'] = "Profile updated successfully!";
            header("location: profile.php");
            exit();
            
        } catch (Exception $e) {
            // Rollback the transaction if something went wrong
            $pdo->rollBack();
            $_SESSION['error'] = "Error updating profile: " . $e->getMessage();
            header("location: profile.php");
            exit();
        }
        
        // Close statements
        unset($stmt1);
        if(isset($stmt2)) unset($stmt2);
    }
    
    unset($pdo);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Profile</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        .profile-header {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background-color: #007bff;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            margin: 0 auto 15px;
        }
    </style>
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
                    <div class="ml-auto">
                        <a href="profile.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Profile
                        </a>
                    </div>
                </div>
            </nav>

            <div class="container mt-4">
                <h2 class="mb-4">Edit Profile</h2>
                
                <?php 
                // Display error messages
                if (isset($_SESSION['error'])) {
                    echo '<div class="alert alert-danger">' . $_SESSION['error'] . '</div>';
                    unset($_SESSION['error']);
                }
                ?>

                <div class="card">
                    <div class="card-body">
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label>First Name <span class="text-danger">*</span></label>
                                    <input type="text" name="first_name" class="form-control <?php echo (!empty($first_name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($first_name); ?>">
                                    <span class="invalid-feedback"><?php echo $first_name_err; ?></span>
                                </div>
                                <div class="form-group col-md-6">
                                    <label>Last Name <span class="text-danger">*</span></label>
                                    <input type="text" name="last_name" class="form-control <?php echo (!empty($last_name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($last_name); ?>">
                                    <span class="invalid-feedback"><?php echo $last_name_err; ?></span>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label>Email <span class="text-danger">*</span></label>
                                    <input type="email" name="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($email); ?>">
                                    <span class="invalid-feedback"><?php echo $email_err; ?></span>
                                </div>
                                <div class="form-group col-md-6">
                                    <label>Phone</label>
                                    <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($phone); ?>">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Address</label>
                                <textarea name="address" class="form-control" rows="3"><?php echo htmlspecialchars($address); ?></textarea>
                            </div>
                            <div class="form-group">
                                <label>New Password (leave blank to keep current password)</label>
                                <input type="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>">
                                <small class="form-text text-muted">Leave blank to keep current password</small>
                                <span class="invalid-feedback"><?php echo $password_err; ?></span>
                            </div>
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">Update Profile</button>
                                <a href="profile.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
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
