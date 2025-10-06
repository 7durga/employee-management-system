<?php
require_once '../config/config.php';

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'Super Admin'){
    header("location: ../login.php");
    exit;
}

// Handle delete action
if(isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id']) && isset($_GET['type'])) {
    $id = $_GET['id'];
    $type = $_GET['type'];
    
    try {
        if($type === 'hr') {
            // Delete from users table first (foreign key constraint)
            $sql = "DELETE FROM users WHERE employee_id = ? AND role = 'HR Admin'";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id]);
            
            // Then delete from hr_admins table
            $sql = "DELETE FROM hr_admins WHERE employee_id = ?";
        } else {
            // Delete from users table first (foreign key constraint)
            $sql = "DELETE FROM users WHERE employee_id = ? AND role = 'Employee'";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id]);
            
            // Then delete from employees table
            $sql = "DELETE FROM employees WHERE employee_id = ?";
        }
        
        $stmt = $pdo->prepare($sql);
        if($stmt->execute([$id])) {
            $_SESSION['success'] = ucfirst($type) . " profile deleted successfully!";
        }
    } catch(PDOException $e) {
        $_SESSION['error'] = "Error deleting profile: " . $e->getMessage();
    }
    header("location: manage_profiles.php");
    exit;
}

// Fetch all HR Admins
$hr_admins = [];
$sql = "SELECT e.*, u.username, 'active' as status 
        FROM employees e 
        JOIN users u ON e.employee_id = u.employee_id 
        WHERE u.role = 'HR Admin' 
        ORDER BY e.first_name, e.last_name";
$stmt = $pdo->query($sql);
if($stmt) {
    $hr_admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch all Employees
$employees = [];
$sql = "SELECT e.*, u.username, 'active' as status 
        FROM employees e 
        JOIN users u ON e.employee_id = u.employee_id 
        WHERE u.role = 'Employee' 
        ORDER BY e.first_name, e.last_name";
$stmt = $pdo->query($sql);
if($stmt) {
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Profiles - Employee Management System</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Custom CSS -->
    <link href="../assets/css/style.css" rel="stylesheet">
    
    <style>
        .profile-img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }
        .status-badge {
            font-size: 0.75rem;
        }
        .action-btns .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include 'templates/sidebar.php'; ?>

        <div id="content">
            <nav class="navbar navbar-expand-lg navbar-light bg-light">
                <div class="container-fluid">
                    <button type="button" id="sidebarCollapse" class="btn btn-info">
                        <i class="fas fa-align-left"></i>
                        <span>Toggle Sidebar</span>
                    </button>
                </div>
            </nav>

            <div class="container-fluid">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Manage Profiles</h1>
                </div>

                <?php if(isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php 
                        echo $_SESSION['success']; 
                        unset($_SESSION['success']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if(isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php 
                        echo $_SESSION['error']; 
                        unset($_SESSION['error']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- HR Admins Section -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-user-tie me-2"></i>HR Admins</h5>
                        <a href="add_hr_admin.php" class="btn btn-sm btn-light">
                            <i class="fas fa-plus"></i> Add HR Admin
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Photo</th>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($hr_admins)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center">No HR Admins found</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach($hr_admins as $hr): ?>
                                            <tr>
                                                <td>
                                                    <?php 
                                                    $profile_img = !empty($hr['profile_image']) ? '../uploads/profiles/' . $hr['profile_image'] : '../assets/img/default-avatar.png';
                                                    ?>
                                                    <img src="<?php echo $profile_img; ?>" alt="Profile" class="profile-img">
                                                </td>
                                                <td><?php echo htmlspecialchars($hr['employee_id']); ?></td>
                                                <td><?php echo htmlspecialchars($hr['first_name'] . ' ' . $hr['last_name']); ?></td>
                                                <td><?php echo htmlspecialchars($hr['email']); ?></td>
                                                <td><?php echo htmlspecialchars($hr['phone'] ?? 'N/A'); ?></td>
                                                <td>
                                                    <?php if($hr['status'] == 'active'): ?>
                                                        <span class="badge bg-success status-badge">Active</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger status-badge">Inactive</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="action-btns">
                                                    <a href="view_profile.php?type=hr&id=<?php echo $hr['employee_id']; ?>" 
                                                       class="btn btn-sm btn-info" title="View">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="edit_hr_admin.php?id=<?php echo $hr['employee_id']; ?>" 
                                                       class="btn btn-sm btn-warning" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="#" 
                                                       onclick="confirmDelete('<?php echo $hr['employee_id']; ?>', 'hr', '<?php echo $hr['first_name'] . ' ' . $hr['last_name']; ?>')" 
                                                       class="btn btn-sm btn-danger" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Employees Section -->
                <div class="card">
                    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-users me-2"></i>Employees</h5>
                        <a href="add_employee.php" class="btn btn-sm btn-light">
                            <i class="fas fa-plus"></i> Add Employee
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Photo</th>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Department</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($employees)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center">No Employees found</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach($employees as $emp): ?>
                                            <tr>
                                                <td>
                                                    <?php 
                                                    $profile_img = !empty($emp['profile_image']) ? '../uploads/profiles/' . $emp['profile_image'] : '../assets/img/default-avatar.png';
                                                    ?>
                                                    <img src="<?php echo $profile_img; ?>" alt="Profile" class="profile-img">
                                                </td>
                                                <td><?php echo htmlspecialchars($emp['employee_id']); ?></td>
                                                <td><?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?></td>
                                                <td><?php echo htmlspecialchars($emp['email']); ?></td>
                                                <td><?php echo htmlspecialchars($emp['department'] ?? 'N/A'); ?></td>
                                                <td>
                                                    <?php if($emp['status'] == 'active'): ?>
                                                        <span class="badge bg-success status-badge">Active</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger status-badge">Inactive</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="action-btns">
                                                    <a href="view_profile.php?type=employee&id=<?php echo $emp['employee_id']; ?>" 
                                                       class="btn btn-sm btn-info" title="View">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="edit_employee.php?id=<?php echo $emp['employee_id']; ?>" 
                                                       class="btn btn-sm btn-warning" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="#" 
                                                       onclick="confirmDelete('<?php echo $emp['employee_id']; ?>', 'employee', '<?php echo $emp['first_name'] . ' ' . $emp['last_name']; ?>')" 
                                                       class="btn btn-sm btn-danger" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete <span id="profileName" class="fw-bold"></span>?
                    <p class="text-danger mt-2">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="#" id="confirmDeleteBtn" class="btn btn-danger">Delete</a>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery, Popper.js, Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.10.2/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Toggle sidebar
        $('#sidebarCollapse').on('click', function() {
            $('#sidebar').toggleClass('active');
        });

        // Delete confirmation
        function confirmDelete(id, type, name) {
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            document.getElementById('profileName').textContent = name;
            document.getElementById('confirmDeleteBtn').href = `manage_profiles.php?action=delete&id=${id}&type=${type}`;
            deleteModal.show();
        }

        // Initialize tooltips
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
</body>
</html>