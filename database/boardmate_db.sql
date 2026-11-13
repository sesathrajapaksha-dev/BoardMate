-- =====================================================
-- BoardMate Database
-- Tagline: Find. Compare. Stay.
-- =====================================================

CREATE DATABASE IF NOT EXISTS boardmate_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE boardmate_db;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS activity_logs;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS boarding_facilities;
DROP TABLE IF EXISTS boarding_images;
DROP TABLE IF EXISTS facilities;
DROP TABLE IF EXISTS boardings;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

-- Table 1: users
CREATE TABLE users (
    user_id     INT UNSIGNED NOT NULL AUTO_INCREMENT,
    full_name   VARCHAR(100) NOT NULL,
    username    VARCHAR(50)  NOT NULL,
    email       VARCHAR(120) NOT NULL,
    phone       VARCHAR(20)  NOT NULL,
    password    VARCHAR(255) NOT NULL,
    role        ENUM('student','owner','admin') NOT NULL DEFAULT 'student',
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id),
    UNIQUE KEY uq_users_username (username),
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_role (role)
) ENGINE=InnoDB;

-- Table 2: boardings
CREATE TABLE boardings (
    boarding_id      INT UNSIGNED NOT NULL AUTO_INCREMENT,
    owner_id         INT UNSIGNED NOT NULL,
    university       VARCHAR(100) NOT NULL DEFAULT 'University of Moratuwa',
    boarding_name    VARCHAR(150) NOT NULL,
    description      TEXT NOT NULL,
    location         VARCHAR(100) NOT NULL,
    full_address     VARCHAR(255) NOT NULL,
    price_per_person DECIMAL(10,2) NOT NULL,
    available_beds   SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    contact_number   VARCHAR(20) NOT NULL,
    created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (boarding_id),
    KEY idx_boardings_owner (owner_id),
    KEY idx_boardings_location (location),
    KEY idx_boardings_price (price_per_person),
    KEY idx_boardings_created (created_at),
    KEY idx_boardings_university (university),
    CONSTRAINT fk_boardings_owner FOREIGN KEY (owner_id)
        REFERENCES users (user_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Table 3: boarding_images
CREATE TABLE boarding_images (
    image_id     INT UNSIGNED NOT NULL AUTO_INCREMENT,
    boarding_id  INT UNSIGNED NOT NULL,
    image_path   VARCHAR(255) NOT NULL,
    PRIMARY KEY (image_id),
    KEY idx_images_boarding (boarding_id),
    CONSTRAINT fk_images_boarding FOREIGN KEY (boarding_id)
        REFERENCES boardings (boarding_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Table 4: facilities
CREATE TABLE facilities (
    facility_id    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    facility_name  VARCHAR(60) NOT NULL,
    PRIMARY KEY (facility_id),
    UNIQUE KEY uq_facility_name (facility_name)
) ENGINE=InnoDB;

-- Table 5: boarding_facilities (composite primary key)
CREATE TABLE boarding_facilities (
    boarding_id  INT UNSIGNED NOT NULL,
    facility_id  INT UNSIGNED NOT NULL,
    PRIMARY KEY (boarding_id, facility_id),
    KEY idx_bf_facility (facility_id),
    CONSTRAINT fk_bf_boarding FOREIGN KEY (boarding_id)
        REFERENCES boardings (boarding_id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_bf_facility FOREIGN KEY (facility_id)
        REFERENCES facilities (facility_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Table 6: reviews (one review per student per boarding)
CREATE TABLE reviews (
    review_id    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_id   INT UNSIGNED NOT NULL,
    boarding_id  INT UNSIGNED NOT NULL,
    rating       TINYINT UNSIGNED NOT NULL,
    review_text  TEXT NOT NULL,
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (review_id),
    UNIQUE KEY uq_review_student_boarding (student_id, boarding_id),
    KEY idx_reviews_boarding (boarding_id),
    KEY idx_reviews_rating (rating),
    CONSTRAINT chk_rating CHECK (rating BETWEEN 1 AND 5),
    CONSTRAINT fk_reviews_student FOREIGN KEY (student_id)
        REFERENCES users (user_id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_reviews_boarding FOREIGN KEY (boarding_id)
        REFERENCES boardings (boarding_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Table 7 (extra): activity_logs - for admin monitoring
CREATE TABLE activity_logs (
    log_id      INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED NULL,
    action      VARCHAR(60)  NOT NULL,
    details     VARCHAR(255) NULL,
    ip_address  VARCHAR(45)  NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (log_id),
    KEY idx_logs_user (user_id),
    KEY idx_logs_created (created_at),
    CONSTRAINT fk_logs_user FOREIGN KEY (user_id)
        REFERENCES users (user_id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Default facilities
INSERT INTO facilities (facility_name) VALUES
('Wi-Fi'),
('Electricity'),
('Water'),
('Attached bathroom'),
('Shared bathroom'),
('Kitchen'),
('Parking'),
('Laundry facilities'),
('Furnished rooms'),
('Air conditioning');
