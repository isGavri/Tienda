<?php 
require_once '../includes/db.php';

$pageTitle = 'Usuarios - Sistema POS';
$currentPage = 'users';

$empleados = fetchAll("
    SELECT e.*, r.nombre as rol_nombre
    FROM empleados e
    LEFT JOIN roles r ON e.rol_id = r.id
    ORDER BY e.nombre
");

$roles = fetchAll("SELECT * FROM roles");

include '../includes/header.php';
?>

<div class="layout">
    <?php include '../includes/sidebar.php'; ?>
    
    <main class="main-content">
        <header class="header">
            <h1 class="header-title">Gestión de Usuarios</h1>
            <div class="user-info">
                <div class="user-avatar">A</div>
                <span class="user-name">Administrador</span>
            </div>
        </header>
        
        <div class="container">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Empleados y Usuarios del Sistema</h2>
                    <button class="btn btn-primary" onclick="openUserModal()">
                        <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Nuevo Usuario
                    </button>
                </div>
                
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Email</th>
                                <th>Rol</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($empleados as $emp): ?>
                            <tr <?php echo !$emp['activo'] ? 'style="opacity: 0.6;"' : ''; ?>>
                                <td><?php echo htmlspecialchars($emp['nombre']); ?></td>
                                <td><?php echo htmlspecialchars($emp['email']); ?></td>
                                <td>
                                    <span class="badge <?php echo $emp['rol_id'] == 1 ? 'badge-danger' : 'badge-success'; ?>">
                                        <?php echo htmlspecialchars($emp['rol_nombre']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($emp['activo']): ?>
                                    <span class="badge badge-success">Activo</span>
                                    <?php else: ?>
                                    <span class="badge" style="background-color: var(--gray-200); color: var(--gray-600);">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-secondary" onclick="editUser(<?php echo $emp['id']; ?>)">Editar</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- User Modal -->
<div class="modal-overlay" id="userModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title" id="userModalTitle">Nuevo Usuario</h3>
            <button onclick="closeUserModal()" style="background: none; border: none; cursor: pointer; font-size: 1.5rem; color: var(--gray-400);">&times;</button>
        </div>
        <div class="modal-body">
            <form id="userForm">
                <input type="hidden" id="userId">
                <div class="form-group">
                    <label class="form-label">Nombre Completo</label>
                    <input type="text" class="form-input" id="userName" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-input" id="userEmail" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Contraseña</label>
                    <input type="password" class="form-input" id="userPassword" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Rol</label>
                    <select class="form-select" id="userRole" required>
                        <option value="">Seleccionar rol</option>
                        <?php foreach ($roles as $rol): ?>
                        <option value="<?php echo $rol['id']; ?>"><?php echo htmlspecialchars($rol['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeUserModal()">Cancelar</button>
            <button class="btn btn-primary" onclick="saveUser()">Guardar Usuario</button>
        </div>
    </div>
</div>

<script src="/js/users.js"></script>

<?php include '../includes/footer.php'; ?>
