<?php
require_once '../config/config.php';

// Allow both Super Admin and HR Admin to add employees
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !in_array($_SESSION["role"], ['Super Admin', 'HR Admin'])){
    header("location: login.php");
    exit;
}

// Initialize variables
$first_name = $last_name = $email = $phone = $address = $city = $state = $postal_code = $country = '';
$hire_date = date('Y-m-d'); // Default to today's date
$username = $password = $department = $position = $employment_type = $salary = '';
$emergency_contact_name = $emergency_contact_phone = $emergency_contact_relation = '';
$errors = [];

if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Required fields validation
    $required_fields = [
        'first_name' => 'First Name',
        'last_name' => 'Last Name',
        'email' => 'Email',
        'phone' => 'Phone',
        'username' => 'Username',
        'password' => 'Password',
        'department' => 'Department',
        'position' => 'Position',
        'employment_type' => 'Employment Type',
        'salary' => 'Salary'
    ];
    
    foreach($required_fields as $field => $label) {
        if(empty(trim($_POST[$field] ?? ''))) {
            $errors[$field] = "$label is required.";
        }
    }

    // Specific validations
    if(!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)){
        $errors['email'] = "Invalid email format.";
    }
    if(strlen(trim($_POST['password'])) < 6){
        $errors['password'] = "Password must have at least 6 characters.";
    }

    // Validate email format
    if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Please enter a valid email address.";
    }

    // Check if username already exists
    $sql = "SELECT id FROM users WHERE username = :username";
    if($stmt = $pdo->prepare($sql)){
        $stmt->bindParam(":username", $_POST['username'], PDO::PARAM_STR);
        if($stmt->execute() && $stmt->rowCount() == 1){
            $errors['username'] = "This username is already taken.";
        }
        unset($stmt);
    }

    if(empty($errors)){
        $employee_id = 'EMP' . rand(1000, 9999);
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $address = $_POST['address'];
        $hire_date = $_POST['hire_date'];
        $username = $_POST['username'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        try {
            $pdo->beginTransaction();

            // Insert into employees table with only existing fields
            $sql1 = "INSERT INTO employees (
                employee_id, first_name, last_name, email, phone, 
                hire_date, department, position
            ) VALUES (
                :employee_id, :first_name, :last_name, :email, :phone, 
                :hire_date, :department, :position
            )";
            
            $stmt1 = $pdo->prepare($sql1);
            $stmt1->execute([
                ':employee_id' => $employee_id,
                ':first_name' => $_POST['first_name'],
                ':last_name' => $_POST['last_name'],
                ':email' => $_POST['email'],
                ':phone' => $_POST['phone'],
                ':hire_date' => $_POST['hire_date'],
                ':department' => $_POST['department'],
                ':position' => $_POST['position']
            ]);

            // Insert into users table
            $sql2 = "INSERT INTO users (username, password, role, employee_id) 
                    VALUES (:username, :password, 'Employee', :employee_id)";
            $stmt2 = $pdo->prepare($sql2);
            $stmt2->execute([
                ':username' => $_POST['username'],
                ':password' => password_hash($_POST['password'], PASSWORD_DEFAULT),
                ':employee_id' => $employee_id
            ]);

            $pdo->commit();
            $_SESSION['success_message'] = 'Employee added successfully!';
            header("location: manage_employees.php");
            exit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors['database'] = "Something went wrong. Please try again later. Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Employee</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .wrapper {
            display: flex;
            width: 100%;
            align-items: stretch;
        }
        #content {
            width: 100%;
            padding: 20px;
            min-height: 100vh;
            transition: all 0.3s;
        }
        @media (max-width: 768px) {
            #sidebar {
                margin-left: -250px;
            }
            #sidebar.active {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <?php include 'templates/sidebar.php'; ?>
        
        <!-- Page Content -->
        <div id="content">
            <nav class="navbar navbar-expand-lg navbar-light bg-light mb-4">
                <div class="container-fluid">
                    <button type="button" id="sidebarCollapse" class="btn btn-info">
                        <i class="fas fa-bars"></i>
                        <span>Toggle Sidebar</span>
                    </button>
                </div>
            </nav>
            
            <div class="container">
                <h2 class="mb-4">Add New Employee</h2>
        <?php if (!empty($errors['database'])): ?>
            <div class="alert alert-danger"><?php echo $errors['database']; ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
        <?php endif; ?>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="needs-validation" novalidate>
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
                                    <input type="text" name="first_name" class="form-control <?php echo (isset($errors['first_name'])) ? 'is-invalid' : ''; ?>" required>
                                    <div class="invalid-feedback"><?php echo $errors['first_name'] ?? 'Please enter first name'; ?></div>
                                </div>
                                <div class="form-group col-md-6">
                                    <label>Last Name <span class="text-danger">*</span></label>
                                    <input type="text" name="last_name" class="form-control <?php echo (isset($errors['last_name'])) ? 'is-invalid' : ''; ?>" required>
                                    <div class="invalid-feedback"><?php echo $errors['last_name'] ?? 'Please enter last name'; ?></div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control <?php echo (isset($errors['email'])) ? 'is-invalid' : ''; ?>" required>
                                <div class="invalid-feedback"><?php echo $errors['email'] ?? 'Please enter a valid email'; ?></div>
                            </div>
                            <div class="form-group">
                                <label>Phone <span class="text-danger">*</span></label>
                                <input type="tel" name="phone" class="form-control <?php echo (isset($errors['phone'])) ? 'is-invalid' : ''; ?>" required>
                                <div class="invalid-feedback"><?php echo $errors['phone'] ?? 'Please enter phone number'; ?></div>
                            </div>
                            <div class="form-group">
                                <label>Address</label>
                                <textarea name="address" class="form-control" rows="2"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label>City</label>
                                    <input type="text" name="city" class="form-control" value="<?php echo htmlspecialchars($_POST['city'] ?? ''); ?>">
                                </div>
                                <div class="form-group col-md-6">
                                    <label>State/Province</label>
                                    <input type="text" name="state" class="form-control" value="<?php echo htmlspecialchars($_POST['state'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label>Postal Code</label>
                                    <input type="text" name="postal_code" class="form-control" value="<?php echo htmlspecialchars($_POST['postal_code'] ?? ''); ?>">
                                </div>
                                <div class="form-group col-md-6">
                                    <label>Country</label>
                                    <input type="text" name="country" class="form-control" value="<?php echo htmlspecialchars($_POST['country'] ?? ''); ?>">
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
                                    <option value="IT" <?php echo (isset($_POST['department']) && $_POST['department'] == 'IT') ? 'selected' : ''; ?>>IT</option>
                                    <option value="HR" <?php echo (isset($_POST['department']) && $_POST['department'] == 'HR') ? 'selected' : ''; ?>>Human Resources</option>
                                    <option value="Finance" <?php echo (isset($_POST['department']) && $_POST['department'] == 'Finance') ? 'selected' : ''; ?>>Finance</option>
                                    <option value="Marketing" <?php echo (isset($_POST['department']) && $_POST['department'] == 'Marketing') ? 'selected' : ''; ?>>Marketing</option>
                                    <option value="Operations" <?php echo (isset($_POST['department']) && $_POST['department'] == 'Operations') ? 'selected' : ''; ?>>Operations</option>
                                </select>
                                <div class="invalid-feedback"><?php echo $errors['department'] ?? 'Please select department'; ?></div>
                            </div>
                            <div class="form-group">
                                <label>Position <span class="text-danger">*</span></label>
                                <input type="text" name="position" class="form-control <?php echo (isset($errors['position'])) ? 'is-invalid' : ''; ?>" required value="<?php echo htmlspecialchars($_POST['position'] ?? ''); ?>">
                                <div class="invalid-feedback"><?php echo $errors['position'] ?? 'Please enter position'; ?></div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label>Employment Type <span class="text-danger">*</span></label>
                                    <select name="employment_type" class="form-control <?php echo (isset($errors['employment_type'])) ? 'is-invalid' : ''; ?>" required>
                                        <option value="">Select Type</option>
                                        <option value="Full Time" <?php echo (isset($_POST['employment_type']) && $_POST['employment_type'] == 'Full Time') ? 'selected' : ''; ?>>Full Time</option>
                                        <option value="Part Time" <?php echo (isset($_POST['employment_type']) && $_POST['employment_type'] == 'Part Time') ? 'selected' : ''; ?>>Part Time</option>
                                        <option value="Contract" <?php echo (isset($_POST['employment_type']) && $_POST['employment_type'] == 'Contract') ? 'selected' : ''; ?>>Contract</option>
                                        <option value="Temporary" <?php echo (isset($_POST['employment_type']) && $_POST['employment_type'] == 'Temporary') ? 'selected' : ''; ?>>Temporary</option>
                                    </select>
                                    <div class="invalid-feedback"><?php echo $errors['employment_type'] ?? 'Please select employment type'; ?></div>
                                </div>
                                <div class="form-group col-md-6">
                                    <label>Hire Date <span class="text-danger">*</span></label>
                                    <input type="date" name="hire_date" class="form-control <?php echo (isset($errors['hire_date'])) ? 'is-invalid' : ''; ?>" required value="<?php echo $_POST['hire_date'] ?? date('Y-m-d'); ?>">
                                    <div class="invalid-feedback"><?php echo $errors['hire_date'] ?? 'Please select hire date'; ?></div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Salary <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">$</span>
                                    </div>
                                    <input type="text" name="salary" class="form-control <?php echo (isset($errors['salary'])) ? 'is-invalid' : ''; ?>" required value="<?php echo htmlspecialchars($_POST['salary'] ?? ''); ?>">
                                    <div class="invalid-feedback"><?php echo $errors['salary'] ?? 'Please enter salary'; ?></div>
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
                                <input type="text" name="emergency_contact_name" class="form-control" value="<?php echo htmlspecialchars($_POST['emergency_contact_name'] ?? ''); ?>">
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label>Phone</label>
                                    <input type="tel" name="emergency_contact_phone" class="form-control" value="<?php echo htmlspecialchars($_POST['emergency_contact_phone'] ?? ''); ?>">
                                </div>
                                <div class="form-group col-md-6">
                                    <label>Relationship</label>
                                    <input type="text" name="emergency_contact_relation" class="form-control" value="<?php echo htmlspecialchars($_POST['emergency_contact_relation'] ?? ''); ?>">
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
                                <label>Username <span class="text-danger">*</span></label>
                                <input type="text" name="username" class="form-control <?php echo (isset($errors['username'])) ? 'is-invalid' : ''; ?>" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                                <div class="invalid-feedback"><?php echo $errors['username'] ?? 'Please choose a username'; ?></div>
                            </div>
                            <div class="form-group">
                                <label>Password <span class="text-danger">*</span></label>
                                <input type="password" name="password" class="form-control <?php echo (isset($errors['password'])) ? 'is-invalid' : ''; ?>" required>
                                <div class="invalid-feedback"><?php echo $errors['password'] ?? 'Password must be at least 6 characters'; ?></div>
                                <small class="form-text text-muted">Minimum 6 characters required</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-group mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Employee
                </button>
                <a href="manage_profiles.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
            </div>
        </div>
    </div>

    <!-- jQuery, Popper.js, and Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
    <!-- Form validation -->
    <script>
    // Form validation
    (function() {
        'use strict';
        window.addEventListener('load', function() {
            // Fetch all the forms we want to apply custom Bootstrap validation styles to
            var forms = document.getElementsByClassName('needs-validation');
            // Loop over them and prevent submission
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
    
    // Format salary input
    document.addEventListener('DOMContentLoaded', function() {
        const salaryInput = document.querySelector('input[name="salary"]');
        if (salaryInput) {
            salaryInput.addEventListener('input', function(e) {
                // Remove non-numeric characters
                let value = this.value.replace(/[^\d]/g, '');
                // Format with commas
                value = value.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                this.value = value;
            });
        }
    });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
    <!-- Custom Script -->
    <script>
        $(document).ready(function () {
            // Toggle sidebar
            $('#sidebarCollapse').on('click', function () {
                $('#sidebar').toggleClass('active');
            });
        });
    </script>
</body>
</html>
