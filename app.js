const API_BASE = 'api';


// ===== Toast Notification System (alert() এর বদলে) =====
function createToastContainer() {
    let c = document.getElementById('toast-container');
    if (!c) {
        c = document.createElement('div');
        c.id = 'toast-container';
        c.className = 'toast-container';
        document.body.appendChild(c);
    }
    return c;
}

function showToast(message, type = 'success', duration = 3500) {
    const container = createToastContainer();
    const toast = document.createElement('div');
    const icons = { success: '✅', error: '❌', warning: '⚠️' };
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `<span>${icons[type] || '✅'}</span><span>${message}</span>`;
    container.appendChild(toast);
    setTimeout(() => { toast.style.opacity = '0'; toast.style.transform = 'translateX(100%)'; toast.style.transition = '0.3s'; setTimeout(() => toast.remove(), 300); }, duration);
}

// ===== API Helper =====
async function apiCall(endpoint, options = {}) {
    try {
        const response = await fetch(`${API_BASE}/${endpoint}`, {
            credentials: 'include', // SESSION FIX — এটা ছিল না, এটাই মূল bug ছিল
            headers: { 'Content-Type': 'application/json' },
            ...options
        });
        const data = await response.json();
        return data;
    } catch (error) {
        console.error('API Error:', error);
        return { success: false, message: 'সার্ভারের সাথে সংযোগ হচ্ছে না' };
    }
}

// ===== Auth =====
async function login(email, password) {
    const result = await apiCall('auth.php?action=login', {
        method: 'POST',
        body: JSON.stringify({ email, password })
    });
    if (result.success) {
        sessionStorage.setItem('current_user', JSON.stringify(result.data.user));
    }
    return result;
}

async function register(userData) {
    const result = await apiCall('auth.php?action=register', {
        method: 'POST',
        body: JSON.stringify(userData)
    });
    if (result.success) {
        sessionStorage.setItem('current_user', JSON.stringify(result.data.user));
    }
    return result;
}

async function logout() {
    await apiCall('auth.php?action=logout', { method: 'POST' });
    sessionStorage.removeItem('current_user');
    window.location.href = 'index.html';
}

function getCurrentUser() {
    const u = sessionStorage.getItem('current_user');
    return u ? JSON.parse(u) : null;
}

async function checkAuthAndSync() {
    const result = await apiCall('auth.php?action=me');
    if (result.success) {
        sessionStorage.setItem('current_user', JSON.stringify(result.data.user));
        return result.data.user;
    } else {
        sessionStorage.removeItem('current_user');
        return null;
    }
}

function checkAuth(requiredRole = null) {
    const user = getCurrentUser();
    if (!user) { window.location.href = 'login.html'; return false; }
    if (requiredRole && user.role !== requiredRole && user.role !== 'admin') {
        showToast('আপনার এই পেজে প্রবেশের অনুমতি নেই', 'error');
        setTimeout(() => window.location.href = 'index.html', 1500);
        return false;
    }
    return user;
}

// ===== Products =====
async function getProducts(params = {}) {
    const query = new URLSearchParams(params).toString();
    const result = await apiCall(`products.php?action=list&${query}`);
    return result.success ? result.data.products : [];
}

async function getProductDetail(id) {
    const result = await apiCall(`products.php?action=detail&id=${id}`);
    return result.success ? result.data : null;
}

async function addProduct(productData) {
    return await apiCall('products.php?action=add', { method: 'POST', body: JSON.stringify(productData) });
}

async function deleteProduct(productId) {
    return await apiCall('products.php?action=delete', { method: 'POST', body: JSON.stringify({ id: productId }) });
}

async function getFarmerProducts() {
    const result = await apiCall('products.php?action=farmer_products');
    return result.success ? result.data.products : [];
}

// ===== Image Upload =====
async function uploadProductImage(file) {
    const formData = new FormData();
    formData.append('image', file);
    try {
        const response = await fetch(`${API_BASE}/upload.php`, {
            credentials: 'include',
            method: 'POST',
            body: formData
        });
        return await response.json();
    } catch (e) {
        return { success: false, message: 'Upload ব্যর্থ হয়েছে' };
    }
}

// ===== Cart =====
async function getCart() {
    const user = getCurrentUser();
    if (!user) return { items: [], grand_total: 0, count: 0 };
    const result = await apiCall('cart.php?action=get');
    return result.success ? result.data : { items: [], grand_total: 0, count: 0 };
}

async function addToCart(productId, quantity = 1) {
    return await apiCall('cart.php?action=add', { method: 'POST', body: JSON.stringify({ product_id: productId, quantity }) });
}

async function updateCartQuantity(productId, quantity) {
    return await apiCall('cart.php?action=update', { method: 'POST', body: JSON.stringify({ product_id: productId, quantity }) });
}

async function removeFromCart(productId) {
    return await apiCall('cart.php?action=remove', { method: 'POST', body: JSON.stringify({ product_id: productId }) });
}

async function clearCart() {
    return await apiCall('cart.php?action=clear', { method: 'POST' });
}

// ===== Orders =====
async function createOrder(orderData) {
    return await apiCall('orders.php?action=create', { method: 'POST', body: JSON.stringify(orderData) });
}

async function getMyOrders() {
    const result = await apiCall('orders.php?action=my_orders');
    return result.success ? result.data.orders : [];
}

async function getAllOrders(status = '') {
    const result = await apiCall(`orders.php?action=all_orders&status=${status}`);
    return result.success ? result.data.orders : [];
}

async function updateOrderStatus(orderId, status) {
    return await apiCall('orders.php?action=update_status', { method: 'POST', body: JSON.stringify({ order_id: orderId, status }) });
}

async function getFarmerOrders() {
    const result = await apiCall('orders.php?action=farmer_orders');
    return result.success ? result.data.orders : [];
}

// ===== Reviews =====
async function addReview(productId, rating, comment) {
    return await apiCall('reviews.php?action=add', { method: 'POST', body: JSON.stringify({ product_id: productId, rating, comment }) });
}

// ===== Admin =====
async function getAdminStats() {
    const result = await apiCall('admin.php?action=stats');
    return result.success ? result.data : null;
}

async function getAdminUsers() {
    const result = await apiCall('admin.php?action=users');
    return result.success ? result.data.users : [];
}

async function adminDeleteUser(userId) {
    return await apiCall('admin.php?action=delete_user', { method: 'POST', body: JSON.stringify({ id: userId }) });
}

async function adminDeleteProduct(productId) {
    return await apiCall('admin.php?action=delete_product', { method: 'POST', body: JSON.stringify({ id: productId }) });
}

// ===== Utilities =====
function formatPrice(price) { return `৳${parseFloat(price).toLocaleString('bn-BD')}`; }

function formatDate(dateString) {
    if (!dateString) return '';
    return new Date(dateString).toLocaleDateString('bn-BD', { year: 'numeric', month: 'long', day: 'numeric' });
}

function getFreshnessBadge(daysOld) {
    if (daysOld <= 2) return { text: '🌿 একদম তাজা', class: 'badge-success' };
    if (daysOld <= 4) return { text: '✅ তাজা', class: 'badge-success' };
    if (daysOld <= 6) return { text: '⏰ বিক্রয়যোগ্য', class: 'badge-warning' };
    return { text: '❌ মেয়াদ শেষ', class: 'badge-danger' };
}

function getStatusBadge(status) {
    const map = {
        'pending':    { text: 'অপেক্ষমাণ', class: 'badge-warning' },
        'processing': { text: 'প্রক্রিয়াধীন', class: 'badge-info' },
        'shipped':    { text: 'পাঠানো হয়েছে', class: 'badge-info' },
        'delivered':  { text: 'ডেলিভারি সম্পন্ন', class: 'badge-success' },
        'cancelled':  { text: 'বাতিল', class: 'badge-danger' }
    };
    return map[status] || { text: status, class: 'badge-gray' };
}

// ===== Navbar =====
async function updateNavbar() {
    const authLinks = document.getElementById('auth-links');
    if (!authLinks) return;

    const user = getCurrentUser();

    if (user) {
        let cartBadge = '';
        if (user.role === 'consumer') {
            const cartData = await getCart();
            const count = cartData.count || 0;
            cartBadge = count > 0 ? `<span style="background:var(--danger);color:white;border-radius:999px;padding:1px 7px;font-size:0.75rem;margin-left:4px;font-weight:700">${count}</span>` : '';
        }

        const dashLink = { farmer: 'farmer-dashboard.html', admin: 'admin-dashboard.html', consumer: 'consumer-dashboard.html' }[user.role] || 'index.html';

        authLinks.innerHTML = `
            <a href="${dashLink}">📊 ড্যাশবোর্ড</a>
            ${user.role === 'consumer' ? `<a href="cart.html">🛒 কার্ট${cartBadge}</a>` : ''}
            <a href="#" onclick="logout(); return false;" style="color:var(--danger);">লগআউট</a>
            <span style="font-size:0.875rem;color:var(--gray);padding:0.5rem;">👤 ${user.name}</span>
        `;
    } else {
        authLinks.innerHTML = `
            <a href="login.html">লগইন</a>
            <a href="register.html" class="btn btn-primary btn-sm">রেজিস্টার</a>
        `;
    }
}

document.addEventListener('DOMContentLoaded', updateNavbar);