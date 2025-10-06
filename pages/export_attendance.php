<?php
require_once '../config/config.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || ($_SESSION["role"] !== 'HR Admin' && $_SESSION["role"] !== 'Super Admin')) {
    header("location: login.php");
    exit;
}

// Get filter parameters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
$employee_id = isset($_GET['employee_id']) ? $_GET['employee_id'] : '';

// Build the query
$sql = "SELECT a.*, e.first_name, e.last_name, e.employee_id as emp_id 
        FROM attendance a 
        JOIN employees e ON a.employee_id = e.employee_id 
        WHERE a.date BETWEEN :start_date AND :end_date";

$params = [':start_date' => $start_date, ':end_date' => $end_date];

if (!empty($employee_id)) {
    $sql .= " AND a.employee_id = :employee_id";
    $params[':employee_id'] = $employee_id;
}

$sql .= " ORDER BY a.date DESC, e.first_name, e.last_name";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$attendance_records = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Set headers for download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=attendance_report_' . date('Y-m-d') . '.csv');

// Create a file pointer connected to the output stream
$output = fopen('php://output', 'w');

// Output the column headings
fputcsv($output, array('Date', 'Employee Name', 'Employee ID', 'Punch In', 'Punch Out', 'Working Hours', 'Status'));

// Output each row of the data
foreach ($attendance_records as $record) {
    if ($record['punch_in_time'] && $record['punch_out_time']) {
        $status = 'Present';
        
        // Calculate working hours
        $punch_in = new DateTime($record['punch_in_time']);
        $punch_out = new DateTime($record['punch_out_time']);
        $interval = $punch_in->diff($punch_out);
        $working_hours = $interval->format('%H:%I');
    } elseif ($record['punch_in_time']) {
        $status = 'Incomplete';
        $working_hours = '--:--';
    } else {
        $status = 'Absent';
        $working_hours = '--:--';
    }
    
    fputcsv($output, array(
        date('M d, Y', strtotime($record['date'])),
        $record['first_name'] . ' ' . $record['last_name'],
        $record['emp_id'],
        $record['punch_in_time'] ? date('h:i A', strtotime($record['punch_in_time'])) : '--:--',
        $record['punch_out_time'] ? date('h:i A', strtotime($record['punch_out_time'])) : '--:--',
        $working_hours,
        $status
    ));
}

fclose($output);
exit;
?>
