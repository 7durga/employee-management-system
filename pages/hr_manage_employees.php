<?php
require_once '../config/config.php';

// Only HR Admin can access this page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'HR Admin'){
    header("location: login.php");
    exit;
}

// Handle delete operation
if(isset($_POST["delete"]) && !empty($_POST["employee_id"])){
    $employee_id = trim($_POST["employee_id"]);
    
    try {
        $pdo->beginTransaction();
        
        // Delete from users table first (due to foreign key constraint)
        $sql = "DELETE FROM users WHERE employee_id = :employee_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':employee_id' => $employee_id]);
        
        // Then delete from employees table
        $sql = "DELETE FROM employees WHERE employee_id = :employee_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':employee_id' => $employee_id]);
        
        $pdo->commit();
        $_SESSION['success_message'] = 'Employee deleted successfully';
    } catch(PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error_message'] = 'Error deleting employee: ' . $e->getMessage();
    }
    
    header("location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Fetch all employees
$sql = "SELECT e.*, u.username, u.role, 'active' as status 
        FROM employees e 
        JOIN users u ON e.employee_id = u.employee_id 
        WHERE u.role = 'Employee' 
        ORDER BY e.first_name, e.last_name";
$employees = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Manage Employees";
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
                <h1 class="h3">Manage Employees</h1>
                <a href="add_employee.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Add New Employee
                </a>
            </div>

            <?php include 'templates/messages.php'; ?>

            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="employeesTable">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Department</th>
                                    <th>Position</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($employees)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center">No employees found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach($employees as $employee): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($employee['employee_id']); ?></td>
                                            <td>
                                                <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($employee['email']); ?></td>
                                            <td><?php echo htmlspecialchars($employee['phone'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($employee['department'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($employee['position'] ?? 'N/A'); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $employee['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                    <?php echo ucfirst($employee['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="view_employee.php?id=<?php echo $employee['employee_id']; ?>" 
                                                       class="btn btn-sm btn-info me-1" 
                                                       title="View">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="edit_employee.php?id=<?php echo $employee['employee_id']; ?>" 
                                                       class="btn btn-sm btn-warning me-1" 
                                                       title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form method="post" style="display:inline;" 
                                                          onsubmit="return confirm('Are you sure you want to delete this employee? This action cannot be undone.');">
                                                        <input type="hidden" name="employee_id" value="<?php echo $employee['employee_id']; ?>">
                                                        <button type="submit" name="delete" class="btn btn-sm btn-danger" title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
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
    </div>
</div>

<!-- DataTables JS -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

<!-- Sidebar Toggle and DataTables Initialization -->
<script>
$(document).ready(function() {
    // Toggle sidebar
    $('#sidebarCollapse').on('click', function() {
        $('#sidebar').toggleClass('active');
    });
    
    // Initialize DataTable
    if ($.fn.DataTable.isDataTable('#employeesTable')) {
        $('#employeesTable').DataTable().destroy();
    }
    
    $('#employeesTable').DataTable({
        "pageLength": 10,
        "order": [[1, 'asc']], // Sort by name by default
        "responsive": true,
        "language": {
            "search": "Search:",
            "lengthMenu": "Show _MENU_ entries",
            "info": "Showing _START_ to _END_ of _TOTAL_ entries",
            "infoEmpty": "No entries found",
            "paginate": {
                "first": "First",
                "last": "Last",
                "next": "Next",
                "previous": "Previous"
            }
        }
    });
});
</script>

<?php include 'templates/footer.php'; ?>
