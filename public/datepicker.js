/**
 * Zion Date Picker - Custom styled date picker using Flatpickr
 * Matches the website theme with orange accent color
 */

// Initialize date pickers when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeDatePickers();
});

/**
 * Initialize all date pickers on the page
 */
function initializeDatePickers() {
    // Find all date inputs and convert them to custom date pickers
    const dateInputs = document.querySelectorAll('input[type="date"]');
    
    dateInputs.forEach(input => {
        initializeDatePicker(input);
    });
    
    // Initialize any elements with .date-picker class
    const customDatePickers = document.querySelectorAll('.date-picker');
    customDatePickers.forEach(input => {
        initializeDatePicker(input);
    });
}

/**
 * Initialize a single date picker
 * @param {HTMLElement} element - The input element to convert
 * @param {Object} options - Custom options for the date picker
 */
function initializeDatePicker(element, options = {}) {
    // Skip if already initialized
    if (element._flatpickr) {
        return element._flatpickr;
    }
    
    // Add custom class for styling
    element.classList.add('custom-date');
    
    // Default configuration
    const defaultConfig = {
        dateFormat: "Y-m-d",
        allowInput: true,
        clickOpens: true,
        animate: true,
        theme: "light",
        locale: {
            firstDayOfWeek: 1 // Monday
        },
        // Custom positioning
        position: "auto",
        // Animation
        animate: true,
        // Close on select
        closeOnSelect: true,
        // Allow manual input
        allowInput: true,
        // Show clear button
        wrap: false,
        // Default date (can be overridden)
        defaultDate: element.value || null,
        // Change month and year with dropdowns
        changeMonth: true,
        changeYear: true,
        // Show week numbers
        weekNumbers: false,
        // Disable past dates (can be overridden)
        // minDate: "today",
    };
    
    // Merge with custom options
    const config = { ...defaultConfig, ...options };
    
    // Special handling for different input types
    if (element.hasAttribute('data-enable-time')) {
        config.enableTime = true;
        config.time_24hr = true;
        config.dateFormat = "Y-m-d H:i";
    }
    
    if (element.hasAttribute('data-mode')) {
        config.mode = element.getAttribute('data-mode'); // single, multiple, range
    }
    
    if (element.hasAttribute('data-min-date')) {
        config.minDate = element.getAttribute('data-min-date');
    }
    
    if (element.hasAttribute('data-max-date')) {
        config.maxDate = element.getAttribute('data-max-date');
    }
    
    if (element.hasAttribute('data-disable-weekends')) {
        config.disable = [
            function(date) {
                // Disable weekends (Saturday = 6, Sunday = 0)
                return (date.getDay() === 0 || date.getDay() === 6);
            }
        ];
    }
    
    // Initialize Flatpickr
    const fp = flatpickr(element, config);
    
    // Add icon if the element is wrapped in a container
    addDatePickerIcon(element);
    
    return fp;
}

/**
 * Add calendar icon to date picker
 * @param {HTMLElement} element - The input element
 */
function addDatePickerIcon(element) {
    // Check if parent has the date-picker-container class
    let container = element.parentElement;
    
    if (!container.classList.contains('date-picker-container')) {
        // Create container wrapper
        container = document.createElement('div');
        container.className = 'date-picker-container';
        element.parentNode.insertBefore(container, element);
        container.appendChild(element);
    }
    
    // Add icon if not already present
    if (!container.querySelector('.date-picker-icon')) {
        const icon = document.createElement('i');
        icon.className = 'fas fa-calendar-alt date-picker-icon';
        container.appendChild(icon);
        
        // Update input padding to make room for icon
        element.style.paddingLeft = '42px';
        element.classList.add('date-picker-input');
    }
}

/**
 * Create a date range picker
 * @param {HTMLElement} startElement - Start date input
 * @param {HTMLElement} endElement - End date input
 * @param {Object} options - Custom options
 */
function initializeDateRangePicker(startElement, endElement, options = {}) {
    const config = {
        dateFormat: "Y-m-d",
        allowInput: true,
        clickOpens: true,
        animate: true,
        closeOnSelect: false,
        ...options
    };
    
    // Initialize start date picker
    const startFp = initializeDatePicker(startElement, {
        ...config,
        onChange: function(selectedDates, dateStr, instance) {
            // Update end date picker's minDate
            if (selectedDates.length > 0) {
                endFp.set('minDate', selectedDates[0]);
            }
            if (options.onStartChange) {
                options.onStartChange(selectedDates, dateStr, instance);
            }
        }
    });
    
    // Initialize end date picker
    const endFp = initializeDatePicker(endElement, {
        ...config,
        onChange: function(selectedDates, dateStr, instance) {
            // Update start date picker's maxDate
            if (selectedDates.length > 0) {
                startFp.set('maxDate', selectedDates[0]);
            }
            if (options.onEndChange) {
                options.onEndChange(selectedDates, dateStr, instance);
            }
        }
    });
    
    return { startFp, endFp };
}

/**
 * Utility function to format date
 * @param {Date} date - Date to format
 * @param {string} format - Format string (default: Y-m-d)
 * @returns {string} Formatted date string
 */
function formatDate(date, format = 'Y-m-d') {
    if (!date) return '';
    
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    
    return format
        .replace('Y', year)
        .replace('m', month)
        .replace('d', day);
}

/**
 * Utility function to get today's date in Y-m-d format
 * @returns {string} Today's date
 */
function getTodayDate() {
    return formatDate(new Date());
}

// Export functions for global use
window.ZionDatePicker = {
    initialize: initializeDatePicker,
    initializeRange: initializeDateRangePicker,
    formatDate: formatDate,
    getTodayDate: getTodayDate,
    reinitialize: initializeDatePickers
};
