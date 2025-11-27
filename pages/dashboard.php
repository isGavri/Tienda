<?php 
/*
 * DASHBOARD.PHP - Panel principal del sistema POS
 * 
 * Propósito: Mostrar los KPIs (indicadores clave) del negocio en tiempo real
 * Es lo primero que ve el usuario al entrar al sistema
 * 
 * KPIs que muestra:
 * 1. Ventas del día (total en dinero)
 * 2. Órdenes del día (cantidad de ventas)
 * 3. Stock crítico (productos con menos de 5 unidades)
 * 4. Ticket promedio (cuánto gasta cada cliente en promedio)
 * 
 * También muestra:
 * - Últimas 5 transacciones del día
 * - Productos con stock bajo que necesitan reabastecerse
 */

require_once '../includes/db.php';

$pageTitle = 'Dashboard - Sistema POS';
$currentPage = 'dashboard';

// La fecha actual (aunque ya no la usamos en queries por timezone)
$today = date('Y-m-d');

/*
 * === KPI 1: VENTAS DEL DÍA ===
 * 
 * QUERY: Suma el total de todas las ventas de hoy
 * 
 * COALESCE(SUM(total), 0): Si no hay ventas, retorna 0 en vez de NULL
 * DATE(fecha_venta) = CURDATE(): Solo ventas de hoy
 * CURDATE() es mejor que date('Y-m-d') porque usa la fecha del servidor MySQL
 */
$sales_today = fetchOne("SELECT COALESCE(SUM(total), 0) as total FROM ordenes WHERE DATE(fecha_venta) = CURDATE()");

// Ventas de ayer (para comparar y mostrar el porcentaje de cambio)
$sales_yesterday = fetchOne("SELECT COALESCE(SUM(total), 0) as total FROM ordenes WHERE DATE(fecha_venta) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)");

/*
 * === KPI 2: ÓRDENES DEL DÍA ===
 * 
 * QUERY: Cuenta cuántas ventas se hicieron hoy
 * COUNT(*): Cuenta todos los registros que coincidan
 */
$orders_today = fetchOne("SELECT COUNT(*) as total FROM ordenes WHERE DATE(fecha_venta) = CURDATE()");
$orders_yesterday = fetchOne("SELECT COUNT(*) as total FROM ordenes WHERE DATE(fecha_venta) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)");

/*
 * === KPI 3: STOCK CRÍTICO ===
 * 
 * QUERY: Cuenta cuántos productos tienen menos de 5 unidades
 * stock_actual < 5: Productos que se están acabando
 * activo = TRUE: Solo productos activos (no descontinuados)
 */
$critical_stock = fetchOne("SELECT COUNT(*) as total FROM productos WHERE stock_actual < 5 AND activo = TRUE");

/*
 * === KPI 4: TICKET PROMEDIO ===
 * 
 * Se calcula dividiendo: Total vendido / Número de órdenes
 * Ej: Si vendiste $1000 en 5 órdenes = $200 por cliente
 */
$avg_ticket = $orders_today['total'] > 0 ? $sales_today['total'] / $orders_today['total'] : 0;
$avg_ticket_yesterday = $orders_yesterday['total'] > 0 ? $sales_yesterday['total'] / $orders_yesterday['total'] : 0;

// Cálculo de porcentajes de cambio vs ayer
$sales_change = $sales_yesterday['total'] > 0 ? (($sales_today['total'] - $sales_yesterday['total']) / $sales_yesterday['total']) * 100 : 0;
$orders_change = $orders_today['total'] - $orders_yesterday['total'];
$avg_ticket_change = $avg_ticket_yesterday > 0 ? (($avg_ticket - $avg_ticket_yesterday) / $avg_ticket_yesterday) * 100 : 0;

/*
 * === ÚLTIMAS TRANSACCIONES DEL DÍA ===
 * 
 * QUERY: Muestra las últimas 5 ventas de hoy con toda su info
 * 
 * SELECT o.id: ID de la orden
 * CONCAT(c.nombre, ' ', COALESCE(c.apellido, '')): Nombre completo del cliente
 * COUNT(DISTINCT d.id): Cuenta cuántos productos diferentes se vendieron
 * o.total: Total de la venta
 * mp.nombre: Método de pago (Efectivo/Tarjeta)
 * TIME(o.fecha_venta): Solo la hora de la venta
 * 
 * LEFT JOIN: Trae datos de otras tablas relacionadas
 * - clientes: Para saber quién compró
 * - detalles_orden: Para contar cuántos items
 * - metodos_pago: Para saber cómo pagó
 * 
 * GROUP BY o.id: Agrupa por orden (necesario para COUNT)
 * ORDER BY o.fecha_venta DESC: Más recientes primero
 * LIMIT 5: Solo las últimas 5
 */
$recent_transactions = fetchAll("
    SELECT o.id, 
           CONCAT(c.nombre, ' ', COALESCE(c.apellido, '')) as cliente,
           COUNT(DISTINCT d.id) as items,
           o.total,
           mp.nombre as metodo_pago,
           TIME(o.fecha_venta) as hora
    FROM ordenes o
    LEFT JOIN clientes c ON o.cliente_id = c.id
    LEFT JOIN detalles_orden d ON o.id = d.orden_id
    LEFT JOIN metodos_pago mp ON o.metodo_pago_id = mp.id
    WHERE DATE(o.fecha_venta) = CURDATE()
    GROUP BY o.id
    ORDER BY o.fecha_venta DESC
    LIMIT 5
");

/*
 * === PRODUCTOS CON STOCK BAJO ===
 * 
 * QUERY: Lista productos que necesitan reabastecerse
 * 
 * p.sku: Código del producto
 * p.nombre: Nombre del producto
 * p.stock_actual: Cuántas unidades quedan
 * pr.empresa: Nombre del proveedor (para saber a quién comprarle)
 * 
 * WHERE p.stock_actual < 5: Solo productos casi agotados
 * AND p.activo = TRUE: Solo productos activos
 * ORDER BY p.stock_actual ASC: Los más críticos primero
 * LIMIT 10: Máximo 10 productos
 */
$low_stock_products = fetchAll("
    SELECT p.sku, p.nombre, p.stock_actual,
           pr.empresa as proveedor
    FROM productos p
    LEFT JOIN proveedores pr ON p.proveedor_id = pr.id
    WHERE p.stock_actual < 5 AND p.activo = TRUE
    ORDER BY p.stock_actual ASC
    LIMIT 10
");

include '../includes/header.php';
?>

<div class="layout">
    <?php include '../includes/sidebar.php'; ?>
    
    <main class="main-content">
        <header class="header">
            <h1 class="header-title">Dashboard</h1>
            <div class="user-info">
                <div class="user-avatar">A</div>
                <span class="user-name">Administrador</span>
            </div>
        </header>
        
        <div class="container">
            <!-- KPIs -->
            <div class="grid grid-cols-4">
                <div class="card kpi-card">
                    <div class="card-header">
                        <span class="card-title">Ventas del Día</span>
                    </div>
                    <div class="card-value kpi-success">$<?php echo number_format($sales_today['total'], 2); ?></div>
                    <div class="card-subtitle">
                        <span class="badge <?php echo $sales_change >= 0 ? 'badge-success' : 'badge-danger'; ?>">
                            <?php echo $sales_change >= 0 ? '↑' : '↓'; ?> <?php echo abs(round($sales_change, 1)); ?>%
                        </span> vs. ayer
                    </div>
                </div>
                
                <div class="card kpi-card">
                    <div class="card-header">
                        <span class="card-title">Órdenes Hoy</span>
                    </div>
                    <div class="card-value"><?php echo $orders_today['total']; ?></div>
                    <div class="card-subtitle">
                        <span class="badge <?php echo $orders_change >= 0 ? 'badge-success' : 'badge-danger'; ?>">
                            <?php echo $orders_change >= 0 ? '↑' : '↓'; ?> <?php echo abs($orders_change); ?>
                        </span> desde ayer
                    </div>
                </div>
                
                <div class="card kpi-card">
                    <div class="card-header">
                        <span class="card-title">Stock Crítico</span>
                    </div>
                    <div class="card-value kpi-danger"><?php echo $critical_stock['total']; ?></div>
                    <div class="card-subtitle">
                        <span class="badge badge-danger">Requiere atención</span>
                    </div>
                </div>
                
                <div class="card kpi-card">
                    <div class="card-header">
                        <span class="card-title">Ticket Promedio</span>
                    </div>
                    <div class="card-value">$<?php echo number_format($avg_ticket, 2); ?></div>
                    <div class="card-subtitle">
                        <span class="badge <?php echo $avg_ticket_change >= 0 ? 'badge-success' : 'badge-warning'; ?>">
                            <?php echo $avg_ticket_change >= 0 ? '↑' : '↓'; ?> <?php echo abs(round($avg_ticket_change, 1)); ?>%
                        </span> vs. ayer
                    </div>
                </div>
            </div>
            
            <!-- Recent Transactions -->
            <div class="grid grid-cols-1" style="margin-top: 2rem;">
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Últimas Transacciones</h2>
                        <a href="#" class="btn btn-secondary btn-sm">Ver todas</a>
                    </div>
                    
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID Orden</th>
                                    <th>Cliente</th>
                                    <th>Productos</th>
                                    <th>Total</th>
                                    <th>Método Pago</th>
                                    <th>Hora</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_transactions as $trans): ?>
                                <tr>
                                    <td><span class="font-medium">#<?php echo $trans['id']; ?></span></td>
                                    <td><?php echo htmlspecialchars($trans['cliente']); ?></td>
                                    <td><?php echo $trans['items']; ?> items</td>
                                    <td class="font-semibold">$<?php echo number_format($trans['total'], 2); ?></td>
                                    <td><span class="badge badge-success"><?php echo htmlspecialchars($trans['metodo_pago']); ?></span></td>
                                    <td class="text-gray-500"><?php echo $trans['hora']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($recent_transactions)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-gray-500">No hay transacciones hoy</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Low Stock Alert -->
            <div class="grid grid-cols-1" style="margin-top: 2rem;">
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Productos con Stock Bajo</h2>
                        <a href="inventory.php" class="btn btn-primary btn-sm">Ver Inventario</a>
                    </div>
                    
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>SKU</th>
                                    <th>Producto</th>
                                    <th>Stock Actual</th>
                                    <th>Stock Mínimo</th>
                                    <th>Proveedor</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($low_stock_products as $product): ?>
                                <tr class="stock-low">
                                    <td><span class="font-medium"><?php echo htmlspecialchars($product['sku']); ?></span></td>
                                    <td><?php echo htmlspecialchars($product['nombre']); ?></td>
                                    <td>
                                        <span class="badge <?php echo $product['stock_actual'] < 3 ? 'badge-danger' : 'badge-warning'; ?>">
                                            <?php echo $product['stock_actual']; ?> unidades
                                        </span>
                                    </td>
                                    <td>5</td>
                                    <td class="text-gray-600"><?php echo htmlspecialchars($product['proveedor'] ?? 'N/A'); ?></td>
                                    <td><button class="btn btn-sm btn-primary">Reabastecer</button></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
