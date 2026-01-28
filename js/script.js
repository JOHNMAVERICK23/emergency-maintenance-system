function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form.checkValidity()) {
        form.classList.add('was-validated');
        return false;
    }
    return true;
}

function validateDates(startDateId, endDateId, reportDate) {
    const startDate = document.getElementById(startDateId).value;
    const endDate = document.getElementById(endDateId).value;
    
    if (startDate && new Date(startDate) < new Date(reportDate)) {
        alert('Start date cannot be before the report date.');
        return false;
    }
    
    if (endDate && new Date(endDate) < new Date(startDate)) {
        alert('End date cannot be before the start date.');
        return false;
    }
    
    return true;
}

function validatePart(partId, amount) {
    if (!partId || !amount || amount <= 0) {
        alert('Please select a part and enter a positive amount.');
        return false;
    }
    return true;
}

function confirmAction(message) {
    return confirm(message || 'Are you sure you want to proceed?');
}

function toggleSection(sectionId, show) {
    const section = document.getElementById(sectionId);
    if (section) {
        section.style.display = show ? 'block' : 'none';
    }
}

function showLoading(show, elementId = 'loading') {
    const spinner = document.getElementById(elementId);
    if (spinner) {
        spinner.style.display = show ? 'block' : 'none';
    }
}

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-CA'); 
}

function daysBetween(startDate, endDate) {
    const start = new Date(startDate);
    const end = new Date(endDate);
    const diffTime = Math.abs(end - start);
    return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
}

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

function initTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

function initPopovers() {
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
}

document.addEventListener('DOMContentLoaded', function() {
    initTooltips();
    initPopovers();
    
    setTimeout(() => {
        const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
        alerts.forEach(alert => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    },2000);

    document.querySelectorAll('.btn-delete').forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirmAction('This action cannot be undone. Continue?')) {
                e.preventDefault();
            }
        });
    });

    document.querySelectorAll('.btn-cancel').forEach(button => {
        button.classList.add('text-danger');
    });
});