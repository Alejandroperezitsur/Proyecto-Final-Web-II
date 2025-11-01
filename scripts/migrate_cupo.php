<?php
/**
 * Script de migración: Agregar columna cupo a tabla grupos
 * Ejecutar desde navegador: http://localhost/PWBII/Proyecto-Final-Web-II/scripts/migrate_cupo.php
 */

require_once '../config/db.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Migración: Agregar columna cupo a grupos</h2>";
    
    // Verificar si la columna ya existe
    $stmt = $pdo->query("SHOW COLUMNS FROM grupos LIKE 'cupo'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: orange;'>✓ La columna 'cupo' ya existe en la tabla grupos.</p>";
    } else {
        // Agregar columna cupo
        $pdo->exec("ALTER TABLE grupos ADD COLUMN cupo INT DEFAULT 30 NOT NULL");
        echo "<p style='color: green;'>✓ Columna 'cupo' agregada exitosamente.</p>";
        
        // Actualizar grupos existentes
        $stmt = $pdo->exec("UPDATE grupos SET cupo = 30 WHERE cupo IS NULL");
        echo "<p style='color: green;'>✓ Grupos existentes actualizados con cupo por defecto (30).</p>";
    }
    
    // Mostrar algunos grupos con su cupo
    echo "<h3>Grupos con cupo configurado:</h3>";
    $stmt = $pdo->query("SELECT id, clave, nombre, cupo FROM grupos LIMIT 5");
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Clave</th><th>Nombre</th><th>Cupo</th></tr>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr><td>{$row['id']}</td><td>{$row['clave']}</td><td>{$row['nombre']}</td><td>{$row['cupo']}</td></tr>";
    }
    echo "</table>";
    
    echo "<p style='color: blue;'><strong>Migración completada exitosamente.</strong></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>