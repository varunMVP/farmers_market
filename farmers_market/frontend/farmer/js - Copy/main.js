/**
 * Direct Market Access for Farmers
 * Main JavaScript file for handling client-side functionality
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize any components when the DOM is fully loaded
    initializeComponents();

    // Attach event listeners
    attachEventListeners();

    // Show success/error messages if any
    showMessages();
});

/**
 * Initialize UI components
 */
function initializeComponents() {
    // Toggle mobile navigation menu
    const navToggle = document.getElementById('nav-toggle');
    if (navToggle) {
        navToggle.addEventListener('click', function() {
            const navMenu = document.querySelector('.navbar-nav');
            navMenu.classList.toggle('active');
        });
    }

    // Initialize form validations
    initFormValidations();

    // Initialize image previews
    initImagePreviews();
}

/**
 * Attach event listeners to interactive elements
 */
function attachEventListeners() {
    // Login form submission
    const loginForm = document.getElementById('login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            if (!validateLoginForm()) {
                e.preventDefault();
            }
        });
    }

    // Registration form submission
    const registerForm = document.getElementById('register-form');
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            if (!validateRegisterForm()) {
                e.preventDefault();
            }
        });
    }

    // Add product form submission
    const addProductForm = document.getElementById('add-product-form');
    if (addProductForm) {
        addProductForm.addEventListener('submit', function(e) {
            if (!validateProductForm()) {
                e.preventDefault();
            }
        });
    }

    // Preorder request actions
    const acceptButtons = document.querySelectorAll('.accept-preorder');
    const denyButtons = document.querySelectorAll('.deny-preorder');
    
    acceptButtons.forEach(button => {
        button.addEventListener('click', function() {
            handlePreorderAction(this.dataset.id, 'accept');
        });
    });
    
    denyButtons.forEach(button => {
        button.addEventListener('click', function() {
            handlePreorderAction(this.dataset.id, 'deny');
        });
    });

    // Product category filters
    const categoryFilters = document.querySelectorAll('.category-filter');
    categoryFilters.forEach(filter => {
        filter.addEventListener('click', function() {
            filterProducts(this.dataset.category);
        });
    });

    // Quantity input handlers
    const quantityInputs = document.querySelectorAll('.quantity-input');
    quantityInputs.forEach(input => {
        input.addEventListener('change', function() {
            updateTotalPrice(this);
        });
    });
}

/**
 * Show success/error messages
 */
function showMessages() {
    // Check URL parameters for messages
    const urlParams = new URLSearchParams(window.location.search);
    const message = urlParams.get('message');
    const messageType = urlParams.get('type');
    
    if (message) {
        showAlert(message, messageType || 'info');
    }

    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            alert.style.display = 'none';
        });
    }, 5000);
}

/**
 * Show an alert message
 * @param {string} message - The message to display
 * @param {string} type - The type of alert (success, danger, warning, info)
 */
function showAlert(message, type) {
    const alertContainer = document.getElementById('alert-container');
    if (!alertContainer) return;
    
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.textContent = message;
    
    alertContainer.appendChild(alertDiv);
}

/**
 * Validate login form
 * @returns {boolean} - Whether the form is valid
 */
function validateLoginForm() {
    const username = document.getElementById('username');
    const password = document.getElementById('password');
    let isValid = true;
    
    if (!username.value.trim()) {
        showInputError(username, 'Username is required');
        isValid = false;
    }
    
    if (!password.value) {
        showInputError(password, 'Password is required');
        isValid = false;
    }
    
    return isValid;
}

/**
 * Validate registration form
 * @returns {boolean} - Whether the form is valid
 */
function validateRegisterForm() {
    const username = document.getElementById('username');
    const email = document.getElementById('email');
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm-password');
    const fullName = document.getElementById('full-name');
    const phone = document.getElementById('phone');
    const address = document.getElementById('address');
    let isValid = true;
    
    if (!username.value.trim()) {
        showInputError(username, 'Username is required');
        isValid = false;
    }
    
    if (!email.value.trim()) {
        showInputError(email, 'Email is required');
        isValid = false;
    } else if (!isValidEmail(email.value.trim())) {
        showInputError(email, 'Please enter a valid email address');
        isValid = false;
    }
    
    if (!password.value) {
        showInputError(password, 'Password is required');
        isValid = false;
    } else if (password.value.length < 8) {
        showInputError(password, 'Password must be at least 8 characters');
        isValid = false;
    }
    
    if (password.value !== confirmPassword.value) {
        showInputError(confirmPassword, 'Passwords do not match');
        isValid = false;
    }
    
    if (!fullName.value.trim()) {
        showInputError(fullName, 'Full name is required');
        isValid = false;
    }
    
    if (!phone.value.trim()) {
        showInputError(phone, 'Phone number is required');
        isValid = false;
    }
    
    if (!address.value.trim()) {
        showInputError(address, 'Address is required');
        isValid = false;
    }
    
    return isValid;
}

/**
 * Validate product form
 * @returns {boolean} - Whether the form is valid
 */
function validateProductForm() {
    const productName = document.getElementById('product-name');
    const category = document.getElementById('category');
    const quantity = document.getElementById('quantity');
    const price = document.getElementById('price');
    let isValid = true;
    
    if (!productName.value.trim()) {
        showInputError(productName, 'Product name is required');
        isValid = false;
    }
    
    if (!category.value) {
        showInputError(category, 'Please select a category');
        isValid = false;
    }
    
    if (!quantity.value || quantity.value <= 0) {
        showInputError(quantity, 'Please enter a valid quantity');
        isValid = false;
    }
    
    if (!price.value || price.value <= 0) {
        showInputError(price, 'Please enter a valid price');
        isValid = false;
    }
    
    return isValid;
}

/**
 * Show error message for an input field
 * @param {HTMLElement} inputElement - The input element
 * @param {string} message - The error message
 */
function showInputError(inputElement, message) {
    inputElement.classList.add('is-invalid');
    
    // Check if error message element already exists
    let errorEl = inputElement.parentElement.querySelector('.error-message');
    if (!errorEl) {
        errorEl = document.createElement('div');
        errorEl.className = 'error-message';
        errorEl.style.color = '#dc3545';
        errorEl.style.fontSize = '14px';
        errorEl.style.marginTop = '5px';
        inputElement.parentElement.appendChild(errorEl);
    }
    
    errorEl.textContent = message;
    
    // Add event listener to remove error on input
    inputElement.addEventListener('input', function() {
        this.classList.remove('is-invalid');
        errorEl.textContent = '';
    }, { once: true });
}

/**
 * Check if email is valid
 * @param {string} email - The email to validate
 * @returns {boolean} - Whether the email is valid
 */
function isValidEmail(email) {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(email);
}

/**
 * Handle preorder accept/deny actions
 * @param {string} preorderId - The preorder ID
 * @param {string} action - The action (accept/deny)
 */
function handlePreorderAction(preorderId, action) {
    const url = action === 'accept' ? '../backend/farmer/accept_preorder.php' : '../backend/farmer/deny_preorder.php';
    
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `preorder_id=${preorderId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            // Refresh the preorder list or update the specific row
            updatePreorderStatus(preorderId, action);
        } else {
            showAlert(data.message, 'danger');
        }
    })
    .catch(error => {
        showAlert('An error occurred. Please try again.', 'danger');
        console.error('Error:', error);
    });
}

/**
 * Update preorder status in the UI
 * @param {string} preorderId - The preorder ID
 * @param {string} action - The action (accept/deny)
 */
function updatePreorderStatus(preorderId, action) {
    const statusCell = document.querySelector(`.preorder-row[data-id="${preorderId}"] .status-cell`);
    const actionButtons = document.querySelector(`.preorder-row[data-id="${preorderId}"] .action-buttons`);
    
    if (statusCell) {
        statusCell.textContent = action === 'accept' ? 'Accepted' : 'Denied';
        statusCell.className = `status-cell status-${action === 'accept' ? 'approved' : 'rejected'}`;
    }
    
    if (actionButtons) {
        actionButtons.innerHTML = '<span>Processed</span>';
    }
}

/**
 * Filter products by category
 * @param {string} category - The category to filter by
 */
function filterProducts(category) {
    const productCards = document.querySelectorAll('.product-card');
    
    // Remove active class from all filters
    document.querySelectorAll('.category-filter').forEach(filter => {
        filter.classList.remove('active');
    });
    
    // Add active class to selected filter
    document.querySelector(`.category-filter[data-category="${category}"]`).classList.add('active');
    
    productCards.forEach(card => {
        if (category === 'all' || card.dataset.category === category) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

/**
 * Update total price based on quantity
 * @param {HTMLElement} quantityInput - The quantity input element
 */
function updateTotalPrice(quantityInput) {
    const productCard = quantityInput.closest('.product-card');
    const pricePerUnit = parseFloat(productCard.dataset.price);
    const quantity = parseFloat(quantityInput.value);
    const totalPriceEl = productCard.querySelector('.total-price');
    
    if (totalPriceEl && !isNaN(pricePerUnit) && !isNaN(quantity) && quantity > 0) {
        const totalPrice = pricePerUnit * quantity;
        totalPriceEl.textContent = `Total: $${totalPrice.toFixed(2)}`;
    }
}

/**
 * Initialize form validations
 */
function initFormValidations() {
    // Remove default browser validation
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.setAttribute('novalidate', true);
    });
    
    // Add custom validation
    const inputs = document.querySelectorAll('input, select, textarea');
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            validateInput(this);
        });
    });
}

/**
 * Validate a single input
 * @param {HTMLElement} input - The input element
 */
function validateInput(input) {
    if (input.hasAttribute('required') && !input.value.trim()) {
        showInputError(input, `${input.name || 'This field'} is required`);
        return false;
    }
    
    if (input.type === 'email' && input.value.trim() && !isValidEmail(input.value.trim())) {
        showInputError(input, 'Please enter a valid email address');
        return false;
    }
    
    if (input.type === 'number' && input.min && parseFloat(input.value) < parseFloat(input.min)) {
        showInputError(input, `Value must be at least ${input.min}`);
        return false;
    }
    
    return true;
}

/**
 * Initialize image previews for product uploads
 */
function initImagePreviews() {
    const imageInput = document.getElementById('product-image');
    const previewContainer = document.getElementById('image-preview');
    
    if (imageInput && previewContainer) {
        imageInput.addEventListener('change', function() {
            previewContainer.innerHTML = '';
            
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'preview-img';
                    img.style.maxWidth = '100%';
                    img.style.maxHeight = '200px';
                    previewContainer.appendChild(img);
                }
                
                reader.readAsDataURL(this.files[0]);
            } else {
                // Add a placeholder if no image is selected
                const placeholder = document.createElement('div');
                placeholder.className = 'img-placeholder';
                placeholder.textContent = 'No image selected';
                previewContainer.appendChild(placeholder);
            }
        });
        
        // Trigger change event to show placeholder initially
        if (previewContainer.innerHTML === '') {
            const placeholder = document.createElement('div');
            placeholder.className = 'img-placeholder';
            placeholder.textContent = 'No image selected';
            previewContainer.appendChild(placeholder);
        }
    }
}