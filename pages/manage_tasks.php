<?php
require_once '../config/config.php';

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'HR Admin'){
    header("location: login.php");
    exit;
}

$sql = "SELECT t.id, t.title, t.description, t.due_date, t.status, 
               u.username as assigned_to_name, t.assigned_to, t.assigned_by
        FROM tasks t 
        JOIN users u ON t.assigned_to = u.id 
        WHERE t.assigned_to = :user_id
        ORDER BY t.due_date DESC";
        
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':user_id', $_SESSION['id'], PDO::PARAM_INT);
$stmt->execute();
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Tasks</title>
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

            <h1 class="page-title">Manage Tasks</h1>
            <a href="add_task.php" class="btn btn-success mb-3">Add New Task</a>
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Assigned To</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(!empty($tasks)):
                        foreach($tasks as $task):
                    ?>
                            <tr>
                                <td><?php echo htmlspecialchars($task['title']); ?></td>
                                <td><?php echo htmlspecialchars($task['assigned_to_name']); ?></td>
                                <td><?php echo htmlspecialchars($task['due_date']); ?></td>
                                <td>
                                    <form action="update_task_status.php" method="post" class="status-form" data-task-id="<?php echo $task['id']; ?>">
                                        <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                        <select name="status" class="form-control form-control-sm status-select" 
                                                onchange="this.form.submit()" 
                                                style="min-width: 120px;">
                                            <option value="Pending" <?php echo $task['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="In Progress" <?php echo $task['status'] === 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                                            <option value="Completed" <?php echo $task['status'] === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                        </select>
                                    </form>
                                </td>
                                <td>
                                    <a href="edit_task.php?id=<?php echo $task['id']; ?>" class="btn btn-primary">Edit</a>
                                    <a href="delete_task.php?id=<?php echo $task['id']; ?>" class="btn btn-danger">Delete</a>
                                </td>
                            </tr>
                    <?php
                        endforeach;
                    else:
                    ?>
                        <tr>
                            <td colspan="5" class="text-center">No tasks found.</td>
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
