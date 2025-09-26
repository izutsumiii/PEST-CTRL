// Enhanced addToCart function with stock validation
function addToCart(productId, quantity = 1) {
    // Show loading state
    const button = document.querySelector(`button[data-product-id="${productId}"]`);
    const originalText = button.textContent;
    button.textContent = 'Adding...';
    button.disabled = true;
    
    // Make AJAX request to add item to cart
    fetch('ajax/add-to-cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            product_id: productId,
            quantity: quantity
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success notification
            showNotification(data.message, 'success');
            
            // Update cart count if you have a cart counter in header
            if (typeof updateCartCount === 'function') {
                updateCartCount(data.cartCount);
            }
            
            // Temporarily change button text
            button.textContent = '✓ Added';
            setTimeout(() => {
                button.textContent = originalText;
                button.disabled = false;
            }, 2000);
        } else {
            // Show error notification
            showNotification(data.message || 'Error adding to cart', 'error');
            button.textContent = originalText;
            button.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error adding to cart', 'error');
        button.textContent = originalText;
        button.disabled = false;
    });
}
// Update cart count in header (keep existing)
function updateCartCount(count) {
    const cartCounter = document.querySelector('.cart-count');
    if (cartCounter) {
        cartCounter.textContent = count;
        
        // Add a little animation to draw attention
        cartCounter.style.transform = 'scale(1.2)';
        setTimeout(() => {
            cartCounter.style.transform = 'scale(1)';
        }, 200);
    }
}
function loadCartCount() {
    fetch('ajax/cart-session.php?action=get_count')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateCartCount(data.count);
        }
    })
    .catch(error => {
        console.error('Error loading cart count:', error);
    });
}
document.addEventListener('DOMContentLoaded', function() {
    loadCartCount();
});
// Updated Buy now function - creates separate buy now session
function buyNow(productId, quantity = 1) {
    // Show loading state
    const button = document.querySelector(`button[onclick*="buyNow(${productId})"]`);
    let originalText = 'Buy Now';
    if (button) {
        originalText = button.textContent;
        button.textContent = 'Processing...';
        button.disabled = true;
    }
    
    // Make AJAX request to prepare buy now item
    fetch('ajax/buy-now.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            product_id: productId,
            quantity: quantity
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Redirect to checkout with buy now parameter
            window.location.href = 'checkout.php?buy_now=1';
        } else {
            // Show error notification
            showNotification(data.message || 'Error processing request', 'error');
            if (button) {
                button.textContent = originalText;
                button.disabled = false;
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error processing request', 'error');
        if (button) {
            button.textContent = originalText;
            button.disabled = false;
        }
    });
}
function addToCartAndCheckout(productId, quantity = 1) {
    fetch('ajax/add-to-cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            product_id: productId,
            quantity: quantity
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Redirect to regular cart checkout
            window.location.href = 'checkout.php';
        } else {
            showNotification(data.message || 'Error processing request', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error processing request', 'error');
    });
}
// Enhanced updateCartQuantity function with stock validation
function updateCartQuantity(productId, quantity) {
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'cart-ajax.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    // Update cart total
                    const cartTotal = document.getElementById('cart-total');
                    if (cartTotal) {
                        cartTotal.textContent = '$' + response.total;
                    }
                    
                    // Update stock message if exists
                    const stockMessage = document.getElementById('stock-message-' + productId);
                    if (stockMessage) {
                        updateStockMessage(productId, quantity);
                    }
                    
                    updateCartCount();
                    showNotification(response.message, 'success');
                } else {
                    showNotification(response.message, 'error');
                    // Reload the page to sync with server if there's a stock issue
                    if (response.message.includes('available')) {
                        setTimeout(() => { location.reload(); }, 2000);
                    }
                }
            } catch (e) {
                console.error('Error updating cart quantity', e);
                showNotification('Error updating cart. Please try again.', 'error');
            }
        }
    };
    xhr.send('action=update_quantity&product_id=' + productId + '&quantity=' + quantity);
}

// Enhanced removeCartItem function
function removeCartItem(productId) {
    if (!confirm('Are you sure you want to remove this item from your cart?')) {
        return;
    }
    
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'cart-ajax.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    // Update UI
                    const cartTotal = document.getElementById('cart-total');
                    if (cartTotal) {
                        cartTotal.textContent = '$' + response.total;
                    }
                    
                    const itemRow = document.getElementById('cart-item-' + productId);
                    if (itemRow) {
                        itemRow.remove();
                    }
                    
                    updateCartCount();
                    showNotification(response.message, 'success');
                    
                    // Check if cart is empty
                    if (response.count === 0) {
                        document.querySelector('.cart-table').innerHTML = '<p>Your cart is empty.</p>';
                    }
                } else {
                    showNotification(response.message, 'error');
                }
            } catch (e) {
                console.error('Error removing cart item', e);
                showNotification('Error removing item. Please try again.', 'error');
            }
        }
    };
    xhr.send('action=remove_item&product_id=' + productId);
}

// Function to validate cart before checkout
function validateCartBeforeCheckout() {
    const xhr = new XMLHttpRequest();
    xhr.open('GET', 'cart-ajax.php?action=validate_cart', true);
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                const response = JSON.parse(xhr.responseText);
                if (!response.success) {
                    showNotification(response.message, 'error');
                    // Prevent form submission if cart is invalid
                    const checkoutForm = document.getElementById('checkout-form');
                    if (checkoutForm) {
                        checkoutForm.addEventListener('submit', function(e) {
                            e.preventDefault();
                            showNotification('Please fix cart issues before checkout.', 'error');
                        });
                    }
                }
            } catch (e) {
                console.error('Error validating cart', e);
            }
        }
    };
    xhr.send();
}

// Function to show notifications
function showNotification(message, type) {
    // Remove existing notifications
    const existingNotifications = document.querySelectorAll('.notification');
    existingNotifications.forEach(notification => notification.remove());
    
    // Create new notification
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px;
        border-radius: 5px;
        color: white;
        z-index: 1000;
        max-width: 300px;
    `;
    
    if (type === 'success') {
        notification.style.backgroundColor = '#4CAF50';
    } else {
        notification.style.backgroundColor = '#F44336';
    }
    
    document.body.appendChild(notification);
    
    // Remove notification after 3 seconds
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Function to update stock message for a product
function updateStockMessage(productId, quantity) {
    const stockMessage = document.getElementById('stock-message-' + productId);
    if (!stockMessage) return;
    
    const maxStock = parseInt(stockMessage.getAttribute('data-max-stock'));
    if (quantity > maxStock) {
        stockMessage.style.display = 'block';
        stockMessage.textContent = `Only ${maxStock} available. You cannot add more than this.`;
        stockMessage.style.color = '#F44336';
    } else {
        stockMessage.style.display = 'none';
    }
}

/* ============================
   Rating Filter Functionality
   ============================ */

// Toggle rating filter via checkbox
function toggleRatingFilter(checkbox) {
    const form = checkbox.closest('form');
    const minRatingSelect = form.querySelector('select[name="min_rating_select"]');
    
    if (checkbox.checked) {
        minRatingSelect.value = checkbox.value;
    } else {
        minRatingSelect.value = "0";
    }
    
    form.submit();
}

// Update rating filter via select
function updateRatingFilter(select) {
    const form = select.closest('form');
    const checkboxes = form.querySelectorAll('input[name="min_rating"]');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = false;
    });
    
    if (select.value > 0) {
        const checkbox = form.querySelector(`input[name="min_rating"][value="${select.value}"]`);
        if (checkbox) {
            checkbox.checked = true;
        }
    }
    
    form.submit();
}

// Initialize rating filters
function initRatingFilters() {
    const ratingCheckboxes = document.querySelectorAll('input[name="min_rating"]');
    const ratingSelect = document.querySelector('select[name="min_rating_select"]');
    
    ratingCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            if (this.checked) {
                ratingSelect.value = this.value;
            }
        });
    });
    
    if (ratingSelect) {
        ratingSelect.addEventListener('change', function() {
            ratingCheckboxes.forEach(checkbox => {
                checkbox.checked = (checkbox.value === this.value);
            });
        });
    }
}

/* ============================
   Document Ready Initializers
   ============================ */
document.addEventListener('DOMContentLoaded', function() {
    updateCartCount();
    setupSearchAutocomplete();
    
    // Cart: Add to Cart buttons
    const addToCartButtons = document.querySelectorAll('.add-to-cart-btn');
    addToCartButtons.forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-product-id');
            const quantityInput = document.getElementById('quantity-' + productId);
            const quantity = quantityInput ? parseInt(quantityInput.value) : 1;
            addToCart(productId, quantity);
        });
    });
    
    // Cart: Quantity inputs
    const quantityInputs = document.querySelectorAll('.cart-quantity');
    quantityInputs.forEach(input => {
        input.addEventListener('change', function() {
            const productId = this.getAttribute('data-product-id');
            const quantity = this.value;
            updateCartQuantity(productId, quantity);
        });
    });
    
    // Cart: Remove buttons
    const removeButtons = document.querySelectorAll('.remove-from-cart');
    removeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-product-id');
            removeCartItem(productId);
        });
    });
    
    // Validate cart before checkout
    if (document.getElementById('checkout-form')) {
        validateCartBeforeCheckout();
    }
    
    // Init rating filters
    initRatingFilters();
    
    // Clear filters button
    const clearFiltersBtn = document.querySelector('.clear-filters');
    if (clearFiltersBtn) {
        clearFiltersBtn.addEventListener('click', function(e) {
            e.preventDefault();
            window.location.href = 'products.php';
        });
    }
});
