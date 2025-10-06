<?php
// Include config file
require_once '../config/config.php';

// Check if the user is logged in and has the correct role, otherwise redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'HR Admin'){
    header("location: login.php");
    exit;
}

// Fetch all employees
$sql = "SELECT e.id, e.employee_id, e.first_name, e.last_name, e.email, u.username FROM employees e JOIN users u ON e.employee_id = u.employee_id WHERE u.role = 'Employee'";
$employees = [];
if($result = $pdo->query($sql)){
    if($result->rowCount() > 0){
        $employees = $result->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Employees</title>
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

            <h1 class="page-title">Manage Employees</h1>
            <a href="add_employee.php" class="btn btn-success mb-3">Add New Employee</a>
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Employee ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Username</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(!empty($employees)):
                        foreach($employees as $employee):
                    ?>
                            <tr>
                                <td><?php echo $employee['employee_id']; ?></td>
                                <td><?php echo $employee['first_name'] . ' ' . $employee['last_name']; ?></td>
                                <td><?php echo $employee['email']; ?></td>
                                <td><?php echo $employee['username']; ?></td>
                                <td>
                                    <a href="edit_employee.php?id=<?php echo $employee['id']; ?>" class="btn btn-primary">Edit</a>
                                    <a href="delete_employee.php?id=<?php echo $employee['id']; ?>" class="btn btn-danger">Delete</a>
                                </td>
                            </tr>
                    <?php
                        endforeach;
                    else:
                    ?>
                        <tr>
                            <td colspan="5" class="text-center">No employees found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <a href="hr_admin_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
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
