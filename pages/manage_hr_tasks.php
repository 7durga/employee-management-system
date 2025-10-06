<?php
require_once '../config/config.php';

// Check if the user is logged in and is a Super Admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'Super Admin'){
    header("location: login.php");
    exit;
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$assigned_to = isset($_GET['assigned_to']) ? $_GET['assigned_to'] : '';

// Fetch all HR Admins with their names
$sql = "SELECT u.id, u.username, e.first_name, e.last_name 
        FROM users u 
        JOIN employees e ON u.employee_id = e.employee_id 
        WHERE u.role = 'HR Admin'
        ORDER BY e.first_name, e.last_name";
$hr_admins = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// Handle task creation
$errors = [];
$success = '';

if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validate required fields
    $required_fields = ['title', 'description', 'assigned_to', 'due_date'];
    foreach($required_fields as $field){
        if(empty(trim($_POST[$field]))){
            $errors[$field] = "This field is required.";
        }
    }

    // Validate due date is in the future
    if(empty($errors['due_date']) && strtotime($_POST['due_date']) < strtotime('today')){
        $errors['due_date'] = "Due date must be in the future.";
    }

    if(empty($errors)){
        try {
            $pdo->beginTransaction();
            
            $sql = "INSERT INTO tasks (title, description, assigned_to, assigned_by, due_date, status) 
                    VALUES (:title, :description, :assigned_to, :assigned_by, :due_date, 'Pending')";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':title' => trim($_POST['title']),
                ':description' => trim($_POST['description']),
                ':assigned_to' => $_POST['assigned_to'],
                ':assigned_by' => $_SESSION['id'],
                ':due_date' => $_POST['due_date']
            ]);
            
            $pdo->commit();
            $success = 'Task has been assigned successfully!';
            
            // Clear form
            $_POST = [];
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors['database'] = "Error: " . $e->getMessage();
        }
    }
}

// Build the tasks query
$sql = "SELECT 
            t.id, 
            t.title, 
            t.description,
            t.status, 
            t.due_date, 
            t.assigned_to,
            t.assigned_by,
            CONCAT(e.first_name, ' ', e.last_name) as assignee_name,
            CONCAT(e2.first_name, ' ', e2.last_name) as assigner_name
        FROM tasks t
        JOIN users u ON t.assigned_to = u.id
        JOIN employees e ON u.employee_id = e.employee_id
        LEFT JOIN users u2 ON t.assigned_by = u2.id
        LEFT JOIN employees e2 ON u2.employee_id = e2.employee_id
        WHERE 1=1";

$params = [];

// Add status filter if selected
if (!empty($status_filter)) {
    $sql .= " AND t.status = :status";
    $params[':status'] = $status_filter;
}

// Add assigned to filter if selected
if (!empty($assigned_to)) {
    $sql .= " AND t.assigned_to = :assigned_to";
    $params[':assigned_to'] = $assigned_to;
}

// Add sorting
$sql .= " ORDER BY t.due_date ASC, t.status, assignee_name";

// Execute the query
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage HR Tasks - Super Admin</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 500;
            text-align: center;
            min-width: 100px;
            display: inline-block;
        }
        .status-Pending { background-color: #6c757d; color: white; }
        .status-In-Progress { background-color: #ffc107; color: #212529; }
        .status-Completed { background-color: #28a745; color: white; }
        .due-soon { color: #fd7e14; font-weight: 500; }
        .overdue { color: #dc3545; font-weight: 600; }
        .card { border: none; box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075); }
        .card-header { background-color: #f8f9fa; border-bottom: 1px solid #e3e6f0; }
        .avatar-sm {
            width: 32px;
            height: 32px;
            line-height: 32px;
            font-size: 0.875rem;
            background-color: #e9ecef;
            color: #6c757d;
            font-weight: 600;
            border-radius: 50%;
            text-align: center;
            display: inline-block;
            margin-right: 10px;
        }
        .task-title {
            font-weight: 500;
            color: #2c3e50;
            margin-bottom: 0.25rem;
        }
        .task-description {
            font-size: 0.875rem;
            color: #6c757d;
            margin-bottom: 0;
        }
        .nav-tabs .nav-link {
            border: none;
            color: #6c757d;
            font-weight: 500;
            padding: 0.75rem 1.25rem;
            margin-right: 5px;
        }
        .nav-tabs .nav-link.active {
            color: #007bff;
            background-color: transparent;
            border-bottom: 2px solid #007bff;
        }
        .nav-tabs {
            border-bottom: 1px solid #dee2e6;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
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
                        <h5 class="mb-0 d-none d-md-block">HR Task Management</h5>
                    </div>
                    <div class="ml-auto d-flex">
                        <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addTaskModal">
                            <i class="fas fa-plus"></i> Assign New Task
                        </button>
                    </div>
                </div>
            </nav>

            <!-- Main Content -->
            <div class="container-fluid py-4">
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $success; ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errors['database'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $errors['database']; ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>

                <!-- Filters Card -->
                <div class="card mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0"><i class="fas fa-filter text-primary mr-2"></i>Filter Tasks</h5>
                    </div>
                    <div class="card-body">
                        <form method="get" class="row g-3">
                            <div class="col-md-4">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-control" id="status" name="status">
                                    <option value="">All Statuses</option>
                                    <option value="Pending" <?php echo $status_filter === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="In Progress" <?php echo $status_filter === 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                                    <option value="Completed" <?php echo $status_filter === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="assigned_to" class="form-label">Assigned To</label>
                                <select class="form-control" id="assigned_to" name="assigned_to">
                                    <option value="">All HR Admins</option>
                                    <?php foreach($hr_admins as $admin): ?>
                                        <option value="<?php echo $admin['id']; ?>" 
                                            <?php echo $assigned_to == $admin['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="fas fa-search me-1"></i> Apply Filters
                                </button>
                                <a href="manage_hr_tasks.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-sync-alt me-1"></i> Reset
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Tasks Table -->
                <div class="card">
                    <div class="card-header bg-white py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-tasks text-primary mr-2"></i>Task List</h5>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="exportDropdown" data-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-download me-1"></i> Export
                                </button>
                                <div class="dropdown-menu" aria-labelledby="exportDropdown">
                                    <a class="dropdown-item" href="#" id="exportPdf"><i class="far fa-file-pdf text-danger me-2"></i>PDF</a>
                                    <a class="dropdown-item" href="#" id="exportExcel"><i class="far fa-file-excel text-success me-2"></i>Excel</a>
                                    <a class="dropdown-item" href="#" id="exportPrint"><i class="fas fa-print text-primary me-2"></i>Print</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Task</th>
                                        <th>Assigned To</th>
                                        <th>Assigned By</th>
                                        <th>Due Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($tasks)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-4">
                                                <div class="text-muted">
                                                    <i class="fas fa-inbox fa-3x mb-3"></i>
                                                    <p class="mb-0">No tasks found matching your criteria.</p>
                                                    <a href="manage_hr_tasks.php" class="btn btn-sm btn-outline-primary mt-2">Clear Filters</a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach($tasks as $task): 
                                            $due_date = new DateTime($task['due_date']);
                                            $today = new DateTime();
                                            $interval = $today->diff($due_date);
                                            $days_remaining = $interval->days * ($interval->invert ? -1 : 1);
                                            
                                            // Determine status class
                                            $status_class = 'status-' . str_replace(' ', '-', $task['status']);
                                            
                                            // Determine if task is overdue or due soon
                                            $due_class = '';
                                            if ($task['status'] !== 'Completed') {
                                                if ($days_remaining < 0) {
                                                    $due_class = 'overdue';
                                                    $due_text = abs($days_remaining) . ' days overdue';
                                                } elseif ($days_remaining == 0) {
                                                    $due_class = 'due-soon';
                                                    $due_text = 'Due today';
                                                } elseif ($days_remaining <= 3) {
                                                    $due_class = 'due-soon';
                                                    $due_text = $days_remaining . ' days left';
                                                } else {
                                                    $due_text = $days_remaining . ' days left';
                                                }
                                            } else {
                                                $due_text = 'Completed on ' . $due_date->format('M d, Y');
                                            }
                                        ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div>
                                                        <h6 class="task-title"><?php echo htmlspecialchars($task['title']); ?></h6>
                                                        <?php if (!empty($task['description'])): ?>
                                                            <div class="task-description"><?php echo htmlspecialchars(substr($task['description'], 0, 50)) . (strlen($task['description']) > 50 ? '...' : ''); ?></div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-sm">
                                                        <span class="text-uppercase"><?php echo substr($task['assignee_name'], 0, 1); ?></span>
                                                    </div>
                                                    <span><?php echo htmlspecialchars($task['assignee_name']); ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if (!empty($task['assigner_name'])): ?>
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar-sm">
                                                            <span class="text-uppercase"><?php echo substr($task['assigner_name'], 0, 1); ?></span>
                                                        </div>
                                                        <span><?php echo htmlspecialchars($task['assigner_name']); ?></span>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">System</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="d-flex flex-column">
                                                    <span><?php echo $due_date->format('M d, Y'); ?></span>
                                                    <small class="<?php echo $due_class; ?>">
                                                        <?php echo $due_text; ?>
                                                    </small>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="status-badge <?php echo $status_class; ?>">
                                                    <?php echo htmlspecialchars($task['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="taskActions" data-toggle="dropdown" aria-expanded="false">
                                                        <i class="fas fa-ellipsis-v"></i>
                                                    </button>
                                                    <div class="dropdown-menu" aria-labelledby="taskActions">
                                                        <a class="dropdown-item" href="view_task.php?id=<?php echo $task['id']; ?>"><i class="far fa-eye me-2"></i>View Details</a>
                                                        <a class="dropdown-item" href="edit_task.php?id=<?php echo $task['id']; ?>"><i class="far fa-edit me-2"></i>Edit Task</a>
                                                        <div class="dropdown-divider"></div>
                                                        <a class="dropdown-item text-danger" href="#" onclick="return confirmDelete(<?php echo $task['id']; ?>)"><i class="far fa-trash-alt me-2"></i>Delete</a>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-white py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="text-muted small">
                                Showing <span class="font-weight-bold"><?php echo min(count($tasks), 1); ?></span> to 
                                <span class="font-weight-bold"><?php echo count($tasks); ?></span> of 
                                <span class="font-weight-bold"><?php echo count($tasks); ?></span> tasks
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Task Modal -->
    <div class="modal fade" id="addTaskModal" tabindex="-1" aria-labelledby="addTaskModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addTaskModalLabel">Assign New Task</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="title">Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control <?php echo (isset($errors['title'])) ? 'is-invalid' : ''; ?>" 
                                   id="title" name="title" value="<?php echo $_POST['title'] ?? ''; ?>">
                            <?php if(isset($errors['title'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['title']; ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea class="form-control <?php echo (isset($errors['description'])) ? 'is-invalid' : ''; ?>" 
                                     id="description" name="description" rows="3"><?php echo $_POST['description'] ?? ''; ?></textarea>
                            <?php if(isset($errors['description'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['description']; ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="assigned_to">Assign To <span class="text-danger">*</span></label>
                                    <select class="form-control <?php echo (isset($errors['assigned_to'])) ? 'is-invalid' : ''; ?>" 
                                            id="assigned_to" name="assigned_to">
                                        <option value="">Select HR Admin</option>
                                        <?php foreach($hr_admins as $hr_admin): ?>
                                            <option value="<?php echo $hr_admin['id']; ?>" 
                                                <?php echo (isset($_POST['assigned_to']) && $_POST['assigned_to'] == $hr_admin['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($hr_admin['first_name'] . ' ' . $hr_admin['last_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if(isset($errors['assigned_to'])): ?>
                                        <div class="invalid-feedback"><?php echo $errors['assigned_to']; ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="due_date">Due Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control <?php echo (isset($errors['due_date'])) ? 'is-invalid' : ''; ?>" 
                                           id="due_date" name="due_date" value="<?php echo $_POST['due_date'] ?? ''; ?>">
                                    <?php if(isset($errors['due_date'])): ?>
                                        <div class="invalid-feedback"><?php echo $errors['due_date']; ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Assign Task</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Toggle sidebar
            $('#sidebarCollapse').on('click', function() {
                $('#sidebar').toggleClass('active');
            });

            // Initialize tooltips
            $('[data-toggle="tooltip"]').tooltip();

            // Show modal if there are form errors
            <?php if (!empty($errors)): ?>
                $('#addTaskModal').modal('show');
            <?php endif; ?>

            // Export buttons
            $('#exportPdf').on('click', function(e) {
                e.preventDefault();
                alert('Export to PDF functionality will be implemented here');
                // window.location.href = 'export_tasks.php?type=pdf';
            });

            $('#exportExcel').on('click', function(e) {
                e.preventDefault();
                alert('Export to Excel functionality will be implemented here');
                // window.location.href = 'export_tasks.php?type=excel';
            });

            $('#exportPrint').on('click', function(e) {
                e.preventDefault();
                window.print();
            });
        });

        // Confirm before deleting a task
        function confirmDelete(taskId) {
            if (confirm('Are you sure you want to delete this task? This action cannot be undone.')) {
                window.location.href = 'delete_task.php?id=' + taskId;
            }
            return false;
        }
    </script>
</body>
</html>
