async function openUserModal(userId = null) {
  const modal = document.getElementById('userModal');
  const title = document.getElementById('userModalTitle');
  const passwordField = document.getElementById('userPassword');

  if (userId) {
    title.textContent = 'Editar Usuario';
    passwordField.required = false;
    passwordField.placeholder = 'Dejar en blanco para no cambiar';
    
    try {
      const response = await fetch(`/pages/api.php?action=get_user&id=${userId}`);
      const result = await response.json();
      
      if (result.success) {
        const u = result.data;
        document.getElementById('userId').value = u.id;
        document.getElementById('userName').value = u.nombre;
        document.getElementById('userEmail').value = u.email;
        document.getElementById('userRole').value = u.rol_id;
        passwordField.value = '';
      } else {
        showNotification(result.error, 'error');
        return;
      }
    } catch (error) {
      showNotification('Error al cargar usuario', 'error');
      return;
    }
  } else {
    title.textContent = 'Nuevo Usuario';
    passwordField.required = true;
    passwordField.placeholder = '';
    document.getElementById('userForm').reset();
    document.getElementById('userId').value = '';
  }

  modal.classList.add('active');
}

function closeUserModal() {
  document.getElementById('userModal').classList.remove('active');
}

function editUser(userId) {
  openUserModal(userId);
}

async function saveUser() {
  const form = document.getElementById('userForm');
  const userId = document.getElementById('userId').value;
  
  if (userId) {
    document.getElementById('userPassword').required = false;
  }

  if (!form.checkValidity()) {
    form.reportValidity();
    return;
  }

  const data = {
    nombre: document.getElementById('userName').value,
    email: document.getElementById('userEmail').value,
    password: document.getElementById('userPassword').value,
    rol_id: document.getElementById('userRole').value
  };

  const action = userId ? 'update_user' : 'create_user';
  if (userId) {
    data.id = userId;
    if (!data.password) delete data.password;
  }

  try {
    const response = await fetch(`/pages/api.php?action=${action}`, {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify(data)
    });

    const result = await response.json();

    if (result.success) {
      showNotification(result.message);
      closeUserModal();
      setTimeout(() => location.reload(), 1000);
    } else {
      showNotification(result.error, 'error');
    }
  } catch (error) {
    showNotification('Error al guardar usuario', 'error');
  }
}

async function deactivateUser(userId) {
  if (confirm('¿Está seguro de desactivar este usuario?')) {
    await toggleUserStatus(userId, false);
  }
}

async function activateUser(userId) {
  if (confirm('¿Está seguro de activar este usuario?')) {
    await toggleUserStatus(userId, true);
  }
}

async function toggleUserStatus(userId, activo) {
  try {
    const response = await fetch('/pages/api.php?action=toggle_user', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({ id: userId, activo: activo })
    });

    const result = await response.json();

    if (result.success) {
      showNotification(result.message);
      setTimeout(() => location.reload(), 1000);
    } else {
      showNotification(result.error, 'error');
    }
  } catch (error) {
    showNotification('Error al cambiar estado del usuario', 'error');
  }
}

document.getElementById('userModal')?.addEventListener('click', function(e) {
  if (e.target === this) {
    closeUserModal();
  }
});
