<?php
/**
 * Configuration file for Business Promotion App
 * Contains database connection and app settings
 */

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'business_promo');
define('DB_USER', 'biznis_admin');
define('DB_PASS', 'secure_password_2024');
define('DB_CHARSET', 'utf8mb4');

// App configuration
define('SITE_NAME', 'Lokalni Biznis');
define('SITE_URL', 'http://localhost');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('UPLOAD_URL', SITE_URL . '/uploads/');
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif']);
define('MAX_IMAGES_PER_COMPANY', 6);

// Security settings
define('PASSWORD_MIN_LENGTH', 6);
define('SESSION_TIMEOUT', 3600); // 1 hour

// Default map settings (Belgrade, Serbia)
define('DEFAULT_MAP_LAT', 44.7866);
define('DEFAULT_MAP_LNG', 20.4489);
define('DEFAULT_MAP_ZOOM', 10);

// Error reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('Europe/Belgrade');

// Helper functions
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isAdmin() {
    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function getCurrentUserId() {
    return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
}

function redirectTo($url) {
    header("Location: $url");
    exit();
}

function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function generateToken() {
    return bin2hex(random_bytes(32));
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function formatDate($date, $format = 'd.m.Y H:i') {
    return date($format, strtotime($date));
}

function truncateText($text, $length = 100) {
    return strlen($text) > $length ? substr($text, 0, $length) . '...' : $text;
}

// CSRF protection
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = generateToken();
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Flash messages
function setFlashMessage($type, $message) {
    $_SESSION['flash_messages'][] = [
        'type' => $type,
        'message' => $message
    ];
}

function getFlashMessages() {
    $messages = isset($_SESSION['flash_messages']) ? $_SESSION['flash_messages'] : [];
    unset($_SESSION['flash_messages']);
    return $messages;
}

function hasFlashMessages() {
    return isset($_SESSION['flash_messages']) && !empty($_SESSION['flash_messages']);
}

// File upload helpers
function validateImageUpload($file) {
    $errors = [];
    
    // Check if file was uploaded
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Greška pri upload-u fajla.';
        return $errors;
    }
    
    // Check file size
    if ($file['size'] > MAX_FILE_SIZE) {
        $errors[] = 'Fajl je prevelik. Maksimalna veličina je ' . (MAX_FILE_SIZE / 1024 / 1024) . 'MB.';
    }
    
    // Check file extension
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ALLOWED_EXTENSIONS)) {
        $errors[] = 'Nedozvoljena ekstenzija. Dozvoljene: ' . implode(', ', ALLOWED_EXTENSIONS);
    }
    
    // Check if it's actually an image
    $imageInfo = getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        $errors[] = 'Fajl nije validna slika.';
    }
    
    return $errors;
}

function generateUniqueFileName($originalName) {
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    return uniqid() . '_' . time() . '.' . $extension;
}

// URL helpers
function getCompanyUrl($companyId, $companyName = '') {
    $slug = !empty($companyName) ? createSlug($companyName) : '';
    return SITE_URL . "/company.php?id=$companyId" . (!empty($slug) ? "&slug=$slug" : '');
}

function createSlug($text) {
    // Replace Serbian characters
    $text = str_replace(
        ['č', 'ć', 'đ', 'š', 'ž', 'Č', 'Ć', 'Đ', 'Š', 'Ž'],
        ['c', 'c', 'd', 's', 'z', 'C', 'C', 'D', 'S', 'Z'],
        $text
    );
    
    // Convert to lowercase and replace spaces with hyphens
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim($text, '-');
    
    return $text;
}

// Image resizing helper
function resizeImage($source, $destination, $maxWidth = 800, $maxHeight = 600, $quality = 85) {
    $imageInfo = getimagesize($source);
    if (!$imageInfo) return false;
    
    list($originalWidth, $originalHeight, $imageType) = $imageInfo;
    
    // Calculate new dimensions
    $ratio = min($maxWidth / $originalWidth, $maxHeight / $originalHeight);
    $newWidth = intval($originalWidth * $ratio);
    $newHeight = intval($originalHeight * $ratio);
    
    // Create image resource based on type
    switch ($imageType) {
        case IMAGETYPE_JPEG:
            $sourceImage = imagecreatefromjpeg($source);
            break;
        case IMAGETYPE_PNG:
            $sourceImage = imagecreatefrompng($source);
            break;
        case IMAGETYPE_GIF:
            $sourceImage = imagecreatefromgif($source);
            break;
        default:
            return false;
    }
    
    // Create new image
    $newImage = imagecreatetruecolor($newWidth, $newHeight);
    
    // Preserve transparency for PNG and GIF
    if ($imageType == IMAGETYPE_PNG || $imageType == IMAGETYPE_GIF) {
        imagealphablending($newImage, false);
        imagesavealpha($newImage, true);
        $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
        imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
    }
    
    // Resize image
    imagecopyresampled($newImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);
    
    // Save image
    $result = false;
    switch ($imageType) {
        case IMAGETYPE_JPEG:
            $result = imagejpeg($newImage, $destination, $quality);
            break;
        case IMAGETYPE_PNG:
            $result = imagepng($newImage, $destination);
            break;
        case IMAGETYPE_GIF:
            $result = imagegif($newImage, $destination);
            break;
    }
    
    // Clean up memory
    imagedestroy($sourceImage);
    imagedestroy($newImage);
    
    return $result;
}
?>