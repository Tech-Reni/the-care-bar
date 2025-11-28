/**
 * The Care Bar - Cart Management System
 * Handles all cart operations: add, remove, update, display
 */

// Get BASE_URL from document (needs to be set in HTML)
const BASE_URL = document.querySelector('html').getAttribute('data-base-url') || '/TheCareBar/';

// Cart state management
let cart = {
    items: {},
    total: 0,
    subtotal: 0,

    /**
     * Add product to cart
     */
    async add(productId, quantity = 1) {
        try {
            const formData = new FormData();
            formData.append('action', 'add');
            formData.append('product_id', productId);
            formData.append('quantity', quantity);

            const response = await fetch(BASE_URL + 'api/cart.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                this.updateCount(data.cart_count);
                showSuccess('Added to Cart!', 'Product has been added to your cart.');
                this.loadCart();
            } else {
                // Show detailed error including debug info
                let errorMsg = data.message || 'Failed to add product';
                if (data.debug) {
                    errorMsg += '\n\nDebug Info:';
                    errorMsg += '\nException: ' + data.debug.exception_message;
                    errorMsg += '\nFile: ' + data.debug.exception_file + ':' + data.debug.exception_line;
                    if (data.debug.db_error) {
                        errorMsg += '\nDB Error: ' + data.debug.db_error;
                    }
                }
                console.error('Cart add error:', data);
                showError('Error', errorMsg);
            }
        } catch (error) {
            console.error('Cart error:', error);
            showError('Error', 'Failed to add product to cart');
        }
    },

    /**
     * Remove product from cart
     */
    async remove(productId) {
        try {
            const formData = new FormData();
            formData.append('action', 'remove');
            formData.append('product_id', productId);

            const response = await fetch(BASE_URL + 'api/cart.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                this.updateCount(data.cart_count);
                this.loadCart();
                showSuccess('Removed', 'Product removed from cart');
            } else {
                let errorMsg = data.message || 'Failed to remove product';
                if (data.debug) {
                    errorMsg += '\n\nDebug: ' + data.debug.exception_message;
                    if (data.debug.db_error) errorMsg += '\nDB: ' + data.debug.db_error;
                }
                console.error('Cart remove error:', data);
                showError('Error', errorMsg);
            }
        } catch (error) {
            console.error('Cart error:', error);
        }
    },

    /**
     * Update product quantity
     */
    async updateQuantity(productId, quantity) {
        try {
            const formData = new FormData();
            formData.append('action', 'update');
            formData.append('product_id', productId);
            formData.append('quantity', quantity);

            const response = await fetch(BASE_URL + 'api/cart.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                this.updateCount(data.cart_count);
                this.loadCart();
            } else {
                let errorMsg = data.message || 'Failed to update quantity';
                if (data.debug) {
                    errorMsg += '\n\nDebug: ' + data.debug.exception_message;
                    if (data.debug.db_error) errorMsg += '\nDB: ' + data.debug.db_error;
                }
                console.error('Cart update error:', data);
                showError('Error', errorMsg);
            }
        } catch (error) {
            console.error('Cart error:', error);
        }
    },

    /**
     * Load cart items from server
     */
    async loadCart() {
        try {
            const formData = new FormData();
            formData.append('action', 'get');

            const response = await fetch(BASE_URL + 'api/cart.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                this.items = data.cart;
                this.subtotal = data.subtotal;
                this.total = data.total;
                this.render();
            }
        } catch (error) {
            console.error('Failed to load cart:', error);
        }
    },

    /**
     * Clear entire cart
     */
    async clear() {
        showConfirm('Clear Cart?', 'Are you sure you want to clear your cart?', async () => {
            try {
                const formData = new FormData();
                formData.append('action', 'clear');

                const response = await fetch(BASE_URL + 'api/cart.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    this.updateCount(0);
                    this.items = {};
                    this.subtotal = 0;
                    this.total = 0;
                    this.render();
                    showSuccess('Cart Cleared', 'Your cart is now empty');
                }
            } catch (error) {
                console.error('Cart error:', error);
            }
        });
    },

    /**
     * Render cart UI
     */
    render() {
        const cartPanel = document.getElementById('cart-panel');
        const cartItems = document.getElementById('cart-items');
        const cartTotal = document.getElementById('cart-total');

        if (!cartItems) return;

        if (Object.keys(this.items).length === 0) {
            cartItems.innerHTML = '<p class="empty-cart">Your cart is empty</p>';
            if (cartPanel) cartPanel.classList.add('hidden');
            return;
        }

        let html = '';
        for (const [productId, item] of Object.entries(this.items)) {
            html += `
                <div class="cart-item" data-product-id="${productId}">
                    <img src="uploads/${item.image}" alt="${item.name}">
                    <div class="cart-item-info">
                        <h4>${item.name}</h4>
                        <p class="price">₦${parseFloat(item.price).toLocaleString()}</p>
                    </div>
                    <div class="cart-item-controls">
                        <button class="qty-btn" onclick="cart.updateQuantity(${productId}, ${item.quantity - 1})" title="Decrease">
                            <i class="ri-subtract-line"></i>
                        </button>
                        <span class="qty">${item.quantity}</span>
                        <button class="qty-btn" onclick="cart.updateQuantity(${productId}, ${item.quantity + 1})" title="Increase">
                            <i class="ri-add-line"></i>
                        </button>
                    </div>
                    <p class="item-total">₦${(item.price * item.quantity).toLocaleString()}</p>
                    <button class="remove-btn" onclick="cart.remove(${productId})" title="Remove">
                        <i class="ri-close-line"></i>
                    </button>
                </div>
            `;
        }

        cartItems.innerHTML = html;

        if (cartTotal) {
            cartTotal.textContent = '₦' + this.subtotal.toLocaleString();
        }
    },

    /**
     * Update cart count display
     */
    updateCount(count) {
        document.querySelectorAll('#cartCount, .mobile-cart-count').forEach(el => {
            el.textContent = count;
        });
    }
};

/**
 * Initialize cart on page load
 */
document.addEventListener('DOMContentLoaded', () => {
    // Load cart data
    cart.loadCart();

    // Add to cart buttons
    document.querySelectorAll('.add-to-cart-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const productId = btn.getAttribute('data-product-id');
            const quantity = parseInt(document.getElementById(`quantity-${productId}`)?.value || 1);
            cart.add(parseInt(productId), quantity);
        });
    });

    // Cart panel controls
    const openCartBtns = document.querySelectorAll('.open-cart');
    const closeCartBtn = document.getElementById('close-cart-3');
    const cartPanel = document.getElementById('cart-panel');
    const clearCartBtn = document.getElementById('clear-cart');
    const checkoutBtn = document.getElementById('checkout-btn');

    openCartBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            if (cartPanel) {
                cartPanel.classList.remove('hidden');
            }
        });
    });

    if (closeCartBtn) {
        closeCartBtn.addEventListener('click', () => {
            if (cartPanel) {
                cartPanel.classList.add('hidden');
            }
        });
    }

    if (clearCartBtn) {
        clearCartBtn.addEventListener('click', () => {
            cart.clear();
        });
    }

    if (checkoutBtn) {
        checkoutBtn.addEventListener('click', () => {
            if (Object.keys(cart.items).length === 0) {
                showError('Empty Cart', 'Please add items to your cart before checkout');
            } else {
                window.location.href = BASE_URL + 'checkout.php';
            }
        });
    }
});
