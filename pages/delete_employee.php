<?php
require_once '../config/config.php';

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'HR Admin'){
    header("location: login.php");
    exit;
}

if(isset($_POST["id"]) && !empty($_POST["id"])){
    try {
        $pdo->beginTransaction();

        // Get employee_id before deleting
        $sql = "SELECT employee_id FROM employees WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(":id", $_POST["id"], PDO::PARAM_INT);
        $stmt->execute();
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);
        $employee_id = $employee['employee_id'];

        // Delete from employees table
        $sql1 = "DELETE FROM employees WHERE id = :id";
        $stmt1 = $pdo->prepare($sql1);
        $stmt1->bindParam(":id", $_POST["id"], PDO::PARAM_INT);
        $stmt1->execute();

        // Delete from users table
        $sql2 = "DELETE FROM users WHERE employee_id = :employee_id";
        $stmt2 = $pdo->prepare($sql2);
        $stmt2->bindParam(":employee_id", $employee_id, PDO::PARAM_STR);
        $stmt2->execute();

        $pdo->commit();
        header("location: manage_employees.php");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "Oops! Something went wrong. Please try again later.";
    }
} else {
    if(empty(trim($_GET["id"]))){ 
        header("location: error.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Delete Employee</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .wrapper{ width: 600px; margin: 0 auto; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <h2 class="mt-5 mb-3">Delete Employee</h2>
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                        <div class="alert alert-danger">
                            <input type="hidden" name="id" value="<?php echo trim($_GET["id"]); ?>"/>
                            <p>Are you sure you want to delete this employee record?</p>
                            <p>
                                <input type="submit" value="Yes" class="btn btn-danger">
                                <a href="manage_employees.php" class="btn btn-secondary">No</a>
                            </p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
