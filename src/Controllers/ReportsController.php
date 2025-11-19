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
            if (!preg_match('/^\d{4}-?(1|2|A|B)$/i', $ciclo)) {
                throw new \InvalidArgumentException('Ciclo inválido');
            }
            $where[] = 'g.ciclo = :ciclo';
            $params[':ciclo'] = strtoupper($ciclo);
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
        $cicloVal = (string)($filters['ciclo'] ?? '');
        $profId = $role === 'profesor' ? (int)($_SESSION['user_id'] ?? 0) : ((int)($filters['profesor_id'] ?? 0));
        $profName = '';
        if ($profId > 0) {
            $ps = $this->pdo->prepare("SELECT nombre FROM usuarios WHERE id = :id AND rol = 'profesor' LIMIT 1");
            $ps->execute([':id' => $profId]);
            $profName = (string)($ps->fetchColumn() ?: '');
        }
        if ($cicloVal !== '' || $profName !== '') {
            if ($cicloVal !== '') { fputcsv($out, ['Ciclo', $this->ascii($cicloVal)]); }
            if ($profName !== '') { fputcsv($out, ['Profesor', $this->ascii($profName)]); }
            fputcsv($out, []);
        }
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
        $cicloVal = (string)($filters['ciclo'] ?? '');
        $profId = $role === 'profesor' ? (int)($_SESSION['user_id'] ?? 0) : ((int)($filters['profesor_id'] ?? 0));
        $profName = '';
        if ($profId > 0) {
            $ps = $this->pdo->prepare("SELECT nombre FROM usuarios WHERE id = :id AND rol = 'profesor' LIMIT 1");
            $ps->execute([':id' => $profId]);
            $profName = (string)($ps->fetchColumn() ?: '');
        }

        $html = '<h2>Reporte de Calificaciones</h2>';
        if ($cicloVal !== '' || $profName !== '') {
            $html .= '<div style="margin-bottom:10px">';
            if ($cicloVal !== '') { $html .= '<div><strong>Ciclo:</strong> ' . htmlspecialchars($cicloVal) . '</div>'; }
            if ($profName !== '') { $html .= '<div><strong>Profesor:</strong> ' . htmlspecialchars($profName) . '</div>'; }
            $html .= '</div>';
        }
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

        $topsFilters = [
            'ciclo' => $filters['ciclo'] ?? null,
            'profesor_id' => $filters['profesor_id'] ?? null,
        ];
        [$wTop, $pTop] = $this->buildWhere($topsFilters, $role === 'profesor' ? (int)($_SESSION['user_id'] ?? 0) : null);
        $qTopP = "SELECT g.nombre AS grupo, m.nombre AS materia, g.ciclo, ROUND(AVG(c.final),2) AS promedio
                  FROM calificaciones c JOIN grupos g ON g.id = c.grupo_id JOIN materias m ON m.id = g.materia_id
                  $wTop AND c.final IS NOT NULL GROUP BY g.id, g.nombre, m.nombre, g.ciclo ORDER BY promedio DESC LIMIT 5";
        $stP = $this->pdo->prepare($qTopP); $stP->execute($pTop); $rowsP = $stP->fetchAll(PDO::FETCH_ASSOC);
        $qTopF = "SELECT g.nombre AS grupo, m.nombre AS materia, g.ciclo,
                         ROUND(SUM(CASE WHEN c.final IS NOT NULL AND c.final < 70 THEN 1 ELSE 0 END) / NULLIF(COUNT(CASE WHEN c.final IS NOT NULL THEN 1 END),0) * 100, 2) AS porcentaje
                  FROM calificaciones c JOIN grupos g ON g.id = c.grupo_id JOIN materias m ON m.id = g.materia_id
                  $wTop GROUP BY g.id, g.nombre, m.nombre, g.ciclo ORDER BY porcentaje DESC LIMIT 5";
        $stF = $this->pdo->prepare($qTopF); $stF->execute($pTop); $rowsF = $stF->fetchAll(PDO::FETCH_ASSOC);

        $html .= '<h3 style="margin-top:15px">Top 5 grupos por promedio</h3>';
        $html .= '<table width="100%" border="1" cellspacing="0" cellpadding="6"><thead><tr><th>Ciclo</th><th>Materia</th><th>Grupo</th><th>Promedio</th></tr></thead><tbody>';
        if ($rowsP) { foreach ($rowsP as $r) {
            $html .= '<tr><td>'.htmlspecialchars($r['ciclo']).'</td><td>'.htmlspecialchars($r['materia']).'</td><td>'.htmlspecialchars($r['grupo']).'</td><td>'.htmlspecialchars(number_format((float)$r['promedio'],2)).'</td></tr>';
        } } else { $html .= '<tr><td colspan="4">Sin datos</td></tr>'; }
        $html .= '</tbody></table>';

        $qTopAl = "SELECT a.matricula, CONCAT(a.nombre,' ',a.apellido) AS alumno,
                           ROUND(AVG(c.final),2) AS promedio
                    FROM calificaciones c JOIN alumnos a ON a.id = c.alumno_id JOIN grupos g ON g.id = c.grupo_id
                    $wTop AND c.final IS NOT NULL
                    GROUP BY a.id, a.matricula, a.nombre, a.apellido
                    ORDER BY promedio DESC LIMIT 5";
        $stA = $this->pdo->prepare($qTopAl); $stA->execute($pTop); $rowsA = $stA->fetchAll(PDO::FETCH_ASSOC);
        $qRisk = "SELECT a.matricula, CONCAT(a.nombre,' ',a.apellido) AS alumno, m.nombre AS materia, g.nombre AS grupo, g.ciclo, c.final
                  FROM calificaciones c JOIN alumnos a ON a.id = c.alumno_id JOIN grupos g ON g.id = c.grupo_id JOIN materias m ON m.id = g.materia_id
                  $wTop AND c.final IS NOT NULL AND c.final < 60
                  ORDER BY c.final ASC, a.apellido LIMIT 5";
        $stR = $this->pdo->prepare($qRisk); $stR->execute($pTop); $rowsR = $stR->fetchAll(PDO::FETCH_ASSOC);

        $html .= '<h3 style="margin-top:15px">Top 5 alumnos por promedio</h3>';
        $html .= '<table width="100%" border="1" cellspacing="0" cellpadding="6"><thead><tr><th>Matrícula</th><th>Alumno</th><th>Promedio</th></tr></thead><tbody>';
        if ($rowsA) { foreach ($rowsA as $r) {
            $html .= '<tr><td>'.htmlspecialchars($r['matricula']).'</td><td>'.htmlspecialchars($r['alumno']).'</td><td>'.htmlspecialchars(number_format((float)$r['promedio'],2)).'</td></tr>';
        } } else { $html .= '<tr><td colspan="3">Sin datos</td></tr>'; }
        $html .= '</tbody></table>';

        $html .= '<h3 style="margin-top:15px">Alumnos con riesgo (final < 60)</h3>';
        $html .= '<table width="100%" border="1" cellspacing="0" cellpadding="6"><thead><tr><th>Ciclo</th><th>Materia</th><th>Grupo</th><th>Alumno</th><th>Final</th></tr></thead><tbody>';
        if ($rowsR) { foreach ($rowsR as $r) {
            $html .= '<tr><td>'.htmlspecialchars($r['ciclo']).'</td><td>'.htmlspecialchars($r['materia']).'</td><td>'.htmlspecialchars($r['grupo']).'</td><td>'.htmlspecialchars($r['alumno']).'</td><td>'.htmlspecialchars(number_format((float)$r['final'],2)).'</td></tr>';
        } } else { $html .= '<tr><td colspan="5">Sin datos</td></tr>'; }
        $html .= '</tbody></table>';

        $html .= '<h3 style="margin-top:15px">Top 5 grupos por % reprobados</h3>';
        $html .= '<table width="100%" border="1" cellspacing="0" cellpadding="6"><thead><tr><th>Ciclo</th><th>Materia</th><th>Grupo</th><th>% Reprobados</th></tr></thead><tbody>';
        if ($rowsF) { foreach ($rowsF as $r) {
            $html .= '<tr><td>'.htmlspecialchars($r['ciclo']).'</td><td>'.htmlspecialchars($r['materia']).'</td><td>'.htmlspecialchars($r['grupo']).'</td><td>'.htmlspecialchars(number_format((float)$r['porcentaje'],2)).'%</td></tr>';
        } } else { $html .= '<tr><td colspan="4">Sin datos</td></tr>'; }
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

    public function exportZip(): string
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

        if (!class_exists('ZipArchive')) {
            http_response_code(500);
            echo 'ZipArchive no disponible. Habilita la extensión zip de PHP';
            return '';
        }

        $filters = [
            'ciclo' => $_REQUEST['ciclo'] ?? null,
            'grupo_id' => isset($_REQUEST['grupo_id']) ? (int)$_REQUEST['grupo_id'] : null,
            'profesor_id' => isset($_REQUEST['profesor_id']) ? (int)$_REQUEST['profesor_id'] : null,
        ];
        [$sqlWhere, $params] = $this->buildWhere($filters, $role === 'profesor' ? (int)($_SESSION['user_id'] ?? 0) : null);

        $makeCsv = function(array $header, callable $rowBuilder): string {
            $fp = fopen('php://temp', 'r+');
            fputcsv($fp, $header);
            foreach ($rowBuilder() as $row) { fputcsv($fp, $row); }
            rewind($fp);
            $csv = stream_get_contents($fp);
            fclose($fp);
            return (string)$csv;
        };

        $sqlMain = "SELECT CONCAT(a.nombre,' ',a.apellido) AS alumno, g.nombre AS grupo, m.nombre AS materia, g.ciclo, c.parcial1, c.parcial2, c.final, c.promedio
                    FROM calificaciones c
                    JOIN alumnos a ON c.alumno_id = a.id
                    JOIN grupos g ON c.grupo_id = g.id
                    JOIN materias m ON g.materia_id = m.id
                    $sqlWhere
                    ORDER BY g.ciclo DESC, m.nombre, g.nombre, a.apellido";
        $stmt = $this->pdo->prepare($sqlMain);
        $stmt->execute($params);
        $rowsMain = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $csvMain = $makeCsv(['Alumno', 'Grupo', 'Materia', 'Ciclo', 'Parcial1', 'Parcial2', 'Final', 'Promedio'], function() use ($rowsMain) {
            $out = [];
            foreach ($rowsMain as $r) {
                $out[] = [
                    $this->ascii($r['alumno'] ?? ''),
                    $this->ascii($r['grupo'] ?? ''),
                    $this->ascii($r['materia'] ?? ''),
                    $this->ascii($r['ciclo'] ?? ''),
                    $r['parcial1'], $r['parcial2'], $r['final'], $r['promedio']
                ];
            }
            return $out;
        });

        $sqlAvg = "SELECT g.nombre AS grupo, m.nombre AS materia, g.ciclo, ROUND(AVG(c.final),2) AS promedio
                   FROM calificaciones c JOIN grupos g ON g.id = c.grupo_id JOIN materias m ON m.id = g.materia_id
                   $sqlWhere AND c.final IS NOT NULL
                   GROUP BY g.id, g.nombre, m.nombre, g.ciclo
                   ORDER BY promedio DESC LIMIT 5";
        $stAvg = $this->pdo->prepare($sqlAvg); $stAvg->execute($params); $rowsAvg = $stAvg->fetchAll(PDO::FETCH_ASSOC);
        $csvAvg = $makeCsv(['Ciclo', 'Materia', 'Grupo', 'Promedio'], function() use ($rowsAvg) {
            $out = [];
            foreach ($rowsAvg as $r) { $out[] = [$r['ciclo'], $this->ascii($r['materia']), $this->ascii($r['grupo']), number_format((float)$r['promedio'],2)]; }
            return $out;
        });

        $sqlFail = "SELECT g.nombre AS grupo, m.nombre AS materia, g.ciclo,
                           ROUND(SUM(CASE WHEN c.final IS NOT NULL AND c.final < 70 THEN 1 ELSE 0 END) / NULLIF(COUNT(CASE WHEN c.final IS NOT NULL THEN 1 END),0) * 100, 2) AS porcentaje
                    FROM calificaciones c JOIN grupos g ON g.id = c.grupo_id JOIN materias m ON m.id = g.materia_id
                    $sqlWhere
                    GROUP BY g.id, g.nombre, m.nombre, g.ciclo
                    ORDER BY porcentaje DESC LIMIT 5";
        $stFail = $this->pdo->prepare($sqlFail); $stFail->execute($params); $rowsFail = $stFail->fetchAll(PDO::FETCH_ASSOC);
        $csvFail = $makeCsv(['Ciclo', 'Materia', 'Grupo', '% Reprobados'], function() use ($rowsFail) {
            $out = [];
            foreach ($rowsFail as $r) { $out[] = [$r['ciclo'], $this->ascii($r['materia']), $this->ascii($r['grupo']), number_format((float)$r['porcentaje'],2)]; }
            return $out;
        });

        $sqlTopAlum = "SELECT a.matricula, CONCAT(a.nombre,' ',a.apellido) AS alumno, ROUND(AVG(c.final),2) AS promedio
                        FROM calificaciones c JOIN alumnos a ON a.id = c.alumno_id JOIN grupos g ON g.id = c.grupo_id
                        $sqlWhere AND c.final IS NOT NULL
                        GROUP BY a.id, a.matricula, a.nombre, a.apellido
                        ORDER BY promedio DESC LIMIT 5";
        $stAlum = $this->pdo->prepare($sqlTopAlum); $stAlum->execute($params); $rowsAlum = $stAlum->fetchAll(PDO::FETCH_ASSOC);
        $csvAlum = $makeCsv(['Matrícula', 'Alumno', 'Promedio'], function() use ($rowsAlum) {
            $out = [];
            foreach ($rowsAlum as $r) { $out[] = [$this->ascii($r['matricula']), $this->ascii($r['alumno']), number_format((float)$r['promedio'],2)]; }
            return $out;
        });

        $sqlRisk = "SELECT a.matricula, CONCAT(a.nombre,' ',a.apellido) AS alumno, m.nombre AS materia, g.nombre AS grupo, g.ciclo, c.final
                    FROM calificaciones c JOIN alumnos a ON a.id = c.alumno_id JOIN grupos g ON g.id = c.grupo_id JOIN materias m ON m.id = g.materia_id
                    $sqlWhere AND c.final IS NOT NULL AND c.final < 60
                    ORDER BY c.final ASC, a.apellido LIMIT 5";
        $stRisk = $this->pdo->prepare($sqlRisk); $stRisk->execute($params); $rowsRisk = $stRisk->fetchAll(PDO::FETCH_ASSOC);
        $csvRisk = $makeCsv(['Ciclo', 'Materia', 'Grupo', 'Alumno', 'Final'], function() use ($rowsRisk) {
            $out = [];
            foreach ($rowsRisk as $r) { $out[] = [$r['ciclo'], $this->ascii($r['materia']), $this->ascii($r['grupo']), $this->ascii($r['alumno']), number_format((float)$r['final'],2)]; }
            return $out;
        });

        $tmp = tempnam(sys_get_temp_dir(), 'repzip');
        $zip = new \ZipArchive();
        $zip->open($tmp, \ZipArchive::OVERWRITE);
        $zip->addFromString('calificaciones.csv', $csvMain);
        $zip->addFromString('top_promedios_grupos.csv', $csvAvg);
        $zip->addFromString('top_reprobados_grupos.csv', $csvFail);
        $zip->addFromString('top_alumnos.csv', $csvAlum);
        $zip->addFromString('alumnos_riesgo.csv', $csvRisk);
        $zip->close();

        $cicloVal = (string)($filters['ciclo'] ?? '');
        $profId = $role === 'profesor' ? (int)($_SESSION['user_id'] ?? 0) : ((int)($filters['profesor_id'] ?? 0));
        $profName = '';
        if ($profId > 0) { $ps = $this->pdo->prepare("SELECT nombre FROM usuarios WHERE id = :id AND rol = 'profesor' LIMIT 1"); $ps->execute([':id'=>$profId]); $profName = (string)($ps->fetchColumn() ?: ''); }
        $slug = fn(string $s) => strtolower(preg_replace('/[^a-z0-9_\-]/', '', str_replace(' ', '_', $this->ascii($s))));
        $fnameParts = ['reportes'];
        if ($cicloVal !== '') { $fnameParts[] = $slug($cicloVal); }
        if ($profName !== '') { $fnameParts[] = $slug($profName); }
        $zipName = implode('_', $fnameParts) . '.zip';
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="'.$zipName.'"');
        header('Content-Length: ' . filesize($tmp));
        readfile($tmp);
        @unlink($tmp);
        return '';
    }

    public function exportXlsx(): string
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

        if (!class_exists('ZipArchive')) {
            http_response_code(500);
            echo 'ZipArchive no disponible. Habilita la extensión zip de PHP';
            return '';
        }

        $filters = [
            'ciclo' => $_REQUEST['ciclo'] ?? null,
            'grupo_id' => isset($_REQUEST['grupo_id']) ? (int)$_REQUEST['grupo_id'] : null,
            'profesor_id' => isset($_REQUEST['profesor_id']) ? (int)$_REQUEST['profesor_id'] : null,
        ];
        [$sqlWhere, $params] = $this->buildWhere($filters, $role === 'profesor' ? (int)($_SESSION['user_id'] ?? 0) : null);

        $qMain = "SELECT CONCAT(a.nombre,' ',a.apellido) AS alumno, g.nombre AS grupo, m.nombre AS materia, g.ciclo, c.parcial1, c.parcial2, c.final, c.promedio
                  FROM calificaciones c
                  JOIN alumnos a ON c.alumno_id = a.id
                  JOIN grupos g ON c.grupo_id = g.id
                  JOIN materias m ON g.materia_id = m.id
                  $sqlWhere
                  ORDER BY g.ciclo DESC, m.nombre, g.nombre, a.apellido";
        $stMain = $this->pdo->prepare($qMain); $stMain->execute($params); $rowsMain = $stMain->fetchAll(PDO::FETCH_ASSOC);

        $cicloVal = (string)($filters['ciclo'] ?? '');
        $profId = $role === 'profesor' ? (int)($_SESSION['user_id'] ?? 0) : ((int)($filters['profesor_id'] ?? 0));
        $profName = '';
        if ($profId > 0) { $ps = $this->pdo->prepare("SELECT nombre FROM usuarios WHERE id = :id AND rol = 'profesor' LIMIT 1"); $ps->execute([':id'=>$profId]); $profName = (string)($ps->fetchColumn() ?: ''); }

        $qAvg = "SELECT g.nombre AS grupo, m.nombre AS materia, g.ciclo, ROUND(AVG(c.final),2) AS promedio
                 FROM calificaciones c JOIN grupos g ON g.id = c.grupo_id JOIN materias m ON m.id = g.materia_id
                 $sqlWhere AND c.final IS NOT NULL
                 GROUP BY g.id, g.nombre, m.nombre, g.ciclo
                 ORDER BY promedio DESC LIMIT 5";
        $stAvg = $this->pdo->prepare($qAvg); $stAvg->execute($params); $rowsAvg = $stAvg->fetchAll(PDO::FETCH_ASSOC);

        $qFail = "SELECT g.nombre AS grupo, m.nombre AS materia, g.ciclo,
                         ROUND(SUM(CASE WHEN c.final IS NOT NULL AND c.final < 70 THEN 1 ELSE 0 END) / NULLIF(COUNT(CASE WHEN c.final IS NOT NULL THEN 1 END),0) * 100, 2) AS porcentaje
                  FROM calificaciones c JOIN grupos g ON g.id = c.grupo_id JOIN materias m ON m.id = g.materia_id
                  $sqlWhere
                  GROUP BY g.id, g.nombre, m.nombre, g.ciclo
                  ORDER BY porcentaje DESC LIMIT 5";
        $stFail = $this->pdo->prepare($qFail); $stFail->execute($params); $rowsFail = $stFail->fetchAll(PDO::FETCH_ASSOC);

        $qAlum = "SELECT a.matricula, CONCAT(a.nombre,' ',a.apellido) AS alumno, ROUND(AVG(c.final),2) AS promedio
                  FROM calificaciones c JOIN alumnos a ON a.id = c.alumno_id JOIN grupos g ON g.id = c.grupo_id
                  $sqlWhere AND c.final IS NOT NULL
                  GROUP BY a.id, a.matricula, a.nombre, a.apellido
                  ORDER BY promedio DESC LIMIT 5";
        $stAlum = $this->pdo->prepare($qAlum); $stAlum->execute($params); $rowsAlum = $stAlum->fetchAll(PDO::FETCH_ASSOC);

        $qRisk = "SELECT a.matricula, CONCAT(a.nombre,' ',a.apellido) AS alumno, m.nombre AS materia, g.nombre AS grupo, g.ciclo, c.final
                  FROM calificaciones c JOIN alumnos a ON a.id = c.alumno_id JOIN grupos g ON g.id = c.grupo_id JOIN materias m ON m.id = g.materia_id
                  $sqlWhere AND c.final IS NOT NULL AND c.final < 60
                  ORDER BY c.final ASC, a.apellido LIMIT 5";
        $stRisk = $this->pdo->prepare($qRisk); $stRisk->execute($params); $rowsRisk = $stRisk->fetchAll(PDO::FETCH_ASSOC);

        // Resumen para hoja Calificaciones (promedio general, aprobadas, reprobadas, pendientes)
        $totalConFinal = 0; $sumFinal = 0.0; $aprobadas = 0; $reprobadas = 0; $pendientes = 0;
        foreach ($rowsMain as $r) {
            $f = $r['final'];
            if ($f === null || $f === '' ) { $pendientes++; continue; }
            $val = (float)$f; $totalConFinal++; $sumFinal += $val; if ($val >= 70) { $aprobadas++; } else { $reprobadas++; }
        }
        $promedioGeneral = $totalConFinal > 0 ? round($sumFinal / $totalConFinal, 2) : 0.0;
        $calHeaders = ['Alumno','Grupo','Materia','Ciclo','Parcial1','Parcial2','Final','Promedio'];
        $calRows = [];
        $calRows[] = ['Promedio general', $promedioGeneral];
        $calRows[] = ['Aprobadas', $aprobadas];
        $calRows[] = ['Reprobadas', $reprobadas];
        $calRows[] = ['Pendientes', $pendientes];
        $calRows[] = [''];
        $calRows[] = $calHeaders;
        foreach ($rowsMain as $r) {
            $calRows[] = [
                (string)$r['alumno'], (string)$r['grupo'], (string)$r['materia'], (string)$r['ciclo'],
                ($r['parcial1'] !== null && $r['parcial1'] !== '' ? (float)$r['parcial1'] : ''),
                ($r['parcial2'] !== null && $r['parcial2'] !== '' ? (float)$r['parcial2'] : ''),
                ($r['final'] !== null && $r['final'] !== '' ? (float)$r['final'] : ''),
                ($r['promedio'] !== null && $r['promedio'] !== '' ? (float)$r['promedio'] : ''),
            ];
        }

        $bannerRows = [];
        if ($cicloVal !== '') { $bannerRows[] = ['Ciclo', $cicloVal]; }
        if ($profName !== '') { $bannerRows[] = ['Profesor', $profName]; }

        $sheets = [
            ['name' => 'Calificaciones', 'headers' => [], 'rows' => $calRows, 'preRows' => $bannerRows, 'headerRowIndex' => (count($bannerRows) + 6)],
            ['name' => 'Top Grupos', 'headers' => ['Ciclo','Materia','Grupo','Promedio'], 'rows' => array_map(function($r){ return [
                (string)$r['ciclo'], (string)$r['materia'], (string)$r['grupo'], number_format((float)$r['promedio'],2)
            ]; }, $rowsAvg)],
            ['name' => 'Reprobados', 'headers' => ['Ciclo','Materia','Grupo','% Reprobados'], 'rows' => array_map(function($r){ return [
                (string)$r['ciclo'], (string)$r['materia'], (string)$r['grupo'], number_format((float)$r['porcentaje'],2)
            ]; }, $rowsFail)],
            ['name' => 'Top Alumnos', 'headers' => ['Matrícula','Alumno','Promedio'], 'rows' => array_map(function($r){ return [
                (string)$r['matricula'], (string)$r['alumno'], number_format((float)$r['promedio'],2)
            ]; }, $rowsAlum)],
            ['name' => 'Riesgo', 'headers' => ['Ciclo','Materia','Grupo','Alumno','Final'], 'rows' => array_map(function($r){ return [
                (string)$r['ciclo'], (string)$r['materia'], (string)$r['grupo'], (string)$r['alumno'], number_format((float)$r['final'],2)
            ]; }, $rowsRisk)],
        ];

        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new \ZipArchive();
        $zip->open($tmp, \ZipArchive::OVERWRITE);

        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>';
        $contentTypes .= '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
        $relsWorkbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
        $workbookSheets = '';
        $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets>'; 

        $sheetIndex = 1;
        foreach ($sheets as $s) {
            $sheetPath = '/xl/worksheets/sheet'.$sheetIndex.'.xml';
            $contentTypes .= '<Override PartName="'.$sheetPath.'" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
            $relsWorkbook .= '<Relationship Id="rId'.$sheetIndex.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet'.$sheetIndex.'.xml"/>';
            $workbookSheets .= '<sheet name="'.htmlspecialchars($s['name'], ENT_QUOTES).'" sheetId="'.$sheetIndex.'" r:id="rId'.$sheetIndex.'"/>';
            $options = [];
            if ($s['name'] === 'Calificaciones') { $options = ['freezeRows' => (int)$s['headerRowIndex'], 'numericCols' => [5,6,7,8], 'preRows' => ($s['preRows'] ?? []), 'headerRowIndex' => (int)$s['headerRowIndex']]; }
            elseif ($s['name'] === 'Top Grupos') { $options = ['freezeRows' => (count($bannerRows) + 1), 'numericCols' => [4], 'preRows' => $bannerRows, 'headerRowIndex' => (count($bannerRows) + 1)]; }
            elseif ($s['name'] === 'Reprobados') { $options = ['freezeRows' => (count($bannerRows) + 1), 'numericCols' => [4], 'preRows' => $bannerRows, 'headerRowIndex' => (count($bannerRows) + 1)]; }
            elseif ($s['name'] === 'Top Alumnos') { $options = ['freezeRows' => (count($bannerRows) + 1), 'numericCols' => [3], 'preRows' => $bannerRows, 'headerRowIndex' => (count($bannerRows) + 1)]; }
            elseif ($s['name'] === 'Riesgo') { $options = ['freezeRows' => (count($bannerRows) + 1), 'numericCols' => [5], 'preRows' => $bannerRows, 'headerRowIndex' => (count($bannerRows) + 1)]; }
            $sheetXml = $this->buildSheetXml($s['headers'], $s['rows'], $options);
            $zip->addFromString('xl/worksheets/sheet'.$sheetIndex.'.xml', $sheetXml);
            $sheetIndex++;
        }
        $contentTypes .= '</Types>';
        $relsWorkbook .= '<Relationship Id="rIdStyles" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';
        $relsWorkbook .= '</Relationships>';
        $workbook .= $workbookSheets . '</sheets></workbook>';

        $relsRoot = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';

        $zip->addFromString('[Content_Types].xml', $contentTypes);
        $zip->addFromString('_rels/.rels', $relsRoot);
        $zip->addFromString('xl/_rels/workbook.xml.rels', $relsWorkbook);
        $stylesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<numFmts count="0"/>'
            .'<fonts count="2">'
              .'<font><sz val="11"/><color theme="1"/><name val="Calibri"/><family val="2"/></font>'
              .'<font><b/><sz val="11"/><color theme="1"/><name val="Calibri"/><family val="2"/></font>'
            .'</fonts>'
            .'<fills count="2">'
              .'<fill><patternFill patternType="none"/></fill>'
              .'<fill><patternFill patternType="solid"><fgColor rgb="FFEFEFEF"/><bgColor indexed="64"/></patternFill></fill>'
            .'</fills>'
            .'<borders count="1"><border/></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="3">'
              .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
              .'<xf numFmtId="4" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
              .'<xf numFmtId="0" fontId="1" fillId="1" borderId="0" xfId="0" applyFont="1" applyFill="1"/>'
            .'</cellXfs>'
            .'</styleSheet>';
        $zip->addFromString('xl/styles.xml', $stylesXml);
        $zip->addFromString('xl/workbook.xml', $workbook);
        $zip->close();

        $slug = function(string $s): string { $s = $this->ascii($s); $s = strtolower(str_replace(' ', '_', $s)); $s = preg_replace('/[^a-z0-9_\-]/', '', $s); return $s ?: 'todos'; };
        $fnameParts = ['reportes'];
        if ($cicloVal !== '') { $fnameParts[] = $slug($cicloVal); }
        if ($profName !== '') { $fnameParts[] = $slug($profName); }
        $fname = implode('_', $fnameParts) . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="'.$fname.'"');
        header('Content-Length: ' . filesize($tmp));
        readfile($tmp);
        @unlink($tmp);
        return '';
    }

    private function buildSheetXml(array $headers, array $rows, array $options = []): string
    {
        $freezeRows = (int)($options['freezeRows'] ?? 0);
        $numericCols = (array)($options['numericCols'] ?? []); // 1-based indices
        $preRows = (array)($options['preRows'] ?? []);
        $headerRowIndex = (int)($options['headerRowIndex'] ?? 0);
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
        if ($freezeRows > 0) {
            $xml .= '<sheetViews><sheetView workbookViewId="0"><pane ySplit="'.$freezeRows.'" topLeftCell="A'.($freezeRows+1).'" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>';
        }
        $xml .= '<sheetData>';
        $rowNum = 1;
        $makeRow = function(array $cells, ?int $styleOverride = null) use (&$rowNum, $numericCols) {
            $cols = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $r = '<row r="'.$rowNum.'">';
            for ($i=0; $i<count($cells); $i++) {
                $col = $cols[$i];
                $val = (string)$cells[$i];
                $isNum = is_numeric(str_replace([','], '', $val));
                $applyNumFmt = in_array($i+1, $numericCols, true);
                if ($isNum) {
                    $styleAttr = '';
                    if ($styleOverride !== null) { $styleAttr = ' s="'.$styleOverride.'"'; }
                    elseif ($applyNumFmt) { $styleAttr = ' s="1"'; }
                    $r .= '<c r="'.$col.$rowNum.'" t="n"'.$styleAttr.'><v>'.htmlspecialchars((string)$val, ENT_QUOTES).'</v></c>';
                } else {
                    $styleAttr = ($styleOverride !== null) ? (' s="'.$styleOverride.'"') : '';
                    $r .= '<c r="'.$col.$rowNum.'" t="inlineStr"'.$styleAttr+'><is><t>'.htmlspecialchars($val, ENT_QUOTES).'</t></is></c>';
                }
            }
            $r .= '</row>';
            $rowNum++;
            return $r;
        };
        foreach ($preRows as $pr) { $xml .= $makeRow($pr); }
        if (!empty($headers)) {
            $xml .= $makeRow($headers, 2);
        }
        foreach ($rows as $row) { $xml .= $makeRow($row); }
        $xml .= '</sheetData></worksheet>';
        return $xml;
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

        $aprSql = "SELECT COUNT(*) FROM calificaciones c JOIN grupos g ON g.id = c.grupo_id $sqlWhere AND c.final IS NOT NULL AND c.final >= 70";
        $stmt = $this->pdo->prepare($aprSql);
        $stmt->execute($params);
        $aprobadas = (int)($stmt->fetchColumn() ?: 0);

        $penSql = "SELECT COUNT(*) FROM calificaciones c JOIN grupos g ON g.id = c.grupo_id $sqlWhere AND c.final IS NULL";
        $stmt = $this->pdo->prepare($penSql);
        $stmt->execute($params);
        $pendientes = (int)($stmt->fetchColumn() ?: 0);

        Logger::info('report_summary', ['filters' => $filters, 'promedio' => $promedio, 'reprobados' => $reprobados]);

        header('Content-Type: application/json');
        return json_encode(['ok' => true, 'data' => [
            'promedio' => $promedio,
            'reprobados' => $reprobados,
            'porcentaje_reprobados' => $porcReprobados,
            'aprobadas' => $aprobadas,
            'pendientes' => $pendientes,
            'total_con_final' => $totalConFinal,
        ]]);
    }

    public function tops(): string
    {
        $role = $_SESSION['role'] ?? '';
        if ($role !== 'admin' && $role !== 'profesor') {
            header('Content-Type: application/json');
            http_response_code(403);
            return json_encode(['ok' => false, 'message' => 'No autorizado']);
        }
        $filters = [
            'ciclo' => $_GET['ciclo'] ?? null,
            'grupo_id' => isset($_GET['grupo_id']) ? (int)$_GET['grupo_id'] : null,
            'profesor_id' => isset($_GET['profesor_id']) ? (int)$_GET['profesor_id'] : null,
        ];
        [$sqlWhere, $params] = $this->buildWhere($filters, $role === 'profesor' ? (int)($_SESSION['user_id'] ?? 0) : null);

        $sqlAvg = "SELECT g.id, g.nombre AS grupo, m.nombre AS materia, g.ciclo, ROUND(AVG(c.final),2) AS promedio
                   FROM calificaciones c JOIN grupos g ON g.id = c.grupo_id JOIN materias m ON m.id = g.materia_id
                   $sqlWhere AND c.final IS NOT NULL
                   GROUP BY g.id, g.nombre, m.nombre, g.ciclo
                   ORDER BY promedio DESC LIMIT 5";
        $stmt = $this->pdo->prepare($sqlAvg);
        $stmt->execute($params);
        $topProm = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $sqlFail = "SELECT g.id, g.nombre AS grupo, m.nombre AS materia, g.ciclo,
                           ROUND(SUM(CASE WHEN c.final IS NOT NULL AND c.final < 70 THEN 1 ELSE 0 END) / NULLIF(COUNT(CASE WHEN c.final IS NOT NULL THEN 1 END),0) * 100, 2) AS porcentaje
                    FROM calificaciones c JOIN grupos g ON g.id = c.grupo_id JOIN materias m ON m.id = g.materia_id
                    $sqlWhere
                    GROUP BY g.id, g.nombre, m.nombre, g.ciclo
                    ORDER BY porcentaje DESC LIMIT 5";
        $stmt = $this->pdo->prepare($sqlFail);
        $stmt->execute($params);
        $topFail = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $sqlTopAlum = "SELECT a.matricula, CONCAT(a.nombre,' ',a.apellido) AS alumno,
                              ROUND(AVG(c.final),2) AS promedio
                        FROM calificaciones c
                        JOIN alumnos a ON a.id = c.alumno_id
                        JOIN grupos g ON g.id = c.grupo_id
                        $sqlWhere AND c.final IS NOT NULL
                        GROUP BY a.id, a.matricula, a.nombre, a.apellido
                        ORDER BY promedio DESC LIMIT 5";
        $stmt = $this->pdo->prepare($sqlTopAlum);
        $stmt->execute($params);
        $topAlumnos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $sqlRiesgo = "SELECT a.matricula, CONCAT(a.nombre,' ',a.apellido) AS alumno,
                             m.nombre AS materia, g.nombre AS grupo, g.ciclo, c.final
                       FROM calificaciones c
                       JOIN alumnos a ON a.id = c.alumno_id
                       JOIN grupos g ON g.id = c.grupo_id
                       JOIN materias m ON m.id = g.materia_id
                       $sqlWhere AND c.final IS NOT NULL AND c.final < 60
                       ORDER BY c.final ASC, a.apellido LIMIT 5";
        $stmt = $this->pdo->prepare($sqlRiesgo);
        $stmt->execute($params);
        $alumnosRiesgo = $stmt->fetchAll(PDO::FETCH_ASSOC);

        header('Content-Type: application/json');
        return json_encode(['ok' => true, 'data' => [
            'top_promedios' => $topProm,
            'top_reprobados' => $topFail,
            'top_alumnos' => $topAlumnos,
            'alumnos_riesgo' => $alumnosRiesgo,
        ]]);
    }
}
