<?php
require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/models/Materia.php';
require_once __DIR__ . '/../app/models/Usuario.php';

$auth = new AuthController();
$auth->requireAuth();
$user = $auth->getCurrentUser();
$role = $_SESSION['user_role'] ?? '';

// Determinar carrera por prefijo de matrícula del alumno
$carreras = [
  'S' => ['label' => 'Ingeniería en Sistemas Computacionales', 'prefix' => ['INF','CSI','PROG','BD']],
  'I' => ['label' => 'Ingeniería Industrial', 'prefix' => ['IND','EST','ADM']],
  'C' => ['label' => 'Ingeniería Civil', 'prefix' => ['CIV','EST','MAT']],
  'M' => ['label' => 'Ingeniería Mecánica', 'prefix' => ['MEC','MAT','EST']],
  'Q' => ['label' => 'Ingeniería Química', 'prefix' => ['QUI','QIM','MAT']],
  'E' => ['label' => 'Ingeniería Electrónica', 'prefix' => ['ELE','ELC','DIG']],
  'A' => ['label' => 'Ingeniería Ambiental', 'prefix' => ['AMB','BIO','MAT']],
];

$careerKey = null; $career = null;
if ($role === 'alumno') {
  $mat = (string)($user['matricula'] ?? '');
  $careerKey = $mat !== '' ? strtoupper($mat[0]) : null;
  $career = $careerKey && isset($carreras[$careerKey]) ? $carreras[$careerKey] : null;
} elseif ($role === 'admin') {
  $sel = strtoupper(trim((string)($_GET['carrera'] ?? '')));
  if ($sel === '' || !isset($carreras[$sel])) {
    // valor por defecto para admin
    $sel = 'S';
  }
  $careerKey = $sel;
  $career = $carreras[$careerKey];
}

// Catálogo de materias
$materiaModel = new Materia();
$catalog = $materiaModel->getCatalog();

// Filtrar materias por carrera (prefijos)
function matchPrefix($clave, $prefixes) {
  $clave = (string)$clave;
  foreach ($prefixes as $p) {
    if (stripos($clave, $p) === 0) { return true; }
  }
  return false;
}

$materiasCarrera = [];
if ($career) {
  foreach ($catalog as $m) {
    if (matchPrefix($m['clave'] ?? '', $career['prefix'])) {
      $materiasCarrera[] = $m;
    }
  }
}

// Construir retícula: 8 semestres x 5 materias = 40
$semestres = [];
for ($s=1; $s<=8; $s++) { $semestres[$s] = []; }

// Relleno con materias disponibles; si faltan, marcar como pendiente
$idx = 0;
for ($s=1; $s<=8; $s++) {
  for ($i=0; $i<5; $i++) {
    $mat = $materiasCarrera[$idx] ?? null;
    if ($mat) {
      $semestres[$s][] = $mat;
      $idx++;
    } else {
      $semestres[$s][] = ['nombre' => 'Pendiente de alta', 'clave' => '—'];
    }
  }
}

// Plantillas por carrera para mostrar nombres completos cuando falten en la BD
$plantillasPorCarrera = [
  'S' => [
    1 => ['Fundamentos de Programación','Matemáticas I','Física I','Computación Básica','Comunicación Oral y Escrita'],
    2 => ['Programación Orientada a Objetos','Álgebra Lineal','Estructuras Discretas','Electricidad y Magnetismo','Ética Profesional'],
    3 => ['Estructuras de Datos','Probabilidad y Estadística','Arquitectura de Computadoras','Bases de Datos I','Desarrollo Web I'],
    4 => ['Análisis y Diseño de Sistemas','Sistemas Operativos','Bases de Datos II','Desarrollo Web II','Métodos Numéricos'],
    5 => ['Ingeniería de Software','Redes de Computadoras I','Programación Avanzada','Administración de BD','Desarrollo Móvil I'],
    6 => ['Redes de Computadoras II','Seguridad Informática','Inteligencia Artificial','Computación Paralela','Desarrollo Móvil II'],
    7 => ['Minería de Datos','Desarrollo de APIs','Cloud Computing','UX/UI','Gestión de Proyectos TI'],
    8 => ['Big Data','DevOps','Arquitecturas Empresariales','Integración de Sistemas','Seminario de Titulación'],
  ],
  'I' => [
    1 => ['Dibujo Industrial','Matemáticas I','Física I','Química','Comunicación Oral y Escrita'],
    2 => ['Estadística I','Álgebra Lineal','Ciencia de Materiales','Termodinámica','Ética Profesional'],
    3 => ['Estadística II','Métodos de Ingeniería','Procesos de Manufactura I','Inv. de Operaciones I','Ergonomía'],
    4 => ['Control de Calidad I','Procesos de Manufactura II','Inv. de Operaciones II','Ingeniería Económica','Higiene y Seguridad'],
    5 => ['Planeación y Control de la Producción I','Simulación','Control de Calidad II','Sistemas de Información','Logística I'],
    6 => ['Planeación y Control de la Producción II','Automatización Industrial','Mantenimiento','Logística II','Gestión de Proyectos'],
    7 => ['Mejora Continua','LEAN Manufacturing','Cadena de Suministro','Gestión de Calidad','Auditoría de Procesos'],
    8 => ['Gestión Ambiental','Innovación en Manufactura','Sistemas Integrados','Dirección de Operaciones','Seminario de Titulación'],
  ],
  'C' => [
    1 => ['Dibujo Técnico','Matemáticas I','Física I','Química','Comunicación Oral y Escrita'],
    2 => ['Topografía','Álgebra Lineal','Mecánica','Estática','Ética Profesional'],
    3 => ['Resistencia de Materiales','Hidráulica','Geología','Construcción I','Probabilidad y Estadística'],
    4 => ['Análisis Estructural I','Mecánica de Suelos','Construcción II','Instalaciones','Costos y Presupuestos'],
    5 => ['Análisis Estructural II','Concreto Reforzado','Vías Terrestres I','Hidrología','Urbanismo'],
    6 => ['Estructuras de Acero','Vías Terrestres II','Saneamiento','Planeación Urbana','Administración de Obras'],
    7 => ['Estructuras Avanzadas','Pavimentos','Gestión de Proyectos','Evaluación de Impacto','Seguridad en Obras'],
    8 => ['Ingeniería Ambiental','Construcción Sustentable','Supervisión de Obras','Dirección de Proyectos','Seminario de Titulación'],
  ],
  'M' => [
    1 => ['Dibujo Mecánico','Matemáticas I','Física I','Química','Comunicación Oral y Escrita'],
    2 => ['Álgebra Lineal','Termodinámica I','Estática','Materiales','Ética Profesional'],
    3 => ['Dinámica','Probabilidad y Estadística','Procesos de Manufactura I','Mecánica de Fluidos','Metrología'],
    4 => ['Mecanismos','Transferencia de Calor','Procesos de Manufactura II','Máquinas Herramienta','Ingeniería de Materiales'],
    5 => ['Diseño Mecánico I','Control','Mantenimiento','CAD/CAM','Gestión de Proyectos'],
    6 => ['Diseño Mecánico II','Automatización','Robótica','Máquinas Térmicas','Vibraciones'],
    7 => ['Sistemas de Manufactura','Ingeniería de Confiabilidad','Energías Alternas','Lean Manufacturing','Seguridad Industrial'],
    8 => ['Gestión de Mantenimiento','Innovación Tecnológica','Selección de Materiales','Dirección de Proyectos','Seminario de Titulación'],
  ],
  'Q' => [
    1 => ['Química General','Matemáticas I','Física I','Biología','Comunicación Oral y Escrita'],
    2 => ['Química Inorgánica','Álgebra Lineal','Termodinámica','Química Analítica','Ética Profesional'],
    3 => ['Química Orgánica I','Probabilidad y Estadística','Balance de Materia y Energía','Fisicoquímica','Microbiología'],
    4 => ['Química Orgánica II','Operaciones Unitarias I','Instrumentación','Bioquímica','Métodos Numéricos'],
    5 => ['Operaciones Unitarias II','Ingeniería de Reactores I','Control de Procesos','Análisis de Alimentos','Gestión de Laboratorio'],
    6 => ['Ingeniería de Reactores II','Procesos Químicos','Bioprocesos','Corrosión','Gestión de Proyectos'],
    7 => ['Diseño de Plantas','Seguridad de Procesos','Ambiental en Procesos','Simulación de Procesos','Calidad en Procesos'],
    8 => ['Optimización de Procesos','Innovación Química','Escalamiento','Dirección de Proyectos','Seminario de Titulación'],
  ],
  'E' => [
    1 => ['Introducción a la Electrónica','Matemáticas I','Física I','Computación Básica','Comunicación Oral y Escrita'],
    2 => ['Álgebra Lineal','Electricidad y Magnetismo','Circuitos I','Programación','Ética Profesional'],
    3 => ['Circuitos II','Probabilidad y Estadística','Electrónica Analógica','Arquitectura de Computadoras','Señales y Sistemas'],
    4 => ['Electrónica Digital','Microcontroladores','Sistemas de Comunicación I','Instrumentación','Métodos Numéricos'],
    5 => ['Sistemas de Comunicación II','Control Automático','Procesamiento Digital de Señales','Redes','Gestión de Proyectos'],
    6 => ['Electrónica de Potencia','Sistemas Embebidos','Antenas','Robótica','Seguridad de Sistemas'],
    7 => ['Optoelectrónica','Comunicaciones Inalámbricas','Sensores Inteligentes','Internet de las Cosas','Sistemas Avanzados'],
    8 => ['Telecomunicaciones Avanzadas','Automatización Avanzada','Innovación Tecnológica','Dirección de Proyectos','Seminario de Titulación'],
  ],
  'A' => [
    1 => ['Introducción a la Ambiental','Matemáticas I','Física I','Química','Comunicación Oral y Escrita'],
    2 => ['Álgebra Lineal','Biología','Estadística I','Química Ambiental','Ética Profesional'],
    3 => ['Estadística II','Hidrología','Suelos','Microbiología Ambiental','Contaminación del Aire'],
    4 => ['Saneamiento','Tratamiento de Aguas','Gestión de Residuos','Impacto Ambiental','Métodos Numéricos'],
    5 => ['Remediación de Suelos','Energías Renovables','Gestión Ambiental','Legislación Ambiental','Modelación Ambiental'],
    6 => ['Auditoría Ambiental','Sistemas de Gestión','Cambio Climático','Tecnologías Limpias','Gestión de Proyectos'],
    7 => ['Evaluación Ambiental Avanzada','Economía Ambiental','Planificación Ambiental','Sustentabilidad','Calidad Ambiental'],
    8 => ['Innovación Ambiental','Consultoría Ambiental','Dirección de Proyectos','Sistemas Integrados','Seminario de Titulación'],
  ],
];

if ($careerKey && isset($plantillasPorCarrera[$careerKey])) {
  $tpl = $plantillasPorCarrera[$careerKey];
  for ($s=1; $s<=8; $s++) {
    for ($i=0; $i<5; $i++) {
      if (($semestres[$s][$i]['clave'] ?? '—') === '—') {
        $semestres[$s][$i]['nombre'] = $tpl[$s][$i] ?? $semestres[$s][$i]['nombre'];
        $semestres[$s][$i]['clave'] = '—'; // mantener sin clave para conteo correcto
      }
    }
  }
}

?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>SICEnet · ITSUR — Retícula Académica</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/css/styles.css" rel="stylesheet">
  <style>
    .grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
    @media (min-width: 992px) { .grid { grid-template-columns: repeat(4, 1fr); } }
    @media (max-width: 991px) { .grid { grid-template-columns: repeat(2, 1fr); } }
    .sem-card { border: 1px solid #dee2e6; border-radius: 8px; }
    .sem-header { background: #f8f9fa; border-bottom: 1px solid #dee2e6; padding: 8px 12px; font-weight: 600; }
    .sem-body { padding: 8px 12px; }
    .pending { color: #6c757d; font-style: italic; }
    .print-header { display:none; text-align:center; margin-bottom:12px; }
    .print-header h2 { margin: 0; font-size: 20px; }
    .print-header .meta { font-size: 12px; color: #555; }
    @media print {
      body * { visibility: hidden; }
      .print-area, .print-area * { visibility: visible; }
      .print-area { position: absolute; left:0; top:0; width:100%; }
      .print-header { display:block; }
      nav, .app-sidebar, .btn, .badge { display:none !important; }
    }
  </style>
</head>
<body>
<!-- Header institucional compacto -->
<header class="institutional-header">
  <div class="container-fluid">
    <a href="dashboard.php" class="institutional-brand">
      <img src="assets/ITSUR-LOGO.webp" alt="ITSUR Logo" class="institutional-logo">
      <div class="institutional-text">
        <h1 class="institutional-title">SICEnet · ITSUR</h1>
        <p class="institutional-subtitle">Sistema Integral de Control Escolar</p>
      </div>
    </a>
  </div>
</header>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="dashboard.php">
          <img src="assets/ITSUR-LOGO.webp" alt="ITSUR Logo" class="navbar-logo me-2">
          <span class="brand-text">SICEnet · ITSUR</span>
        </a>
        <button class="btn btn-outline-light btn-sm ms-auto me-2" id="themeToggle" title="Cambiar tema">
          <i class="bi bi-sun-fill"></i>
        </button>
  </div>
  <div class="container-fluid">
    <span class="navbar-text text-white">Retícula Académica</span>
  </div>
</nav>

  <div class="app-shell">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>
  <main class="app-content">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h1 class="h3">Retícula Académica</h1>
      <div>
        <?php if ($role === 'alumno' && $career): ?>
          <span class="badge bg-primary">Carrera: <?= htmlspecialchars($career['label']) ?></span>
        <?php elseif ($role === 'admin'): ?>
          <form class="d-inline" method="get">
            <label class="form-label me-2 mb-0">Carrera</label>
            <select name="carrera" class="form-select d-inline w-auto" onchange="this.form.submit()">
              <?php foreach ($carreras as $key => $info): ?>
                <option value="<?= htmlspecialchars($key) ?>" <?= $key === $careerKey ? 'selected' : '' ?>><?= htmlspecialchars($info['label']) ?></option>
              <?php endforeach; ?>
            </select>
          </form>
          <button class="btn btn-outline-secondary ms-2" onclick="window.print()"><i class="bi bi-printer"></i> Exportar PDF</button>
        <?php else: ?>
          <span class="badge bg-secondary">Carrera no determinada</span>
        <?php endif; ?>
      </div>
    </div>

    <div class="alert alert-info">
      Para poder cursar <strong>Residencias Profesionales (9º semestre)</strong> es indispensable haber liberado los <strong>10 niveles de Inglés</strong> y el <strong>Servicio Social</strong>.
    </div>

    <?php
      // Resumen de conteos
      $totalPlan = 8 * 5; // 40 materias
      $totalReg = 0;
      foreach ($semestres as $materias) {
        foreach ($materias as $m) {
          if (($m['clave'] ?? '—') !== '—') { $totalReg++; }
        }
      }
      $totalPend = max(0, $totalPlan - $totalReg);
    ?>
    <?php if ($career): ?>
      <div class="mb-3">
        <span class="badge bg-success">Registradas: <?= (int)$totalReg ?>/<?= (int)$totalPlan ?></span>
        <span class="badge bg-warning text-dark ms-2">Pendientes: <?= (int)$totalPend ?></span>
      </div>
      <div class="print-area">
        <div class="print-header">
          <h2>Retícula Académica</h2>
          <div class="meta">Carrera: <?= htmlspecialchars($career['label'] ?? '') ?> • Plan: 8 semestres + Residencias</div>
        </div>
        <div class="grid">
        <?php foreach ($semestres as $num => $materias): ?>
          <div class="sem-card">
            <?php $reg = 0; foreach ($materias as $m) { if (($m['clave'] ?? '—') !== '—') { $reg++; } } ?>
            <div class="sem-header">Semestre <?= (int)$num ?> (<?= (int)$reg ?>/5)</div>
            <div class="sem-body">
              <ul class="mb-0">
                <?php foreach ($materias as $m): ?>
                  <?php $isPending = ($m['clave'] ?? '—') === '—'; ?>
                  <li class="<?= $isPending ? 'pending' : '' ?>">
                    <?= htmlspecialchars($m['nombre'] ?? '') ?>
                    <?php if (!$isPending): ?>
                      <span class="text-muted">(<?= htmlspecialchars($m['clave'] ?? '') ?>)</span>
                    <?php else: ?>
                      <span class="text-muted">(no registrada)</span>
                    <?php endif; ?>
                  </li>
                <?php endforeach; ?>
              </ul>
            </div>
          </div>
        <?php endforeach; ?>
        <div class="sem-card">
          <div class="sem-header">9º Semestre</div>
          <div class="sem-body">
            <ul class="mb-0">
              <li><strong>Residencias Profesionales</strong></li>
              <li class="text-muted">Requisitos: 10 niveles de Inglés y Servicio Social liberados.</li>
            </ul>
          </div>
        </div>
        </div>
      </div>
    <?php else: ?>
      <div class="alert alert-warning">No se pudo determinar la carrera del alumno. Asegúrate de que la matrícula tenga un prefijo válido (S, I, C, M, Q, E, A) y que existan materias en el catálogo.</div>
    <?php endif; ?>
  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>