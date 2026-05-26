-- SQL migration to add optional due date, priority, and category fields to the tasks table

ALTER TABLE tasks
    ADD COLUMN due_date DATE NULL AFTER description,
    ADD COLUMN priority ENUM('Low','Medium','High') NOT NULL DEFAULT 'Medium' AFTER due_date,
    ADD COLUMN category VARCHAR(64) NOT NULL DEFAULT 'Personal' AFTER priority;
