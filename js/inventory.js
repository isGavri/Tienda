async function openProductModal(productId = null) {
  const modal = document.getElementById('productModal');
  const title = document.getElementById('productModalTitle');

  if (productId) {
    title.textContent = 'Editar Producto';
    try {
      const response = await fetch(`/pages/api.php?action=get_product&id=${productId}`);
      const result = await response.json();
      
      if (result.success) {
        const p = result.data;
        document.getElementById('productId').value = p.id;
        document.getElementById('productSKU').value = p.sku;
        document.getElementById('productName').value = p.nombre;
        document.getElementById('productCategory').value = p.categoria_id;
        document.getElementById('productPrice').value = p.precio_venta;
        document.getElementById('productStock').value = p.stock_actual;
        document.getElementById('productSupplier').value = p.proveedor_id;
      } else {
        showNotification(result.error, 'error');
        return;
      }
    } catch (error) {
      showNotification('Error al cargar producto', 'error');
      return;
    }
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

async function saveProduct() {
  const form = document.getElementById('productForm');

  if (!form.checkValidity()) {
    form.reportValidity();
    return;
  }

  const productId = document.getElementById('productId').value;
  const data = {
    sku: document.getElementById('productSKU').value,
    nombre: document.getElementById('productName').value,
    categoria_id: document.getElementById('productCategory').value,
    costo: 0,
    precio: parseFloat(document.getElementById('productPrice').value),
    stock: parseInt(document.getElementById('productStock').value),
    proveedor_id: document.getElementById('productSupplier').value
  };

  if (data.precio <= 0) {
    showNotification('El precio debe ser mayor a 0', 'error');
    return;
  }

  if (data.stock < 0) {
    showNotification('El stock no puede ser negativo', 'error');
    return;
  }

  const action = productId ? 'update_product' : 'create_product';
  if (productId) data.id = productId;

  try {
    const response = await fetch(`/pages/api.php?action=${action}`, {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify(data)
    });

    const result = await response.json();

    if (result.success) {
      showNotification(result.message);
      closeProductModal();
      setTimeout(() => location.reload(), 1000);
    } else {
      showNotification(result.error, 'error');
    }
  } catch (error) {
    showNotification('Error al guardar producto', 'error');
  }
}

async function adjustStock(productId, currentStock) {
  const newStock = prompt(`Stock actual: ${currentStock}\nIngrese el nuevo stock:`);

  if (newStock === null) return;

  const stock = parseInt(newStock);

  if (isNaN(stock) || stock < 0) {
    showNotification('Stock inválido', 'error');
    return;
  }

  try {
    const response = await fetch('/pages/api.php?action=adjust_stock', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({ id: productId, stock: stock })
    });

    const result = await response.json();

    if (result.success) {
      showNotification(result.message);
      setTimeout(() => location.reload(), 1000);
    } else {
      showNotification(result.error, 'error');
    }
  } catch (error) {
    showNotification('Error al ajustar stock', 'error');
  }
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
