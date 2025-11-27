// POS (Point of Sale) functionality with API integration

let cart = [];
let selectedPaymentMethod = null;
let selectedPaymentId = null;

// Product search with API
document.getElementById('productSearch').addEventListener('keypress', async function(e) {
  if (e.key === 'Enter') {
    const searchTerm = this.value.trim();
    if (!searchTerm) return;

    try {
      const response = await fetch(`/pages/api.php?action=search_product&search=${encodeURIComponent(searchTerm)}`);
      const data = await response.json();

      if (data.success && data.data.length > 0) {
        const product = data.data[0];
        addToCart({
          id: product.id,
          sku: product.sku,
          name: product.nombre,
          price: parseFloat(product.precio_venta),
          stock: parseInt(product.stock_actual)
        });
        this.value = '';
      } else {
        showNotification('Producto no encontrado', 'error');
      }
    } catch (error) {
      console.error('Error:', error);
      showNotification('Error al buscar producto', 'error');
    }
  }
});

function addToCart(product) {
  const existingItem = cart.find(item => item.id === product.id);

  if (existingItem) {
    if (existingItem.quantity < product.stock) {
      existingItem.quantity++;
    } else {
      showNotification('Stock insuficiente', 'error');
      return;
    }
  } else {
    cart.push({
      ...product,
      quantity: 1
    });
  }

  renderCart();
  updateSummary();
}

function removeFromCart(productId) {
  cart = cart.filter(item => item.id !== productId);
  renderCart();
  updateSummary();
}

function updateQuantity(productId, delta) {
  const item = cart.find(item => item.id === productId);
  if (!item) return;

  const newQuantity = item.quantity + delta;

  if (newQuantity <= 0) {
    removeFromCart(productId);
  } else if (newQuantity <= item.stock) {
    item.quantity = newQuantity;
    renderCart();
    updateSummary();
  } else {
    showNotification('Stock insuficiente', 'error');
  }
}

function renderCart() {
  const cartContainer = document.getElementById('cartItems');

  if (cart.length === 0) {
    cartContainer.innerHTML = `
            <div style="text-align: center; padding: 3rem; color: var(--gray-400);">
                <svg style="width: 64px; height: 64px; margin: 0 auto 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <p>Escanea o busca productos para comenzar</p>
            </div>
        `;
    document.getElementById('checkoutBtn').disabled = true;
    return;
  }

  cartContainer.innerHTML = cart.map(item => `
        <div class="cart-item">
            <div style="flex: 1;">
                <div class="font-medium">${item.name}</div>
                <div class="text-sm text-gray-500">${item.sku} - ${formatCurrency(item.price)}</div>
            </div>
            <div class="flex items-center gap-2">
                <button onclick="updateQuantity(${item.id}, -1)" class="btn btn-sm btn-secondary">-</button>
                <span class="font-medium" style="min-width: 30px; text-align: center;">${item.quantity}</span>
                <button onclick="updateQuantity(${item.id}, 1)" class="btn btn-sm btn-secondary">+</button>
                <span class="font-semibold" style="min-width: 100px; text-align: right;">${formatCurrency(item.price * item.quantity)}</span>
                <button onclick="removeFromCart(${item.id})" class="btn btn-sm btn-danger">
                    <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
    `).join('');

  document.getElementById('checkoutBtn').disabled = false;
}

function updateSummary() {
  const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
  const tax = subtotal * 0.16;
  const total = subtotal + tax;

  document.getElementById('subtotal').textContent = formatCurrency(subtotal);
  document.getElementById('tax').textContent = formatCurrency(tax);
  document.getElementById('total').textContent = formatCurrency(total);
}

function selectPayment(method, paymentId) {
  selectedPaymentMethod = method;
  selectedPaymentId = paymentId;

  document.getElementById('paymentCash').className = 'btn btn-secondary';
  document.getElementById('paymentCard').className = 'btn btn-secondary';

  if (method === 'cash') {
    document.getElementById('paymentCash').className = 'btn btn-primary';
  } else {
    document.getElementById('paymentCard').className = 'btn btn-primary';
  }
}

function clearCart() {
  if (cart.length === 0) return;

  if (confirm('¿Desea limpiar el carrito?')) {
    cart = [];
    selectedPaymentMethod = null;
    selectedPaymentId = null;
    document.getElementById('paymentCash').className = 'btn btn-secondary';
    document.getElementById('paymentCard').className = 'btn btn-secondary';
    renderCart();
    updateSummary();
    showNotification('Carrito limpiado');
  }
}

async function processCheckout() {
  if (cart.length === 0) {
    showNotification('El carrito está vacío', 'error');
    return;
  }

  if (!selectedPaymentId) {
    showNotification('Seleccione un método de pago', 'error');
    return;
  }

  const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
  const impuesto = subtotal * 0.16;
  const total = subtotal + impuesto;

  const saleData = {
    cliente_id: document.getElementById('customerSelect').value,
    empleado_id: 1, // TODO: Get from session
    metodo_pago_id: selectedPaymentId,
    subtotal: subtotal,
    impuesto: impuesto,
    total: total,
    items: cart.map(item => ({
      id: item.id,
      cantidad: item.quantity,
      precio: item.price
    }))
  };

  try {
    const response = await fetch('/pages/api.php?action=process_sale', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(saleData)
    });

    const data = await response.json();

    if (data.success) {
      showNotification(`Venta procesada: ${formatCurrency(total)}`);

      // Clear cart
      cart = [];
      selectedPaymentMethod = null;
      selectedPaymentId = null;
      document.getElementById('customerSelect').value = '1';
      document.getElementById('paymentCash').className = 'btn btn-secondary';
      document.getElementById('paymentCard').className = 'btn btn-secondary';
      renderCart();
      updateSummary();
    } else {
      showNotification('Error al procesar venta: ' + data.error, 'error');
    }
  } catch (error) {
    console.error('Error:', error);
    showNotification('Error al procesar venta', 'error');
  }
}

// Customer modal
function openCustomerModal() {
  document.getElementById('customerModal').classList.add('active');
}

function closeCustomerModal() {
  document.getElementById('customerModal').classList.remove('active');
}

function selectCustomer(customerId) {
  document.getElementById('customerSelect').value = customerId;
  closeCustomerModal();
}

// Close modal on overlay click
document.getElementById('customerModal').addEventListener('click', function(e) {
  if (e.target === this) {
    closeCustomerModal();
  }
});

// Customer search in modal
document.getElementById('customerSearchInput')?.addEventListener('input', function(e) {
  const searchTerm = e.target.value.toLowerCase();
  const items = document.querySelectorAll('#customerList .cart-item');

  items.forEach(item => {
    const text = item.textContent.toLowerCase();
    item.style.display = text.includes(searchTerm) ? '' : 'none';
  });
});
