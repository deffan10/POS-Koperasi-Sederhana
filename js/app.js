/**
 * POS Koperasi Al-Farmasi - Main JavaScript
 */

// Global cart state
let cart = [];
let products = [];

// ========== UTILITY FUNCTIONS ==========

/**
 * Format number to Indonesian Rupiah
 */
function formatRupiah(amount) {
    return 'Rp ' + new Intl.NumberFormat('id-ID').format(amount);
}

/**
 * Show toast notification
 */
function showToast(message, type = 'success') {
    const container = document.getElementById('toast-container') || createToastContainer();
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'times-circle' : 'exclamation-circle'}"></i>
        <span>${message}</span>
    `;
    container.appendChild(toast);
    
    setTimeout(() => {
        toast.remove();
    }, 3000);
}

function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toast-container';
    container.className = 'toast-container';
    document.body.appendChild(container);
    return container;
}

/**
 * AJAX helper function
 */
async function fetchAPI(url, method = 'GET', data = null) {
    const options = {
        method,
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    };
    
    if (data && method !== 'GET') {
        options.body = JSON.stringify(data);
    }
    
    try {
        const response = await fetch(url, options);
        return await response.json();
    } catch (error) {
        console.error('API Error:', error);
        throw error;
    }
}

// ========== MOBILE MENU ==========

function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    sidebar.classList.toggle('active');
}

// Close sidebar when clicking outside on mobile
document.addEventListener('click', (e) => {
    const sidebar = document.querySelector('.sidebar');
    const menuToggle = document.querySelector('.menu-toggle');
    
    if (sidebar && menuToggle && window.innerWidth <= 768) {
        if (!sidebar.contains(e.target) && !menuToggle.contains(e.target)) {
            sidebar.classList.remove('active');
        }
    }
});

// ========== MODAL FUNCTIONS ==========

function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

// Close modal when clicking overlay
document.addEventListener('click', (e) => {
    if (e.target.classList.contains('modal-overlay')) {
        e.target.classList.remove('active');
        document.body.style.overflow = '';
    }
});

// ========== POS FUNCTIONS ==========

/**
 * Search products
 */
async function searchProducts(query) {
    if (!query.trim()) {
        renderProductList(products);
        return;
    }
    
    const result = await fetchAPI(`api/products.php?search=${encodeURIComponent(query)}`);
    if (result.success) {
        renderProductList(result.data);
    }
}

/**
 * Load all products
 */
async function loadProducts() {
    const result = await fetchAPI('api/products.php');
    if (result.success) {
        products = result.data;
        renderProductList(products);
    }
}

/**
 * Render product list
 */
function renderProductList(productList) {
    const container = document.getElementById('product-list');
    if (!container) return;
    
    if (productList.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-box-open"></i>
                <p>Tidak ada produk ditemukan</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = productList.map(product => `
        <div class="product-item ${product.stok <= 0 ? 'out-of-stock' : ''}" 
             onclick="${product.stok > 0 ? `addToCart(${product.id})` : ''}">
            <div class="product-info">
                <h4>${escapeHtml(product.nama_produk)}</h4>
                <span class="code">${escapeHtml(product.kode_produk)}</span>
            </div>
            <div class="product-price">
                <div class="price">${formatRupiah(product.harga_jual)}</div>
                <div class="stock ${product.stok <= 5 ? 'text-danger' : ''}">
                    Stok: ${product.stok}
                </div>
            </div>
        </div>
    `).join('');
}

/**
 * Add product to cart
 */
function addToCart(productId) {
    const product = products.find(p => p.id == productId);
    if (!product || product.stok <= 0) return;
    
    const existingItem = cart.find(item => item.id == productId);
    
    if (existingItem) {
        if (existingItem.qty >= product.stok) {
            showToast('Stok tidak mencukupi', 'error');
            return;
        }
        existingItem.qty++;
    } else {
        cart.push({
            id: product.id,
            kode: product.kode_produk,
            nama: product.nama_produk,
            harga: parseFloat(product.harga_jual),
            qty: 1,
            stok: product.stok
        });
    }
    
    renderCart();
    showToast(`${product.nama_produk} ditambahkan`);
}

/**
 * Update cart item quantity
 */
function updateQty(productId, delta) {
    const item = cart.find(i => i.id == productId);
    if (!item) return;
    
    const newQty = item.qty + delta;
    
    if (newQty <= 0) {
        removeFromCart(productId);
        return;
    }
    
    if (newQty > item.stok) {
        showToast('Stok tidak mencukupi', 'error');
        return;
    }
    
    item.qty = newQty;
    renderCart();
}

/**
 * Remove item from cart
 */
function removeFromCart(productId) {
    cart = cart.filter(item => item.id != productId);
    renderCart();
}

/**
 * Clear cart
 */
function clearCart() {
    cart = [];
    renderCart();
}

/**
 * Render cart
 */
function renderCart() {
    const container = document.getElementById('cart-items');
    const totalElement = document.getElementById('cart-total');
    
    if (!container) return;
    
    if (cart.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-shopping-cart"></i>
                <p>Keranjang kosong</p>
            </div>
        `;
        if (totalElement) totalElement.textContent = formatRupiah(0);
        return;
    }
    
    let total = 0;
    
    container.innerHTML = cart.map(item => {
        const subtotal = item.harga * item.qty;
        total += subtotal;
        
        return `
            <div class="cart-item">
                <div class="cart-item-info">
                    <h5>${escapeHtml(item.nama)}</h5>
                    <small>${formatRupiah(item.harga)}</small>
                </div>
                <div class="cart-item-qty">
                    <button class="qty-btn" onclick="updateQty(${item.id}, -1)">-</button>
                    <span>${item.qty}</span>
                    <button class="qty-btn" onclick="updateQty(${item.id}, 1)">+</button>
                </div>
                <div class="cart-item-subtotal">${formatRupiah(subtotal)}</div>
                <span class="cart-item-remove" onclick="removeFromCart(${item.id})">
                    <i class="fas fa-trash"></i>
                </span>
            </div>
        `;
    }).join('');
    
    if (totalElement) totalElement.textContent = formatRupiah(total);
}

/**
 * Calculate cart total
 */
function getCartTotal() {
    return cart.reduce((total, item) => total + (item.harga * item.qty), 0);
}

/**
 * Process payment
 */
async function processPayment(method) {
    if (cart.length === 0) {
        showToast('Keranjang masih kosong', 'error');
        return;
    }
    
    const total = getCartTotal();
    
    if (method === 'tunai') {
        // Show cash payment modal
        document.getElementById('total-amount').textContent = formatRupiah(total);
        document.getElementById('cash-amount').value = '';
        document.getElementById('change-amount').textContent = formatRupiah(0);
        openModal('cash-modal');
    } else {
        // Non-cash payment - process directly
        await submitTransaction(method, total, 0);
    }
}

/**
 * Calculate change
 */
function calculateChange() {
    const total = getCartTotal();
    const cash = parseFloat(document.getElementById('cash-amount').value) || 0;
    const change = cash - total;
    document.getElementById('change-amount').textContent = formatRupiah(Math.max(0, change));
    return change;
}

/**
 * Confirm cash payment
 */
async function confirmCashPayment() {
    const total = getCartTotal();
    const cash = parseFloat(document.getElementById('cash-amount').value) || 0;
    
    if (cash < total) {
        showToast('Jumlah pembayaran kurang', 'error');
        return;
    }
    
    closeModal('cash-modal');
    await submitTransaction('tunai', cash, cash - total);
}

/**
 * Submit transaction to server
 */
async function submitTransaction(method, amountPaid, change) {
    const items = cart.map(item => ({
        product_id: item.id,
        qty: item.qty
    }));
    
    try {
        const result = await fetchAPI('api/transactions.php', 'POST', {
            items: items,
            metode_pembayaran: method,
            jumlah_bayar: amountPaid,
            kembalian: change
        });
        
        if (result.success) {
            showToast('Transaksi berhasil!', 'success');
            clearCart();
            loadProducts(); // Refresh product stock
            
            // Show success modal with transaction number
            if (result.data && result.data.nomor_transaksi) {
                document.getElementById('success-trx-number').textContent = result.data.nomor_transaksi;
                openModal('success-modal');
            }
        } else {
            showToast(result.message || 'Transaksi gagal', 'error');
        }
    } catch (error) {
        showToast('Terjadi kesalahan', 'error');
    }
}

// ========== PRODUCT MANAGEMENT ==========

/**
 * Save product (create or update)
 */
async function saveProduct(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());
    
    const method = data.id ? 'PUT' : 'POST';
    
    try {
        const result = await fetchAPI('api/products.php', method, data);
        
        if (result.success) {
            showToast(result.message, 'success');
            closeModal('product-modal');
            loadProductTable();
            form.reset();
        } else {
            showToast(result.message || 'Gagal menyimpan produk', 'error');
        }
    } catch (error) {
        showToast('Terjadi kesalahan', 'error');
    }
}

/**
 * Delete product
 */
async function deleteProduct(id) {
    if (!confirm('Yakin ingin menghapus produk ini?')) return;
    
    try {
        const result = await fetchAPI(`api/products.php?id=${id}`, 'DELETE');
        
        if (result.success) {
            showToast('Produk berhasil dihapus', 'success');
            loadProductTable();
        } else {
            showToast(result.message || 'Gagal menghapus produk', 'error');
        }
    } catch (error) {
        showToast('Terjadi kesalahan', 'error');
    }
}

/**
 * Edit product - open modal with data
 */
async function editProduct(id) {
    try {
        const result = await fetchAPI(`api/products.php?id=${id}`);
        
        if (result.success && result.data) {
            const product = result.data;
            document.getElementById('product-id').value = product.id;
            document.getElementById('kode-produk').value = product.kode_produk;
            document.getElementById('nama-produk').value = product.nama_produk;
            document.getElementById('category-id').value = product.category_id || '';
            document.getElementById('harga-jual').value = product.harga_jual;
            document.getElementById('stok').value = product.stok;
            document.getElementById('satuan').value = product.satuan;
            
            document.getElementById('modal-title').textContent = 'Edit Produk';
            openModal('product-modal');
        }
    } catch (error) {
        showToast('Gagal memuat data produk', 'error');
    }
}

/**
 * Open new product modal
 */
function newProduct() {
    document.getElementById('product-form').reset();
    document.getElementById('product-id').value = '';
    document.getElementById('modal-title').textContent = 'Tambah Produk Baru';
    openModal('product-modal');
}

/**
 * Load product table
 */
async function loadProductTable() {
    const tbody = document.getElementById('product-table-body');
    if (!tbody) return;
    
    try {
        const result = await fetchAPI('api/products.php');
        
        if (result.success) {
            if (result.data.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" class="text-center text-muted">Belum ada produk</td>
                    </tr>
                `;
                return;
            }
            
            tbody.innerHTML = result.data.map(product => `
                <tr>
                    <td><strong>${escapeHtml(product.kode_produk)}</strong></td>
                    <td>${escapeHtml(product.nama_produk)}</td>
                    <td>${escapeHtml(product.nama_kategori || '-')}</td>
                    <td class="text-right">${formatRupiah(product.harga_jual)}</td>
                    <td class="text-center">
                        <span class="badge ${product.stok <= 5 ? 'badge-danger' : 'badge-success'}">
                            ${product.stok}
                        </span>
                    </td>
                    <td>${escapeHtml(product.satuan)}</td>
                    <td>
                        <button class="btn btn-sm btn-primary" onclick="editProduct(${product.id})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="deleteProduct(${product.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `).join('');
        }
    } catch (error) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center text-danger">Gagal memuat data</td>
            </tr>
        `;
    }
}

// ========== STOCK MANAGEMENT ==========

/**
 * Add stock
 */
async function addStock(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());
    
    try {
        const result = await fetchAPI('api/stock.php', 'POST', data);
        
        if (result.success) {
            showToast(result.message, 'success');
            closeModal('stock-modal');
            loadStockHistory();
            loadProductTable();
            form.reset();
        } else {
            showToast(result.message || 'Gagal menambah stok', 'error');
        }
    } catch (error) {
        showToast('Terjadi kesalahan', 'error');
    }
}

/**
 * Load stock history
 */
async function loadStockHistory() {
    const tbody = document.getElementById('stock-history-body');
    if (!tbody) return;
    
    try {
        const result = await fetchAPI('api/stock.php');
        
        if (result.success) {
            if (result.data.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" class="text-center text-muted">Belum ada riwayat stok</td>
                    </tr>
                `;
                return;
            }
            
            tbody.innerHTML = result.data.map(item => `
                <tr>
                    <td>${formatDateTime(item.created_at)}</td>
                    <td>${escapeHtml(item.kode_produk)}</td>
                    <td>${escapeHtml(item.nama_produk)}</td>
                    <td>
                        <span class="badge ${item.jenis_perubahan === 'masuk' ? 'badge-success' : 'badge-danger'}">
                            ${item.jenis_perubahan.toUpperCase()}
                        </span>
                    </td>
                    <td class="text-center">${item.jumlah}</td>
                    <td class="text-center">${item.stok_sebelum} → ${item.stok_sesudah}</td>
                    <td>${escapeHtml(item.keterangan || '-')}</td>
                </tr>
            `).join('');
        }
    } catch (error) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center text-danger">Gagal memuat data</td>
            </tr>
        `;
    }
}

// ========== REPORTS ==========

/**
 * Load sales report
 */
async function loadSalesReport(date = null) {
    const tbody = document.getElementById('report-table-body');
    const statsContainer = document.getElementById('report-stats');
    
    if (!tbody) return;
    
    const url = date ? `api/reports.php?date=${date}` : 'api/reports.php';
    
    try {
        const result = await fetchAPI(url);
        
        if (result.success) {
            // Update stats
            if (statsContainer && result.summary) {
                document.getElementById('total-transactions').textContent = result.summary.total_transaksi || 0;
                document.getElementById('total-revenue').textContent = formatRupiah(result.summary.total_omzet || 0);
                document.getElementById('total-items').textContent = result.summary.total_item || 0;
            }
            
            // Update table
            if (result.data.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center text-muted">Tidak ada transaksi</td>
                    </tr>
                `;
                return;
            }
            
            tbody.innerHTML = result.data.map(trx => `
                <tr>
                    <td><strong>${escapeHtml(trx.nomor_transaksi)}</strong></td>
                    <td>${formatDateTime(trx.tanggal_transaksi)}</td>
                    <td>${escapeHtml(trx.nama_kasir)}</td>
                    <td>
                        <span class="badge ${trx.metode_pembayaran === 'tunai' ? 'badge-success' : 'badge-info'}">
                            ${trx.metode_pembayaran.toUpperCase()}
                        </span>
                    </td>
                    <td class="text-right"><strong>${formatRupiah(trx.total_harga)}</strong></td>
                    <td>
                        <button class="btn btn-sm btn-outline" onclick="viewTransaction('${trx.nomor_transaksi}')">
                            <i class="fas fa-eye"></i> Detail
                        </button>
                    </td>
                </tr>
            `).join('');
        }
    } catch (error) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center text-danger">Gagal memuat data</td>
            </tr>
        `;
    }
}

/**
 * View transaction detail
 */
async function viewTransaction(nomorTransaksi) {
    try {
        const result = await fetchAPI(`api/transactions.php?nomor=${nomorTransaksi}`);
        
        if (result.success && result.data) {
            const trx = result.data;
            const itemsHtml = trx.items.map(item => `
                <tr>
                    <td>${escapeHtml(item.nama_produk)}</td>
                    <td class="text-center">${item.jumlah}</td>
                    <td class="text-right">${formatRupiah(item.harga_satuan)}</td>
                    <td class="text-right">${formatRupiah(item.subtotal)}</td>
                </tr>
            `).join('');
            
            document.getElementById('detail-content').innerHTML = `
                <div class="mb-2">
                    <strong>No. Transaksi:</strong> ${escapeHtml(trx.nomor_transaksi)}<br>
                    <strong>Tanggal:</strong> ${formatDateTime(trx.tanggal_transaksi)}<br>
                    <strong>Kasir:</strong> ${escapeHtml(trx.nama_kasir)}<br>
                    <strong>Metode:</strong> ${trx.metode_pembayaran.toUpperCase()}
                </div>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th class="text-center">Qty</th>
                            <th class="text-right">Harga</th>
                            <th class="text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>${itemsHtml}</tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-right"><strong>Total:</strong></td>
                            <td class="text-right"><strong>${formatRupiah(trx.total_harga)}</strong></td>
                        </tr>
                        <tr>
                            <td colspan="3" class="text-right">Bayar:</td>
                            <td class="text-right">${formatRupiah(trx.jumlah_bayar)}</td>
                        </tr>
                        <tr>
                            <td colspan="3" class="text-right">Kembali:</td>
                            <td class="text-right">${formatRupiah(trx.kembalian)}</td>
                        </tr>
                    </tfoot>
                </table>
            `;
            
            openModal('detail-modal');
        }
    } catch (error) {
        showToast('Gagal memuat detail transaksi', 'error');
    }
}

// ========== HELPER FUNCTIONS ==========

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDateTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// ========== CATEGORIES ==========

async function loadCategories() {
    const select = document.getElementById('category-id');
    const stockSelect = document.getElementById('stock-product-id');
    
    try {
        const result = await fetchAPI('api/categories.php');
        
        if (result.success && select) {
            select.innerHTML = '<option value="">-- Pilih Kategori --</option>' +
                result.data.map(cat => `<option value="${cat.id}">${escapeHtml(cat.nama_kategori)}</option>`).join('');
        }
    } catch (error) {
        console.error('Failed to load categories');
    }
    
    // Load products for stock modal
    if (stockSelect) {
        try {
            const result = await fetchAPI('api/products.php');
            if (result.success) {
                stockSelect.innerHTML = '<option value="">-- Pilih Produk --</option>' +
                    result.data.map(p => `<option value="${p.id}">${escapeHtml(p.kode_produk)} - ${escapeHtml(p.nama_produk)}</option>`).join('');
            }
        } catch (error) {
            console.error('Failed to load products for stock');
        }
    }
}

// ========== INITIALIZATION ==========

document.addEventListener('DOMContentLoaded', () => {
    // Initialize POS page
    if (document.getElementById('product-list')) {
        loadProducts();
        
        // Search input handler with debounce
        const searchInput = document.getElementById('search-input');
        if (searchInput) {
            let timeout;
            searchInput.addEventListener('input', (e) => {
                clearTimeout(timeout);
                timeout = setTimeout(() => searchProducts(e.target.value), 300);
            });
            
            // Focus search on page load
            searchInput.focus();
        }
    }
    
    // Initialize Product Management page
    if (document.getElementById('product-table-body')) {
        loadProductTable();
        loadCategories();
    }
    
    // Initialize Stock page
    if (document.getElementById('stock-history-body')) {
        loadStockHistory();
        loadCategories();
    }
    
    // Initialize Reports page
    if (document.getElementById('report-table-body')) {
        loadSalesReport();
        
        const dateFilter = document.getElementById('date-filter');
        if (dateFilter) {
            dateFilter.addEventListener('change', (e) => {
                loadSalesReport(e.target.value);
            });
        }
    }
});
