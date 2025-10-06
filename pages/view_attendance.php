<?php
require_once '../config/config.php';

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !in_array($_SESSION["role"], ['HR Admin', 'Super Admin'])){
    header("location: login.php");
    exit;
}

$filter_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

$sql = "SELECT a.id, e.first_name, e.last_name, a.punch_in_time, a.punch_out_time, a.date 
        FROM attendance a 
        JOIN employees e ON a.employee_id = e.employee_id 
        WHERE a.date = :filter_date";

$stmt = $pdo->prepare($sql);
$stmt->bindParam(':filter_date', $filter_date, PDO::PARAM_STR);
$stmt->execute();
$attendance_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Attendance</title>
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

            <h1 class="page-title">View Attendance</h1>
            <form method="get" class="form-inline mb-3">
                <div class="form-group">
                    <label for="date" class="mr-2">Select Date:</label>
                    <input type="date" id="date" name="date" class="form-control" value="<?php echo $filter_date; ?>">
                </div>
                <button type="submit" class="btn btn-primary ml-2">Filter</button>
            </form>

            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Employee Name</th>
                        <th>Date</th>
                        <th>Punch In Time</th>
                        <th>Punch Out Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(!empty($attendance_records)):
                        foreach($attendance_records as $record):
                    ?>
                            <tr>
                                <td><?php echo $record['first_name'] . ' ' . $record['last_name']; ?></td>
                                <td><?php echo $record['date']; ?></td>
                                <td><?php echo $record['punch_in_time']; ?></td>
                                <td><?php echo $record['punch_out_time']; ?></td>
                            </tr>
                    <?php
                        endforeach;
                    else:
                    ?>
                        <tr>
                            <td colspan="4" class="text-center">No attendance records found for this date.</td>
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
