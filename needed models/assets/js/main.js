/**
 * Main JavaScript file for Income ERP System
 */

// DOM Elements
const sidebar = document.querySelector('.sidebar');
const mainContent = document.querySelector('.main-content');
const header = document.querySelector('.header');
const toggleSidebarBtn = document.querySelector('.toggle-sidebar');
const userDropdownToggle = document.querySelector('.user-dropdown-toggle');
const userDropdownMenu = document.querySelector('.user-dropdown-menu');
const modalTriggers = document.querySelectorAll('[data-toggle="modal"]');
const modalCloseButtons = document.querySelectorAll('.modal-close, .modal-cancel');
const modalBackdrops = document.querySelectorAll('.modal-backdrop');
const forms = document.querySelectorAll('form');
const datePickers = document.querySelectorAll('.datepicker');
const selectPickers = document.querySelectorAll('select');
const dataTableElements = document.querySelectorAll('.datatable');
const deleteButtons = document.querySelectorAll('.btn-delete');
const alertCloseButtons = document.querySelectorAll('.alert .close');

// Initialize DataTables
if (dataTableElements.length > 0) {
    dataTableElements.forEach(table => {
        $(table).DataTable({
            responsive: true,
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search...",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                infoEmpty: "Showing 0 to 0 of 0 entries",
                infoFiltered: "(filtered from _MAX_ total entries)",
                emptyTable: "No data available in table",
                zeroRecords: "No matching records found",
                paginate: {
                    first: "First",
                    last: "Last",
                    next: "Next",
                    previous: "Previous"
                }
            }
        });
    });
}

// Initialize Date Pickers
if (datePickers.length > 0 && typeof flatpickr !== 'undefined') {
    datePickers.forEach(picker => {
        flatpickr(picker, {
            dateFormat: "Y-m-d",
            allowInput: true,
            altInput: true,
            altFormat: "F j, Y",
            theme: "light"
        });
    });
}

// Initialize Select Pickers
if (selectPickers.length > 0 && typeof Choices !== 'undefined') {
    selectPickers.forEach(select => {
        if (!select.classList.contains('no-choices')) {
            new Choices(select, {
                searchEnabled: true,
                itemSelectText: '',
                shouldSort: false
            });
        }
    });
}

// Charts Initialization (if any)
function initCharts() {
    // Sample Revenue Chart
    const revenueChart = document.getElementById('revenue-chart');
    if (revenueChart && typeof Chart !== 'undefined') {
        new Chart(revenueChart, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                datasets: [{
                    label: 'Revenue',
                    data: [65, 59, 80, 81, 56, 55, 40, 58, 62, 70, 75, 80],
                    fill: false,
                    borderColor: 'rgb(26, 86, 219)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
    
    // Sample Income Sources Chart
    const incomeSourcesChart = document.getElementById('income-sources-chart');
    if (incomeSourcesChart && typeof Chart !== 'undefined') {
        new Chart(incomeSourcesChart, {
            type: 'pie',
            data: {
                labels: ['Shop Rent', 'Service Charge', 'Abattoir', 'Car Loading', 'Car Park', 'Hawkers', 'Others'],
                datasets: [{
                    data: [30, 25, 12, 8, 10, 5, 10],
                    backgroundColor: [
                        '#1a56db',
                        '#7e3af2',
                        '#0694a2',
                        '#1c64f2',
                        '#7e22ce',
                        '#0369a1',
                        '#374151'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }
}

// Call charts initialization if needed
document.addEventListener('DOMContentLoaded', () => {
    initCharts();
});

// Toggle Sidebar
if (toggleSidebarBtn) {
    toggleSidebarBtn.addEventListener('click', () => {
        sidebar.classList.toggle('collapsed');
        mainContent.classList.toggle('expanded');
        header.classList.toggle('expanded');
    });
}

// User Dropdown
if (userDropdownToggle && userDropdownMenu) {
    userDropdownToggle.addEventListener('click', (e) => {
        e.preventDefault();
        userDropdownMenu.classList.toggle('show');
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', (e) => {
        if (!userDropdownToggle.contains(e.target) && !userDropdownMenu.contains(e.target)) {
            userDropdownMenu.classList.remove('show');
        }
    });
}

// Modal Handling
if (modalTriggers.length > 0) {
    modalTriggers.forEach(trigger => {
        trigger.addEventListener('click', (e) => {
            e.preventDefault();
            const target = trigger.getAttribute('data-target');
            const modal = document.querySelector(target);
            const backdrop = modal.nextElementSibling;
            
            if (modal) {
                modal.classList.add('show');
                backdrop.classList.add('show');
            }
        });
    });
}

if (modalCloseButtons.length > 0) {
    modalCloseButtons.forEach(button => {
        button.addEventListener('click', () => {
            const modal = button.closest('.modal');
            const backdrop = modal.nextElementSibling;
            
            modal.classList.remove('show');
            backdrop.classList.remove('show');
        });
    });
}

if (modalBackdrops.length > 0) {
    modalBackdrops.forEach(backdrop => {
        backdrop.addEventListener('click', () => {
            const modal = backdrop.previousElementSibling;
            
            modal.classList.remove('show');
            backdrop.classList.remove('show');
        });
    });
}

// Form Validation
if (forms.length > 0) {
    forms.forEach(form => {
        if (form.classList.contains('needs-validation')) {
            form.addEventListener('submit', (e) => {
                if (!form.checkValidity()) {
                    e.preventDefault();
                    e.stopPropagation();
                }
                
                form.classList.add('was-validated');
            }, false);
        }
    });
}

// Delete Confirmation
if (deleteButtons.length > 0) {
    deleteButtons.forEach(button => {
        button.addEventListener('click', (e) => {
            if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
    });
}

// Alert Close Buttons
if (alertCloseButtons.length > 0) {
    alertCloseButtons.forEach(button => {
        button.addEventListener('click', () => {
            const alert = button.closest('.alert');
            alert.remove();
        });
    });
}

// Auto-dismiss alerts after 5 seconds
document.addEventListener('DOMContentLoaded', () => {
    const alerts = document.querySelectorAll('.alert-dismissible');
    
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.remove();
        }, 5000);
    });
});

// Print Functionality
function printContent(el) {
    const printContents = document.getElementById(el).innerHTML;
    const originalContents = document.body.innerHTML;
    
    document.body.innerHTML = printContents;
    window.print();
    document.body.innerHTML = originalContents;
    
    // Reinitialize the JavaScript after printing
    initCharts();
}

// Format currency
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
        minimumFractionDigits: 2
    }).format(amount);
}

// Format date
function formatDate(dateString) {
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    }).format(date);
}

// Form Input Mask for Currency
document.addEventListener('DOMContentLoaded', () => {
    const currencyInputs = document.querySelectorAll('.currency-input');
    
    if (currencyInputs.length > 0) {
        currencyInputs.forEach(input => {
            input.addEventListener('input', function(e) {
                // Keep the cursor position
                const cursorPos = this.selectionStart;
                
                // Get the value without any non-digit characters
                let value = this.value.replace(/[^\d]/g, '');
                
                // Format the value as currency
                if (value.length > 0) {
                    value = (parseInt(value) / 100).toFixed(2);
                    this.value = '₦' + value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                } else {
                    this.value = '';
                }
                
                // Restore the cursor position
                this.setSelectionRange(cursorPos, cursorPos);
            });
        });
    }
});

// AJAX Functions
function ajaxRequest(url, method, data, successCallback, errorCallback) {
    const xhr = new XMLHttpRequest();
    
    xhr.open(method, url, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    
    xhr.onload = function() {
        if (xhr.status >= 200 && xhr.status < 300) {
            try {
                const response = JSON.parse(xhr.responseText);
                successCallback(response);
            } catch (e) {
                errorCallback('Invalid JSON response: ' + e.message);
            }
        } else {
            errorCallback('Request failed with status: ' + xhr.status);
        }
    };
    
    xhr.onerror = function() {
        errorCallback('Network error occurred');
    };
    
    xhr.send(data);
}

// Function to serialize form data
function serializeForm(form) {
    const formData = new FormData(form);
    const serialized = [];
    
    for (const [name, value] of formData) {
        serialized.push(`${encodeURIComponent(name)}=${encodeURIComponent(value)}`);
    }
    
    return serialized.join('&');
}

// Function to handle form submission via AJAX
function submitFormAjax(formId, successCallback, errorCallback) {
    const form = document.getElementById(formId);
    
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<span class="spinner"></span> Processing...';
                submitBtn.disabled = true;
            }
            
            const url = form.getAttribute('action');
            const method = form.getAttribute('method') || 'POST';
            const formData = serializeForm(form);
            
            ajaxRequest(
                url,
                method,
                formData,
                function(response) {
                    if (submitBtn) {
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    }
                    
                    if (successCallback) {
                        successCallback(response);
                    }
                },
                function(error) {
                    if (submitBtn) {
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    }
                    
                    if (errorCallback) {
                        errorCallback(error);
                    } else {
                        alert('Error: ' + error);
                    }
                }
            );
        });
    }
}

// Notifications
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-icon">
            ${type === 'success' ? '<i class="fas fa-check-circle"></i>' : ''}
            ${type === 'error' ? '<i class="fas fa-times-circle"></i>' : ''}
            ${type === 'warning' ? '<i class="fas fa-exclamation-triangle"></i>' : ''}
            ${type === 'info' ? '<i class="fas fa-info-circle"></i>' : ''}
        </div>
        <div class="notification-content">
            <p>${message}</p>
        </div>
        <button class="notification-close">&times;</button>
    `;
    
    document.body.appendChild(notification);
    
    // Show the notification
    setTimeout(() => {
        notification.classList.add('show');
    }, 10);
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 5000);
    
    // Close button
    const closeBtn = notification.querySelector('.notification-close');
    closeBtn.addEventListener('click', () => {
        notification.classList.remove('show');
        setTimeout(() => {
            notification.remove();
        }, 300);
    });
}

// Mobile menu
const mobileMenuTrigger = document.querySelector('.mobile-menu-trigger');
if (mobileMenuTrigger) {
    mobileMenuTrigger.addEventListener('click', () => {
        sidebar.classList.toggle('show');
    });
    
    // Close mobile menu when clicking outside
    document.addEventListener('click', (e) => {
        if (!sidebar.contains(e.target) && !mobileMenuTrigger.contains(e.target) && sidebar.classList.contains('show')) {
            sidebar.classList.remove('show');
        }
    });
}

// Custom number input with increment/decrement buttons
const numberInputs = document.querySelectorAll('.custom-number-input');
if (numberInputs.length > 0) {
    numberInputs.forEach(input => {
        const container = document.createElement('div');
        container.className = 'custom-number-input-container';
        
        const decrementBtn = document.createElement('button');
        decrementBtn.type = 'button';
        decrementBtn.className = 'custom-number-input-decrement';
        decrementBtn.textContent = '-';
        
        const incrementBtn = document.createElement('button');
        incrementBtn.type = 'button';
        incrementBtn.className = 'custom-number-input-increment';
        incrementBtn.textContent = '+';
        
        // Replace the input with the container
        input.parentNode.insertBefore(container, input);
        container.appendChild(decrementBtn);
        container.appendChild(input);
        container.appendChild(incrementBtn);
        
        // Event listeners
        decrementBtn.addEventListener('click', () => {
            if (input.value > parseInt(input.min || 0)) {
                input.value = parseInt(input.value) - 1;
                // Trigger change event
                const event = new Event('change', { bubbles: true });
                input.dispatchEvent(event);
            }
        });
        
        incrementBtn.addEventListener('click', () => {
            if (input.value < parseInt(input.max || Infinity)) {
                input.value = parseInt(input.value) + 1;
                // Trigger change event
                const event = new Event('change', { bubbles: true });
                input.dispatchEvent(event);
            }
        });
    });
}

// Dynamic form fields
const addFieldButtons = document.querySelectorAll('.add-field-button');
if (addFieldButtons.length > 0) {
    addFieldButtons.forEach(button => {
        button.addEventListener('click', () => {
            const fieldContainer = button.closest('.dynamic-fields-container');
            const fieldTemplate = fieldContainer.querySelector('.field-template');
            const newField = fieldTemplate.cloneNode(true);
            
            newField.classList.remove('field-template');
            newField.style.display = 'block';
            
            const removeButton = newField.querySelector('.remove-field-button');
            removeButton.addEventListener('click', () => {
                newField.remove();
            });
            
            fieldContainer.appendChild(newField);
        });
    });
}

// Conditional form fields
const conditionalTriggers = document.querySelectorAll('[data-condition-trigger]');
if (conditionalTriggers.length > 0) {
    conditionalTriggers.forEach(trigger => {
        const updateConditionalFields = () => {
            const targetSelector = trigger.getAttribute('data-condition-target');
            const targetValue = trigger.getAttribute('data-condition-value');
            const targets = document.querySelectorAll(targetSelector);
            
            targets.forEach(target => {
                if (trigger.type === 'checkbox') {
                    if ((trigger.checked && targetValue === 'true') || (!trigger.checked && targetValue === 'false')) {
                        target.style.display = 'block';
                    } else {
                        target.style.display = 'none';
                    }
                } else {
                    if (trigger.value === targetValue) {
                        target.style.display = 'block';
                    } else {
                        target.style.display = 'none';
                    }
                }
            });
        };
        
        // Initial update
        updateConditionalFields();
        
        // Event listener
        trigger.addEventListener('change', updateConditionalFields);
    });
}
