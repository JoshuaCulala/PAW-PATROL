CREATE DATABASE IF NOT EXISTS paw_patrol
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE paw_patrol;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(254) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS reports (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  photo VARCHAR(255) NOT NULL DEFAULT '',
  photo_data LONGBLOB NULL,
  photo_mime VARCHAR(40) NULL,
  location VARCHAR(150) NOT NULL,
  animal_type VARCHAR(40) NOT NULL,
  condition_status VARCHAR(40) NOT NULL,
  urgency VARCHAR(20) NOT NULL,
  notes TEXT NOT NULL,
  status ENUM('Pending', 'Rescued') NOT NULL DEFAULT 'Pending',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_reports_status (status),
  KEY idx_reports_created_at (created_at),
  CONSTRAINT fk_reports_user FOREIGN KEY (user_id) REFERENCES users (id)
    ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS rescued (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  slug VARCHAR(100) NOT NULL,
  report_id INT UNSIGNED NULL,
  name VARCHAR(100) NOT NULL,
  animal_type VARCHAR(40) NOT NULL,
  age VARCHAR(40) NOT NULL,
  photo VARCHAR(255) NOT NULL DEFAULT '',
  photo_data LONGBLOB NULL,
  photo_mime VARCHAR(40) NULL,
  location VARCHAR(150) NOT NULL,
  status ENUM('Available', 'Adopted') NOT NULL DEFAULT 'Available',
  adopted_by INT UNSIGNED NULL,
  adopted_at TIMESTAMP NULL DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_rescued_slug (slug),
  UNIQUE KEY uq_rescued_report_id (report_id),
  KEY idx_rescued_adopted_by (adopted_by),
  KEY idx_rescued_status (status),
  CONSTRAINT fk_rescued_report FOREIGN KEY (report_id) REFERENCES reports (id)
    ON DELETE CASCADE,
  CONSTRAINT fk_rescued_adopted_by FOREIGN KEY (adopted_by) REFERENCES users (id)
    ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT IGNORE INTO rescued (slug, name, animal_type, age, photo, location) VALUES
  ('browny', 'Browny', 'Dog', '2 yrs', 'https://images.unsplash.com/photo-1552053831-71594a27632d?w=400&q=80', 'Gym'),
  ('mingming', 'Mingming', 'Cat', '6 months', 'https://images.unsplash.com/photo-1514888286974-6c03e2ca1dba?w=400&q=80', 'Library');