<?php
/*
 * ================================================================================================
 * API.PHP - API REST para operaciones del sistema POS
 * ================================================================================================
 * 
 * PROPÓSITO GENERAL:
 * ------------------
 * Este archivo es el "backend" del sistema. Maneja todas las peticiones AJAX desde el frontend.
 * Es como el "mesero" que recibe pedidos del navegador y trae/guarda datos en la base de datos.
 * 
 * ACCIONES DISPONIBLES:
 * ---------------------
 * 1. search_product   - Busca productos en tiempo real para el POS
 * 2. get_customers    - Obtiene la lista de clientes para seleccionar
 * 3. process_sale     - Procesa una venta completa (orden + detalles + actualiza stock)
 * 
 * CÓMO FUNCIONA:
 * --------------
 * 1. El frontend hace una petición: fetch('/pages/api.php?action=search_product&search=mouse')
 * 2. PHP recibe el parámetro 'action' y ejecuta el case correspondiente
 * 3. Se ejecuta el query SQL correspondiente
 * 4. Se retorna la respuesta en formato JSON: {success: true, data: [...]}
 * 5. El frontend (JavaScript) recibe y procesa la respuesta
 * 
 * CÓMO AGREGAR NUEVAS ACCIONES:
 * ------------------------------
 * 
 * Ejemplo: Agregar un endpoint para crear productos
 * 
 * case 'create_product':
 *     // 1. Obtén los datos enviados desde el frontend
 *     $data = json_decode(file_get_contents('php://input'), true);
 *     
 *     // 2. Valida que vengan los datos necesarios
 *     if (!$data['sku'] || !$data['nombre']) {
 *         echo json_encode(['success' => false, 'error' => 'Faltan datos']);
 *         break;
 *     }
 *     
 *     // 3. Ejecuta el INSERT
 *     $sql = "INSERT INTO productos (sku, nombre, precio_venta, stock_actual) VALUES (?, ?, ?, ?)";
 *     execute($sql, [$data['sku'], $data['nombre'], $data['precio'], $data['stock']]);
 *     
 *     // 4. Retorna éxito
 *     echo json_encode(['success' => true, 'message' => 'Producto creado']);
 *     break;
 * 
 * TIPS PARA TRABAJAR CON ESTA API:
 * ---------------------------------
 * - Siempre retorna JSON: echo json_encode([...])
 * - Usa prepared statements (?) para evitar SQL injection
 * - Captura errores con try/catch
 * - Para INSERT/UPDATE/DELETE usa transacciones si afectas múltiples tablas
 * - Valida los datos antes de usarlos
 * - Retorna mensajes claros de error: ['success' => false, 'error' => 'mensaje']
 * 
 * ESTRUCTURA DE RESPUESTAS:
 * -------------------------
 * Éxito:  {'success': true, 'data': [...]}
 * Error:  {'success': false, 'error': 'Descripción del error'}
 * 
 * ================================================================================================
 */

// Le dice al navegador que vamos a retornar JSON (importante para que JavaScript lo interprete bien)
header('Content-Type: application/json');

// Importa las funciones de base de datos (fetchAll, fetchOne, execute, etc.)
require_once '../includes/db.php';

// Obtiene la acción solicitada desde la URL (?action=lo_que_sea)
// Ej: api.php?action=search_product → $action = 'search_product'
$action = $_GET['action'] ?? '';

// Bloque try-catch para capturar cualquier error que ocurra
try {
    // Switch que ejecuta la acción solicitada
    switch($action) {
        
        /*
         * ========================================================================
         * ACCIÓN 1: SEARCH_PRODUCT - Búsqueda de productos en tiempo real
         * ========================================================================
         * 
         * LLAMADA DESDE JAVASCRIPT:
         * fetch(`/pages/api.php?action=search_product&search=${searchTerm}`)
         * 
         * PARÁMETROS:
         * - search: Texto a buscar (puede ser SKU o nombre de producto)
         * 
         * RETORNA:
         * Array de productos que coincidan con la búsqueda
         * 
         * EJEMPLO DE USO:
         * Usuario escribe "mouse" en el POS → Se buscan todos los productos
         * que tengan "mouse" en el SKU o nombre → Se muestran en tiempo real
         */
        case 'search_product':
            // Obtiene el término de búsqueda del query string
            $search = $_GET['search'] ?? '';
            
            /*
             * QUERY EXPLICADO:
             * ----------------
             * SELECT p.*                         → Trae todos los campos de productos
             * c.nombre as categoria_nombre       → Trae el nombre de la categoría (ej: "Laptops")
             * pr.empresa as proveedor_nombre     → Trae el nombre del proveedor (ej: "TechDistributor SA")
             * 
             * FROM productos p                   → Tabla principal (alias 'p')
             * LEFT JOIN categorias c             → Une con categorías (LEFT = aunque no tenga categoría)
             *   ON p.categoria_id = c.id         → Relaciona por el ID
             * LEFT JOIN proveedores pr           → Une con proveedores
             *   ON p.proveedor_id = pr.id        → Relaciona por el ID
             * 
             * WHERE p.activo = TRUE              → Solo productos activos (no eliminados)
             * AND (p.sku LIKE ? OR p.nombre LIKE ?) → Busca en SKU o nombre
             * 
             * LIMIT 10                           → Máximo 10 resultados (para no saturar la UI)
             * 
             * PREPARED STATEMENTS:
             * Los símbolos ? se reemplazan de forma SEGURA con los valores del array
             * "%$search%" significa: busca cualquier cosa que CONTENGA el texto
             * Ej: search="mou" encontrará "Mouse", "Mouse Gamer", etc.
             */
            $sql = "SELECT p.*, c.nombre as categoria_nombre, pr.empresa as proveedor_nombre 
                    FROM productos p 
                    LEFT JOIN categorias c ON p.categoria_id = c.id 
                    LEFT JOIN proveedores pr ON p.proveedor_id = pr.id 
                    WHERE p.activo = TRUE AND (p.sku LIKE ? OR p.nombre LIKE ?)
                    LIMIT 10";
            
            // Ejecuta el query y obtiene todos los resultados
            $results = fetchAll($sql, ["%$search%", "%$search%"]);
            
            // Retorna los resultados en formato JSON
            // El frontend recibirá: {success: true, data: [{id: 1, nombre: "Mouse", ...}, ...]}
            echo json_encode(['success' => true, 'data' => $results]);
            break;
            
        /*
         * ========================================================================
         * ACCIÓN 2: GET_CUSTOMERS - Obtener lista de clientes
         * ========================================================================
         * 
         * LLAMADA DESDE JAVASCRIPT:
         * fetch('/pages/api.php?action=get_customers')
         * 
         * PARÁMETROS:
         * Ninguno
         * 
         * RETORNA:
         * Array de todos los clientes con id, nombre_completo y teléfono
         * 
         * EJEMPLO DE USO:
         * Al abrir el POS, se carga la lista de clientes para el selector
         */
        case 'get_customers':
            /*
             * QUERY EXPLICADO:
             * ----------------
             * CONCAT(nombre, ' ', apellido)  → Junta nombre y apellido con un espacio
             *                                   Ej: "María" + " " + "González" = "María González"
             * ORDER BY nombre                → Ordena alfabéticamente por nombre
             */
            $sql = "SELECT id, CONCAT(nombre, ' ', apellido) as nombre_completo, telefono 
                    FROM clientes ORDER BY nombre";
            
            // Ejecuta el query y obtiene todos los clientes
            $results = fetchAll($sql);
            
            // Retorna en formato JSON
            echo json_encode(['success' => true, 'data' => $results]);
            break;
            
        /*
         * ========================================================================
         * ACCIÓN 3: PROCESS_SALE - Procesar una venta completa
         * ========================================================================
         * 
         * LLAMADA DESDE JAVASCRIPT:
         * fetch('/pages/api.php?action=process_sale', {
         *   method: 'POST',
         *   headers: {'Content-Type': 'application/json'},
         *   body: JSON.stringify({
         *     cliente_id: 1,
         *     empleado_id: 1,
         *     metodo_pago_id: 1,
         *     subtotal: 100.00,
         *     impuesto: 16.00,
         *     total: 116.00,
         *     items: [
         *       {id: 5, cantidad: 2, precio: 50.00},
         *       {id: 8, cantidad: 1, precio: 100.00}
         *     ]
         *   })
         * })
         * 
         * PARÁMETROS (enviados como JSON en el body):
         * - cliente_id: ID del cliente (1 = Público General por defecto)
         * - empleado_id: ID del empleado que vendió
         * - metodo_pago_id: 1 = Efectivo, 2 = Tarjeta
         * - subtotal: Total sin impuestos
         * - impuesto: Monto del IVA (16%)
         * - total: Subtotal + Impuesto
         * - items: Array de productos [{id, cantidad, precio}, ...]
         * 
         * RETORNA:
         * {success: true, orden_id: 12} si todo sale bien
         * {success: false, error: "..."} si algo falla
         * 
         * EJEMPLO DE USO:
         * Cliente compra 2 Mouse ($50 c/u) y 1 Teclado ($100)
         * Subtotal: $200, IVA: $32, Total: $232
         * Se crea la orden, se insertan los detalles y se descuenta el stock
         */
        case 'process_sale':
            // Lee los datos JSON enviados desde JavaScript (viene en el body del POST)
            $data = json_decode(file_get_contents('php://input'), true);
            
            /*
             * ====================================================================
             * TRANSACCIONES DE BASE DE DATOS - ¡MUY IMPORTANTE!
             * ====================================================================
             * 
             * ¿QUÉ ES UNA TRANSACCIÓN?
             * ------------------------
             * Es como una "caja fuerte" que agrupa varias operaciones SQL
             * Todo se ejecuta como una UNIDAD: o TODO funciona, o NADA funciona
             * 
             * COMANDOS:
             * - beginTransaction()  → Inicia la transacción
             * - commit()            → Confirma TODO (si no hubo errores)
             * - rollBack()          → Cancela TODO (si hubo algún error)
             * 
             * ¿POR QUÉ LA NECESITAMOS?
             * ------------------------
             * Imagina este escenario SIN transacción:
             * 
             * 1. Se inserta la orden ✅
             * 2. Se insertan los detalles ✅
             * 3. Al actualizar el stock... ❌ ERROR (se va la luz, falla la BD, etc.)
             * 
             * Resultado: Tienes una venta registrada pero el stock NO se descontó
             * ¡Desastre! Tus números no cuadrarían nunca
             * 
             * CON TRANSACCIÓN:
             * ----------------
             * Si CUALQUIER paso falla, TODO se cancela automáticamente
             * La BD queda como si nunca hubiera pasado nada
             * 
             * CUÁNDO USAR TRANSACCIONES:
             * --------------------------
             * - Cuando modificas MÚLTIPLES tablas relacionadas
             * - En operaciones críticas (ventas, pagos, transferencias)
             * - Cuando la integridad de datos es crucial
             * 
             * EJEMPLO DE OTRAS OPERACIONES QUE NECESITAN TRANSACCIONES:
             * - Transferir dinero entre cuentas (restar de una, sumar a otra)
             * - Devolver un producto (insertar devolución, sumar stock, restar venta)
             * - Cancelar una orden (marcar como cancelada, restaurar stock)
             */
            $pdo = getDBConnection();
            $pdo->beginTransaction();  // ← Inicia la transacción
            
            try {
                /*
                 * ============================================================
                 * PASO 1: Insertar la orden principal en tabla "ordenes"
                 * ============================================================
                 * 
                 * Esta es la "cabecera" de la venta: quién compró, cuándo,
                 * cuánto fue el total, cómo pagó, etc.
                 * 
                 * CAMPOS DE LA TABLA "ordenes":
                 * - id (AUTO_INCREMENT)         → Se genera automáticamente
                 * - cliente_id                  → ¿Quién compró?
                 * - empleado_id                 → ¿Quién vendió?
                 * - metodo_pago_id              → ¿Cómo pagó? (1=Efectivo, 2=Tarjeta)
                 * - tipo_venta                  → 'fisica' o 'online'
                 * - estado                      → 'pagado', 'pendiente', 'cancelado'
                 * - subtotal                    → Total sin impuestos
                 * - impuesto_monto              → Monto del IVA (16%)
                 * - total                       → Subtotal + Impuesto
                 * - fecha_venta (TIMESTAMP)     → Se genera automáticamente (NOW())
                 */
                $sql = "INSERT INTO ordenes (cliente_id, empleado_id, metodo_pago_id, tipo_venta, estado, subtotal, impuesto_monto, total) 
                        VALUES (?, ?, ?, 'fisica', 'pagado', ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $data['cliente_id'] ?? 1,      // Si no viene cliente, usa 1 (Público General)
                    $data['empleado_id'] ?? 1,     // Si no viene empleado, usa 1 (Admin)
                    $data['metodo_pago_id'],       // 1 = Efectivo, 2 = Tarjeta
                    $data['subtotal'],             // Ej: 200.00
                    $data['impuesto'],             // Ej: 32.00 (16% de 200)
                    $data['total']                 // Ej: 232.00
                ]);
                
                // Obtiene el ID de la orden que acabamos de crear
                // Esto es CRUCIAL porque lo necesitamos para insertar los detalles
                $orden_id = $pdo->lastInsertId();
                
                /*
                 * ============================================================
                 * PASO 2: Insertar los detalles y actualizar el stock
                 * ============================================================
                 * 
                 * Por cada producto en el carrito:
                 * 1. Insertar un registro en "detalles_orden" (qué se vendió)
                 * 2. Actualizar el stock en "productos" (descontar unidades)
                 * 
                 * TABLA "detalles_orden":
                 * - orden_id          → ID de la orden (del paso 1)
                 * - producto_id       → ¿Qué producto?
                 * - cantidad          → ¿Cuántos?
                 * - precio_unitario   → ¿A qué precio se vendió? (puede cambiar)
                 * 
                 * ¿POR QUÉ GUARDAMOS EL PRECIO?
                 * Porque el precio de un producto puede cambiar en el futuro
                 * Necesitamos saber a qué precio se vendió EN ESE MOMENTO
                 */
                foreach ($data['items'] as $item) {
                    // 2.1: Inserta el detalle de la orden
                    $sql = "INSERT INTO detalles_orden (orden_id, producto_id, cantidad, precio_unitario) 
                            VALUES (?, ?, ?, ?)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        $orden_id,           // ID de la orden (del paso 1)
                        $item['id'],         // ID del producto
                        $item['cantidad'],   // Cantidad vendida
                        $item['precio']      // Precio al que se vendió
                    ]);
                    
                    // 2.2: Actualiza el stock del producto
                    /*
                     * QUERY EXPLICADO:
                     * ----------------
                     * stock_actual = stock_actual - ?
                     * 
                     * Esto es una operación ATÓMICA: lee el stock actual y lo resta
                     * en una sola operación (no hay riesgo de race conditions)
                     * 
                     * EJEMPLO:
                     * Producto ID 5 tiene stock_actual = 10
                     * Se venden 2 unidades
                     * Query: UPDATE productos SET stock_actual = stock_actual - 2 WHERE id = 5
                     * Resultado: stock_actual = 8
                     * 
                     * MEJORA FUTURA:
                     * Podrías agregar una validación ANTES de vender:
                     * 
                     * $stock = fetchOne("SELECT stock_actual FROM productos WHERE id = ?", [$item['id']]);
                     * if ($stock['stock_actual'] < $item['cantidad']) {
                     *     throw new Exception("Stock insuficiente para producto ID {$item['id']}");
                     * }
                     */
                    $sql = "UPDATE productos SET stock_actual = stock_actual - ? WHERE id = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        $item['cantidad'],   // Cantidad a restar
                        $item['id']          // ID del producto
                    ]);
                }
                
                // Si llegamos aquí, TODO salió bien → confirmamos la transacción
                $pdo->commit();  // ← ESTE ES EL MOMENTO en que TODO se guarda en la BD
                
                // Retorna éxito con el ID de la orden creada
                echo json_encode(['success' => true, 'orden_id' => $orden_id]);
                
            } catch (Exception $e) {
                // Si CUALQUIER cosa falló arriba, llegamos aquí
                // Deshacemos TODO (la BD queda como si nada hubiera pasado)
                $pdo->rollBack();  // ← Cancela TODO lo que se hizo en la transacción
                
                // Re-lanza el error para que lo capture el catch de afuera
                throw $e;
            }
            break;
            
        /*
         * ========================================================================
         * AGREGAR NUEVAS ACCIONES AQUÍ
         * ========================================================================
         * 
         * PLANTILLA PARA NUEVOS ENDPOINTS:
         * 
         * case 'nombre_de_tu_accion':
         *     // 1. Obtener datos (GET o POST)
         *     $param = $_GET['param'] ?? '';
         *     // O para POST:
         *     $data = json_decode(file_get_contents('php://input'), true);
         *     
         *     // 2. Validar datos
         *     if (empty($param)) {
         *         echo json_encode(['success' => false, 'error' => 'Falta parámetro']);
         *         break;
         *     }
         *     
         *     // 3. Ejecutar query
         *     $sql = "SELECT * FROM tabla WHERE campo = ?";
         *     $result = fetchOne($sql, [$param]);
         *     
         *     // 4. Retornar respuesta
         *     echo json_encode(['success' => true, 'data' => $result]);
         *     break;
         * 
         * IDEAS DE ENDPOINTS QUE PODRÍAS AGREGAR:
         * ---------------------------------------
         * - update_product: Actualizar datos de un producto
         * - delete_product: Marcar producto como inactivo
         * - create_supplier: Crear nuevo proveedor
         * - get_sales_report: Reporte de ventas por fecha
         * - adjust_stock: Ajustar stock manualmente
         * - cancel_order: Cancelar una venta y restaurar stock
         * - get_product_by_id: Obtener info completa de un producto
         * - search_customers: Buscar clientes por nombre/teléfono
         */
            
        // Si la acción no existe, retorna error
        default:
            echo json_encode(['success' => false, 'error' => 'Acción no válida']);
    }
    
} catch (Exception $e) {
    // Captura cualquier error que no haya sido manejado arriba
    // Lo registra en los logs del servidor para debugging
    error_log("API Error: " . $e->getMessage());
    
    // Retorna el error al frontend
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

/*
 * ============================================================================
 * DEBUGGING Y TESTING
 * ============================================================================
 * 
 * CÓMO PROBAR LA API:
 * -------------------
 * 
 * 1. Desde el navegador (solo GET):
 *    http://localhost:8000/pages/api.php?action=search_product&search=mouse
 * 
 * 2. Desde JavaScript (en la consola del navegador):
 *    fetch('/pages/api.php?action=get_customers')
 *      .then(r => r.json())
 *      .then(data => console.log(data))
 * 
 * 3. Con curl (desde terminal):
 *    curl "http://localhost:8000/pages/api.php?action=search_product&search=mouse"
 * 
 * 4. Para POST (desde JavaScript):
 *    fetch('/pages/api.php?action=process_sale', {
 *      method: 'POST',
 *      headers: {'Content-Type': 'application/json'},
 *      body: JSON.stringify({...datos...})
 *    })
 * 
 * CÓMO VER ERRORES:
 * -----------------
 * 1. En el navegador: Abre DevTools (F12) → pestaña Network → ve la respuesta
 * 2. En PHP: Revisa los logs en /var/log/apache2/error.log o similar
 * 3. Agrega temporalmente al inicio del archivo para debugging:
 *    ini_set('display_errors', 1);
 *    error_reporting(E_ALL);
 * 
 * ERRORES COMUNES:
 * ----------------
 * - "Acción no válida": El parámetro 'action' no coincide con ningún case
 * - "Undefined index": Falta un parámetro esperado (usa ?? '' para defaults)
 * - "SQL error": Revisa la sintaxis del query y que las tablas/campos existan
 * - "JSON parse error": Los datos enviados no son JSON válido
 * 
 * ============================================================================
 */
?>
