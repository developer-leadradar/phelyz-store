// Phelyz Admin Panel JavaScript

// Toggle Sidebar (Mobile)
function toggleSidebar() {
    const sidebar = document.querySelector('.admin-sidebar');
    if (sidebar) {
        sidebar.classList.toggle('open');
    }
}

// Delete confirmation — used as onclick="return confirmDelete('message')"
// Returns true (proceed with href) or false (cancel)
function confirmDelete(message) {
    return confirm(message || 'Are you sure you want to delete this?');
}

// Delete Category Confirmation
function confirmDeleteCategory(categoryId, categoryName) {
    if (confirm(`Are you sure you want to delete category "${categoryName}"? This will also delete all products in this category.`)) {
        window.location.href = `delete-category.php?id=${categoryId}`;
    }
}

// Delete Customer Confirmation
function confirmDeleteCustomer(customerId, customerName) {
    if (confirm(`Are you sure you want to delete customer "${customerName}"?`)) {
        window.location.href = `delete-customer.php?id=${customerId}`;
    }
}

// Update Order Status
async function updateOrderStatus(orderId, newStatus) {
    try {
        const response = await fetch('update-order-status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                order_id: orderId,
                status: newStatus
            })
        });

        const data = await response.json();

        if (data.success) {
            showNotification('Order status updated successfully', 'success');
            location.reload();
        } else {
            showNotification('Failed to update order status', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('An error occurred', 'error');
    }
}

// Image Preview
function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            const preview = document.getElementById('image-preview');
            if (preview) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            } else {
                // Create preview if doesn't exist
                const img = document.createElement('img');
                img.id = 'image-preview';
                img.src = e.target.result;
                img.style.cssText = 'max-width: 200px; margin-top: 10px; border-radius: 5px;';
                input.parentElement.appendChild(img);
            }
        };
        
        reader.readAsDataURL(input.files[0]);
    }
}

// Form Validation
function validateProductForm() {
    const name = document.getElementById('product_name').value.trim();
    const price = parseFloat(document.getElementById('price').value);
    const stock = parseInt(document.getElementById('stock').value);
    const category = document.getElementById('category').value;

    if (!name) {
        showNotification('Product name is required', 'error');
        return false;
    }

    if (!price || price <= 0) {
        showNotification('Valid price is required', 'error');
        return false;
    }

    if (!stock || stock < 0) {
        showNotification('Valid stock quantity is required', 'error');
        return false;
    }

    if (!category) {
        showNotification('Category is required', 'error');
        return false;
    }

    return true;
}

// Bulk Actions
function handleBulkAction() {
    const action = document.getElementById('bulk-action').value;
    const checkboxes = document.querySelectorAll('input[name="product_ids[]"]:checked');
    
    if (checkboxes.length === 0) {
        showNotification('Please select at least one item', 'warning');
        return;
    }

    if (!action) {
        showNotification('Please select an action', 'warning');
        return;
    }

    const productIds = Array.from(checkboxes).map(cb => cb.value);
    
    if (action === 'delete') {
        if (confirm(`Delete ${productIds.length} selected items?`)) {
            // Implement bulk delete
            console.log('Bulk delete:', productIds);
        }
    } else if (action === 'activate') {
        // Implement bulk activate
        console.log('Bulk activate:', productIds);
    } else if (action === 'deactivate') {
        // Implement bulk deactivate
        console.log('Bulk deactivate:', productIds);
    }
}

// Select All Checkbox
function toggleSelectAll(source) {
    const checkboxes = document.querySelectorAll('input[name="product_ids[]"]');
    checkboxes.forEach(checkbox => {
        checkbox.checked = source.checked;
    });
}

// Charts (using Chart.js if available)
function initSalesChart() {
    const canvas = document.getElementById('salesChart');
    if (!canvas || typeof Chart === 'undefined') return;

    const ctx = canvas.getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            datasets: [{
                label: 'Sales',
                data: [12000, 19000, 15000, 25000, 22000, 30000],
                borderColor: '#FFD700',
                backgroundColor: 'rgba(255, 215, 0, 0.1)',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '₦' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
}

// Data Tables Enhancement
function initDataTable() {
    const tables = document.querySelectorAll('.data-table');
    
    tables.forEach(table => {
        // Add sortable headers
        const headers = table.querySelectorAll('th[data-sortable]');
        headers.forEach(header => {
            header.style.cursor = 'pointer';
            header.addEventListener('click', function() {
                sortTable(table, this);
            });
        });
    });
}

function sortTable(table, header) {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    const column = Array.from(header.parentElement.children).indexOf(header);
    const isAscending = header.dataset.order !== 'asc';

    rows.sort((a, b) => {
        const aValue = a.children[column].textContent.trim();
        const bValue = b.children[column].textContent.trim();
        
        if (!isNaN(aValue) && !isNaN(bValue)) {
            return isAscending ? aValue - bValue : bValue - aValue;
        }
        
        return isAscending ? 
            aValue.localeCompare(bValue) : 
            bValue.localeCompare(aValue);
    });

    rows.forEach(row => tbody.appendChild(row));
    header.dataset.order = isAscending ? 'asc' : 'desc';
}

// Export Data
function exportToCSV(tableId, filename = 'export.csv') {
    const table = document.getElementById(tableId);
    if (!table) return;

    let csv = [];
    const rows = table.querySelectorAll('tr');

    rows.forEach(row => {
        const cols = row.querySelectorAll('td, th');
        const csvRow = Array.from(cols).map(col => {
            return '"' + col.textContent.trim().replace(/"/g, '""') + '"';
        });
        csv.push(csvRow.join(','));
    });

    const csvString = csv.join('\n');
    const blob = new Blob([csvString], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    a.click();
}

// Print Invoice
function printInvoice() {
    window.print();
}

// Show Notification
function showNotification(message, type = 'info') {
    const existing = document.querySelector('.notification');
    if (existing) {
        existing.remove();
    }

    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;

    notification.style.cssText = `
        position: fixed;
        top: 80px;
        right: 20px;
        padding: 15px 25px;
        border-radius: 5px;
        color: white;
        font-weight: 500;
        z-index: 10000;
        animation: slideIn 0.3s ease-out;
        box-shadow: 0 4px 20px rgba(0,0,0,0.2);
    `;

    if (type === 'success') {
        notification.style.background = '#4CAF50';
    } else if (type === 'error') {
        notification.style.background = '#F44336';
    } else if (type === 'warning') {
        notification.style.background = '#FF9800';
    } else {
        notification.style.background = '#2196F3';
    }

    document.body.appendChild(notification);

    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease-in';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    console.log('Admin panel initialized');
    
    // Initialize charts
    initSalesChart();
    
    // Initialize data tables
    initDataTable();
    
    // Image upload preview
    const imageInput = document.getElementById('product_image');
    if (imageInput) {
        imageInput.addEventListener('change', function() {
            previewImage(this);
        });
    }
    
    // Form submissions
    const productForm = document.getElementById('product-form');
    if (productForm) {
        productForm.addEventListener('submit', function(e) {
            if (!validateProductForm()) {
                e.preventDefault();
            }
        });
    }
    
    // Auto-hide alerts
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
});

// Animation styles
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
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
document.head.appendChild(style);