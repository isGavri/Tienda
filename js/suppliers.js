// Supplier management functionality

function openSupplierModal(supplierId = null) {
  const modal = document.getElementById('supplierModal');
  const title = document.getElementById('supplierModalTitle');

  if (supplierId) {
    title.textContent = 'Editar Proveedor';
  } else {
    title.textContent = 'Nuevo Proveedor';
    document.getElementById('supplierForm').reset();
  }

  modal.classList.add('active');
}

function closeSupplierModal() {
  document.getElementById('supplierModal').classList.remove('active');
}

function editSupplier(supplierId) {
  openSupplierModal(supplierId);
}

function saveSupplier() {
  const form = document.getElementById('supplierForm');

  if (!form.checkValidity()) {
    form.reportValidity();
    return;
  }

  showNotification('Funcionalidad en desarrollo');
  closeSupplierModal();
}

function viewProducts(supplierId) {
  showNotification('Funcionalidad en desarrollo');
}

document.getElementById('supplierModal')?.addEventListener('click', function(e) {
  if (e.target === this) {
    closeSupplierModal();
  }
});
