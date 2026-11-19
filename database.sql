-- CampusCareer BD - MySQL schema
-- (api.php creates all of this automatically on the first request;
--  this file is only for showing / importing the schema by hand in phpMyAdmin.)

CREATE DATABASE IF NOT EXISTS `campuscareerbd` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `campuscareerbd`;

CREATE TABLE IF NOT EXISTS students (
      seq INT NOT NULL AUTO_INCREMENT,
      email VARCHAR(190) NOT NULL,
      name VARCHAR(190) NOT NULL DEFAULT '',
      university VARCHAR(190) NOT NULL DEFAULT '',
      password_hash VARCHAR(255) NOT NULL DEFAULT '',
      verified TINYINT(1) NOT NULL DEFAULT 0,
      data LONGTEXT NOT NULL,
      PRIMARY KEY (email),
      UNIQUE KEY seq_idx (seq)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS employers (
      seq INT NOT NULL AUTO_INCREMENT,
      email VARCHAR(190) NOT NULL,
      company_name VARCHAR(190) NOT NULL DEFAULT '',
      industry VARCHAR(190) NOT NULL DEFAULT '',
      password_hash VARCHAR(255) NOT NULL DEFAULT '',
      verified TINYINT(1) NOT NULL DEFAULT 0,
      data LONGTEXT NOT NULL,
      PRIMARY KEY (email),
      UNIQUE KEY seq_idx (seq)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS jobs (
      seq INT NOT NULL AUTO_INCREMENT,
      id INT NOT NULL,
      title VARCHAR(190) NOT NULL DEFAULT '',
      company VARCHAR(190) NOT NULL DEFAULT '',
      location VARCHAR(190) NOT NULL DEFAULT '',
      job_type VARCHAR(60) NOT NULL DEFAULT '',
      status VARCHAR(20) NOT NULL DEFAULT 'Pending',
      posted_by VARCHAR(190) NOT NULL DEFAULT '',
      data LONGTEXT NOT NULL,
      PRIMARY KEY (id),
      UNIQUE KEY seq_idx (seq)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS applications (
      seq INT NOT NULL AUTO_INCREMENT,
      student_email VARCHAR(190) NOT NULL,
      job_id INT NOT NULL,
      status VARCHAR(30) NOT NULL DEFAULT 'Applied',
      applied_at VARCHAR(40) NOT NULL DEFAULT '',
      data LONGTEXT NOT NULL,
      PRIMARY KEY (student_email, job_id),
      UNIQUE KEY seq_idx (seq)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notifications (
      seq INT NOT NULL AUTO_INCREMENT,
      id VARCHAR(64) NOT NULL,
      to_email VARCHAR(190) NOT NULL DEFAULT '',
      message TEXT NOT NULL,
      created_at VARCHAR(40) NOT NULL DEFAULT '',
      is_read TINYINT(1) NOT NULL DEFAULT 0,
      data LONGTEXT NOT NULL,
      PRIMARY KEY (id),
      UNIQUE KEY seq_idx (seq)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS meta (
      k VARCHAR(64) NOT NULL,
      v TEXT NOT NULL,
      PRIMARY KEY (k)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

