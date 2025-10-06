<?php
// Include config file
require_once '../config/config.php';

// Check if the user is logged in and has the correct role, otherwise redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'Super Admin'){
    header("location: login.php");
    exit;
}

// Fetch all HR Admins
$sql = "SELECT id, username FROM users WHERE role = 'HR Admin'";
$hr_admins = [];
if($result = $pdo->query($sql)){
    if($result->rowCount() > 0){
        $hr_admins = $result->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage HR Admins</title>
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

            <h1 class="page-title">Manage HR Admins</h1>
            <a href="add_hr_admin.php" class="btn btn-success mb-3">Add New HR Admin</a>
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(!empty($hr_admins)): ?>
                        <?php foreach($hr_admins as $hr_admin): ?>
                            <tr>
                                <td><?php echo $hr_admin['id']; ?></td>
                                <td><?php echo $hr_admin['username']; ?></td>
                                <td>
                                    <a href="edit_hr_admin.php?id=<?php echo $hr_admin['id']; ?>" class="btn btn-primary">Edit</a>
                                    <a href="delete_user.php?id=<?php echo $hr_admin['id']; ?>" class="btn btn-danger">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" class="text-center">No HR Admins found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <a href="super_admin_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
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
