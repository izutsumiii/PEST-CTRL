
/**
 * Order Management JavaScript
 * Handles real-time updates, grace periods, and order cancellations
 */

class OrderManager {
    constructor() {
        this.activeTimers = new Map();
        this.refreshInterval = null;
        this.gracePeriodMinutes = 5;
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.startGracePeriodTimers();
        this.setupPeriodicRefresh();
    }

    setupEventListeners() {
        // Cancel order modal events
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('cancel-order-btn')) {
                const orderId = e.target.dataset.orderId;
                const orderNumber = e.target.dataset.orderNumber;
                this.showCancelModal(orderId, orderNumber);
            }

            if (e.target.classList.contains('close-modal') || e.target.classList.contains('cancel-modal-backdrop')) {
                this.closeCancelModal();
            }
        });

        // Keyboard events
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeCancelModal();
            }
        });

        // Form submissions
        document.addEventListener('submit', (e) => {
            if (e.target.classList.contains('cancel-order-form')) {
                e.preventDefault();
                this.handleOrderCancellation(e.target);
            }
        });

        // Status update confirmations for sellers
        document.addEventListener('change', (e) => {
            if (e.target.classList.contains('status-select')) {
                if (e.target.value === 'processing') {
                    const orderId = e.target.form.querySelector('input[name="order_id"]').value;
                    this.confirmProcessOrder(e.target, orderId);
                }
            }
        });
    }

    startGracePeriodTimers() {
        const timerElements = document.querySelectorAll('[data-countdown-timer]');
        
        timerElements.forEach(element => {
            const orderId = element.dataset.orderId;
            const totalSeconds = parseInt(element.dataset.totalSeconds);
            
            if (totalSeconds > 0) {
                this.startCountdown(orderId, totalSeconds, element);
            }
        });
    }

    startCountdown(orderId, totalSeconds, element) {
        // Clear existing timer if any
        if (this.activeTimers.has(orderId)) {
            clearInterval(this.activeTimers.get(orderId));
        }

        let remaining = totalSeconds;
        
        const interval = setInterval(() => {
            if (remaining <= 0) {
                clearInterval(interval);
                this.activeTimers.delete(orderId);
                this.handleGracePeriodExpired(orderId);
                return;
            }

            const minutes = Math.floor(remaining / 60);
            const seconds = remaining % 60;
            
            // Update display
            if (element) {
                element.textContent = `${minutes}m ${seconds}s remaining`;
            }

            // Update all elements with this order ID
            this.updateAllTimerElements(orderId, minutes, seconds);

            remaining--;
        }, 1000);

        this.activeTimers.set(orderId, interval);
    }

    updateAllTimerElements(orderId, minutes, seconds) {
        const elements = document.querySelectorAll(`[data-order-id="${orderId}"] .countdown-timer`);
        elements.forEach(el => {
            el.textContent = `${minutes}m ${seconds}s remaining`;
        });

        // Update cancel buttons
        const cancelButtons = document.querySelectorAll(`[data-order-id="${orderId}"] .cancel-order-btn`);
        if (minutes === 0 && seconds <= 10) {
            cancelButtons.forEach(btn => {
                btn.style.animation = 'pulse 1s infinite';
                btn.textContent = 'Cancel Now!';
            });
        }
    }

    handleGracePeriodExpired(orderId) {
        // Disable cancel buttons
        const cancelButtons = document.querySelectorAll(`[data-order-id="${orderId}"] .cancel-order-btn`);
        cancelButtons.forEach(btn => {
            btn.disabled = true;
            btn.textContent = 'Cannot Cancel';
            btn.title = 'Cancellation window expired';
        });

        // Update timer displays
        const timerElements = document.querySelectorAll(`[data-order-id="${orderId}"] .countdown-timer`);
        timerElements.forEach(el => {
            el.textContent = 'Grace period expired';
            el.style.color = '#6c757d';
        });

        // Enable seller processing if applicable
        const sellerSelects = document.querySelectorAll(`[data-order-id="${orderId}"] .status-select`);
        sellerSelects.forEach(select => {
            if (select.disabled) {
                select.disabled = false;
                const processingOption = select.querySelector('option[value="processing"]');
                if (processingOption) {
                    processingOption.textContent = 'Process Order (Auto-Confirm)';
                }
            }
        });

        // Show notification
        this.showNotification(`Order #${orderId.toString().padStart(6, '0')} can now be processed by seller`, 'info');
    }

    setupPeriodicRefresh() {
        // Refresh page every 5 minutes to sync with server
        this.refreshInterval = setInterval(() => {
            this.checkForUpdates();
        }, 5 * 60 * 1000); // 5 minutes
    }

    async checkForUpdates() {
        try {
            const orderIds = Array.from(this.activeTimers.keys()).join(',');
            if (!orderIds) return;

            const response = await fetch(`ajax/order_status.php?action=batch_check&order_ids=${orderIds}`);
            const data = await response.json();

            if (data.orders) {
                data.orders.forEach(order => {
                    if (!order.within_grace_period) {
                        this.handleGracePeriodExpired(order.order_id);
                    }
                });
            }
        } catch (error) {
            console.error('Error checking for updates:', error);
        }
    }

    showCancelModal(orderId, orderNumber) {
        const modal = document.getElementById('cancelModal');
        if (!modal) {
            // Create modal dynamically if it doesn't exist
            this.createCancelModal();
        }

        document.getElementById('cancelOrderId').value = orderId;
        document.getElementById('cancelOrderNumber').textContent = orderNumber;
        document.getElementById('cancelModal').style.display = 'block';
        document.body.style.overflow = 'hidden'; // Prevent scrolling
    }

    closeCancelModal() {
        const modal = document.getElementById('cancelModal');
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = ''; // Restore scrolling
        }
    }

    createCancelModal() {
        const modalHTML = `
            <div id="cancelModal" class="cancel-modal">
                <div class="cancel-modal-content">
                    <span class="close-modal">&times;</span>
                    <h3>⚠️ Cancel Order</h3>
                    <p>Are you sure you want to cancel order <strong id="cancelOrderNumber">#000000</strong>?</p>
                    <p><strong>Please note:</strong></p>
                    <ul style="margin: 10px 0 20px 20px; color: #666;">
                        <li>This action cannot be undone</li>
                        <li>Product stock will be restored</li>
                        <li>Any payment made will be refunded within 3-5 business days</li>
                        <li>You can only cancel orders within 10 minutes of placing them</li>
                    </ul>
                    
                    <form method="POST" action="" class="cancel-order-form" style="margin: 0;">
                        <input type="hidden" name="order_id" id="cancelOrderId" value="">
                        <input type="hidden" name="cancel_order" value="1">
                        <div class="cancel-modal-buttons">
                            <button type="button" class="btn btn-primary close-modal">Keep Order</button>
                            <button type="submit" class="btn btn-danger">Yes, Cancel Order</button>
                        </div>
                    </form>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHTML);
    }

    async handleOrderCancellation(form) {
        const formData = new FormData(form);
        const submitButton = form.querySelector('button[type="submit"]');
        const originalText = submitButton.textContent;

        try {
            submitButton.disabled = true;
            submitButton.textContent = 'Cancelling...';

            const response = await fetch(window.location.href, {
                method: 'POST',
                body: formData
            });

            if (response.ok) {
                // Reload page to show cancellation result
                window.location.reload();
            } else {
                throw new Error('Network response was not ok');
            }
        } catch (error) {
            console.error('Error cancelling order:', error);
            this.showNotification('Error cancelling order. Please try again.', 'error');
            submitButton.disabled = false;
            submitButton.textContent = originalText;
        }
    }

    confirmProcessOrder(selectElement, orderId) {
        const confirmation = confirm(
            `Are you sure you want to process Order #${orderId.toString().padStart(6, '0')}?\n\n` +
            'This will:\n' +
            '• Confirm the order automatically\n' +
            '• Send confirmation email to customer\n' +
            '• Prevent customer from cancelling\n' +
            '• Move order to processing status'
        );

        if (!confirmation) {
            selectElement.selectedIndex = 0; // Reset to default option
            return false;
        }

        // Show processing indicator
        selectElement.disabled = true;
        const processingOption = selectElement.querySelector('option[value="processing"]');
        if (processingOption) {
            processingOption.textContent = 'Processing...';
        }

        return true;
    }

    showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <span class="notification-message">${message}</span>
                <button class="notification-close">&times;</button>
            </div>
        `;

        // Add styles
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
            background: ${type === 'error' ? '#f8d7da' : type === 'success' ? '#d4edda' : '#d1ecf1'};
            color: ${type === 'error' ? '#721c24' : type === 'success' ? '#155724' : '#0c5460'};
            border: 1px solid ${type === 'error' ? '#f5c6cb' : type === 'success' ? '#c3e6cb' : '#bee5eb'};
            border-radius: 8px;
            padding: 15px;
            max-width: 400px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            animation: slideInRight 0.3s ease-out;
        `;

        document.body.appendChild(notification);

        // Auto remove after 5 seconds
        setTimeout(() => {
            this.removeNotification(notification);
        }, 5000);

        // Close button event
        notification.querySelector('.notification-close').addEventListener('click', () => {
            this.removeNotification(notification);
        });
    }

    removeNotification(notification) {
        notification.style.animation = 'slideOutRight 0.3s ease-in';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }

    // Real-time order status checking
    async checkOrderStatus(orderId) {
        try {
            const response = await fetch(`ajax/order_status.php?action=check_order_status&order_id=${orderId}`);
            const data = await response.json();
            return data;
        } catch (error) {
            console.error('Error checking order status:', error);
            return null;
        }
    }

    // Get all cancellable orders for current user
    async getCancellableOrders() {
        try {
            const response = await fetch('ajax/order_status.php?action=get_cancellable_orders');
            const data = await response.json();
            return data.cancellable_orders || [];
        } catch (error) {
            console.error('Error fetching cancellable orders:', error);
            return [];
        }
    }

    // Cleanup when page unloads
    cleanup() {
        // Clear all active timers
        this.activeTimers.forEach((interval, orderId) => {
            clearInterval(interval);
        });
        this.activeTimers.clear();

        // Clear refresh interval
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
        }
    }
}

// CSS animations for notifications
const notificationStyles = `
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

    .notification-content {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }

    .notification-close {
        background: none;
        border: none;
        font-size: 18px;
        font-weight: bold;
        cursor: pointer;
        padding: 0;
        margin-left: 10px;
        opacity: 0.7;
    }

    .notification-close:hover {
        opacity: 1;
    }
`;

// Add styles to page
const styleSheet = document.createElement('style');
styleSheet.textContent = notificationStyles;
document.head.appendChild(styleSheet);

// Initialize when DOM is ready
let orderManager;

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        orderManager = new OrderManager();
    });
} else {
    orderManager = new OrderManager();
}

// Cleanup on page unload
window.addEventListener('beforeunload', () => {
    if (orderManager) {
        orderManager.cleanup();
    }
});

// Export for use in other scripts
window.OrderManager = OrderManager;