// Mobile Navigation
const hamburger = document.querySelector('.hamburger');
const navMenu = document.querySelector('.nav-menu');

if (hamburger && navMenu) {
    hamburger.addEventListener('click', () => {
        hamburger.classList.toggle('active');
        navMenu.classList.toggle('active');
    });

    // Close mobile menu when clicking on a link
    document.querySelectorAll('.nav-link').forEach(n => n.addEventListener('click', () => {
        hamburger.classList.remove('active');
        navMenu.classList.remove('active');
    }));
}

// Carousel Functionality
class Carousel {
    constructor(selector) {
        this.carousel = document.querySelector(selector);
        if (!this.carousel) return;
        
        this.slides = this.carousel.querySelectorAll('.slide');
        this.dots = this.carousel.querySelectorAll('.dot');
        this.prevBtn = this.carousel.querySelector('.prev-btn');
        this.nextBtn = this.carousel.querySelector('.next-btn');
        
        this.currentSlide = 0;
        this.isPlaying = true;
        
        this.init();
    }
    
    init() {
        this.setupEventListeners();
        this.startAutoPlay();
    }
    
    setupEventListeners() {
        if (this.prevBtn) {
            this.prevBtn.addEventListener('click', () => {
                this.prevSlide();
                this.pauseAutoPlay();
            });
        }
        
        if (this.nextBtn) {
            this.nextBtn.addEventListener('click', () => {
                this.nextSlide();
                this.pauseAutoPlay();
            });
        }
        
        this.dots.forEach((dot, index) => {
            dot.addEventListener('click', () => {
                this.goToSlide(index);
                this.pauseAutoPlay();
            });
        });
        
        // Pause on hover
        this.carousel.addEventListener('mouseenter', () => this.pauseAutoPlay());
        this.carousel.addEventListener('mouseleave', () => this.startAutoPlay());
    }
    
    goToSlide(index) {
        this.slides[this.currentSlide].classList.remove('active');
        this.dots[this.currentSlide].classList.remove('active');
        
        this.currentSlide = index;
        
        this.slides[this.currentSlide].classList.add('active');
        this.dots[this.currentSlide].classList.add('active');
    }
    
    nextSlide() {
        const next = (this.currentSlide + 1) % this.slides.length;
        this.goToSlide(next);
    }
    
    prevSlide() {
        const prev = (this.currentSlide - 1 + this.slides.length) % this.slides.length;
        this.goToSlide(prev);
    }
    
    startAutoPlay() {
        if (this.autoPlayInterval) return;
        
        this.autoPlayInterval = setInterval(() => {
            this.nextSlide();
        }, 5000);
    }
    
    pauseAutoPlay() {
        if (this.autoPlayInterval) {
            clearInterval(this.autoPlayInterval);
            this.autoPlayInterval = null;
        }
    }
}

// Initialize carousel on home page
if (document.querySelector('.carousel')) {
    new Carousel('.carousel');
}

// Smooth scrolling for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Animation on scroll
const observeElements = () => {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('fade-in');
            }
        });
    }, {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    });

    // Observe elements that should animate
    document.querySelectorAll('.feature-card, .program-card, .course-card, .gallery-item, .branch-card').forEach(el => {
        observer.observe(el);
    });
};

// Initialize animations when DOM is loaded
document.addEventListener('DOMContentLoaded', observeElements);

// Contact form submission
const contactForm = document.getElementById('contactForm');
if (contactForm) {
    contactForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Get form data
        const formData = new FormData(this);
        const data = Object.fromEntries(formData);
        
        // Show loading state
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.innerHTML = '<span class="loading"></span> Sending...';
        submitBtn.disabled = true;
        
        // Simulate form submission
        setTimeout(() => {
            alert('Thank you for your message! We will get back to you soon.');
            this.reset();
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
        }, 2000);
    });
}

// Gallery lightbox functionality
const createLightbox = () => {
    const galleryItems = document.querySelectorAll('.gallery-item');
    
    if (galleryItems.length === 0) return;
    
    // Create lightbox HTML
    const lightbox = document.createElement('div');
    lightbox.className = 'lightbox';
    lightbox.innerHTML = `
        <div class="lightbox-content">
            <span class="lightbox-close">&times;</span>
            <img class="lightbox-img" src="" alt="">
            <div class="lightbox-caption"></div>
        </div>
    `;
    
    // Add lightbox styles
    const lightboxStyles = `
        .lightbox {
            display: none;
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.9);
        }
        
        .lightbox-content {
            position: relative;
            margin: auto;
            padding: 20px;
            width: 90%;
            max-width: 800px;
            top: 50%;
            transform: translateY(-50%);
        }
        
        .lightbox-img {
            width: 100%;
            height: auto;
            border-radius: 10px;
        }
        
        .lightbox-close {
            position: absolute;
            top: 10px;
            right: 25px;
            color: white;
            font-size: 35px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .lightbox-close:hover {
            opacity: 0.7;
        }
        
        .lightbox-caption {
            text-align: center;
            color: white;
            padding: 10px 0;
            font-size: 18px;
        }
    `;
    
    // Add styles to head
    const styleSheet = document.createElement('style');
    styleSheet.textContent = lightboxStyles;
    document.head.appendChild(styleSheet);
    
    document.body.appendChild(lightbox);
    
    const lightboxImg = lightbox.querySelector('.lightbox-img');
    const lightboxCaption = lightbox.querySelector('.lightbox-caption');
    const closeBtn = lightbox.querySelector('.lightbox-close');
    
    // Add click event to gallery items
    galleryItems.forEach(item => {
        item.addEventListener('click', () => {
            const img = item.querySelector('img');
            lightboxImg.src = img.src;
            lightboxCaption.textContent = img.alt;
            lightbox.style.display = 'block';
        });
    });
    
    // Close lightbox
    const closeLightbox = () => {
        lightbox.style.display = 'none';
    };
    
    closeBtn.addEventListener('click', closeLightbox);
    lightbox.addEventListener('click', (e) => {
        if (e.target === lightbox) closeLightbox();
    });
    
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeLightbox();
    });
};

// Initialize lightbox on gallery page
if (document.querySelector('.gallery-grid')) {
    createLightbox();
}

// Dashboard functionality
const initDashboard = () => {
    const navButtons = document.querySelectorAll('.dashboard-nav button');
    const sections = document.querySelectorAll('.dashboard-section');
    
    navButtons.forEach(button => {
        button.addEventListener('click', () => {
            const target = button.dataset.section;
            
            // Update active button
            navButtons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');
            
            // Show target section
            sections.forEach(section => {
                section.classList.remove('active');
                if (section.id === target) {
                    section.classList.add('active');
                }
            });
        });
    });
};

// Initialize dashboard if on grievance page
if (document.querySelector('.dashboard')) {
    initDashboard();
}

// Login functionality
const loginForm = document.getElementById('loginForm');
if (loginForm) {
    loginForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const email = this.querySelector('input[type="email"]').value;
        const password = this.querySelector('input[type="password"]').value;
        
        // Simple validation (in real app, this would be server-side)
        if (email && password) {
            // Show loading
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<span class="loading"></span> Logging in...';
            
            setTimeout(() => {
                // Hide login form, show dashboard
                document.querySelector('.login-container').style.display = 'none';
                document.querySelector('.dashboard').style.display = 'block';
                
                // Store login state
                localStorage.setItem('ipmc_logged_in', 'true');
                localStorage.setItem('ipmc_user_email', email);
                
                // Update dashboard with user info
                document.querySelector('.dashboard h2').textContent = `Welcome, ${email}`;
            }, 1500);
        }
    });
}

// Check login state on grievance page
if (window.location.pathname.includes('grievance.html')) {
    const isLoggedIn = localStorage.getItem('ipmc_logged_in');
    if (isLoggedIn) {
        const userEmail = localStorage.getItem('ipmc_user_email');
        document.querySelector('.login-container').style.display = 'none';
        document.querySelector('.dashboard').style.display = 'block';
        document.querySelector('.dashboard h2').textContent = `Welcome, ${userEmail}`;
    }
}

// Logout functionality
const logoutBtn = document.getElementById('logoutBtn');
if (logoutBtn) {
    logoutBtn.addEventListener('click', () => {
        localStorage.removeItem('ipmc_logged_in');
        localStorage.removeItem('ipmc_user_email');
        location.reload();
    });
}

// Generate sample student data
const generateStudentData = () => {
    return {
        profile: {
            name: 'John Doe',
            studentId: 'IPMC2024001',
            email: localStorage.getItem('ipmc_user_email') || 'student@ipmc.edu.gh',
            program: 'BSc Computing with AI',
            level: 'Level 5',
            year: '2024/2025'
        },
        courses: [
            { code: 'CS501', name: 'Advanced Programming with AI', credits: 15, grade: 'A' },
            { code: 'CS502', name: 'Database Systems', credits: 15, grade: 'B+' },
            { code: 'CS503', name: 'Web Technologies', credits: 15, grade: 'A-' },
            { code: 'CS504', name: 'Systems Analysis', credits: 15, grade: 'B' }
        ],
        results: [
            { semester: 'Semester 1 2024', gpa: 3.6, status: 'Pass' },
            { semester: 'Semester 2 2024', gpa: 3.8, status: 'Pass' }
        ],
        payments: [
            { date: '2024-01-15', description: 'Tuition Fee', amount: 'GHS 2,500', status: 'Paid' },
            { date: '2024-02-15', description: 'Lab Fee', amount: 'GHS 500', status: 'Paid' },
            { date: '2024-03-15', description: 'Examination Fee', amount: 'GHS 300', status: 'Pending' }
        ]
    };
};

// Populate dashboard data
const populateDashboard = () => {
    const data = generateStudentData();
    
    // Profile section
    const profileSection = document.getElementById('profile');
    if (profileSection) {
        profileSection.innerHTML = `
            <h3>Student Profile</h3>
            <div class="profile-info">
                <p><strong>Name:</strong> ${data.profile.name}</p>
                <p><strong>Student ID:</strong> ${data.profile.studentId}</p>
                <p><strong>Email:</strong> ${data.profile.email}</p>
                <p><strong>Program:</strong> ${data.profile.program}</p>
                <p><strong>Level:</strong> ${data.profile.level}</p>
                <p><strong>Academic Year:</strong> ${data.profile.year}</p>
            </div>
        `;
    }
    
    // Courses section
    const coursesSection = document.getElementById('courses');
    if (coursesSection) {
        const coursesHTML = data.courses.map(course => `
            <tr>
                <td>${course.code}</td>
                <td>${course.name}</td>
                <td>${course.credits}</td>
                <td>${course.grade}</td>
            </tr>
        `).join('');
        
        coursesSection.innerHTML = `
            <h3>Current Courses</h3>
            <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
                <thead>
                    <tr style="background: var(--light-color);">
                        <th style="padding: 12px; text-align: left; border: 1px solid var(--border-color);">Code</th>
                        <th style="padding: 12px; text-align: left; border: 1px solid var(--border-color);">Course Name</th>
                        <th style="padding: 12px; text-align: left; border: 1px solid var(--border-color);">Credits</th>
                        <th style="padding: 12px; text-align: left; border: 1px solid var(--border-color);">Grade</th>
                    </tr>
                </thead>
                <tbody>
                    ${coursesHTML}
                </tbody>
            </table>
        `;
    }
    
    // Results section
    const resultsSection = document.getElementById('results');
    if (resultsSection) {
        const resultsHTML = data.results.map(result => `
            <tr>
                <td>${result.semester}</td>
                <td>${result.gpa}</td>
                <td><span class="status-badge ${result.status.toLowerCase()}">${result.status}</span></td>
            </tr>
        `).join('');
        
        resultsSection.innerHTML = `
            <h3>Academic Results</h3>
            <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
                <thead>
                    <tr style="background: var(--light-color);">
                        <th style="padding: 12px; text-align: left; border: 1px solid var(--border-color);">Semester</th>
                        <th style="padding: 12px; text-align: left; border: 1px solid var(--border-color);">GPA</th>
                        <th style="padding: 12px; text-align: left; border: 1px solid var(--border-color);">Status</th>
                    </tr>
                </thead>
                <tbody>
                    ${resultsHTML}
                </tbody>
            </table>
        `;
    }
    
    // Add status badge styles
    const statusStyles = `
        .status-badge {
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .status-badge.pass {
            background: #dcfce7;
            color: #166534;
        }
        .status-badge.pending {
            background: #fef3c7;
            color: #92400e;
        }
        .status-badge.paid {
            background: #dcfce7;
            color: #166534;
        }
    `;
    
    if (!document.getElementById('status-styles')) {
        const styleSheet = document.createElement('style');
        styleSheet.id = 'status-styles';
        styleSheet.textContent = statusStyles;
        document.head.appendChild(styleSheet);
    }
};

// Initialize dashboard data if logged in
if (document.querySelector('.dashboard') && localStorage.getItem('ipmc_logged_in')) {
    populateDashboard();
}

// Branch location hover effects
const branchCards = document.querySelectorAll('.branch-card');
branchCards.forEach(card => {
    const locationDetails = card.querySelector('.location-details');
    if (locationDetails) {
        card.addEventListener('mouseenter', () => {
            locationDetails.style.display = 'block';
        });
        
        card.addEventListener('mouseleave', () => {
            locationDetails.style.display = 'none';
        });
    }
});

// Add page transition effects
const addPageTransitions = () => {
    // Fade in page content
    document.body.style.opacity = '0';
    window.addEventListener('load', () => {
        document.body.style.transition = 'opacity 0.5s ease';
        document.body.style.opacity = '1';
    });
    
    // Add hover effects to nav links
    const navLinks = document.querySelectorAll('.nav-link:not(.grievance-btn)');
    navLinks.forEach(link => {
        link.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
        });
        
        link.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
};

// Initialize page transitions
addPageTransitions();

// Counter animation for statistics
const animateCounters = () => {
    const counters = document.querySelectorAll('.stat-item h3');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const counter = entry.target;
                const target = parseInt(counter.textContent.replace(/\D/g, ''));
                const suffix = counter.textContent.replace(/\d/g, '');
                
                let current = 0;
                const increment = target / 50;
                
                const updateCounter = () => {
                    if (current < target) {
                        current += increment;
                        counter.textContent = Math.floor(current) + suffix;
                        requestAnimationFrame(updateCounter);
                    } else {
                        counter.textContent = target + suffix;
                    }
                };
                
                updateCounter();
                observer.unobserve(counter);
            }
        });
    });
    
    counters.forEach(counter => observer.observe(counter));
};

// Initialize counter animation
if (document.querySelector('.stats')) {
    animateCounters();
}

// Form validation
const validateForm = (form) => {
    const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.style.borderColor = 'var(--primary-color)';
            isValid = false;
        } else {
            input.style.borderColor = 'var(--border-color)';
        }
    });
    
    return isValid;
};

// Add form validation to all forms
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function(e) {
        if (!validateForm(this)) {
            e.preventDefault();
            alert('Please fill in all required fields.');
        }
    });
});

console.log('IPMC Website Scripts Loaded Successfully');