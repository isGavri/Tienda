-- Seed data for tienda_db

USE tienda_db;

-- Insert Roles
INSERT INTO roles (nombre) VALUES 
('Administrador'),
('Vendedor');

-- Insert Empleados (Admin and vendors)
INSERT INTO empleados (nombre, email, password, rol_id, activo) VALUES 
('Admin Principal', 'admin@possystem.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, TRUE), -- password: password
('Juan Pérez', 'juan.perez@possystem.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, TRUE),
('Laura Sánchez', 'laura.sanchez@possystem.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, TRUE),
('Pedro García', 'pedro.garcia@possystem.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, FALSE);

-- Insert Clientes
INSERT INTO clientes (nombre, apellido, email, telefono, direccion) VALUES 
('Público', 'General', NULL, NULL, NULL),
('María', 'González', 'maria.gonzalez@email.com', '555-0101', 'Av. Principal 123, Col. Centro'),
('Carlos', 'Ruiz', 'carlos.ruiz@email.com', '555-0102', 'Calle Reforma 456, Col. Norte'),
('Ana', 'Martínez', 'ana.martinez@email.com', '555-0103', 'Blvd. Juárez 789, Col. Sur');

-- Insert Proveedores
INSERT INTO proveedores (empresa, contacto_nombre, telefono, email, activo) VALUES 
('TechDistributor SA', 'Roberto Hernández', '555-1001', 'ventas@techdist.com', TRUE),
('Componentes Tech', 'Ana Martínez', '555-1002', 'contacto@comptech.com', TRUE),
('Periféricos MX', 'Luis García', '555-1003', 'ventas@perifmx.com', TRUE);

-- Insert Categorias
INSERT INTO categorias (nombre, descripcion) VALUES 
('Laptops', 'Computadoras portátiles'),
('Monitores', 'Pantallas y monitores'),
('Periféricos', 'Teclados, ratones y accesorios'),
('Componentes', 'Componentes de hardware');

-- Insert Productos
INSERT INTO productos (sku, nombre, descripcion, precio_venta, costo_compra, stock_actual, categoria_id, proveedor_id, activo) VALUES 
('SKU-001', 'Laptop HP 15-ef2126wm', 'Laptop AMD Ryzen 5, 8GB RAM, 256GB SSD', 12999.00, 8500.00, 2, 1, 1, TRUE),
('SKU-002', 'Monitor Dell 27" 4K', 'Monitor UHD 4K IPS 27 pulgadas', 6999.00, 4200.00, 15, 2, 1, TRUE),
('SKU-003', 'Laptop Lenovo IdeaPad 3', 'Intel Core i5, 8GB RAM, 512GB SSD', 11499.00, 7800.00, 8, 1, 1, TRUE),
('SKU-004', 'Monitor LG UltraWide 34"', 'Monitor curvo 21:9 QHD', 9999.00, 6500.00, 6, 2, 1, TRUE),
('SKU-015', 'Mouse Logitech M185', 'Mouse inalámbrico ergonómico', 249.00, 120.00, 3, 3, 1, TRUE),
('SKU-016', 'Teclado Logitech K120', 'Teclado USB de membrana', 199.00, 95.00, 12, 3, 3, TRUE),
('SKU-017', 'Webcam Logitech C920', 'Cámara Full HD 1080p', 1299.00, 750.00, 8, 3, 3, TRUE),
('SKU-018', 'SSD Samsung 1TB NVMe', 'Disco sólido M.2 NVMe PCIe 3.0', 1899.00, 1200.00, 25, 4, 2, TRUE),
('SKU-019', 'RAM Corsair 16GB DDR4', 'Memoria RAM 3200MHz (2x8GB)', 1599.00, 980.00, 18, 4, 2, TRUE),
('SKU-020', 'Fuente EVGA 650W', 'Fuente de poder 80+ Bronze', 1299.00, 780.00, 10, 4, 2, TRUE),
('SKU-021', 'Mouse Gamer Razer DeathAdder', 'Mouse óptico RGB 16000 DPI', 899.00, 550.00, 7, 3, 3, TRUE),
('SKU-022', 'Teclado Mecánico RGB', 'Teclado mecánico switches Blue', 1499.00, 850.00, 4, 3, 3, TRUE),
('SKU-023', 'Audífonos HyperX Cloud II', 'Audífonos gaming 7.1 virtual', 1799.00, 1100.00, 9, 3, 3, TRUE),
('SKU-024', 'Monitor ASUS 24" 144Hz', 'Monitor gaming Full HD 144Hz', 4999.00, 3200.00, 11, 2, 1, TRUE),
('SKU-025', 'Laptop ASUS TUF Gaming', 'Ryzen 7, RTX 3050, 16GB RAM', 18999.00, 13500.00, 5, 1, 1, TRUE);

-- Insert Métodos de Pago
INSERT INTO metodos_pago (nombre) VALUES 
('Efectivo'),
('Tarjeta'),
('Transferencia');

-- Insert Sample Orders (Recent transactions for dashboard)
INSERT INTO ordenes (cliente_id, empleado_id, metodo_pago_id, tipo_venta, estado, subtotal, impuesto_monto, total, fecha_venta) VALUES 
(2, 2, 2, 'fisica', 'pagado', 1060.34, 169.66, 1230.00, '2024-11-25 14:15:00'),
(1, 2, 1, 'fisica', 'pagado', 387.93, 62.07, 450.00, '2024-11-25 14:32:00'),
(3, 3, 2, 'fisica', 'pagado', 1853.45, 296.55, 2150.00, '2024-11-25 13:42:00'),
(1, 3, 1, 'fisica', 'pagado', 77.58, 12.42, 89.99, '2024-11-25 13:58:00'),
(1, 2, 1, 'fisica', 'pagado', 280.60, 44.90, 325.50, '2024-11-25 13:25:00');

-- Insert Order Details for the sample orders
-- Order 1 (ID 1): María González - Tarjeta - $1,230.00
INSERT INTO detalles_orden (orden_id, producto_id, cantidad, precio_unitario) VALUES 
(1, 5, 5, 249.00); -- 5 Mouse Logitech M185 (producto_id 5)

-- Order 2 (ID 2): Público General - Efectivo - $450.00
INSERT INTO detalles_orden (orden_id, producto_id, cantidad, precio_unitario) VALUES 
(2, 6, 2, 199.00), -- 2 Teclados K120 (producto_id 6)
(2, 5, 1, 249.00); -- 1 Mouse (producto_id 5)

-- Order 3 (ID 3): Carlos Ruiz - Tarjeta - $2,150.00
INSERT INTO detalles_orden (orden_id, producto_id, cantidad, precio_unitario) VALUES 
(3, 12, 1, 1499.00), -- 1 Teclado Mecánico (producto_id 12)
(3, 11, 1, 899.00);  -- 1 Mouse Gamer (producto_id 11)

-- Order 4 (ID 4): Público General - Efectivo - $89.99
INSERT INTO detalles_orden (orden_id, producto_id, cantidad, precio_unitario) VALUES 
(4, 5, 1, 249.00); -- 1 Mouse (producto_id 5)

-- Order 5 (ID 5): Público General - Efectivo - $325.50
INSERT INTO detalles_orden (orden_id, producto_id, cantidad, precio_unitario) VALUES 
(5, 6, 2, 199.00); -- 2 Teclados (producto_id 6)

-- Add more historical orders for better dashboard data
INSERT INTO ordenes (cliente_id, empleado_id, metodo_pago_id, tipo_venta, estado, subtotal, impuesto_monto, total, fecha_venta) VALUES 
(4, 2, 1, 'fisica', 'pagado', 11206.90, 1793.10, 12999.00, '2024-11-25 10:30:00'),
(2, 3, 2, 'fisica', 'pagado', 6033.62, 965.38, 6999.00, '2024-11-25 11:15:00'),
(1, 2, 1, 'fisica', 'pagado', 3448.28, 551.72, 4000.00, '2024-11-25 09:45:00'),
(3, 2, 2, 'fisica', 'pagado', 9913.79, 1586.21, 11500.00, '2024-11-25 12:20:00'),
(1, 3, 1, 'fisica', 'pagado', 1637.93, 262.07, 1900.00, '2024-11-25 08:30:00');

-- Order details for additional orders
INSERT INTO detalles_orden (orden_id, producto_id, cantidad, precio_unitario) VALUES 
(6, 1, 1, 12999.00), -- 1 Laptop HP (producto_id 1)
(7, 2, 1, 6999.00),  -- 1 Monitor Dell 4K (producto_id 2)
(8, 12, 2, 1499.00), -- 2 Teclados Mecánicos (producto_id 12)
(8, 5, 3, 249.00),  -- 3 Mouse (producto_id 5)
(9, 3, 1, 11499.00), -- 1 Laptop Lenovo (producto_id 3)
(10, 8, 1, 1899.00); -- 1 SSD Samsung (producto_id 8)
