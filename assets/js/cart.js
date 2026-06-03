// Cart-specific JavaScript functions

// Update cart item quantity
async function updateCartItemQuantity(itemId, newQuantity) {
    try {
        const response = await fetch('/phelyz-store/api/update-cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                item_id: itemId,
                quantity: newQuantity
            })
        });

        const data = await response.json();

        if (data.success) {
            location.reload(); // Reload to update cart display
        } else {
            showNotification(data.message || 'Failed to update cart', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('An error occurred', 'error');
    }
}

// Remove item from cart
async function removeCartItem(itemId) {
    if (!confirm('Remove this item from cart?')) {
        return;
    }

    try {
        const response = await fetch('/phelyz-store/api/update-cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                item_id: itemId,
                quantity: 0
            })
        });

        const data = await response.json();

        if (data.success) {
            showNotification('Item removed from cart', 'success');
            location.reload();
        } else {
            showNotification('Failed to remove item', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('An error occurred', 'error');
    }
}

// Clear entire cart
async function clearEntireCart() {
    if (!confirm('Are you sure you want to clear your entire cart?')) {
        return;
    }

    try {
        const response = await fetch('/phelyz-store/api/update-cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'clear'
            })
        });

        const data = await response.json();

        if (data.success) {
            showNotification('Cart cleared', 'success');
            location.reload();
        } else {
            showNotification('Failed to clear cart', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('An error occurred', 'error');
    }
}

// Update cart totals
function updateCartTotals() {
    let subtotal = 0;
    
    document.querySelectorAll('.cart-item').forEach(item => {
        const price = parseFloat(item.dataset.price);
        const quantity = parseInt(item.querySelector('input[type="number"]').value);
        subtotal += price * quantity;
    });

    const tax = subtotal * 0.05; // 5% tax
    const shipping = subtotal >= 50000 ? 0 : 2500;
    const total = subtotal + tax + shipping;

    // Update display
    document.querySelector('.subtotal-amount').textContent = formatPrice(subtotal);
    document.querySelector('.tax-amount').textContent = formatPrice(tax);
    document.querySelector('.shipping-amount').textContent = shipping === 0 ? 'FREE' : formatPrice(shipping);
    document.querySelector('.total-amount').textContent = formatPrice(total);
}

// Format price
function formatPrice(amount) {
    return '₦' + amount.toLocaleString('en-NG', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

// Quantity controls
document.querySelectorAll('.quantity-control').forEach(control => {
    const decreaseBtn = control.querySelector('.decrease');
    const increaseBtn = control.querySelector('.increase');
    const input = control.querySelector('input');

    if (decreaseBtn) {
        decreaseBtn.addEventListener('click', function() {
            const currentValue = parseInt(input.value);
            if (currentValue > 1) {
                input.value = currentValue - 1;
                input.dispatchEvent(new Event('change'));
            }
        });
    }

    if (increaseBtn) {
        increaseBtn.addEventListener('click', function() {
            const currentValue = parseInt(input.value);
            const maxValue = parseInt(input.max) || Infinity;
            if (currentValue < maxValue) {
                input.value = currentValue + 1;
                input.dispatchEvent(new Event('change'));
            }
        });
    }

    if (input) {
        input.addEventListener('change', function() {
            const itemId = this.closest('.cart-item').dataset.itemId;
            const newQuantity = parseInt(this.value);
            updateCartItemQuantity(itemId, newQuantity);
        });
    }
});

// Apply coupon code
async function applyCoupon() {
    const couponInput = document.querySelector('.coupon-input');
    if (!couponInput) return;

    const code = couponInput.value.trim();
    if (!code) {
        showNotification('Please enter a coupon code', 'warning');
        return;
    }

    try {
        const response = await fetch('/phelyz-store/api/apply-coupon.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                code: code
            })
        });

        const data = await response.json();

        if (data.success) {
            showNotification('Coupon applied successfully!', 'success');
            location.reload();
        } else {
            showNotification(data.message || 'Invalid coupon code', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('An error occurred', 'error');
    }
}

// Save for later
async function saveForLater(itemId) {
    try {
        const response = await fetch('/phelyz-store/api/save-for-later.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                item_id: itemId
            })
        });

        const data = await response.json();

        if (data.success) {
            showNotification('Item saved for later', 'success');
            location.reload();
        } else {
            showNotification('Failed to save item', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('An error occurred', 'error');
    }
}

// Initialize cart page
document.addEventListener('DOMContentLoaded', function() {
    // Update totals on page load
    if (document.querySelector('.cart-page')) {
        updateCartTotals();
    }

    // Coupon apply button
    const applyBtn = document.querySelector('.apply-coupon-btn');
    if (applyBtn) {
        applyBtn.addEventListener('click', applyCoupon);
    }
});