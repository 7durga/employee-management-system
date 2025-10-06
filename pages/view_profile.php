<?php
require_once '../config/config.php';

// Check if user is logged in and has appropriate role
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
}

// Check if id and type are provided
if(!isset($_GET['id']) || !isset($_GET['type'])) {
    $_SESSION['error'] = "Invalid request.";
    header("location: manage_profiles.php");
    exit;
}

$id = $_GET['id'];
$type = $_GET['type'];
$profile = [];

// Set page title based on profile type
$page_title = ($type === 'hr') ? 'HR Admin Profile' : 'Employee Profile';

// Include header
include_once 'templates/header.php';

try {
    $sql = "SELECT e.*, u.username, u.role, 'active' as status 
            FROM employees e 
            JOIN users u ON e.employee_id = u.employee_id 
            WHERE e.employee_id = ? AND u.role = ?";
    
    $role = ($type === 'hr') ? 'HR Admin' : 'Employee';
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id, $role]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if(!$profile) {
        throw new Exception("Profile not found.");
    }
    
} catch(PDOException $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
    header("location: manage_profiles.php");
    exit;
} catch(Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header("location: manage_profiles.php");
    exit;
}
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include 'templates/sidebar.php'; ?>
        <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Employee</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-4 border-bottom">
                <h1 class="h3">
                    <i class="fas fa-user-circle me-2"></i><?php echo $page_title; ?>
                    <span class="badge bg-<?php echo $type === 'hr' ? 'info' : 'primary'; ?> ms-2">
                        <?php echo strtoupper($type); ?>
                    </span>
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="manage_profiles.php" class="btn btn-outline-secondary me-2">
                    </a>
                    <a href="edit_<?php echo $type; ?>.php?id=<?php echo $id; ?>" class="btn btn-primary">
                        <i class="fas fa-edit me-1"></i> Edit Profile
                    </a>
                </div>
            </div>
                    <!-- Profile Header -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <!-- Profile Picture -->
                                <div class="col-md-3 text-center">
                                    <?php 
                                    $profile_image = !empty($profile['profile_image']) 
                                        ? '../uploads/profiles/' . $profile['profile_image'] 
                                        : 'https://ui-avatars.com/api/?name=' . urlencode($profile['first_name'] . '+' . $profile['last_name']) . '&background=random&size=200';
                                    ?>
                                    <div class="position-relative d-inline-block">
                                        <img src="<?php echo $profile_image; ?>" 
                                             alt="Profile Image" 
                                             class="img-thumbnail rounded-circle border-primary" 
                                             style="width: 150px; height: 150px; object-fit: cover;">
                                        <span class="position-absolute bottom-0 end-0 bg-<?php echo ($profile['status'] ?? 'active') === 'active' ? 'success' : 'danger'; ?> p-2 rounded-circle border border-3 border-white">
                                            <i class="fas fa-circle"></i>
                                        </span>
                                    </div>
                                    <h4 class="mt-3 mb-0"><?php echo htmlspecialchars($profile['first_name'] . ' ' . $profile['last_name']); ?></h4>
                                    <p class="text-muted"><?php echo htmlspecialchars($profile['position'] ?? 'No position specified'); ?></p>
                                    <div class="d-flex justify-content-center gap-2">
                                        <a href="mailto:<?php echo htmlspecialchars($profile['email']); ?>" class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="Send Email">
                                            <i class="fas fa-envelope"></i>
                                        </a>
                                        <a href="tel:<?php echo htmlspecialchars($profile['phone_number'] ?? ''); ?>" class="btn btn-sm btn-outline-success" data-bs-toggle="tooltip" title="Call">
                                            <i class="fas fa-phone"></i>
                                        </a>
                                        <a href="#" class="btn btn-sm btn-outline-info" data-bs-toggle="tooltip" title="Send Message">
                                            <i class="fas fa-comment"></i>
                                        </a>
                                    </div>
                                </div>
                                
                                <!-- Profile Details -->
                                <div class="col-md-9">
                                    <div class="row g-3">
                                        <!-- Personal Information -->
                                        <div class="col-md-6">
                                            <div class="card h-100">
                                                <div class="card-header bg-light">
                                                    <h6 class="text-uppercase text-muted mb-0">Personal Information</h6>
                                                </div>
                                                <div class="card-body">
                                                    <dl class="mb-0">
                                                        <dt class="small text-muted mb-1">Employee ID</dt>
                                                        <dd class="mb-3"><?php echo htmlspecialchars($profile['employee_id']); ?></dd>
                                                        
                                                        <dt class="small text-muted mb-1">Full Name</dt>
                                                        <dd class="mb-3"><?php echo htmlspecialchars($profile['first_name'] . ' ' . $profile['last_name']); ?></dd>
                                                        
                                                        <dt class="small text-muted mb-1">Email</dt>
                                                        <dd class="mb-3">
                                                            <a href="mailto:<?php echo htmlspecialchars($profile['email']); ?>" class="text-decoration-none">
                                                                <i class="fas fa-envelope me-2 text-primary"></i><?php echo htmlspecialchars($profile['email']); ?>
                                                            </a>
                                                        </dd>
                                                        
                                                        <dt class="small text-muted mb-1">Phone</dt>
                                                        <dd class="mb-0">
                                                            <?php if (!empty($profile['phone_number'])): ?>
                                                                <a href="tel:<?php echo htmlspecialchars($profile['phone_number']); ?>" class="text-decoration-none">
                                                                    <i class="fas fa-phone me-2 text-success"></i><?php echo htmlspecialchars($profile['phone_number']); ?>
                                                                </a>
                                                            <?php else: ?>
                                                                <span class="text-muted">N/A</span>
                                                            <?php endif; ?>
                                                        </dd>
                                                    </dl>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Employment Details -->
                                        <div class="col-md-6">
                                            <div class="card h-100">
                                                <div class="card-header bg-light">
                                                    <h6 class="text-uppercase text-muted mb-0">Employment Details</h6>
                                                </div>
                                                <div class="card-body">
                                                    <dl class="mb-0">
                                                        <dt class="small text-muted mb-1">Department</dt>
                                                        <dd class="mb-3"><?php echo !empty($profile['department']) ? htmlspecialchars($profile['department']) : 'N/A'; ?></dd>
                                                        
                                                        <dt class="small text-muted mb-1">Position</dt>
                                                        <dd class="mb-3"><?php echo !empty($profile['position']) ? htmlspecialchars($profile['position']) : 'N/A'; ?></dd>
                                                        
                                                        <dt class="small text-muted mb-1">Employment Type</dt>
                                                        <dd class="mb-3">
                                                            <span class="badge bg-<?php echo ($profile['employment_type'] ?? '') === 'Full Time' ? 'primary' : 'info'; ?>">
                                                                <?php echo !empty($profile['employment_type']) ? htmlspecialchars($profile['employment_type']) : 'N/A'; ?>
                                                            </span>
                                                        </dd>
                                                        
                                                        <dt class="small text-muted mb-1">Hire Date</dt>
                                                        <dd class="mb-0">
                                                            <?php if (!empty($profile['hire_date'])): ?>
                                                                <i class="far fa-calendar-alt me-2 text-primary"></i>
                                                                <?php echo date('d M, Y', strtotime($profile['hire_date'])); ?>
                                                                <small class="text-muted">
                                                                    (<?php echo floor((time() - strtotime($profile['hire_date'])) / (365*60*60*24)); ?> days)
                                                                </small>
                                                            <?php else: ?>
                                                                N/A
                                                            <?php endif; ?>
                                                        </dd>
                                                    </dl>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <a href="edit_<?php echo $type; ?>.php?id=<?php echo $id; ?>" class="btn btn-primary">
                        <i class="fas fa-edit"></i> Edit Profile
                    </a>
                    <a href="#" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                        <i class="fas fa-trash-alt"></i> Delete
                    </a>
                </div>
            </div>
        </main>
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
                <p>Are you sure you want to delete this <?php echo $type; ?> profile? This action cannot be undone.</p>
                <p class="text-danger"><strong>Warning:</strong> This will permanently delete all data associated with this profile.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="manage_profiles.php?action=delete&id=<?php echo $id; ?>&type=<?php echo $type; ?>" class="btn btn-danger">
                    <i class="fas fa-trash-alt"></i> Delete Permanently
                </a>
            </div>
        </div>
    </div>
</div>

<?php include_once 'templates/footer.php'; ?>
