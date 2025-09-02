// ===== MAIN WEBSITE JAVASCRIPT =====

document.addEventListener('DOMContentLoaded', function() {
    // Initialize all components
    initializeNavigation();
    initializeAuthForms();
    initializeScrollEffects();
    initializeAnimations();
    initializeCounters();
    initializeModals();
    initializeToasts();
    initializeFormValidation();
    initializeFaceShapeQuiz();
});

// ===== NAVIGATION =====
function initializeNavigation() {
    const navToggle = document.querySelector('.nav-toggle');
    const navMenu = document.querySelector('.nav-menu');
    const navLinks = document.querySelectorAll('.nav-link');
    
    if (navToggle && navMenu) {
        navToggle.addEventListener('click', function() {
            navMenu.classList.toggle('active');
            navToggle.classList.toggle('active');
        });
    }
    
    // Close mobile menu when clicking links
    navLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (navMenu) {
                navMenu.classList.remove('active');
                navToggle.classList.remove('active');
            }
        });
    });
    
    // Navbar scroll effect
    window.addEventListener('scroll', function() {
        const navbar = document.querySelector('.navbar');
        if (navbar) {
            if (window.scrollY > 100) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        }
    });
}

// ===== AUTHENTICATION FORMS =====
function initializeAuthForms() {
    const passwordInputs = document.querySelectorAll('input[type="password"]');
    const toggleButtons = document.querySelectorAll('.password-toggle');
    
    toggleButtons.forEach((button, index) => {
        button.addEventListener('click', function() {
            const input = passwordInputs[index];
            const icon = this.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });
    
    // Password strength meter
    const passwordInput = document.querySelector('#password');
    const strengthMeter = document.querySelector('.strength-meter');
    const strengthText = document.querySelector('.strength-text');
    
    if (passwordInput && strengthMeter) {
        passwordInput.addEventListener('input', function() {
            const strength = calculatePasswordStrength(this.value);
            updatePasswordStrength(strength, strengthMeter, strengthText);
        });
    }
    
    // Social login buttons
    const socialButtons = document.querySelectorAll('.social-btn');
    socialButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const provider = this.getAttribute('data-provider');
            showToast(`${provider} login would be implemented here`, 'info');
        });
    });
}

function calculatePasswordStrength(password) {
    let strength = 0;
    
    if (password.length >= 8) strength += 25;
    if (password.match(/[a-z]/)) strength += 25;
    if (password.match(/[A-Z]/)) strength += 25;
    if (password.match(/[0-9]/)) strength += 15;
    if (password.match(/[^a-zA-Z0-9]/)) strength += 10;
    
    return Math.min(strength, 100);
}

function updatePasswordStrength(strength, meter, text) {
    const fill = meter.querySelector('.strength-fill');
    const labels = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong'];
    const colors = ['#ef4444', '#f97316', '#eab308', '#22c55e', '#10b981'];
    
    let level = 0;
    if (strength >= 80) level = 4;
    else if (strength >= 60) level = 3;
    else if (strength >= 40) level = 2;
    else if (strength >= 20) level = 1;
    
    fill.style.width = strength + '%';
    fill.style.background = colors[level];
    text.textContent = labels[level];
    text.style.color = colors[level];
}

// ===== SCROLL EFFECTS =====
function initializeScrollEffects() {
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-in');
            }
        });
    }, observerOptions);
    
    // Observe elements with animation classes
    const animatedElements = document.querySelectorAll('.fade-up, .fade-in, .slide-left, .slide-right');
    animatedElements.forEach(el => observer.observe(el));
    
    // Parallax effect for hero section
    const hero = document.querySelector('.hero-section');
    if (hero) {
        window.addEventListener('scroll', () => {
            const scrolled = window.pageYOffset;
            const parallax = scrolled * 0.5;
            hero.style.transform = `translateY(${parallax}px)`;
        });
    }
}

// ===== ANIMATIONS =====
function initializeAnimations() {
    // Stagger animations for feature cards
    const featureCards = document.querySelectorAll('.feature-card');
    featureCards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.2}s`;
    });
    
    // Testimonial slider
    const testimonialSlider = document.querySelector('.testimonials-slider');
    if (testimonialSlider) {
        let currentSlide = 0;
        const slides = testimonialSlider.querySelectorAll('.testimonial-item');
        const totalSlides = slides.length;
        
        setInterval(() => {
            slides[currentSlide].classList.remove('active');
            currentSlide = (currentSlide + 1) % totalSlides;
            slides[currentSlide].classList.add('active');
        }, 5000);
    }
}

// ===== COUNTERS =====
function initializeCounters() {
    const counters = document.querySelectorAll('.counter');
    const counterObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const counter = entry.target;
                const target = parseInt(counter.getAttribute('data-target'));
                animateCounter(counter, target);
                counterObserver.unobserve(counter);
            }
        });
    });
    
    counters.forEach(counter => counterObserver.observe(counter));
}

function animateCounter(element, target) {
    const duration = 2000;
    const step = target / (duration / 16);
    let current = 0;
    
    const timer = setInterval(() => {
        current += step;
        if (current >= target) {
            element.textContent = target.toLocaleString();
            clearInterval(timer);
        } else {
            element.textContent = Math.floor(current).toLocaleString();
        }
    }, 16);
}

// ===== MODALS =====
function initializeModals() {
    const modalTriggers = document.querySelectorAll('[data-modal]');
    const modals = document.querySelectorAll('.modal');
    const modalCloses = document.querySelectorAll('.modal-close');
    
    modalTriggers.forEach(trigger => {
        trigger.addEventListener('click', function(e) {
            e.preventDefault();
            const modalId = this.getAttribute('data-modal');
            const modal = document.getElementById(modalId);
            if (modal) {
                showModal(modal);
            }
        });
    });
    
    modalCloses.forEach(close => {
        close.addEventListener('click', function() {
            const modal = this.closest('.modal');
            if (modal) {
                hideModal(modal);
            }
        });
    });
    
    // Close modal when clicking outside
    modals.forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                hideModal(modal);
            }
        });
    });
}

function showModal(modal) {
    modal.style.display = 'flex';
    modal.style.opacity = '0';
    setTimeout(() => {
        modal.style.opacity = '1';
    }, 10);
    document.body.style.overflow = 'hidden';
}

function hideModal(modal) {
    modal.style.opacity = '0';
    setTimeout(() => {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }, 300);
}

// ===== TOASTS =====
function initializeToasts() {
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            hideAlert(alert);
        }, 5000);
    });
}

function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        background: ${getToastColor(type)};
        color: white;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        z-index: 10000;
        transform: translateX(100%);
        transition: transform 0.3s ease;
        max-width: 300px;
        font-weight: 500;
    `;
    
    toast.innerHTML = `
        <div style="display: flex; align-items: center; gap: 10px;">
            <i class="fas ${getToastIcon(type)}"></i>
            <span>${message}</span>
            <button onclick="this.parentElement.parentElement.remove()" style="background: none; border: none; color: white; margin-left: auto; cursor: pointer;">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => toast.style.transform = 'translateX(0)', 100);
    setTimeout(() => {
        toast.style.transform = 'translateX(100%)';
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}

function getToastColor(type) {
    const colors = {
        success: '#10b981',
        error: '#ef4444',
        warning: '#f59e0b',
        info: '#3b82f6'
    };
    return colors[type] || colors.info;
}

function getToastIcon(type) {
    const icons = {
        success: 'fa-check-circle',
        error: 'fa-exclamation-circle',
        warning: 'fa-exclamation-triangle',
        info: 'fa-info-circle'
    };
    return icons[type] || icons.info;
}

function hideAlert(alert) {
    alert.style.opacity = '0';
    alert.style.transform = 'translateY(-10px)';
    setTimeout(() => {
        alert.remove();
    }, 300);
}

// ===== FORM VALIDATION =====
function initializeFormValidation() {
    const forms = document.querySelectorAll('form[data-validate]');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
            }
        });
        
        // Real-time validation
        const inputs = form.querySelectorAll('input[required], input[type="email"]');
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                validateField(this);
            });
            
            input.addEventListener('input', function() {
                if (this.classList.contains('error')) {
                    validateField(this);
                }
            });
        });
    });
}

function validateForm(form) {
    const inputs = form.querySelectorAll('input[required], input[type="email"]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!validateField(input)) {
            isValid = false;
        }
    });
    
    return isValid;
}

function validateField(field) {
    const value = field.value.trim();
    let isValid = true;
    let message = '';
    
    // Required field validation
    if (field.hasAttribute('required') && !value) {
        isValid = false;
        message = 'This field is required';
    }
    
    // Email validation
    if (field.type === 'email' && value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
            isValid = false;
            message = 'Please enter a valid email address';
        }
    }
    
    // Password validation
    if (field.type === 'password' && value) {
        if (value.length < 8) {
            isValid = false;
            message = 'Password must be at least 8 characters long';
        }
    }
    
    // Confirm password validation
    if (field.name === 'confirm_password') {
        const passwordField = document.querySelector('input[name="password"]');
        if (passwordField && value !== passwordField.value) {
            isValid = false;
            message = 'Passwords do not match';
        }
    }
    
    showFieldValidation(field, isValid, message);
    return isValid;
}

function showFieldValidation(field, isValid, message) {
    const fieldGroup = field.closest('.form-group') || field.parentElement;
    let errorElement = fieldGroup.querySelector('.field-error');
    
    if (!errorElement) {
        errorElement = document.createElement('div');
        errorElement.className = 'field-error';
        errorElement.style.cssText = `
            color: #ef4444;
            font-size: 12px;
            margin-top: 5px;
            display: none;
        `;
        fieldGroup.appendChild(errorElement);
    }
    
    if (isValid) {
        field.classList.remove('error');
        field.classList.add('valid');
        errorElement.style.display = 'none';
    } else {
        field.classList.remove('valid');
        field.classList.add('error');
        errorElement.textContent = message;
        errorElement.style.display = 'block';
    }
}

// ===== FACE SHAPE QUIZ =====
function initializeFaceShapeQuiz() {
    const quizContainer = document.querySelector('.quiz-container');
    if (!quizContainer) return;
    
    let currentQuestion = 0;
    let answers = {};
    
    const questions = [
        {
            id: 'face_length',
            question: 'How would you describe the length of your face?',
            options: [
                { value: 'short', text: 'Short - about as wide as it is long' },
                { value: 'medium', text: 'Medium - slightly longer than it is wide' },
                { value: 'long', text: 'Long - noticeably longer than it is wide' }
            ]
        },
        {
            id: 'forehead_width',
            question: 'How wide is your forehead compared to your jawline?',
            options: [
                { value: 'wider', text: 'Wider than my jawline' },
                { value: 'same', text: 'About the same width' },
                { value: 'narrower', text: 'Narrower than my jawline' }
            ]
        },
        {
            id: 'jawline_shape',
            question: 'How would you describe your jawline?',
            options: [
                { value: 'sharp', text: 'Sharp and angular' },
                { value: 'soft', text: 'Soft and rounded' },
                { value: 'square', text: 'Square and strong' }
            ]
        },
        {
            id: 'cheekbones',
            question: 'Where are your cheekbones most prominent?',
            options: [
                { value: 'high', text: 'High on my face' },
                { value: 'wide', text: 'Wide across my face' },
                { value: 'subtle', text: 'Not very prominent' }
            ]
        }
    ];
    
    window.startQuiz = function() {
        currentQuestion = 0;
        answers = {};
        showQuestion(questions[currentQuestion]);
    };
    
    window.nextQuestion = function(answer) {
        answers[questions[currentQuestion].id] = answer;
        currentQuestion++;
        
        if (currentQuestion < questions.length) {
            showQuestion(questions[currentQuestion]);
        } else {
            showResults();
        }
    };
    
    function showQuestion(question) {
        const quizHTML = `
            <div class="quiz-question">
                <div class="question-progress">
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: ${((currentQuestion + 1) / questions.length) * 100}%"></div>
                    </div>
                    <span class="progress-text">Question ${currentQuestion + 1} of ${questions.length}</span>
                </div>
                
                <h3>${question.question}</h3>
                
                <div class="quiz-options">
                    ${question.options.map(option => `
                        <button class="quiz-option" onclick="nextQuestion('${option.value}')">
                            ${option.text}
                        </button>
                    `).join('')}
                </div>
            </div>
        `;
        
        quizContainer.innerHTML = quizHTML;
    }
    
    function showResults() {
        const faceShape = determineFaceShape(answers);
        const recommendations = getFaceShapeRecommendations(faceShape);
        
        const resultsHTML = `
            <div class="quiz-results">
                <div class="result-header">
                    <i class="fas fa-check-circle"></i>
                    <h2>Your Face Shape: ${faceShape.name}</h2>
                    <p>${faceShape.description}</p>
                </div>
                
                <div class="recommendations">
                    <h3>Recommended Haircuts</h3>
                    <div class="haircut-recommendations">
                        ${recommendations.map(rec => `
                            <div class="recommendation-item">
                                <img src="/assets/images/haircuts/${rec.image}" alt="${rec.name}">
                                <h4>${rec.name}</h4>
                                <p>${rec.description}</p>
                            </div>
                        `).join('')}
                    </div>
                </div>
                
                <div class="result-actions">
                    <button class="btn btn-primary" onclick="saveResults('${faceShape.shape}')">
                        Save Results
                    </button>
                    <button class="btn btn-outline" onclick="startQuiz()">
                        Retake Quiz
                    </button>
                </div>
            </div>
        `;
        
        quizContainer.innerHTML = resultsHTML;
    }
    
    function determineFaceShape(answers) {
        // Simple logic to determine face shape
        const { face_length, forehead_width, jawline_shape, cheekbones } = answers;
        
        if (face_length === 'short' && jawline_shape === 'soft') {
            return {
                shape: 'round',
                name: 'Round',
                description: 'Your face is about as wide as it is long with soft, curved lines.'
            };
        } else if (face_length === 'long' && forehead_width === 'same') {
            return {
                shape: 'oval',
                name: 'Oval',
                description: 'Your face is longer than it is wide with balanced proportions.'
            };
        } else if (jawline_shape === 'square' && forehead_width === 'same') {
            return {
                shape: 'square',
                name: 'Square',
                description: 'Your face has a strong, angular jawline with similar width at forehead and jaw.'
            };
        } else if (forehead_width === 'wider' && jawline_shape === 'soft') {
            return {
                shape: 'heart',
                name: 'Heart',
                description: 'Your face is wider at the forehead and tapers to a narrower jawline.'
            };
        } else {
            return {
                shape: 'diamond',
                name: 'Diamond',
                description: 'Your face is widest at the cheekbones with a narrower forehead and jawline.'
            };
        }
    }
    
    function getFaceShapeRecommendations(faceShape) {
        const recommendations = {
            round: [
                { name: 'Long Layered Cut', image: 'long-layers.jpg', description: 'Adds length and movement' },
                { name: 'Side-Swept Bangs', image: 'side-bangs.jpg', description: 'Creates vertical lines' },
                { name: 'High Ponytail', image: 'high-ponytail.jpg', description: 'Elongates the face' }
            ],
            oval: [
                { name: 'Almost Any Style', image: 'versatile.jpg', description: 'Your face shape is very versatile' },
                { name: 'Bob Cut', image: 'bob.jpg', description: 'Classic and flattering' },
                { name: 'Pixie Cut', image: 'pixie.jpg', description: 'Short and chic' }
            ],
            square: [
                { name: 'Soft Waves', image: 'soft-waves.jpg', description: 'Softens angular features' },
                { name: 'Long Layers', image: 'long-layers-2.jpg', description: 'Adds movement and softness' },
                { name: 'Side Part', image: 'side-part.jpg', description: 'Asymmetrical balance' }
            ],
            heart: [
                { name: 'Chin-Length Bob', image: 'chin-bob.jpg', description: 'Balances the forehead' },
                { name: 'Full Bangs', image: 'full-bangs.jpg', description: 'Minimizes forehead width' },
                { name: 'Waves at Jaw Level', image: 'jaw-waves.jpg', description: 'Adds volume at jawline' }
            ],
            diamond: [
                { name: 'Side-Swept Fringe', image: 'side-fringe.jpg', description: 'Softens the forehead' },
                { name: 'Chin-Length Layers', image: 'chin-layers.jpg', description: 'Adds width at jawline' },
                { name: 'Textured Bob', image: 'textured-bob.jpg', description: 'Creates balance' }
            ]
        };
        
        return recommendations[faceShape.shape] || recommendations.oval;
    }
    
    window.saveResults = function(faceShape) {
        // Here you would normally save to database
        showToast('Quiz results saved to your profile!', 'success');
        
        // Redirect to recommendations page
        setTimeout(() => {
            window.location.href = '/user/recommendations.php?face_shape=' + faceShape;
        }, 1500);
    };
}

// ===== UTILITY FUNCTIONS =====
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function throttle(func, limit) {
    let inThrottle;
    return function() {
        const args = arguments;
        const context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    }
}

// ===== EXPORT FUNCTIONS =====
window.MainJS = {
    showToast,
    showModal,
    hideModal,
    validateForm,
    animateCounter
};
