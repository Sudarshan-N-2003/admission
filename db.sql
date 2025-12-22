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





CREATE TABLE admin_users (
    id SERIAL PRIMARY KEY,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    role VARCHAR(30) DEFAULT 'OFFICE',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);



CREATE TABLE admissions (
    id SERIAL PRIMARY KEY,
    application_id VARCHAR(20) UNIQUE NOT NULL,

    student_name VARCHAR(100),
    gender VARCHAR(10),
    dob DATE,

    father_name VARCHAR(100),
    mother_name VARCHAR(100),

    mobile VARCHAR(15),
    guardian_mobile VARCHAR(15),
    email VARCHAR(100),

    state VARCHAR(50),
    category VARCHAR(20),
    sub_caste VARCHAR(50),

    prev_combination VARCHAR(50),
    prev_college TEXT,
    permanent_address TEXT,

    admission_through VARCHAR(20),

    cet_number VARCHAR(30),
    cet_rank VARCHAR(30),
    seat_allotted VARCHAR(30),
    allotted_branch VARCHAR(50),

    r2_files JSONB,

    document_status JSONB DEFAULT '{
      "marks_10": "",
      "marks_12": "",
      "study_certificate": "",
      "transfer_certificate": "",
      "photo": ""
    }',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);



CREATE EXTENSION IF NOT EXISTS pgcrypto;

INSERT INTO admin_users (email, password_hash)
VALUES (
  'admin@vvit.ac.in',
  crypt('Vvit@123', gen_salt('bf'))
);



