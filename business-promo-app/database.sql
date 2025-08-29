-- Database setup for Business Promotion App
-- Creator: Assistant
-- Date: 2024

-- Create database
CREATE DATABASE IF NOT EXISTS business_promo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE business_promo;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ime VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    lozinka VARCHAR(255) NOT NULL,
    status ENUM('active', 'inactive', 'banned') DEFAULT 'active',
    role ENUM('user', 'admin') DEFAULT 'user',
    datum_registracije TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_status (status)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Companies table
CREATE TABLE IF NOT EXISTS companies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    naziv VARCHAR(200) NOT NULL,
    opis TEXT,
    adresa VARCHAR(300),
    lat DECIMAL(10, 8),
    lng DECIMAL(11, 8),
    kategorija VARCHAR(100),
    telefon VARCHAR(50),
    email VARCHAR(150),
    website VARCHAR(200),
    radno_vreme TEXT,
    status ENUM('active', 'pending', 'inactive') DEFAULT 'pending',
    datum_kreiranja TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    datum_azuriranja TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_kategorija (kategorija),
    INDEX idx_status (status),
    INDEX idx_lokacija (lat, lng)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Images table
CREATE TABLE IF NOT EXISTS images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    file_path VARCHAR(300) NOT NULL,
    alt_text VARCHAR(200),
    is_main BOOLEAN DEFAULT FALSE,
    datum_upload TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    INDEX idx_company_id (company_id),
    INDEX idx_is_main (is_main)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Reviews table
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    ime_korisnika VARCHAR(100) NOT NULL,
    email VARCHAR(150),
    ocena TINYINT NOT NULL CHECK (ocena BETWEEN 1 AND 5),
    komentar TEXT,
    status ENUM('approved', 'pending', 'rejected') DEFAULT 'pending',
    datum TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    INDEX idx_company_id (company_id),
    INDEX idx_ocena (ocena),
    INDEX idx_status (status),
    INDEX idx_datum (datum)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Visits table
CREATE TABLE IF NOT EXISTS visits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    referer VARCHAR(500),
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    INDEX idx_company_id (company_id),
    INDEX idx_timestamp (timestamp),
    INDEX idx_ip (ip_address)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Pages table (static content)
CREATE TABLE IF NOT EXISTS pages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(100) UNIQUE NOT NULL,
    naslov VARCHAR(200) NOT NULL,
    sadrzaj LONGTEXT,
    meta_description VARCHAR(300),
    meta_keywords VARCHAR(500),
    status ENUM('published', 'draft') DEFAULT 'draft',
    datum_kreiranja TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    datum_azuriranja TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_status (status)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Categories table (for better organization)
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    naziv VARCHAR(100) UNIQUE NOT NULL,
    opis TEXT,
    ikona VARCHAR(50),
    boja VARCHAR(7) DEFAULT '#007bff',
    status ENUM('active', 'inactive') DEFAULT 'active',
    INDEX idx_status (status)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Settings table (for app configuration)
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kljuc VARCHAR(100) UNIQUE NOT NULL,
    vrednost TEXT,
    opis VARCHAR(300),
    tip ENUM('text', 'number', 'boolean', 'json') DEFAULT 'text',
    INDEX idx_kljuc (kljuc)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Insert default admin user (password: admin123)
INSERT INTO users (ime, email, lozinka, role) VALUES 
('Administrator', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Insert default categories
INSERT INTO categories (naziv, opis, ikona, boja) VALUES 
('Restorani', 'Restorani, kafići, dostava hrane', 'fas fa-utensils', '#e74c3c'),
('Trgovina', 'Prodavnice, supermarketi, butici', 'fas fa-shopping-cart', '#f39c12'),
('Usluge', 'Frizerski saloni, autoservisi, čišćenje', 'fas fa-tools', '#3498db'),
('Zdravstvo', 'Lekari, apoteke, veterinari', 'fas fa-heartbeat', '#2ecc71'),
('Obrazovanje', 'Škole, kursevi, privatni časovi', 'fas fa-graduation-cap', '#9b59b6'),
('Sport i rekreacija', 'Teretane, sportski centri', 'fas fa-dumbbell', '#e67e22'),
('Turizam', 'Hoteli, apartmani, agencije', 'fas fa-plane', '#1abc9c'),
('Tehnologija', 'IT servisi, prodaja tehnike', 'fas fa-laptop', '#34495e'),
('Automobili', 'Prodaja, servis, delovi', 'fas fa-car', '#95a5a6'),
('Ostalo', 'Sve ostalo što ne spada u kategorije', 'fas fa-ellipsis-h', '#7f8c8d');

-- Insert default settings
INSERT INTO settings (kljuc, vrednost, opis, tip) VALUES 
('site_name', 'Lokalni Biznis', 'Naziv sajta', 'text'),
('site_description', 'Platforma za promociju lokalnih biznisa', 'Opis sajta', 'text'),
('max_images_per_company', '6', 'Maksimalan broj slika po firmi', 'number'),
('max_file_size', '5242880', 'Maksimalna veličina fajla u bajtovima (5MB)', 'number'),
('allowed_extensions', 'jpg,jpeg,png,gif', 'Dozvoljene ekstenzije za slike', 'text'),
('default_map_lat', '44.7866', 'Default geografska širina za mapu (Beograd)', 'text'),
('default_map_lng', '20.4489', 'Default geografska dužina za mapu (Beograd)', 'text'),
('default_map_zoom', '10', 'Default zoom level za mapu', 'number'),
('reviews_require_approval', '1', 'Da li recenzije zahtevaju odobravanje', 'boolean'),
('contact_email', 'kontakt@lokalnibiznis.rs', 'Kontakt email', 'text');

-- Create admin user for database access
CREATE USER IF NOT EXISTS 'biznis_admin'@'localhost' IDENTIFIED BY 'secure_password_2024';
GRANT ALL PRIVILEGES ON business_promo.* TO 'biznis_admin'@'localhost';
FLUSH PRIVILEGES;

-- Show tables and status
SHOW TABLES;
SELECT 'Database setup completed successfully!' as Status;