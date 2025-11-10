<?php
namespace Controllers;

use Core\Controller;
use Core\Database;
use PDO;

class AdminController extends Controller
{
    /**
     * Convert UTF-8 text to ISO-8859-1 for FPDF without using deprecated utf8_decode.
     */
    private static function toLatin(string $text): string
    {
        $converted = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $text);
        if ($converted === false || $converted === null) {
            $converted = mb_convert_encoding($text, 'ISO-8859-1', 'UTF-8');
        }
        return $converted;
    }
    public static function getSidebarStats(): array
    {
        $pdo = Database::getConnection();
        // Consultas simples con prepared statements
        $stats = [];
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM alumnos');
        $stmt->execute();
        $stats['alumnos'] = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM profesores');
        $stmt->execute();
        $stats['profesores'] = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM carreras');
        $stmt->execute();
        $stats['carreras'] = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM materias');
        $stmt->execute();
        $stats['materias'] = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM grupos');
        $stmt->execute();
        $stats['grupos'] = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM periodos');
        $stmt->execute();
        $stats['periodos'] = (int)$stmt->fetchColumn();

        return $stats;
    }

    public static function isReinscripcionActiva(): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT valor FROM configuraciones WHERE clave='reinscripcion_activa'");
        return ($stmt->fetchColumn() === '1');
    }

    public function toggleReinscripcion(): void
    {
        $this->requireRole('admin');
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT valor FROM configuraciones WHERE clave='reinscripcion_activa'");
        $valor = $stmt->fetchColumn();
        $nuevo = ($valor === '1') ? '0' : '1';
        $upd = $pdo->prepare("UPDATE configuraciones SET valor=? WHERE clave='reinscripcion_activa'");
        $upd->execute([$nuevo]);
        header('Location: /?route=admin/dashboard&msg=reinscripcion_' . ($nuevo === '1' ? 'activada' : 'desactivada'));
        exit;
    }
    private function fetchEntityData(string $entity, string $q): array
    {
        $pdo = Database::getConnection();
        $rows = [];
        $headers = [];
        switch ($entity) {
            case 'carreras':
                $stmt = $pdo->prepare('SELECT id, nombre FROM carreras WHERE nombre LIKE ? ORDER BY nombre');
                $stmt->execute(['%'.$q.'%']);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $headers = ['id','nombre'];
                break;
            case 'periodos':
                $stmt = $pdo->prepare('SELECT id, nombre, activo FROM periodos WHERE nombre LIKE ? ORDER BY id DESC');
                $stmt->execute(['%'.$q.'%']);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $headers = ['id','nombre','activo'];
                break;
            case 'materias':
                $stmt = $pdo->prepare('SELECT m.id, m.nombre, m.semestre, c.nombre AS carrera FROM materias m JOIN carreras c ON c.id=m.carrera_id WHERE m.nombre LIKE ? ORDER BY c.nombre, m.semestre');
                $stmt->execute(['%'.$q.'%']);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $headers = ['id','nombre','semestre','carrera'];
                break;
            case 'grupos':
                $stmt = $pdo->prepare('SELECT g.id, g.clave, g.salon, m.nombre AS materia FROM grupos g JOIN materias m ON m.id=g.materia_id WHERE g.clave LIKE ? ORDER BY g.clave');
                $stmt->execute(['%'.$q.'%']);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $headers = ['id','clave','salon','materia'];
                break;
            case 'alumnos':
                $stmt = $pdo->prepare('SELECT id, matricula, nombre, apellido, semestre_actual, carrera_id FROM alumnos WHERE (matricula LIKE ? OR nombre LIKE ? OR apellido LIKE ?) ORDER BY matricula');
                $stmt->execute(['%'.$q.'%','%'.$q.'%','%'.$q.'%']);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $headers = ['id','matricula','nombre','apellido','semestre_actual','carrera_id'];
                break;
            case 'profesores':
                $stmt = $pdo->prepare('SELECT id, usuario, nombre, apellido, carrera_id FROM profesores WHERE (usuario LIKE ? OR nombre LIKE ? OR apellido LIKE ?) ORDER BY usuario');
                $stmt->execute(['%'.$q.'%','%'.$q.'%','%'.$q.'%']);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $headers = ['id','usuario','nombre','apellido','carrera_id'];
                break;
        }
        return [$headers, $rows];
    }
    public function dashboard(): void
    {
        $this->requireRole('admin');
        $pdo = Database::getConnection();
        $stats = [
            'alumnos' => (int)$pdo->query('SELECT COUNT(*) FROM alumnos')->fetchColumn(),
            'profesores' => (int)$pdo->query('SELECT COUNT(*) FROM profesores')->fetchColumn(),
            'carreras' => (int)$pdo->query('SELECT COUNT(*) FROM carreras')->fetchColumn(),
            'materias' => (int)$pdo->query('SELECT COUNT(*) FROM materias')->fetchColumn(),
            'grupos' => (int)$pdo->query('SELECT COUNT(*) FROM grupos')->fetchColumn(),
        ];
        // Alumnos por carrera
        $alumnosPorCarrera = [];
        $stmt = $pdo->query('SELECT c.nombre AS carrera, COUNT(a.id) AS total FROM carreras c LEFT JOIN alumnos a ON a.carrera_id=c.id GROUP BY c.id ORDER BY c.nombre');
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) { $alumnosPorCarrera[] = $row; }
        // Profesores por carrera
        $profesoresPorCarrera = [];
        $stmt2 = $pdo->query('SELECT c.nombre AS carrera, COUNT(p.id) AS total FROM carreras c LEFT JOIN profesores p ON p.carrera_id=c.id GROUP BY c.id ORDER BY c.nombre');
        while($row = $stmt2->fetch(PDO::FETCH_ASSOC)) { $profesoresPorCarrera[] = $row; }
        // Promedio global de calificaciones
        $avgStmt = $pdo->query('SELECT AVG(COALESCE(c.segunda_oportunidad, c.calificacion)) FROM calificaciones c');
        $promedioGlobal = (float)($avgStmt->fetchColumn() ?: 0);
        $this->render('admin/dashboard', [
            'stats' => $stats,
            'alumnos_por_carrera' => $alumnosPorCarrera,
            'profesores_por_carrera' => $profesoresPorCarrera,
            'promedio_global' => $promedioGlobal,
            'reinscripcion_activa' => self::isReinscripcionActiva(),
        ]);
    }

    public function stats(): void
    {
        $this->requireRole('admin');
        $this->render('admin/stats', [
            'reinscripcion_activa' => self::isReinscripcionActiva(),
        ]);
    }

    public function crud(): void
    {
        $this->requireRole('admin');
        $pdo = Database::getConnection();
        $entity = $_GET['entity'] ?? 'carreras';
        $q = trim((string)($_GET['q'] ?? ''));
        $page = max(1, (int)($_GET['page'] ?? 1));
        $per = min(50, max(10, (int)($_GET['per_page'] ?? 10)));
        $offset = ($page - 1) * $per;
        $rows = [];
        $total = 0;
        switch ($entity) {
            case 'carreras':
                $sql = 'SELECT SQL_CALC_FOUND_ROWS * FROM carreras WHERE nombre LIKE ? ORDER BY nombre LIMIT ' . (int)$per . ' OFFSET ' . (int)$offset;
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['%'.$q.'%']);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $total = (int)$pdo->query('SELECT FOUND_ROWS()')->fetchColumn();
                break;
            case 'periodos':
                $sql = 'SELECT SQL_CALC_FOUND_ROWS id, nombre, activo FROM periodos WHERE nombre LIKE ? ORDER BY id DESC LIMIT ' . (int)$per . ' OFFSET ' . (int)$offset;
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['%'.$q.'%']);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $total = (int)$pdo->query('SELECT FOUND_ROWS()')->fetchColumn();
                break;
            case 'materias':
                $sql = 'SELECT SQL_CALC_FOUND_ROWS m.id, m.nombre, m.semestre, c.nombre AS carrera FROM materias m JOIN carreras c ON c.id=m.carrera_id WHERE m.nombre LIKE ? ORDER BY c.nombre, m.semestre LIMIT ' . (int)$per . ' OFFSET ' . (int)$offset;
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['%'.$q.'%']);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $total = (int)$pdo->query('SELECT FOUND_ROWS()')->fetchColumn();
                break;
            case 'grupos':
                $sql = 'SELECT SQL_CALC_FOUND_ROWS g.id, g.clave, g.salon, m.nombre AS materia FROM grupos g JOIN materias m ON m.id=g.materia_id WHERE g.clave LIKE ? ORDER BY g.clave LIMIT ' . (int)$per . ' OFFSET ' . (int)$offset;
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['%'.$q.'%']);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $total = (int)$pdo->query('SELECT FOUND_ROWS()')->fetchColumn();
                break;
            case 'alumnos':
                $sql = 'SELECT SQL_CALC_FOUND_ROWS id, matricula, nombre, apellido, semestre_actual, carrera_id FROM alumnos WHERE (matricula LIKE ? OR nombre LIKE ? OR apellido LIKE ?) ORDER BY matricula LIMIT ' . (int)$per . ' OFFSET ' . (int)$offset;
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['%'.$q.'%','%'.$q.'%','%'.$q.'%']);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $total = (int)$pdo->query('SELECT FOUND_ROWS()')->fetchColumn();
                break;
            case 'profesores':
                $sql = 'SELECT SQL_CALC_FOUND_ROWS id, usuario, nombre, apellido, carrera_id FROM profesores WHERE (usuario LIKE ? OR nombre LIKE ? OR apellido LIKE ?) ORDER BY usuario LIMIT ' . (int)$per . ' OFFSET ' . (int)$offset;
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['%'.$q.'%','%'.$q.'%','%'.$q.'%']);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $total = (int)$pdo->query('SELECT FOUND_ROWS()')->fetchColumn();
                break;
        }
        $this->render('admin/crud', ['entity' => $entity, 'rows' => $rows, 'q' => $q, 'page' => $page, 'per_page' => $per, 'total' => $total]);
    }

    public function exportPDF(): void
    {
        $this->requireRole('admin');
        $mode = $_GET['mode'] ?? ($_POST['mode'] ?? '');
        $entity = $_GET['entity'] ?? ($_POST['entity'] ?? '');
        $q = trim((string)($_GET['q'] ?? ($_POST['q'] ?? '')));

        // Cargar librerías
        $autoload = __DIR__ . '/../vendor/autoload.php';
        if (file_exists($autoload)) { require_once $autoload; }
        if (!class_exists('FPDF')) {
            $fpdfPath = __DIR__ . '/../lib/fpdf/fpdf.php';
            if (file_exists($fpdfPath)) { require_once $fpdfPath; }
        }
        if (!class_exists('FPDF')) {
            http_response_code(500);
            echo 'FPDF no disponible. Instala la dependencia.';
            return;
        }

        $date = date('Y-m-d_H-i');
        $title = ($mode==='dashboard') ? 'Dashboard' : ('CRUD: ' . $entity);

        $pdf = new \FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial','B',16);
        $pdf->Cell(0,10,self::toLatin('SICEnet - Exportación ' . $title),0,1,'C');
        $pdf->SetFont('Arial','',10);
        $pdf->Cell(0,6,self::toLatin('Fecha: ' . date('Y-m-d H:i')),0,1,'R');
        $pdf->Ln(4);

        if ($mode==='dashboard') {
            // Resumen de métricas y datos de gráficas
            $pdo = Database::getConnection();
            $stats = [
                'Carreras' => (int)$pdo->query('SELECT COUNT(*) FROM carreras')->fetchColumn(),
                'Alumnos' => (int)$pdo->query('SELECT COUNT(*) FROM alumnos')->fetchColumn(),
                'Profesores' => (int)$pdo->query('SELECT COUNT(*) FROM profesores')->fetchColumn(),
                'Materias' => (int)$pdo->query('SELECT COUNT(*) FROM materias')->fetchColumn(),
                'Grupos' => (int)$pdo->query('SELECT COUNT(*) FROM grupos')->fetchColumn(),
            ];
            $avgStmt = $pdo->query('SELECT AVG(COALESCE(c.segunda_oportunidad, c.calificacion)) FROM calificaciones c');
            $promedioGlobal = (float)($avgStmt->fetchColumn() ?: 0);
            // Sección resumen
            $pdf->SetFont('Arial','B',12);
            $pdf->Cell(0,8,self::toLatin('Resumen de métricas'),0,1);
            $pdf->SetFont('Arial','',10);
            foreach($stats as $k=>$v){ $pdf->Cell(0,6,self::toLatin($k.': '.$v),0,1); }
            $pdf->Cell(0,6,self::toLatin('Promedio global: '.number_format($promedioGlobal,2)),0,1);
            $pdf->Ln(4);
            // Tablas de alumnos/profesores por carrera
            $pdf->SetFont('Arial','B',12);
            $pdf->Cell(0,8,self::toLatin('Alumnos por carrera'),0,1);
            $pdf->SetFont('Arial','',10);
            $stmt = $pdo->query('SELECT c.nombre AS carrera, COUNT(a.id) AS total FROM carreras c LEFT JOIN alumnos a ON a.carrera_id=c.id GROUP BY c.id ORDER BY c.nombre');
            while($row=$stmt->fetch(PDO::FETCH_ASSOC)){ $pdf->Cell(0,6,self::toLatin($row['carrera'].': '.$row['total']),0,1); }
            $pdf->Ln(2);

            $pdf->SetFont('Arial','B',12);
            $pdf->Cell(0,8,self::toLatin('Profesores por carrera'),0,1);
            $pdf->SetFont('Arial','',10);
            $stmt2 = $pdo->query('SELECT c.nombre AS carrera, COUNT(p.id) AS total FROM carreras c LEFT JOIN profesores p ON p.carrera_id=c.id GROUP BY c.id ORDER BY c.nombre');
            while($row=$stmt2->fetch(PDO::FETCH_ASSOC)){ $pdf->Cell(0,6,self::toLatin($row['carrera'].': '.$row['total']),0,1); }
        } else {
            // Exportación del CRUD
            [$headers, $rows] = $this->fetchEntityData($entity, $q);
            $pdf->SetFont('Arial','B',12);
            $pdf->Cell(0,8,self::toLatin('Listado de '.ucfirst($entity)),0,1);
            $pdf->SetFont('Arial','',9);
            $colCount = max(1, count($headers));
            $w = 190 / $colCount;
            // Encabezados
            foreach($headers as $h){ $pdf->Cell($w,8,self::toLatin(strtoupper($h)),1,0,'C'); }
            $pdf->Ln();
            // Filas
            foreach($rows as $r){
                foreach($headers as $h){ $pdf->Cell($w,7,self::toLatin((string)($r[$h] ?? '')),1,0); }
                $pdf->Ln();
            }
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="export_'.($mode==='dashboard'?'dashboard':$entity).'_'.$date.'.pdf"');
        $pdf->Output('I');
        exit;
    }

    public function exportExcel(): void
    {
        $this->requireRole('admin');
        $mode = $_GET['mode'] ?? ($_POST['mode'] ?? '');
        $entity = $_GET['entity'] ?? ($_POST['entity'] ?? '');
        $q = trim((string)($_GET['q'] ?? ($_POST['q'] ?? '')));

        // Cargar librerías
        $autoload = __DIR__ . '/../vendor/autoload.php';
        if (file_exists($autoload)) { require_once $autoload; }
        if (!class_exists('PhpOffice\\PhpSpreadsheet\\Spreadsheet')) {
            $psAuto = __DIR__ . '/../lib/PhpSpreadsheet/vendor/autoload.php';
            if (file_exists($psAuto)) { require_once $psAuto; }
        }
        if (!class_exists('PhpOffice\\PhpSpreadsheet\\Spreadsheet')) {
            http_response_code(500);
            echo 'PhpSpreadsheet no disponible. Instala la dependencia.';
            return;
        }

        $date = date('Y-m-d_H-i');
        $filename = 'export_'.($mode==='dashboard'?'dashboard':$entity).'_'.$date.'.xlsx';
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $spreadsheet->getProperties()->setCreator('SICEnet')->setTitle('Exportación');

        if ($mode==='dashboard') {
            $pdo = Database::getConnection();
            $stats = [
                ['Métrica','Valor'],
                ['Carreras', (int)$pdo->query('SELECT COUNT(*) FROM carreras')->fetchColumn()],
                ['Alumnos', (int)$pdo->query('SELECT COUNT(*) FROM alumnos')->fetchColumn()],
                ['Profesores', (int)$pdo->query('SELECT COUNT(*) FROM profesores')->fetchColumn()],
                ['Materias', (int)$pdo->query('SELECT COUNT(*) FROM materias')->fetchColumn()],
                ['Grupos', (int)$pdo->query('SELECT COUNT(*) FROM grupos')->fetchColumn()],
            ];
            $avgStmt = $pdo->query('SELECT AVG(COALESCE(c.segunda_oportunidad, c.calificacion)) FROM calificaciones c');
            $promedioGlobal = (float)($avgStmt->fetchColumn() ?: 0);

            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Resumen');
            $sheet->fromArray($stats, null, 'A1');
            $sheet->setCellValue('A'.(count($stats)+2), 'Promedio global');
            $sheet->setCellValue('B'.(count($stats)+2), number_format($promedioGlobal,2));

            // Alumnos por carrera
            $sheet2 = $spreadsheet->createSheet();
            $sheet2->setTitle('AlumnosPorCarrera');
            $sheet2->fromArray([['Carrera','Total']], null, 'A1');
            $stmt = $pdo->query('SELECT c.nombre AS carrera, COUNT(a.id) AS total FROM carreras c LEFT JOIN alumnos a ON a.carrera_id=c.id GROUP BY c.id ORDER BY c.nombre');
            $rowIdx = 2;
            while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
                $sheet2->setCellValue('A'.$rowIdx, $row['carrera']);
                $sheet2->setCellValue('B'.$rowIdx, (int)$row['total']);
                $rowIdx++;
            }
            // Profesores por carrera
            $sheet3 = $spreadsheet->createSheet();
            $sheet3->setTitle('ProfesoresPorCarrera');
            $sheet3->fromArray([['Carrera','Total']], null, 'A1');
            $stmt2 = $pdo->query('SELECT c.nombre AS carrera, COUNT(p.id) AS total FROM carreras c LEFT JOIN profesores p ON p.carrera_id=c.id GROUP BY c.id ORDER BY c.nombre');
            $rowIdx = 2;
            while($row=$stmt2->fetch(PDO::FETCH_ASSOC)){
                $sheet3->setCellValue('A'.$rowIdx, $row['carrera']);
                $sheet3->setCellValue('B'.$rowIdx, (int)$row['total']);
                $rowIdx++;
            }
        } else {
            [$headers, $rows] = $this->fetchEntityData($entity, $q);
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle(ucfirst($entity));
            $sheet->fromArray($headers, null, 'A1');
            $sheet->fromArray($rows, null, 'A2');
        }

        foreach ($spreadsheet->getAllSheets() as $sh) {
            foreach(range('A','Z') as $col){ $sh->getColumnDimension($col)->setAutoSize(true); }
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="'.$filename.'"');
        header('Cache-Control: max-age=0');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    public function crudSave(): void
    {
        $this->requireRole('admin');
        $pdo = Database::getConnection();
        $csrf = \Core\Security::input('csrf_token');
        if (!\Core\Security::verifyCsrf($csrf)) {
            $_SESSION['error'] = 'CSRF inválido';
            $this->redirect('admin/dashboard');
        }
        $entity = $_POST['entity'] ?? '';
        switch ($entity) {
            case 'carreras':
                $nombre = trim($_POST['nombre'] ?? '');
                if ($nombre !== '') {
                    $stmt = $pdo->prepare('INSERT INTO carreras (nombre) VALUES (?)');
                    $stmt->execute([$nombre]);
                }
                break;
            case 'periodos':
                $nombre = trim($_POST['nombre'] ?? '');
                $activo = isset($_POST['activo']) ? 1 : 0;
                if ($nombre !== '') {
                    if ($activo === 1) {
                        // Asegurar único período activo
                        $pdo->exec('UPDATE periodos SET activo=0');
                    }
                    $stmt = $pdo->prepare('INSERT INTO periodos (nombre, activo) VALUES (?,?)');
                    $stmt->execute([$nombre, $activo]);
                }
                break;
            case 'materias':
                $nombre = trim($_POST['nombre'] ?? '');
                $semestre = (int)($_POST['semestre'] ?? 1);
                $carrera_id = (int)($_POST['carrera_id'] ?? 0);
                $unidades = max(3, min(11, (int)($_POST['unidades'] ?? 5)));
                if ($nombre !== '' && $carrera_id > 0) {
                    $stmt = $pdo->prepare('INSERT INTO materias (carrera_id, nombre, semestre, unidades) VALUES (?,?,?,?)');
                    $stmt->execute([$carrera_id, $nombre, $semestre, $unidades]);
                }
                break;
            case 'grupos':
                $materia_id = (int)($_POST['materia_id'] ?? 0);
                $profesor_id = (int)($_POST['profesor_id'] ?? 0);
                $clave = trim($_POST['clave'] ?? '');
                $salon = trim($_POST['salon'] ?? '');
                if ($materia_id > 0 && $profesor_id > 0 && $clave !== '') {
                    $stmt = $pdo->prepare('INSERT INTO grupos (materia_id, profesor_id, clave, salon) VALUES (?,?,?,?)');
                    $stmt->execute([$materia_id, $profesor_id, $clave, $salon]);
                }
                break;
            case 'alumnos':
                $matricula = trim($_POST['matricula'] ?? '');
                $nombre = trim($_POST['nombre'] ?? '');
                $apellido = trim($_POST['apellido'] ?? '');
                $carrera_id = (int)($_POST['carrera_id'] ?? 0);
                $semestre = (int)($_POST['semestre_actual'] ?? 1);
                $password = $_POST['password'] ?? '';
                if (preg_match('/^\d{9}$/', $matricula) && strlen($password) >= 8) {
                    $hash = password_hash($password, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare('INSERT INTO alumnos (matricula, nombre, apellido, carrera_id, semestre_actual, password_hash) VALUES (?,?,?,?,?,?)');
                    $stmt->execute([$matricula, $nombre, $apellido, $carrera_id, $semestre, $hash]);
                }
                break;
            case 'profesores':
                $usuario = trim($_POST['usuario'] ?? '');
                $nombre = trim($_POST['nombre'] ?? '');
                $apellido = trim($_POST['apellido'] ?? '');
                $carrera_id = (int)($_POST['carrera_id'] ?? 0);
                $password = $_POST['password'] ?? '';
                if ($usuario !== '' && strlen($password) >= 8) {
                    $hash = password_hash($password, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare('INSERT INTO profesores (usuario, nombre, apellido, carrera_id, password_hash) VALUES (?,?,?,?,?)');
                    $stmt->execute([$usuario, $nombre, $apellido, $carrera_id, $hash]);
                }
                break;
        }
        $_SESSION['success'] = 'Guardado';
        $this->redirect('admin/crud&entity=' . $entity . '&msg=guardado');
    }

    public function crudUpdate(): void
    {
        $this->requireRole('admin');
        $pdo = Database::getConnection();
        $csrf = \Core\Security::input('csrf_token');
        if (!\Core\Security::verifyCsrf($csrf)) {
            $_SESSION['error'] = 'CSRF inválido';
            $this->redirect('admin/dashboard');
        }
        $entity = $_POST['entity'] ?? '';
        $id = (int)(\Core\Security::input('id','POST',FILTER_SANITIZE_NUMBER_INT) ?? 0);
        switch ($entity) {
            case 'carreras':
                $nombre = trim($_POST['nombre'] ?? '');
                if ($id>0 && $nombre !== '') {
                    $stmt = $pdo->prepare('UPDATE carreras SET nombre=? WHERE id=?');
                    $stmt->execute([$nombre, $id]);
                }
                break;
            case 'periodos':
                $nombre = trim($_POST['nombre'] ?? '');
                $activo = (int)($_POST['activo'] ?? 0);
                if ($id>0 && $nombre !== '') {
                    if ($activo === 1) {
                        // Un solo activo
                        $pdo->exec('UPDATE periodos SET activo=0');
                        $stmt = $pdo->prepare('UPDATE periodos SET nombre=?, activo=1 WHERE id=?');
                        $stmt->execute([$nombre, $id]);
                    } else {
                        $stmt = $pdo->prepare('UPDATE periodos SET nombre=?, activo=0 WHERE id=?');
                        $stmt->execute([$nombre, $id]);
                    }
                }
                break;
            case 'materias':
                $nombre = trim($_POST['nombre'] ?? '');
                $semestre = (int)($_POST['semestre'] ?? 1);
                $carrera_id = (int)($_POST['carrera_id'] ?? 0);
                $unidades = max(3, min(11, (int)($_POST['unidades'] ?? 5)));
                if ($id>0 && $nombre !== '' && $carrera_id > 0) {
                    $stmt = $pdo->prepare('UPDATE materias SET carrera_id=?, nombre=?, semestre=?, unidades=? WHERE id=?');
                    $stmt->execute([$carrera_id, $nombre, $semestre, $unidades, $id]);
                }
                break;
            case 'grupos':
                $materia_id = (int)($_POST['materia_id'] ?? 0);
                $profesor_id = (int)($_POST['profesor_id'] ?? 0);
                $clave = trim($_POST['clave'] ?? '');
                $salon = trim($_POST['salon'] ?? '');
                $carrera_id = (int)($_POST['carrera_id'] ?? 0);
                if ($id>0 && $materia_id > 0 && $profesor_id > 0 && $clave !== '') {
                    $stmt = $pdo->prepare('UPDATE grupos SET carrera_id=?, materia_id=?, profesor_id=?, clave=?, salon=? WHERE id=?');
                    $stmt->execute([$carrera_id, $materia_id, $profesor_id, $clave, $salon, $id]);
                }
                break;
            case 'alumnos':
                $matricula = trim($_POST['matricula'] ?? '');
                $nombre = trim($_POST['nombre'] ?? '');
                $apellido = trim($_POST['apellido'] ?? '');
                $carrera_id = (int)($_POST['carrera_id'] ?? 0);
                $semestre = (int)($_POST['semestre_actual'] ?? 1);
                if ($id>0 && preg_match('/^\d{9}$/', $matricula)) {
                    $stmt = $pdo->prepare('UPDATE alumnos SET matricula=?, nombre=?, apellido=?, carrera_id=?, semestre_actual=? WHERE id=?');
                    $stmt->execute([$matricula, $nombre, $apellido, $carrera_id, $semestre, $id]);
                }
                break;
            case 'profesores':
                $usuario = trim($_POST['usuario'] ?? '');
                $nombre = trim($_POST['nombre'] ?? '');
                $apellido = trim($_POST['apellido'] ?? '');
                $carrera_id = (int)($_POST['carrera_id'] ?? 0);
                if ($id>0 && $usuario !== '') {
                    $stmt = $pdo->prepare('UPDATE profesores SET usuario=?, nombre=?, apellido=?, carrera_id=? WHERE id=?');
                    $stmt->execute([$usuario, $nombre, $apellido, $carrera_id, $id]);
                }
                break;
        }
        $_SESSION['success'] = 'Actualizado';
        $this->redirect('admin/crud&entity=' . $entity . '&msg=actualizado');
    }

    public function crudDelete(): void
    {
        $this->requireRole('admin');
        $pdo = Database::getConnection();
        $csrf = \Core\Security::input('csrf_token');
        if (!\Core\Security::verifyCsrf($csrf)) {
            $_SESSION['error'] = 'CSRF inválido';
            $this->redirect('admin/dashboard');
        }
        $entity = $_POST['entity'] ?? '';
        $id = (int)(\Core\Security::input('id','POST',FILTER_SANITIZE_NUMBER_INT) ?? 0);
        switch ($entity) {
            case 'carreras':
                $stmt = $pdo->prepare('DELETE FROM carreras WHERE id=?');
                $stmt->execute([$id]);
                break;
            case 'periodos':
                // No permitir borrar períodos activos o con inscripciones asociadas
                $chk = $pdo->prepare('SELECT activo FROM periodos WHERE id=?');
                $chk->execute([$id]);
                $activo = (int)($chk->fetchColumn() ?? 0);
                if ($activo === 1) {
                    $_SESSION['error'] = 'No se puede eliminar el período ACTIVO.';
                    $this->redirect('admin/crud&entity=periodos');
                }
                $cnt = $pdo->prepare('SELECT COUNT(*) FROM inscripciones WHERE periodo_id=?');
                $cnt->execute([$id]);
                if ((int)$cnt->fetchColumn() > 0) {
                    $_SESSION['error'] = 'No se puede eliminar un período con inscripciones asociadas.';
                    $this->redirect('admin/crud&entity=periodos');
                }
                $stmt = $pdo->prepare('DELETE FROM periodos WHERE id=?');
                $stmt->execute([$id]);
                break;
            case 'materias':
                $stmt = $pdo->prepare('DELETE FROM materias WHERE id=?');
                $stmt->execute([$id]);
                break;
            case 'grupos':
                $stmt = $pdo->prepare('DELETE FROM grupos WHERE id=?');
                $stmt->execute([$id]);
                break;
            case 'alumnos':
                $stmt = $pdo->prepare('DELETE FROM alumnos WHERE id=?');
                $stmt->execute([$id]);
                break;
            case 'profesores':
                $stmt = $pdo->prepare('DELETE FROM profesores WHERE id=?');
                $stmt->execute([$id]);
                break;
        }
        $_SESSION['success'] = 'Eliminado';
        $this->redirect('admin/crud&entity=' . $entity . '&msg=eliminado');
    }
}