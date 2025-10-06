# Role-Based Employee Management System

This is a comprehensive role-based admin panel website built with PHP and MySQL. It provides a secure and efficient way to manage employees, track attendance, and assign tasks, with different levels of access for Super Admins, HR Admins, and Employees.

## Features

- **Three User Roles**: Super Admin, HR Admin, and Employee.
- **Secure Authentication**: Login/logout, password hashing, and session management.
- **Role-Based Access Control**: Users can only access features permitted for their role.

### Super Admin
- Full access to the system.
- Manage HR Admin accounts (Create, Read, Update, Delete).
- View monthly attendance reports for all employees.
- View monthly task completion reports.

### HR Admin
- Manage employee accounts (Create, Read, Update, Delete).
- Assign tasks to employees.
- Manage and view daily attendance records.

### Employee
- Login with unique credentials.
- Mark daily attendance with "Punch In" and "Punch Out".
- View assigned tasks.
- Update the status of their tasks (Pending, In Progress, Completed).

## Technology Stack

- **Backend**: PHP (OOP with PDO)
- **Database**: MySQL
- **Frontend**: HTML, CSS, Bootstrap, JavaScript/jQuery

## Setup Instructions

1.  **Database Setup**:
    - Make sure you have a MySQL server running (e.g., via XAMPP, WAMP).
    - Open a MySQL client (like phpMyAdmin) and import the `sql/db.sql` file to create the `employee_management` database and its tables.

2.  **Configuration**:
    - Open the `config/config.php` file.
    - Update the database credentials (`DB_SERVER`, `DB_USERNAME`, `DB_PASSWORD`, `DB_NAME`) to match your local environment.

3.  **Run the Application**:
    - Place the project folder in your web server's root directory (e.g., `htdocs` for XAMPP).
    - Open your web browser and navigate to the project's URL (e.g., `http://localhost/Emp/`).

4.  **Default Login**:
    - You will need to create a Super Admin account directly in the `users` table to get started. You can do this by inserting a new record with the role set to 'Super Admin'. Remember to hash the password using PHP's `password_hash()` function.

