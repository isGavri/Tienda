
function openProductModal(productId = null) {
  const modal = document.getElementById('productModal');
  const title = document.getElementById('productModalTitle');

  if (productId) {
    title.textContent = 'Editar Producto';
    // TODO: Load product data from DB
  } else {
    title.textContent = 'Nuevo Producto';
    document.getElementById('productForm').reset();
    document.getElementById('productId').value = '';
  }

  modal.classList.add('active');
}

function closeProductModal() {
  document.getElementById('productModal').classList.remove('active');
}

function editProduct(productId) {
  openProductModal(productId);
}

function saveProduct() {
  const form = document.getElementById('productForm');

  if (!form.checkValidity()) {
    form.reportValidity();
    return;
  }

  showNotification('Funcionalidad en desarrollo');
  closeProductModal();
}

function adjustStock(productId, currentStock) {
  const newStock = prompt(`Stock actual: ${currentStock}\nIngrese el nuevo stock:`);

  if (newStock === null) return;

  const stock = parseInt(newStock);

  if (isNaN(stock) || stock < 0) {
    showNotification('Stock inválido', 'error');
    return;
  }

  showNotification('Funcionalidad en desarrollo');
}

// Search functionality
document.getElementById('inventorySearch')?.addEventListener('input', function(e) {
  const searchTerm = e.target.value.toLowerCase();
  const rows = document.querySelectorAll('tbody tr');

  rows.forEach(row => {
    const text = row.textContent.toLowerCase();
    row.style.display = text.includes(searchTerm) ? '' : 'none';
  });
});

// Category filter
document.getElementById('categoryFilter')?.addEventListener('change', function(e) {
  const categoryId = e.target.value;
  const rows = document.querySelectorAll('tbody tr');

  rows.forEach(row => {
    if (!categoryId || row.dataset.category === categoryId) {
      row.style.display = '';
    } else {
      row.style.display = 'none';
    }
  });
});

// Close modal on overlay click
document.getElementById('productModal')?.addEventListener('click', function(e) {
  if (e.target === this) {
    closeProductModal();
  }
});
