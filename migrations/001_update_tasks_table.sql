-- This migration updates the 'tasks' table to allow assigning tasks to any user (HR Admins or Employees)
-- by using the user's ID instead of the employee ID.

-- Step 1: Add a new column to store the user ID.
ALTER TABLE `tasks` ADD `assigned_to_user_id` INT NULL AFTER `description`;

-- Step 2: Populate the new column by matching the old employee_id with the user's ID.
-- This ensures existing task assignments are not lost.
UPDATE `tasks` t
JOIN `users` u ON t.assigned_to = u.employee_id
SET t.assigned_to_user_id = u.id;

-- Step 3: Remove the old 'assigned_to' column as it's now replaced by 'assigned_to_user_id'.
ALTER TABLE `tasks` DROP COLUMN `assigned_to`;

-- Step 4: Rename the new column to 'assigned_to' for consistency.
ALTER TABLE `tasks` CHANGE `assigned_to_user_id` `assigned_to` INT NOT NULL;

-- Step 5: Add an index for better performance on task lookups.
ALTER TABLE `tasks` ADD INDEX (`assigned_to`);
