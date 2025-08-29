<?php
/**
 * Database connection and helper functions
 * Business Promotion App
 */

require_once 'config.php';

class Database {
    private $connection;
    private $host;
    private $dbname;
    private $username;
    private $password;
    
    public function __construct() {
        $this->host = DB_HOST;
        $this->dbname = DB_NAME;
        $this->username = DB_USER;
        $this->password = DB_PASS;
    }
    
    public function connect() {
        if ($this->connection === null) {
            try {
                $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset=" . DB_CHARSET;
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ];
                
                $this->connection = new PDO($dsn, $this->username, $this->password, $options);
            } catch (PDOException $e) {
                error_log("Database connection error: " . $e->getMessage());
                die("Greška pri konekciji sa bazom podataka.");
            }
        }
        
        return $this->connection;
    }
    
    public function query($sql, $params = []) {
        try {
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Database query error: " . $e->getMessage());
            throw new Exception("Greška pri izvršavanju upita.");
        }
    }
    
    public function fetchOne($sql, $params = []) {
        return $this->query($sql, $params)->fetch();
    }
    
    public function fetchAll($sql, $params = []) {
        return $this->query($sql, $params)->fetchAll();
    }
    
    public function insert($table, $data) {
        $columns = implode(',', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $this->query($sql, $data);
        
        return $this->connect()->lastInsertId();
    }
    
    public function update($table, $data, $condition, $conditionParams = []) {
        $setParts = [];
        foreach (array_keys($data) as $key) {
            $setParts[] = "{$key} = :{$key}";
        }
        $setClause = implode(', ', $setParts);
        
        $sql = "UPDATE {$table} SET {$setClause} WHERE {$condition}";
        $params = array_merge($data, $conditionParams);
        
        return $this->query($sql, $params);
    }
    
    public function delete($table, $condition, $params = []) {
        $sql = "DELETE FROM {$table} WHERE {$condition}";
        return $this->query($sql, $params);
    }
    
    public function exists($table, $condition, $params = []) {
        $sql = "SELECT 1 FROM {$table} WHERE {$condition} LIMIT 1";
        $result = $this->fetchOne($sql, $params);
        return $result !== false;
    }
    
    public function count($table, $condition = '1=1', $params = []) {
        $sql = "SELECT COUNT(*) as count FROM {$table} WHERE {$condition}";
        $result = $this->fetchOne($sql, $params);
        return $result ? $result['count'] : 0;
    }
}

// Global database instance
$db = new Database();

// User-related functions
function authenticateUser($email, $password) {
    global $db;
    
    $user = $db->fetchOne(
        "SELECT * FROM users WHERE email = ? AND status = 'active'",
        [$email]
    );
    
    if ($user && password_verify($password, $user['lozinka'])) {
        // Update last login (if we add this field later)
        return $user;
    }
    
    return false;
}

function createUser($ime, $email, $password) {
    global $db;
    
    // Check if email already exists
    if ($db->exists('users', 'email = ?', [$email])) {
        return false;
    }
    
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    return $db->insert('users', [
        'ime' => $ime,
        'email' => $email,
        'lozinka' => $hashedPassword
    ]);
}

function getUserById($id) {
    global $db;
    return $db->fetchOne("SELECT * FROM users WHERE id = ?", [$id]);
}

function updateUserLastLogin($userId) {
    global $db;
    // We can add last_login field later if needed
    return true;
}

// Company-related functions
function getCompaniesByUser($userId) {
    global $db;
    return $db->fetchAll(
        "SELECT c.*, 
                (SELECT COUNT(*) FROM images WHERE company_id = c.id) as image_count,
                (SELECT AVG(ocena) FROM reviews WHERE company_id = c.id AND status = 'approved') as avg_rating,
                (SELECT COUNT(*) FROM reviews WHERE company_id = c.id AND status = 'approved') as review_count
         FROM companies c 
         WHERE c.user_id = ? 
         ORDER BY c.datum_kreiranja DESC",
        [$userId]
    );
}

function getCompanyById($id) {
    global $db;
    return $db->fetchOne(
        "SELECT c.*, u.ime as owner_name,
                (SELECT COUNT(*) FROM reviews WHERE company_id = c.id AND status = 'approved') as review_count,
                (SELECT AVG(ocena) FROM reviews WHERE company_id = c.id AND status = 'approved') as avg_rating
         FROM companies c 
         JOIN users u ON c.user_id = u.id 
         WHERE c.id = ?",
        [$id]
    );
}

function getCompanyImages($companyId) {
    global $db;
    return $db->fetchAll(
        "SELECT * FROM images WHERE company_id = ? ORDER BY is_main DESC, datum_upload ASC",
        [$companyId]
    );
}

function getCompanyReviews($companyId, $limit = 10) {
    global $db;
    return $db->fetchAll(
        "SELECT * FROM reviews 
         WHERE company_id = ? AND status = 'approved' 
         ORDER BY datum DESC 
         LIMIT ?",
        [$companyId, $limit]
    );
}

function searchCompanies($search = '', $category = '', $lat = null, $lng = null, $radius = null) {
    global $db;
    
    $sql = "SELECT c.*, 
                   (SELECT COUNT(*) FROM reviews WHERE company_id = c.id AND status = 'approved') as review_count,
                   (SELECT AVG(ocena) FROM reviews WHERE company_id = c.id AND status = 'approved') as avg_rating";
    
    if ($lat && $lng && $radius) {
        $sql .= ", (6371 * acos(cos(radians(?)) * cos(radians(lat)) * cos(radians(lng) - radians(?)) + sin(radians(?)) * sin(radians(lat)))) AS distance";
    }
    
    $sql .= " FROM companies c WHERE c.status = 'active'";
    
    $params = [];
    
    if ($lat && $lng && $radius) {
        $params = [$lat, $lng, $lat];
    }
    
    if (!empty($search)) {
        $sql .= " AND (c.naziv LIKE ? OR c.opis LIKE ? OR c.adresa LIKE ?)";
        $searchTerm = "%$search%";
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
    }
    
    if (!empty($category)) {
        $sql .= " AND c.kategorija = ?";
        $params[] = $category;
    }
    
    if ($lat && $lng && $radius) {
        $sql .= " HAVING distance < ? ORDER BY distance";
        $params[] = $radius;
    } else {
        $sql .= " ORDER BY c.datum_kreiranja DESC";
    }
    
    return $db->fetchAll($sql, $params);
}

function getActiveCompaniesForMap() {
    global $db;
    return $db->fetchAll(
        "SELECT id, naziv, lat, lng, kategorija, adresa 
         FROM companies 
         WHERE status = 'active' AND lat IS NOT NULL AND lng IS NOT NULL"
    );
}

function incrementVisitCount($companyId) {
    global $db;
    
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    
    return $db->insert('visits', [
        'company_id' => $companyId,
        'ip_address' => $ipAddress,
        'user_agent' => $userAgent,
        'referer' => $referer
    ]);
}

function getCompanyVisitCount($companyId) {
    global $db;
    return $db->count('visits', 'company_id = ?', [$companyId]);
}

// Category functions
function getAllCategories() {
    global $db;
    return $db->fetchAll("SELECT * FROM categories WHERE status = 'active' ORDER BY naziv");
}

function getCategoryByName($name) {
    global $db;
    return $db->fetchOne("SELECT * FROM categories WHERE naziv = ?", [$name]);
}

// Settings functions
function getSetting($key, $default = null) {
    global $db;
    $setting = $db->fetchOne("SELECT vrednost FROM settings WHERE kljuc = ?", [$key]);
    return $setting ? $setting['vrednost'] : $default;
}

function updateSetting($key, $value) {
    global $db;
    
    if ($db->exists('settings', 'kljuc = ?', [$key])) {
        return $db->update('settings', ['vrednost' => $value], 'kljuc = ?', [$key]);
    } else {
        return $db->insert('settings', ['kljuc' => $key, 'vrednost' => $value]);
    }
}

// Pages functions
function getPageBySlug($slug) {
    global $db;
    return $db->fetchOne("SELECT * FROM pages WHERE slug = ? AND status = 'published'", [$slug]);
}

function getAllPages() {
    global $db;
    return $db->fetchAll("SELECT * FROM pages ORDER BY naslov");
}

// Statistics functions
function getDashboardStats() {
    global $db;
    
    return [
        'total_companies' => $db->count('companies'),
        'active_companies' => $db->count('companies', "status = 'active'"),
        'total_users' => $db->count('users'),
        'total_reviews' => $db->count('reviews'),
        'pending_reviews' => $db->count('reviews', "status = 'pending'"),
        'total_visits' => $db->count('visits')
    ];
}
?>