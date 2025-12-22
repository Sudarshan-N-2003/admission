-- Run this in Neon SQL editor
CREATE TABLE IF NOT EXISTS serial_counters (
year INTEGER PRIMARY KEY,
last_serial INTEGER NOT NULL DEFAULT 0
);


CREATE TABLE IF NOT EXISTS submissions (
id VARCHAR(32) PRIMARY KEY,
student_name VARCHAR(255) NOT NULL,
email VARCHAR(255),
mobile VARCHAR(50),
data JSONB NOT NULL,
files JSONB,
submitted_documents JSONB,
created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT (now() AT TIME ZONE 'UTC'),
email_sent BOOLEAN DEFAULT FALSE
);


CREATE INDEX IF NOT EXISTS ix_submissions_student_name ON submissions ((data->>'student_name'));






ALTER TABLE admissions
ALTER COLUMN document_status
SET DEFAULT '{
  "marks_10": "",
  "marks_12": "",
  "study_certificate": "",
  "transfer_certificate": "",
  "photo": ""
}';