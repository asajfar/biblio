<?php
require_once 'includes/config.php';
require_once 'includes/database.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirectTo('dashboard.php');
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ime = sanitizeInput($_POST['ime'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($ime)) {
        $errors[] = 'Ime je obavezno.';
    }
    
    if (empty($email)) {
        $errors[] = 'Email je obavezan.';
    } elseif (!validateEmail($email)) {
        $errors[] = 'Email adresa nije validna.';
    }
    
    if (empty($password)) {
        $errors[] = 'Lozinka je obavezna.';
    } elseif (strlen($password) < PASSWORD_MIN_LENGTH) {
        $errors[] = 'Lozinka mora imati najmanje ' . PASSWORD_MIN_LENGTH . ' karaktera.';
    }
    
    if ($password !== $confirmPassword) {
        $errors[] = 'Lozinke se ne poklapaju.';
    }
    
    // Check if email already exists
    if (empty($errors) && $db->exists('users', 'email = ?', [$email])) {
        $errors[] = 'Email adresa je već registrovana.';
    }
    
    // Create user if no errors
    if (empty($errors)) {
        $userId = createUser($ime, $email, $password);
        if ($userId) {
            $success = true;
            setFlashMessage('success', 'Registracija je uspešna! Možete se prijaviti.');
        } else {
            $errors[] = 'Greška pri registraciji. Pokušajte ponovo.';
        }
    }
}

$pageTitle = 'Registracija';
include 'includes/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-user-plus"></i> Registracija
                    </h3>
                </div>
                <div class="card-body">
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            Registracija je uspešna! 
                            <a href="login.php" class="alert-link">Prijavite se ovde</a>
                        </div>
                    <?php else: ?>
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
                                <label for="ime" class="form-label">
                                    <i class="fas fa-user"></i> Ime i prezime
                                </label>
                                <input 
                                    type="text" 
                                    class="form-control" 
                                    id="ime" 
                                    name="ime" 
                                    value="<?php echo htmlspecialchars($ime ?? ''); ?>"
                                    required
                                >
                            </div>
                            
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
                                    minlength="<?php echo PASSWORD_MIN_LENGTH; ?>"
                                    required
                                >
                                <div class="form-text">
                                    Minimum <?php echo PASSWORD_MIN_LENGTH; ?> karaktera
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">
                                    <i class="fas fa-lock"></i> Potvrdite lozinku
                                </label>
                                <input 
                                    type="password" 
                                    class="form-control" 
                                    id="confirm_password" 
                                    name="confirm_password" 
                                    required
                                >
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-user-plus"></i> Registruj se
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
                <div class="card-footer text-center">
                    <small>
                        Već imate nalog? 
                        <a href="login.php">Prijavite se ovde</a>
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Password confirmation validation
document.getElementById('confirm_password').addEventListener('input', function() {
    const password = document.getElementById('password').value;
    const confirmPassword = this.value;
    
    if (password !== confirmPassword) {
        this.setCustomValidity('Lozinke se ne poklapaju');
    } else {
        this.setCustomValidity('');
    }
});
</script>

<?php include 'includes/footer.php'; ?>