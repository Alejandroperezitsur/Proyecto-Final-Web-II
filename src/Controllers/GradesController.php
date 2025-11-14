<?php
namespace App\Controllers;

use PDO;

class GradesController
{
    private PDO $pdo;
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function index(): string
    {
        ob_start();
        $csrf = $_SESSION['csrf_token'] ?? '';
        include __DIR__ . '/../Views/grades/index.php';
        return ob_get_clean();
    }

    public function showBulkForm(): string
    {
        ob_start();
        include __DIR__ . '/../Views/grades/bulk_upload.php';
        return ob_get_clean();
    }

    public function processBulkUpload(): string
    {
        $token = $_POST['csrf_token'] ?? '';
        if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            http_response_code(403);
            return 'CSRF inválido';
        }
        $role = $_SESSION['role'] ?? '';
        $profId = (int)($_SESSION['user_id'] ?? 0);
        if (!isset($_FILES['csv']) || $_FILES['csv']['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            return 'Archivo CSV inválido';
        }
        $fp = fopen($_FILES['csv']['tmp_name'], 'r');
        $headers = fgetcsv($fp);
        $stmt = $this->pdo->prepare("UPDATE calificaciones SET parcial1 = :p1, parcial2 = :p2, final = :fin WHERE alumno_id = :alumno AND grupo_id = :grupo");
        $count = 0;
        $skipped = 0;
        $processed = 0;
        while (($row = fgetcsv($fp)) !== false) {
            // Espera columnas: alumno_id, grupo_id, parcial1, parcial2, final
            [$alumnoId, $grupoId, $p1, $p2, $fin] = $row;

            $alumnoId = filter_var($alumnoId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $grupoId = filter_var($grupoId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $p1 = ($p1 !== '' ? filter_var($p1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 100]]) : null);
            $p2 = ($p2 !== '' ? filter_var($p2, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 100]]) : null);
            $fin = ($fin !== '' ? filter_var($fin, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 100]]) : null);

            if (!$alumnoId || !$grupoId) { $skipped++; $processed++; continue; }

            // Validar alumno activo
            $chkAlumno = $this->pdo->prepare('SELECT 1 FROM alumnos WHERE id = :id AND activo = 1');
            $chkAlumno->execute([':id' => $alumnoId]);
            if (!$chkAlumno->fetchColumn()) { $skipped++; $processed++; continue; }

            // Validar grupo existente y pertenencia del profesor (si aplica)
            $chkGrupo = $this->pdo->prepare('SELECT profesor_id FROM grupos WHERE id = :id');
            $chkGrupo->execute([':id' => $grupoId]);
            $grupoRow = $chkGrupo->fetch(PDO::FETCH_ASSOC);
            if (!$grupoRow) { $skipped++; $processed++; continue; }
            if ($role === 'profesor' && (int)$grupoRow['profesor_id'] !== $profId) { $skipped++; $processed++; continue; }

            $stmt->execute([
                ':alumno' => $alumnoId,
                ':grupo' => $grupoId,
                ':p1' => $p1,
                ':p2' => $p2,
                ':fin' => $fin,
            ]);
            $count += $stmt->rowCount();
            $processed++;
        }
        fclose($fp);
        \App\Utils\Logger::info('grades_bulk_update', ['updated' => $count, 'skipped' => $skipped, 'processed' => $processed]);
        $_SESSION['bulk_last_summary'] = ['updated' => $count, 'skipped' => $skipped, 'processed' => $processed, 'ts' => time()];
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        if (strpos($accept, 'application/json') !== false) {
            header('Content-Type: application/json');
            return json_encode(['ok' => true, 'updated' => $count, 'skipped' => $skipped, 'processed' => $processed]);
        }
        return 'Registros actualizados: ' . $count . ($skipped ? "; Filas omitidas: $skipped" : '') . "; Procesadas: $processed";
    }

    public function create(): string
    {
        $token = $_POST['csrf_token'] ?? '';
        if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            http_response_code(403);
            return 'CSRF inválido';
        }

        $alumnoId = filter_input(INPUT_POST, 'alumno_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $grupoId = filter_input(INPUT_POST, 'grupo_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $p1 = ($_POST['parcial1'] ?? '') !== '' ? filter_var($_POST['parcial1'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 100]]) : null;
        $p2 = ($_POST['parcial2'] ?? '') !== '' ? filter_var($_POST['parcial2'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 100]]) : null;
        $fin = ($_POST['final'] ?? '') !== '' ? filter_var($_POST['final'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 100]]) : null;

        if (!$alumnoId || !$grupoId) {
            http_response_code(400);
            $_SESSION['flash'] = 'Datos inválidos: IDs requeridos.';
            $_SESSION['flash_type'] = 'danger';
            header('Location: /grades');
            return '';
        }

        // Upsert
        $stmt = $this->pdo->prepare('SELECT 1 FROM calificaciones WHERE alumno_id = :a AND grupo_id = :g');
        $stmt->execute([':a' => $alumnoId, ':g' => $grupoId]);
        if ($stmt->fetchColumn()) {
            $upd = $this->pdo->prepare('UPDATE calificaciones SET parcial1 = :p1, parcial2 = :p2, final = :fin WHERE alumno_id = :a AND grupo_id = :g');
            $upd->execute([':p1' => $p1, ':p2' => $p2, ':fin' => $fin, ':a' => $alumnoId, ':g' => $grupoId]);
        } else {
            $ins = $this->pdo->prepare('INSERT INTO calificaciones (alumno_id, grupo_id, parcial1, parcial2, final) VALUES (:a, :g, :p1, :p2, :fin)');
            $ins->execute([':a' => $alumnoId, ':g' => $grupoId, ':p1' => $p1, ':p2' => $p2, ':fin' => $fin]);
        }

        \App\Utils\Logger::info('grade_upsert', ['alumno_id' => $alumnoId, 'grupo_id' => $grupoId]);
        $_SESSION['flash'] = 'Calificación registrada correctamente';
        $_SESSION['flash_type'] = 'success';
        header('Location: /grades');
        return '';
    }

    public function downloadBulkLog(): string
    {
        $sum = $_SESSION['bulk_last_summary'] ?? null;
        if (!$sum) { http_response_code(404); return 'No hay log disponible'; }
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="bulk_upload_log_'.date('Ymd_His', $sum['ts']).'.json' .'"');
        return json_encode($sum);
    }
}
