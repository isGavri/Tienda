// User management functionality

function openUserModal(userId = null) {
  const modal = document.getElementById('userModal');
  const title = document.getElementById('userModalTitle');

  if (userId) {
    title.textContent = 'Editar Usuario';
  } else {
    title.textContent = 'Nuevo Usuario';
    document.getElementById('userForm').reset();
  }

  modal.classList.add('active');
}

function closeUserModal() {
  document.getElementById('userModal').classList.remove('active');
}

function editUser(userId) {
  openUserModal(userId);
}

function saveUser() {
  const form = document.getElementById('userForm');

  if (!form.checkValidity()) {
    form.reportValidity();
    return;
  }

  showNotification('Funcionalidad en desarrollo');
  closeUserModal();
}

function deactivateUser(userId) {
  if (confirm('¿Está seguro de desactivar este usuario?')) {
    showNotification('Funcionalidad en desarrollo');
  }
}

function activateUser(userId) {
  if (confirm('¿Está seguro de activar este usuario?')) {
    showNotification('Funcionalidad en desarrollo');
  }
}

document.getElementById('userModal')?.addEventListener('click', function(e) {
  if (e.target === this) {
    closeUserModal();
  }
});
