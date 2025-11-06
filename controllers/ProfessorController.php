<?php
namespace Controllers;

use Core\Controller;
use Core\Database;
use PDO;

class ProfessorController extends Controller
{
    public function dashboard(): void
    {
        $this->requireRole('professor');
        $pdo = Database::getConnection();
        $profId = $_SESSION['user']['id'];
        $stats = [
            'grupos' => (int)(function() use($pdo,$profId){$s=$pdo->prepare('SELECT COUNT(*) FROM grupos WHERE profesor_id=?');$s->execute([$profId]);return $s->fetchColumn();})(),
            'alumnos' => (int)(function() use($pdo,$profId){$s=$pdo->prepare('SELECT COUNT(*) FROM inscripciones i JOIN grupos g ON g.id=i.grupo_id WHERE g.profesor_id=?');$s->execute([$profId]);return $s->fetchColumn();})(),
        ];
        // Promedio por grupo
        $promediosPorGrupo = [];
        $stmt = $pdo->prepare('SELECT g.clave AS grupo, ROUND(AVG(COALESCE(c.segunda_oportunidad, c.calificacion)),2) AS promedio
            FROM grupos g
            JOIN inscripciones i ON i.grupo_id=g.id
            LEFT JOIN calificaciones c ON c.inscripcion_id=i.id
            WHERE g.profesor_id=?
            GROUP BY g.id
            ORDER BY g.clave');
        $stmt->execute([$profId]);
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) { $promediosPorGrupo[] = $row; }

        // Distribución de calificaciones por alumno (promedio por inscripcion)
        $dist = ['reprobados'=>0,'aprobados'=>0,'destacados'=>0];
        $stmt2 = $pdo->prepare('SELECT ROUND(AVG(COALESCE(c.segunda_oportunidad, c.calificacion)),2) AS prom
            FROM inscripciones i
            JOIN grupos g ON g.id=i.grupo_id
            LEFT JOIN calificaciones c ON c.inscripcion_id=i.id
            WHERE g.profesor_id=?
            GROUP BY i.id');
        $stmt2->execute([$profId]);
        while($r = $stmt2->fetch(PDO::FETCH_ASSOC)){
            $p = (float)$r['prom'];
            if ($p < 70) $dist['reprobados']++;
            elseif ($p >= 90) $dist['destacados']++;
            else $dist['aprobados']++;
        }
        // Grupos por materia (para este profesor)
        $gruposPorMateria = [];
        $stmt3 = $pdo->prepare('SELECT m.nombre AS materia, COUNT(g.id) AS total FROM grupos g JOIN materias m ON m.id=g.materia_id WHERE g.profesor_id=? GROUP BY m.id ORDER BY m.nombre');
        $stmt3->execute([$profId]);
        while($row = $stmt3->fetch(PDO::FETCH_ASSOC)) { $gruposPorMateria[] = $row; }
        // Materias asignadas (distintas)
        $materiasAsignadas = (int)(function() use($pdo,$profId){$s=$pdo->prepare('SELECT COUNT(DISTINCT materia_id) FROM grupos WHERE profesor_id=?');$s->execute([$profId]);return $s->fetchColumn();})();
        // Promedio total
        $promTotalStmt = $pdo->prepare('SELECT ROUND(AVG(COALESCE(c.segunda_oportunidad, c.calificacion)),2) FROM grupos g JOIN inscripciones i ON i.grupo_id=g.id LEFT JOIN calificaciones c ON c.inscripcion_id=i.id WHERE g.profesor_id=?');
        $promedioTotal = (float)($promTotalStmt->execute([$profId]) ? ($promTotalStmt->fetchColumn() ?: 0) : 0);
        // Estadísticas personalizadas del profesor
        $totalGrupos = (int)$stats['grupos'];
        $totalAlumnos = (int)$stats['alumnos'];
        $promedioGeneral = (float)$promedioTotal;

        $this->render('professor/dashboard', [
            'stats' => $stats,
            'promedios_por_grupo' => $promediosPorGrupo,
            'distribucion' => $dist,
            'grupos_por_materia' => $gruposPorMateria,
            'materias_asignadas' => $materiasAsignadas,
            'promedio_total' => $promedioTotal,
            // nuevos datos personalizados
            'totalGrupos' => $totalGrupos,
            'totalAlumnos' => $totalAlumnos,
            'promedioGeneral' => $promedioGeneral,
        ]);
    }

    public function groups(): void
    {
        $this->requireRole('professor');
        $pdo = Database::getConnection();
        $profId = $_SESSION['user']['id'];
        $stmt = $pdo->prepare('SELECT g.id, g.clave, g.salon, m.nombre AS materia FROM grupos g JOIN materias m ON m.id=g.materia_id WHERE g.profesor_id=?');
        $stmt->execute([$profId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->render('professor/groups', ['rows' => $rows]);
    }

    public function group(): void
    {
        $this->requireRole('professor');
        $pdo = Database::getConnection();
        $profId = $_SESSION['user']['id'];
        $grupoId = (int)(\Core\Security::input('id','GET',FILTER_SANITIZE_NUMBER_INT) ?? 0);
        $s = $pdo->prepare('SELECT COUNT(*) FROM grupos WHERE id=? AND profesor_id=?');
        $s->execute([$grupoId,$profId]);
        $own = (int)$s->fetchColumn();
        if (!$own) {
            $_SESSION['error'] = 'Acceso denegado a grupo';
            $this->redirect('professor/groups');
        }
        $stmt = $pdo->prepare('SELECT i.id as inscripcion_id, a.matricula, a.nombre, a.apellido FROM inscripciones i JOIN alumnos a ON a.id=i.alumno_id WHERE i.grupo_id=?');
        $stmt->execute([$grupoId]);
        $alumnos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->render('professor/group', ['alumnos' => $alumnos, 'grupoId' => $grupoId]);
    }

    public function updateGrades(): void
    {
        $this->requireRole('professor');
        $pdo = Database::getConnection();
        $csrf = \Core\Security::input('csrf_token');
        if (!\Core\Security::verifyCsrf($csrf)) {
            $_SESSION['error'] = 'CSRF inválido';
            $this->redirect('professor/groups');
        }
        $grupoId = (int)(\Core\Security::input('grupo_id','POST',FILTER_SANITIZE_NUMBER_INT) ?? 0);
        $profId = $_SESSION['user']['id'];
        $s = $pdo->prepare('SELECT COUNT(*) FROM grupos WHERE id=? AND profesor_id=?');
        $s->execute([$grupoId,$profId]);
        $own = (int)$s->fetchColumn();
        if (!$own) {
            $_SESSION['error'] = 'Acceso denegado';
            $this->redirect('professor/groups');
        }
        $unidades = max(3, min(11, (int)($_POST['unidades'] ?? 5)));
        foreach ($_POST['grades'] ?? [] as $inscripcionId => $unidadesVals) {
            for ($u = 1; $u <= $unidades; $u++) {
                $val = $unidadesVals[$u] ?? null;
                if ($val !== null && $val !== '') {
                    $stmt = $pdo->prepare('REPLACE INTO calificaciones (inscripcion_id, unidad, calificacion) VALUES (?,?,?)');
                    $stmt->execute([$inscripcionId, $u, floatval($val)]);
                }
            }
        }
        $_SESSION['success'] = 'Calificaciones actualizadas';
        $this->redirect('professor/group&id=' . $grupoId);
    }
}