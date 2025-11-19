<?php
namespace App\Controllers\Api;

use App\Services\GradesService;
use App\Services\GroupsService;
use App\Services\SubjectsService;
use PDO;

class KpiController
{
    private PDO $pdo;
    private GradesService $grades;
    private GroupsService $groups;
    private SubjectsService $subjects;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->grades = new GradesService($pdo);
        $this->groups = new GroupsService($pdo);
        $this->subjects = new SubjectsService($pdo);
    }

    public function admin(): void
    {
        header('Content-Type: application/json');
        $totalAlumnos = (int)$this->pdo->query('SELECT COUNT(*) FROM alumnos')->fetchColumn();
        $totalProfesores = (int)$this->pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'profesor' AND activo = 1")->fetchColumn();
        if ($totalProfesores === 0) {
            $pwd = password_hash('demo123', PASSWORD_BCRYPT);
            $stmt = $this->pdo->prepare("INSERT INTO usuarios (matricula, email, nombre, password, rol, activo) VALUES (:mat,:em,:nom,:pwd,'profesor',1)");
            $stmt->execute([':mat' => 'P' . random_int(10000000,99999999), ':em' => 'prof.demo@example.com', ':nom' => 'Profesor Demo', ':pwd' => $pwd]);
            $totalProfesores = (int)$this->pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'profesor' AND activo = 1")->fetchColumn();
        }
        $totalMaterias = $this->subjects->count();
        $activosGrupos = $this->groups->count();

        if ($totalMaterias === 0) {
            $this->pdo->exec("INSERT INTO materias (nombre, clave) VALUES
                ('Matemáticas I','MAT1'),('Programación I','PRO1'),('Física I','FIS1'),('Química I','QUI1'),('Inglés I','ING1')");
            $totalMaterias = $this->subjects->count();
        }

        if ($activosGrupos === 0 && $totalProfesores > 0) {
            $profs = $this->pdo->query("SELECT id FROM usuarios WHERE rol = 'profesor' AND activo = 1")->fetchAll(PDO::FETCH_ASSOC);
            $mats = $this->pdo->query("SELECT id, clave FROM materias ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
            $ins = $this->pdo->prepare('INSERT INTO grupos (materia_id, profesor_id, nombre, ciclo) VALUES (:m,:p,:n,:c)');
            foreach ($profs as $idx => $p) {
                $pid = (int)$p['id'];
                for ($k=0; $k<min(3,count($mats)); $k++) {
                    $m = $mats[$k];
                    $name = ($m['clave'] ?? ('GRP'.($k+1))) . '-G' . str_pad((string)($idx+1), 2, '0', STR_PAD_LEFT);
                    $ciclo = date('Y') . '-' . ((($k % 2) === 0) ? '1' : '2');
                    $sel = $this->pdo->prepare('SELECT 1 FROM grupos WHERE materia_id = :m AND profesor_id = :p AND nombre = :n AND ciclo = :c LIMIT 1');
                    $sel->execute([':m'=>(int)$m['id'], ':p'=>$pid, ':n'=>$name, ':c'=>$ciclo]);
                    if (!$sel->fetchColumn()) { $ins->execute([':m'=>(int)$m['id'], ':p'=>$pid, ':n'=>$name, ':c'=>$ciclo]); }
                }
            }
            $activosGrupos = $this->groups->count();
        }

        if ($totalAlumnos === 0) {
            $insA = $this->pdo->prepare('INSERT INTO alumnos (matricula, nombre, apellido, email, password, activo) VALUES (:mat,:nom,:ape,:em,:pwd,1)');
            for ($i=0;$i<10;$i++) {
                $mat = 'S' . str_pad((string)random_int(10000000, 99999999), 8, '0', STR_PAD_LEFT);
                $nom = 'Alumno' . ($i+1);
                $ape = 'Demo';
                $em = strtolower($nom) . ($i+1) . '@example.com';
                $pwd = password_hash('demo123', PASSWORD_BCRYPT);
                $sel = $this->pdo->prepare('SELECT 1 FROM alumnos WHERE matricula = :m LIMIT 1');
                $sel->execute([':m'=>$mat]);
                if (!$sel->fetchColumn()) { $insA->execute([':mat'=>$mat, ':nom'=>$nom, ':ape'=>$ape, ':em'=>$em, ':pwd'=>$pwd]); }
            }
            $totalAlumnos = (int)$this->pdo->query('SELECT COUNT(*) FROM alumnos')->fetchColumn();
        }

        $grps = $this->pdo->query('SELECT id FROM grupos')->fetchAll(PDO::FETCH_ASSOC);
        if ($grps) {
            $alIds = $this->pdo->query('SELECT id FROM alumnos WHERE activo = 1 LIMIT 20')->fetchAll(PDO::FETCH_ASSOC);
            $chk = $this->pdo->prepare('SELECT 1 FROM calificaciones WHERE alumno_id = :a AND grupo_id = :g LIMIT 1');
            $insC = $this->pdo->prepare('INSERT INTO calificaciones (alumno_id, grupo_id, parcial1, parcial2, final) VALUES (:a,:g,:p1,:p2,:fin)');
            foreach ($grps as $g) {
                $gid = (int)$g['id'];
                foreach (array_slice($alIds, 0, 5) as $a) {
                    $aid = (int)$a['id'];
                    $chk->execute([':a'=>$aid, ':g'=>$gid]);
                    if (!$chk->fetchColumn()) {
                        $p1 = random_int(50, 95);
                        $p2 = random_int(50, 95);
                        $fin = (random_int(0, 3) === 0) ? null : random_int(50, 95);
                        $insC->execute([':a'=>$aid, ':g'=>$gid, ':p1'=>$p1, ':p2'=>$p2, ':fin'=>$fin]);
                    }
                }
            }
        }

        $promedioGeneral = $this->grades->globalAverage();
        $pendientes = (int)$this->pdo->query('SELECT COUNT(*) FROM calificaciones WHERE final IS NULL')->fetchColumn();
        echo json_encode([
            'alumnos' => $totalAlumnos,
            'profesores' => $totalProfesores,
            'materias' => $totalMaterias,
            'promedio' => $promedioGeneral,
            'grupos' => $activosGrupos,
            'pendientes_evaluacion' => $pendientes,
        ]);
    }

    public function profesorDashboard(int $profesorId): void
    {
        header('Content-Type: application/json');
        $grupos = $this->groups->activeByTeacher($profesorId);
        if (!$grupos) {
            $mats = $this->pdo->query('SELECT id, clave FROM materias ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
            if ($mats) {
                $ins = $this->pdo->prepare('INSERT INTO grupos (materia_id, profesor_id, nombre, ciclo) VALUES (:m,:p,:n,:c)');
                for ($k=0; $k<min(3,count($mats)); $k++) {
                    $m = $mats[$k];
                    $name = ($m['clave'] ?? ('GRP'.($k+1))) . '-G' . str_pad((string)$profesorId, 2, '0', STR_PAD_LEFT);
                    $ciclo = date('Y') . '-' . ((($k % 2) === 0) ? '1' : '2');
                    $sel = $this->pdo->prepare('SELECT 1 FROM grupos WHERE materia_id = :m AND profesor_id = :p AND nombre = :n AND ciclo = :c LIMIT 1');
                    $sel->execute([':m'=>(int)$m['id'], ':p'=>$profesorId, ':n'=>$name, ':c'=>$ciclo]);
                    if (!$sel->fetchColumn()) { $ins->execute([':m'=>(int)$m['id'], ':p'=>$profesorId, ':n'=>$name, ':c'=>$ciclo]); }
                }
                $grupos = $this->groups->activeByTeacher($profesorId);
                if ($grupos) {
                    $als = $this->pdo->query('SELECT id FROM alumnos WHERE activo = 1 LIMIT 12')->fetchAll(PDO::FETCH_ASSOC);
                    $chk = $this->pdo->prepare('SELECT 1 FROM calificaciones WHERE alumno_id = :a AND grupo_id = :g LIMIT 1');
                    $insC = $this->pdo->prepare('INSERT INTO calificaciones (alumno_id, grupo_id, parcial1, parcial2, final) VALUES (:a,:g,:p1,:p2,:fin)');
                    foreach ($grupos as $g) {
                        $gid = (int)$g['id'];
                        foreach (array_slice($als, 0, 4) as $a) {
                            $aid = (int)$a['id'];
                            $chk->execute([':a'=>$aid, ':g'=>$gid]);
                            if (!$chk->fetchColumn()) {
                                $p1 = random_int(50, 95);
                                $p2 = random_int(50, 95);
                                $fin = (random_int(0, 3) === 0) ? null : random_int(50, 95);
                                $insC->execute([':a'=>$aid, ':g'=>$gid, ':p1'=>$p1, ':p2'=>$p2, ':fin'=>$fin]);
                            }
                        }
                    }
                }
            }
        }
        $totalAlumnos = 0;
        foreach ($grupos as $g) { $totalAlumnos += (int)($g['alumnos'] ?? 0); }
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM calificaciones c JOIN grupos g ON g.id = c.grupo_id WHERE g.profesor_id = :pid AND c.final IS NULL");
        $stmt->execute([':pid' => $profesorId]);
        $pendientes = (int)($stmt->fetchColumn() ?: 0);
        echo json_encode([
            'grupos_activos' => count($grupos),
            'alumnos' => $totalAlumnos,
            'grupos' => $grupos,
            'pendientes' => $pendientes,
        ]);
    }
}
