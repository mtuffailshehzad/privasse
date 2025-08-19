import './bootstrap';
import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

// Make Alpine available globally
window.Alpine = Alpine;

// Start Alpine
Alpine.start();

// Chart.js global configuration
Chart.defaults.font.family = 'Inter, system-ui, sans-serif';
Chart.defaults.color = '#6B7280';
Chart.defaults.borderColor = '#E5E7EB';

// Global chart colors
window.chartColors = {
    primary: '#6366F1',
    secondary: '#8B5CF6',
    success: '#10B981',
    warning: '#F59E0B',
    danger: '#EF4444',
    info: '#3B82F6',
    light: '#F3F4F6',
    dark: '#1F2937'
};

// Utility functions
window.AdminUtils = {
    // Format currency
    formatCurrency: (amount, currency = 'AED') => {
        return new Intl.NumberFormat('en-AE', {
            style: 'currency',
            currency: currency
        }).format(amount);
    },

    // Format date
    formatDate: (date, options = {}) => {
        const defaultOptions = {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        };
        return new Intl.DateTimeFormat('en-US', { ...defaultOptions, ...options }).format(new Date(date));
    },

    // Format number
    formatNumber: (number) => {
        return new Intl.NumberFormat('en-US').format(number);
    },

    // Show toast notification
    showToast: (message, type = 'info', duration = 3000) => {
        const toast = document.createElement('div');
        toast.className = `fixed top-4 right-4 z-50 p-4 rounded-md shadow-lg max-w-sm ${getToastClasses(type)}`;
        toast.innerHTML = `
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    ${getToastIcon(type)}
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium">${message}</p>
                </div>
                <div class="ml-4 flex-shrink-0 flex">
                    <button class="inline-flex text-gray-400 hover:text-gray-600 focus:outline-none" onclick="this.parentElement.parentElement.parentElement.remove()">
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            </div>
        `;
        
        document.body.appendChild(toast);
        
        // Auto remove after duration
        setTimeout(() => {
            if (toast.parentElement) {
                toast.remove();
            }
        }, duration);
    },

    // Confirm dialog
    confirm: (message, callback) => {
        if (window.confirm(message)) {
            callback();
        }
    },

    // Copy to clipboard
    copyToClipboard: async (text) => {
        try {
            await navigator.clipboard.writeText(text);
            AdminUtils.showToast('Copied to clipboard!', 'success');
        } catch (err) {
            console.error('Failed to copy: ', err);
            AdminUtils.showToast('Failed to copy to clipboard', 'error');
        }
    },

    // Debounce function
    debounce: (func, wait) => {
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

    // Throttle function
    throttle: (func, limit) => {
        let inThrottle;
        return function() {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }
};

// Helper functions for toast
function getToastClasses(type) {
    const classes = {
        success: 'bg-green-50 border border-green-200 text-green-800',
        error: 'bg-red-50 border border-red-200 text-red-800',
        warning: 'bg-yellow-50 border border-yellow-200 text-yellow-800',
        info: 'bg-blue-50 border border-blue-200 text-blue-800'
    };
    return classes[type] || classes.info;
}

function getToastIcon(type) {
    const icons = {
        success: '<svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>',
        error: '<svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" /></svg>',
        warning: '<svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>',
        info: '<svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" /></svg>'
    };
    return icons[type] || icons.info;
}

// Data table functionality
window.DataTable = {
    init: (tableId, options = {}) => {
        const table = document.getElementById(tableId);
        if (!table) return;

        const defaultOptions = {
            sortable: true,
            searchable: true,
            pagination: true,
            pageSize: 10
        };

        const config = { ...defaultOptions, ...options };
        
        if (config.searchable) {
            addSearchFunctionality(table);
        }
        
        if (config.sortable) {
            addSortFunctionality(table);
        }
        
        if (config.pagination) {
            addPaginationFunctionality(table, config.pageSize);
        }
    }
};

function addSearchFunctionality(table) {
    const searchInput = table.parentElement.querySelector('.table-search');
    if (!searchInput) return;

    searchInput.addEventListener('input', AdminUtils.debounce((e) => {
        const searchTerm = e.target.value.toLowerCase();
        const rows = table.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchTerm) ? '' : 'none';
        });
    }, 300));
}

function addSortFunctionality(table) {
    const headers = table.querySelectorAll('th[data-sortable]');
    
    headers.forEach(header => {
        header.style.cursor = 'pointer';
        header.addEventListener('click', () => {
            const column = header.dataset.sortable;
            const direction = header.dataset.direction === 'asc' ? 'desc' : 'asc';
            
            // Reset other headers
            headers.forEach(h => {
                if (h !== header) {
                    delete h.dataset.direction;
                }
            });
            
            header.dataset.direction = direction;
            sortTable(table, column, direction);
        });
    });
}

function sortTable(table, column, direction) {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    rows.sort((a, b) => {
        const aVal = a.querySelector(`[data-column="${column}"]`)?.textContent || '';
        const bVal = b.querySelector(`[data-column="${column}"]`)?.textContent || '';
        
        if (direction === 'asc') {
            return aVal.localeCompare(bVal, undefined, { numeric: true });
        } else {
            return bVal.localeCompare(aVal, undefined, { numeric: true });
        }
    });
    
    rows.forEach(row => tbody.appendChild(row));
}

// Modal functionality
window.Modal = {
    open: (modalId) => {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
    },
    
    close: (modalId) => {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('hidden');
            document.body.style.overflow = '';
        }
    }
};

// Form validation
window.FormValidator = {
    validate: (formId, rules) => {
        const form = document.getElementById(formId);
        if (!form) return false;
        
        let isValid = true;
        const errors = {};
        
        Object.keys(rules).forEach(fieldName => {
            const field = form.querySelector(`[name="${fieldName}"]`);
            if (!field) return;
            
            const fieldRules = rules[fieldName];
            const value = field.value.trim();
            
            // Required validation
            if (fieldRules.required && !value) {
                errors[fieldName] = fieldRules.messages?.required || 'This field is required';
                isValid = false;
            }
            
            // Min length validation
            if (fieldRules.minLength && value.length < fieldRules.minLength) {
                errors[fieldName] = fieldRules.messages?.minLength || `Minimum ${fieldRules.minLength} characters required`;
                isValid = false;
            }
            
            // Email validation
            if (fieldRules.email && value && !isValidEmail(value)) {
                errors[fieldName] = fieldRules.messages?.email || 'Please enter a valid email address';
                isValid = false;
            }
            
            // Custom validation
            if (fieldRules.custom && typeof fieldRules.custom === 'function') {
                const customResult = fieldRules.custom(value);
                if (customResult !== true) {
                    errors[fieldName] = customResult;
                    isValid = false;
                }
            }
        });
        
        // Display errors
        displayValidationErrors(form, errors);
        
        return isValid;
    }
};

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function displayValidationErrors(form, errors) {
    // Clear previous errors
    form.querySelectorAll('.error-message').forEach(el => el.remove());
    form.querySelectorAll('.border-red-500').forEach(el => {
        el.classList.remove('border-red-500');
        el.classList.add('border-gray-300');
    });
    
    // Display new errors
    Object.keys(errors).forEach(fieldName => {
        const field = form.querySelector(`[name="${fieldName}"]`);
        if (field) {
            field.classList.remove('border-gray-300');
            field.classList.add('border-red-500');
            
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error-message text-red-600 text-sm mt-1';
            errorDiv.textContent = errors[fieldName];
            
            field.parentElement.appendChild(errorDiv);
        }
    });
}

// File upload functionality
window.FileUpload = {
    init: (uploadAreaId, options = {}) => {
        const uploadArea = document.getElementById(uploadAreaId);
        if (!uploadArea) return;
        
        const defaultOptions = {
            maxFiles: 5,
            maxFileSize: 10 * 1024 * 1024, // 10MB
            allowedTypes: ['image/jpeg', 'image/png', 'image/webp', 'application/pdf']
        };
        
        const config = { ...defaultOptions, ...options };
        
        // Drag and drop events
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });
        
        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragover');
        });
        
        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            
            const files = Array.from(e.dataTransfer.files);
            handleFiles(files, config);
        });
        
        // File input change
        const fileInput = uploadArea.querySelector('input[type="file"]');
        if (fileInput) {
            fileInput.addEventListener('change', (e) => {
                const files = Array.from(e.target.files);
                handleFiles(files, config);
            });
        }
    }
};

function handleFiles(files, config) {
    if (files.length > config.maxFiles) {
        AdminUtils.showToast(`Maximum ${config.maxFiles} files allowed`, 'warning');
        return;
    }
    
    files.forEach(file => {
        if (!config.allowedTypes.includes(file.type)) {
            AdminUtils.showToast(`File type ${file.type} not allowed`, 'error');
            return;
        }
        
        if (file.size > config.maxFileSize) {
            AdminUtils.showToast(`File ${file.name} is too large`, 'error');
            return;
        }
        
        // Process file (upload, preview, etc.)
        processFile(file);
    });
}

function processFile(file) {
    // Create file preview
    const reader = new FileReader();
    reader.onload = (e) => {
        // Display preview or handle file data
        console.log('File processed:', file.name);
    };
    reader.readAsDataURL(file);
}

// Real-time updates using Server-Sent Events
window.RealTimeUpdates = {
    init: (endpoint) => {
        if (typeof EventSource === 'undefined') {
            console.warn('Server-Sent Events not supported');
            return;
        }
        
        const eventSource = new EventSource(endpoint);
        
        eventSource.onmessage = (event) => {
            const data = JSON.parse(event.data);
            handleRealTimeUpdate(data);
        };
        
        eventSource.onerror = (error) => {
            console.error('SSE error:', error);
        };
        
        // Close connection when page unloads
        window.addEventListener('beforeunload', () => {
            eventSource.close();
        });
    }
};

function handleRealTimeUpdate(data) {
    switch (data.type) {
        case 'notification':
            AdminUtils.showToast(data.message, data.level || 'info');
            break;
        case 'stats_update':
            updateDashboardStats(data.stats);
            break;
        case 'new_user':
            updateUserCount(data.count);
            break;
        default:
            console.log('Unknown update type:', data.type);
    }
}

function updateDashboardStats(stats) {
    Object.keys(stats).forEach(key => {
        const element = document.querySelector(`[data-stat="${key}"]`);
        if (element) {
            element.textContent = AdminUtils.formatNumber(stats[key]);
        }
    });
}

function updateUserCount(count) {
    const element = document.querySelector('[data-stat="total_users"]');
    if (element) {
        element.textContent = AdminUtils.formatNumber(count);
    }
}

// Initialize common functionality when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    // Initialize tooltips
    const tooltips = document.querySelectorAll('[data-tooltip]');
    tooltips.forEach(tooltip => {
        tooltip.addEventListener('mouseenter', showTooltip);
        tooltip.addEventListener('mouseleave', hideTooltip);
    });
    
    // Initialize dropdowns
    const dropdowns = document.querySelectorAll('[data-dropdown]');
    dropdowns.forEach(dropdown => {
        const trigger = dropdown.querySelector('[data-dropdown-trigger]');
        const menu = dropdown.querySelector('[data-dropdown-menu]');
        
        if (trigger && menu) {
            trigger.addEventListener('click', (e) => {
                e.stopPropagation();
                menu.classList.toggle('hidden');
            });
        }
    });
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', () => {
        dropdowns.forEach(dropdown => {
            const menu = dropdown.querySelector('[data-dropdown-menu]');
            if (menu) {
                menu.classList.add('hidden');
            }
        });
    });
    
    // Initialize data tables
    const tables = document.querySelectorAll('[data-table]');
    tables.forEach(table => {
        DataTable.init(table.id);
    });
});

function showTooltip(e) {
    const tooltip = e.target;
    const text = tooltip.dataset.tooltip;
    
    const tooltipEl = document.createElement('div');
    tooltipEl.className = 'absolute z-50 px-2 py-1 text-sm text-white bg-gray-900 rounded shadow-lg';
    tooltipEl.textContent = text;
    tooltipEl.id = 'tooltip';
    
    document.body.appendChild(tooltipEl);
    
    const rect = tooltip.getBoundingClientRect();
    tooltipEl.style.left = rect.left + (rect.width / 2) - (tooltipEl.offsetWidth / 2) + 'px';
    tooltipEl.style.top = rect.top - tooltipEl.offsetHeight - 5 + 'px';
}

function hideTooltip() {
    const tooltip = document.getElementById('tooltip');
    if (tooltip) {
        tooltip.remove();
    }
}

// Export utilities for use in other scripts
window.AdminJS = {
    utils: AdminUtils,
    modal: Modal,
    validator: FormValidator,
    fileUpload: FileUpload,
    dataTable: DataTable,
    realTime: RealTimeUpdates
};