<?php
require_once '../config/config.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || ($_SESSION["role"] !== 'HR Admin' && $_SESSION["role"] !== 'Super Admin')) {
    header("location: login.php");
    exit;
}

// Set default date range (current month)
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
$employee_id = isset($_GET['employee_id']) ? $_GET['employee_id'] : '';

// Build the query
$sql = "SELECT a.*, e.first_name, e.last_name, e.employee_id as emp_id 
        FROM attendance a 
        JOIN employees e ON a.employee_id = e.employee_id 
        WHERE a.date BETWEEN :start_date AND :end_date";

$params = [':start_date' => $start_date, ':end_date' => $end_date];

if (!empty($employee_id)) {
    $sql .= " AND a.employee_id = :employee_id";
    $params[':employee_id'] = $employee_id;
}

$sql .= " ORDER BY a.date DESC, a.punch_in_time DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$attendance_records = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all employees for filter
$employees = $pdo->query("SELECT employee_id, CONCAT(first_name, ' ', last_name) as name FROM employees ORDER BY first_name, last_name")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Attendance - HR Admin</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.24/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        .status-present {
            background-color: #d4edda;
            color: #155724;
        }
        .status-absent {
            background-color: #f8d7da;
            color: #721c24;
        }
        .status-partial {
            background-color: #fff3cd;
            color: #856404;
        }
        .attendance-table th {
            white-space: nowrap;
        }
    </style>
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
                <h2 class="mb-4">Employee Attendance</h2>
                
                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Filters</h5>
                    </div>
                    <div class="card-body">
                        <form method="get" class="row">
                            <div class="form-group col-md-4">
                                <label>Start Date</label>
                                <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
                            </div>
                            <div class="form-group col-md-4">
                                <label>End Date</label>
                                <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
                            </div>
                            <div class="form-group col-md-3">
                                <label>Employee</label>
                                <select name="employee_id" class="form-control">
                                    <option value="">All Employees</option>
                                    <?php foreach ($employees as $emp): ?>
                                        <option value="<?php echo $emp['employee_id']; ?>" <?php echo ($employee_id == $emp['employee_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($emp['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group col-md-1 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">Filter</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Attendance Summary -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Employees</h5>
                                <h2 class="mb-0"><?php echo count($employees); ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title">Present Today</h5>
                                <?php
                                $today = date('Y-m-d');
                                $stmt = $pdo->prepare("SELECT COUNT(DISTINCT employee_id) as count FROM attendance WHERE date = :today");
                                $stmt->execute([':today' => $today]);
                                $present_today = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                                ?>
                                <h2 class="mb-0"><?php echo $present_today; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-warning text-dark">
                            <div class="card-body">
                                <h5 class="card-title">Late Today</h5>
                                <?php
                                $stmt = $pdo->prepare("SELECT COUNT(DISTINCT a.employee_id) as count 
                                                     FROM attendance a 
                                                     WHERE a.date = :today 
                                                     AND TIME(a.punch_in_time) > '10:00:00'");
                                $stmt->execute([':today' => $today]);
                                $late_today = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                                ?>
                                <h2 class="mb-0"><?php echo $late_today; ?></h2>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Attendance Table -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Attendance Records</h5>
                        <a href="export_attendance.php?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&employee_id=<?php echo $employee_id; ?>" class="btn btn-sm btn-success">
                            <i class="fas fa-file-export"></i> Export to Excel
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="attendanceTable" class="table table-striped table-bordered" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Employee</th>
                                        <th>Employee ID</th>
                                        <th>Punch In</th>
                                        <th>Punch Out</th>
                                        <th>Working Hours</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($attendance_records as $record): 
                                        $status = '';
                                        $status_class = '';
                                        
                                        if ($record['punch_in_time'] && $record['punch_out_time']) {
                                            $status = 'Present';
                                            $status_class = 'status-present';
                                            
                                            // Calculate working hours
                                            $punch_in = new DateTime($record['punch_in_time']);
                                            $punch_out = new DateTime($record['punch_out_time']);
                                            $interval = $punch_in->diff($punch_out);
                                            $working_hours = $interval->format('%H:%I');
                                        } elseif ($record['punch_in_time']) {
                                            $status = 'Incomplete';
                                            $status_class = 'status-partial';
                                            $working_hours = '--:--';
                                        } else {
                                            $status = 'Absent';
                                            $status_class = 'status-absent';
                                            $working_hours = '--:--';
                                        }
                                    ?>
                                        <tr>
                                            <td><?php echo date('M d, Y', strtotime($record['date'])); ?></td>
                                            <td><?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?></td>
                                            <td><?php echo htmlspecialchars($record['emp_id']); ?></td>
                                            <td><?php echo $record['punch_in_time'] ? date('h:i A', strtotime($record['punch_in_time'])) : '--:--'; ?></td>
                                            <td><?php echo $record['punch_out_time'] ? date('h:i A', strtotime($record['punch_out_time'])) : '--:--'; ?></td>
                                            <td><?php echo $working_hours; ?></td>
                                            <td><span class="status-badge <?php echo $status_class; ?>"><?php echo $status; ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
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
    <script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.24/js/dataTables.bootstrap4.min.js"></script>
    
    <script>
        $(document).ready(function () {
            // Toggle sidebar
            $('#sidebarCollapse').on('click', function () {
                $('#sidebar').toggleClass('active');
            });
            
            // Initialize DataTable
            $('#attendanceTable').DataTable({
                "order": [[0, "desc"], [1, "asc"]],
                "pageLength": 25,
                "responsive": true
            });
        });
    </script>
</body>
</html>
