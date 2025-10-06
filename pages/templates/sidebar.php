<?php
$current_page = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['role'];
?>

<nav id="sidebar" class="bg-dark text-white">
    <div class="sidebar-header">
        <h3 class="text-center"><?php echo htmlspecialchars($role); ?></h3>
    </div>

    <ul class="nav flex-column px-2">
        <li class="nav-item mb-3">
            <div class="text-center">
                <i class="fas fa-user-circle fa-3x mb-2"></i>
                <p class="mb-0">Welcome,</p>
                <p class="fw-bold"><?php echo htmlspecialchars($_SESSION['username']); ?></p>
            </div>
        </li>

        <?php if ($role == 'Super Admin'): ?>
            <li class="nav-item <?php echo ($current_page == 'super_admin_dashboard.php') ? 'active' : ''; ?>">
                <a href="super_admin_dashboard.php" class="nav-link">
                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                </a>
            </li>
            <li class="nav-item <?php echo ($current_page == 'manage_hr_admins.php') ? 'active' : ''; ?>">
                <a href="manage_hr_admins.php" class="nav-link">
                    <i class="fas fa-user-shield me-2"></i>Manage HR Admins
                </a>
            </li>
            <li class="nav-item <?php echo ($current_page == 'manage_hr_tasks.php') ? 'active' : ''; ?>">
                <a href="manage_hr_tasks.php" class="nav-link">
                    <i class="fas fa-tasks me-2"></i>Assign HR Tasks
                </a>
            </li>
            <li class="nav-item <?php echo ($current_page == 'manage_profiles.php') ? 'active' : ''; ?>">
                <a href="manage_profiles.php" class="nav-link">
                    <i class="fas fa-users-cog me-2"></i>Manage Profiles
                </a>
            </li>
            <li class="nav-item <?php echo ($current_page == 'add_employee.php') ? 'active' : ''; ?>">
                <a href="add_employee.php" class="nav-link">
                    <i class="fas fa-user-plus me-2"></i>Add Employee
                </a>
            </li>
            <li class="nav-item <?php echo ($current_page == 'attendance_report.php') ? 'active' : ''; ?>">
                <a href="attendance_report.php" class="nav-link">
                    <i class="fas fa-calendar-check me-2"></i>Attendance Report
                </a>
            </li>

        <?php elseif ($role == 'HR Admin'): ?>
            <li class="nav-item <?php echo ($current_page == 'hr_admin_dashboard.php') ? 'active' : ''; ?>">
                <a href="hr_admin_dashboard.php" class="nav-link">
                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                </a>
            </li>

            <li class="nav-item <?php echo ($current_page == 'hr_manage_employees.php') ? 'active' : ''; ?>">
                <a href="hr_manage_employees.php" class="nav-link">
                    <i class="fas fa-users-cog me-2"></i>Manage Employees
                </a>
            </li>
            <li class="nav-item <?php echo ($current_page == 'manage_attendance.php') ? 'active' : ''; ?>">
                <a href="manage_attendance.php" class="nav-link">
                    <i class="fas fa-calendar-check me-2"></i>Attendance
                </a>
            </li>

            <li class="nav-item <?php echo ($current_page == 'assign_task.php') ? 'active' : ''; ?>">
                <a href="assign_task.php" class="nav-link">
                    <i class="fas fa-plus-circle me-2"></i>Assign Task
                </a>
            </li>
            <li class="nav-item <?php echo ($current_page == 'manage_tasks.php') ? 'active' : ''; ?>">
                <a href="manage_tasks.php" class="nav-link">
                    <i class="fas fa-tasks me-2"></i>Manage Tasks
                </a>
            </li>
            <li class="nav-item <?php echo ($current_page == 'punch_in_out.php') ? 'active' : ''; ?>">
                <a href="punch_in_out.php" class="nav-link">
                    <i class="fas fa-fingerprint me-2"></i>Punch In/Out
                </a>
            </li>

        <?php elseif ($role == 'Employee'): ?>
            <li class="nav-item <?php echo ($current_page == 'employee_dashboard.php') ? 'active' : ''; ?>">
                <a href="employee_dashboard.php" class="nav-link">
                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                </a>
            </li>
            <li class="nav-item <?php echo ($current_page == 'punch_in_out.php') ? 'active' : ''; ?>">
                <a href="punch_in_out.php" class="nav-link">
                    <i class="fas fa-fingerprint me-2"></i>Punch In/Out
                </a>
            </li>
        <?php endif; ?>

        <!-- Common menu items for all roles -->
        <?php if ($role != 'Super Admin'): ?>
        <li class="nav-item mt-auto <?php echo (in_array($current_page, ['profile.php', 'edit_profile.php'])) ? 'active' : ''; ?>">
            <a href="profile.php" class="nav-link">
                <i class="fas fa-user-edit me-2"></i>My Profile
            </a>
        </li>
        <?php endif; ?>
        <li class="nav-item">
            <a href="logout.php" class="nav-link text-danger">
                <i class="fas fa-sign-out-alt me-2"></i>Logout
            </a>
        </li>
    </ul>
</nav>

<!-- Include CSS and JS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="../assets/css/style.css" rel="stylesheet">
        </li>
    </ul>
</nav>
