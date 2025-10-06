<?php
// Include config file
require_once '../config/config.php';

// Check if the user is logged in, otherwise redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Initialize variables
$username = $first_name = $last_name = $email = $phone = $address = $hire_date = '';
$employee_id = $_SESSION["employee_id"];

// Fetch user data with employee details
$sql = "SELECT u.username, e.first_name, e.last_name, e.email, e.phone, e.address, e.hire_date 
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
            $hire_date = $row['hire_date'];
        } else {
            $_SESSION['error'] = "Profile not found.";
            header("location: dashboard.php");
            exit();
        }
    } else {
        $_SESSION['error'] = "Oops! Something went wrong. Please try again later.";
        header("location: dashboard.php");
        exit();
    }
    unset($stmt);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile</title>
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
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background-color: #007bff;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            margin: 0 auto 15px;
        }
        .profile-details {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .detail-item {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .detail-item:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: 600;
            color: #6c757d;
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
                        <a href="edit_profile.php" class="btn btn-primary">
                            <i class="fas fa-edit"></i> Edit Profile
                        </a>
                    </div>
                </div>
            </nav>

            <div class="container mt-4">
                <?php 
                // Display success/error messages
                if (isset($_SESSION['success'])) {
                    echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
                    unset($_SESSION['success']);
                }
                if (isset($_SESSION['error'])) {
                    echo '<div class="alert alert-danger">' . $_SESSION['error'] . '</div>';
                    unset($_SESSION['error']);
                }
                ?>

                <div class="profile-header text-center">
                    <div class="profile-avatar">
                        <?php echo strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1)); ?>
                    </div>
                    <h3><?php echo htmlspecialchars($first_name . ' ' . $last_name); ?></h3>
                    <p class="text-muted">HR Administrator</p>
                </div>

                <div class="profile-details">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="detail-item">
                                <div class="detail-label">Employee ID</div>
                                <div><?php echo htmlspecialchars($employee_id); ?></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-item">
                                <div class="detail-label">Username</div>
                                <div><?php echo htmlspecialchars($username); ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="detail-item">
                                <div class="detail-label">Email</div>
                                <div><?php echo htmlspecialchars($email); ?></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-item">
                                <div class="detail-label">Phone</div>
                                <div><?php echo htmlspecialchars($phone ?: 'N/A'); ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <div class="detail-item">
                                <div class="detail-label">Address</div>
                                <div><?php echo nl2br(htmlspecialchars($address ?: 'N/A')); ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="detail-item">
                                <div class="detail-label">Hire Date</div>
                                <div><?php echo date('F j, Y', strtotime($hire_date)); ?></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-item">
                                <div class="detail-label">Role</div>
                                <div>HR Administrator</div>
                            </div>
                        </div>
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
