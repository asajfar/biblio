<?php
require_once 'includes/config.php';
require_once 'includes/database.php';

// Get active companies for map
$companies = getActiveCompaniesForMap();

// Get categories for search
$categories = getAllCategories();

// Get statistics
$stats = getDashboardStats();

$pageTitle = 'Početna';
$pageDescription = 'Pronađite najbolje lokalne firme u vašoj okolini. Platforma za promociju malih biznisa.';
$pageKeywords = 'lokalni biznis, firme, mapa, pretraga, Srbija';

include 'includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-section bg-primary text-white py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1 class="display-4 fw-bold mb-4">
                    Pronađite najbolje <span class="text-warning">lokalne firme</span>
                </h1>
                <p class="lead mb-4">
                    Otkrijte kvalitetne usluge i proizvode u vašoj okolini. 
                    Više od <?php echo number_format($stats['active_companies']); ?> aktivnih firmi čeka na vas!
                </p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="search.php" class="btn btn-warning btn-lg">
                        <i class="fas fa-search"></i> Pretražite firme
                    </a>
                    <?php if (!isLoggedIn()): ?>
                        <a href="register.php" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-plus"></i> Dodajte svoju firmu
                        </a>
                    <?php else: ?>
                        <a href="add-company.php" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-plus"></i> Dodajte firmu
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="text-center">
                    <i class="fas fa-store fa-10x text-warning opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Search Section -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="row">
            <div class="col-lg-4 mb-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-header bg-white border-0">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-search text-primary"></i>
                            Pretraga firmi
                        </h5>
                    </div>
                    <div class="card-body">
                        <form id="searchForm" action="search.php" method="GET">
                            <div class="mb-3">
                                <label for="search" class="form-label">Naziv ili ključna reč</label>
                                <input type="text" class="form-control" id="search" name="search" 
                                       placeholder="npr. restoran, frizer...">
                            </div>
                            
                            <div class="mb-3">
                                <label for="category" class="form-label">Kategorija</label>
                                <select class="form-select" id="category" name="category">
                                    <option value="">Sve kategorije</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo htmlspecialchars($category['naziv']); ?>">
                                            <?php echo htmlspecialchars($category['naziv']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="location" class="form-label">Lokacija</label>
                                <input type="text" class="form-control" id="location" name="location" 
                                       placeholder="Grad ili adresa">
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Pretražite
                                </button>
                            </div>
                        </form>
                        
                        <hr>
                        
                        <div class="text-center">
                            <small class="text-muted">
                                Ili kliknite na mapu da pretražite po lokaciji
                            </small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-8 mb-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-header bg-white border-0">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-map-marked-alt text-success"></i>
                            Interaktivna mapa
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div id="map" style="height: 400px; border-radius: 0 0 0.375rem 0.375rem;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Categories Section -->
<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-6 fw-bold">Kategorije firmi</h2>
            <p class="lead text-muted">Pronađite firme po kategorijama</p>
        </div>
        
        <div class="row">
            <?php foreach (array_slice($categories, 0, 8) as $category): ?>
                <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                    <a href="search.php?category=<?php echo urlencode($category['naziv']); ?>" 
                       class="text-decoration-none">
                        <div class="card category-card h-100 shadow-sm">
                            <div class="card-body text-center">
                                <div class="category-icon mb-3" style="color: <?php echo $category['boja']; ?>">
                                    <i class="<?php echo $category['ikona']; ?> fa-3x"></i>
                                </div>
                                <h6 class="card-title"><?php echo htmlspecialchars($category['naziv']); ?></h6>
                                <p class="card-text text-muted small">
                                    <?php echo htmlspecialchars($category['opis']); ?>
                                </p>
                                <small class="text-muted">
                                    <?php 
                                    $count = $db->count('companies', "kategorija = ? AND status = 'active'", [$category['naziv']]);
                                    echo "$count " . ($count == 1 ? 'firma' : ($count < 5 ? 'firme' : 'firmi'));
                                    ?>
                                </small>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="text-center">
            <a href="categories.php" class="btn btn-outline-primary">
                <i class="fas fa-th-large"></i> Sve kategorije
            </a>
        </div>
    </div>
</section>

<!-- Statistics Section -->
<section class="py-5 bg-dark text-white">
    <div class="container">
        <div class="row text-center">
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="stat-item">
                    <div class="stat-number display-4 fw-bold text-warning">
                        <?php echo number_format($stats['active_companies']); ?>
                    </div>
                    <div class="stat-label">Aktivnih firmi</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="stat-item">
                    <div class="stat-number display-4 fw-bold text-warning">
                        <?php echo number_format($stats['total_users']); ?>
                    </div>
                    <div class="stat-label">Registrovanih korisnika</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="stat-item">
                    <div class="stat-number display-4 fw-bold text-warning">
                        <?php echo number_format($stats['total_reviews']); ?>
                    </div>
                    <div class="stat-label">Recenzija</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="stat-item">
                    <div class="stat-number display-4 fw-bold text-warning">
                        <?php echo count($categories); ?>
                    </div>
                    <div class="stat-label">Kategorija</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<?php if (!isLoggedIn()): ?>
<section class="py-5 bg-primary text-white">
    <div class="container text-center">
        <h2 class="mb-4">Promoviši svoju firmu besplatno!</h2>
        <p class="lead mb-4">
            Pridružite se našoj platformi i povećajte vidljivost vaše firme.
        </p>
        <a href="register.php" class="btn btn-warning btn-lg me-3">
            <i class="fas fa-user-plus"></i> Registruj se
        </a>
        <a href="page.php?slug=kako-funkcionise" class="btn btn-outline-light btn-lg">
            <i class="fas fa-info-circle"></i> Kako funkcioniše
        </a>
    </div>
</section>
<?php endif; ?>

<script>
// Map initialization
document.addEventListener('DOMContentLoaded', function() {
    // Initialize map
    const map = L.map('map').setView([<?php echo DEFAULT_MAP_LAT; ?>, <?php echo DEFAULT_MAP_LNG; ?>], <?php echo DEFAULT_MAP_ZOOM; ?>);
    
    // Add tile layer
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);
    
    // Companies data
    const companies = <?php echo json_encode($companies); ?>;
    
    // Category colors
    const categoryColors = {
        <?php foreach ($categories as $category): ?>
        '<?php echo $category['naziv']; ?>': '<?php echo $category['boja']; ?>',
        <?php endforeach; ?>
        'default': '#007bff'
    };
    
    // Add markers for companies
    companies.forEach(function(company) {
        if (company.lat && company.lng) {
            const color = categoryColors[company.kategorija] || categoryColors.default;
            
            const marker = L.circleMarker([company.lat, company.lng], {
                radius: 8,
                fillColor: color,
                color: '#fff',
                weight: 2,
                opacity: 1,
                fillOpacity: 0.8
            }).addTo(map);
            
            marker.bindPopup(`
                <div class="text-center">
                    <h6 class="mb-2">${company.naziv}</h6>
                    <p class="mb-2 small text-muted">${company.kategorija}</p>
                    <p class="mb-2 small">${company.adresa || 'Adresa nije navedena'}</p>
                    <a href="company.php?id=${company.id}" class="btn btn-sm btn-primary">
                        <i class="fas fa-eye"></i> Pogledajte
                    </a>
                </div>
            `);
        }
    });
    
    // Map click event for location search
    map.on('click', function(e) {
        const lat = e.latlng.lat;
        const lng = e.latlng.lng;
        
        // Redirect to search with coordinates
        window.location.href = `search.php?lat=${lat}&lng=${lng}&radius=5`;
    });
});

// Category card hover effects
document.querySelectorAll('.category-card').forEach(card => {
    card.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-5px)';
        this.style.transition = 'transform 0.3s ease';
    });
    
    card.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0)';
    });
});
</script>

<?php include 'includes/footer.php'; ?>