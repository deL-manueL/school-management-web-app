// Global Variables
let currentSlide = 0;
let slideInterval;
let isLoggedIn = false;
let currentUser = null;

// DOM Content Loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

// Initialize Application
function initializeApp() {
    initializeSlider();
    initializeNavigation();
    initializeGallery();
    initializeForms();
    initializePortal();
    initializeBranches();
    initializeAnimations();
}

// Hero Slider Functionality
function initializeSlider() {
    const slides = document.querySelectorAll('.slide');
    const indicators = document.querySelectorAll('.indicator');
    const prevBtn = document.querySelector('.prev-btn');
    const nextBtn = document.querySelector('.next-btn');
    
    if (!slides.length) return;
    
    // Auto-play slider
    function startSlider() {
        slideInterval = setInterval(() => {
            nextSlide();
        }, 5000);
    }
    
    function stopSlider() {
        clearInterval(slideInterval);
    }
    
    function showSlide(index) {
        slides.forEach((slide, i) => {
            slide.classList.toggle('active', i === index);
        });
        
        indicators.forEach((indicator, i) => {
            indicator.classList.toggle('active', i === index);
        });
        
        currentSlide = index;
    }
    
    function nextSlide() {
        const nextIndex = (currentSlide + 1) % slides.length;
        showSlide(nextIndex);
    }
    
    function prevSlide() {
        const prevIndex = (currentSlide - 1 + slides.length) % slides.length;
        showSlide(prevIndex);
    }
    
    // Event Listeners
    if (nextBtn) nextBtn.addEventListener('click', nextSlide);
    if (prevBtn) prevBtn.addEventListener('click', prevSlide);
    
    indicators.forEach((indicator, index) => {
        indicator.addEventListener('click', () => showSlide(index));
    });
    
    // Pause on hover
    const sliderContainer = document.querySelector('.hero-slider');
    if (sliderContainer) {
        sliderContainer.addEventListener('mouseenter', stopSlider);
        sliderContainer.addEventListener('mouseleave', startSlider);
    }
    
    // Start the slider
    startSlider();
}

// Navigation Functionality
function initializeNavigation() {
    const hamburger = document.querySelector('.hamburger');
    const navLinks = document.querySelector('.nav-links');
    const header = document.querySelector('.header');
    
    // Mobile menu toggle
    if (hamburger && navLinks) {
        hamburger.addEventListener('click', () => {
            navLinks.classList.toggle('active');
            hamburger.classList.toggle('active');
        });
    }
    
    // Header scroll effect
    if (header) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 100) {
                header.style.background = 'rgba(255, 255, 255, 0.95)';
                header.style.backdropFilter = 'blur(10px)';
            } else {
                header.style.background = 'var(--white)';
                header.style.backdropFilter = 'none';
            }
        });
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
}

// Gallery Functionality
function initializeGallery() {
    const filterBtns = document.querySelectorAll('.filter-btn');
    const galleryItems = document.querySelectorAll('.gallery-item');
    const modal = document.getElementById('galleryModal');
    
    // Filter functionality
    filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const filter = btn.getAttribute('data-filter');
            
            // Update active button
            filterBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            
            // Filter items
            galleryItems.forEach(item => {
                const category = item.getAttribute('data-category');
                if (filter === 'all' || category === filter) {
                    item.style.display = 'block';
                    item.style.animation = 'slideUp 0.5s ease-out';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    });
}

// Modal Functions
function openModal(button) {
    const modal = document.getElementById('galleryModal');
    const modalImage = document.getElementById('modalImage');
    const modalTitle = document.getElementById('modalTitle');
    const modalDescription = document.getElementById('modalDescription');
    
    const galleryItem = button.closest('.gallery-item');
    const img = galleryItem.querySelector('img');
    const overlay = galleryItem.querySelector('.gallery-overlay');
    const title = overlay.querySelector('h3').textContent;
    const description = overlay.querySelector('p').textContent;
    
    modalImage.src = img.src;
    modalImage.alt = img.alt;
    modalTitle.textContent = title;
    modalDescription.textContent = description;
    
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    const modal = document.getElementById('galleryModal');
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Close modal on outside click
window.addEventListener('click', function(event) {
    const modal = document.getElementById('galleryModal');
    if (event.target === modal) {
        closeModal();
    }
});

// Forms Functionality
function initializeForms() {
    const contactForm = document.getElementById('contactForm');
    const loginForm = document.getElementById('loginForm');
    const registrationForm = document.getElementById('registrationForm');
    const profileUpdateForm = document.getElementById('profileUpdateForm');
    
    // Contact Form
    if (contactForm) {
        contactForm.addEventListener('submit', handleContactForm);
    }
    
    // Login Form
    if (loginForm) {
        loginForm.addEventListener('submit', handleLogin);
    }
    
    // Registration Form
    if (registrationForm) {
        registrationForm.addEventListener('submit', handleRegistration);
    }
    
    // Profile Update Form
    if (profileUpdateForm) {
        profileUpdateForm.addEventListener('submit', handleProfileUpdate);
    }
    
    // Form validation
    const inputs = document.querySelectorAll('input, textarea, select');
    inputs.forEach(input => {
        input.addEventListener('blur', validateField);
        input.addEventListener('input', clearFieldError);
    });
}

function handleContactForm(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);
    
    // Simulate form submission
    showNotification('Thank you for your message! We will get back to you soon.', 'success');
    e.target.reset();
}

function handleLogin(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const studentId = formData.get('studentId');
    const password = formData.get('password');
    
    // Simulate login (in real app, this would be an API call)
    if (studentId && password) {
        currentUser = {
            id: studentId,
            name: 'John Doe',
            email: 'john.doe@student.ipmc.edu.gh',
            program: 'Systems Engineering with AI'
        };
        
        isLoggedIn = true;
        showDashboard();
        showNotification('Login successful! Welcome back.', 'success');
    } else {
        showNotification('Please enter valid credentials.', 'error');
    }
}

function handleRegistration(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const password = formData.get('password');
    const confirmPassword = formData.get('confirmPassword');
    
    if (password !== confirmPassword) {
        showNotification('Passwords do not match.', 'error');
        return;
    }
    
    // Simulate registration
    showNotification('Registration successful! You can now login.', 'success');
    showLoginSection();
}

function handleProfileUpdate(e) {
    e.preventDefault();
    
    showNotification('Profile updated successfully!', 'success');
}

function validateField(e) {
    const field = e.target;
    const value = field.value.trim();
    
    // Remove existing error
    clearFieldError(e);
    
    // Validation rules
    if (field.hasAttribute('required') && !value) {
        showFieldError(field, 'This field is required');
        return false;
    }
    
    if (field.type === 'email' && value && !isValidEmail(value)) {
        showFieldError(field, 'Please enter a valid email address');
        return false;
    }
    
    if (field.type === 'tel' && value && !isValidPhone(value)) {
        showFieldError(field, 'Please enter a valid phone number');
        return false;
    }
    
    return true;
}

function clearFieldError(e) {
    const field = e.target;
    const errorElement = field.parentNode.querySelector('.field-error');
    if (errorElement) {
        errorElement.remove();
    }
    field.classList.remove('error');
}

function showFieldError(field, message) {
    field.classList.add('error');
    
    const errorElement = document.createElement('span');
    errorElement.className = 'field-error';
    errorElement.textContent = message;
    errorElement.style.color = 'var(--error-red)';
    errorElement.style.fontSize = '0.875rem';
    errorElement.style.marginTop = 'var(--spacing-xs)';
    
    field.parentNode.appendChild(errorElement);
}

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function isValidPhone(phone) {
    const phoneRegex = /^[\+]?[0-9\s\-\(\)]{10,}$/;
    return phoneRegex.test(phone);
}

// Portal Functionality
function initializePortal() {
    const loginBtn = document.getElementById('loginBtn');
    const registerBtn = document.getElementById('registerBtn');
    const logoutBtn = document.getElementById('logoutBtn');
    const tabBtns = document.querySelectorAll('.tab-btn');
    const newGrievanceBtn = document.getElementById('newGrievanceBtn');
    const cancelGrievanceBtn = document.getElementById('cancelGrievance');
    
    // Portal navigation
    if (loginBtn) {
        loginBtn.addEventListener('click', showLoginSection);
    }
    
    if (registerBtn) {
        registerBtn.addEventListener('click', showRegistrationSection);
    }
    
    if (logoutBtn) {
        logoutBtn.addEventListener('click', handleLogout);
    }
    
    // Dashboard tabs
    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const tabName = btn.getAttribute('data-tab');
            showTab(tabName);
        });
    });
    
    // Grievance form
    if (newGrievanceBtn) {
        newGrievanceBtn.addEventListener('click', showGrievanceForm);
    }
    
    if (cancelGrievanceBtn) {
        cancelGrievanceBtn.addEventListener('click', hideGrievanceForm);
    }
    
    // Initialize grievance form submission
    const grievanceForm = document.querySelector('.grievance-form');
    if (grievanceForm) {
        grievanceForm.addEventListener('submit', handleGrievanceSubmission);
    }
}

function showLoginSection() {
    document.getElementById('loginSection').classList.remove('hidden');
    document.getElementById('registrationSection').classList.add('hidden');
    document.getElementById('dashboardSection').classList.add('hidden');
}

function showRegistrationSection() {
    document.getElementById('loginSection').classList.add('hidden');
    document.getElementById('registrationSection').classList.remove('hidden');
    document.getElementById('dashboardSection').classList.add('hidden');
}

function showDashboard() {
    document.getElementById('loginSection').classList.add('hidden');
    document.getElementById('registrationSection').classList.add('hidden');
    document.getElementById('dashboardSection').classList.remove('hidden');
    
    // Update user info in dashboard
    if (currentUser) {
        document.getElementById('studentName').textContent = currentUser.name;
        document.getElementById('displayStudentId').textContent = currentUser.id;
        document.getElementById('profileName').textContent = currentUser.name;
        document.getElementById('profileStudentId').textContent = currentUser.id;
    }
}

function handleLogout() {
    isLoggedIn = false;
    currentUser = null;
    showLoginSection();
    showNotification('Logged out successfully.', 'success');
}

function showTab(tabName) {
    // Update tab buttons
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.toggle('active', btn.getAttribute('data-tab') === tabName);
    });
    
    // Update tab content
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.toggle('active', content.id === tabName);
    });
}

function showGrievanceForm() {
    document.getElementById('grievanceForm').classList.remove('hidden');
    document.getElementById('newGrievanceBtn').style.display = 'none';
}

function hideGrievanceForm() {
    document.getElementById('grievanceForm').classList.add('hidden');
    document.getElementById('newGrievanceBtn').style.display = 'block';
}

function handleGrievanceSubmission(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const grievanceData = Object.fromEntries(formData);
    
    // Simulate grievance submission
    showNotification('Grievance submitted successfully! You will receive updates via email.', 'success');
    hideGrievanceForm();
    e.target.reset();
    
    // Add to grievances list (simulation)
    addGrievanceToList(grievanceData);
}

function addGrievanceToList(data) {
    const grievancesList = document.querySelector('.grievances-list');
    const grievanceItem = document.createElement('div');
    grievanceItem.className = 'grievance-item';
    
    grievanceItem.innerHTML = `
        <div class="grievance-header">
            <h4>${data.title}</h4>
            <span class="status-badge status-pending">Under Review</span>
        </div>
        <p><strong>Submitted:</strong> ${new Date().toLocaleDateString()}</p>
        <p><strong>Category:</strong> ${data.category}</p>
        <p class="grievance-description">${data.description}</p>
        <div class="grievance-actions">
            <button class="btn-view">View Details</button>
            <button class="btn-edit">Edit</button>
        </div>
    `;
    
    grievancesList.insertBefore(grievanceItem, grievancesList.firstChild);
}

// Branches Functionality
function initializeBranches() {
    const branchCards = document.querySelectorAll('.branch-card');
    const tooltip = document.getElementById('locationTooltip');
    
    if (!tooltip) return;
    
    branchCards.forEach(card => {
        card.addEventListener('mouseenter', (e) => {
            const location = card.getAttribute('data-location');
            if (location) {
                tooltip.textContent = location;
                tooltip.classList.add('show');
                updateTooltipPosition(e);
            }
        });
        
        card.addEventListener('mousemove', updateTooltipPosition);
        
        card.addEventListener('mouseleave', () => {
            tooltip.classList.remove('show');
        });
    });
    
    function updateTooltipPosition(e) {
        const x = e.clientX;
        const y = e.clientY;
        
        tooltip.style.left = x + 10 + 'px';
        tooltip.style.top = y - 10 + 'px';
    }
}

// Animations
function initializeAnimations() {
    // Intersection Observer for scroll animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.animation = 'slideUp 0.6s ease-out forwards';
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);
    
    // Observe elements for animation
    const animateElements = document.querySelectorAll('.feature-card, .program-card, .service-card, .story-card, .advantage-card, .leader-card');
    animateElements.forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(30px)';
        observer.observe(el);
    });
}

// Notification System
function showNotification(message, type = 'info') {
    // Remove existing notifications
    const existingNotifications = document.querySelectorAll('.notification');
    existingNotifications.forEach(notification => notification.remove());
    
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas fa-${getNotificationIcon(type)}"></i>
            <span>${message}</span>
            <button class="notification-close" onclick="this.parentElement.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    // Styles
    notification.style.cssText = `
        position: fixed;
        top: 100px;
        right: 20px;
        background: ${getNotificationColor(type)};
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        z-index: 10000;
        animation: slideInRight 0.3s ease-out;
        max-width: 400px;
    `;
    
    notification.querySelector('.notification-content').style.cssText = `
        display: flex;
        align-items: center;
        gap: 0.75rem;
    `;
    
    notification.querySelector('.notification-close').style.cssText = `
        background: none;
        border: none;
        color: white;
        cursor: pointer;
        padding: 0;
        margin-left: auto;
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentElement) {
            notification.style.animation = 'slideOutRight 0.3s ease-out';
            setTimeout(() => notification.remove(), 300);
        }
    }, 5000);
}

function getNotificationIcon(type) {
    const icons = {
        success: 'check-circle',
        error: 'exclamation-circle',
        warning: 'exclamation-triangle',
        info: 'info-circle'
    };
    return icons[type] || icons.info;
}

function getNotificationColor(type) {
    const colors = {
        success: '#16a34a',
        error: '#dc2626',
        warning: '#ca8a04',
        info: '#2563eb'
    };
    return colors[type] || colors.info;
}

// Add notification animations to CSS
const notificationStyles = document.createElement('style');
notificationStyles.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
`;
document.head.appendChild(notificationStyles);

// Utility Functions
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

// Performance optimizations
const debouncedResize = debounce(() => {
    // Handle resize events
    console.log('Window resized');
}, 250);

const throttledScroll = throttle(() => {
    // Handle scroll events
}, 16);

window.addEventListener('resize', debouncedResize);
window.addEventListener('scroll', throttledScroll);

// Error handling
window.addEventListener('error', (e) => {
    console.error('JavaScript error:', e.error);
    // In production, you might want to send this to an error tracking service
});

// Service Worker registration (for PWA capabilities)
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js')
            .then(registration => {
                console.log('SW registered: ', registration);
            })
            .catch(registrationError => {
                console.log('SW registration failed: ', registrationError);
            });
    });
}

// Export functions for global access
window.openModal = openModal;
window.closeModal = closeModal;
window.showNotification = showNotification;