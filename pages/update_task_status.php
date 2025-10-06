<?php
// Initialize the session
session_start();
require_once '../config/config.php';

// Check if the user is logged in and is either HR Admin or Employee
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || 
   ($_SESSION["role"] !== 'HR Admin' && $_SESSION["role"] !== 'Employee')) {
    header("location: login.php");
    exit;
}

if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['task_id']) && isset($_POST['status'])){
    $task_id = trim($_POST['task_id']);
    $status = trim($_POST['status']);
    $user_id = $_SESSION['id'];

    // For HR Admin, allow updating status for any task
    if ($_SESSION["role"] === 'HR Admin') {
        $sql = "UPDATE tasks SET status = :status WHERE id = :task_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        $stmt->bindParam(':task_id', $task_id, PDO::PARAM_INT);
    } 
    // For Employees, only allow updating their own tasks
    else {
        // First, get the employee_id for the current user
        $sql_emp = "SELECT employee_id FROM users WHERE id = :user_id";
        $stmt_emp = $pdo->prepare($sql_emp);
        $stmt_emp->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt_emp->execute();
        $user = $stmt_emp->fetch(PDO::FETCH_ASSOC);
        
        if ($user && !empty($user['employee_id'])) {
            $employee_id = $user['employee_id'];
            $sql = "UPDATE tasks SET status = :status WHERE id = :task_id AND assigned_to = :employee_id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':status', $status, PDO::PARAM_STR);
            $stmt->bindParam(':task_id', $task_id, PDO::PARAM_INT);
            $stmt->bindParam(':employee_id', $employee_id, PDO::PARAM_STR);
        } else {
            // Redirect with error if employee not found
            header("Location: " . (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'employee_dashboard.php') . "?error=employee_not_found");
            exit();
        }
    }
    
    if($stmt->execute()){
        // Success - redirect back to the previous page
        $redirect = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'manage_tasks.php';
        header("Location: " . $redirect);
    } else {
        // Log the error for debugging
        error_log("Failed to update task status: " . print_r($stmt->errorInfo(), true));
        // Redirect with error message
        $redirect = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] . '?error=update_failed' : 'manage_tasks.php?error=update_failed';
        header("Location: " . $redirect);
    }
    exit();
} else {
    // Invalid request
    header("Location: manage_tasks.php?error=invalid_request");
    exit();
}
?>
