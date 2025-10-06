<?php
session_start();
require_once '../config/config.php';

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'Employee'){
    header("location: login.php");
    exit;
}

// Get employee_id from session
$user_id = $_SESSION["id"];
$sql = "SELECT employee_id FROM users WHERE id = :id";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$employee_id = $user['employee_id'];
$_SESSION['employee_id'] = $employee_id;

// Check current day's attendance
$today = date("Y-m-d");
$sql = "SELECT * FROM attendance WHERE employee_id = :employee_id AND date = :today";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':employee_id', $employee_id, PDO::PARAM_STR);
$stmt->bindParam(':today', $today, PDO::PARAM_STR);
$stmt->execute();
$attendance = $stmt->fetch(PDO::FETCH_ASSOC);

$punched_in = false;
$punched_out = false;
if($attendance){
    if(!empty($attendance['punch_in_time'])){
        $punched_in = true;
    }
    if(!empty($attendance['punch_out_time'])){
        $punched_out = true;
    }
}

// Fetch assigned tasks for the employee
$sql_tasks = "SELECT t.*, CONCAT(e.first_name, ' ', e.last_name) as assigned_by_name 
             FROM tasks t 
             LEFT JOIN users u ON t.assigned_by = u.id 
             LEFT JOIN employees e ON u.employee_id = e.employee_id 
             WHERE t.assigned_to = :employee_id 
             ORDER BY 
                 CASE 
                     WHEN t.status = 'In Progress' THEN 1 
                     WHEN t.status = 'Pending' THEN 2 
                     ELSE 3 
                 END,
                 t.due_date ASC";
$stmt_tasks = $pdo->prepare($sql_tasks);
$stmt_tasks->bindParam(':employee_id', $employee_id, PDO::PARAM_STR);
$stmt_tasks->execute();
$tasks = $stmt_tasks->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Employee Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4cc9f0;
            --light-bg: #f8f9ff;
            --card-radius: 15px;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7ff 0%, #e9ecff 100%);
            min-height: 100vh;
            color: #2b2d42;
        }
        
        .wrapper {
            display: flex;
            min-height: 100vh;
        }
        
        #content {
            width: 100%;
            padding: 2rem;
            transition: all 0.3s;
        }
        
        .card {
            border: none;
            border-radius: var(--card-radius);
            box-shadow: 0 10px 30px rgba(67, 97, 238, 0.1);
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            overflow: hidden;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(67, 97, 238, 0.2);
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-bottom: none;
            padding: 1.25rem 1.5rem;
            font-weight: 600;
            border-radius: var(--card-radius) var(--card-radius) 0 0 !important;
        }
        
        .btn {
            border-radius: 8px;
            font-weight: 500;
            padding: 0.5rem 1.25rem;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            box-shadow: 0 4px 15px rgba(67, 97, 238, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 7px 20px rgba(67, 97, 238, 0.4);
        }
        
        .btn-info {
            background: linear-gradient(135deg, #4cc9f0, #4895ef);
            border: none;
            box-shadow: 0 4px 15px rgba(76, 201, 240, 0.3);
        }
        
        .btn-info:hover {
            transform: translateY(-2px);
            box-shadow: 0 7px 20px rgba(76, 201, 240, 0.4);
        }
        
        .table {
            background: white;
            border-radius: var(--card-radius);
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        .table th {
            background: #f8f9ff;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            color: #5e72e4;
            border-bottom: 2px solid #e9ecef;
        }
        
        .table td {
            vertical-align: middle;
            border-color: #f8f9ff;
        }
        
        .status-badge {
            padding: 0.35rem 0.75rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: capitalize;
        }
        
        .status-Pending {
            background-color: rgba(255, 193, 7, 0.1);
            color: #ffc107;
        }
        
        .status-In-Progress {
            background-color: rgba(13, 110, 253, 0.1);
            color: #0d6efd;
        }
        
        .status-Completed {
            background-color: rgba(25, 135, 84, 0.1);
            color: #198754;
        }
        
        .page-title {
            color: #2b2d42;
            font-weight: 700;
            margin-bottom: 1.5rem;
            position: relative;
            display: inline-block;
        }
        
        .page-title:after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 50px;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
            border-radius: 2px;
        }
        
        .form-control, .form-select {
            border-radius: 8px;
            padding: 0.5rem 1rem;
            border: 1px solid #e0e0ff;
            transition: all 0.3s;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.15);
        }
        
        /* 3D Card Effect */
        .card-3d {
            position: relative;
            transform-style: preserve-3d;
            perspective: 1000px;
        }
        
        .card-3d .card-body {
            position: relative;
            z-index: 1;
            transform: translateZ(20px);
        }
        
        .card-3d:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.3) 0%, rgba(255,255,255,0) 100%);
            border-radius: var(--card-radius);
            z-index: 0;
        }
        
        /* Stats Cards */
        .stat-card {
            padding: 1.5rem;
            text-align: center;
            border-radius: var(--card-radius);
            color: white;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }
        
        .stat-card:after {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 60%);
            z-index: -1;
        }
        
        .stat-card i {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            opacity: 0.9;
        }
        
        .stat-card .stat-value {
            font-size: 2rem;
            font-weight: 700;
            margin: 0.5rem 0;
        }
        
        .stat-card .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        /* Animation */
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }
        
        .floating {
            animation: float 6s ease-in-out infinite;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            #content {
                padding: 1rem;
            }
            
            .card {
                margin-bottom: 1.5rem;
            }
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

            <h1 class="page-title">Employee Dashboard</h1>
            <p>Welcome, <strong><?php echo htmlspecialchars($_SESSION["username"]); ?></strong>. Manage your attendance and tasks here.</p>
            
            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-4 mb-4">
                    <div class="card stat-card" style="background: linear-gradient(135deg, #4e54c8, #8f94fb);">
                        <div class="card-body">
                            <i class="fas fa-tasks"></i>
                            <div class="stat-value"><?php echo count($tasks); ?></div>
                            <div class="stat-label">Total Tasks</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card stat-card" style="background: linear-gradient(135deg, #11998e, #38ef7d);">
                        <div class="card-body">
                            <i class="fas fa-check-circle"></i>
                            <div class="stat-value"><?php echo count(array_filter($tasks, function($t) { return $t['status'] === 'Completed'; })); ?></div>
                            <div class="stat-label">Completed</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card stat-card" style="background: linear-gradient(135deg, #f46b45, #eea849);">
                        <div class="card-body">
                            <i class="fas fa-clock"></i>
                            <div class="stat-value"><?php echo count(array_filter($tasks, function($t) { return $t['status'] === 'In Progress'; })); ?></div>
                            <div class="stat-label">In Progress</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Cards -->
            <div class="row">
                <div class="col-lg-6 mb-4">
                    <div class="card card-3d h-100">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-calendar-check me-2"></i>Today's Attendance</h5>
                        </div>
                        <div class="card-body d-flex flex-column justify-content-center">
                            <?php if($punched_in): ?>
                                <div class="text-center mb-4">
                                    <i class="fas fa-fingerprint text-primary mb-3" style="font-size: 2.5rem;"></i>
                                    <p class="text-success mb-2"><i class="fas fa-sign-in-alt me-2"></i>Punched in at: <strong><?php echo date('h:i A', strtotime($attendance['punch_in_time'])); ?></strong></p>
                                    <?php if($punched_out): ?>
                                        <p class="text-info mb-3"><i class="fas fa-sign-out-alt me-2"></i>Punched out at: <strong><?php echo date('h:i A', strtotime($attendance['punch_out_time'])); ?></strong></p>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <form action="handle_attendance.php" method="post" class="text-center">
                                <?php if(!$punched_in): ?>
                                    <button type="submit" name="punch_in" class="btn btn-primary btn-lg">
                                        <i class="fas fa-sign-in-alt me-2"></i>Punch In
                                    </button>
                                <?php elseif(!$punched_out): ?>
                                    <button type="submit" name="punch_out" class="btn btn-warning btn-lg">
                                        <i class="fas fa-sign-out-alt me-2"></i>Punch Out
                                    </button>
                                <?php else: ?>
                                    <div class="alert alert-success mb-0">
                                        <i class="fas fa-check-circle me-2"></i>Your attendance for today is complete.
                                    </div>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 mb-4">
                    <div class="card card-3d h-100">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-tasks me-2"></i>Quick Actions</h5>
                        </div>
                        <div class="card-body d-flex flex-column justify-content-center">
                            <div class="text-center mb-4">
                                <i class="fas fa-clipboard-list text-primary mb-3 floating" style="font-size: 2.5rem;"></i>
                                <p class="mb-4">You have <strong><?php echo count($tasks); ?> task(s)</strong> assigned to you.</p>
                            </div>
                            <div class="d-grid gap-3">
                                <a href="#tasks-section" class="btn btn-primary btn-lg" id="viewAllTasksBtn">
                                    <i class="fas fa-tasks me-2"></i>View All Tasks
                                </a>
                                <a href="attendance_report.php" class="btn btn-info btn-lg">
                                    <i class="fas fa-calendar-alt me-2"></i>View Attendance Report
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="tasks-section" class="mt-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3><i class="fas fa-tasks me-2"></i>Your Tasks</h3>
                    <div>
                        <a href="#" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-sync-alt me-1"></i> Refresh
                        </a>
                    </div>
                </div>
                
                <div class="card">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th><i class="fas fa-heading me-2"></i>Title</th>
                                    <th><i class="fas fa-align-left me-2"></i>Description</th>
                                    <th><i class="far fa-calendar-alt me-2"></i>Due Date</th>
                                    <th><i class="fas fa-tag me-2"></i>Status</th>
                                    <th><i class="fas fa-cog me-2"></i>Action</th>
                                </tr>
                            </thead>
                    <tbody>
                        <?php if(!empty($tasks)):
                            foreach($tasks as $task):
                                $due_date = new DateTime($task['due_date']);
                                $today = new DateTime();
                                $interval = $today->diff($due_date);
                                $days_remaining = $interval->format('%r%a');
                                $is_overdue = $days_remaining < 0 && $task['status'] !== 'Completed';
                                $is_due_soon = $days_remaining >= 0 && $days_remaining <= 3 && $task['status'] !== 'Completed';
                        ?>
                                <tr class="<?php echo $is_overdue ? 'table-danger' : ($is_due_soon ? 'table-warning' : ''); ?>">
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="me-2">
                                                <i class="fas fa-tasks text-primary"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold"><?php echo htmlspecialchars($task['title']); ?></div>
                                                <?php if(isset($task['assigned_by_name'])): ?>
                                                    <small class="text-muted">Assigned by: <?php echo htmlspecialchars($task['assigned_by_name']); ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-muted"><?php echo !empty($task['description']) ? htmlspecialchars(substr($task['description'], 0, 50)) . (strlen($task['description']) > 50 ? '...' : '') : 'No description'; ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="far fa-calendar-alt me-2 text-muted"></i>
                                            <div>
                                                <div><?php echo date('M j, Y', strtotime($task['due_date'])); ?></div>
                                                <small class="text-muted">
                                                    <?php 
                                                    if($task['status'] === 'Completed') {
                                                        echo '<span class="text-success"><i class="fas fa-check-circle"></i> Completed</span>';
                                                    } elseif($is_overdue) {
                                                        echo '<span class="text-danger"><i class="fas fa-exclamation-circle"></i> Overdue by '.abs($days_remaining).' day'.(abs($days_remaining) != 1 ? 's' : '').'</span>';
                                                    } elseif($is_due_soon) {
                                                        echo '<span class="text-warning"><i class="fas fa-clock"></i> Due in '.$days_remaining.' day'.($days_remaining != 1 ? 's' : '').'</span>';
                                                    } else {
                                                        echo '<span class="text-muted"><i class="far fa-clock"></i> '.$days_remaining.' day'.($days_remaining != 1 ? 's' : '').' remaining</span>';
                                                    }
                                                    ?>
                                                </small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo str_replace(' ', '-', $task['status']); ?>">
                                            <i class="fas fa-circle me-1" style="font-size: 0.5rem;"></i>
                                            <?php echo $task['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form action="update_task_status.php" method="post" class="d-flex">
                                            <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width: 140px;">
                                                <option value="Pending" <?php if($task['status'] == 'Pending') echo 'selected'; ?>>⏳ Pending</option>
                                                <option value="In Progress" <?php if($task['status'] == 'In Progress') echo 'selected'; ?>>🚧 In Progress</option>
                                                <option value="Completed" <?php if($task['status'] == 'Completed') echo 'selected'; ?>>✅ Completed</option>
                                            </select>
                                            <a href="#" class="btn btn-sm btn-outline-info ms-2" data-bs-toggle="tooltip" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </form>
                                    </td>
                                </tr>
                        <?php 
                            endforeach;
                        else:
                        ?>
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="fas fa-inbox fa-3x mb-3"></i>
                                        <p class="mb-0">No tasks assigned to you yet.</p>
                                        <small>Check back later or contact your manager if you're expecting tasks.</small>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom Scripts -->
    <script>
        // Smooth scroll to tasks section when clicking View All Tasks
        document.getElementById('viewAllTasksBtn').addEventListener('click', function(e) {
            e.preventDefault();
            const tasksSection = document.getElementById('tasks-section');
            tasksSection.scrollIntoView({ behavior: 'smooth' });
            
            // Add highlight effect
            tasksSection.style.transition = 'box-shadow 0.5s ease';
            tasksSection.style.boxShadow = '0 0 0 5px rgba(67, 97, 238, 0.3)';
            
            // Remove highlight after animation
            setTimeout(() => {
                tasksSection.style.boxShadow = '0 0 0 0 rgba(67, 97, 238, 0)';
            }, 1500);
        });
    </script>
    <script>
        // Enable tooltips
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Toggle sidebar
            document.getElementById('sidebarCollapse').addEventListener('click', function() {
                document.getElementById('sidebar').classList.toggle('active');
            });
            
            // Add animation to cards on scroll
            const animateOnScroll = function() {
                const cards = document.querySelectorAll('.card-3d');
                cards.forEach(card => {
                    const cardPosition = card.getBoundingClientRect().top;
                    const screenPosition = window.innerHeight / 1.3;
                    
                    if (cardPosition < screenPosition) {
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    }
                });
            };
            
            // Initial check
            animateOnScroll();
            
            // Check on scroll
            window.addEventListener('scroll', animateOnScroll);
        });
        
        // Auto-hide alerts after 5 seconds
        window.setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const fadeOut = () => {
                    alert.style.opacity = '0';
                    setTimeout(() => alert.style.display = 'none', 300);
                };
                setTimeout(fadeOut, 5000);
            });
        }, 1000);
    </script>
</body>
</html>
