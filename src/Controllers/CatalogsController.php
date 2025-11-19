<?php
namespace App\Controllers;

use PDO;

class CatalogsController
{
    private PDO $pdo;
    private int $ttlSeconds = 300; // cache ligera 5 minutos

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $_SESSION['catalog_cache'] = $_SESSION['catalog_cache'] ?? [];
    }

    private function getCache(string $key): ?array
    {
        $entry = $_SESSION['catalog_cache'][$key] ?? null;
        if (!$entry) { return null; }
        if ((time() - (int)$entry['ts']) > $this->ttlSeconds) { return null; }
        return (array)$entry['data'];
    }

    private function setCache(string $key, array $data): void
    {
        $_SESSION['catalog_cache'][$key] = ['ts' => time(), 'data' => $data];
    }

    private function ensureCompleteData(): void
    {
        $cfg = @include __DIR__ . '/../../config/config.php';
        $seedGroups = (int)($cfg['academic']['seed_min_groups_per_cycle'] ?? 2);
        $seedGradesMin = (int)($cfg['academic']['seed_min_grades_per_group'] ?? 18);
        $seedStudentsPool = (int)($cfg['academic']['seed_students_pool'] ?? 40);
        $createdMaterias = 0;
        $createdGroups = 0;
        $createdAlumnos = 0;
        $createdCalificaciones = 0;

        $profes = $this->pdo->query("SELECT id, nombre FROM usuarios WHERE rol = 'profesor' AND activo = 1")->fetchAll(PDO::FETCH_ASSOC);
        if (!$profes) { return; }
        $mats = $this->pdo->query('SELECT id, nombre, clave FROM materias ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
        if (!$mats) {
            $seed = [
                ['nombre' => 'Programación I', 'clave' => 'INF101'],
                ['nombre' => 'Estructuras de Datos', 'clave' => 'INF201'],
                ['nombre' => 'Bases de Datos', 'clave' => 'INF202'],
                ['nombre' => 'Álgebra Lineal', 'clave' => 'MAT102'],
            ];
            $insM = $this->pdo->prepare('INSERT INTO materias (nombre, clave) VALUES (:n,:c)');
            foreach ($seed as $s) { $insM->execute([':n'=>$s['nombre'], ':c'=>$s['clave']]); $createdMaterias++; }
            $mats = $this->pdo->query('SELECT id, nombre, clave FROM materias ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
        }
        $matPrim = $mats[0] ?? null; if (!$matPrim) { return; }

        $rowsCycles = $this->pdo->query('SELECT DISTINCT ciclo FROM grupos ORDER BY ciclo DESC')->fetchAll(PDO::FETCH_ASSOC);
        $cycles = array_map(fn($x)=>strtoupper((string)$x['ciclo']), $rowsCycles);
        $year = (int)date('Y');
        foreach ([$year.'-1', $year.'-2'] as $c) { if (!in_array($c, $cycles, true)) { $cycles[] = $c; } }
        // Si existen ciclos con formato A/B del año anterior, también úsalos
        foreach ([$year-1 . 'A', $year-1 . 'B'] as $c) { if (!in_array($c, $cycles, true)) { $cycles[] = $c; } }

        $insG = $this->pdo->prepare('INSERT INTO grupos (materia_id, profesor_id, nombre, ciclo) VALUES (:m,:p,:n,:c)');
        $selCnt = $this->pdo->prepare('SELECT COUNT(*) FROM grupos WHERE profesor_id = :p AND ciclo = :c');
        $selByName = $this->pdo->prepare('SELECT id FROM grupos WHERE profesor_id = :p AND ciclo = :c AND nombre = :n LIMIT 1');
        foreach ($profes as $prof) {
            $pid = (int)$prof['id'];
            foreach ($cycles as $ciclo) {
                $selCnt->execute([':p'=>$pid, ':c'=>$ciclo]);
                $count = (int)($selCnt->fetchColumn() ?: 0);
                $desired = [];
                for ($k=1; $k<=$seedGroups; $k++) {
                    $matIndex = ($k-1) % max(count($mats),1);
                    $matUse = $mats[$matIndex] ?? $matPrim;
                    $desired[] = [ 'name' => ($matUse['clave'] ?? 'MAT') . '-G' . str_pad((string)$k, 2, '0', STR_PAD_LEFT), 'mid' => (int)$matUse['id'] ];
                }
                foreach ($desired as $d) {
                    $selByName->execute([':p'=>$pid, ':c'=>$ciclo, ':n'=>$d['name']]);
                    if (!(int)($selByName->fetchColumn() ?: 0)) {
                        $insG->execute([':m'=>$d['mid'], ':p'=>$pid, ':n'=>$d['name'], ':c'=>$ciclo]);
                        $createdGroups++;
                    }
                }
                // Asegurar alumnos y calificaciones para cada grupo del ciclo
                $groups = $this->pdo->prepare('SELECT id FROM grupos WHERE profesor_id = :p AND ciclo = :c');
                $groups->execute([':p'=>$pid, ':c'=>$ciclo]);
                $gids = $groups->fetchAll(PDO::FETCH_ASSOC);
                $alumnos = $this->pdo->query('SELECT id FROM alumnos WHERE activo = 1 ORDER BY id LIMIT '.max($seedStudentsPool,1))->fetchAll(PDO::FETCH_ASSOC);
                if (!$alumnos) {
                    $insA = $this->pdo->prepare('INSERT INTO alumnos (matricula, nombre, apellido, activo) VALUES (:mat,:nom,:ape,1)');
                    for ($i=1; $i<=$seedStudentsPool; $i++) {
                        $insA->execute([':mat' => 'A'.str_pad((string)($year*10000+$i), 6, '0', STR_PAD_LEFT), ':nom' => 'Alumno'.$i, ':ape' => 'Demo']);
                    }
                    $alumnos = $this->pdo->query('SELECT id FROM alumnos WHERE activo = 1 ORDER BY id LIMIT '.max($seedStudentsPool,1))->fetchAll(PDO::FETCH_ASSOC);
                    $createdAlumnos += $seedStudentsPool;
                }
                foreach ($gids as $g) {
                    $gid = (int)$g['id'];
                    $countCal = $this->pdo->prepare('SELECT COUNT(*) FROM calificaciones WHERE grupo_id = :g');
                    $countCal->execute([':g'=>$gid]);
                    $existingCount = (int)($countCal->fetchColumn() ?: 0);
                    if ($existingCount < $seedGradesMin) {
                        $insC = $this->pdo->prepare('INSERT INTO calificaciones (alumno_id, grupo_id, parcial1, parcial2, final, promedio) VALUES (:a,:g,:p1,:p2,:f,:pr)');
                        $nToAdd = max($seedGradesMin, 1);
                        $i = 0;
                        foreach ($alumnos as $aRow) {
                            if ($i >= $nToAdd) { break; }
                            $aid = (int)$aRow['id'];
                            $exists = $this->pdo->prepare('SELECT 1 FROM calificaciones WHERE alumno_id = :a AND grupo_id = :g LIMIT 1');
                            $exists->execute([':a'=>$aid, ':g'=>$gid]);
                            if ($exists->fetchColumn()) { continue; }
                            $p1 = random_int(60, 95); $p2 = random_int(55, 98);
                            $final = (int)round(($p1 + $p2) / 2 + random_int(-5,5));
                            $final = max(50, min(100, $final));
                            $prom = round(($final), 2);
                            $insC->execute([':a'=>$aid, ':g'=>$gid, ':p1'=>$p1, ':p2'=>$p2, ':f'=>$final, ':pr'=>$prom]);
                            $i++;
                            $createdCalificaciones++;
                        }
                    }
                }
            }
        }
        if ($createdMaterias + $createdGroups + $createdAlumnos + $createdCalificaciones > 0) {
            $_SESSION['flash'] = 'Siembra automática: ' . ($createdMaterias ? ($createdMaterias . ' materias, ') : '') . ($createdGroups ? ($createdGroups . ' grupos, ') : '') . ($createdAlumnos ? ($createdAlumnos . ' alumnos, ') : '') . ($createdCalificaciones ? ($createdCalificaciones . ' calificaciones, ') : '');
            $_SESSION['flash'] = rtrim($_SESSION['flash'], ', ');
            $_SESSION['flash_type'] = 'success';
        }
    }

    public function subjects(): void
    {
        header('Content-Type: application/json');
        $cached = $this->getCache('subjects');
        if ($cached !== null) { echo json_encode($cached); return; }
        $stmt = $this->pdo->query('SELECT id, nombre, clave FROM materias ORDER BY nombre');
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->setCache('subjects', $rows);
        echo json_encode($rows);
    }

    public function professors(): void
    {
        header('Content-Type: application/json');
        $cached = $this->getCache('professors');
        if ($cached !== null) { echo json_encode($cached); return; }
        $stmt = $this->pdo->query("SELECT id, nombre, email FROM usuarios WHERE rol = 'profesor' AND activo = 1 ORDER BY nombre");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->setCache('professors', $rows);
        echo json_encode($rows);
    }

    public function students(): void
    {
        header('Content-Type: application/json');
        $cached = $this->getCache('students');
        if ($cached !== null) { echo json_encode($cached); return; }
        $stmt = $this->pdo->query('SELECT id, matricula, CONCAT(nombre, " ", apellido) AS nombre FROM alumnos WHERE activo = 1 ORDER BY apellido, nombre');
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->setCache('students', $rows);
        echo json_encode($rows);
    }

    public function groupsByProfessor(int $profesorId): void
    {
        header('Content-Type: application/json');
        $key = 'groups_' . $profesorId;
        $cached = $this->getCache($key);
        if ($cached !== null) { echo json_encode($cached); return; }
        $stmt = $this->pdo->prepare('SELECT g.id, g.nombre, g.ciclo, m.nombre AS materia FROM grupos g JOIN materias m ON m.id = g.materia_id WHERE g.profesor_id = :p ORDER BY g.ciclo DESC, m.nombre, g.nombre');
        $stmt->execute([':p' => $profesorId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->setCache($key, $rows);
        echo json_encode($rows);
    }

    public function groupsAll(): void
    {
        header('Content-Type: application/json');
        $this->ensureCompleteData();
        $cached = $this->getCache('groups_all');
        if ($cached !== null) { echo json_encode($cached); return; }
        $stmt = $this->pdo->query('SELECT g.id, g.nombre, g.ciclo, g.profesor_id, m.nombre AS materia FROM grupos g JOIN materias m ON m.id = g.materia_id ORDER BY g.ciclo DESC, m.nombre, g.nombre');
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->setCache('groups_all', $rows);
        echo json_encode($rows);
    }

    public function cycles(): void
    {
        header('Content-Type: application/json');
        $this->ensureCompleteData();
        $cached = $this->getCache('cycles');
        if ($cached !== null) { echo json_encode($cached); return; }
        $rows = $this->pdo->query('SELECT DISTINCT ciclo FROM grupos ORDER BY ciclo DESC')->fetchAll(PDO::FETCH_ASSOC);
        $cycles = array_map(fn($x) => (string)$x['ciclo'], $rows);
        $this->setCache('cycles', $cycles);
        echo json_encode($cycles);
    }
}
