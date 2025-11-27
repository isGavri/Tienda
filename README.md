# Tienda

Ejecuta el servidor con 
```bash
php -S localhost:8000 
```
Abre la ruta `localhost:8000` en tu navegador

- Si tienes error de que no encuentra el ejecutable `php`
```zsh
C:\\xampp\php\php.exe -S localhost:8000
```

- Si tienes error en la conexión a la base de datos, en el archivo `./includes/db.php` cambia las credenciales a tu usuario de mysql local

```php
    define('DB_USER', 'notsy'); // cambia `notsy` por tu usuario
    define('DB_PASS', '1234'); // cambia '1234' por tu contraseña
```

## base de datos
Toda la conexion con la base de datos ocurre en este archivo,
tambien nos ayuda a realizar todas las queries/peticiones a la base de datos,
llamamos a las funciones `query`, `fetchAll`, `fetchOne`, `execute`, `lastInsertedId`,
para ayudarnos con las operaciones
## interfaz
La conexión entre la interfaz y la base de datos se hace mediante una API, en el archivo `pages/api.php`,
todos los clicks o acciones de la interfaz pasan por aqui antes de ir a `db.php` para obtener la conexion y finalmente la base de datos.
Ejemplo:
```js
const response = await fetch(`/pages/api.php?action=get_product&id=${productId}`);
```
Hace un request a `./pages/api.php` con una `action` y esta es `get_product`, se procesa esa accion con sus argumentos desde aqui
### javascript
Nos ayuda a tener una pagina interactiva (botones funcionen y actualizacioones de los elementos la interfaz)

Muchas operaciones de las vistas (todo bajo `./pages`) se encuentran en este archivo, tambien deben aprenderlas, por ejemplo en `./pages/suppliers.php` hay un boton para crear nuevos provedores `onClick="openSupplierModal()` esta fucnion es manejada en `./js/suppliers.js` abre el formulario y al clickear save se llama a este archivo `./pages/api.php`
