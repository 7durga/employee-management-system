<?php
// Initialize the session
session_start();
require_once '../config/config.php';

// Check if the user is logged in and is an employee
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'Employee'){
    header("location: login.php");
    exit;
}

$employee_id = $_SESSION['employee_id'];
$today = date("Y-m-d");
$now = date("Y-m-d H:i:s");

if(isset($_POST['punch_in'])){
    // Check if already punched in
    $sql = "SELECT id FROM attendance WHERE employee_id = :employee_id AND date = :today";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':employee_id' => $employee_id, ':today' => $today]);

    if($stmt->rowCount() == 0){
        // Insert new attendance record
        $sql = "INSERT INTO attendance (employee_id, date, punch_in_time) VALUES (:employee_id, :date, :punch_in_time)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':employee_id' => $employee_id, ':date' => $today, ':punch_in_time' => $now]);
    }
} elseif(isset($_POST['punch_out'])){
    // Update existing attendance record
    $sql = "UPDATE attendance SET punch_out_time = :punch_out_time WHERE employee_id = :employee_id AND date = :today AND punch_out_time IS NULL";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':punch_out_time' => $now, ':employee_id' => $employee_id, ':today' => $today]);
}

// Redirect back to the dashboard
header("location: employee_dashboard.php");
exit;
?>
