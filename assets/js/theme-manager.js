// Theme Manager System - Complete Working Version
(function () {
  // Check if ThemeManager already exists
  if (typeof window.ThemeManager !== "undefined" && window.ThemeManager) {
    console.log("ThemeManager already loaded");
    return;
  }

  class ThemeManager {
    constructor() {
      this.themes = {
        ocean: { name: "Ocean Blue", primary: "#3b82f6", secondary: "#06b6d4" },
        forest: {
          name: "Forest Green",
          primary: "#10b981",
          secondary: "#34d399",
        },
        sunset: {
          name: "Sunset Orange",
          primary: "#f97316",
          secondary: "#fb923c",
        },
        royal: {
          name: "Royal Purple",
          primary: "#8b5cf6",
          secondary: "#a78bfa",
        },
        crimson: {
          name: "Crimson Red",
          primary: "#ef4444",
          secondary: "#f87171",
        },
        teal: { name: "Teal", primary: "#14b8a6", secondary: "#2dd4bf" },
        dark: { name: "Dark Mode", primary: "#6366f1", secondary: "#a855f7" },
        pastel: { name: "Pastel", primary: "#c084fc", secondary: "#fbcfe8" },
      };

      this.currentTheme = localStorage.getItem("app_theme") || "ocean";
      this.init();
    }

    init() {
      console.log("ThemeManager initializing...");
      
      // Wait for DOM to be ready before proceeding
      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
          this.loadTheme();
          this.injectThemeSwitcher();
          console.log("ThemeManager ready. Current theme:", this.currentTheme);
        });
      } else {
        // DOM already loaded
        this.loadTheme();
        this.injectThemeSwitcher();
        console.log("ThemeManager ready. Current theme:", this.currentTheme);
      }
    }

    loadTheme() {
      console.log("Loading theme:", this.currentTheme);

      // Remove all existing theme classes
      const allThemes = [
        "ocean",
        "forest",
        "sunset",
        "royal",
        "crimson",
        "teal",
        "dark",
        "pastel",
        "custom",
      ];
      allThemes.forEach((theme) => {
        document.body.classList.remove(`theme-${theme}`);
      });

      // Apply new theme class
      if (this.currentTheme !== "custom") {
        document.body.classList.add(`theme-${this.currentTheme}`);
      }

      // Apply custom colors if available
      const customColors = localStorage.getItem("customColors");
      if (customColors && this.currentTheme === "custom") {
        this.applyCustomColors(JSON.parse(customColors));
      }

      // Save to localStorage
      localStorage.setItem("app_theme", this.currentTheme);

      // Force update of gradient backgrounds
      this.updateGradients();

      // Dispatch event
      window.dispatchEvent(
        new CustomEvent("themeChanged", {
          detail: { theme: this.currentTheme },
        }),
      );
    }

    updateGradients() {
      // Force repaint of gradient elements
      const gradientElements = document.querySelectorAll(
        ".gradient-bg, .btn-primary, .navbar, .welcome-section",
      );
      gradientElements.forEach((el) => {
        const computedStyle = getComputedStyle(el);
        if (computedStyle.backgroundImage.includes("gradient")) {
          el.style.backgroundImage = computedStyle.backgroundImage;
        }
      });
    }

    applyCustomColors(colors) {
      console.log("Applying custom colors:", colors);
      const root = document.documentElement;
      root.style.setProperty("--primary-color", colors.primary);
      root.style.setProperty(
        "--primary-dark",
        this.darkenColor(colors.primary, 10),
      );
      root.style.setProperty(
        "--primary-light",
        this.lightenColor(colors.primary, 20),
      );
      root.style.setProperty("--secondary-color", colors.secondary);
      root.style.setProperty(
        "--gradient-primary",
        `linear-gradient(135deg, ${colors.primary}, ${colors.secondary})`,
      );

      // Update navbar background
      const navbar = document.querySelector(".navbar");
      if (navbar) {
        navbar.style.background = `linear-gradient(135deg, ${colors.primary}, ${colors.secondary})`;
      }
    }

    darkenColor(color, percent) {
      let r, g, b;
      if (color.startsWith("#")) {
        r = parseInt(color.slice(1, 3), 16);
        g = parseInt(color.slice(3, 5), 16);
        b = parseInt(color.slice(5, 7), 16);
      } else {
        return color;
      }

      r = Math.max(0, Math.floor(r - (r * percent) / 100));
      g = Math.max(0, Math.floor(g - (g * percent) / 100));
      b = Math.max(0, Math.floor(b - (b * percent) / 100));

      return `#${r.toString(16).padStart(2, "0")}${g.toString(16).padStart(2, "0")}${b.toString(16).padStart(2, "0")}`;
    }

    lightenColor(color, percent) {
      let r, g, b;
      if (color.startsWith("#")) {
        r = parseInt(color.slice(1, 3), 16);
        g = parseInt(color.slice(3, 5), 16);
        b = parseInt(color.slice(5, 7), 16);
      } else {
        return color;
      }

      r = Math.min(255, Math.floor(r + ((255 - r) * percent) / 100));
      g = Math.min(255, Math.floor(g + ((255 - g) * percent) / 100));
      b = Math.min(255, Math.floor(b + ((255 - b) * percent) / 100));

      return `#${r.toString(16).padStart(2, "0")}${g.toString(16).padStart(2, "0")}${b.toString(16).padStart(2, "0")}`;
    }

    injectThemeSwitcher() {
      // Don't inject if already exists
      if (document.querySelector(".theme-switcher")) {
        console.log("Theme switcher already exists");
        this.attachThemeSwitcherEvents();
        return;
      }

      console.log("Injecting theme switcher...");

      // Create theme switcher HTML
      const themeSwitcherHTML = `
                <div class="theme-switcher">
                    <button class="theme-toggle-btn" id="globalThemeToggleBtn">
                        <i class="bi bi-palette"></i>
                    </button>
                    <div class="theme-menu" id="globalThemeMenu">
                        ${Object.entries(this.themes)
                          .map(
                            ([key, theme]) => `
                            <div class="theme-option" data-theme="${key}">
                                <div class="theme-color-preview" style="background: linear-gradient(135deg, ${theme.primary}, ${theme.secondary});"></div>
                                <span class="theme-name">${theme.name}</span>
                                ${this.currentTheme === key ? '<i class="bi bi-check-circle-fill text-primary ms-2"></i>' : ""}
                            </div>
                        `,
                          )
                          .join("")}
                        <div class="dropdown-divider"></div>
                        <div class="theme-option" id="globalCustomColorOption">
                            <div class="theme-color-preview" style="background: linear-gradient(135deg, #667eea, #764ba2);"></div>
                            <span class="theme-name">Custom Colors</span>
                        </div>
                    </div>
                </div>
            `;

      document.body.insertAdjacentHTML("beforeend", themeSwitcherHTML);
      this.attachThemeSwitcherEvents();
    }

    attachThemeSwitcherEvents() {
      const toggleBtn = document.getElementById("globalThemeToggleBtn");
      const menu = document.getElementById("globalThemeMenu");

      if (toggleBtn && menu) {
        // Remove existing listeners to avoid duplicates
        const newToggleBtn = toggleBtn.cloneNode(true);
        toggleBtn.parentNode.replaceChild(newToggleBtn, toggleBtn);

        newToggleBtn.addEventListener("click", (e) => {
          e.stopPropagation();
          console.log("Theme toggle clicked");
          menu.classList.toggle("show");
        });

        // Close menu when clicking outside
        document.addEventListener("click", (e) => {
          if (!newToggleBtn.contains(e.target) && !menu.contains(e.target)) {
            menu.classList.remove("show");
          }
        });
      }

      // Theme selection
      document
        .querySelectorAll(".theme-option[data-theme]")
        .forEach((option) => {
          option.removeEventListener("click", this.handleThemeClick);
          option.addEventListener("click", this.handleThemeClick.bind(this));
        });

      // Custom color option
      const customOption = document.getElementById("globalCustomColorOption");
      if (customOption) {
        customOption.removeEventListener("click", this.handleCustomClick);
        customOption.addEventListener(
          "click",
          this.handleCustomClick.bind(this),
        );
      }
    }

    handleThemeClick(e) {
      const option = e.currentTarget;
      const theme = option.dataset.theme;
      console.log("Theme selected:", theme);
      this.switchTheme(theme);

      // Close menu
      const menu = document.getElementById("globalThemeMenu");
      if (menu) menu.classList.remove("show");
    }

    handleCustomClick(e) {
      console.log("Custom color option clicked");
      this.showCustomColorPicker();

      // Close menu
      const menu = document.getElementById("globalThemeMenu");
      if (menu) menu.classList.remove("show");
    }

    switchTheme(theme) {
      console.log("Switching to theme:", theme);
      this.currentTheme = theme;
      this.loadTheme();
      this.updateMenuCheckmarks();
      this.showNotification(
        `Theme changed to ${this.themes[theme].name}`,
        "success",
      );
    }

    updateMenuCheckmarks() {
      document
        .querySelectorAll(".theme-option[data-theme]")
        .forEach((option) => {
          // Remove existing checkmark
          const existingCheck = option.querySelector(".bi-check-circle-fill");
          if (existingCheck) existingCheck.remove();

          // Add checkmark to current theme
          if (option.dataset.theme === this.currentTheme) {
            option.insertAdjacentHTML(
              "beforeend",
              '<i class="bi bi-check-circle-fill text-primary ms-2"></i>',
            );
          }
        });
    }

    showCustomColorPicker() {
      // Remove existing modal
      const existingModal = document.querySelector(".custom-color-modal");
      if (existingModal) existingModal.remove();

      const modalHTML = `
                <div class="modal fade custom-color-modal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Custom Theme Colors</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Primary Color</label>
                                    <div class="d-flex align-items-center gap-3">
                                        <input type="color" id="customPrimaryColor" class="form-control form-control-color" style="width: 80px; height: 50px;" value="#4f46e5">
                                        <div class="color-preview-box" style="width: 50px; height: 50px; background: #4f46e5; border-radius: 8px; border: 2px solid #ddd;"></div>
                                        <span id="primaryColorValue" class="text-muted">#4f46e5</span>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Secondary Color</label>
                                    <div class="d-flex align-items-center gap-3">
                                        <input type="color" id="customSecondaryColor" class="form-control form-control-color" style="width: 80px; height: 50px;" value="#06b6d4">
                                        <div class="color-preview-box" style="width: 50px; height: 50px; background: #06b6d4; border-radius: 8px; border: 2px solid #ddd;"></div>
                                        <span id="secondaryColorValue" class="text-muted">#06b6d4</span>
                                    </div>
                                </div>
                                <div class="mt-3 p-3 rounded text-center text-white" id="customColorPreview" style="background: linear-gradient(135deg, #4f46e5, #06b6d4)">
                                    <i class="bi bi-brush fs-1"></i>
                                    <p class="mt-2 mb-0">Live Preview</p>
                                </div>
                                <div class="alert alert-info mt-3">
                                    <i class="bi bi-info-circle"></i> 
                                    Choose colors that complement each other for the best experience.
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-primary" id="applyCustomThemeBtn">Apply Custom Theme</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;

      document.body.insertAdjacentHTML("beforeend", modalHTML);
      const modal = document.querySelector(".custom-color-modal");

      const primaryPicker = document.getElementById("customPrimaryColor");
      const secondaryPicker = document.getElementById("customSecondaryColor");
      const primaryPreview = document.getElementById("primaryColorValue");
      const secondaryPreview = document.getElementById("secondaryColorValue");
      const colorPreview = document.getElementById("customColorPreview");
      const primaryBox = document.querySelector(
        "#customPrimaryColor + .color-preview-box",
      );
      const secondaryBox = document.querySelector(
        "#customSecondaryColor + .color-preview-box",
      );

      const updatePreview = () => {
        const primary = primaryPicker.value;
        const secondary = secondaryPicker.value;
        if (colorPreview)
          colorPreview.style.background = `linear-gradient(135deg, ${primary}, ${secondary})`;
        if (primaryPreview) primaryPreview.textContent = primary;
        if (secondaryPreview) secondaryPreview.textContent = secondary;
        if (primaryBox) primaryBox.style.background = primary;
        if (secondaryBox) secondaryBox.style.background = secondary;
      };

      if (primaryPicker) primaryPicker.addEventListener("input", updatePreview);
      if (secondaryPicker)
        secondaryPicker.addEventListener("input", updatePreview);

      const applyBtn = document.getElementById("applyCustomThemeBtn");
      if (applyBtn) {
        applyBtn.addEventListener("click", () => {
          const colors = {
            primary: primaryPicker.value,
            secondary: secondaryPicker.value,
          };
          localStorage.setItem("customColors", JSON.stringify(colors));
          this.currentTheme = "custom";
          this.loadTheme();

          const bsModal = bootstrap.Modal.getInstance(modal);
          if (bsModal) bsModal.hide();

          setTimeout(() => modal.remove(), 300);
          this.showNotification("Custom theme applied!", "success");
        });
      }

      const bsModal = new bootstrap.Modal(modal);
      bsModal.show();

      modal.addEventListener("hidden.bs.modal", () => {
        setTimeout(() => modal.remove(), 300);
      });
    }

    showNotification(message, type = "info") {
      // Create a simple notification
      const notification = document.createElement("div");
      notification.className = `position-fixed bottom-0 end-0 m-3 alert alert-${type === "success" ? "success" : "info"} shadow-lg`;
      notification.style.zIndex = "9999";
      notification.style.minWidth = "250px";
      notification.style.animation = "slideInRight 0.3s ease";
      notification.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="bi bi-${type === "success" ? "check-circle-fill" : "palette-fill"} fs-4 me-2"></i>
                    <div>${message}</div>
                    <button type="button" class="btn-close ms-3" data-bs-dismiss="alert"></button>
                </div>
            `;

      document.body.appendChild(notification);

      setTimeout(() => {
        notification.style.animation = "slideOutRight 0.3s ease";
        setTimeout(() => notification.remove(), 300);
      }, 3000);
    }
  }

  // Initialize theme manager
  if (!window.themeManager) {
    window.themeManager = new ThemeManager();
  }

  // Add animation styles if not present
  if (!document.querySelector("#theme-animation-styles")) {
    const styles = document.createElement("style");
    styles.id = "theme-animation-styles";
    styles.textContent = `
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
            .theme-switcher {
                position: fixed;
                bottom: 20px;
                right: 20px;
                z-index: 9999;
            }
            .theme-toggle-btn {
                width: 50px;
                height: 50px;
                border-radius: 50%;
                background: var(--gradient-primary, linear-gradient(135deg, #4f46e5, #06b6d4));
                border: none;
                color: white;
                font-size: 24px;
                cursor: pointer;
                box-shadow: 0 4px 15px rgba(0,0,0,0.2);
                transition: transform 0.3s ease;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .theme-toggle-btn:hover {
                transform: scale(1.1);
            }
            .theme-menu {
                position: absolute;
                bottom: 60px;
                right: 0;
                background: var(--bg-white, white);
                border-radius: 12px;
                padding: 10px;
                box-shadow: 0 10px 40px rgba(0,0,0,0.2);
                min-width: 200px;
                display: none;
                border: 1px solid var(--border-color, #e5e7eb);
            }
            .theme-menu.show {
                display: block;
                animation: slideUp 0.3s ease;
            }
            @keyframes slideUp {
                from {
                    opacity: 0;
                    transform: translateY(20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            .theme-option {
                display: flex;
                align-items: center;
                padding: 8px 12px;
                cursor: pointer;
                border-radius: 8px;
                transition: background 0.2s ease;
            }
            .theme-option:hover {
                background: var(--bg-light, #f3f4f6);
            }
            .theme-color-preview {
                width: 30px;
                height: 30px;
                border-radius: 50%;
                margin-right: 10px;
                border: 2px solid var(--border-color, #e5e7eb);
            }
            .theme-name {
                flex: 1;
                font-size: 14px;
            }
            .dropdown-divider {
                margin: 8px 0;
                border-top: 1px solid var(--border-color, #e5e7eb);
            }
            @media (max-width: 768px) {
                .theme-toggle-btn {
                    width: 40px;
                    height: 40px;
                    font-size: 20px;
                }
                .theme-menu {
                    min-width: 180px;
                }
            }
        `;
    document.head.appendChild(styles);
  }
})();
