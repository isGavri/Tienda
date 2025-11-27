<?php 
/*
 * POS.PHP - Punto de Venta (Point of Sale)
 * 
 * Propósito: Interfaz principal para procesar ventas
 * Es donde el cajero escanea/busca productos y cobra a los clientes
 * 
 * Funcionamiento:
 * 1. El cajero busca productos (consulta a la BD en tiempo real vía AJAX)
 * 2. Agrega productos al carrito (solo en memoria del navegador)
 * 3. Selecciona cliente y método de pago
 * 4. Al cobrar, envía todo a api.php que procesa la venta
 * 
 * La mayoría de la lógica está en /js/pos.js
 * Este archivo solo trae los clientes de la BD para el selector
 */

require_once '../includes/db.php';

$pageTitle = 'Punto de Venta - Sistema POS';
$currentPage = 'pos';

/*
 * QUERY: Obtiene todos los clientes para el selector
 * 
 * CONCAT(nombre, ' ', COALESCE(apellido, '')): Junta nombre y apellido
 * COALESCE(apellido, ''): Si no hay apellido, usa string vacío
 * ORDER BY nombre: Ordena alfabéticamente
 * 
 * Esto llena el <select> de clientes para elegir a quién venderle
 */
$clientes = fetchAll("SELECT id, CONCAT(nombre, ' ', COALESCE(apellido, '')) as nombre_completo, telefono FROM clientes ORDER BY nombre");

include '../includes/header.php';
?>

<div class="layout">
    <?php include '../includes/sidebar.php'; ?>
    
    <main class="main-content">
        <header class="header">
            <h1 class="header-title">Punto de Venta</h1>
            <div class="user-info">
                <div class="user-avatar">A</div>
                <span class="user-name">Administrador</span>
            </div>
        </header>
        
        <div class="container">
            <div class="pos-container">
                <!-- Product Search & Cart Items -->
                <div class="card" style="display: flex; flex-direction: column;">
                    <div class="product-search">
                        <input type="text" 
                               id="productSearch" 
                               class="search-input" 
                               placeholder="Buscar por SKU o nombre del producto..."
                               autofocus>
                    </div>
                    
                    <div class="cart-items" id="cartItems">
                        <div style="text-align: center; padding: 3rem; color: var(--gray-400);">
                            <svg style="width: 64px; height: 64px; margin: 0 auto 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            <p>Escanea o busca productos para comenzar</p>
                        </div>
                    </div>
                </div>
                
                <!-- Cart Summary & Checkout -->
                <div class="card" style="display: flex; flex-direction: column;">
                    <div class="card-header">
                        <h2 class="card-title">Resumen de Venta</h2>
                    </div>
                    
                    <!-- Customer Selection -->
                    <div class="form-group">
                        <label class="form-label">Cliente</label>
                        <div class="flex gap-2">
                            <select class="form-select" id="customerSelect">
                                <?php foreach ($clientes as $cliente): ?>
                                <option value="<?php echo $cliente['id']; ?>">
                                    <?php echo htmlspecialchars($cliente['nombre_completo']); ?>
                                    <?php echo $cliente['telefono'] ? ' - ' . $cliente['telefono'] : ''; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <button class="btn btn-secondary" onclick="openCustomerModal()">
                                <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                    <div class="cart-summary" style="margin-top: auto;">
                        <div class="summary-row">
                            <span class="text-gray-600">Subtotal:</span>
                            <span class="font-medium" id="subtotal">$0.00</span>
                        </div>
                        <div class="summary-row">
                            <span class="text-gray-600">IVA (16%):</span>
                            <span class="font-medium" id="tax">$0.00</span>
                        </div>
                        <div class="summary-row total">
                            <span>Total:</span>
                            <span id="total">$0.00</span>
                        </div>
                    </div>
                    
                    <!-- Payment Method -->
                    <div class="form-group" style="margin-top: 1rem;">
                        <label class="form-label">Método de Pago</label>
                        <div class="grid grid-cols-2 gap-2">
                            <button class="btn btn-secondary" id="paymentCash" onclick="selectPayment('cash', 1)">
                                <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                                Efectivo
                            </button>
                            <button class="btn btn-secondary" id="paymentCard" onclick="selectPayment('card', 2)">
                                <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                </svg>
                                Tarjeta
                            </button>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="grid grid-cols-2 gap-2" style="margin-top: 1rem;">
                        <button class="btn btn-danger" onclick="clearCart()">
                            <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            Limpiar
                        </button>
                        <button class="btn btn-success" onclick="processCheckout()" id="checkoutBtn" disabled>
                            <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Cobrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Customer Search Modal -->
<div class="modal-overlay" id="customerModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Buscar Cliente</h3>
            <button onclick="closeCustomerModal()" style="background: none; border: none; cursor: pointer; font-size: 1.5rem; color: var(--gray-400);">&times;</button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <input type="text" class="search-input" id="customerSearchInput" placeholder="Buscar por nombre o teléfono...">
            </div>
            <div style="margin-top: 1rem;" id="customerList">
                <?php foreach ($clientes as $cliente): ?>
                <div class="cart-item" style="cursor: pointer;" onclick="selectCustomer(<?php echo $cliente['id']; ?>)">
                    <div>
                        <div class="font-medium"><?php echo htmlspecialchars($cliente['nombre_completo']); ?></div>
                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($cliente['telefono'] ?? 'Sin teléfono'); ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeCustomerModal()">Cancelar</button>
        </div>
    </div>
</div>

<script src="/js/pos.js"></script>

<?php include '../includes/footer.php'; ?>
