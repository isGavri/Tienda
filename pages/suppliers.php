<?php 
/*
 * SUPPLIERS.PHP - Gestión de Proveedores
 * 
 * Propósito: Administrar los proveedores (empresas que nos venden productos)
 * Útil para saber a quién comprarle cuando hay que reabastecer
 * 
 * Muestra:
 * - Lista de proveedores activos
 * - Cuántos productos tiene cada proveedor
 * - Información de contacto
 */

require_once '../includes/db.php';

$pageTitle = 'Proveedores - Sistema POS';
$currentPage = 'suppliers';

/*
 * QUERY: Obtiene proveedores con el conteo de sus productos
 * 
 * SELECT p.*: Todos los campos de proveedores
 * COUNT(pr.id) as total_productos: Cuenta cuántos productos tiene
 * 
 * FROM proveedores p: Tabla principal
 * LEFT JOIN productos pr: Une con productos
 * ON p.id = pr.proveedor_id: Relación proveedor-productos
 * AND pr.activo = TRUE: Solo cuenta productos activos
 * 
 * WHERE p.activo = TRUE: Solo proveedores activos
 * GROUP BY p.id: Agrupa por proveedor (necesario para COUNT)
 * ORDER BY p.empresa: Ordena alfabéticamente por nombre de empresa
 * 
 * Ejemplo de resultado:
 * {id: 1, empresa: "TechDistributor SA", total_productos: 8}
 */
$proveedores = fetchAll("
    SELECT p.*, COUNT(pr.id) as total_productos
    FROM proveedores p
    LEFT JOIN productos pr ON p.id = pr.proveedor_id AND pr.activo = TRUE
    WHERE p.activo = TRUE
    GROUP BY p.id
    ORDER BY p.empresa
");

include '../includes/header.php';
?>

<div class="layout">
    <?php include '../includes/sidebar.php'; ?>
    
    <main class="main-content">
        <header class="header">
            <h1 class="header-title">Gestión de Proveedores</h1>
            <div class="user-info">
                <div class="user-avatar">A</div>
                <span class="user-name">Administrador</span>
            </div>
        </header>
        
        <div class="container">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Proveedores Registrados</h2>
                    <button class="btn btn-primary" onclick="openSupplierModal()">
                        <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Nuevo Proveedor
                    </button>
                </div>
                
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre Empresa</th>
                                <th>Contacto</th>
                                <th>Teléfono</th>
                                <th>Email</th>
                                <th>Productos</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($proveedores as $prov): ?>
                            <tr>
                                <td><span class="font-medium">#<?php echo str_pad($prov['id'], 3, '0', STR_PAD_LEFT); ?></span></td>
                                <td><?php echo htmlspecialchars($prov['empresa']); ?></td>
                                <td><?php echo htmlspecialchars($prov['contacto_nombre'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($prov['telefono'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($prov['email'] ?? 'N/A'); ?></td>
                                <td><span class="badge badge-success"><?php echo $prov['total_productos']; ?> productos</span></td>
                                <td>
                                    <div class="flex gap-2">
                                        <button class="btn btn-sm btn-secondary" onclick="editSupplier(<?php echo $prov['id']; ?>)">Editar</button>
                                        <button class="btn btn-sm btn-primary" onclick="viewProducts(<?php echo $prov['id']; ?>)">Ver Productos</button>
                                    </div>
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

<!-- Supplier Modal -->
<div class="modal-overlay" id="supplierModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title" id="supplierModalTitle">Nuevo Proveedor</h3>
            <button onclick="closeSupplierModal()" style="background: none; border: none; cursor: pointer; font-size: 1.5rem; color: var(--gray-400);">&times;</button>
        </div>
        <div class="modal-body">
            <form id="supplierForm">
                <div class="form-group">
                    <label class="form-label">Nombre de la Empresa</label>
                    <input type="text" class="form-input" id="supplierName" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Nombre del Contacto</label>
                    <input type="text" class="form-input" id="supplierContact" required>
                </div>
                
                <div class="grid grid-cols-2">
                    <div class="form-group">
                        <label class="form-label">Teléfono</label>
                        <input type="tel" class="form-input" id="supplierPhone" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-input" id="supplierEmail" required>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeSupplierModal()">Cancelar</button>
            <button class="btn btn-primary" onclick="saveSupplier()">Guardar Proveedor</button>
        </div>
    </div>
</div>

<script src="/js/suppliers.js"></script>

<?php include '../includes/footer.php'; ?>
