<?php 
require_once '../includes/db.php';

$pageTitle = 'Dashboard - Sistema POS';
$currentPage = 'dashboard';

$today = date('Y-m-d');

$sales_today = fetchOne("SELECT COALESCE(SUM(total), 0) as total FROM ordenes WHERE DATE(fecha_venta) = CURDATE()");
$sales_yesterday = fetchOne("SELECT COALESCE(SUM(total), 0) as total FROM ordenes WHERE DATE(fecha_venta) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)");

$orders_today = fetchOne("SELECT COUNT(*) as total FROM ordenes WHERE DATE(fecha_venta) = CURDATE()");
$orders_yesterday = fetchOne("SELECT COUNT(*) as total FROM ordenes WHERE DATE(fecha_venta) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)");

$critical_stock = fetchOne("SELECT COUNT(*) as total FROM productos WHERE stock_actual < 5 AND activo = TRUE");

$avg_ticket = $orders_today['total'] > 0 ? $sales_today['total'] / $orders_today['total'] : 0;
$avg_ticket_yesterday = $orders_yesterday['total'] > 0 ? $sales_yesterday['total'] / $orders_yesterday['total'] : 0;

$sales_change = $sales_yesterday['total'] > 0 ? (($sales_today['total'] - $sales_yesterday['total']) / $sales_yesterday['total']) * 100 : 0;
$orders_change = $orders_today['total'] - $orders_yesterday['total'];
$avg_ticket_change = $avg_ticket_yesterday > 0 ? (($avg_ticket - $avg_ticket_yesterday) / $avg_ticket_yesterday) * 100 : 0;

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
            <div class="card kpi-card" style="max-width: 400px;">
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
            
            <div class="card" style="margin-top: 2rem;">
                <div class="card-header">
                    <h2 class="card-title">Últimas Transacciones</h2>
                </div>
                
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Cliente</th>
                                <th>Total</th>
                                <th>Pago</th>
                                <th>Hora</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_transactions as $trans): ?>
                            <tr>
                                <td><span class="font-medium">#<?php echo $trans['id']; ?></span></td>
                                <td><?php echo htmlspecialchars($trans['cliente']); ?></td>
                                <td class="font-semibold">$<?php echo number_format($trans['total'], 2); ?></td>
                                <td><span class="badge badge-success"><?php echo htmlspecialchars($trans['metodo_pago']); ?></span></td>
                                <td><?php echo $trans['hora']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($recent_transactions)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-gray-500">No hay transacciones hoy</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="card" style="margin-top: 2rem;">
                <div class="card-header">
                    <h2 class="card-title">Productos con Stock Bajo</h2>
                </div>
                
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>SKU</th>
                                <th>Producto</th>
                                <th>Stock</th>
                                <th>Proveedor</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($low_stock_products as $product): ?>
                            <tr>
                                <td><span class="font-medium"><?php echo htmlspecialchars($product['sku']); ?></span></td>
                                <td><?php echo htmlspecialchars($product['nombre']); ?></td>
                                <td>
                                    <span class="badge <?php echo $product['stock_actual'] < 3 ? 'badge-danger' : 'badge-warning'; ?>">
                                        <?php echo $product['stock_actual']; ?> unidades
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($product['proveedor'] ?? 'N/A'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
