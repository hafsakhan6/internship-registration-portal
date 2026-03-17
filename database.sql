-- ============================================================
-- PART C: DATABASE DESIGN (MySQL)
-- University Internship Registration Portal
-- ============================================================

CREATE DATABASE IF NOT EXISTS internship_portal;
USE internship_portal;

CREATE TABLE IF NOT EXISTS students (
    id          INT AUTO_INCREMENT PRIMARY KEY,          -- surrogate PK
    student_id  VARCHAR(20)    NOT NULL UNIQUE,          -- e.g. FA21-BCS-001
    full_name   VARCHAR(100)   NOT NULL,
    email       VARCHAR(150)   NOT NULL UNIQUE,
    password    VARCHAR(255)   NOT NULL,                 -- bcrypt hash
    cnic        VARCHAR(15)    NOT NULL UNIQUE,          -- 12345-1234567-1
    phone       VARCHAR(12)    NOT NULL,                 -- 03XXXXXXXXX
    cgpa        DECIMAL(3,2)   NOT NULL,
    department  VARCHAR(100)   NOT NULL,
    resume_path VARCHAR(255)   NOT NULL,
    created_at  DATETIME       DEFAULT CURRENT_TIMESTAMP,

    -- CHECK constraints (MySQL 8.0.16+)
    CONSTRAINT chk_cgpa     CHECK (cgpa >= 0.00 AND cgpa <= 4.00),
    CONSTRAINT chk_phone    CHECK (phone REGEXP '^03[0-9]{9}$'),
    CONSTRAINT chk_cnic     CHECK (cnic REGEXP '^[0-9]{5}-[0-9]{7}-[0-9]$'),
    CONSTRAINT chk_sid      CHECK (student_id REGEXP '^[A-Z]{2}[0-9]{2}-[A-Z]{3}-[0-9]{3}$')
);