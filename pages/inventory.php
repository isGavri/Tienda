<?php 
require_once '../includes/db.php';

$pageTitle = 'Inventario - Sistema POS';
$currentPage = 'inventory';

$productos = fetchAll("
    SELECT p.*, c.nombre as categoria_nombre, pr.empresa as proveedor_nombre
    FROM productos p
    LEFT JOIN categorias c ON p.categoria_id = c.id
    LEFT JOIN proveedores pr ON p.proveedor_id = pr.id
    WHERE p.activo = TRUE
    ORDER BY p.nombre
");

$categorias = fetchAll("SELECT * FROM categorias ORDER BY nombre");

$proveedores = fetchAll("SELECT * FROM proveedores WHERE activo = TRUE ORDER BY empresa");

include '../includes/header.php';
?>

<div class="layout">
    <?php include '../includes/sidebar.php'; ?>
    
    <main class="main-content">
        <header class="header">
            <h1 class="header-title">Gestión de Inventario</h1>
            <div class="user-info">
                <div class="user-avatar">A</div>
                <span class="user-name">Administrador</span>
            </div>
        </header>
        
        <div class="container">
            <div class="card">
                <div class="card-header">
                    <div style="flex: 1;">
                        <input type="text" 
                               id="inventorySearch" 
                               class="search-input" 
                               placeholder="Buscar por SKU o nombre..."
                               style="max-width: 400px;">
                    </div>
                    <div class="flex gap-2">
                        <select class="form-select" id="categoryFilter" style="width: auto;">
                            <option value="">Todas las categorías</option>
                            <?php foreach ($categorias as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button class="btn btn-primary" onclick="openProductModal()">
                            <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Nuevo Producto
                        </button>
                    </div>
                </div>
                
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>SKU</th>
                                <th>Producto</th>
                                <th>Precio</th>
                                <th>Stock</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($productos as $prod): ?>
                            <tr data-category="<?php echo $prod['categoria_id']; ?>">
                                <td><?php echo htmlspecialchars($prod['sku']); ?></td>
                                <td><?php echo htmlspecialchars($prod['nombre']); ?></td>
                                <td>$<?php echo number_format($prod['precio_venta'], 2); ?></td>
                                <td>
                                    <span class="badge <?php echo $prod['stock_actual'] < 3 ? 'badge-danger' : ($prod['stock_actual'] < 5 ? 'badge-warning' : 'badge-success'); ?>">
                                        <?php echo $prod['stock_actual']; ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-secondary" onclick="editProduct(<?php echo $prod['id']; ?>)">Editar</button>
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

<!-- Product Modal -->
<div class="modal-overlay" id="productModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title" id="productModalTitle">Nuevo Producto</h3>
            <button onclick="closeProductModal()" style="background: none; border: none; cursor: pointer; font-size: 1.5rem; color: var(--gray-400);">&times;</button>
        </div>
        <div class="modal-body">
            <form id="productForm">
                <input type="hidden" id="productId">
                <div class="form-group">
                    <label class="form-label">SKU</label>
                    <input type="text" class="form-input" id="productSKU" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Nombre</label>
                    <input type="text" class="form-input" id="productName" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Categoría</label>
                    <select class="form-select" id="productCategory" required>
                        <option value="">Seleccionar</option>
                        <?php foreach ($categorias as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Precio</label>
                    <input type="number" step="0.01" class="form-input" id="productPrice" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Stock</label>
                    <input type="number" class="form-input" id="productStock" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Proveedor</label>
                    <select class="form-select" id="productSupplier" required>
                        <option value="">Seleccionar</option>
                        <?php foreach ($proveedores as $prov): ?>
                        <option value="<?php echo $prov['id']; ?>"><?php echo htmlspecialchars($prov['empresa']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeProductModal()">Cancelar</button>
            <button class="btn btn-primary" onclick="saveProduct()">Guardar Producto</button>
        </div>
    </div>
</div>

<script src="/js/inventory.js"></script>

<?php include '../includes/footer.php'; ?>
