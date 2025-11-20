<?php
// Fix for ISC Curriculum
// Access this file via browser: http://localhost/PWBII/Control-Escolar-ITSUR/public/fix_isc.php

require_once __DIR__ . '/../config/db.php';
$pdo = Database::getInstance()->getConnection();

echo "<pre>";
echo "=== DIAGNÓSTICO Y REPARACIÓN DE ISC (WEB MODE) ===\n\n";

// 1. Verificar Carrera ISC
echo "1. Buscando carrera ISC...\n";
$stmt = $pdo->prepare("SELECT * FROM carreras WHERE clave = ?");
$stmt->execute(['ISC']);
$isc = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$isc) {
    echo "ERROR: No se encontró la carrera ISC. Intentando crearla...\n";
    $pdo->exec("INSERT INTO carreras (nombre, clave, descripcion, duracion_semestres, creditos_totales) VALUES 
        ('Ingeniería en Sistemas Computacionales', 'ISC', 'Profesionista capaz de diseñar, desarrollar e implementar sistemas computacionales.', 9, 240)");
    $iscId = $pdo->lastInsertId();
    echo "Carrera creada con ID: $iscId\n";
} else {
    $iscId = $isc['id'];
    echo "Carrera encontrada: ID $iscId, Nombre: {$isc['nombre']}\n";
}

// 2. Verificar Materias (Muestra)
echo "\n2. Verificando materias clave...\n";
$materiasClave = ['ISC-1001', 'MAT-1001', 'ING-1001'];
foreach ($materiasClave as $clave) {
    $stmt = $pdo->prepare("SELECT id, nombre FROM materias WHERE clave = ?");
    $stmt->execute([$clave]);
    $m = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($m) {
        echo "  [OK] $clave: ID {$m['id']} - {$m['nombre']}\n";
    } else {
        echo "  [FALTA] $clave no existe. Creando...\n";
        $pdo->prepare("INSERT INTO materias (nombre, clave) VALUES (?, ?)")->execute(["Materia $clave", $clave]);
        echo "  -> Creada.\n";
    }
}

// 3. Limpiar y Reparar
echo "\n3. EJECUTANDO REPARACIÓN FORZADA...\n";
echo "  -> Limpiando registros actuales de ISC...\n";
$pdo->prepare("DELETE FROM materias_carrera WHERE carrera_id = ?")->execute([$iscId]);

$sqlFile = __DIR__ . '/../migrations/force_full_isc_curriculum.sql';
if (!file_exists($sqlFile)) {
    die("ERROR CRÍTICO: No se encuentra el archivo SQL en $sqlFile\n");
}

$sql = file_get_contents($sqlFile);
$sqlProcessed = str_replace('@isc_id', $iscId, $sql);
$sqlProcessed = preg_replace('/SET @isc_id = .*?;/', '', $sqlProcessed);

$statements = explode(';', $sqlProcessed);
$errors = 0;
$success = 0;

foreach ($statements as $query) {
    $query = trim($query);
    if (empty($query)) continue;
    if (strpos($query, 'USE control_escolar') !== false) continue;

    try {
        $pdo->exec($query);
        $success++;
    } catch (PDOException $e) {
        echo "  [ERROR SQL] " . $e->getMessage() . "\n";
        $errors++;
    }
}

echo "\n=== RESULTADO ===\n";
echo "Queries ejecutados: $success\n";
echo "Errores: $errors\n";

$stmt = $pdo->prepare("SELECT COUNT(*) FROM materias_carrera WHERE carrera_id = ?");
$stmt->execute([$iscId]);
$finalCount = $stmt->fetchColumn();
echo "Total materias asignadas a ISC ahora: $finalCount\n";

if ($finalCount > 0) {
    echo "<h1>¡ÉXITO! La retícula ha sido reparada.</h1>";
    echo "<p>Por favor regrese al Dashboard y verifique.</p>";
} else {
    echo "<h1>FALLO</h1>";
}
echo "</pre>";
