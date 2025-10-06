<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include configuration
require_once '../config/config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and has the right permissions
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || 
    ($_SESSION["role"] !== 'Super Admin' && $_SESSION["role"] !== 'HR Admin')) {
    header("location: login.php");
    exit();
}

// Initialize variables
$errors = [];
$success = '';

// Get all employees for the assignee dropdown
$employees_sql = "SELECT e.employee_id, e.first_name, e.last_name, u.role 
                FROM employees e 
                JOIN users u ON e.employee_id = u.employee_id 
                ORDER BY e.first_name, e.last_name";
$employees = $pdo->query($employees_sql)->fetchAll(PDO::FETCH_ASSOC);

// Fetch tasks assigned to the current user
$current_employee_id = $_SESSION['employee_id'] ?? 0;
$recent_tasks_sql = "SELECT t.*, 
                    CONCAT(e.first_name, ' ', e.last_name) as assignee_name,
                    CONCAT(e2.first_name, ' ', e2.last_name) as assigner_name
                    FROM tasks t
                    JOIN employees e ON t.assigned_to = e.employee_id
                    LEFT JOIN employees e2 ON t.assigned_by = e2.employee_id
                    WHERE t.assigned_to = :employee_id
                    ORDER BY 
                        CASE 
                            WHEN t.status = 'Completed' THEN 2
                            WHEN t.due_date < CURDATE() THEN 0 
                            ELSE 1 
                        END,
                        t.due_date ASC, 
                        t.id DESC
                    LIMIT 10";
$stmt = $pdo->prepare($recent_tasks_sql);
$stmt->execute([':employee_id' => $current_employee_id]);
$recent_tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate inputs
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $assigned_to = $_POST['assigned_to'] ?? '';
    $priority = $_POST['priority'] ?? 'medium';
    $due_date = $_POST['due_date'] ?? '';
    
    // Basic validation
    if (empty($title)) {
        $errors['title'] = 'Title is required';
    }
    
    if (empty($assigned_to)) {
        $errors['assigned_to'] = 'Please select an employee';
    }
    
    if (empty($due_date)) {
        $errors['due_date'] = 'Due date is required';
    } elseif (strtotime($due_date) < strtotime('today')) {
        $errors['due_date'] = 'Due date cannot be in the past';
    }
    
    // If no errors, insert into database
    if (empty($errors)) {
        try {
            // Get the current user's employee ID
            $assigned_by = $_SESSION['employee_id'];
            
            // Prepare SQL
            $sql = "INSERT INTO tasks (title, description, assigned_to, assigned_by, due_date, status) 
                    VALUES (:title, :description, :assigned_to, :assigned_by, :due_date, 'Pending')";
            
            $stmt = $pdo->prepare($sql);
            
            // Bind parameters
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':assigned_to', $assigned_to);
            $stmt->bindParam(':assigned_by', $assigned_by);
            $stmt->bindParam(':due_date', $due_date);
            
            // Execute the query
            if ($stmt->execute()) {
                $success = 'Task has been assigned successfully!';
                // Clear form
                $_POST = [];
            } else {
                $errors['database'] = 'Something went wrong. Please try again.';
            }
        } catch (PDOException $e) {
            $errors['database'] = 'Database error: ' . $e->getMessage();
        }
    }
}

// Set page title
$page_title = "Assign New Task";
include 'templates/header.php';
?>

<div class="wrapper">
    <!-- Sidebar -->
    <?php include 'templates/sidebar.php'; ?>
    
    <!-- Page Content -->
    <div id="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-header d-flex justify-content-between align-items-center mb-4">
                        <h1 class="h3">Assign New Task</h1>
                        <div>
                            <a href="task_report.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Back to Tasks
                            </a>
                        </div>
                    </div>

                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($errors['database'])): ?>
                        <div class="alert alert-danger"><?php echo $errors['database']; ?></div>
                    <?php endif; ?>

                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Task Details</h5>
                        </div>
                        <div class="card-body">
                            <form method="post" class="needs-validation" novalidate>
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label for="title" class="form-label">Task Title <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control <?php echo !empty($errors['title']) ? 'is-invalid' : ''; ?>" 
                                               id="title" name="title" value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" required>
                                        <?php if (!empty($errors['title'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['title']; ?></div>
                                        <?php else: ?>
                                            <div class="form-text">A clear and concise title for the task</div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="col-md-12 mb-3">
                                        <label for="description" class="form-label">Description</label>
                                        <textarea class="form-control" id="description" name="description" 
                                                  rows="4"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                                        <div class="form-text">Provide detailed instructions or requirements</div>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="assigned_to" class="form-label">Assign To <span class="text-danger">*</span></label>
                                        <select class="form-select <?php echo !empty($errors['assigned_to']) ? 'is-invalid' : ''; ?>" 
                                                id="assigned_to" name="assigned_to" required>
                                            <option value="">Select Employee</option>
                                            <?php foreach ($employees as $employee): ?>
                                                <option value="<?php echo $employee['employee_id']; ?>" 
                                                    <?php echo (isset($_POST['assigned_to']) && $_POST['assigned_to'] == $employee['employee_id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name'] . ' (' . $employee['role'] . ')'); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php if (!empty($errors['assigned_to'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['assigned_to']; ?></div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="col-md-3 mb-3">
                                        <label for="priority" class="form-label">Priority</label>
                                        <select class="form-select" id="priority" name="priority">
                                            <option value="low" <?php echo (isset($_POST['priority']) && $_POST['priority'] == 'low') ? 'selected' : ''; ?>>Low</option>
                                            <option value="medium" <?php echo (!isset($_POST['priority']) || $_POST['priority'] == 'medium') ? 'selected' : ''; ?>>Medium</option>
                                            <option value="high" <?php echo (isset($_POST['priority']) && $_POST['priority'] == 'high') ? 'selected' : ''; ?>>High</option>
                                            <option value="urgent" <?php echo (isset($_POST['priority']) && $_POST['priority'] == 'urgent') ? 'selected' : ''; ?>>Urgent</option>
                                        </select>
                                    </div>

                                    <div class="col-md-3 mb-3">
                                        <label for="due_date" class="form-label">Due Date <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control <?php echo !empty($errors['due_date']) ? 'is-invalid' : ''; ?>" 
                                               id="due_date" name="due_date" 
                                               value="<?php echo htmlspecialchars($_POST['due_date'] ?? ''); ?>" 
                                               min="<?php echo date('Y-m-d'); ?>" required>
                                        <?php if (!empty($errors['due_date'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['due_date']; ?></div>
                                        <?php else: ?>
                                            <div class="form-text">The deadline for this task</div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                    <button type="reset" class="btn btn-outline-secondary me-md-2">
                                        <i class="fas fa-undo me-1"></i> Reset
                                    </button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-paper-plane me-1"></i> Assign Task
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Recent Tasks Card -->
                    <div class="card mt-4">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0"><i class="fas fa-tasks me-2"></i>Recent Tasks</h5>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($recent_tasks)): ?>
                                <div class="p-4 text-center text-muted">
                                    No tasks found.
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Task</th>
                                                <th>Assigned To</th>
                                                <th>Due Date</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recent_tasks as $task): 
                                                $due_date = new DateTime($task['due_date']);
                                                $today = new DateTime();
                                                $is_overdue = $due_date < $today && $task['status'] != 'Completed';
                                                $status_class = [
                                                    'Pending' => 'warning',
                                                    'In Progress' => 'primary',
                                                    'Completed' => 'success',
                                                    'Overdue' => 'danger'
                                                ][$is_overdue ? 'Overdue' : $task['status']];
                                            ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex justify-content-between align-items-start">
                                                            <div>
                                                                <div class="fw-bold"><?php echo htmlspecialchars($task['title']); ?></div>
                                                                <small class="text-muted"><?php echo htmlspecialchars(substr($task['description'], 0, 50)) . (strlen($task['description']) > 50 ? '...' : ''); ?></small>
                                                            </div>
                                                            <div class="dropdown">
                                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                                    <i class="fas fa-ellipsis-v"></i>
                                                                </button>
                                                                <ul class="dropdown-menu dropdown-menu-end">
                                                                    <li>
                                                                        <a class="dropdown-item" href="edit_task.php?id=<?php echo $task['id']; ?>">
                                                                            <i class="fas fa-edit me-2"></i>Edit
                                                                        </a>
                                                                    </li>
                                                                    <li>
                                                                        <a class="dropdown-item" href="update_task_status.php?id=<?php echo $task['id']; ?>&status=In Progress">
                                                                            <i class="fas fa-spinner me-2"></i>Mark as In Progress
                                                                        </a>
                                                                    </li>
                                                                    <li>
                                                                        <a class="dropdown-item" href="update_task_status.php?id=<?php echo $task['id']; ?>&status=Completed">
                                                                            <i class="fas fa-check-circle me-2"></i>Mark as Completed
                                                                        </a>
                                                                    </li>
                                                                </ul>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($task['assignee_name']); ?></td>
                                                    <td>
                                                        <?php echo $due_date->format('M d, Y'); ?>
                                                        <?php if ($is_overdue): ?>
                                                            <span class="badge bg-danger ms-2">Overdue</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $status_class; ?>">
                                                            <?php echo $is_overdue ? 'Overdue' : htmlspecialchars($task['status']); ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="card-footer bg-white d-flex justify-content-between align-items-center">
                                    <a href="task_report.php" class="btn btn-sm btn-outline-primary">
                                        View All Tasks <i class="fas fa-arrow-right ms-1"></i>
                                    </a>
                                    <small class="text-muted">
                                        Showing <?php echo count($recent_tasks); ?> of your tasks
                                    </small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Enable form validation
(function() {
    'use strict';
    window.addEventListener('load', function() {
        var forms = document.getElementsByClassName('needs-validation');
        var validation = Array.prototype.filter.call(forms, function(form) {
            form.addEventListener('submit', function(event) {
                if (form.checkValidity() === false) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    }, false);
})();
</script>

<?php include 'templates/footer.php'; ?>
