<?php
require_once __DIR__ . '/core/Autoloader.php';
use Core\Autoloader;
use Config\Auth;

Autoloader::register();
Auth::requireLogin();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Theme Settings - Loan Management System</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <!-- Theme CSS - Fixed path -->
    <link rel="stylesheet" href="assets/css/themes.css">
    
    <style>
        body {
            background: var(--bg-secondary);
        }
        
        .theme-preview-card {
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        
        .theme-preview-card:hover {
            transform: translateY(-5px);
        }
        
        .theme-preview {
            width: 100%;
            height: 100px;
            border-radius: 10px;
            margin-bottom: 10px;
        }
        
        .color-preview-box {
            width: 60px;
            height: 60px;
            border-radius: 10px;
            border: 2px solid var(--border-medium);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark bg-dark navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-bank2"></i> Loan Management System
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="settings.php">
                            <i class="bi bi-gear"></i> Settings
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-3 mb-4">
                <div class="list-group">
                    <a class="list-group-item list-group-item-action active" data-bs-toggle="list" href="#themes">
                        <i class="bi bi-palette"></i> Themes
                    </a>
                    <a class="list-group-item list-group-item-action" data-bs-toggle="list" href="#custom">
                        <i class="bi bi-brush"></i> Custom Colors
                    </a>
                    <a class="list-group-item list-group-item-action" data-bs-toggle="list" href="#advanced">
                        <i class="bi bi-sliders2"></i> Advanced
                    </a>
                </div>
            </div>
            
            <div class="col-md-9">
                <div class="tab-content">
                    <!-- Themes Tab -->
                    <div class="tab-pane fade show active" id="themes">
                        <div class="card shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">Pre-defined Themes</h5>
                            </div>
                            <div class="card-body">
                                <div class="row" id="themeGrid">
                                    <!-- Theme options will be populated via JavaScript -->
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Custom Colors Tab -->
                    <div class="tab-pane fade" id="custom">
                        <div class="card shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">Custom Color Theme</h5>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle"></i>
                                    Create your own unique color scheme for the application.
                                </div>
                                
                                <div class="mb-4">
                                    <label class="form-label fw-bold">Primary Color</label>
                                    <div class="d-flex align-items-center gap-3">
                                        <input type="color" id="customPrimary" class="form-control form-control-color" style="width: 80px; height: 50px;" value="#6366f1">
                                        <span id="primaryValue" class="text-muted">#6366f1</span>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="form-label fw-bold">Secondary Color</label>
                                    <div class="d-flex align-items-center gap-3">
                                        <input type="color" id="customSecondary" class="form-control form-control-color" style="width: 80px; height: 50px;" value="#a855f7">
                                        <span id="secondaryValue" class="text-muted">#a855f7</span>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="form-label fw-bold">Preview</label>
                                    <div class="p-4 rounded text-center text-white" id="colorPreview" style="background: linear-gradient(135deg, #6366f1, #a855f7)">
                                        <i class="bi bi-check-circle-fill fs-1"></i>
                                        <p class="mt-2 mb-0">Live preview of your color combination</p>
                                    </div>
                                </div>
                                
                                <button class="btn btn-primary" id="applyCustomThemeBtn">
                                    <i class="bi bi-check-circle"></i> Apply Custom Theme
                                </button>
                                <button class="btn btn-secondary" id="resetCustomBtn">
                                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Advanced Tab -->
                    <div class="tab-pane fade" id="advanced">
                        <div class="card shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">Advanced Settings</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-4">
                                    <label class="form-label fw-bold">Border Radius (px)</label>
                                    <input type="range" class="form-range" id="borderRadius" min="0" max="30" step="1" value="8">
                                    <div class="d-flex justify-content-between">
                                        <small>Sharp (0px)</small>
                                        <small>Default (8px)</small>
                                        <small>Rounded (30px)</small>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="form-label fw-bold">Animation Speed</label>
                                    <select class="form-select" id="animationSpeed">
                                        <option value="fast">Fast (150ms)</option>
                                        <option value="normal" selected>Normal (300ms)</option>
                                        <option value="slow">Slow (500ms)</option>
                                    </select>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="form-label fw-bold">Card Style</label>
                                    <select class="form-select" id="cardStyle">
                                        <option value="default">Default</option>
                                        <option value="compact">Compact</option>
                                        <option value="elevated">Elevated</option>
                                    </select>
                                </div>
                                
                                <hr>
                                
                                <button class="btn btn-danger" id="resetSettings">
                                    <i class="bi bi-arrow-counterclockwise"></i> Reset All Settings to Default
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/theme-manager.js"></script>
    
    <script>
        // Theme definitions
        const themes = {
            ocean: { name: 'Ocean Blue', primary: '#3b82f6', secondary: '#06b6d4', icon: 'bi-water' },
            forest: { name: 'Forest Green', primary: '#10b981', secondary: '#34d399', icon: 'bi-tree' },
            sunset: { name: 'Sunset Orange', primary: '#f97316', secondary: '#fb923c', icon: 'bi-sunset' },
            royal: { name: 'Royal Purple', primary: '#8b5cf6', secondary: '#a78bfa', icon: 'bi-gem' },
            crimson: { name: 'Crimson Red', primary: '#ef4444', secondary: '#f87171', icon: 'bi-heart' },
            teal: { name: 'Teal', primary: '#14b8a6', secondary: '#2dd4bf', icon: 'bi-droplet' },
            dark: { name: 'Dark Mode', primary: '#6366f1', secondary: '#a855f7', icon: 'bi-moon-stars' },
            pastel: { name: 'Pastel', primary: '#a78bfa', secondary: '#fbcfe8', icon: 'bi-flower1' }
        };
        
        // Populate theme grid
        const themeGrid = document.getElementById('themeGrid');
        
        Object.entries(themes).forEach(([key, theme]) => {
            const col = document.createElement('div');
            col.className = 'col-md-4 mb-3';
            col.innerHTML = `
                <div class="card theme-preview-card h-100">
                    <div class="card-body text-center">
                        <div class="theme-preview" style="background: linear-gradient(135deg, ${theme.primary}, ${theme.secondary})">
                            <i class="bi ${theme.icon} text-white" style="font-size: 2rem; line-height: 100px;"></i>
                        </div>
                        <h6 class="mt-2 mb-1">${theme.name}</h6>
                        <div class="small text-muted">${theme.primary}</div>
                        <button class="btn btn-sm btn-primary mt-2" onclick="applyTheme('${key}')">
                            <i class="bi bi-check2"></i> Apply
                        </button>
                    </div>
                </div>
            `;
            themeGrid.appendChild(col);
        });
        
        // Custom color preview
        const primaryPicker = document.getElementById('customPrimary');
        const secondaryPicker = document.getElementById('customSecondary');
        const primaryValue = document.getElementById('primaryValue');
        const secondaryValue = document.getElementById('secondaryValue');
        const colorPreview = document.getElementById('colorPreview');
        
        function updatePreview() {
            const primary = primaryPicker.value;
            const secondary = secondaryPicker.value;
            colorPreview.style.background = `linear-gradient(135deg, ${primary}, ${secondary})`;
            primaryValue.textContent = primary;
            secondaryValue.textContent = secondary;
        }
        
        primaryPicker.addEventListener('input', updatePreview);
        secondaryPicker.addEventListener('input', updatePreview);
        
        document.getElementById('applyCustomThemeBtn').addEventListener('click', () => {
            const colors = {
                primary: primaryPicker.value,
                secondary: secondaryPicker.value
            };
            localStorage.setItem('customColors', JSON.stringify(colors));
            
            if (window.themeManager) {
                window.themeManager.currentTheme = 'custom';
                window.themeManager.loadTheme();
                window.themeManager.showNotification('Custom theme applied successfully!', 'success');
            } else {
                alert('Custom theme saved! Refresh the page to see changes.');
            }
        });
        
        document.getElementById('resetCustomBtn').addEventListener('click', () => {
            primaryPicker.value = '#6366f1';
            secondaryPicker.value = '#a855f7';
            updatePreview();
        });
        
        // Advanced settings
        const borderRadius = document.getElementById('borderRadius');
        borderRadius.addEventListener('input', (e) => {
            const value = e.target.value;
            document.documentElement.style.setProperty('--radius-sm', `${value * 0.5}px`);
            document.documentElement.style.setProperty('--radius-md', `${value}px`);
            document.documentElement.style.setProperty('--radius-lg', `${value * 1.5}px`);
            localStorage.setItem('borderRadius', value);
        });
        
        const animationSpeed = document.getElementById('animationSpeed');
        animationSpeed.addEventListener('change', (e) => {
            let speed = e.target.value;
            let value = speed === 'fast' ? '150ms' : (speed === 'normal' ? '300ms' : '500ms');
            document.documentElement.style.setProperty('--transition-fast', speed === 'fast' ? '150ms' : (speed === 'normal' ? '200ms' : '300ms'));
            document.documentElement.style.setProperty('--transition-normal', value);
            document.documentElement.style.setProperty('--transition-slow', speed === 'fast' ? '400ms' : (speed === 'normal' ? '600ms' : '800ms'));
            localStorage.setItem('animationSpeed', speed);
        });
        
        const cardStyle = document.getElementById('cardStyle');
        cardStyle.addEventListener('change', (e) => {
            const style = e.target.value;
            if (style === 'compact') {
                document.documentElement.style.setProperty('--spacing-4', '0.75rem');
                document.documentElement.style.setProperty('--spacing-6', '1rem');
            } else if (style === 'elevated') {
                document.documentElement.style.setProperty('--shadow-sm', '0 4px 8px 0 rgba(0, 0, 0, 0.1)');
                document.documentElement.style.setProperty('--shadow-md', '0 8px 16px 0 rgba(0, 0, 0, 0.1)');
            } else {
                document.documentElement.style.setProperty('--spacing-4', '1rem');
                document.documentElement.style.setProperty('--spacing-6', '1.5rem');
                document.documentElement.style.setProperty('--shadow-sm', '0 1px 2px 0 rgba(0, 0, 0, 0.05)');
                document.documentElement.style.setProperty('--shadow-md', '0 4px 6px -1px rgba(0, 0, 0, 0.1)');
            }
            localStorage.setItem('cardStyle', style);
        });
        
        // Load saved settings
        const savedRadius = localStorage.getItem('borderRadius');
        if (savedRadius) {
            borderRadius.value = savedRadius;
            document.documentElement.style.setProperty('--radius-sm', `${savedRadius * 0.5}px`);
            document.documentElement.style.setProperty('--radius-md', `${savedRadius}px`);
            document.documentElement.style.setProperty('--radius-lg', `${savedRadius * 1.5}px`);
        }
        
        const savedSpeed = localStorage.getItem('animationSpeed');
        if (savedSpeed) {
            animationSpeed.value = savedSpeed;
            animationSpeed.dispatchEvent(new Event('change'));
        }
        
        const savedCardStyle = localStorage.getItem('cardStyle');
        if (savedCardStyle) {
            cardStyle.value = savedCardStyle;
            cardStyle.dispatchEvent(new Event('change'));
        }
        
        document.getElementById('resetSettings').addEventListener('click', () => {
            if (confirm('Reset all settings to default? This will clear your custom theme and preferences.')) {
                localStorage.removeItem('customColors');
                localStorage.removeItem('borderRadius');
                localStorage.removeItem('animationSpeed');
                localStorage.removeItem('cardStyle');
                localStorage.removeItem('theme');
                location.reload();
            }
        });
        
        function applyTheme(themeName) {
            if (window.themeManager) {
                window.themeManager.switchTheme(themeName);
            } else {
                localStorage.setItem('theme', themeName);
                location.reload();
            }
        }
    </script>
</body>
</html>