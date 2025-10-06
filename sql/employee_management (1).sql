-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 06, 2025 at 11:41 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `employee_management`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `employee_id` varchar(20) NOT NULL,
  `punch_in_time` datetime DEFAULT NULL,
  `punch_out_time` datetime DEFAULT NULL,
  `date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `employee_id`, `punch_in_time`, `punch_out_time`, `date`) VALUES
(1, 'HR68DF5ADC10B62', '2025-10-03 14:35:04', '2025-10-03 18:02:34', '2025-10-03'),
(2, 'EMP9487', '2025-10-03 11:26:29', '2025-10-03 18:14:41', '2025-10-03'),
(3, 'EMP9487', '2025-10-04 09:32:50', NULL, '2025-10-04'),
(4, 'HR68DF5ADC10B62', '2025-10-06 12:33:17', NULL, '2025-10-06'),
(5, 'EMP9487', '2025-10-06 12:35:08', NULL, '2025-10-06');

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `employee_id` varchar(20) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `hire_date` date NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `employee_id`, `first_name`, `last_name`, `email`, `phone`, `address`, `hire_date`, `department`, `position`) VALUES
(1, 'HR68DF5ADC10B62', 'Durga', 'Rao', 'd@gmail.com', '12394568', NULL, '2025-10-03', NULL, NULL),
(2, 'EMP9487', 'Kranthi', 'k', 'k@gmail.com', '7896542130', 'hyhderabad', '2025-02-03', NULL, NULL),
(3, 'HRSS7226', 'Sravani', 's', 's@gmail.com', '123456688989', NULL, '2025-10-06', 'ddcdvc', 'dcdvcd');

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `assigned_to` int(11) NOT NULL,
  `assigned_by` int(11) NOT NULL,
  `due_date` date NOT NULL,
  `status` enum('Pending','In Progress','Completed') NOT NULL DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tasks`
--

INSERT INTO `tasks` (`id`, `title`, `description`, `assigned_to`, `assigned_by`, `due_date`, `status`) VALUES
(1, 'Web Development', 'is work with kranthi and sravani', 2, 1, '2025-10-04', 'Completed'),
(2, 'Web Devlopment ', 'is work with  two employees kranthi and sravbani', 2, 1, '2025-10-17', 'In Progress'),
(3, 'Web Developement', 'Kranth and srevani', 0, 0, '2025-10-31', 'Pending'),
(4, 'efsegdfv', 'dffdbfgb', 2, 1, '2025-10-22', 'Pending'),
(5, 'sdxscsdvsd', 'asdcadvsdvcs', 0, 0, '2025-10-30', 'In Progress');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('Super Admin','HR Admin','Employee') NOT NULL,
  `employee_id` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `employee_id`) VALUES
(1, 'superadmin', '$2y$10$b9kDCPFOmUxfS10kY8JeBe.QaBQO1ZweiGxAzdN8tMAj5sjymTh.m', 'Super Admin', NULL),
(2, 'Durga', '$2y$10$r8JbbNnKaLXF03Zfe01/cOOCqB/UWw7d6qMYsj3tu0.L5O/moMQfa', 'HR Admin', 'HR68DF5ADC10B62'),
(3, 'Kranthi', '$2y$10$DjQuRoqSh6WRADHujyo30eJDbF7ojUleeSN7QTLv2cOYFkMEo2x0S', 'Employee', 'EMP9487'),
(4, 'Sravani', '$2y$10$6bIC.fv/q.HfJO0lmXLb/eKqS8Q3/zgucwMiZjGCokvca0Dbkvpiy', 'HR Admin', 'HRSS7226');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_id` (`employee_id`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assigned_to` (`assigned_to`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
