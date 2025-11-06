<?php
use PDO;

function SICEnetRunSeed(PDO $pdo): void {
    // Carreras
    $carreras = [
        'Ingeniería en Sistemas Computacionales',
        'Ingeniería Industrial',
        'Ingeniería Mecatrónica',
        'Ingeniería Electrónica',
        'Ingeniería Civil',
        'Ingeniería en Gestión Empresarial',
        'Ingeniería Bioquímica',
    ];
    foreach ($carreras as $c) {
        $stmt = $pdo->prepare('INSERT INTO carreras (nombre) VALUES (?)');
        $stmt->execute([$c]);
    }

    // Admin default if not exists
    $existsAdm = (int)$pdo->query("SELECT COUNT(*) FROM admins WHERE usuario='admin'")->fetchColumn();
    if (!$existsAdm) {
        $pdo->prepare('INSERT INTO admins (usuario, nombre, password_hash) VALUES (?,?,?)')
            ->execute(['admin', 'Administrador', password_hash('admin1234', PASSWORD_BCRYPT)]);
    }

    // Profesores (30)
    $profCount = (int)$pdo->query('SELECT COUNT(*) FROM profesores')->fetchColumn();
    if ($profCount < 30) {
        for ($i = 1; $i <= 30; $i++) {
            $carreraId = (($i - 1) % count($carreras)) + 1;
            $pdo->prepare('INSERT INTO profesores (usuario, nombre, apellido, carrera_id, password_hash) VALUES (?,?,?,?,?)')
                ->execute(['prof' . $i, 'Profesor ' . $i, 'ITSUR', $carreraId, password_hash('prof' . $i . '1234', PASSWORD_BCRYPT)]);
        }
    }

    // Materias por carrera y semestre (9 semestres, 5 materias por sem)
    $matCount = (int)$pdo->query('SELECT COUNT(*) FROM materias')->fetchColumn();
    if ($matCount === 0) {
        $carreraIds = $pdo->query('SELECT id FROM carreras')->fetchAll(PDO::FETCH_COLUMN);
        foreach ($carreraIds as $cid) {
            for ($s = 1; $s <= 9; $s++) {
                for ($m = 1; $m <= 5; $m++) {
                    $nombre = "Materia S{$s}-{$m} (Carrera {$cid})";
                    $pdo->prepare('INSERT INTO materias (carrera_id, nombre, semestre, unidades) VALUES (?,?,?,?)')
                        ->execute([$cid, $nombre, $s, 5]);
                }
            }
        }
    }

    // Alumnos (50)
    $alCount = (int)$pdo->query('SELECT COUNT(*) FROM alumnos')->fetchColumn();
    if ($alCount < 50) {
        for ($i = 1; $i <= 50; $i++) {
            $mat = str_pad((string)(700000000 + $i), 9, '0', STR_PAD_LEFT);
            $carreraId = (($i - 1) % count($carreras)) + 1;
            $sem = (($i - 1) % 9) + 1;
            $pdo->prepare('INSERT INTO alumnos (matricula, nombre, apellido, carrera_id, semestre_actual, password_hash) VALUES (?,?,?,?,?,?)')
                ->execute([$mat, 'Alumno ' . $i, 'ITSUR', $carreraId, $sem, password_hash('alumno' . $i . '1234', PASSWORD_BCRYPT)]);
        }
    }

    // Grupos: crear 2 por materia de semestre actual (periodo activo)
    $periodoId = (int)$pdo->query('SELECT id FROM periodos WHERE activo=1 LIMIT 1')->fetchColumn();
    if (!$periodoId) {
        $pdo->prepare('INSERT INTO periodos (nombre, activo) VALUES (?,1)')->execute(['2025-1']);
        $periodoId = (int)$pdo->lastInsertId();
    }

    $profIds = $pdo->query('SELECT id, carrera_id FROM profesores')->fetchAll(PDO::FETCH_ASSOC);
    $profByCarrera = [];
    foreach ($profIds as $p) { $profByCarrera[$p['carrera_id']][] = $p['id']; }

    $mats = $pdo->query('SELECT id, carrera_id, semestre FROM materias')->fetchAll(PDO::FETCH_ASSOC);
    $createdGrupos = 0;
    foreach ($mats as $m) {
        // crear un grupo por materia
        $profList = $profByCarrera[$m['carrera_id']] ?? [];
        if (!$profList) continue;
        $profId = $profList[array_rand($profList)];
        $clave = 'G' . $m['id'];
        $salon = 'A-' . rand(1, 20);
        $pdo->prepare('INSERT INTO grupos (carrera_id, materia_id, profesor_id, clave, salon) VALUES (?,?,?,?,?)')
            ->execute([$m['carrera_id'], $m['id'], $profId, $clave, $salon]);
        $grupoId = (int)$pdo->lastInsertId();
        // Horarios
        $dias = ['Lunes','Martes','Miércoles','Jueves','Viernes'];
        $dia = $dias[array_rand($dias)];
        $inicio = sprintf('%02d:00:00', rand(7, 18));
        $fin = date('H:i:s', strtotime($inicio) + 60*60);
        $pdo->prepare('INSERT INTO horarios (grupo_id, dia, hora_inicio, hora_fin) VALUES (?,?,?,?)')
            ->execute([$grupoId, $dia, $inicio, $fin]);
        $createdGrupos++;
        if ($createdGrupos > 300) break; // límite razonable
    }

    // Inscripciones y calificaciones aleatorias
    $alumnos = $pdo->query('SELECT id, carrera_id FROM alumnos')->fetchAll(PDO::FETCH_ASSOC);
    foreach ($alumnos as $a) {
        // elegir 6 grupos de su carrera
        $grupos = $pdo->prepare('SELECT id FROM grupos WHERE carrera_id=? ORDER BY RAND() LIMIT 6');
        $grupos->execute([$a['carrera_id']]);
        $grs = $grupos->fetchAll(PDO::FETCH_COLUMN);
        foreach ($grs as $gid) {
            $estatuses = ['Cursando','Aprobada','Reprobada'];
            $estatus = $estatuses[array_rand($estatuses)];
            $pdo->prepare('INSERT INTO inscripciones (alumno_id, grupo_id, periodo_id, estatus) VALUES (?,?,?,?)')
                ->execute([$a['id'], $gid, $periodoId, $estatus]);
            $inscId = (int)$pdo->lastInsertId();
            $unidades = 5;
            for ($u=1;$u<=$unidades;$u++) {
                $nota = rand(60, 100);
                $pdo->prepare('INSERT INTO calificaciones (inscripcion_id, unidad, calificacion) VALUES (?,?,?)')
                    ->execute([$inscId, $u, $nota]);
            }
        }
    }
}