# API Documentation - Library Authentication System

## Overview

This is a PHP-based authentication system for a library management application. The system provides comprehensive user management functionality including registration, login, password reset, and logout capabilities.

## Table of Contents

1. [Configuration](#configuration)
2. [Authentication Functions](#authentication-functions)
3. [Database Setup](#database-setup)
4. [Usage Examples](#usage-examples)
5. [Security Considerations](#security-considerations)
6. [Error Handling](#error-handling)

---

## Configuration

### config.php

The configuration file establishes database connection and sets up the MySQL environment.

#### Database Configuration Variables

| Variable | Type | Description | Default Value |
|----------|------|-------------|---------------|
| `$host` | string | Database server address | `'localhost'` |
| `$db_name` | string | Database name | `'ombudsma_biblioteka'` |
| `$user` | string | Database username | `'ombudsma_biblioteka'` |
| `$password` | string | Database password | `'ApvOmbDtd'` |
| `$conn` | mysqli | Database connection object | Auto-generated |

#### Usage

```php
// Include the configuration file in your scripts
require_once 'config.php';

// The $conn variable is now available for database operations
$query = "SELECT * FROM KORISNIK";
$result = mysqli_query($conn, $query);
```

#### Features

- **Automatic Connection**: Establishes MySQLi connection automatically
- **UTF-8 Support**: Sets charset to `utf8mb4` for full Unicode support
- **Error Handling**: Dies with descriptive error message if connection fails
- **Ready-to-Use**: Provides `$conn` variable for immediate use in other scripts

---

## Authentication Functions

### auth_functions.php

This file contains all user authentication and management functions.

---

### registerUser()

Registers a new user in the system with email verification.

#### Syntax

```php
function registerUser($conn, $ime, $prezime, $email, $password, $telefon = null)
```

#### Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `$conn` | mysqli | Yes | Database connection object |
| `$ime` | string | Yes | User's first name |
| `$prezime` | string | Yes | User's last name |
| `$email` | string | Yes | User's email address |
| `$password` | string | Yes | User's password (will be MD5 hashed) |
| `$telefon` | string | No | User's phone number (optional) |

#### Return Values

| Return Value | Description |
|--------------|-------------|
| `"Email adresa već postoji u sistemu."` | Email already exists in database |
| `"Registracija je uspešna. Proverite email za verifikaciju naloga."` | Registration successful |

#### Database Fields Created

- **Verification Token**: 3-day validity period
- **Password**: MD5 hashed
- **Verification Status**: Initially set to unverified (0)
- **Expiration Date**: Token expires after 3 days

#### Example Usage

```php
require_once 'config.php';
require_once 'auth_functions.php';

// Register a new user
$result = registerUser(
    $conn,
    'Marko',
    'Petrović',
    'marko.petrovic@email.com',
    'mojaSifra123',
    '+381641234567'
);

echo $result; // Output: "Registracija je uspešna. Proverite email za verifikaciju naloga."
```

#### Implementation Notes

- Automatically generates unique verification token using `md5(uniqid(mt_rand(), true))`
- Prevents duplicate email registration
- Trims whitespace from all input parameters
- Uses prepared statements to prevent SQL injection

---

### loginUser()

Authenticates a user and starts a session.

#### Syntax

```php
function loginUser($conn, $email, $password)
```

#### Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `$conn` | mysqli | Yes | Database connection object |
| `$email` | string | Yes | User's email address |
| `$password` | string | Yes | User's password |

#### Return Values

| Return Value | Description |
|--------------|-------------|
| `"Molimo, verifikujte svoj nalog pre prijave."` | Account not verified |
| `"Prijava je uspešna."` | Login successful |
| `"Neispravni email ili lozinka."` | Invalid credentials |

#### Session Variables Set

Upon successful login, the following session variables are created:

- `$_SESSION['user_id']` - User's database ID
- `$_SESSION['ime']` - User's first name
- `$_SESSION['prezime']` - User's last name
- `$_SESSION['email']` - User's email address

#### Remember Me Feature

- Sets a 10-day cookie: `user_id`
- Cookie path: `/` (site-wide)
- Automatic session persistence

#### Example Usage

```php
require_once 'config.php';
require_once 'auth_functions.php';

$result = loginUser($conn, 'marko.petrovic@email.com', 'mojaSifra123');

if ($result === "Prijava je uspešna.") {
    // User successfully logged in
    echo "Dobrodošli, " . $_SESSION['ime'] . " " . $_SESSION['prezime'];
    // Redirect to dashboard or main application
    header('Location: dashboard.php');
} else {
    // Handle login error
    echo $result;
}
```

---

### forgotPasswordRequest()

Initiates password reset process by generating a reset token.

#### Syntax

```php
function forgotPasswordRequest($conn, $email)
```

#### Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `$conn` | mysqli | Yes | Database connection object |
| `$email` | string | Yes | User's email address |

#### Return Values

| Return Value | Description |
|--------------|-------------|
| `"Korisnik sa unetim emailom ne postoji."` | Email not found in database |
| `"Email za reset lozinke je poslat, proverite svoj inbox."` | Reset token generated successfully |

#### Reset Token Properties

- **Validity**: 24 hours (1 day)
- **Generation**: `md5(uniqid(mt_rand(), true))`
- **Storage**: `reset_token` and `reset_expires` database fields

#### Example Usage

```php
require_once 'config.php';
require_once 'auth_functions.php';

$result = forgotPasswordRequest($conn, 'marko.petrovic@email.com');
echo $result;

// In production, you would send an email like this:
// $resetLink = "https://yourdomain.com/reset.php?token=" . $reset_token;
// mail($email, "Reset lozinke", "Kliknite na link: $resetLink");
```

---

### resetPassword()

Completes the password reset process using a valid reset token.

#### Syntax

```php
function resetPassword($conn, $reset_token, $new_password)
```

#### Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `$conn` | mysqli | Yes | Database connection object |
| `$reset_token` | string | Yes | Valid reset token from email |
| `$new_password` | string | Yes | New password to set |

#### Return Values

| Return Value | Description |
|--------------|-------------|
| `"Reset token je istekao. Pokušajte ponovo."` | Token has expired |
| `"Lozinka je uspešno resetovana."` | Password successfully reset |
| `"Nevažeći reset token."` | Invalid or non-existent token |

#### Security Features

- Validates token expiration time
- Clears reset token after successful use
- MD5 hashes the new password
- Uses prepared statements

#### Example Usage

```php
require_once 'config.php';
require_once 'auth_functions.php';

// This would typically come from a form or URL parameter
$token = $_GET['token'] ?? '';
$newPassword = $_POST['new_password'] ?? '';

if ($token && $newPassword) {
    $result = resetPassword($conn, $token, $newPassword);
    echo $result;
    
    if ($result === "Lozinka je uspešno resetovana.") {
        // Redirect to login page
        header('Location: login.php?message=password_reset_success');
    }
}
```

---

### logoutUser()

Logs out the current user by destroying session and clearing cookies.

#### Syntax

```php
function logoutUser()
```

#### Parameters

None

#### Return Values

| Return Value | Description |
|--------------|-------------|
| `"Uspešno ste se odjavili."` | User successfully logged out |

#### Actions Performed

1. **Session Management**:
   - Clears all session variables (`$_SESSION = array()`)
   - Destroys session cookie if cookies are enabled
   - Calls `session_destroy()`

2. **Cookie Management**:
   - Removes "remember me" cookie (`user_id`)
   - Sets cookie expiration to past time

#### Example Usage

```php
require_once 'auth_functions.php';

$result = logoutUser();
echo $result; // Output: "Uspešno ste se odjavili."

// Redirect to login page
header('Location: login.php?message=logged_out');
exit();
```

---

## Database Setup

### Required Database Table: KORISNIK

The system expects a `KORISNIK` table with the following structure:

```sql
CREATE TABLE KORISNIK (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ime VARCHAR(100) NOT NULL,
    prezime VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    lozinka VARCHAR(32) NOT NULL, -- MD5 hash (32 characters)
    telefon VARCHAR(20),
    verifikacioni_token VARCHAR(32),
    verifikovan TINYINT(1) DEFAULT 0,
    verifikacija_expires DATETIME,
    reset_token VARCHAR(32),
    reset_expires DATETIME,
    datum_kreiranja TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Field Descriptions

| Field | Type | Purpose |
|-------|------|---------|
| `id` | INT | Primary key, auto-increment |
| `ime` | VARCHAR(100) | User's first name |
| `prezime` | VARCHAR(100) | User's last name |
| `email` | VARCHAR(255) | Unique email address |
| `lozinka` | VARCHAR(32) | MD5 hashed password |
| `telefon` | VARCHAR(20) | Optional phone number |
| `verifikacioni_token` | VARCHAR(32) | Email verification token |
| `verifikovan` | TINYINT(1) | Verification status (0/1) |
| `verifikacija_expires` | DATETIME | Verification token expiration |
| `reset_token` | VARCHAR(32) | Password reset token |
| `reset_expires` | DATETIME | Reset token expiration |

---

## Usage Examples

### Complete User Registration Flow

```php
<?php
require_once 'config.php';
require_once 'auth_functions.php';

// Handle registration form submission
if ($_POST['action'] === 'register') {
    $ime = $_POST['ime'] ?? '';
    $prezime = $_POST['prezime'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $telefon = $_POST['telefon'] ?? '';
    
    $result = registerUser($conn, $ime, $prezime, $email, $password, $telefon);
    
    if ($result === "Registracija je uspešna. Proverite email za verifikaciju naloga.") {
        // Show success message and redirect
        $_SESSION['message'] = $result;
        header('Location: login.php');
        exit();
    } else {
        // Show error message
        $error = $result;
    }
}
?>
```

### Login with Session Management

```php
<?php
require_once 'config.php';
require_once 'auth_functions.php';

session_start();

// Check if user is already logged in
if (isset($_SESSION['user_id']) || isset($_COOKIE['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

// Handle login form submission
if ($_POST['action'] === 'login') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $result = loginUser($conn, $email, $password);
    
    if ($result === "Prijava je uspešna.") {
        header('Location: dashboard.php');
        exit();
    } else {
        $error = $result;
    }
}
?>
```

### Password Reset Flow

```php
<?php
require_once 'config.php';
require_once 'auth_functions.php';

// Step 1: Request password reset
if ($_POST['action'] === 'forgot_password') {
    $email = $_POST['email'] ?? '';
    $result = forgotPasswordRequest($conn, $email);
    echo $result;
}

// Step 2: Reset password with token
if ($_POST['action'] === 'reset_password') {
    $token = $_POST['token'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    
    $result = resetPassword($conn, $token, $newPassword);
    
    if ($result === "Lozinka je uspešno resetovana.") {
        header('Location: login.php?message=password_reset_success');
        exit();
    } else {
        echo $result;
    }
}
?>
```

### Protected Page with Authentication Check

```php
<?php
require_once 'config.php';
require_once 'auth_functions.php';

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) && !isset($_COOKIE['user_id'])) {
    header('Location: login.php');
    exit();
}

// If using remember me cookie, restore session
if (!isset($_SESSION['user_id']) && isset($_COOKIE['user_id'])) {
    $user_id = $_COOKIE['user_id'];
    // Fetch user data from database and restore session
    $query = "SELECT ime, prezime, email FROM KORISNIK WHERE id = ? AND verifikovan = 1";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $ime, $prezime, $email);
    
    if (mysqli_stmt_fetch($stmt)) {
        $_SESSION['user_id'] = $user_id;
        $_SESSION['ime'] = $ime;
        $_SESSION['prezime'] = $prezime;
        $_SESSION['email'] = $email;
    } else {
        // Invalid cookie, redirect to login
        setcookie("user_id", "", time() - 3600, "/");
        header('Location: login.php');
        exit();
    }
    mysqli_stmt_close($stmt);
}

// User is authenticated, show protected content
echo "Dobrodošli, " . $_SESSION['ime'] . " " . $_SESSION['prezime'];
?>
```

---

## Security Considerations

### Current Security Measures

1. **SQL Injection Prevention**: Uses prepared statements for all database queries
2. **Input Sanitization**: Trims whitespace from all user inputs
3. **Session Security**: Proper session management and destruction
4. **Token-Based Operations**: Secure token generation for verification and reset
5. **Email Uniqueness**: Prevents duplicate email registration

### Security Limitations and Recommendations

⚠️ **Important Security Notes**:

1. **MD5 Password Hashing**: 
   - **Current**: Uses MD5 for password hashing
   - **Recommendation**: Upgrade to `password_hash()` and `password_verify()` with bcrypt
   - **Example**:
   ```php
   // Instead of: $hashed_password = md5($password);
   $hashed_password = password_hash($password, PASSWORD_DEFAULT);
   
   // For verification:
   if (password_verify($password, $hashed_password)) {
       // Password is correct
   }
   ```

2. **HTTPS Requirement**: Always use HTTPS in production for credential transmission

3. **Password Policy**: Implement minimum password strength requirements

4. **Rate Limiting**: Add protection against brute force attacks

5. **Email Verification**: Currently generates tokens but doesn't send emails

### Recommended Security Improvements

```php
// Enhanced password hashing function
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Enhanced password verification
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Rate limiting example
function checkRateLimit($conn, $email, $action = 'login') {
    $query = "SELECT COUNT(*) as attempts FROM login_attempts 
              WHERE email = ? AND action = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ss", $email, $action);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $attempts);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
    
    return $attempts < 5; // Allow 5 attempts per hour
}
```

---

## Error Handling

### Error Response Patterns

All functions return string messages for both success and error conditions:

- **Success Messages**: Descriptive success confirmations
- **Error Messages**: User-friendly error descriptions
- **System Errors**: Fatal errors with `die()` for critical failures

### Common Error Scenarios

1. **Database Connection Issues**: Handled in `config.php`
2. **Duplicate Email Registration**: Graceful error message
3. **Invalid Login Credentials**: Generic error to prevent user enumeration
4. **Expired Tokens**: Clear expiration messages with retry instructions
5. **SQL Preparation Failures**: Fatal errors with debugging information

### Error Handling Best Practices

```php
// Wrapper function for better error handling
function safeExecuteAuthFunction($callback, $errorContext = '') {
    try {
        return $callback();
    } catch (Exception $e) {
        error_log("Auth Error [$errorContext]: " . $e->getMessage());
        return "Došlo je do greške. Molimo pokušajte ponovo.";
    }
}

// Usage example
$result = safeExecuteAuthFunction(function() use ($conn, $email, $password) {
    return loginUser($conn, $email, $password);
}, 'User Login');
```

---

## Conclusion

This authentication system provides a solid foundation for user management in a library application. While functional as-is, consider implementing the security recommendations for production use, particularly upgrading password hashing and adding email functionality for verification and reset processes.

For questions or issues, refer to the individual function documentation above or check the inline comments in the source code files.