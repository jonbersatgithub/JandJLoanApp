<?php
// Start session and load config FIRST
require_once __DIR__ . '/config.php';

require_once __DIR__ . '/core/Autoloader.php';
use Core\Autoloader;
use Config\Auth;
use Models\User;

Autoloader::register();

// If already logged in, redirect to dashboard
if (Auth::isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter username/email and password';
    } else {
        $userModel = new User();
        $user = $userModel->authenticate($username, $password);
        
        if ($user) {
            Auth::login($user);
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Invalid username/email or password';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Loan Management System</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <!-- Theme CSS -->
    <link rel="stylesheet" href="assets/css/themes.css">
    
    <style>
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-card {
            max-width: 450px;
            width: 100%;
            background: var(--bg-white);
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-xl);
        }
        
        .login-header {
            background: var(--gradient-primary);
            color: white;
            padding: 40px;
            text-align: center;
        }
        
        .login-body {
            padding: 40px;
        }
        
        .form-control-custom {
            border: 2px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 12px 15px;
            transition: var(--transition-normal);
        }
        
        .form-control-custom:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
        
        .btn-login {
            background: var(--gradient-primary);
            border: none;
            padding: 12px;
            font-weight: 600;
            width: 100%;
            border-radius: var(--radius-md);
            transition: var(--transition-normal);
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        
        .demo-credentials {
            background: var(--bg-light);
            border-radius: var(--radius-md);
            padding: 15px;
            margin-top: 20px;
        }
        
        /* Animation for notifications */
        @keyframes fadeInOut {
            0% { opacity: 0; transform: translateY(-20px); }
            10% { opacity: 1; transform: translateY(0); }
            90% { opacity: 1; transform: translateY(0); }
            100% { opacity: 0; transform: translateY(-20px); }
        }
        
        .notification-toast {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            animation: fadeInOut 3s ease forwards;
        }
    </style>
</head>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/theme-manager.js"></script>
    
    <script>
        // ============================================
        // DEDICATED FUNCTIONS FOR LOGIN PAGE
        // ============================================
        
        // Global variables
        let loginAttempts = 0;
        let isSubmitting = false;
        
        /**
         * Initialize all login page functionality
         */
        function initLoginPage() {
            console.log('Initializing login page...');
            
            // Attach event listeners
            attachLoginFormListener();
            attachTogglePasswordListener();
            attachRememberMeListener();
            attachDemoCredentialsListener();
            
            // Load saved credentials if "Remember Me" was checked
            loadSavedCredentials();
            
            // Add input validation
            addInputValidation();
            
            console.log('Login page initialized');
        }
        
        /**
         * Handle login form submission
         */
        function attachLoginFormListener() {
            const loginForm = document.getElementById('loginForm');
            if (loginForm) {
                loginForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    await handleLogin();
                });
            }
        }
        
        /**
         * Main login handler function
         */
        async function handleLogin() {
            // Prevent multiple submissions
            if (isSubmitting) {
                showNotification('Please wait...', 'warning');
                return;
            }
            
            // Check login attempts (basic brute force protection)
            if (loginAttempts >= 5) {
                showNotification('Too many failed attempts. Please try again later.', 'danger');
                return;
            }
            
            // Get form values
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;
            
            // Validate inputs
            if (!validateLoginInput(username, password)) {
                return;
            }
            
            // Show loading state
            setLoadingState(true);
            
            try {
                // Simulate API call or use actual authentication
                const result = await authenticateUser(username, password);
                
                if (result.success) {
                    // Handle successful login
                    handleSuccessfulLogin(username, password);
                } else {
                    // Handle failed login
                    handleFailedLogin(result.message);
                }
            } catch (error) {
                console.error('Login error:', error);
                showNotification('An error occurred. Please try again.', 'danger');
            } finally {
                setLoadingState(false);
            }
        }
        
        /**
         * Validate login input
         */
        function validateLoginInput(username, password) {
            if (!username) {
                showNotification('Please enter username or email', 'warning');
                return false;
            }
            
            if (!password) {
                showNotification('Please enter password', 'warning');
                return false;
            }
            
            if (password.length < 4) {
                showNotification('Password must be at least 4 characters', 'warning');
                return false;
            }
            
            return true;
        }
        
        /**
         * Authenticate user (can be extended for AJAX)
         */
        async function authenticateUser(username, password) {
            // You can replace this with AJAX call to your API
            // For now, it will submit the form normally
            return new Promise((resolve) => {
                // If you want AJAX authentication, uncomment this:
                /*
                fetch('/api/auth/login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ username, password })
                })
                .then(response => response.json())
                .then(data => resolve(data))
                .catch(error => resolve({ success: false, message: error.message }));
                */
                
                // For standard form submission, submit the form
                document.getElementById('loginForm').submit();
                resolve({ success: true });
            });
        }
        
        /**
         * Handle successful login
         */
        function handleSuccessfulLogin(username, password) {
            // Reset login attempts
            loginAttempts = 0;
            
            // Save credentials if "Remember Me" is checked
            if (document.getElementById('rememberMe').checked) {
                saveCredentials(username, password);
            } else {
                clearSavedCredentials();
            }
            
            // Show success message
            showNotification('Login successful! Redirecting...', 'success');
            
            // Redirect will happen via form submission
        }
        
        /**
         * Handle failed login
         */
        function handleFailedLogin(errorMessage) {
            loginAttempts++;
            
            // Show error message
            showNotification(errorMessage || 'Invalid username or password', 'danger');
            
            // Clear password field
            document.getElementById('password').value = '';
            document.getElementById('password').focus();
            
            // Highlight error fields
            highlightErrorFields();
        }
        
        /**
         * Toggle password visibility
         */
        function attachTogglePasswordListener() {
            const toggleBtn = document.getElementById('togglePasswordBtn');
            if (toggleBtn) {
                toggleBtn.addEventListener('click', function() {
                    togglePasswordVisibility();
                });
            }
        }
        
        /**
         * Toggle password visibility function
         */
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('password');
            const toggleBtn = document.getElementById('togglePasswordBtn');
            const icon = toggleBtn.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
                showNotification('Password visible', 'info');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        }
        
        /**
         * Remember me functionality
         */
        function attachRememberMeListener() {
            const rememberMe = document.getElementById('rememberMe');
            if (rememberMe) {
                rememberMe.addEventListener('change', function() {
                    if (!this.checked) {
                        clearSavedCredentials();
                    }
                });
            }
        }
        
        /**
         * Save credentials to localStorage
         */
        function saveCredentials(username, password) {
            const credentials = {
                username: username,
                password: btoa(password), // Simple encoding (not secure, just for demo)
                timestamp: new Date().getTime()
            };
            localStorage.setItem('savedCredentials', JSON.stringify(credentials));
            console.log('Credentials saved');
        }
        
        /**
         * Load saved credentials
         */
        function loadSavedCredentials() {
            const saved = localStorage.getItem('savedCredentials');
            if (saved) {
                try {
                    const credentials = JSON.parse(saved);
                    const hoursSince = (new Date().getTime() - credentials.timestamp) / (1000 * 60 * 60);
                    
                    // Only auto-fill if less than 24 hours old
                    if (hoursSince < 24) {
                        document.getElementById('username').value = credentials.username;
                        document.getElementById('password').value = atob(credentials.password);
                        document.getElementById('rememberMe').checked = true;
                        console.log('Credentials loaded');
                    } else {
                        clearSavedCredentials();
                    }
                } catch (e) {
                    console.error('Error loading credentials:', e);
                }
            }
        }
        
        /**
         * Clear saved credentials
         */
        function clearSavedCredentials() {
            localStorage.removeItem('savedCredentials');
            console.log('Credentials cleared');
        }
        
        /**
         * Demo credentials autofill
         */
        function attachDemoCredentialsListener() {
            // Add click handlers for demo credentials
            const demoText = document.querySelector('.demo-credentials');
            if (demoText) {
                const adminSpan = demoText.querySelector('.col-6:first-child small:last-child');
                const managerSpan = demoText.querySelector('.col-6:last-child small:last-child');
                
                if (adminSpan) {
                    adminSpan.style.cursor = 'pointer';
                    adminSpan.addEventListener('click', () => fillDemoCredentials('admin', 'Admin@123'));
                }
                
                if (managerSpan) {
                    managerSpan.style.cursor = 'pointer';
                    managerSpan.addEventListener('click', () => fillDemoCredentials('manager', 'Manager@123'));
                }
            }
        }
        
        /**
         * Fill demo credentials
         */
        function fillDemoCredentials(username, password) {
            document.getElementById('username').value = username;
            document.getElementById('password').value = password;
            showNotification(`Demo credentials filled: ${username}`, 'info');
            
            // Optional: Auto-submit
            // setTimeout(() => handleLogin(), 500);
        }
        
        /**
         * Add input validation on the fly
         */
        function addInputValidation() {
            const usernameInput = document.getElementById('username');
            const passwordInput = document.getElementById('password');
            
            if (usernameInput) {
                usernameInput.addEventListener('input', function() {
                    removeErrorHighlight(this);
                });
            }
            
            if (passwordInput) {
                passwordInput.addEventListener('input', function() {
                    removeErrorHighlight(this);
                });
            }
        }
        
        /**
         * Highlight error fields
         */
        function highlightErrorFields() {
            const inputs = ['username', 'password'];
            inputs.forEach(id => {
                const input = document.getElementById(id);
                if (input) {
                    input.classList.add('is-invalid');
                    setTimeout(() => {
                        input.classList.remove('is-invalid');
                    }, 2000);
                }
            });
        }
        
        /**
         * Remove error highlight
         */
        function removeErrorHighlight(input) {
            input.classList.remove('is-invalid');
        }
        
        /**
         * Set loading state for login button
         */
        function setLoadingState(isLoading) {
            isSubmitting = isLoading;
            const loginBtn = document.getElementById('loginBtn');
            
            if (loginBtn) {
                if (isLoading) {
                    loginBtn.disabled = true;
                    loginBtn.innerHTML = `
                        <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                        Signing in...
                    `;
                } else {
                    loginBtn.disabled = false;
                    loginBtn.innerHTML = '<i class="bi bi-box-arrow-in-right"></i> Sign In';
                }
            }
        }
        
        /**
         * Show notification message
         */
        function showNotification(message, type = 'info') {
            // Remove existing notification
            const existingNotification = document.querySelector('.notification-toast');
            if (existingNotification) {
                existingNotification.remove();
            }
            
            // Create notification element
            const notification = document.createElement('div');
            notification.className = `notification-toast alert alert-${type} shadow-lg`;
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 9999;
                min-width: 300px;
                animation: fadeInOut 3s ease forwards;
            `;
            
            // Set icon based on type
            let icon = 'bi-info-circle';
            if (type === 'success') icon = 'bi-check-circle';
            if (type === 'danger') icon = 'bi-exclamation-triangle';
            if (type === 'warning') icon = 'bi-exclamation-circle';
            
            notification.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="bi ${icon} fs-4 me-2"></i>
                    <div class="flex-grow-1">${message}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Auto remove after animation
            setTimeout(() => {
                if (notification && notification.parentNode) {
                    notification.remove();
                }
            }, 3000);
        }
        
        /**
         * Handle theme change
         */
        function handleThemeChange(themeName) {
            console.log('Theme changed to:', themeName);
            showNotification(`Theme changed to ${themeName}`, 'success');
            
            // You can add additional logic here when theme changes
            // For example, update logo colors, etc.
            updateLogoForTheme(themeName);
        }
        
        /**
         * Update logo based on theme
         */
        function updateLogoForTheme(themeName) {
            const logo = document.querySelector('.login-header i');
            if (logo) {
                // Add theme-specific classes or styles
                logo.style.transition = 'all 0.3s ease';
            }
        }
        
        /**
         * Reset form function
         */
        function resetLoginForm() {
            document.getElementById('loginForm').reset();
            document.getElementById('username').focus();
            loginAttempts = 0;
            showNotification('Form reset', 'info');
        }
        
        /**
         * Get form data as object
         */
        function getFormData() {
            return {
                username: document.getElementById('username').value.trim(),
                password: document.getElementById('password').value,
                rememberMe: document.getElementById('rememberMe').checked,
                timestamp: new Date().toISOString()
            };
        }
        
        /**
         * Log form data (for debugging)
         */
        function logFormData() {
            const formData = getFormData();
            console.log('Form Data:', {
                ...formData,
                password: '***HIDDEN***'
            });
        }
        
        // ============================================
        // THEME SWITCHER CONTROLLER
        // ============================================
        
        /**
         * Custom theme switcher controller
         */
        class LoginThemeController {
            constructor() {
                this.isMenuOpen = false;
                this.init();
            }
            
            init() {
                this.injectThemeSwitcher();
                this.attachThemeEvents();
            }
            
            injectThemeSwitcher() {
                const container = document.getElementById('themeSwitcherContainer');
                if (!container) return;
                
                const themes = [
                    { name: 'Ocean Blue', value: 'ocean', primary: '#3b82f6', secondary: '#06b6d4' },
                    { name: 'Forest Green', value: 'forest', primary: '#10b981', secondary: '#34d399' },
                    { name: 'Sunset Orange', value: 'sunset', primary: '#f97316', secondary: '#fb923c' },
                    { name: 'Royal Purple', value: 'royal', primary: '#8b5cf6', secondary: '#a78bfa' },
                    { name: 'Crimson Red', value: 'crimson', primary: '#ef4444', secondary: '#f87171' },
                    { name: 'Dark Mode', value: 'dark', primary: '#6366f1', secondary: '#a855f7' },
                    { name: 'Pastel', value: 'pastel', primary: '#c084fc', secondary: '#fbcfe8' }
                ];
                
                const currentTheme = localStorage.getItem('app_theme') || 'ocean';
                
                container.innerHTML = `
                    <div class="theme-switcher">
                        <button class="theme-toggle-btn" id="customThemeToggleBtn">
                            <i class="bi bi-palette"></i>
                        </button>
                        <div class="theme-menu" id="customThemeMenu">
                            ${themes.map(theme => `
                                <div class="theme-option" data-theme="${theme.value}">
                                    <div class="theme-color-preview" style="background: linear-gradient(135deg, ${theme.primary}, ${theme.secondary});"></div>
                                    <span class="theme-name">${theme.name}</span>
                                    ${currentTheme === theme.value ? '<i class="bi bi-check-circle-fill text-primary ms-2"></i>' : ''}
                                </div>
                            `).join('')}
                            <div class="dropdown-divider"></div>
                            <div class="theme-option" id="customColorThemeOption">
                                <div class="theme-color-preview" style="background: linear-gradient(135deg, #667eea, #764ba2);"></div>
                                <span class="theme-name">Custom Colors</span>
                            </div>
                        </div>
                    </div>
                `;
            }
            
            attachThemeEvents() {
                const toggleBtn = document.getElementById('customThemeToggleBtn');
                const menu = document.getElementById('customThemeMenu');
                
                if (toggleBtn && menu) {
                    toggleBtn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        this.isMenuOpen = !this.isMenuOpen;
                        menu.classList.toggle('show', this.isMenuOpen);
                    });
                    
                    document.addEventListener('click', (e) => {
                        if (!toggleBtn.contains(e.target) && !menu.contains(e.target)) {
                            menu.classList.remove('show');
                            this.isMenuOpen = false;
                        }
                    });
                }
                
                // Theme selection
                document.querySelectorAll('.theme-option[data-theme]').forEach(option => {
                    option.addEventListener('click', () => {
                        const theme = option.dataset.theme;
                        this.applyTheme(theme);
                        if (menu) menu.classList.remove('show');
                        this.isMenuOpen = false;
                    });
                });
                
                // Custom color option
                const customOption = document.getElementById('customColorThemeOption');
                if (customOption) {
                    customOption.addEventListener('click', () => {
                        this.openCustomColorPicker();
                        if (menu) menu.classList.remove('show');
                        this.isMenuOpen = false;
                    });
                }
            }
            
            applyTheme(theme) {
                if (window.themeManager) {
                    window.themeManager.switchTheme(theme);
                } else {
                    // Fallback
                    document.body.className = '';
                    document.body.classList.add(`theme-${theme}`);
                    localStorage.setItem('app_theme', theme);
                    showNotification(`Theme changed to ${theme}`, 'success');
                }
            }
            
            openCustomColorPicker() {
                if (window.themeManager && window.themeManager.showCustomColorPicker) {
                    window.themeManager.showCustomColorPicker();
                } else {
                    showNotification('Custom color picker coming soon!', 'info');
                }
            }
        }
        
        // ============================================
        // INITIALIZE EVERYTHING
        // ============================================
        
        // Wait for DOM to be ready
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, initializing...');
            
            // Initialize login page functions
            initLoginPage();
            
            // Initialize theme controller
            const themeController = new LoginThemeController();
            
            // Listen for theme changes
            window.addEventListener('themeChanged', function(e) {
                handleThemeChange(e.detail.theme);
            });
            
            console.log('All systems ready');
        });
        
        // Export functions for global access (optional)
        window.loginFunctions = {
            resetForm: resetLoginForm,
            getFormData: getFormData,
            logFormData: logFormData,
            fillDemoCredentials: fillDemoCredentials
        };
    </script>

<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <i class="bi bi-bank2" style="font-size: 3rem;"></i>
                <h3 class="mt-3 mb-0">Loan Management System</h3>
                <p class="mb-0 mt-2">Sign in to your account</p>
            </div>
            <div class="login-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <form id="loginForm" method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Username or Email</label>
                        <div class="input-group">
                            <span class="input-group-text bg-transparent border-end-0">
                                <i class="bi bi-person text-muted"></i>
                            </span>
                            <input type="text" class="form-control form-control-custom border-start-0" 
                                   id="username" name="username" required placeholder="Enter username or email">
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-transparent border-end-0">
                                <i class="bi bi-lock text-muted"></i>
                            </span>
                            <input type="password" class="form-control form-control-custom border-start-0" 
                                   id="password" name="password" required placeholder="Enter password">
                            <button class="btn btn-outline-secondary" type="button" id="togglePasswordBtn">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="rememberMe">
                        <label class="form-check-label" for="rememberMe">Remember me</label>
                    </div>
                    
                    <button type="submit" class="btn btn-login text-white" id="loginBtn">
                        <i class="bi bi-box-arrow-in-right"></i> Sign In
                    </button>
                </form>
                
                <!-- <div class="demo-credentials">
                    <div class="text-center mb-2">
                        <small class="text-muted">Demo Credentials:</small>
                    </div>
                    <div class="row text-center">
                        <div class="col-6">
                            <small class="text-muted d-block">Admin</small>
                            <small>admin / Admin@123</small>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block">Manager</small>
                            <small>manager / Manager@123</small>
                        </div>
                    </div>
                </div> -->
                
                <div class="text-center mt-3">
                    <a href="register.php" class="text-decoration-none">
                        Don't have an account? <strong>Register here</strong>
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Theme Switcher will be injected here by JavaScript -->
    <div id="themeSwitcherContainer"></div>
    
    
</body>
</html>