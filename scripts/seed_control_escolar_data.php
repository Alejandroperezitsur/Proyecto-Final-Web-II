<?php
// Seed integral para Control Escolar:
// - Crea 30+ materias (claves únicas)
// - Crea grupos por materia y asigna profesor
// - Inscribe alumnos (vincula alumno-grupo) y genera calificaciones
// Uso: php scripts/seed_control_escolar_data.php

require_once __DIR__ . '/../config/db.php';

function pdo(): PDO {
    return Database::getInstance()->getConnection();
}

function getProfesores(PDO $pdo): array {
    $stmt = $pdo->query("SELECT id, matricula, email FROM usuarios WHERE rol = 'profesor' AND activo = 1");
    return $stmt->fetchAll();
}

function getAlumnos(PDO $pdo): array {
    $stmt = $pdo->query("SELECT id, matricula, nombre, apellido FROM alumnos");
    return $stmt->fetchAll();
}

function ensureMaterias(PDO $pdo, array $materias): array {
    // Inserta materias si no existen por clave
    $createdOrExisting = [];
    $sel = $pdo->prepare("SELECT id FROM materias WHERE clave = :c");
    $ins = $pdo->prepare("INSERT INTO materias (nombre, clave) VALUES (:n, :c)");
    foreach ($materias as $m) {
        $clave = $m['clave'];
        $sel->execute([':c' => $clave]);
        $id = $sel->fetchColumn();
        if (!$id) {
            $ins->execute([':n' => $m['nombre'], ':c' => $m['clave']]);
            $id = (int)$pdo->lastInsertId();
        }
        $createdOrExisting[] = ['id' => (int)$id, 'nombre' => $m['nombre'], 'clave' => $clave];
    }
    return $createdOrExisting;
}

function crearGrupos(PDO $pdo, array $materias, array $profesores, array $ciclos): array {
    // Crea 1-2 grupos por materia repartidos entre profesores y ciclos
    $created = [];
    $sel = $pdo->prepare("SELECT id FROM grupos WHERE materia_id = :m AND nombre = :nom AND ciclo <=> :c");
    $ins = $pdo->prepare("INSERT INTO grupos (materia_id, profesor_id, nombre, ciclo) VALUES (:m, :p, :nom, :c)");

    foreach ($materias as $m) {
        $grupoCount = random_int(1, 2);
        for ($i = 1; $i <= $grupoCount; $i++) {
            $prof = $profesores[random_int(0, count($profesores) - 1)];
            $ciclo = $ciclos[random_int(0, count($ciclos) - 1)];
            $nombre = $m['clave'] . '-' . $i;
            $sel->execute([':m' => (int)$m['id'], ':nom' => $nombre, ':c' => $ciclo]);
            $gid = $sel->fetchColumn();
            if (!$gid) {
                $ins->execute([':m' => (int)$m['id'], ':p' => (int)$prof['id'], ':nom' => $nombre, ':c' => $ciclo]);
                $gid = (int)$pdo->lastInsertId();
            }
            $created[] = [
                'id' => (int)$gid,
                'materia_id' => (int)$m['id'],
                'materia_clave' => $m['clave'],
                'materia_nombre' => $m['nombre'],
                'profesor_id' => (int)$prof['id'],
                'ciclo' => $ciclo,
                'nombre' => $nombre,
            ];
        }
    }
    return $created;
}

function getGrupos(PDO $pdo): array {
    $stmt = $pdo->query("SELECT id, materia_id, nombre, ciclo FROM grupos");
    return $stmt->fetchAll();
}

function calificacionExiste(PDO $pdo, int $alumnoId, int $grupoId): bool {
    $stmt = $pdo->prepare("SELECT id FROM calificaciones WHERE alumno_id = :a AND grupo_id = :g");
    $stmt->execute([':a' => $alumnoId, ':g' => $grupoId]);
    return (bool)$stmt->fetchColumn();
}

function crearCalificacion(PDO $pdo, int $alumnoId, int $grupoId): void {
    // Genera calificación razonable 60–100
    $p1 = random_int(60, 100);
    $p2 = random_int(60, 100);
    $fin = random_int(60, 100);
    $stmt = $pdo->prepare("INSERT INTO calificaciones (alumno_id, grupo_id, parcial1, parcial2, final) VALUES (:a, :g, :p1, :p2, :f)");
    $stmt->execute([':a' => $alumnoId, ':g' => $grupoId, ':p1' => $p1, ':p2' => $p2, ':f' => $fin]);
}

function main() {
    $pdo = pdo();

    // 1) Materias por carrera (≥ 30 totales)
    $materias = [
        // Ingeniería en Sistemas
        ['nombre' => 'Programación I', 'clave' => 'INF101'],
        ['nombre' => 'Programación II', 'clave' => 'INF102'],
        ['nombre' => 'Estructuras de Datos', 'clave' => 'INF201'],
        ['nombre' => 'Bases de Datos', 'clave' => 'INF202'],
        ['nombre' => 'Ingeniería de Software', 'clave' => 'INF301'],
        ['nombre' => 'Redes de Computadoras', 'clave' => 'INF302'],
        ['nombre' => 'Arquitectura de Computadoras', 'clave' => 'INF303'],
        // Matemáticas
        ['nombre' => 'Álgebra Lineal', 'clave' => 'MAT102'],
        ['nombre' => 'Cálculo Diferencial', 'clave' => 'MAT201'],
        ['nombre' => 'Cálculo Integral', 'clave' => 'MAT202'],
        ['nombre' => 'Probabilidad y Estadística', 'clave' => 'MAT301'],
        // Industrial
        ['nombre' => 'Termodinámica', 'clave' => 'IND101'],
        ['nombre' => 'Procesos de Manufactura', 'clave' => 'IND201'],
        ['nombre' => 'Investigación de Operaciones', 'clave' => 'IND301'],
        ['nombre' => 'Control de Calidad', 'clave' => 'IND302'],
        // Química
        ['nombre' => 'Química General', 'clave' => 'QUI101'],
        ['nombre' => 'Química Orgánica', 'clave' => 'QUI201'],
        ['nombre' => 'Química Analítica', 'clave' => 'QUI202'],
        ['nombre' => 'Bioquímica', 'clave' => 'QUI301'],
        // Administración
        ['nombre' => 'Contabilidad I', 'clave' => 'ADM101'],
        ['nombre' => 'Contabilidad II', 'clave' => 'ADM102'],
        ['nombre' => 'Finanzas Corporativas', 'clave' => 'ADM201'],
        ['nombre' => 'Mercadotecnia', 'clave' => 'ADM202'],
        ['nombre' => 'Recursos Humanos', 'clave' => 'ADM301'],
        ['nombre' => 'Administración de Operaciones', 'clave' => 'ADM302'],
        // Extras para rebasar 30
        ['nombre' => 'Derecho Empresarial', 'clave' => 'ADM303'],
        ['nombre' => 'Análisis Numérico', 'clave' => 'MAT303'],
        ['nombre' => 'Compiladores', 'clave' => 'INF401'],
        ['nombre' => 'Inteligencia Artificial', 'clave' => 'INF402'],
        ['nombre' => 'Ética Profesional', 'clave' => 'GEN101'],
        ['nombre' => 'Metodología de la Investigación', 'clave' => 'GEN102'],
    ];

    $materiasOk = ensureMaterias($pdo, $materias);
    echo "Materias aseguradas: " . count($materiasOk) . "\n";

    // 2) Profesores (tomar existentes del seed)
    $profesores = getProfesores($pdo);
    if (count($profesores) < 1) {
        throw new RuntimeException('No hay profesores activos. Ejecuta primero scripts/seed_test_users.php');
    }
    echo "Profesores disponibles: " . count($profesores) . "\n";

    // 3) Crear grupos por materia
    $ciclos = ['2024A', '2024B'];
    $grupos = crearGrupos($pdo, $materiasOk, $profesores, $ciclos);
    echo "Grupos creados/asegurados: " . count($grupos) . "\n";

    // 4) Inscribir alumnos generando calificaciones
    $alumnos = getAlumnos($pdo);
    $gruposTodos = getGrupos($pdo);
    if (count($gruposTodos) < 1) {
        throw new RuntimeException('No hay grupos disponibles.');
    }

    $inscritos = 0;
    foreach ($alumnos as $al) {
        // Asignar entre 5 y 8 grupos distintos
        $k = random_int(5, 8);
        $indices = array_rand($gruposTodos, $k);
        if (!is_array($indices)) { $indices = [$indices]; }
        foreach ($indices as $idx) {
            $g = $gruposTodos[$idx];
            if (!calificacionExiste($pdo, (int)$al['id'], (int)$g['id'])) {
                crearCalificacion($pdo, (int)$al['id'], (int)$g['id']);
                $inscritos++;
            }
        }
    }
    echo "Inscripciones (alumno-grupo) con calificaciones generadas: {$inscritos}\n";

    echo "Seed de información académica completado.\n";
}

main();
?>