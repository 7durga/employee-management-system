<?php
require_once 'config/config.php';

$username = 'superadmin';
$password = 'admin123';
$role = 'Super Admin';

// Hash the password for security
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Check if the user already exists
$sql_check = "SELECT id FROM users WHERE username = :username";
if($stmt_check = $pdo->prepare($sql_check)){
    $stmt_check->bindParam(':username', $username, PDO::PARAM_STR);
    $stmt_check->execute();
    if($stmt_check->rowCount() > 0){
        echo "Super Admin account already exists.";
        exit;
    }
}

// Insert the new Super Admin user
$sql = "INSERT INTO users (username, password, role) VALUES (:username, :password, :role)";

if($stmt = $pdo->prepare($sql)){
    $stmt->bindParam(':username', $username, PDO::PARAM_STR);
    $stmt->bindParam(':password', $hashed_password, PDO::PARAM_STR);
    $stmt->bindParam(':role', $role, PDO::PARAM_STR);

    if($stmt->execute()){
        echo "Super Admin account created successfully.<br>";
        echo "You can now log in with:<br>";
        echo "Username: <strong>superadmin</strong><br>";
        echo "Password: <strong>admin123</strong><br><br>";
        echo "<strong style='color:red;'>IMPORTANT: Delete this setup.php file now for security!</strong>";
    } else{
        echo "Error: Could not create the Super Admin account.";
    }

    unset($stmt);
}

unset($pdo);
?>
