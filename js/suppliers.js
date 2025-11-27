async function openSupplierModal(supplierId = null) {
  const modal = document.getElementById('supplierModal');
  const title = document.getElementById('supplierModalTitle');

  if (supplierId) {
    title.textContent = 'Editar Proveedor';
    try {
      const response = await fetch(`/pages/api.php?action=get_supplier&id=${supplierId}`);
      const result = await response.json();
      
      if (result.success) {
        const s = result.data;
        document.getElementById('supplierId').value = s.id;
        document.getElementById('supplierName').value = s.empresa;
        document.getElementById('supplierContact').value = s.contacto_nombre;
        document.getElementById('supplierPhone').value = s.telefono || '';
        document.getElementById('supplierEmail').value = s.email || '';
      } else {
        showNotification(result.error, 'error');
        return;
      }
    } catch (error) {
      showNotification('Error al cargar proveedor', 'error');
      return;
    }
  } else {
    title.textContent = 'Nuevo Proveedor';
    document.getElementById('supplierForm').reset();
    document.getElementById('supplierId').value = '';
  }

  modal.classList.add('active');
}

function closeSupplierModal() {
  document.getElementById('supplierModal').classList.remove('active');
}

function editSupplier(supplierId) {
  openSupplierModal(supplierId);
}

async function saveSupplier() {
  const form = document.getElementById('supplierForm');

  if (!form.checkValidity()) {
    form.reportValidity();
    return;
  }

  const supplierId = document.getElementById('supplierId').value;
  const data = {
    empresa: document.getElementById('supplierName').value,
    contacto: document.getElementById('supplierContact').value,
    telefono: document.getElementById('supplierPhone').value,
    email: document.getElementById('supplierEmail').value
  };

  const action = supplierId ? 'update_supplier' : 'create_supplier';
  if (supplierId) data.id = supplierId;

  try {
    const response = await fetch(`/pages/api.php?action=${action}`, {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify(data)
    });

    const result = await response.json();

    if (result.success) {
      showNotification(result.message);
      closeSupplierModal();
      setTimeout(() => location.reload(), 1000);
    } else {
      showNotification(result.error, 'error');
    }
  } catch (error) {
    showNotification('Error al guardar proveedor', 'error');
  }
}

function viewProducts(supplierId) {
  window.location.href = `/pages/inventory.php?proveedor=${supplierId}`;
}

document.getElementById('supplierModal')?.addEventListener('click', function(e) {
  if (e.target === this) {
    closeSupplierModal();
  }
});
