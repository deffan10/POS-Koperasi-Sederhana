/**
 * POS Koperasi Al-Farmasi
 * POS (Kasir) JavaScript
 */

// Cart state
let cart = [];
let selectedProduct = null;

// DOM Elements
const searchInput = document.getElementById('searchProduct');
const suggestionsBox = document.getElementById('productSuggestions');
const quantityInput = document.getElementById('quantity');
const selectedProductDiv = document.getElementById('selectedProduct');
const cartBody = document.getElementById('cartBody');
const emptyCart = document.getElementById('emptyCart');
const totalItemsEl = document.getElementById('totalItems');
const grandTotalEl = document.getElementById('grandTotal');
const cashReceivedInput = document.getElementById('cashReceived');
const changeAmountEl = document.getElementById('changeAmount');
const cashSection = document.getElementById('cashSection');
const changeSection = document.getElementById('changeSection');
const btnSaveTransaction = document.getElementById('btnSaveTransaction');

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    initializeEventListeners();
    updateCartDisplay();
});

function initializeEventListeners() {
    // Product search
    searchInput.addEventListener('input', handleSearch);
    searchInput.addEventListener('keydown', handleSearchKeydown);
    searchInput.addEventListener('focus', () => {
        if (searchInput.value.length >= 1) {
            handleSearch();
        }
    });
    
    // Close suggestions when clicking outside
    document.addEventListener('click', (e) => {
        if (!searchInput.contains(e.target) && !suggestionsBox.contains(e.target)) {
            hideSuggestions();
        }
    });
    
    // Quantity buttons
    document.getElementById('btnPlus').addEventListener('click', () => {
        quantityInput.value = parseInt(quantityInput.value) + 1;
    });
    
    document.getElementById('btnMinus').addEventListener('click', () => {
        const current = parseInt(quantityInput.value);
        if (current > 1) {
            quantityInput.value = current - 1;
        }
    });
    
    // Add to cart
    document.getElementById('btnAddToCart').addEventListener('click', addToCart);
    
    // Enter key on quantity input
    quantityInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            addToCart();
        }
    });
    
    // Payment method
    document.querySelectorAll('input[name="paymentMethod"]').forEach(radio => {
        radio.addEventListener('change', handlePaymentMethodChange);
    });
    
    // Cash received
    cashReceivedInput.addEventListener('input', calculateChange);
    
    // Quick cash buttons
    document.querySelectorAll('.quick-cash').forEach(btn => {
        btn.addEventListener('click', () => {
            const amount = btn.dataset.amount;
            if (amount === 'exact') {
                cashReceivedInput.value = getGrandTotal();
            } else {
                cashReceivedInput.value = parseInt(amount);
            }
            calculateChange();
        });
    });
    
    // Clear cart
    document.getElementById('btnClearCart').addEventListener('click', () => {
        if (cart.length > 0) {
            if (confirm('Kosongkan keranjang?')) {
                clearCart();
            }
        }
    });
    
    // Save transaction
    btnSaveTransaction.addEventListener('click', saveTransaction);
    
    // New transaction button (in modal)
    document.getElementById('btnNewTransaction').addEventListener('click', () => {
        location.reload();
    });
}

// Search products
function handleSearch() {
    const query = searchInput.value.trim();
    
    if (query.length < 1) {
        hideSuggestions();
        return;
    }
    
    const filtered = products.filter(p => 
        p.kode_produk.toLowerCase().includes(query.toLowerCase()) ||
        p.nama_produk.toLowerCase().includes(query.toLowerCase())
    ).slice(0, 10);
    
    showSuggestions(filtered);
}

function handleSearchKeydown(e) {
    const items = suggestionsBox.querySelectorAll('.suggestion-item');
    const activeItem = suggestionsBox.querySelector('.suggestion-item.active');
    let activeIndex = Array.from(items).indexOf(activeItem);
    
    if (e.key === 'ArrowDown') {
        e.preventDefault();
        if (activeIndex < items.length - 1) {
            items[activeIndex]?.classList.remove('active');
            items[activeIndex + 1]?.classList.add('active');
            items[activeIndex + 1]?.scrollIntoView({ block: 'nearest' });
        }
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        if (activeIndex > 0) {
            items[activeIndex]?.classList.remove('active');
            items[activeIndex - 1]?.classList.add('active');
            items[activeIndex - 1]?.scrollIntoView({ block: 'nearest' });
        }
    } else if (e.key === 'Enter') {
        e.preventDefault();
        if (activeItem) {
            const productId = activeItem.dataset.id;
            selectProduct(products.find(p => p.id == productId));
        } else if (items.length === 1) {
            const productId = items[0].dataset.id;
            selectProduct(products.find(p => p.id == productId));
        }
    } else if (e.key === 'Escape') {
        hideSuggestions();
    }
}

function showSuggestions(productList) {
    if (productList.length === 0) {
        suggestionsBox.innerHTML = `
            <div class="suggestion-item text-muted text-center">
                <i class="bi bi-search me-2"></i>Produk tidak ditemukan
            </div>
        `;
        suggestionsBox.classList.add('show');
        return;
    }
    
    let html = '';
    productList.forEach((product, index) => {
        const stockClass = product.stok < 10 ? 'text-danger' : 'text-success';
        html += `
            <div class="suggestion-item ${index === 0 ? 'active' : ''}" 
                 data-id="${product.id}" 
                 onclick="selectProduct(products.find(p => p.id == ${product.id}))">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="product-name">${product.nama_produk}</div>
                        <div class="product-info">
                            <code>${product.kode_produk}</code>
                            <span class="mx-2">|</span>
                            <span class="${stockClass}">Stok: ${product.stok}</span>
                        </div>
                    </div>
                    <div class="product-price">${formatRupiah(product.harga_jual)}</div>
                </div>
            </div>
        `;
    });
    
    suggestionsBox.innerHTML = html;
    suggestionsBox.classList.add('show');
}

function hideSuggestions() {
    suggestionsBox.classList.remove('show');
}

function selectProduct(product) {
    if (!product) return;
    
    selectedProduct = product;
    searchInput.value = product.nama_produk;
    hideSuggestions();
    
    // Show selected product info
    document.getElementById('selectedName').textContent = product.nama_produk;
    document.getElementById('selectedCode').textContent = product.kode_produk;
    document.getElementById('selectedStock').textContent = product.stok;
    document.getElementById('selectedPrice').textContent = formatRupiah(product.harga_jual);
    selectedProductDiv.classList.remove('d-none');
    
    // Focus on quantity
    quantityInput.focus();
    quantityInput.select();
}

// Cart functions
function addToCart() {
    if (!selectedProduct) {
        showToast('Pilih produk terlebih dahulu', 'warning');
        searchInput.focus();
        return;
    }
    
    const qty = parseInt(quantityInput.value) || 1;
    
    if (qty <= 0) {
        showToast('Jumlah harus lebih dari 0', 'warning');
        return;
    }
    
    // Check stock
    const existingItem = cart.find(item => item.produk_id === selectedProduct.id);
    const currentQtyInCart = existingItem ? existingItem.jumlah : 0;
    const totalQty = currentQtyInCart + qty;
    
    if (totalQty > selectedProduct.stok) {
        showToast(`Stok tidak mencukupi! Tersedia: ${selectedProduct.stok}, Di keranjang: ${currentQtyInCart}`, 'danger');
        return;
    }
    
    if (existingItem) {
        existingItem.jumlah = totalQty;
        existingItem.subtotal = existingItem.jumlah * existingItem.harga;
    } else {
        cart.push({
            produk_id: selectedProduct.id,
            kode_produk: selectedProduct.kode_produk,
            nama_produk: selectedProduct.nama_produk,
            harga: parseFloat(selectedProduct.harga_jual),
            stok: selectedProduct.stok,
            jumlah: qty,
            subtotal: qty * parseFloat(selectedProduct.harga_jual)
        });
    }
    
    updateCartDisplay();
    resetProductInput();
    showToast('Produk ditambahkan ke keranjang');
}

function removeFromCart(index) {
    cart.splice(index, 1);
    updateCartDisplay();
}

function updateQuantity(index, newQty) {
    if (newQty <= 0) {
        removeFromCart(index);
        return;
    }
    
    const item = cart[index];
    if (newQty > item.stok) {
        showToast(`Stok tidak mencukupi! Maksimal: ${item.stok}`, 'warning');
        return;
    }
    
    item.jumlah = newQty;
    item.subtotal = item.jumlah * item.harga;
    updateCartDisplay();
}

function clearCart() {
    cart = [];
    updateCartDisplay();
    resetProductInput();
}

function updateCartDisplay() {
    if (cart.length === 0) {
        emptyCart.style.display = '';
        cartBody.innerHTML = '';
        cartBody.appendChild(emptyCart);
    } else {
        emptyCart.style.display = 'none';
        
        let html = '';
        cart.forEach((item, index) => {
            html += `
                <tr>
                    <td>${index + 1}</td>
                    <td>
                        <strong>${item.nama_produk}</strong>
                        <br><small class="text-muted">${item.kode_produk}</small>
                    </td>
                    <td class="text-center">${formatRupiah(item.harga)}</td>
                    <td class="text-center">
                        <div class="input-group input-group-sm" style="width: 100px; margin: 0 auto;">
                            <button class="btn btn-outline-secondary" type="button" onclick="updateQuantity(${index}, ${item.jumlah - 1})">-</button>
                            <input type="number" class="form-control text-center" value="${item.jumlah}" 
                                   onchange="updateQuantity(${index}, parseInt(this.value))" min="1" max="${item.stok}">
                            <button class="btn btn-outline-secondary" type="button" onclick="updateQuantity(${index}, ${item.jumlah + 1})">+</button>
                        </div>
                    </td>
                    <td class="text-end fw-bold">${formatRupiah(item.subtotal)}</td>
                    <td>
                        <button class="btn btn-sm btn-danger" onclick="removeFromCart(${index})">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        });
        
        cartBody.innerHTML = html;
    }
    
    // Update totals
    const totalItems = cart.reduce((sum, item) => sum + item.jumlah, 0);
    const grandTotal = getGrandTotal();
    
    totalItemsEl.textContent = totalItems + ' item';
    grandTotalEl.textContent = formatRupiah(grandTotal);
    
    // Update save button state
    btnSaveTransaction.disabled = cart.length === 0;
    
    // Recalculate change
    calculateChange();
}

function getGrandTotal() {
    return cart.reduce((sum, item) => sum + item.subtotal, 0);
}

function resetProductInput() {
    selectedProduct = null;
    searchInput.value = '';
    quantityInput.value = 1;
    selectedProductDiv.classList.add('d-none');
    searchInput.focus();
}

// Payment functions
function handlePaymentMethodChange() {
    const method = document.querySelector('input[name="paymentMethod"]:checked').value;
    
    // Hide all payment sections first
    document.querySelectorAll('.payment-section').forEach(section => {
        section.classList.add('d-none');
    });
    
    if (method === 'tunai') {
        cashSection.style.display = '';
        changeSection.style.display = '';
    } else {
        cashSection.style.display = 'none';
        changeSection.style.display = 'none';
        
        // Show QRIS or Transfer section
        const sectionId = method + 'Section';
        const section = document.getElementById(sectionId);
        if (section) {
            section.classList.remove('d-none');
        }
    }
    
    calculateChange();
}

function calculateChange() {
    const method = document.querySelector('input[name="paymentMethod"]:checked').value;
    const grandTotal = getGrandTotal();
    
    if (method === 'tunai') {
        const cashReceived = parseFloat(cashReceivedInput.value) || 0;
        const change = cashReceived - grandTotal;
        
        changeAmountEl.textContent = formatRupiah(Math.max(0, change));
        
        if (change < 0 && cashReceived > 0) {
            changeAmountEl.parentElement.classList.add('bg-danger');
            changeAmountEl.parentElement.classList.remove('bg-success');
            changeAmountEl.textContent = 'Kurang ' + formatRupiah(Math.abs(change));
        } else {
            changeAmountEl.parentElement.classList.remove('bg-danger');
        }
        
        // Enable/disable save button based on payment
        btnSaveTransaction.disabled = cart.length === 0 || cashReceived < grandTotal;
    } else {
        btnSaveTransaction.disabled = cart.length === 0;
    }
}

// Save transaction
async function saveTransaction() {
    if (cart.length === 0) {
        showToast('Keranjang masih kosong', 'warning');
        return;
    }
    
    const method = document.querySelector('input[name="paymentMethod"]:checked').value;
    const grandTotal = getGrandTotal();
    const cashReceived = method === 'tunai' ? parseFloat(cashReceivedInput.value) || 0 : grandTotal;
    
    if (method === 'tunai' && cashReceived < grandTotal) {
        showToast('Uang yang diterima kurang!', 'danger');
        cashReceivedInput.focus();
        return;
    }
    
    // Disable button to prevent double submit
    btnSaveTransaction.disabled = true;
    btnSaveTransaction.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...';
    
    try {
        const response = await fetch('api/transactions.php?action=save', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify({
                items: cart,
                metode_pembayaran: method,
                uang_diterima: cashReceived,
                total_harga: grandTotal,
                total_item: cart.reduce((sum, item) => sum + item.jumlah, 0)
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('transactionNumber').textContent = data.no_transaksi;
            new bootstrap.Modal(document.getElementById('successModal')).show();
        } else {
            throw new Error(data.message || 'Terjadi kesalahan');
        }
    } catch (error) {
        showToast(error.message, 'danger');
        btnSaveTransaction.disabled = false;
        btnSaveTransaction.innerHTML = '<i class="bi bi-check-circle me-2"></i>Simpan Transaksi';
    }
}

// Helper function - format rupiah
function formatRupiah(angka) {
    return 'Rp ' + new Intl.NumberFormat('id-ID').format(angka);
}

// Show toast notification (if not already defined in app.js)
if (typeof showToast !== 'function') {
    function showToast(message, type = 'success') {
        alert(message);
    }
}
