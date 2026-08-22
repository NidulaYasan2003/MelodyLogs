    </main>

    <!-- Footer Component -->
    <footer class="footer-custom">
        <div class="container">
            <div class="row g-4 justify-content-between mb-4">
                <!-- Brand & Mission -->
                <div class="col-lg-4">
                    <a class="brand-logo mb-3" href="index.php">
                        <div class="brand-icon-box">
                            <i class="bi bi-soundwave fs-5"></i>
                        </div>
                        <span>Melody<span class="text-gradient">Logs</span></span>
                    </a>
                    <p class="text-secondary small leading-relaxed">
                        A collaborative journal and knowledge hub for singers, vocalists, and voice practitioners worldwide. Document your vocal journey, share acoustic insights, and master the art of vocal performance.
                    </p>
                    <div class="soundwave-box mt-3">
                        <div class="soundwave-bar"></div>
                        <div class="soundwave-bar"></div>
                        <div class="soundwave-bar"></div>
                        <div class="soundwave-bar"></div>
                        <div class="soundwave-bar"></div>
                    </div>
                </div>

                <!-- Vocal Categories Quick Nav -->
                <div class="col-6 col-md-3 col-lg-2">
                    <h6 class="text-white fw-bold mb-3 small text-uppercase tracking-wider">Categories</h6>
                    <ul class="list-unstyled small mb-0 d-flex flex-column gap-2">
                        <li><a href="index.php?category=Vocal+Technique" class="text-secondary text-decoration-none hover-white"><i class="bi bi-chevron-right me-1 text-primary small"></i> Vocal Technique</a></li>
                        <li><a href="index.php?category=Vocal+Warmups" class="text-secondary text-decoration-none hover-white"><i class="bi bi-chevron-right me-1 text-primary small"></i> Warmups & SOVTE</a></li>
                        <li><a href="index.php?category=Voice+Care+%26+Health" class="text-secondary text-decoration-none hover-white"><i class="bi bi-chevron-right me-1 text-primary small"></i> Vocal Health</a></li>
                        <li><a href="index.php?category=Studio+%26+Recording" class="text-secondary text-decoration-none hover-white"><i class="bi bi-chevron-right me-1 text-primary small"></i> Studio & Mics</a></li>
                    </ul>
                </div>

                <!-- Platform Quick Links -->
                <div class="col-6 col-md-3 col-lg-2">
                    <h6 class="text-white fw-bold mb-3 small text-uppercase tracking-wider">MelodyLogs</h6>
                    <ul class="list-unstyled small mb-0 d-flex flex-column gap-2">
                        <li><a href="index.php" class="text-secondary text-decoration-none"><i class="bi bi-chevron-right me-1 text-primary small"></i> Feed / Home</a></li>
                        <?php if (is_logged_in()): ?>
                            <li><a href="editor.php" class="text-secondary text-decoration-none"><i class="bi bi-chevron-right me-1 text-primary small"></i> New Melody Log</a></li>
                            <li><a href="logout.php" class="text-danger text-decoration-none"><i class="bi bi-chevron-right me-1 text-danger small"></i> Sign Out</a></li>
                        <?php else: ?>
                            <li><a href="login.php" class="text-secondary text-decoration-none"><i class="bi bi-chevron-right me-1 text-primary small"></i> Sign In</a></li>
                            <li><a href="register.php" class="text-secondary text-decoration-none"><i class="bi bi-chevron-right me-1 text-primary small"></i> Create Account</a></li>
                        <?php endif; ?>
                    </ul>
                </div>

                <!-- Vocal Inspiration Quote -->
                <div class="col-lg-3">
                    <div class="p-3 rounded-3" style="background: rgba(255, 255, 255, 0.03); border: 1px solid var(--border-color);">
                        <div class="d-flex align-items-center gap-2 mb-2 text-warning">
                            <i class="bi bi-quote fs-4"></i>
                            <span class="fw-semibold small text-white">Vocal Wisdom</span>
                        </div>
                        <p class="text-secondary fst-italic small mb-0">
                            "The voice is the muscle of the soul. Train it with patience, protect it with love, and let it resonate freely."
                        </p>
                    </div>
                </div>
            </div>

            <!-- Bottom Copyright -->
            <div class="pt-4 border-top border-secondary border-opacity-25 d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
                <p class="text-muted small mb-0">
                    &copy; <?= date('Y') ?> MelodyLogs · Designed for Vocalists & Musicians.
                </p>
                <div class="d-flex align-items-center gap-3 text-secondary small">
                    <span><i class="bi bi-shield-check text-success me-1"></i> Secure Core PHP & PDO</span>
                    <span><i class="bi bi-bootstrap-fill text-primary me-1"></i> Bootstrap 5</span>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5.3 Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    
    <!-- Custom Theme Scripts -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Handle Hamburger Animation State
            const mobileToggle = document.getElementById('mobileMenuToggle');
            const navbarCollapse = document.getElementById('navbarMelody');
            
            if (mobileToggle && navbarCollapse) {
                // Listen to Bootstrap's native collapse events
                navbarCollapse.addEventListener('show.bs.collapse', () => {
                    mobileToggle.setAttribute('aria-expanded', 'true');
                });
                
                navbarCollapse.addEventListener('hide.bs.collapse', () => {
                    mobileToggle.setAttribute('aria-expanded', 'false');
                });
            }

            // Theme Toggle Logic
            const themeBtns = document.querySelectorAll('.theme-toggle-btn');
            const htmlElement = document.documentElement;
            
            const setTheme = (theme) => {
                htmlElement.setAttribute('data-bs-theme', theme);
                localStorage.setItem('melodylogs_theme', theme);
                
                themeBtns.forEach(btn => {
                    const darkIcon = btn.querySelector('.theme-icon-dark');
                    const lightIcon = btn.querySelector('.theme-icon-light');
                    if (theme === 'light') {
                        darkIcon.classList.add('d-none');
                        lightIcon.classList.remove('d-none');
                    } else {
                        darkIcon.classList.remove('d-none');
                        lightIcon.classList.add('d-none');
                    }
                });
            };
            
            const savedTheme = localStorage.getItem('melodylogs_theme');
            if (savedTheme) {
                setTheme(savedTheme);
            } else {
                if (window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches) {
                    setTheme('light');
                } else {
                    setTheme('dark');
                }
            }
            
            themeBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    const currentTheme = htmlElement.getAttribute('data-bs-theme');
                    setTheme(currentTheme === 'dark' ? 'light' : 'dark');
                });
            });
        });
    </script>
</body>
</html>
