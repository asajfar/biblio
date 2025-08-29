/**
 * Main JavaScript file for Business Promotion App
 * Contains common functionality and utilities
 */

// Global app object
const BusinessApp = {
    // App configuration
    config: {
        mapDefaultLat: 44.7866,
        mapDefaultLng: 20.4489,
        mapDefaultZoom: 10,
        maxFileSize: 5242880, // 5MB
        allowedExtensions: ['jpg', 'jpeg', 'png', 'gif']
    },
    
    // Initialize app
    init() {
        this.initializeComponents();
        this.bindEvents();
        this.loadSavedPreferences();
    },
    
    // Initialize various components
    initializeComponents() {
        // Initialize tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Initialize popovers
        const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        popoverTriggerList.map(function (popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl);
        });
        
        // Initialize form validation
        this.initFormValidation();
        
        // Initialize image preview
        this.initImagePreview();
        
        // Initialize search functionality
        this.initSearch();
    },
    
    // Bind global events
    bindEvents() {
        // Handle form submissions
        document.addEventListener('submit', this.handleFormSubmit.bind(this));
        
        // Handle file uploads
        document.addEventListener('change', this.handleFileUpload.bind(this));
        
        // Handle AJAX errors
        window.addEventListener('error', this.handleError.bind(this));
        
        // Handle scroll events
        window.addEventListener('scroll', this.handleScroll.bind(this));
        
        // Handle resize events
        window.addEventListener('resize', this.handleResize.bind(this));
    },
    
    // Initialize form validation
    initFormValidation() {
        const forms = document.querySelectorAll('.needs-validation');
        Array.from(forms).forEach(form => {
            form.addEventListener('submit', event => {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            });
        });
    },
    
    // Initialize image preview functionality
    initImagePreview() {
        const imageInputs = document.querySelectorAll('input[type="file"][accept*="image"]');
        imageInputs.forEach(input => {
            input.addEventListener('change', this.previewImages.bind(this));
        });
    },
    
    // Initialize search functionality
    initSearch() {
        const searchInputs = document.querySelectorAll('.search-input');
        searchInputs.forEach(input => {
            input.addEventListener('input', this.debounce(this.handleSearch.bind(this), 300));
        });
    },
    
    // Handle form submissions
    handleFormSubmit(event) {
        const form = event.target;
        
        // Add loading state to submit button
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<span class="spinner"></span> Slanje...';
            
            // Restore button after 10 seconds (failsafe)
            setTimeout(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }, 10000);
        }
    },
    
    // Handle file uploads
    handleFileUpload(event) {
        const input = event.target;
        if (input.type !== 'file') return;
        
        const files = input.files;
        if (!files.length) return;
        
        // Validate files
        for (let file of files) {
            if (!this.validateFile(file)) {
                input.value = '';
                return;
            }
        }
        
        // Preview images if it's an image input
        if (input.accept && input.accept.includes('image')) {
            this.previewImages(event);
        }
    },
    
    // Validate file upload
    validateFile(file) {
        // Check file size
        if (file.size > this.config.maxFileSize) {
            this.showAlert('Fajl je prevelik. Maksimalna veličina je 5MB.', 'danger');
            return false;
        }
        
        // Check file extension
        const extension = file.name.split('.').pop().toLowerCase();
        if (this.config.allowedExtensions.indexOf(extension) === -1) {
            this.showAlert('Nedozvoljena ekstenzija. Dozvoljene: ' + this.config.allowedExtensions.join(', '), 'danger');
            return false;
        }
        
        return true;
    },
    
    // Preview uploaded images
    previewImages(event) {
        const input = event.target;
        const files = input.files;
        const previewContainer = document.getElementById(input.dataset.preview || 'image-preview');
        
        if (!previewContainer) return;
        
        previewContainer.innerHTML = '';
        
        Array.from(files).forEach(file => {
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    const preview = document.createElement('div');
                    preview.className = 'col-md-3 mb-3';
                    preview.innerHTML = `
                        <div class="card">
                            <img src="${e.target.result}" class="card-img-top" style="height: 150px; object-fit: cover;">
                            <div class="card-body p-2">
                                <small class="text-muted">${file.name}</small>
                            </div>
                        </div>
                    `;
                    previewContainer.appendChild(preview);
                };
                reader.readAsDataURL(file);
            }
        });
    },
    
    // Handle search input
    handleSearch(event) {
        const query = event.target.value.trim();
        if (query.length < 2) return;
        
        // Implement live search functionality here
        console.log('Searching for:', query);
    },
    
    // Handle errors
    handleError(event) {
        console.error('App Error:', event.error);
        // You can implement error reporting here
    },
    
    // Handle scroll events
    handleScroll() {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        
        // Show/hide back to top button
        const backToTopBtn = document.getElementById('back-to-top');
        if (backToTopBtn) {
            if (scrollTop > 300) {
                backToTopBtn.style.display = 'block';
            } else {
                backToTopBtn.style.display = 'none';
            }
        }
        
        // Add shadow to navbar when scrolled
        const navbar = document.querySelector('.navbar');
        if (navbar) {
            if (scrollTop > 50) {
                navbar.classList.add('shadow');
            } else {
                navbar.classList.remove('shadow');
            }
        }
    },
    
    // Handle resize events
    handleResize() {
        // Responsive map handling
        const map = window.mapInstance;
        if (map) {
            setTimeout(() => {
                map.invalidateSize();
            }, 300);
        }
    },
    
    // Show alert message
    showAlert(message, type = 'info', duration = 5000) {
        const alertContainer = document.getElementById('alert-container') || document.body;
        
        const alert = document.createElement('div');
        alert.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        alert.style.cssText = 'top: 20px; right: 20px; z-index: 9999; max-width: 400px;';
        alert.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        alertContainer.appendChild(alert);
        
        // Auto remove alert
        setTimeout(() => {
            if (alert.parentNode) {
                alert.remove();
            }
        }, duration);
    },
    
    // Show loading spinner
    showLoading(target = document.body) {
        const spinner = document.createElement('div');
        spinner.id = 'loading-spinner';
        spinner.className = 'position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center';
        spinner.style.cssText = 'background: rgba(0,0,0,0.5); z-index: 9999;';
        spinner.innerHTML = `
            <div class="bg-white p-4 rounded shadow">
                <div class="d-flex align-items-center">
                    <div class="spinner me-3"></div>
                    <span>Učitavanje...</span>
                </div>
            </div>
        `;
        
        target.appendChild(spinner);
    },
    
    // Hide loading spinner
    hideLoading() {
        const spinner = document.getElementById('loading-spinner');
        if (spinner) {
            spinner.remove();
        }
    },
    
    // Debounce function
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },
    
    // Format number with thousands separator
    formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    },
    
    // Format date
    formatDate(date, format = 'dd.mm.yyyy') {
        const d = new Date(date);
        const day = String(d.getDate()).padStart(2, '0');
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const year = d.getFullYear();
        
        switch (format) {
            case 'dd.mm.yyyy':
                return `${day}.${month}.${year}`;
            case 'mm/dd/yyyy':
                return `${month}/${day}/${year}`;
            case 'yyyy-mm-dd':
                return `${year}-${month}-${day}`;
            default:
                return d.toLocaleDateString();
        }
    },
    
    // Save user preferences
    savePreference(key, value) {
        try {
            localStorage.setItem(`businessapp_${key}`, JSON.stringify(value));
        } catch (e) {
            console.warn('Could not save preference:', e);
        }
    },
    
    // Load user preferences
    loadPreference(key, defaultValue = null) {
        try {
            const value = localStorage.getItem(`businessapp_${key}`);
            return value ? JSON.parse(value) : defaultValue;
        } catch (e) {
            console.warn('Could not load preference:', e);
            return defaultValue;
        }
    },
    
    // Load saved preferences
    loadSavedPreferences() {
        // Load theme preference
        const theme = this.loadPreference('theme', 'light');
        if (theme === 'dark') {
            document.body.classList.add('dark-theme');
        }
        
        // Load language preference
        const language = this.loadPreference('language', 'sr');
        if (language !== 'sr') {
            // Implement language switching here
        }
    },
    
    // AJAX helper function
    async fetchData(url, options = {}) {
        try {
            const response = await fetch(url, {
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    ...options.headers
                },
                ...options
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            return await response.json();
        } catch (error) {
            console.error('Fetch error:', error);
            this.showAlert('Greška pri učitavanju podataka.', 'danger');
            throw error;
        }
    },
    
    // Scroll to element
    scrollTo(element, offset = 0) {
        const target = typeof element === 'string' ? document.querySelector(element) : element;
        if (target) {
            const targetPosition = target.offsetTop - offset;
            window.scrollTo({
                top: targetPosition,
                behavior: 'smooth'
            });
        }
    },
    
    // Copy text to clipboard
    async copyToClipboard(text) {
        try {
            await navigator.clipboard.writeText(text);
            this.showAlert('Kopirano u clipboard!', 'success');
        } catch (err) {
            console.error('Failed to copy:', err);
            this.showAlert('Greška pri kopiranju.', 'danger');
        }
    }
};

// Map utilities
const MapUtils = {
    // Initialize Leaflet map
    initMap(containerId, lat = BusinessApp.config.mapDefaultLat, lng = BusinessApp.config.mapDefaultLng, zoom = BusinessApp.config.mapDefaultZoom) {
        const map = L.map(containerId).setView([lat, lng], zoom);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);
        
        // Store map instance globally
        window.mapInstance = map;
        
        return map;
    },
    
    // Add marker to map
    addMarker(map, lat, lng, popupContent = '', options = {}) {
        const defaultOptions = {
            radius: 8,
            fillColor: '#007bff',
            color: '#fff',
            weight: 2,
            opacity: 1,
            fillOpacity: 0.8
        };
        
        const marker = L.circleMarker([lat, lng], { ...defaultOptions, ...options }).addTo(map);
        
        if (popupContent) {
            marker.bindPopup(popupContent);
        }
        
        return marker;
    },
    
    // Get user location
    getUserLocation(callback) {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                position => {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    callback(null, { lat, lng });
                },
                error => {
                    console.error('Geolocation error:', error);
                    callback(error, null);
                }
            );
        } else {
            callback(new Error('Geolocation not supported'), null);
        }
    }
};

// Form utilities
const FormUtils = {
    // Serialize form data
    serializeForm(form) {
        const formData = new FormData(form);
        const data = {};
        
        for (let [key, value] of formData.entries()) {
            if (data[key]) {
                if (Array.isArray(data[key])) {
                    data[key].push(value);
                } else {
                    data[key] = [data[key], value];
                }
            } else {
                data[key] = value;
            }
        }
        
        return data;
    },
    
    // Reset form with animation
    resetForm(form) {
        form.reset();
        form.classList.remove('was-validated');
        
        // Clear image previews
        const previews = form.querySelectorAll('[id*="preview"]');
        previews.forEach(preview => {
            preview.innerHTML = '';
        });
    }
};

// Initialize app when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    BusinessApp.init();
    
    // Add back to top button
    const backToTopBtn = document.createElement('button');
    backToTopBtn.id = 'back-to-top';
    backToTopBtn.className = 'btn btn-primary position-fixed';
    backToTopBtn.style.cssText = 'bottom: 20px; right: 20px; z-index: 1000; display: none; border-radius: 50%; width: 50px; height: 50px;';
    backToTopBtn.innerHTML = '<i class="fas fa-arrow-up"></i>';
    backToTopBtn.onclick = () => BusinessApp.scrollTo(document.body);
    document.body.appendChild(backToTopBtn);
});

// Export for use in other scripts
window.BusinessApp = BusinessApp;
window.MapUtils = MapUtils;
window.FormUtils = FormUtils;