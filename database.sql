-- IPMC website/admin database schema
-- Assumptions:
-- 1. MySQL 8+ or MariaDB 10.4+.
-- 2. The application uses student_id as the public student identifier.
-- 3. Deleting a student should also delete that student's results and grievances.
-- 4. Admin users are local database users, not external SSO identities.

CREATE DATABASE IF NOT EXISTS school_system
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE school_system;

CREATE TABLE IF NOT EXISTS admin_users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE,
  email VARCHAR(191) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS students (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id VARCHAR(32) NOT NULL UNIQUE,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  email VARCHAR(191) NOT NULL UNIQUE,
  phone VARCHAR(50) NOT NULL,
  program VARCHAR(191) NOT NULL,
  password VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_students_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS contacts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  email VARCHAR(191) NOT NULL,
  phone VARCHAR(50) NOT NULL,
  program VARCHAR(191) NULL,
  campus VARCHAR(100) NULL,
  message TEXT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_contacts_created_at (created_at),
  INDEX idx_contacts_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS courses (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  course_code VARCHAR(50) NOT NULL UNIQUE,
  course_name VARCHAR(191) NOT NULL,
  category VARCHAR(80) NOT NULL,
  description TEXT NULL,
  duration VARCHAR(100) NULL,
  fee DECIMAL(10,2) NULL,
  status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  display_order INT NOT NULL DEFAULT 0,
  show_in_contact_dropdown TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_courses_status_contact (status, show_in_contact_dropdown),
  INDEX idx_courses_category (category),
  INDEX idx_courses_display_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS results (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id VARCHAR(32) NOT NULL,
  course_code VARCHAR(50) NOT NULL,
  course_name VARCHAR(191) NOT NULL,
  semester VARCHAR(50) NOT NULL,
  academic_year VARCHAR(20) NOT NULL,
  grade VARCHAR(5) NOT NULL,
  credits INT UNSIGNED NOT NULL,
  score DECIMAL(5,2) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_result_student_course_term (student_id, course_code, semester, academic_year),
  INDEX idx_results_student_id (student_id),
  INDEX idx_results_created_at (created_at),
  CONSTRAINT fk_results_student
    FOREIGN KEY (student_id) REFERENCES students(student_id)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS grievances (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id VARCHAR(32) NOT NULL,
  category VARCHAR(80) NOT NULL,
  title VARCHAR(191) NOT NULL,
  description TEXT NOT NULL,
  status ENUM('pending', 'replied', 'in_progress', 'under_review', 'resolved') NOT NULL DEFAULT 'pending',
  admin_response TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_grievances_student_id (student_id),
  INDEX idx_grievances_status_created_at (status, created_at),
  CONSTRAINT fk_grievances_student
    FOREIGN KEY (student_id) REFERENCES students(student_id)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO courses
  (course_code, course_name, category, description, duration, fee, status, display_order, show_in_contact_dropdown)
VALUES
  ('CAREER-DOME', 'Career Dome Programs', 'career-dome', 'Career-focused professional programs.', NULL, NULL, 'active', 10, 1),
  ('UNIVERSITY', 'University Programmes', 'university', 'University degree and diploma programmes.', NULL, NULL, 'active', 20, 1),
  ('CERTIFICATION', 'Certification Programs', 'certification', 'Professional certification programs.', NULL, NULL, 'active', 30, 1),
  ('SHORT-COURSES', 'Short Courses', 'short-courses', 'Short practical courses.', NULL, NULL, 'active', 40, 1)
ON DUPLICATE KEY UPDATE
  course_name = VALUES(course_name),
  category = VALUES(category),
  description = VALUES(description),
  status = VALUES(status),
  display_order = VALUES(display_order),
  show_in_contact_dropdown = VALUES(show_in_contact_dropdown);
