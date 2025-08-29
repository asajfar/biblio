-- MySQL Setup Script za Svečani Prijemi aplikaciju

-- Kreiranje korisnika i baze
CREATE DATABASE IF NOT EXISTS svecani_prijemi CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Kreiranje korisnika (opciono - može se koristiti root)
CREATE USER IF NOT EXISTS 'svecani_prijemi'@'localhost' IDENTIFIED BY 'password123';
CREATE USER IF NOT EXISTS 'svecani_prijemi'@'%' IDENTIFIED BY 'password123';

-- Davanje dozvola
GRANT ALL PRIVILEGES ON svecani_prijemi.* TO 'svecani_prijemi'@'localhost';
GRANT ALL PRIVILEGES ON svecani_prijemi.* TO 'svecani_prijemi'@'%';
FLUSH PRIVILEGES;

-- Koristimo kreiraną bazu
USE svecani_prijemi;

-- Kreiranje tabela (aplikacija će ih kreirati automatski, ali ovde je backup)

-- Tabela korisnika
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(80) UNIQUE NOT NULL,
    email VARCHAR(120) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Tabela prijema
CREATE TABLE IF NOT EXISTS receptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    date DATE NOT NULL,
    venue VARCHAR(200) NOT NULL,
    description TEXT,
    user_id INT NOT NULL,
    public_id VARCHAR(100) UNIQUE,
    is_public BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_public_id (public_id),
    INDEX idx_date (date)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Tabela stolova
CREATE TABLE IF NOT EXISTS tables (
    id INT AUTO_INCREMENT PRIMARY KEY,
    number INT NOT NULL,
    capacity INT NOT NULL DEFAULT 8,
    reception_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reception_id) REFERENCES receptions(id) ON DELETE CASCADE,
    UNIQUE KEY unique_table_number (reception_id, number),
    INDEX idx_reception_id (reception_id)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Tabela gostiju
CREATE TABLE IF NOT EXISTS guests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(120),
    notes TEXT,
    reception_id INT NOT NULL,
    table_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (reception_id) REFERENCES receptions(id) ON DELETE CASCADE,
    FOREIGN KEY (table_id) REFERENCES tables(id) ON DELETE SET NULL,
    INDEX idx_reception_id (reception_id),
    INDEX idx_table_id (table_id),
    INDEX idx_name (name)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Dodavanje početnih podataka (opciono)
-- INSERT INTO users (username, email, password_hash) VALUES 
-- ('admin', 'admin@example.com', '$2b$12$...');

-- Prikaz tabela
SHOW TABLES;

-- Prikaz strukture
DESCRIBE users;
DESCRIBE receptions;
DESCRIBE tables;
DESCRIBE guests;