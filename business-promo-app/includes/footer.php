    <!-- Footer -->
    <footer class="bg-dark text-light py-5 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <h5>
                        <i class="fas fa-store"></i>
                        <?php echo SITE_NAME; ?>
                    </h5>
                    <p class="text-light">
                        Platforma za promociju lokalnih biznisa. 
                        Pronađite najbolje firme u vašoj okolini ili promovisite svoju firmu.
                    </p>
                    <div class="social-links">
                        <a href="#" class="text-light me-3"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-light me-3"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-light me-3"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-light me-3"><i class="fab fa-linkedin"></i></a>
                    </div>
                </div>
                
                <div class="col-lg-2 mb-4">
                    <h6>Navigacija</h6>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-light text-decoration-none">Početna</a></li>
                        <li><a href="search.php" class="text-light text-decoration-none">Pretraga</a></li>
                        <li><a href="categories.php" class="text-light text-decoration-none">Kategorije</a></li>
                        <?php if (!isLoggedIn()): ?>
                            <li><a href="register.php" class="text-light text-decoration-none">Registracija</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <div class="col-lg-2 mb-4">
                    <h6>Za firme</h6>
                    <ul class="list-unstyled">
                        <?php if (isLoggedIn()): ?>
                            <li><a href="add-company.php" class="text-light text-decoration-none">Dodaj firmu</a></li>
                            <li><a href="dashboard.php" class="text-light text-decoration-none">Dashboard</a></li>
                        <?php else: ?>
                            <li><a href="login.php" class="text-light text-decoration-none">Prijava</a></li>
                        <?php endif; ?>
                        <li><a href="page.php?slug=cene" class="text-light text-decoration-none">Cene</a></li>
                        <li><a href="page.php?slug=pomoc" class="text-light text-decoration-none">Pomoć</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-2 mb-4">
                    <h6>Informacije</h6>
                    <ul class="list-unstyled">
                        <li><a href="page.php?slug=o-nama" class="text-light text-decoration-none">O nama</a></li>
                        <li><a href="page.php?slug=kontakt" class="text-light text-decoration-none">Kontakt</a></li>
                        <li><a href="page.php?slug=uslovi-koriscenja" class="text-light text-decoration-none">Uslovi korišćenja</a></li>
                        <li><a href="page.php?slug=privatnost" class="text-light text-decoration-none">Privatnost</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-2 mb-4">
                    <h6>Kontakt</h6>
                    <ul class="list-unstyled text-light">
                        <li><i class="fas fa-envelope"></i> <?php echo getSetting('contact_email', 'kontakt@lokalnibiznis.rs'); ?></li>
                        <li><i class="fas fa-phone"></i> +381 11 123 4567</li>
                        <li><i class="fas fa-map-marker-alt"></i> Beograd, Srbija</li>
                    </ul>
                </div>
            </div>
            
            <hr class="my-4">
            
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0 text-light">
                        &copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. Sva prava zadržana.
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <small class="text-light">
                        Verzija MVP 1.0 | 
                        <span id="visitors-count">Ukupno firmi: <?php echo $db->count('companies', "status = 'active'"); ?></span>
                    </small>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <!-- Custom JS -->
    <script src="js/main.js"></script>
    
    <!-- Page specific scripts -->
    <?php if (isset($pageScripts)): ?>
        <?php foreach ($pageScripts as $script): ?>
            <script src="<?php echo $script; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Inline scripts -->
    <?php if (isset($inlineScripts)): ?>
        <script>
            <?php echo $inlineScripts; ?>
        </script>
    <?php endif; ?>
    
    <!-- Google Analytics (if needed) -->
    <?php if (getSetting('google_analytics_id')): ?>
        <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo getSetting('google_analytics_id'); ?>"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', '<?php echo getSetting('google_analytics_id'); ?>');
        </script>
    <?php endif; ?>
</body>
</html>