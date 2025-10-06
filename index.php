<?php
// Include config file
require_once 'config/config.php';

// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: pages/login.php");
    exit;
}

// Redirect user to their respective dashboard based on role
$role = $_SESSION["role"];
switch($role){
    case 'Super Admin':
        header("location: pages/super_admin_dashboard.php");
        break;
    case 'HR Admin':
        header("location: pages/hr_admin_dashboard.php");
        break;
    case 'Employee':
        header("location: pages/employee_dashboard.php");
        break;
    default:
        // Redirect to login page if role is not set or invalid
        header("location: pages/login.php");
        break;
}
exit;
?>
