<?php
// Stub para suprimir solicitudes fantasma a /@vite/dashboard.php
// Algunas herramientas insertan este recurso durante desarrollo.
// Servimos respuesta vacía para evitar errores visuales en el navegador.
header('Content-Type: application/javascript');
// No emitimos contenido.
exit;