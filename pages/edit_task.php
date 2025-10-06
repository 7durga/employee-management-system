<?php
require_once '../config/config.php';

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'HR Admin'){
    header("location: login.php");
    exit;
}

$task_id = $_GET['id'];
$task = null;
$errors = [];

// Fetch task data
$sql_task = "SELECT * FROM tasks WHERE id = :id";
if($stmt_task = $pdo->prepare($sql_task)){
    $stmt_task->bindParam(":id", $task_id, PDO::PARAM_INT);
    if($stmt_task->execute() && $stmt_task->rowCount() == 1){
        $task = $stmt_task->fetch(PDO::FETCH_ASSOC);
    } else {
        echo "Task not found.";
        exit();
    }
    unset($stmt_task);
}

// Fetch employees for dropdown
$sql_employees = "SELECT employee_id, first_name, last_name FROM employees";
$stmt_employees = $pdo->query($sql_employees);
$employees = $stmt_employees->fetchAll(PDO::FETCH_ASSOC);

if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validation
    if(empty($errors)){
        $sql = "UPDATE tasks SET title = :title, description = :description, assigned_to = :assigned_to, due_date = :due_date WHERE id = :id";
        if($stmt = $pdo->prepare($sql)){
            $stmt->execute([
                ':title' => $_POST['title'],
                ':description' => $_POST['description'],
                ':assigned_to' => $_POST['assigned_to'],
                ':due_date' => $_POST['due_date'],
                ':id' => $task_id
            ]);
            header("location: manage_tasks.php");
            exit();
        } else {
            echo "Something went wrong. Please try again later.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Task</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container">
        <h2 class="mt-5">Edit Task</h2>
        <?php if($task): ?>
        <form action="<?php echo htmlspecialchars(basename($_SERVER['REQUEST_URI'])); ?>" method="post">
            <div class="form-group">
                <label>Title</label>
                <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($task['title']); ?>">
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" class="form-control"><?php echo htmlspecialchars($task['description']); ?></textarea>
            </div>
            <div class="form-group">
                <label>Assign To</label>
                <select name="assigned_to" class="form-control">
                    <?php foreach($employees as $employee): ?>
                        <option value="<?php echo $employee['employee_id']; ?>" <?php if($task['assigned_to'] == $employee['employee_id']) echo 'selected'; ?>>
                            <?php echo $employee['first_name'] . ' ' . $employee['last_name']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Due Date</label>
                <input type="date" name="due_date" class="form-control" value="<?php echo $task['due_date']; ?>">
            </div>
            <div class="form-group">
                <input type="submit" class="btn btn-primary" value="Submit">
                <a href="manage_tasks.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
        <?php endif; ?>
    </div>
</body>
</html>
