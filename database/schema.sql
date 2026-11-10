-- 1. Database
CREATE DATABASE IF NOT EXISTS institute_db
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE institute_db;

-- 2. users
CREATE TABLE IF NOT EXISTS users(
    user_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL,
    national_code CHAR(10) NULL,
    password VARCHAR(255) NOT NULL,
    fullname VARCHAR(100) NOT NULL,
    phone VARCHAR(15) NULL,
    address VARCHAR(255) NULL,
    skill VARCHAR(100) NULL,
    img_url VARCHAR(255) NULL,
    role ENUM('admin' , 'teacher' , 'student') NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY(user_id),
    UNIQUE KEY uq_users_username (username),
    UNIQUE KEY uq_users_national_code (national_code),
    KEY idx_users_role (role)
) ENGINE=InnoDB;

-- 3. classes (user_id is the teacher)
CREATE TABLE IF NOT EXISTS classes (
    class_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(150) NOT NULL,
    class_num VARCHAR(20) NOT NULL,
    sessions SMALLINT UNSIGNED NOT NULL,
    s_date DATE NOT NULL,
    e_date DATE NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY(class_id),
    UNIQUE KEY uq_classes_number (class_num),
    KEY idx_classes_user (user_id),
    CONSTRAINT fk_classes_user FOREIGN KEY (user_id)
    REFERENCES users (user_id)
    ON DELETE RESTRICT
    ON UPDATE CASCADE,
    CONSTRAINT chk_classes_dates CHECK (e_date >= s_date)
) ENGINE=InnoDB;

-- 4. class_student (user_id is the student)
CREATE TABLE IF NOT EXISTS class_student (
    c_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    class_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    PRIMARY KEY(c_id),
    UNIQUE KEY uq_class_student (class_id , user_id),
    KEY idx_class_student_user (user_id),
    CONSTRAINT fk_class_student_class FOREIGN KEY (class_id)
    REFERENCES classes (class_id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
    CONSTRAINT fk_class_student_user FOREIGN KEY (user_id)
    REFERENCES users (user_id)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB;

-- 5. class_time (day: 0 = saturday ... 6 = friday)
CREATE TABLE IF NOT EXISTS class_time (
    t_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    class_id INT UNSIGNED NOT NULL,
    day TINYINT UNSIGNED NOT NULL,
    s_clock TIME NOT NULL,
    e_clock TIME NOT NULL,
    PRIMARY KEY(t_id),
    KEY idx_class_time_class (class_id),
    CONSTRAINT fk_class_time_class FOREIGN KEY (class_id)
    REFERENCES classes (class_id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
    CONSTRAINT chk_class_time_day CHECK (day BETWEEN 0 AND 6),
    CONSTRAINT chk_class_time_range CHECK (e_clock > s_clock)
) ENGINE=InnoDB;

-- 6. class_session (one row per session of a class)
CREATE TABLE IF NOT EXISTS class_session (
    session_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    class_id INT UNSIGNED NOT NULL,
    session_num SMALLINT UNSIGNED NOT NULL,
    status ENUM('scheduled', 'held', 'cancelled', 'postponed') NOT NULL DEFAULT 'scheduled',
    `date` DATE NOT NULL,
    PRIMARY KEY(session_id),
    UNIQUE KEY uq_class_session_num (class_id , session_num),
    KEY idx_class_session_date (`date`),
    CONSTRAINT fk_class_session_class FOREIGN KEY (class_id)
    REFERENCES classes (class_id)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB;

-- 7. landing page: info (single row , admin only edits it)
CREATE TABLE IF NOT EXISTS info (
    info_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    about TEXT NULL,
    phone VARCHAR(30) NULL,
    email VARCHAR(120) NULL,
    address VARCHAR(255) NULL,
    PRIMARY KEY(info_id)
) ENGINE=InnoDB;

-- 8. landing page: slide_shows
CREATE TABLE IF NOT EXISTS slide_shows (
    slide_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(150) NOT NULL,
    description TEXT NULL,
    img_url VARCHAR(255) NULL,
    PRIMARY KEY(slide_id)
) ENGINE=InnoDB;

-- 9. landing page: new_content
CREATE TABLE IF NOT EXISTS new_content (
    content_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(150) NOT NULL,
    description TEXT NULL,
    img_url VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY(content_id)
) ENGINE=InnoDB;