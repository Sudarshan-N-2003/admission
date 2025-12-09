-- create database (if not exists)
CREATE DATABASE IF NOT EXISTS vvit_admission CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE vvit_admission;

-- submissions table
CREATE TABLE submissions (
  id VARCHAR(20) NOT NULL PRIMARY KEY,        -- example: 1VJ25001
  student_name VARCHAR(255) NOT NULL,
  email VARCHAR(255) DEFAULT NULL,
  mobile VARCHAR(32) DEFAULT NULL,
  data JSON NOT NULL,                         -- full form data
  files JSON DEFAULT NULL,                    -- stored file paths or S3 URLs (JSON object)
  submitted_documents JSON DEFAULT NULL,      -- document received dates
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  email_sent TINYINT(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- serial counters table (one row per year)
CREATE TABLE serial_counters (
  year INT NOT NULL PRIMARY KEY,
  last_serial INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- index to help searching by student_name
CREATE INDEX ix_submissions_student_name ON submissions(student_name(100));
