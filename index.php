<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HairCut Suggester - Find Your Perfect Hairstyle</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <a href="#home" aria-label="GAÑO'S Barbershop Home" class="brand-link">
                    <span class="brand-logo-wrap">
                        <img src="assets/images/logo.png" alt="GAÑO'S Barbershop logo" class="brand-logo" />
                    </span>
                    <span class="brand-title">GAÑO'S Barbershop</span>
                </a>
            </div>
            <div class="nav-links">
                <a href="#home">Home</a>
                <a href="#how-it-works">How It Works</a>
                <a href="#features">Features</a>
                <a href="#about">About</a>
                <a href="login.php" class="btn-login">Log In / Sign Up</a>
            </div>
            <div class="nav-toggle">
                <i class="fas fa-bars"></i>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="hero">
        <div class="hero-container">
            <div class="hero-content">
                <h1 class="hero-title">Find Your Perfect Haircut with Smart Recommendations</h1>
                <p class="hero-subtitle">Discover the ideal hairstyle for your face shape, hair type, and lifestyle with our intelligent recommendation system</p>
                
                <div class="features-list">
                    <div class="feature-item">
                        <i class="fas fa-check-circle"></i>
                        <span>Works with your unique face shape</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-check-circle"></i>
                        <span>Instant personalized results</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-check-circle"></i>
                        <span>No sign up required - privacy first</span>
                    </div>
                </div>

                <div class="hero-buttons">
                    <a href="register.php" class="btn btn-primary btn-large">
                        <i class="fas fa-rocket"></i>
                        Get Started
                    </a>
                    <a href="#how-it-works" class="btn btn-secondary btn-large">
                        <i class="fas fa-play"></i>
                        See How It Works
                    </a>
                </div>

                <div class="hero-stats">
                    <div class="stat">
                        <span class="stat-number">10,000+</span>
                        <span class="stat-label">Happy Users</span>
                    </div>
                    <div class="stat">
                        <span class="stat-number">50+</span>
                        <span class="stat-label">Hairstyles</span>
                    </div>
                    <div class="stat">
                        <span class="stat-number">98%</span>
                        <span class="stat-label">Satisfaction Rate</span>
                    </div>
                </div>
            </div>
            
            <div class="hero-image">
                <div class="image-container">
                    <img src="assets/images/gano_logo.jpg" alt="GAÑO'S Barbershop logo" class="hero-logo" />
                    <div class="image-overlay">
                        <div class="style-badge">
                            <i class="fas fa-star"></i>
                            Perfect Style Match
                        </div>
                    </div>
                    <div class="floating-elements">
                        <div class="floating-element element-1">
                            <i class="fas fa-cut"></i>
                        </div>
                        <div class="floating-element element-2">
                            <i class="fas fa-heart"></i>
                        </div>
                        <div class="floating-element element-3">
                            <i class="fas fa-magic"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section id="how-it-works" class="how-it-works">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">How It Works</h2>
                <p class="section-subtitle">Three simple steps to discover your perfect hairstyle</p>
            </div>
            
            <div class="steps-container">
                <div class="step">
                    <div class="step-icon">
                        <i class="fas fa-user-circle"></i>
                        <span class="step-number">1</span>
                    </div>
                    <h3>Identify Your Face Shape</h3>
                    <p>Take our quick quiz to determine your face shape: oval, round, square, heart, oblong, or diamond.</p>
                    <div class="step-image">
                        <i class="fas fa-search"></i>
                    </div>
                </div>

                <div class="step">
                    <div class="step-icon">
                        <i class="fas fa-sliders-h"></i>
                        <span class="step-number">2</span>
                    </div>
                    <h3>Set Your Preferences</h3>
                    <p>Tell us about your hair type, lifestyle, maintenance preferences, and style goals.</p>
                    <div class="step-image">
                        <i class="fas fa-cogs"></i>
                    </div>
                </div>

                <div class="step">
                    <div class="step-icon">
                        <i class="fas fa-magic"></i>
                        <span class="step-number">3</span>
                    </div>
                    <h3>Get Recommendations</h3>
                    <p>Receive curated haircut suggestions with styling tips, maintenance guides, and professional advice.</p>
                    <div class="step-image">
                        <i class="fas fa-star"></i>
                    </div>
                </div>
            </div>

            <div class="process-visual">
                <div class="process-line"></div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Why Choose Our Suggester?</h2>
                <p class="section-subtitle">Powered by expert knowledge and personalized recommendations</p>
            </div>
            
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-bullseye"></i>
                    </div>
                    <h3>Face Shape Analysis</h3>
                    <p>Scientific approach to matching haircuts with your unique facial structure and proportions.</p>
                    <a href="#" class="feature-link">Learn More <i class="fas fa-arrow-right"></i></a>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-brain"></i>
                    </div>
                    <h3>Expert Recommendations</h3>
                    <p>Curated suggestions from professional stylists and hair experts with years of experience.</p>
                    <a href="#" class="feature-link">Learn More <i class="fas fa-arrow-right"></i></a>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <h3>Instant Results</h3>
                    <p>Get your personalized recommendations in seconds, not hours. Fast and efficient.</p>
                    <a href="#" class="feature-link">Learn More <i class="fas fa-arrow-right"></i></a>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-heart"></i>
                    </div>
                    <h3>Lifestyle Matching</h3>
                    <p>Find styles that fit your daily routine, maintenance preferences, and personal lifestyle.</p>
                    <a href="#" class="feature-link">Learn More <i class="fas fa-arrow-right"></i></a>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h3>Mobile Optimized</h3>
                    <p>Perfect experience on any device. Take your style recommendations anywhere you go.</p>
                    <a href="#" class="feature-link">Learn More <i class="fas fa-arrow-right"></i></a>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3>Privacy Focused</h3>
                    <p>Your data is secure. No unnecessary tracking or data collection. Privacy first approach.</p>
                    <a href="#" class="feature-link">Learn More <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="about">
        <div class="container">
            <div class="about-content">
                <div class="about-text">
                    <h2>About HairCut Suggester</h2>
                    <p>We believe everyone deserves to feel confident with their hairstyle. Our platform combines expert hairstylist knowledge with modern technology to provide personalized haircut recommendations.</p>
                    
                    <div class="about-features">
                        <div class="about-feature">
                            <i class="fas fa-users"></i>
                            <div>
                                <h4>Expert Team</h4>
                                <p>Professional hairstylists and beauty experts</p>
                            </div>
                        </div>
                        <div class="about-feature">
                            <i class="fas fa-database"></i>
                            <div>
                                <h4>Comprehensive Database</h4>
                                <p>Extensive collection of hairstyles and trends</p>
                            </div>
                        </div>
                        <div class="about-feature">
                            <i class="fas fa-chart-line"></i>
                            <div>
                                <h4>Proven Results</h4>
                                <p>Thousands of satisfied users worldwide</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="about-image">
                    <img src="assets/images/about-us.jpg" alt="Professional hairstylist at work">
                    <div class="about-overlay">
                        <div class="play-button">
                            <i class="fas fa-play"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta">
        <div class="container">
            <div class="cta-content">
                <h2>Ready to Transform Your Look?</h2>
                <p>Join thousands of users who found their perfect hairstyle with our intelligent recommendation system</p>
                <div class="cta-buttons">
                    <a href="register.php" class="btn btn-primary btn-large">
                        <i class="fas fa-rocket"></i>
                        Start Your Style Journey
                    </a>
                    <a href="login.php" class="btn btn-outline btn-large">
                        <i class="fas fa-sign-in-alt"></i>
                        Already Have Account?
                    </a>
                </div>
                <div class="cta-note">
                    <small><i class="fas fa-lock"></i> Secure and private. No spam, ever.</small>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3><i class="fas fa-cut"></i> HairCut Suggester</h3>
                    <p>Find your perfect hairstyle with our intelligent recommendation system. Expert advice meets modern technology.</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                
                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="#home">Home</a></li>
                        <li><a href="#how-it-works">How It Works</a></li>
                        <li><a href="#features">Features</a></li>
                        <li><a href="login.php">Login</a></li>
                        <li><a href="register.php">Register</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Support</h4>
                    <ul>
                        <li><a href="#">Help Center</a></li>
                        <li><a href="#">Contact Us</a></li>
                        <li><a href="#">Privacy Policy</a></li>
                        <li><a href="#">Terms of Service</a></li>
                        <li><a href="#">FAQ</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Newsletter</h4>
                    <p>Get the latest hair trends and styling tips delivered to your inbox.</p>
                    <div class="newsletter-form">
                        <input type="email" placeholder="Enter your email">
                        <button type="submit"><i class="fas fa-paper-plane"></i></button>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2025 HairCut Suggester. All rights reserved.</p>
                <div class="footer-links">
                    <a href="#">Privacy</a>
                    <a href="#">Terms</a>
                    <a href="#">Cookies</a>
                </div>
            </div>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
</body>
</html>
