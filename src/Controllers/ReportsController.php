<?php
namespace App\Controllers;

use PDO;
use App\Utils\Logger;

class ReportsController
{
    private PDO $pdo;
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    private function ascii(?string $s): string
    {
        if ($s === null) { return ''; }
        $t = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
        if ($t !== false) { $s = $t; }
        $s = strtr($s, [
            'Á'=>'A','À'=>'A','Â'=>'A','Ä'=>'A','Ã'=>'A','á'=>'a','à'=>'a','â'=>'a','ä'=>'a','ã'=>'a',
            'É'=>'E','È'=>'E','Ê'=>'E','Ë'=>'E','é'=>'e','è'=>'e','ê'=>'e','ë'=>'e',
            'Í'=>'I','Ì'=>'I','Î'=>'I','Ï'=>'I','í'=>'i','ì'=>'i','î'=>'i','ï'=>'i',
            'Ó'=>'O','Ò'=>'O','Ô'=>'O','Ö'=>'O','Õ'=>'O','ó'=>'o','ò'=>'o','ô'=>'o','ö'=>'o','õ'=>'o',
            'Ú'=>'U','Ù'=>'U','Û'=>'U','Ü'=>'U','ú'=>'u','ù'=>'u','û'=>'u','ü'=>'u',
            'Ñ'=>'N','ñ'=>'n','Ç'=>'C','ç'=>'c'
        ]);
        $s = str_replace(["'", "`"], '', $s);
        $s = preg_replace('/[^\x20-\x7E]/', '', (string)$s);
        return (string)$s;
    }

    public function index(): void
    {
        $role = $_SESSION['role'] ?? '';
        if ($role !== 'admin' && $role !== 'profesor') {
            http_response_code(403);
            echo 'No autorizado';
            return;
        }
        $view = __DIR__ . '/../Views/reports/index.php';
        if (file_exists($view)) {
            include $view;
        } else {
            echo '<div class="container py-4">Vista de reportes no encontrada.</div>';
        }
    }

    private function validateCsrf(string $token = ''): bool
    {
        $sessionToken = $_SESSION['csrf_token'] ?? '';
        return ($sessionToken !== '' && $token !== '' && hash_equals($sessionToken, $token));
    }

    private function buildWhere(array $filters, ?int $profesorIdFromSession): array
    {
        $where = [];
        $params = [];
        if (!empty($filters['ciclo'])) {
            $ciclo = trim((string)$filters['ciclo']);
            if (!preg_match('/^\d{4}-(1|2)$/', $ciclo)) {
                throw new \InvalidArgumentException('Ciclo inválido');
            }
            $where[] = 'g.ciclo = :ciclo';
            $params[':ciclo'] = $ciclo;
        }
        if (!empty($filters['grupo_id'])) {
            $gid = (int)$filters['grupo_id'];
            if ($gid > 0) { $where[] = 'g.id = :gid'; $params[':gid'] = $gid; }
        }
        // Si el rol es profesor, forzamos su propio profesor_id
        if ($profesorIdFromSession && $profesorIdFromSession > 0) {
            $where[] = 'g.profesor_id = :pid';
            $params[':pid'] = $profesorIdFromSession;
        } elseif (!empty($filters['profesor_id'])) {
            $pid = (int)$filters['profesor_id'];
            if ($pid > 0) { $where[] = 'g.profesor_id = :pid'; $params[':pid'] = $pid; }
        }
        $sqlWhere = count($where) ? ('WHERE ' . implode(' AND ', $where)) : '';
        return [$sqlWhere, $params];
    }

    public function exportCsv(): string
    {
        $role = $_SESSION['role'] ?? '';
        if ($role !== 'admin' && $role !== 'profesor') {
            http_response_code(403);
            echo 'No autorizado';
            return '';
        }
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if ($method !== 'GET') {
            $token = $_POST['csrf_token'] ?? ($_GET['csrf_token'] ?? '');
            if (!$this->validateCsrf($token)) {
                http_response_code(400);
                echo 'CSRF inválido';
                return '';
            }
        }

        $filters = [
            'ciclo' => $_REQUEST['ciclo'] ?? null,
            'grupo_id' => isset($_REQUEST['grupo_id']) ? (int)$_REQUEST['grupo_id'] : null,
            'profesor_id' => isset($_REQUEST['profesor_id']) ? (int)$_REQUEST['profesor_id'] : null,
        ];
        [$sqlWhere, $params] = $this->buildWhere($filters, $role === 'profesor' ? (int)($_SESSION['user_id'] ?? 0) : null);

        Logger::info('report_export_csv', ['filters' => $filters]);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=calificaciones.csv');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Alumno', 'Grupo', 'Materia', 'Ciclo', 'Parcial1', 'Parcial2', 'Final', 'Promedio']);

        $sql = "SELECT CONCAT(a.nombre,' ',a.apellido) AS alumno, g.nombre AS grupo, m.nombre AS materia, g.ciclo, c.parcial1, c.parcial2, c.final, c.promedio
                FROM calificaciones c
                JOIN alumnos a ON c.alumno_id = a.id
                JOIN grupos g ON c.grupo_id = g.id
                JOIN materias m ON g.materia_id = m.id
                $sqlWhere
                ORDER BY g.ciclo DESC, m.nombre, g.nombre, a.apellido";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($out, [
                $this->ascii($row['alumno'] ?? ''),
                $this->ascii($row['grupo'] ?? ''),
                $this->ascii($row['materia'] ?? ''),
                $this->ascii($row['ciclo'] ?? ''),
                $row['parcial1'], $row['parcial2'], $row['final'], $row['promedio']
            ]);
        }
        fclose($out);
        return '';
    }

    public function exportPdf(): void
    {
        $role = $_SESSION['role'] ?? '';
        if ($role !== 'admin' && $role !== 'profesor') {
            http_response_code(403);
            exit('No autorizado');
        }
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if ($method !== 'GET') {
            $token = $_POST['csrf_token'] ?? ($_GET['csrf_token'] ?? '');
            if (!$this->validateCsrf($token)) {
                http_response_code(400);
                exit('CSRF inválido');
            }
        }

        $filters = [
            'ciclo' => $_REQUEST['ciclo'] ?? null,
            'grupo_id' => isset($_REQUEST['grupo_id']) ? (int)$_REQUEST['grupo_id'] : null,
            'profesor_id' => isset($_REQUEST['profesor_id']) ? (int)$_REQUEST['profesor_id'] : null,
        ];
        [$sqlWhere, $params] = $this->buildWhere($filters, $role === 'profesor' ? (int)($_SESSION['user_id'] ?? 0) : null);

        Logger::info('report_export_pdf', ['filters' => $filters]);

        $sql = "SELECT a.matricula, CONCAT(a.nombre,' ',a.apellido) AS alumno,
                       m.nombre AS materia, g.nombre AS grupo, g.ciclo,
                       c.parcial1, c.parcial2, c.final
                FROM calificaciones c
                JOIN alumnos a ON a.id = c.alumno_id
                JOIN grupos g ON g.id = c.grupo_id
                JOIN materias m ON m.id = g.materia_id
                $sqlWhere
                ORDER BY g.ciclo DESC, m.nombre, g.nombre, a.apellido";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $html = '<h2>Reporte de Calificaciones</h2>';
        $html .= '<table width="100%" border="1" cellspacing="0" cellpadding="6">';
        $html .= '<thead><tr><th>Matrícula</th><th>Alumno</th><th>Materia</th><th>Grupo</th><th>Ciclo</th><th>Parcial 1</th><th>Parcial 2</th><th>Final</th></tr></thead><tbody>';
        foreach ($rows as $r) {
            $html .= '<tr>'
                .'<td>'.htmlspecialchars($r['matricula']).'</td>'
                .'<td>'.htmlspecialchars($r['alumno']).'</td>'
                .'<td>'.htmlspecialchars($r['materia']).'</td>'
                .'<td>'.htmlspecialchars($r['grupo']).'</td>'
                .'<td>'.htmlspecialchars($r['ciclo']).'</td>'
                .'<td>'.htmlspecialchars((string)($r['parcial1'] ?? '')).'</td>'
                .'<td>'.htmlspecialchars((string)($r['parcial2'] ?? '')).'</td>'
                .'<td>'.htmlspecialchars((string)($r['final'] ?? '')).'</td>'
                .'</tr>';
        }
        $html .= '</tbody></table>';

        if (!class_exists('Dompdf\\Dompdf')) {
            $autoload = __DIR__ . '/../../vendor/autoload.php';
            if (file_exists($autoload)) {
                require_once $autoload;
            }
        }
        if (!class_exists('Dompdf\\Dompdf')) {
            http_response_code(500);
            exit('Dompdf no disponible. Instala con composer require dompdf/dompdf');
        }

        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $dompdf->stream('reporte_calificaciones.pdf', ['Attachment' => false]);
    }

    public function summary(): string
    {
        $role = $_SESSION['role'] ?? '';
        if ($role !== 'admin' && $role !== 'profesor') {
            header('Content-Type: application/json');
            http_response_code(403);
            return json_encode(['ok' => false, 'message' => 'No autorizado']);
        }

        $filters = [
            'ciclo' => $_GET['ciclo'] ?? null,
            'profesor_id' => isset($_GET['profesor_id']) ? (int)$_GET['profesor_id'] : null,
        ];
        [$sqlWhere, $params] = $this->buildWhere($filters, $role === 'profesor' ? (int)($_SESSION['user_id'] ?? 0) : null);

        $avgSql = "SELECT ROUND(AVG(c.final),2) AS promedio FROM calificaciones c JOIN grupos g ON g.id = c.grupo_id $sqlWhere";
        $stmt = $this->pdo->prepare($avgSql);
        $stmt->execute($params);
        $promedio = (float)($stmt->fetchColumn() ?: 0);

        $repSql = "SELECT COUNT(*) FROM calificaciones c JOIN grupos g ON g.id = c.grupo_id $sqlWhere AND c.final IS NOT NULL AND c.final < 70";
        $stmt = $this->pdo->prepare($repSql);
        $stmt->execute($params);
        $reprobados = (int)($stmt->fetchColumn() ?: 0);

        $totSql = "SELECT COUNT(*) FROM calificaciones c JOIN grupos g ON g.id = c.grupo_id $sqlWhere AND c.final IS NOT NULL";
        $stmt = $this->pdo->prepare($totSql);
        $stmt->execute($params);
        $totalConFinal = (int)($stmt->fetchColumn() ?: 0);
        $porcReprobados = $totalConFinal > 0 ? round(($reprobados / $totalConFinal) * 100, 2) : 0.0;

        Logger::info('report_summary', ['filters' => $filters, 'promedio' => $promedio, 'reprobados' => $reprobados]);

        header('Content-Type: application/json');
        return json_encode(['ok' => true, 'data' => [
            'promedio' => $promedio,
            'reprobados' => $reprobados,
            'porcentaje_reprobados' => $porcReprobados,
        ]]);
    }
}
