<?php
require_once '../config/config.php';

// Check if the user is logged in and is an HR Admin
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'HR Admin') {
    header("location: login.php");
    exit;
}

// Get employee statistics
$employee_stats = $pdo->query("
    SELECT 
        COUNT(*) as total_employees,
        COUNT(*) as active_employees,
        0 as inactive_employees
    FROM employees
")->fetch(PDO::FETCH_ASSOC);

// Get task statistics
$task_stats = $pdo->query("
    SELECT 
        COUNT(*) as total_tasks,
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_tasks,
        SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) as in_progress_tasks,
        SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed_tasks
    FROM tasks
    WHERE due_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
")->fetch(PDO::FETCH_ASSOC);

$employee_count = $employee_stats['total_employees'] ?? 0;
$active_tasks = $task_stats['total_tasks'] - ($task_stats['completed_tasks'] ?? 0);

// Get recent tasks
$recent_activities = $pdo->query("
    SELECT 'task' as type, 
           CONCAT('Task: ', title) as description, 
           due_date as activity_date,
           CASE 
               WHEN status = 'Completed' THEN 'success'
               WHEN due_date < CURDATE() THEN 'danger'
               ELSE 'primary' 
           END as color
    FROM tasks 
    WHERE due_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    ORDER BY due_date DESC 
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Add some system activities
$system_activities = [
    [
        'type' => 'system',
        'description' => 'System: Welcome to your dashboard',
        'created_at' => date('Y-m-d H:i:s'),
        'color' => 'info'
    ]
];

// Merge activities
$recent_activities = array_merge($recent_activities, $system_activities);

$page_title = "HR Admin Dashboard";
include 'templates/header.php';
?>

<div class="wrapper">
    <?php include 'templates/sidebar.php'; ?>

    <div id="content">
        <!-- Top Navigation -->
        <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
            <div class="container-fluid">
                <button type="button" id="sidebarCollapse" class="btn btn-outline-secondary">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="d-flex align-items-center">
                    <h4 class="mb-0 ms-3">HR Admin Dashboard</h4>
                </div>
            </div>
        </nav>

        <!-- Dashboard Content -->
        <div class="container-fluid py-4">
            <!-- Stats Cards -->
            <div class="row g-4 mb-4">
                <!-- Employees Card -->
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm hover-3d">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="bg-primary bg-opacity-10 p-3 rounded-3">
                                    <i class="fas fa-users fa-2x text-primary"></i>
                                </div>
                                <div class="text-end">
                                    <h2 class="mb-0"><?php echo $employee_count; ?></h2>
                                    <span class="text-muted">Total Employees</span>
                                    <div class="small mt-1">
                                        <span class="text-success"><?php echo $employee_stats['active_employees'] ?? 0; ?> active</span> • 
                                        <span class="text-secondary"><?php echo $employee_stats['inactive_employees'] ?? 0; ?> inactive</span>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <a href="hr_manage_employees.php" class="btn btn-sm btn-outline-primary rounded-pill">Manage Employees</a>
                                <a href="add_employee.php" class="btn btn-sm btn-primary rounded-pill">
                                    <i class="fas fa-plus me-1"></i> Add New
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tasks Card -->
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm hover-3d">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="bg-warning bg-opacity-10 p-3 rounded-3">
                                    <i class="fas fa-tasks fa-2x text-warning"></i>
                                </div>
                                <div class="text-end">
                                    <h2 class="mb-0"><?php echo $active_tasks; ?></h2>
                                    <span class="text-muted">Active Tasks</span>
                                    <div class="small mt-1">
                                        <span class="text-warning"><?php echo $task_stats['pending_tasks'] ?? 0; ?> pending</span> • 
                                        <span class="text-info"><?php echo $task_stats['in_progress_tasks'] ?? 0; ?> in progress</span>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <a href="manage_tasks.php" class="btn btn-sm btn-outline-warning rounded-pill">View All Tasks</a>
                                <a href="assign_task.php" class="btn btn-sm btn-warning text-white rounded-pill">
                                    <i class="fas fa-plus me-1"></i> New Task
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions Card -->
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm hover-3d">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="bg-success bg-opacity-10 p-3 rounded-3">
                                    <i class="fas fa-bolt fa-2x text-success"></i>
                                </div>
                                <div class="text-end">
                                    <h2 class="mb-0">Quick Actions</h2>
                                    <span class="text-muted">Common tasks</span>
                                </div>
                            </div>
                            <div class="d-grid gap-2">
                                <a href="assign_task.php" class="btn btn-sm btn-outline-success rounded-pill mb-2">
                                    <i class="fas fa-plus-circle me-1"></i> New Task
                                </a>
                                <a href="hr_manage_employees.php" class="btn btn-sm btn-outline-primary rounded-pill">
                                    <i class="fas fa-user-plus me-1"></i> Add Employee
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activities -->
            <div class="row">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-0">
                            <h5 class="mb-0">Recent Activities</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush">
                                <?php if (empty($recent_activities)): ?>
                                    <div class="text-center p-4 text-muted">
                                        No recent activities found.
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($recent_activities as $activity): ?>
                                        <div class="list-group-item border-0 py-3">
                                            <div class="d-flex align-items-center">
                                                <div class="flex-shrink-0 me-3">
                                                    <?php 
                                                    $icon = 'tasks';
                                                    $color = $activity['color'] ?? 'primary';
                                                    $bgClass = "bg-{$color} bg-opacity-10";
                                                    $textClass = "text-{$color}";
                                                    
                                                    switch($activity['type']) {
                                                        case 'system':
                                                            $icon = 'info-circle';
                                                            break;
                                                        case 'task':
                                                        default:
                                                            $icon = 'tasks';
                                                    }
                                                    ?>
                                                    <div class="<?php echo $bgClass; ?> p-2 rounded-3">
                                                        <i class="fas fa-<?php echo $icon; ?> <?php echo $textClass; ?>"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($activity['description']); ?></h6>
                                                    <small class="text-muted">
                                                        <i class="far fa-clock me-1"></i>
                                                        <?php 
                                                        $activityDate = $activity['activity_date'] ?? $activity['created_at'] ?? date('Y-m-d H:i:s');
                                                        echo date('M d, Y h:i A', strtotime($activityDate)); 
                                                        ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* 3D Card Effects */
    .hover-3d {
        position: relative;
        transition: all 0.3s ease;
        transform-style: preserve-3d;
        transform: perspective(1000px) rotateY(0) rotateX(0) translateZ(0);
        backface-visibility: hidden;
        box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.1);
    }
    
    .hover-3d:hover {
        transform: perspective(1000px) rotateY(2deg) rotateX(1deg) translateZ(15px);
        box-shadow: 0 1rem 3rem rgba(0, 0, 0, 0.2) !important;
    }
    
    /* Card Styling */
    .card {
        border-radius: 1rem;
        overflow: hidden;
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        border: none;
        background: linear-gradient(145deg, #ffffff, #f5f7fa);
    }
    
    .card-header {
        font-weight: 700;
        padding: 1.5rem;
        background: transparent;
        border-bottom: 1px solid rgba(0,0,0,0.05);
        letter-spacing: 0.5px;
    }
    
    /* Enhanced Opacity */
    .bg-opacity-10 {
        opacity: 0.15;
    }
    
    /* Button Styling */
    .btn {
        font-weight: 500;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        font-size: 0.75rem;
        padding: 0.5rem 1.25rem;
        transition: all 0.3s ease;
    }
    
    .btn-outline-primary {
        border-width: 2px;
    }
    
    /* Hover Effects */
    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    
    /* Animation */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .card {
        animation: fadeInUp 0.6s ease-out forwards;
    }
    
    /* Delay animations for each card */
    .card:nth-child(2) { animation-delay: 0.1s; }
    .card:nth-child(3) { animation-delay: 0.2s; }
</style>

<?php include 'templates/footer.php'; ?>