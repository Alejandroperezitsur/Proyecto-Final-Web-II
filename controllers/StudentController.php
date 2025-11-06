<?php
namespace Controllers;

use Core\Controller;
use Core\Database;
use PDO;

class StudentController extends Controller
{
    public static function reinscripcionDisponible(): bool
    {
        return \Controllers\AdminController::isReinscripcionActiva();
    }

    public function dashboard(): void
    {
        $this->requireRole('student');
        $pdo = Database::getConnection();
        $alumnoId = $_SESSION['user']['id'];
        $stats = [
            'aprobadas' => (int) (function() use($pdo,$alumnoId){$s=$pdo->prepare("SELECT COUNT(*) FROM inscripciones i WHERE i.alumno_id=? AND i.estatus='Aprobada'");$s->execute([$alumnoId]);return $s->fetchColumn();})(),
            'cursando' => (int) (function() use($pdo,$alumnoId){$s=$pdo->prepare("SELECT COUNT(*) FROM inscripciones i WHERE i.alumno_id=? AND i.estatus='Cursando'");$s->execute([$alumnoId]);return $s->fetchColumn();})(),
            'pendientes' => (int) (function() use($pdo,$alumnoId){$s=$pdo->prepare("SELECT COUNT(*) FROM materias m WHERE m.semestre > (SELECT semestre_actual FROM alumnos WHERE id=?)");$s->execute([$alumnoId]);return $s->fetchColumn();})(),
        ];
        // Estadísticas personalizadas del alumno
        $totalMateriasStmt = $pdo->prepare('SELECT COUNT(*) FROM inscripciones WHERE alumno_id=?');
        $totalMateriasStmt->execute([$alumnoId]);
        $totalMaterias = (int)($totalMateriasStmt->fetchColumn() ?: 0);
        // Aprobadas por promedio de calificaciones (>=70). Si no hay calificaciones, no cuenta.
        $aprobadasPromStmt = $pdo->prepare('SELECT COUNT(*) FROM (
            SELECT i.id, AVG(COALESCE(c.segunda_oportunidad, c.calificacion)) AS promedio
            FROM inscripciones i
            LEFT JOIN calificaciones c ON c.inscripcion_id=i.id
            WHERE i.alumno_id=?
            GROUP BY i.id
        ) t WHERE t.promedio >= 70');
        $aprobadasPromStmt->execute([$alumnoId]);
        $materiasAprobadas = (int)($aprobadasPromStmt->fetchColumn() ?: 0);
        $materiasPendientes = max(0, $totalMaterias - $materiasAprobadas);
        $promStmtGeneral = $pdo->prepare('SELECT ROUND(AVG(COALESCE(c.segunda_oportunidad, c.calificacion)),2)
            FROM inscripciones i LEFT JOIN calificaciones c ON c.inscripcion_id=i.id WHERE i.alumno_id=?');
        $promStmtGeneral->execute([$alumnoId]);
        $promedio = (float)($promStmtGeneral->fetchColumn() ?: 0);
        // Carrera y total de materias
        $q = $pdo->prepare("SELECT carrera_id, semestre_actual FROM alumnos WHERE id=?");
        $q->execute([$alumnoId]);
        $info = $q->fetch(PDO::FETCH_ASSOC);
        $carreraId = (int)($info['carrera_id'] ?? 0);
        $totalMaterias = (int)(function() use($pdo,$carreraId){$s=$pdo->prepare('SELECT COUNT(*) FROM materias WHERE carrera_id=?');$s->execute([$carreraId]);return $s->fetchColumn();})();
        // Promedio actual del semestre (cursando)
        $promStmt = $pdo->prepare("SELECT ROUND(AVG(COALESCE(c.segunda_oportunidad, c.calificacion)),2) FROM inscripciones i LEFT JOIN calificaciones c ON c.inscripcion_id=i.id WHERE i.alumno_id=? AND i.estatus='Cursando'");
        $promStmt->execute([$alumnoId]);
        $promedioActual = (float)($promStmt->fetchColumn() ?: 0);
        // Historial de promedios por semestre
        $histStmt = $pdo->prepare("SELECT m.semestre, ROUND(AVG(COALESCE(c.segunda_oportunidad, c.calificacion)),2) AS promedio
            FROM inscripciones i
            JOIN grupos g ON g.id=i.grupo_id
            JOIN materias m ON m.id=g.materia_id
            LEFT JOIN calificaciones c ON c.inscripcion_id=i.id
            WHERE i.alumno_id=?
            GROUP BY m.semestre
            ORDER BY m.semestre");
        $histStmt->execute([$alumnoId]);
        $historial = $histStmt->fetchAll(PDO::FETCH_ASSOC);
        // Avance %
        $avance = $totalMaterias > 0 ? round(($stats['aprobadas'] / $totalMaterias) * 100, 2) : 0;
        // Créditos (aproximación si no hay columna de créditos)
        $creditos = $stats['aprobadas'];
        $this->render('student/dashboard', [
            'stats' => $stats,
            'promedio_actual' => $promedioActual,
            'total_materias' => $totalMaterias,
            'avance_porcentaje' => $avance,
            'creditos' => $creditos,
            'historial' => $historial,
            'reinscripcion_activa' => \Controllers\AdminController::isReinscripcionActiva(),
            // nuevos datos personalizados del alumno
            'totalMaterias' => $totalMaterias,
            'materiasAprobadas' => $materiasAprobadas,
            'materiasPendientes' => $materiasPendientes,
            'promedio' => $promedio,
        ]);
    }

    public function cardex(): void
    {
        $this->requireRole('student');
        $pdo = Database::getConnection();
        $alumnoId = $_SESSION['user']['id'];
        $stmt = $pdo->prepare("SELECT m.nombre as materia, m.semestre, i.estatus,
            ROUND(AVG(c.calificacion),2) as final
            FROM inscripciones i
            JOIN grupos g ON g.id=i.grupo_id
            JOIN materias m ON m.id=g.materia_id
            LEFT JOIN calificaciones c ON c.inscripcion_id=i.id
            WHERE i.alumno_id=?
            GROUP BY i.id
            ORDER BY m.semestre, m.nombre");
        $stmt->execute([$alumnoId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->render('student/cardex', ['rows' => $rows]);
    }

    public function grades(): void
    {
        $this->requireRole('student');
        $pdo = Database::getConnection();
        $alumnoId = $_SESSION['user']['id'];
        $stmt = $pdo->prepare("SELECT m.nombre as materia, c.unidad, c.calificacion, c.segunda_oportunidad
            FROM inscripciones i
            JOIN grupos g ON g.id=i.grupo_id
            JOIN materias m ON m.id=g.materia_id
            JOIN calificaciones c ON c.inscripcion_id=i.id
            WHERE i.alumno_id=? AND i.estatus='Cursando'
            ORDER BY m.nombre, c.unidad");
        $stmt->execute([$alumnoId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->render('student/grades', ['rows' => $rows]);
    }

    public function schedule(): void
    {
        $this->requireRole('student');
        $pdo = Database::getConnection();
        $alumnoId = $_SESSION['user']['id'];
        $stmt = $pdo->prepare("SELECT m.nombre as materia, g.clave as grupo, g.salon, h.dia, h.hora_inicio, h.hora_fin
            FROM inscripciones i
            JOIN grupos g ON g.id=i.grupo_id
            JOIN materias m ON m.id=g.materia_id
            JOIN horarios h ON h.grupo_id=g.id
            WHERE i.alumno_id=? AND i.estatus='Cursando'");
        $stmt->execute([$alumnoId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->render('student/schedule', ['rows' => $rows]);
    }

    public function reticula(): void
    {
        $this->requireRole('student');
        $pdo = Database::getConnection();
        $alumnoId = $_SESSION['user']['id'];
        $q = $pdo->prepare("SELECT carrera_id FROM alumnos WHERE id=?");
        $q->execute([$alumnoId]);
        $carreraId = (int)$q->fetchColumn();
        $materias = $pdo->prepare("SELECT id, nombre, semestre FROM materias WHERE carrera_id=? ORDER BY semestre");
        $materias->execute([$carreraId]);
        $list = $materias->fetchAll(PDO::FETCH_ASSOC);
        // estatus por materia
        $aprobadas = $pdo->prepare("SELECT DISTINCT g.materia_id FROM inscripciones i JOIN grupos g ON g.id=i.grupo_id WHERE i.alumno_id=? AND i.estatus='Aprobada'");
        $aprobadas->execute([$alumnoId]);
        $aprobIds = array_column($aprobadas->fetchAll(PDO::FETCH_ASSOC), 'materia_id');
        $cursando = $pdo->prepare("SELECT DISTINCT g.materia_id FROM inscripciones i JOIN grupos g ON g.id=i.grupo_id WHERE i.alumno_id=? AND i.estatus='Cursando'");
        $cursando->execute([$alumnoId]);
        $cursaIds = array_column($cursando->fetchAll(PDO::FETCH_ASSOC), 'materia_id');
        $this->render('student/reticula', ['materias' => $list, 'aprob' => $aprobIds, 'cursa' => $cursaIds]);
    }

    public function reinscripcion(): void
    {
        $this->requireRole('student');
        $pdo = Database::getConnection();
        $alumnoId = $_SESSION['user']['id'];
        $periodoActivo = (int)$pdo->query("SELECT id FROM periodos WHERE activo=1 LIMIT 1")->fetchColumn();
        // CSRF
        $csrf = \Core\Security::input('csrf_token');
        if (!\Core\Security::verifyCsrf($csrf)) {
            $_SESSION['error'] = 'CSRF inválido';
            $this->redirect('student/dashboard');
        }
        if (!$periodoActivo) {
            $_SESSION['error'] = 'No hay periodo activo para reinscripción';
            $this->redirect('student/dashboard');
        }
        $materiasIds = $_POST['materias'] ?? [];
        foreach ($materiasIds as $materiaId) {
            // Seleccionar un grupo de la materia y carrera
            $stmt = $pdo->prepare('SELECT id FROM grupos WHERE materia_id=? LIMIT 1');
            $stmt->execute([$materiaId]);
            $grupoId = $stmt->fetchColumn();
            if ($grupoId) {
                $ins = $pdo->prepare('INSERT INTO inscripciones (alumno_id, grupo_id, periodo_id, estatus) VALUES (?,?,?,\'Cursando\')');
                $ins->execute([$alumnoId, $grupoId, $periodoActivo]);
            }
        }
        $_SESSION['success'] = 'Reinscripción realizada';
        $this->redirect('student/dashboard');
    }
}