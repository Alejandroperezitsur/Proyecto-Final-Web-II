<?php
// Script integral: inserta usuarios desde public/test_users.html
// y genera materias, grupos y calificaciones conectadas.
// Uso: php scripts/setup_from_test_users.php

require_once __DIR__ . '/../config/db.php';

function pdo(): PDO { return Database::getInstance()->getConnection(); }

function parseTestUsers(string $htmlPath): array {
  $html = file_get_contents($htmlPath);
  if ($html === false) { throw new RuntimeException("No se pudo leer $htmlPath"); }
  $dom = new DOMDocument();
  @$dom->loadHTML($html);
  $tables = $dom->getElementsByTagName('table');
  if ($tables->length < 2) { throw new RuntimeException('Formato de test_users.html inválido: se esperan 2 tablas'); }
  // Alumnos
  $alumnos = [];
  $rows = $tables->item(0)->getElementsByTagName('tr');
  foreach ($rows as $i => $row) {
    if ($i === 0) continue; // header
    $cols = $row->getElementsByTagName('td');
    if ($cols->length < 5) continue;
    $alumnos[] = [
      'matricula' => trim($cols->item(0)->nodeValue),
      'nombre' => trim($cols->item(1)->nodeValue),
      'apellido' => trim($cols->item(2)->nodeValue),
      'email' => trim($cols->item(3)->nodeValue),
      'password' => trim($cols->item(4)->nodeValue),
    ];
  }
  // Profesores
  $profesores = [];
  $rows = $tables->item(1)->getElementsByTagName('tr');
  foreach ($rows as $i => $row) {
    if ($i === 0) continue; // header
    $cols = $row->getElementsByTagName('td');
    if ($cols->length < 3) continue;
    $profesores[] = [
      'matricula' => trim($cols->item(0)->nodeValue),
      'email' => trim($cols->item(1)->nodeValue),
      'password' => trim($cols->item(2)->nodeValue),
    ];
  }
  return [$alumnos, $profesores];
}

function ensureUsers(PDO $pdo, array $alumnos, array $profesores): array {
  $insAlumno = $pdo->prepare("INSERT INTO alumnos (matricula, nombre, apellido, email, password) VALUES (:mat, :nom, :ape, :email, :pw)");
  $selAlumno = $pdo->prepare("SELECT id FROM alumnos WHERE matricula = :mat");
  $insProf = $pdo->prepare("INSERT INTO usuarios (matricula, email, password, rol, activo) VALUES (:mat, :email, :pw, 'profesor', 1)");
  $selProf = $pdo->prepare("SELECT id FROM usuarios WHERE matricula = :mat");
  $addedA = 0; $addedP = 0; $skippedA = 0; $skippedP = 0;
  foreach ($alumnos as $a) {
    $selAlumno->execute([':mat' => $a['matricula']]);
    $id = $selAlumno->fetchColumn();
    if ($id) { $skippedA++; continue; }
    $insAlumno->execute([
      ':mat' => $a['matricula'], ':nom' => $a['nombre'], ':ape' => $a['apellido'], ':email' => $a['email'],
      ':pw' => password_hash($a['password'], PASSWORD_DEFAULT),
    ]);
    $addedA++;
  }
  foreach ($profesores as $p) {
    $selProf->execute([':mat' => $p['matricula']]);
    $id = $selProf->fetchColumn();
    if ($id) { $skippedP++; continue; }
    $insProf->execute([
      ':mat' => $p['matricula'], ':email' => $p['email'], ':pw' => password_hash($p['password'], PASSWORD_DEFAULT),
    ]);
    $addedP++;
  }
  return compact('addedA','addedP','skippedA','skippedP');
}

function ensureMaterias(PDO $pdo): array {
  $materias = [
    ['nombre' => 'Programación I', 'clave' => 'INF101'],
    ['nombre' => 'Programación II', 'clave' => 'INF102'],
    ['nombre' => 'Estructuras de Datos', 'clave' => 'INF201'],
    ['nombre' => 'Bases de Datos', 'clave' => 'INF202'],
    ['nombre' => 'Ingeniería de Software', 'clave' => 'INF301'],
    ['nombre' => 'Redes de Computadoras', 'clave' => 'INF302'],
    ['nombre' => 'Arquitectura de Computadoras', 'clave' => 'INF303'],
    ['nombre' => 'Álgebra Lineal', 'clave' => 'MAT102'],
    ['nombre' => 'Cálculo Diferencial', 'clave' => 'MAT201'],
    ['nombre' => 'Cálculo Integral', 'clave' => 'MAT202'],
    ['nombre' => 'Probabilidad y Estadística', 'clave' => 'MAT301'],
    ['nombre' => 'Termodinámica', 'clave' => 'IND101'],
    ['nombre' => 'Procesos de Manufactura', 'clave' => 'IND201'],
    ['nombre' => 'Investigación de Operaciones', 'clave' => 'IND301'],
    ['nombre' => 'Control de Calidad', 'clave' => 'IND302'],
    ['nombre' => 'Química General', 'clave' => 'QUI101'],
    ['nombre' => 'Química Orgánica', 'clave' => 'QUI201'],
    ['nombre' => 'Química Analítica', 'clave' => 'QUI202'],
    ['nombre' => 'Bioquímica', 'clave' => 'QUI301'],
    ['nombre' => 'Contabilidad I', 'clave' => 'ADM101'],
    ['nombre' => 'Contabilidad II', 'clave' => 'ADM102'],
    ['nombre' => 'Finanzas Corporativas', 'clave' => 'ADM201'],
    ['nombre' => 'Mercadotecnia', 'clave' => 'ADM202'],
    ['nombre' => 'Recursos Humanos', 'clave' => 'ADM301'],
    ['nombre' => 'Administración de Operaciones', 'clave' => 'ADM302'],
    ['nombre' => 'Derecho Empresarial', 'clave' => 'ADM303'],
    ['nombre' => 'Análisis Numérico', 'clave' => 'MAT303'],
    ['nombre' => 'Compiladores', 'clave' => 'INF401'],
    ['nombre' => 'Inteligencia Artificial', 'clave' => 'INF402'],
    ['nombre' => 'Ética Profesional', 'clave' => 'GEN101'],
    ['nombre' => 'Metodología de la Investigación', 'clave' => 'GEN102'],
  ];
  $sel = $pdo->prepare("SELECT id FROM materias WHERE clave = :c");
  $ins = $pdo->prepare("INSERT INTO materias (nombre, clave) VALUES (:n, :c)");
  $result = [];
  foreach ($materias as $m) {
    $sel->execute([':c' => $m['clave']]);
    $id = $sel->fetchColumn();
    if (!$id) { $ins->execute([':n' => $m['nombre'], ':c' => $m['clave']]); $id = (int)$pdo->lastInsertId(); }
    $result[] = ['id' => (int)$id, 'nombre' => $m['nombre'], 'clave' => $m['clave']];
  }
  return $result;
}

function crearGrupos(PDO $pdo, array $materias, array $profesores, array $ciclos): array {
  if (count($profesores) < 1) { throw new RuntimeException('No hay profesores activos para crear grupos'); }
  $sel = $pdo->prepare("SELECT id FROM grupos WHERE materia_id = :m AND nombre = :nom AND ciclo <=> :c");
  $ins = $pdo->prepare("INSERT INTO grupos (materia_id, profesor_id, nombre, ciclo) VALUES (:m, :p, :nom, :c)");
  $created = [];
  foreach ($materias as $m) {
    $grupoCount = 1; // uno por materia para garantizar distribución
    for ($i = 1; $i <= $grupoCount; $i++) {
      $prof = $profesores[random_int(0, count($profesores) - 1)];
      // Obtener id profesor por matrícula
      $stmtProf = $pdo->prepare("SELECT id FROM usuarios WHERE matricula = :mat AND rol = 'profesor' AND activo = 1");
      $stmtProf->execute([':mat' => $prof['matricula']]);
      $profId = (int)($stmtProf->fetchColumn() ?: 0);
      if ($profId <= 0) { continue; }
      $ciclo = $ciclos[random_int(0, count($ciclos) - 1)];
      $nombre = $m['clave'] . '-' . $i;
      $sel->execute([':m' => (int)$m['id'], ':nom' => $nombre, ':c' => $ciclo]);
      $gid = $sel->fetchColumn();
      if (!$gid) {
        $ins->execute([':m' => (int)$m['id'], ':p' => $profId, ':nom' => $nombre, ':c' => $ciclo]);
        $gid = (int)$pdo->lastInsertId();
      }
      $created[] = ['id' => (int)$gid, 'materia_id' => (int)$m['id'], 'nombre' => $nombre, 'ciclo' => $ciclo, 'profesor_id' => $profId];
    }
  }
  return $created;
}

function getGrupos(PDO $pdo): array { return $pdo->query("SELECT id FROM grupos")->fetchAll(); }

function calificacionExiste(PDO $pdo, int $alumnoId, int $grupoId): bool {
  $stmt = $pdo->prepare("SELECT id FROM calificaciones WHERE alumno_id = :a AND grupo_id = :g");
  $stmt->execute([':a' => $alumnoId, ':g' => $grupoId]);
  return (bool)$stmt->fetchColumn();
}

function crearCalificacion(PDO $pdo, int $alumnoId, int $grupoId): void {
  $p1 = random_int(60, 100); $p2 = random_int(60, 100); $fin = random_int(60, 100);
  $stmt = $pdo->prepare("INSERT INTO calificaciones (alumno_id, grupo_id, parcial1, parcial2, final) VALUES (:a, :g, :p1, :p2, :f)");
  $stmt->execute([':a' => $alumnoId, ':g' => $grupoId, ':p1' => $p1, ':p2' => $p2, ':f' => $fin]);
}

function enrollAll(PDO $pdo, int $minGroups = 5, int $maxGroups = 8): int {
  $alumnos = $pdo->query("SELECT id FROM alumnos")->fetchAll();
  $grupos = $pdo->query("SELECT id FROM grupos")->fetchAll();
  if (!$alumnos || !$grupos) { return 0; }
  $inscritos = 0;
  foreach ($alumnos as $al) {
    $k = random_int($minGroups, $maxGroups);
    $indices = array_rand($grupos, min($k, count($grupos)));
    $indices = is_array($indices) ? $indices : [$indices];
    foreach ($indices as $idx) {
      $gid = (int)$grupos[$idx]['id'];
      $aid = (int)$al['id'];
      if (!calificacionExiste($pdo, $aid, $gid)) { crearCalificacion($pdo, $aid, $gid); $inscritos++; }
    }
  }
  return $inscritos;
}

function summary(PDO $pdo): array {
  $count = function($table) use ($pdo){ return (int)$pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn(); };
  // Coherencia: alumnos con al menos una calificación
  $alConCal = (int)$pdo->query("SELECT COUNT(DISTINCT alumno_id) FROM calificaciones")->fetchColumn();
  // Profesores con al menos un grupo
  $profConGrupo = (int)$pdo->query("SELECT COUNT(DISTINCT u.id) FROM usuarios u JOIN grupos g ON g.profesor_id = u.id WHERE u.rol = 'profesor' AND u.activo = 1")->fetchColumn();
  return [
    'alumnos' => $count('alumnos'),
    'profesores' => (int)$pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'profesor' AND activo = 1")->fetchColumn(),
    'materias' => $count('materias'),
    'grupos' => $count('grupos'),
    'calificaciones' => $count('calificaciones'),
    'alumnos_con_calificacion' => $alConCal,
    'profesores_con_grupo' => $profConGrupo,
  ];
}

function main(): void {
  $pdo = pdo();
  $pdo->beginTransaction();
  try {
    [$alumnos, $profesores] = parseTestUsers(__DIR__ . '/../public/test_users.html');
    $resUsers = ensureUsers($pdo, $alumnos, $profesores);
    $materias = ensureMaterias($pdo);
    $grupos = crearGrupos($pdo, $materias, $profesores, ['2024A','2024B']);
    $inscritos = enrollAll($pdo);
    $pdo->commit();
    $sum = summary($pdo);
    echo "Inserción de usuarios: alumnos añadidos={$resUsers['addedA']}, profesores añadidos={$resUsers['addedP']}, alumnos existentes={$resUsers['skippedA']}, profesores existentes={$resUsers['skippedP']}\n";
    echo "Materias aseguradas: " . count($materias) . "\n";
    echo "Grupos creados/asegurados: " . count($grupos) . "\n";
    echo "Inscripciones generadas (alumno-grupo con calificaciones): {$inscritos}\n";
    echo "Resumen: alumnos={$sum['alumnos']}, profesores={$sum['profesores']}, materias={$sum['materias']}, grupos={$sum['grupos']}, calificaciones={$sum['calificaciones']}\n";
    echo "Coherencia: alumnos con calificación={$sum['alumnos_con_calificacion']} de {$sum['alumnos']}; profesores con grupo={$sum['profesores_con_grupo']} de {$sum['profesores']}\n";
  } catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, "Error: " . $e->getMessage() . "\n");
    exit(1);
  }
}

main();

?>