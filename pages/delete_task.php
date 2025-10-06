<?php
require_once '../config/config.php';

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'HR Admin'){
    header("location: login.php");
    exit;
}

if(isset($_POST["id"]) && !empty($_POST["id"])){
    $sql = "DELETE FROM tasks WHERE id = :id";
    
    if($stmt = $pdo->prepare($sql)){
        $stmt->bindParam(":id", $_POST["id"], PDO::PARAM_INT);
        if($stmt->execute()){
            header("location: manage_tasks.php");
            exit();
        } else{
            echo "Oops! Something went wrong. Please try again later.";
        }
    }
    unset($stmt);
    unset($pdo);
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
    <title>Delete Task</title>
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
                    <h2 class="mt-5 mb-3">Delete Task</h2>
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                        <div class="alert alert-danger">
                            <input type="hidden" name="id" value="<?php echo trim($_GET["id"]); ?>"/>
                            <p>Are you sure you want to delete this task?</p>
                            <p>
                                <input type="submit" value="Yes" class="btn btn-danger">
                                <a href="manage_tasks.php" class="btn btn-secondary">No</a>
                            </p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
