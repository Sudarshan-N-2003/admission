-- use/create database as per your Neon setup (Neon provides a database)
-- Create tables for submissions and serial counters

CREATE TABLE IF NOT EXISTS serial_counters (
  year INTEGER PRIMARY KEY,
  last_serial INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS submissions (
  id VARCHAR(32) PRIMARY KEY,            -- e.g. 1VJ25001
  student_name VARCHAR(255) NOT NULL,
  email VARCHAR(255),
  mobile VARCHAR(50),
  data JSONB NOT NULL,                    -- full form data
  files JSONB,                            -- file paths or S3 URLs
  submitted_documents JSONB,              -- submitted-date map
  created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT (now() AT TIME ZONE 'UTC'),
  email_sent BOOLEAN DEFAULT FALSE
);

-- optional index for search
CREATE INDEX IF NOT EXISTS ix_submissions_student_name ON submissions ( (data->>'student_name') );
