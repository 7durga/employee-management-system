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
$errors = [];
// Fetch employee data
$sql = "SELECT e.*, u.username, u.role 
        FROM employees e 
        JOIN users u ON e.employee_id = u.employee_id 
        WHERE e.employee_id = :employee_id";

if($stmt = $pdo->prepare($sql)){
    $stmt->bindParam(":employee_id", $employee_id, PDO::PARAM_STR);
    
    if($stmt->execute()){
        if($stmt->rowCount() == 1){
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
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

// Process form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validate inputs
    $required_fields = [
        'first_name' => 'First Name',
        'last_name' => 'Last Name',
        'email' => 'Email',
        'phone' => 'Phone',
        'department' => 'Department',
        'position' => 'Position',
        'employment_type' => 'Employment Type'
    ];
    
    foreach($required_fields as $field => $label) {
        if(empty(trim($_POST[$field] ?? ''))) {
            $errors[$field] = "$label is required.";
        }
    }

    // Validate email format
    if(!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)){
        $errors['email'] = "Please enter a valid email address.";
    }
    
    // Validate password if provided
    if(!empty(trim($_POST['password'])) && strlen(trim($_POST['password'])) < 6){
        $errors['password'] = "Password must be at least 6 characters.";
    }

    if(empty($errors)){
        try {
            $pdo->beginTransaction();

            // Update employees table with all fields
            $sql = "UPDATE employees SET 
                    first_name = :first_name,
                    last_name = :last_name,
                    email = :email,
                    phone = :phone,
                    address = :address,
                    city = :city,
                    state = :state,
                    postal_code = :postal_code,
                    country = :country,
                    department = :department,
                    position = :position,
                    employment_type = :employment_type,
                    salary = :salary,
                    emergency_contact_name = :emergency_contact_name,
                    emergency_contact_phone = :emergency_contact_phone,
                    emergency_contact_relation = :emergency_contact_relation
                    WHERE employee_id = :employee_id";

            $stmt = $pdo->prepare($sql);
            
            $stmt->execute([
                ':first_name' => $_POST['first_name'],
                ':last_name' => $_POST['last_name'],
                ':email' => $_POST['email'],
                ':phone' => $_POST['phone'],
                ':address' => $_POST['address'] ?? null,
                ':city' => $_POST['city'] ?? null,
                ':state' => $_POST['state'] ?? null,
                ':postal_code' => $_POST['postal_code'] ?? null,
                ':country' => $_POST['country'] ?? null,
                ':department' => $_POST['department'],
                ':position' => $_POST['position'],
                ':employment_type' => $_POST['employment_type'],
                ':salary' => !empty($_POST['salary']) ? str_replace(',', '', $_POST['salary']) : null,
                ':emergency_contact_name' => $_POST['emergency_contact_name'] ?? null,
                ':emergency_contact_phone' => $_POST['emergency_contact_phone'] ?? null,
                ':emergency_contact_relation' => $_POST['emergency_contact_relation'] ?? null,
                ':employee_id' => $employee_id
            ]);

            // Update password if provided
            if(!empty(trim($_POST['password']))){
                $sql = "UPDATE users SET password = :password WHERE employee_id = :employee_id";
                $stmt = $pdo->prepare($sql);
                $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $stmt->execute([':password' => $hashed_password, ':employee_id' => $employee_id]);
            }

            $pdo->commit();
            $_SESSION['success_message'] = 'Employee updated successfully!';
            header("location: view_employee.php?id=" . $employee_id);
            exit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors['database'] = "Something went wrong. Please try again later. Error: " . $e->getMessage();
        }
    }
}
?>

<?php
$page_title = "Edit Employee";
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
                        <h1 class="h3">Edit Employee: <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></h1>
                        <div>
                            <a href="view_employee.php?id=<?php echo $employee_id; ?>" class="btn btn-info">
                                <i class="fas fa-eye me-2"></i>View
                            </a>
                            <a href="hr_manage_employees.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Back to List
                            </a>
                        </div>
                    </div>

                    <?php if (!empty($errors['database'])): ?>
                        <div class="alert alert-danger"><?php echo $errors['database']; ?></div>
                    <?php endif; ?>

                    <form action="<?php echo htmlspecialchars(basename($_SERVER['REQUEST_URI'])); ?>" method="post" class="needs-validation" novalidate>
                        <div class="row">
                            <!-- Personal Information -->
                            <div class="col-md-6">
                                <div class="card mb-4">
                                    <div class="card-header bg-primary text-white">
                                        <h5 class="mb-0">Personal Information</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-row">
                                            <div class="form-group col-md-6">
                                                <label>First Name <span class="text-danger">*</span></label>
                                                <input type="text" name="first_name" class="form-control <?php echo (isset($errors['first_name'])) ? 'is-invalid' : ''; ?>" 
                                                       value="<?php echo htmlspecialchars($employee['first_name']); ?>" required>
                                                <div class="invalid-feedback"><?php echo $errors['first_name'] ?? 'Please enter first name'; ?></div>
                                            </div>
                                            <div class="form-group col-md-6">
                                                <label>Last Name <span class="text-danger">*</span></label>
                                                <input type="text" name="last_name" class="form-control <?php echo (isset($errors['last_name'])) ? 'is-invalid' : ''; ?>" 
                                                       value="<?php echo htmlspecialchars($employee['last_name']); ?>" required>
                                                <div class="invalid-feedback"><?php echo $errors['last_name'] ?? 'Please enter last name'; ?></div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label>Email <span class="text-danger">*</span></label>
                                            <input type="email" name="email" class="form-control <?php echo (isset($errors['email'])) ? 'is-invalid' : ''; ?>" 
                                                   value="<?php echo htmlspecialchars($employee['email']); ?>" required>
                                            <div class="invalid-feedback"><?php echo $errors['email'] ?? 'Please enter a valid email'; ?></div>
                                        </div>
                                        <div class="form-group">
                                            <label>Phone <span class="text-danger">*</span></label>
                                            <input type="tel" name="phone" class="form-control <?php echo (isset($errors['phone'])) ? 'is-invalid' : ''; ?>" 
                                                   value="<?php echo htmlspecialchars($employee['phone']); ?>" required>
                                            <div class="invalid-feedback"><?php echo $errors['phone'] ?? 'Please enter phone number'; ?></div>
                                        </div>
                                        <div class="form-group">
                                            <label>Address</label>
                                            <textarea name="address" class="form-control" rows="2"><?php echo htmlspecialchars($employee['address'] ?? ''); ?></textarea>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-6">
                                                <label>City</label>
                                                <input type="text" name="city" class="form-control" value="<?php echo htmlspecialchars($employee['city'] ?? ''); ?>">
                                            </div>
                                            <div class="form-group col-md-6">
                                                <label>State/Province</label>
                                                <input type="text" name="state" class="form-control" value="<?php echo htmlspecialchars($employee['state'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-6">
                                                <label>Postal Code</label>
                                                <input type="text" name="postal_code" class="form-control" value="<?php echo htmlspecialchars($employee['postal_code'] ?? ''); ?>">
                                            </div>
                                            <div class="form-group col-md-6">
                                                <label>Country</label>
                                                <input type="text" name="country" class="form-control" value="<?php echo htmlspecialchars($employee['country'] ?? ''); ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Employment Details -->
                            <div class="col-md-6">
                                <div class="card mb-4">
                                    <div class="card-header bg-success text-white">
                                        <h5 class="mb-0">Employment Details</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label>Department <span class="text-danger">*</span></label>
                                            <select name="department" class="form-control <?php echo (isset($errors['department'])) ? 'is-invalid' : ''; ?>" required>
                                                <option value="">Select Department</option>
                                                <option value="IT" <?php echo (isset($employee['department']) && $employee['department'] == 'IT') ? 'selected' : ''; ?>>IT</option>
                                                <option value="HR" <?php echo (isset($employee['department']) && $employee['department'] == 'HR') ? 'selected' : ''; ?>>Human Resources</option>
                                                <option value="Finance" <?php echo (isset($employee['department']) && $employee['department'] == 'Finance') ? 'selected' : ''; ?>>Finance</option>
                                                <option value="Marketing" <?php echo (isset($employee['department']) && $employee['department'] == 'Marketing') ? 'selected' : ''; ?>>Marketing</option>
                                                <option value="Operations" <?php echo (isset($employee['department']) && $employee['department'] == 'Operations') ? 'selected' : ''; ?>>Operations</option>
                                            </select>
                                            <div class="invalid-feedback"><?php echo $errors['department'] ?? 'Please select department'; ?></div>
                                        </div>
                                        <div class="form-group">
                                            <label>Position <span class="text-danger">*</span></label>
                                            <input type="text" name="position" class="form-control <?php echo (isset($errors['position'])) ? 'is-invalid' : ''; ?>" 
                                                   value="<?php echo htmlspecialchars($employee['position'] ?? ''); ?>" required>
                                            <div class="invalid-feedback"><?php echo $errors['position'] ?? 'Please enter position'; ?></div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-6">
                                                <label>Employment Type <span class="text-danger">*</span></label>
                                                <select name="employment_type" class="form-control <?php echo (isset($errors['employment_type'])) ? 'is-invalid' : ''; ?>" required>
                                                    <option value="">Select Type</option>
                                                    <option value="Full Time" <?php echo (isset($employee['employment_type']) && $employee['employment_type'] == 'Full Time') ? 'selected' : ''; ?>>Full Time</option>
                                                    <option value="Part Time" <?php echo (isset($employee['employment_type']) && $employee['employment_type'] == 'Part Time') ? 'selected' : ''; ?>>Part Time</option>
                                                    <option value="Contract" <?php echo (isset($employee['employment_type']) && $employee['employment_type'] == 'Contract') ? 'selected' : ''; ?>>Contract</option>
                                                    <option value="Temporary" <?php echo (isset($employee['employment_type']) && $employee['employment_type'] == 'Temporary') ? 'selected' : ''; ?>>Temporary</option>
                                                </select>
                                                <div class="invalid-feedback"><?php echo $errors['employment_type'] ?? 'Please select employment type'; ?></div>
                                            </div>
                                            <div class="form-group col-md-6">
                                                <label>Hire Date</label>
                                                <input type="date" name="hire_date" class="form-control" 
                                                       value="<?php echo !empty($employee['hire_date']) ? date('Y-m-d', strtotime($employee['hire_date'])) : ''; ?>">
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label>Salary</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">$</span>
                                                </div>
                                                <input type="text" name="salary" class="form-control" 
                                                       value="<?php echo !empty($employee['salary']) ? number_format($employee['salary']) : ''; ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Emergency Contact -->
                                <div class="card mb-4">
                                    <div class="card-header bg-warning">
                                        <h5 class="mb-0">Emergency Contact</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label>Contact Name</label>
                                            <input type="text" name="emergency_contact_name" class="form-control" 
                                                   value="<?php echo htmlspecialchars($employee['emergency_contact_name'] ?? ''); ?>">
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-6">
                                                <label>Phone</label>
                                                <input type="tel" name="emergency_contact_phone" class="form-control" 
                                                       value="<?php echo htmlspecialchars($employee['emergency_contact_phone'] ?? ''); ?>">
                                            </div>
                                            <div class="form-group col-md-6">
                                                <label>Relationship</label>
                                                <input type="text" name="emergency_contact_relation" class="form-control" 
                                                       value="<?php echo htmlspecialchars($employee['emergency_contact_relation'] ?? ''); ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Login Credentials -->
                                <div class="card">
                                    <div class="card-header bg-info text-white">
                                        <h5 class="mb-0">Login Credentials</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label>Username</label>
                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($employee['username']); ?>" disabled>
                                            <small class="form-text text-muted">Username cannot be changed</small>
                                        </div>
                                        <div class="form-group">
                                            <label>New Password</label>
                                            <input type="password" name="password" class="form-control <?php echo (isset($errors['password'])) ? 'is-invalid' : ''; ?>">
                                            <div class="invalid-feedback"><?php echo $errors['password'] ?? ''; ?></div>
                                            <small class="form-text text-muted">Leave blank to keep current password</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Update Employee
                            </button>
                            <a href="hr_manage_employees.php" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Format salary input
$(document).ready(function() {
    $('input[name="salary"]').on('input', function(e) {
        // Remove non-numeric characters
        let value = this.value.replace(/[^\d]/g, '');
        // Format with commas
        value = value.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        this.value = value;
    });
    
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
});
</script>

<?php include 'templates/footer.php'; ?>
