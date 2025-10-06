<?php
require_once '../config/config.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Check if employee_id is set in session
if (!isset($_SESSION['employee_id'])) {
    die("Error: Employee ID not found in session. Please contact HR.");
}

$employee_id = $_SESSION['employee_id'];
$today = date('Y-m-d');
$message = '';

// Check if user has already punched in today
$attendance = null;
try {
    $sql = "SELECT * FROM attendance WHERE employee_id = :employee_id AND date = :date";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':employee_id' => $employee_id, ':date' => $today]);
    $attendance = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Handle punch in/out
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['punch_in'])) {
        // Punch in
        $sql = "INSERT INTO attendance (employee_id, punch_in_time, date) VALUES (:employee_id, NOW(), :date)";
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([':employee_id' => $employee_id, ':date' => $today])) {
            $message = "<div class='alert alert-success'>Successfully punched in at " . date('h:i A') . "</div>";
        }
    } elseif (isset($_POST['punch_out'])) {
        // Punch out
        $sql = "UPDATE attendance SET punch_out_time = NOW() WHERE employee_id = :employee_id AND date = :date";
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([':employee_id' => $employee_id, ':date' => $today])) {
            $message = "<div class='alert alert-info'>Successfully punched out at " . date('h:i A') . "</div>";
        }
    }
    // Refresh attendance data
    $stmt = $pdo->prepare("SELECT * FROM attendance WHERE employee_id = :employee_id AND date = :date");
    $stmt->execute([':employee_id' => $employee_id, ':date' => $today]);
    $attendance = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get employee's attendance history (current month)
$current_month = date('Y-m');
$sql = "SELECT * FROM attendance 
        WHERE employee_id = :employee_id 
        AND DATE_FORMAT(date, '%Y-%m') = :current_month 
        ORDER BY date DESC";
$history_stmt = $pdo->prepare($sql);
$history_stmt->execute([':employee_id' => $employee_id, ':current_month' => $current_month]);
$attendance_history = $history_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Punch In/Out - Employee Portal</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <?php include 'templates/sidebar.php'; ?>
        
        <!-- Page Content -->
        <div id="content">
            <nav class="navbar navbar-expand-lg navbar-light bg-light mb-4">
                <div class="container-fluid">
                    <button type="button" id="sidebarCollapse" class="btn btn-info">
                        <i class="fas fa-bars"></i>
                        <span>Toggle Sidebar</span>
                    </button>
                </div>
            </nav>
            
            <div class="container">
                <h2 class="mb-4">Attendance</h2>
                
                <?php echo $message; ?>
                
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Today's Attendance</h5>
                    </div>
                    <div class="card-body text-center">
                        <?php if (empty($attendance) || $attendance['punch_in_time'] === null): ?>
                            <form action="punch_in_out.php" method="post">
                                <button type="submit" name="punch_in" class="btn btn-success btn-lg">
                                    <i class="fas fa-fingerprint"></i> Punch In
                                </button>
                            </form>
                        <?php elseif ($attendance['punch_out_time'] === null): ?>
                            <div class="mb-3">
                                <p>Punched in at: <?php echo date('h:i A', strtotime($attendance['punch_in_time'])); ?></p>
                                <form action="punch_in_out.php" method="post">
                                    <button type="submit" name="punch_out" class="btn btn-danger btn-lg">
                                        <i class="fas fa-sign-out-alt"></i> Punch Out
                                    </button>
                                </form>
                            </div>
                        <?php else: ?>
                            <p>You've completed your attendance for today.</p>
                            <p>Punched in: <?php echo date('h:i A', strtotime($attendance['punch_in_time'])); ?></p>
                            <p>Punched out: <?php echo date('h:i A', strtotime($attendance['punch_out_time'])); ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Attendance History (<?php echo date('F Y'); ?>)</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Punch In</th>
                                        <th>Punch Out</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($attendance_history)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center">No attendance records found for this month.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($attendance_history as $record): ?>
                                            <tr>
                                                <td><?php echo date('M d, Y', strtotime($record['date'])); ?></td>
                                                <td><?php echo $record['punch_in_time'] ? date('h:i A', strtotime($record['punch_in_time'])) : '--:--'; ?></td>
                                                <td><?php echo $record['punch_out_time'] ? date('h:i A', strtotime($record['punch_out_time'])) : '--:--'; ?></td>
                                                <td>
                                                    <?php if ($record['punch_in_time'] && $record['punch_out_time']): ?>
                                                        <span class="badge badge-success">Completed</span>
                                                    <?php elseif ($record['punch_in_time']): ?>
                                                        <span class="badge badge-warning">In Progress</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-danger">Absent</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery and Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
    <script>
        $(document).ready(function () {
            // Toggle sidebar
            $('#sidebarCollapse').on('click', function () {
                $('#sidebar').toggleClass('active');
            });
            
            // Auto-refresh the page every 5 minutes to update attendance status
            setTimeout(function() {
                location.reload();
            }, 300000); // 5 minutes
        });
    </script>
</body>
</html>
