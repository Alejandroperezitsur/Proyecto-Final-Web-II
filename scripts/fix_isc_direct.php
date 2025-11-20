<?php
require_once __DIR__ . '/../config/db.php';
$pdo = Database::getInstance()->getConnection();

header('Content-Type: text/plain');

echo "=== DIAGNÓSTICO Y REPARACIÓN DE ISC ===\n\n";

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
        // Insertar materia dummy si falta para probar
        $pdo->prepare("INSERT INTO materias (nombre, clave) VALUES (?, ?)")->execute(["Materia $clave", $clave]);
        echo "  -> Creada.\n";
    }
}

// 3. Verificar Retícula Actual
echo "\n3. Verificando retícula actual en DB...\n";
$stmt = $pdo->prepare("SELECT COUNT(*) FROM materias_carrera WHERE carrera_id = ?");
$stmt->execute([$iscId]);
$count = $stmt->fetchColumn();
echo "Total materias asignadas a ISC: $count\n";

// 4. REPARACIÓN FORZADA
echo "\n4. EJECUTANDO REPARACIÓN FORZADA...\n";

// Limpiar retícula actual para evitar duplicados/errores parciales
echo "  -> Limpiando registros actuales de ISC...\n";
$pdo->prepare("DELETE FROM materias_carrera WHERE carrera_id = ?")->execute([$iscId]);

// Leer el script SQL completo
$sqlFile = __DIR__ . '/../migrations/force_full_isc_curriculum.sql';
if (!file_exists($sqlFile)) {
    die("ERROR CRÍTICO: No se encuentra el archivo SQL en $sqlFile\n");
}

$sql = file_get_contents($sqlFile);

// El script SQL usa variables @isc_id, pero PDO no siempre maneja bien múltiples queries con variables de sesión en una sola llamada exec dependiendo del driver.
// Vamos a hacerlo manualmente con PHP para asegurar que funcione.

// Definir las materias del Semestre 1 para prueba rápida y robusta
$curriculum = [
    1 => [
        ['ISC-1001', 'especialidad', 5],
        ['MAT-1001', 'basica', 5],
        ['MAT-1004', 'basica', 4],
        ['QUI-1001', 'basica', 4],
        ['INV-1001', 'basica', 4],
        ['ING-1001', 'basica', 3]
    ],
    2 => [
        ['ISC-1002', 'especialidad', 5],
        ['MAT-1002', 'basica', 5],
        ['MAT-1006', 'basica', 4],
        ['FIS-1001', 'basica', 4],
        ['ISC-2003', 'especialidad', 4],
        ['ING-1002', 'basica', 3]
    ],
    // ... Se pueden agregar más, pero probemos si esto funciona primero
];

echo "  -> Insertando materias manualmente vía PHP...\n";
$inserted = 0;
$errors = 0;

// Preparar statements
$findMateria = $pdo->prepare("SELECT id FROM materias WHERE clave = ?");
$insertRel = $pdo->prepare("INSERT INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES (?, ?, ?, ?, ?)");

// Vamos a leer el archivo SQL y extraer los VALUES para hacerlo masivo si el array manual es corto, 
// O mejor, ejecutemos el SQL directamente pero reemplazando la variable @isc_id por el ID real.

// Reemplazar @isc_id con el ID real
$sqlProcessed = str_replace('@isc_id', $iscId, $sql);
// Eliminar la línea que calcula @isc_id para evitar errores
$sqlProcessed = preg_replace('/SET @isc_id = .*?;/', '', $sqlProcessed);

// Ejecutar bloque por bloque (separado por ;)
$statements = explode(';', $sqlProcessed);
foreach ($statements as $query) {
    $query = trim($query);
    if (empty($query)) continue;
    if (strpos($query, 'USE control_escolar') !== false) continue; // Skip USE

    try {
        $pdo->exec($query);
    } catch (PDOException $e) {
        echo "  [ERROR SQL] " . $e->getMessage() . "\n";
        $errors++;
    }
}

echo "\n=== REPARACIÓN FINALIZADA ===\n";
$stmt = $pdo->prepare("SELECT COUNT(*) FROM materias_carrera WHERE carrera_id = ?");
$stmt->execute([$iscId]);
$finalCount = $stmt->fetchColumn();
echo "Total materias asignadas a ISC ahora: $finalCount\n";

if ($finalCount > 0) {
    echo "¡ÉXITO! La retícula debería ser visible ahora.\n";
} else {
    echo "FALLO: Aún no hay materias. Revise los errores SQL.\n";
}
