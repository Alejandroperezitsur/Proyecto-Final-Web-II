<?php
/**
 * Script para actualizar la tabla carreras
 * Ejecutar desde línea de comandos: php migrations/update_carreras_table.php
 */

require_once __DIR__ . '/../config/db.php';

echo "=== Actualización de tabla carreras ===\n";

try {
    $pdo = Database::getInstance()->getConnection();
    
    echo "Conectado a la base de datos.\n\n";
    
    // Leer el archivo SQL
    $sqlFile = __DIR__ . '/update_carreras_add_columns.sql';
    if (!file_exists($sqlFile)) {
        die("Error: No se encontró el archivo de migración.\n");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Dividir en statements individuales
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^--/', $stmt);
        }
    );
    
    echo "Ejecutando " . count($statements) . " statements SQL...\n\n";
    
    foreach ($statements as $index => $statement) {
        if (empty(trim($statement))) continue;
        
        // Mostrar el tipo de operación
        $statementPreview = substr(trim($statement), 0, 50) . '...';
        echo sprintf("[%d/%d] %s\n", $index + 1, count($statements), $statementPreview);
        
        try {
            $pdo->exec($statement);
            echo "  ✓ Ejecutado correctamente\n";
        } catch (PDOException $e) {
            // Si el error es porque la columna ya existe, está bien
            if (strpos($e->getMessage(), 'Duplicate column') !== false) {
                echo "  ⚠ La columna ya existe (omitiendo)\n";
            } else {
                echo "  ✗ Error: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\n=== Verificando datos ===\n\n";
    
    // Verificar carreras
    $stmt = $pdo->query("SELECT nombre, clave, descripcion, duracion_semestres, creditos_totales FROM carreras ORDER BY nombre");
    $carreras = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Total de carreras: " . count($carreras) . "\n\n";
    
    foreach ($carreras as $carrera) {
        echo "- " . $carrera['nombre'] . " (" . $carrera['clave'] . ")\n";
        echo "  Duración: " . ($carrera['duracion_semestres'] ?? 'N/A') . " semestres\n";
        echo "  Créditos: " . ($carrera['creditos_totales'] ?? 'N/A') . "\n";
        echo "  Descripción: " . (isset($carrera['descripcion']) ? substr($carrera['descripcion'], 0, 60) . '...' : 'N/A') . "\n\n";
    }
    
    echo "=== Actualización completada exitosamente ===\n";
    
} catch (Exception $e) {
    echo "Error fatal: " . $e->getMessage() . "\n";
    exit(1);
}
