<?php
require_once 'includes/config.php';
require_once 'includes/database.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirectTo('dashboard.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email)) {
        $errors[] = 'Email je obavezan.';
    }
    
    if (empty($password)) {
        $errors[] = 'Lozinka je obavezna.';
    }
    
    if (empty($errors)) {
        $user = authenticateUser($email, $password);
        
        if ($user) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_ime'] = $user['ime'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['login_time'] = time();
            
            // Update last login
            updateUserLastLogin($user['id']);
            
            // Redirect based on role
            if ($user['role'] === 'admin') {
                redirectTo('admin/dashboard.php');
            } else {
                redirectTo('dashboard.php');
            }
        } else {
            $errors[] = 'Neispravni podaci za prijavu.';
        }
    }
}

$pageTitle = 'Prijava';
include 'includes/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-sign-in-alt"></i> Prijava
                    </h3>
                </div>
                <div class="card-body">
                    <?php if (hasFlashMessages()): ?>
                        <?php foreach (getFlashMessages() as $message): ?>
                            <div class="alert alert-<?php echo $message['type']; ?>">
                                <?php echo $message['message']; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="email" class="form-label">
                                <i class="fas fa-envelope"></i> Email adresa
                            </label>
                            <input 
                                type="email" 
                                class="form-control" 
                                id="email" 
                                name="email" 
                                value="<?php echo htmlspecialchars($email ?? ''); ?>"
                                required
                                autofocus
                            >
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">
                                <i class="fas fa-lock"></i> Lozinka
                            </label>
                            <input 
                                type="password" 
                                class="form-control" 
                                id="password" 
                                name="password" 
                                required
                            >
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="remember">
                            <label class="form-check-label" for="remember">
                                Zapamti me
                            </label>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-sign-in-alt"></i> Prijaviť se
                            </button>
                        </div>
                    </form>
                    
                    <hr>
                    
                    <div class="text-center">
                        <p class="mb-2">Demo nalozi za testiranje:</p>
                        <small class="text-muted">
                            <strong>Admin:</strong> admin@example.com / admin123<br>
                            <strong>Korisnik:</strong> Registrujte se ili kreirajte novi nalog
                        </small>
                    </div>
                </div>
                <div class="card-footer text-center">
                    <small>
                        Nemate nalog? 
                        <a href="register.php">Registrujte se ovde</a>
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>