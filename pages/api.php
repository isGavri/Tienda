<?php

header('Content-Type: application/json');

require_once '../includes/db.php';

$action = $_GET['action'] ?? '';

try {
    switch ($action) {

        case 'search_product':
            $search = $_GET['search'] ?? '';

            $sql = 'SELECT p.*, c.nombre as categoria_nombre, pr.empresa as proveedor_nombre 
                    FROM productos p 
                    LEFT JOIN categorias c ON p.categoria_id = c.id 
                    LEFT JOIN proveedores pr ON p.proveedor_id = pr.id 
                    WHERE p.activo = TRUE AND (p.sku LIKE ? OR p.nombre LIKE ?)
                    LIMIT 10';

            $results = fetchAll($sql, ["%$search%", "%$search%"]);

            echo json_encode(['success' => true, 'data' => $results]);
            break;

        case 'get_customers':
            $sql = "SELECT id, CONCAT(nombre, ' ', apellido) as nombre_completo, telefono 
                    FROM clientes ORDER BY nombre";

            $results = fetchAll($sql);

            echo json_encode(['success' => true, 'data' => $results]);
            break;

        case 'process_sale':
            $data = json_decode(file_get_contents('php://input'), true);

            $pdo = getDBConnection();
            $pdo->beginTransaction();

            try {
                $sql = "INSERT INTO ordenes (cliente_id, empleado_id, metodo_pago_id, tipo_venta, estado, subtotal, impuesto_monto, total) 
                        VALUES (?, ?, ?, 'fisica', 'pagado', ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $data['cliente_id'] ?? 1,
                    $data['empleado_id'] ?? 1,
                    $data['metodo_pago_id'],
                    $data['subtotal'],
                    $data['impuesto'],
                    $data['total'],
                ]);

                $orden_id = $pdo->lastInsertId();

                foreach ($data['items'] as $item) {
                    $sql = 'INSERT INTO detalles_orden (orden_id, producto_id, cantidad, precio_unitario) 
                            VALUES (?, ?, ?, ?)';
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        $orden_id,
                        $item['id'],
                        $item['cantidad'],
                        $item['precio'],
                    ]);

                    $sql = 'UPDATE productos SET stock_actual = stock_actual - ? WHERE id = ?';
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        $item['cantidad'],
                        $item['id'],
                    ]);
                }

                $pdo->commit();

                echo json_encode(['success' => true, 'orden_id' => $orden_id]);

            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
            break;

        case 'create_product':
            $data = json_decode(file_get_contents('php://input'), true);

            if (empty($data['sku']) || empty($data['nombre'])) {
                echo json_encode(['success' => false, 'error' => 'Faltan datos obligatorios']);
                break;
            }

            $exists = fetchOne('SELECT id FROM productos WHERE sku = ?', [$data['sku']]);
            if ($exists) {
                echo json_encode(['success' => false, 'error' => 'El SKU ya existe']);
                break;
            }

            $sql = 'INSERT INTO productos (sku, nombre, categoria_id, costo_compra, precio_venta, stock_actual, proveedor_id, activo) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, TRUE)';
            execute($sql, [
                $data['sku'],
                $data['nombre'],
                $data['categoria_id'],
                $data['costo'],
                $data['precio'],
                $data['stock'],
                $data['proveedor_id'],
            ]);

            echo json_encode(['success' => true, 'message' => 'Producto creado exitosamente']);
            break;

        case 'get_product':
            $id = $_GET['id'] ?? '';
            if (empty($id)) {
                echo json_encode(['success' => false, 'error' => 'ID requerido']);
                break;
            }

            $sql = 'SELECT * FROM productos WHERE id = ?';
            $product = fetchOne($sql, [$id]);

            if (! $product) {
                echo json_encode(['success' => false, 'error' => 'Producto no encontrado']);
                break;
            }

            echo json_encode(['success' => true, 'data' => $product]);
            break;

        case 'update_product':
            $data = json_decode(file_get_contents('php://input'), true);

            if (empty($data['id']) || empty($data['sku']) || empty($data['nombre'])) {
                echo json_encode(['success' => false, 'error' => 'Faltan datos obligatorios']);
                break;
            }

            $exists = fetchOne('SELECT id FROM productos WHERE sku = ? AND id != ?', [$data['sku'], $data['id']]);
            if ($exists) {
                echo json_encode(['success' => false, 'error' => 'El SKU ya existe en otro producto']);
                break;
            }

            $sql = 'UPDATE productos SET sku = ?, nombre = ?, categoria_id = ?, costo_compra = ?, 
                    precio_venta = ?, proveedor_id = ? WHERE id = ?';
            execute($sql, [
                $data['sku'],
                $data['nombre'],
                $data['categoria_id'],
                $data['costo'],
                $data['precio'],
                $data['proveedor_id'],
                $data['id'],
            ]);

            echo json_encode(['success' => true, 'message' => 'Producto actualizado exitosamente']);
            break;

        case 'adjust_stock':
            $data = json_decode(file_get_contents('php://input'), true);

            if (empty($data['id']) || ! isset($data['stock'])) {
                echo json_encode(['success' => false, 'error' => 'Faltan datos obligatorios']);
                break;
            }

            if ($data['stock'] < 0) {
                echo json_encode(['success' => false, 'error' => 'El stock no puede ser negativo']);
                break;
            }

            $sql = 'UPDATE productos SET stock_actual = ? WHERE id = ?';
            execute($sql, [$data['stock'], $data['id']]);

            echo json_encode(['success' => true, 'message' => 'Stock actualizado exitosamente']);
            break;

        case 'create_user':
            $data = json_decode(file_get_contents('php://input'), true);

            if (empty($data['nombre']) || empty($data['email']) || empty($data['password'])) {
                echo json_encode(['success' => false, 'error' => 'Faltan datos obligatorios']);
                break;
            }

            $exists = fetchOne('SELECT id FROM empleados WHERE email = ?', [$data['email']]);
            if ($exists) {
                echo json_encode(['success' => false, 'error' => 'El email ya está registrado']);
                break;
            }

            $password_hash = password_hash($data['password'], PASSWORD_DEFAULT);

            $sql = 'INSERT INTO empleados (nombre, email, password, rol_id, activo) VALUES (?, ?, ?, ?, TRUE)';
            execute($sql, [$data['nombre'], $data['email'], $password_hash, $data['rol_id']]);

            echo json_encode(['success' => true, 'message' => 'Usuario creado exitosamente']);
            break;

        case 'get_user':
            $id = $_GET['id'] ?? '';
            if (empty($id)) {
                echo json_encode(['success' => false, 'error' => 'ID requerido']);
                break;
            }

            $sql = 'SELECT id, nombre, email, rol_id, activo FROM empleados WHERE id = ?';
            $user = fetchOne($sql, [$id]);

            if (! $user) {
                echo json_encode(['success' => false, 'error' => 'Usuario no encontrado']);
                break;
            }

            echo json_encode(['success' => true, 'data' => $user]);
            break;

        case 'update_user':
            $data = json_decode(file_get_contents('php://input'), true);

            if (empty($data['id']) || empty($data['nombre']) || empty($data['email'])) {
                echo json_encode(['success' => false, 'error' => 'Faltan datos obligatorios']);
                break;
            }

            $exists = fetchOne('SELECT id FROM empleados WHERE email = ? AND id != ?', [$data['email'], $data['id']]);
            if ($exists) {
                echo json_encode(['success' => false, 'error' => 'El email ya está registrado']);
                break;
            }

            if (! empty($data['password'])) {
                $password_hash = password_hash($data['password'], PASSWORD_DEFAULT);
                $sql = 'UPDATE empleados SET nombre = ?, email = ?, password = ?, rol_id = ? WHERE id = ?';
                execute($sql, [$data['nombre'], $data['email'], $password_hash, $data['rol_id'], $data['id']]);
            } else {
                $sql = 'UPDATE empleados SET nombre = ?, email = ?, rol_id = ? WHERE id = ?';
                execute($sql, [$data['nombre'], $data['email'], $data['rol_id'], $data['id']]);
            }

            echo json_encode(['success' => true, 'message' => 'Usuario actualizado exitosamente']);
            break;

        case 'toggle_user':
            $data = json_decode(file_get_contents('php://input'), true);

            if (empty($data['id'])) {
                echo json_encode(['success' => false, 'error' => 'ID requerido']);
                break;
            }

            $sql = 'UPDATE empleados SET activo = ? WHERE id = ?';
            execute($sql, [$data['activo'], $data['id']]);

            $estado = $data['activo'] ? 'activado' : 'desactivado';
            echo json_encode(['success' => true, 'message' => "Usuario $estado exitosamente"]);
            break;

        case 'create_supplier':
            $data = json_decode(file_get_contents('php://input'), true);

            if (empty($data['empresa']) || empty($data['contacto'])) {
                echo json_encode(['success' => false, 'error' => 'Faltan datos obligatorios']);
                break;
            }

            $sql = 'INSERT INTO proveedores (empresa, contacto_nombre, telefono, email, activo) VALUES (?, ?, ?, ?, TRUE)';
            execute($sql, [
                $data['empresa'],
                $data['contacto'],
                $data['telefono'],
                $data['email'],
            ]);

            echo json_encode(['success' => true, 'message' => 'Proveedor creado exitosamente']);
            break;

        case 'get_supplier':
            $id = $_GET['id'] ?? '';
            if (empty($id)) {
                echo json_encode(['success' => false, 'error' => 'ID requerido']);
                break;
            }

            $sql = 'SELECT * FROM proveedores WHERE id = ?';
            $supplier = fetchOne($sql, [$id]);

            if (! $supplier) {
                echo json_encode(['success' => false, 'error' => 'Proveedor no encontrado']);
                break;
            }

            echo json_encode(['success' => true, 'data' => $supplier]);
            break;

        case 'update_supplier':
            $data = json_decode(file_get_contents('php://input'), true);

            if (empty($data['id']) || empty($data['empresa']) || empty($data['contacto'])) {
                echo json_encode(['success' => false, 'error' => 'Faltan datos obligatorios']);
                break;
            }

            $sql = 'UPDATE proveedores SET empresa = ?, contacto_nombre = ?, telefono = ?, email = ? WHERE id = ?';
            execute($sql, [
                $data['empresa'],
                $data['contacto'],
                $data['telefono'],
                $data['email'],
                $data['id'],
            ]);

            echo json_encode(['success' => true, 'message' => 'Proveedor actualizado exitosamente']);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Acción no válida']);
    }

} catch (Exception $e) {
    error_log('API Error: '.$e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
