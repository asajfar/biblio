<?php
require_once 'includes/config.php';

// Destroy session
session_destroy();

// Clear session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Start new session for flash message
session_start();
setFlashMessage('info', 'Uspešno ste se odjavili.');

// Redirect to home page
redirectTo('index.php');
?>