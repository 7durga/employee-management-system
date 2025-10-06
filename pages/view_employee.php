<?php
require_once '../config/config.php';

// Only allow logged in Super Admin or HR Admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || 
   ($_SESSION["role"] !== 'Super Admin' && $_SESSION["role"] !== 'HR Admin')){
    header("location: login.php");
    exit;
}

// Check if employee ID is provided
if(!isset($_GET['id']) || empty(trim($_GET['id']))){
    // Redirect based on role
    $redirectTo = ($_SESSION['role'] === 'Super Admin') ? 'manage_employees.php' : 'hr_manage_employees.php';
    header("location: $redirectTo");
    exit();
}

$employee_id = trim($_GET['id']);

// Fetch employee details
$sql = "SELECT e.*, u.username, u.role 
        FROM employees e 
        JOIN users u ON e.employee_id = u.employee_id 
        WHERE e.employee_id = :employee_id";
        
if($stmt = $pdo->prepare($sql)){
    $stmt->bindParam(":employee_id", $employee_id, PDO::PARAM_STR);
    
    if($stmt->execute()){
        if($stmt->rowCount() == 1){
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);
        } else{
            $_SESSION['error_message'] = "Employee not found.";
            header("location: hr_manage_employees.php");
            exit();
        }
    } else{
        $_SESSION['error_message'] = "Oops! Something went wrong. Please try again later.";
        header("location: hr_manage_employees.php");
        exit();
    }
}

$page_title = "View Employee: " . $employee['first_name'] . " " . $employee['last_name'];
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
                        <h1 class="h3">Employee Details</h1>
                        <div>
                            <a href="edit_employee.php?id=<?php echo $employee['employee_id']; ?>" class="btn btn-warning">
                                <i class="fas fa-edit me-2"></i>Edit Employee
                            </a>
                            <a href="hr_manage_employees.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Back to List
                            </a>
                        </div>
                    </div>

                    <?php include 'templates/messages.php'; ?>

                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Personal Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Employee ID:</strong> <?php echo htmlspecialchars($employee['employee_id']); ?></p>
                                    <p><strong>First Name:</strong> <?php echo htmlspecialchars($employee['first_name']); ?></p>
                                    <p><strong>Last Name:</strong> <?php echo htmlspecialchars($employee['last_name']); ?></p>
                                    <p><strong>Email:</strong> <?php echo htmlspecialchars($employee['email']); ?></p>
                                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($employee['phone'] ?? 'N/A'); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Username:</strong> <?php echo htmlspecialchars($employee['username']); ?></p>
                                    <p><strong>Role:</strong> <?php echo htmlspecialchars($employee['role']); ?></p>
                                    <p><strong>Department:</strong> <?php echo htmlspecialchars($employee['department'] ?? 'N/A'); ?></p>
                                    <p><strong>Position:</strong> <?php echo htmlspecialchars($employee['position'] ?? 'N/A'); ?></p>
                                    <p><strong>Hire Date:</strong> <?php echo !empty($employee['hire_date']) ? date('M d, Y', strtotime($employee['hire_date'])) : 'N/A'; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">Contact Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Address:</strong> <?php echo !empty($employee['address']) ? nl2br(htmlspecialchars($employee['address'])) : 'N/A'; ?></p>
                                    <p><strong>City:</strong> <?php echo htmlspecialchars($employee['city'] ?? 'N/A'); ?></p>
                                    <p><strong>State/Province:</strong> <?php echo htmlspecialchars($employee['state'] ?? 'N/A'); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Postal Code:</strong> <?php echo htmlspecialchars($employee['postal_code'] ?? 'N/A'); ?></p>
                                    <p><strong>Country:</strong> <?php echo htmlspecialchars($employee['country'] ?? 'N/A'); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if(!empty($employee['emergency_contact_name'])): ?>
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-warning">
                            <h5 class="mb-0">Emergency Contact</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Name:</strong> <?php echo htmlspecialchars($employee['emergency_contact_name']); ?></p>
                                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($employee['emergency_contact_phone'] ?? 'N/A'); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Relationship:</strong> <?php echo htmlspecialchars($employee['emergency_contact_relation'] ?? 'N/A'); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>
