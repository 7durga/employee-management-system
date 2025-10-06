<?php
require_once '../config/config.php';

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
}

$selected_month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
$start_date = $selected_month . '-01';
$end_date = date('Y-m-t', strtotime($start_date));

// Function to get attendance data
function getAttendanceData($pdo, $start_date, $end_date, $role = null) {
    $sql = "SELECT 
                e.employee_id, 
                e.first_name, 
                e.last_name, 
                DATE_FORMAT(a.date, '%Y-%m-%d') as date, 
                MIN(a.punch_in_time) as punch_in_time, 
                MAX(a.punch_out_time) as punch_out_time,
                u.role
            FROM attendance a
            JOIN employees e ON a.employee_id = e.employee_id
            JOIN users u ON e.employee_id = u.employee_id
            WHERE a.date BETWEEN :start_date AND :end_date" . 
            ($role ? " AND u.role = :role" : "") . "
            GROUP BY e.employee_id, a.date
            ORDER BY e.first_name, e.last_name, a.date";
    
    $stmt = $pdo->prepare($sql);
    $params = [':start_date' => $start_date, ':end_date' => $end_date];
    if ($role) {
        $params[':role'] = $role;
    }
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get data based on user role
$all_data = [];
$employees_data = [];
$hr_data = [];

if ($_SESSION['role'] === 'Super Admin') {
    $all_data = getAttendanceData($pdo, $start_date, $end_date);
    $employees_data = array_filter($all_data, function($row) {
        return $row['role'] === 'Employee';
    });
    $hr_data = array_filter($all_data, function($row) {
        return $row['role'] === 'HR Admin';
    });
} else if ($_SESSION['role'] === 'HR Admin') {
    $hr_data = getAttendanceData($pdo, $start_date, $end_date, 'HR Admin');
} else {
    $all_data = getAttendanceData($pdo, $start_date, $end_date, 'Employee');
    $employees_data = $all_data;
}

// Function to calculate working hours
function calculateWorkingHours($punch_in, $punch_out) {
    if (!$punch_in || !$punch_out) return 'N/A';
    
    $start = new DateTime($punch_in);
    $end = new DateTime($punch_out);
    $diff = $start->diff($end);
    
    return $diff->format('%H:%I');
}

// Function to get status badge
function getStatusBadge($punch_in, $punch_out) {
    if ($punch_in && $punch_out) {
        return '<span class="badge badge-success">Present</span>';
    } elseif ($punch_in) {
        return '<span class="badge badge-warning">Half Day</span>';
    } else {
        return '<span class="badge badge-danger">Absent</span>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Report - Employee Management System</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Custom CSS -->
    <link href="../assets/css/style.css" rel="stylesheet">
    
    <style>
        .card {
            border: none;
            border-radius: 0.5rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            margin-bottom: 1.5rem;
        }
        
        .card-header {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
            padding: 1rem 1.25rem;
            font-weight: 600;
            color: #4e73df;
        }
        
        .table th {
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.7rem;
            letter-spacing: 0.05em;
            color: #858796;
            background-color: #f8f9fc;
        }
        
        .nav-tabs {
            border-bottom: 2px solid #e3e6f0;
            margin-bottom: 1.5rem;
        }
        
        .nav-tabs .nav-link {
            border: none;
            color: #6e707e;
            font-weight: 600;
            padding: 0.75rem 1.25rem;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }
        
        .nav-tabs .nav-link.active {
            color: #4e73df;
            background-color: transparent;
            border-bottom: 3px solid #4e73df;
        }
        
        .nav-tabs .nav-link:hover {
            border-color: transparent;
            color: #4e73df;
        }
        
        .table-responsive {
            border-radius: 0.5rem;
            overflow: hidden;
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(78, 115, 223, 0.05);
        }
        
        .badge {
            padding: 0.4em 0.8em;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .badge-success {
            background-color: #1cc88a;
        }
        
        .badge-warning {
            background-color: #f6c23e;
            color: #1f2d3d;
        }
        
        .badge-danger {
            background-color: #e74a3b;
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
                </div>
            </nav>

            <div class="container-fluid">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3">
                    <h1 class="h3 mb-0">Attendance Report</h1>
                    <?php if ($_SESSION['role'] === 'Super Admin' || $_SESSION['role'] === 'HR Admin'): ?>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="export_attendance.php?month=<?php echo urlencode($selected_month); ?>" class="btn btn-sm btn-primary">
                                <i class="fas fa-file-export"></i> Export to Excel
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="get" class="row">
                            <div class="col-md-4 mb-3">
                                <label for="month" class="form-label">Select Month</label>
                                <div class="input-group">
                                    <input type="month" class="form-control" id="month" name="month" 
                                           value="<?php echo htmlspecialchars($selected_month); ?>" 
                                           max="<?php echo date('Y-m'); ?>">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i> Go
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Tabs -->
                <ul class="nav nav-tabs mb-3" id="attendanceTabs" role="tablist">
                    <?php if ($_SESSION['role'] === 'Super Admin' || $_SESSION['role'] === 'Employee'): ?>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?php echo ($_SESSION['role'] !== 'HR Admin') ? 'active' : ''; ?>" 
                                id="employees-tab" data-bs-toggle="tab" data-bs-target="#employees" 
                                type="button" role="tab" aria-controls="employees" aria-selected="true">
                            <i class="fas fa-users me-1"></i> Employees
                            <span class="badge bg-primary rounded-pill ms-1">
                                <?php echo count($employees_data); ?>
                            </span>
                        </button>
                    </li>
                    <?php endif; ?>
                    
                    <?php if ($_SESSION['role'] === 'Super Admin' || $_SESSION['role'] === 'HR Admin'): ?>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?php echo ($_SESSION['role'] === 'HR Admin') ? 'active' : ''; ?>" 
                                id="hr-tab" data-bs-toggle="tab" data-bs-target="#hr" 
                                type="button" role="tab" aria-controls="hr" aria-selected="false">
                            <i class="fas fa-user-tie me-1"></i> HR Admins
                            <span class="badge bg-primary rounded-pill ms-1">
                                <?php echo count($hr_data); ?>
                            </span>
                        </button>
                    </li>
                    <?php endif; ?>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content" id="attendanceTabsContent">
                    <!-- Employees Tab -->
                    <?php if ($_SESSION['role'] === 'Super Admin' || $_SESSION['role'] === 'Employee'): ?>
                    <div class="tab-pane fade <?php echo ($_SESSION['role'] !== 'HR Admin') ? 'show active' : ''; ?>" 
                         id="employees" role="tabpanel" aria-labelledby="employees-tab">
                        <?php if (!empty($employees_data)): ?>
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <span><i class="fas fa-users me-2"></i>Employee Attendance</span>
                                    <span class="badge bg-primary">
                                        <?php echo date('F Y', strtotime($selected_month)); ?>
                                    </span>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Employee ID</th>
                                                <th>Name</th>
                                                <th>Date</th>
                                                <th>Punch In</th>
                                                <th>Punch Out</th>
                                                <th>Working Hours</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            // Group by employee for better readability
                                            $grouped_data = [];
                                            foreach ($employees_data as $row) {
                                                $emp_id = $row['employee_id'];
                                                if (!isset($grouped_data[$emp_id])) {
                                                    $grouped_data[$emp_id] = [
                                                        'name' => $row['first_name'] . ' ' . $row['last_name'],
                                                        'records' => []
                                                    ];
                                                }
                                                $grouped_data[$emp_id]['records'][] = $row;
                                            }
                                            
                                            foreach ($grouped_data as $emp_id => $employee): 
                                                foreach ($employee['records'] as $index => $row):
                                            ?>
                                                <tr>
                                                    <?php if ($index === 0): ?>
                                                    <td rowspan="<?php echo count($employee['records']); ?>">
                                                        <?php echo htmlspecialchars($emp_id); ?>
                                                    </td>
                                                    <td rowspan="<?php echo count($employee['records']); ?>">
                                                        <?php echo htmlspecialchars($employee['name']); ?>
                                                    </td>
                                                    <?php endif; ?>
                                                    <td><?php echo date('M j, Y', strtotime($row['date'])); ?></td>
                                                    <td><?php echo $row['punch_in_time'] ? date('h:i A', strtotime($row['punch_in_time'])) : '-'; ?></td>
                                                    <td><?php echo $row['punch_out_time'] ? date('h:i A', strtotime($row['punch_out_time'])) : '-'; ?></td>
                                                    <td><?php echo calculateWorkingHours($row['punch_in_time'], $row['punch_out_time']); ?></td>
                                                    <td><?php echo getStatusBadge($row['punch_in_time'], $row['punch_out_time']); ?></td>
                                                </tr>
                                            <?php 
                                                endforeach;
                                            endforeach; 
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i> No attendance records found for employees in the selected month.
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <!-- HR Admins Tab -->
                    <?php if ($_SESSION['role'] === 'Super Admin' || $_SESSION['role'] === 'HR Admin'): ?>
                    <div class="tab-pane fade <?php echo ($_SESSION['role'] === 'HR Admin') ? 'show active' : ''; ?>" 
                         id="hr" role="tabpanel" aria-labelledby="hr-tab">
                        <?php if (!empty($hr_data)): ?>
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <span><i class="fas fa-user-tie me-2"></i>HR Admins Attendance</span>
                                    <span class="badge bg-primary">
                                        <?php echo date('F Y', strtotime($selected_month)); ?>
                                    </span>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Employee ID</th>
                                                <th>Name</th>
                                                <th>Date</th>
                                                <th>Punch In</th>
                                                <th>Punch Out</th>
                                                <th>Working Hours</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            // Group by HR admin for better readability
                                            $grouped_hr = [];
                                            foreach ($hr_data as $row) {
                                                $emp_id = $row['employee_id'];
                                                if (!isset($grouped_hr[$emp_id])) {
                                                    $grouped_hr[$emp_id] = [
                                                        'name' => $row['first_name'] . ' ' . $row['last_name'],
                                                        'records' => []
                                                    ];
                                                }
                                                $grouped_hr[$emp_id]['records'][] = $row;
                                            }
                                            
                                            foreach ($grouped_hr as $emp_id => $hr): 
                                                foreach ($hr['records'] as $index => $row):
                                            ?>
                                                <tr>
                                                    <?php if ($index === 0): ?>
                                                    <td rowspan="<?php echo count($hr['records']); ?>">
                                                        <?php echo htmlspecialchars($emp_id); ?>
                                                    </td>
                                                    <td rowspan="<?php echo count($hr['records']); ?>">
                                                        <?php echo htmlspecialchars($hr['name']); ?>
                                                    </td>
                                                    <?php endif; ?>
                                                    <td><?php echo date('M j, Y', strtotime($row['date'])); ?></td>
                                                    <td><?php echo $row['punch_in_time'] ? date('h:i A', strtotime($row['punch_in_time'])) : '-'; ?></td>
                                                    <td><?php echo $row['punch_out_time'] ? date('h:i A', strtotime($row['punch_out_time'])) : '-'; ?></td>
                                                    <td><?php echo calculateWorkingHours($row['punch_in_time'], $row['punch_out_time']); ?></td>
                                                    <td><?php echo getStatusBadge($row['punch_in_time'], $row['punch_out_time']); ?></td>
                                                </tr>
                                            <?php 
                                                endforeach;
                                            endforeach; 
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i> No attendance records found for HR admins in the selected month.
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery, Popper.js, Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.10.2/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom Scripts -->
    <script>
        $(document).ready(function() {
            // Toggle sidebar
            $('#sidebarCollapse').on('click', function() {
                $('#sidebar').toggleClass('active');
            });
            
            // Remember active tab
            const activeTab = localStorage.getItem('activeTab');
            if (activeTab) {
                const tab = new bootstrap.Tab(document.querySelector(activeTab));
                tab.show();
            }
            
            // Save active tab on change
            $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
                localStorage.setItem('activeTab', e.target.getAttribute('data-bs-target'));
            });
            
            // Initialize tooltips
            $('[data-bs-toggle="tooltip"]').tooltip();
        });
    </script>
</body>
</html>