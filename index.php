<?php
/*
 * INDEX.PHP - Punto de entrada principal del sistema POS
 * 
 * Propósito: Redirecciona automáticamente al dashboard cuando se accede a la raíz del sitio
 * Básicamente es solo para que no quede una página en blanco cuando entres al localhost:8000
 */

// Redirige al usuario directamente al dashboard
header('Location: pages/dashboard.php');
exit;
?>
