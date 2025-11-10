<?php
// $viewFile viene de Controller::render
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>SICEnet</title>
  <link rel="stylesheet" href="<?= \Core\Url::asset('css/style.css') ?>" />
  <link rel="stylesheet" href="<?= \Core\Url::asset('css/utilities.css') ?>" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
</head>
<body class="with-sidebar" data-route="<?= htmlspecialchars($route) ?>">
  <?php $route = $_GET['route'] ?? '/'; $role = $_SESSION['role'] ?? null; $entity = $_GET['entity'] ?? ''; ?>
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
      <div class="logo">IT</div>
      <div class="title">SICEnet</div>
      <button id="sidebar-toggle" class="hamburger" aria-label="Alternar menú"><i class="fa fa-bars"></i></button>
    </div>
    <nav class="menu">
      <?php if(!$role): ?>
        <a href="<?= \Core\Url::route('/') ?>" class="<?= ($route === '/') ? 'active' : '' ?>"><i class="fa fa-house"></i><span>Inicio</span></a>
        <a href="<?= \Core\Url::route('login/student') ?>" class="<?= ($route === 'login/student') ? 'active' : '' ?>"><i class="fa fa-user-graduate"></i><span>Alumno</span></a>
        <a href="<?= \Core\Url::route('login/professor') ?>" class="<?= ($route === 'login/professor') ? 'active' : '' ?>"><i class="fa fa-chalkboard-teacher"></i><span>Profesor</span></a>
        <a href="<?= \Core\Url::route('login/admin') ?>" class="<?= ($route === 'login/admin') ? 'active' : '' ?>"><i class="fa fa-user-shield"></i><span>Admin</span></a>
      <?php elseif($role==='admin'): ?>
        <?php $sidebarStats = \Controllers\AdminController::getSidebarStats(); ?>
        <a href="<?= \Core\Url::route('admin/dashboard') ?>" class="<?= str_starts_with($route,'admin/dashboard') || $route==='admin/stats' ? 'active' : '' ?>"><i class="fa fa-gauge"></i><span>Dashboard</span></a>
        <a href="<?= \Core\Url::route('admin/crud', ['entity'=>'periodos']) ?>" class="<?= ($route==='admin/crud' && $entity==='periodos') ? 'active' : '' ?>"><i class="fa fa-calendar"></i><span>Períodos</span><span class="menu-badge"><?= (int)($sidebarStats['periodos'] ?? 0) ?></span></a>
        <a href="<?= \Core\Url::route('admin/crud', ['entity'=>'alumnos']) ?>" class="<?= ($route==='admin/crud' && $entity==='alumnos') ? 'active' : '' ?>"><i class="fa fa-user-graduate"></i><span>Alumnos</span><span class="menu-badge"><?= (int)($sidebarStats['alumnos'] ?? 0) ?></span></a>
        <a href="<?= \Core\Url::route('admin/crud', ['entity'=>'profesores']) ?>" class="<?= ($route==='admin/crud' && $entity==='profesores') ? 'active' : '' ?>"><i class="fa fa-chalkboard-teacher"></i><span>Profesores</span><span class="menu-badge"><?= (int)($sidebarStats['profesores'] ?? 0) ?></span></a>
        <a href="<?= \Core\Url::route('admin/crud', ['entity'=>'carreras']) ?>" class="<?= ($route==='admin/crud' && $entity==='carreras') ? 'active' : '' ?>"><i class="fa fa-graduation-cap"></i><span>Carreras</span><span class="menu-badge"><?= (int)($sidebarStats['carreras'] ?? 0) ?></span></a>
        <a href="<?= \Core\Url::route('admin/crud', ['entity'=>'materias']) ?>" class="<?= ($route==='admin/crud' && $entity==='materias') ? 'active' : '' ?>"><i class="fa fa-book"></i><span>Materias</span><span class="menu-badge"><?= (int)($sidebarStats['materias'] ?? 0) ?></span></a>
        <a href="<?= \Core\Url::route('admin/crud', ['entity'=>'grupos']) ?>" class="<?= ($route==='admin/crud' && $entity==='grupos') ? 'active' : '' ?>"><i class="fa fa-people-group"></i><span>Grupos</span><span class="menu-badge"><?= (int)($sidebarStats['grupos'] ?? 0) ?></span></a>
        <a href="<?= \Core\Url::route('admin/stats') ?>" class="<?= ($route==='admin/stats') ? 'active' : '' ?>"><i class="fa fa-gear"></i><span>Configuración</span></a>
      <?php elseif($role==='professor'): ?>
        <a href="<?= \Core\Url::route('professor/dashboard') ?>" class="<?= ($route==='professor/dashboard') ? 'active' : '' ?>"><i class="fa fa-gauge"></i><span>Dashboard</span></a>
        <a href="<?= \Core\Url::route('professor/groups') ?>" class="<?= ($route==='professor/groups' || $route==='professor/group') ? 'active' : '' ?>"><i class="fa fa-people-group"></i><span>Mis grupos</span></a>
        <a href="<?= \Core\Url::route('professor/groups') ?>" class="<?= ($route==='professor/group') ? 'active' : '' ?>"><i class="fa fa-star"></i><span>Calificaciones</span></a>
      <?php elseif($role==='student'): ?>
        <a href="<?= \Core\Url::route('student/dashboard') ?>" class="<?= ($route==='student/dashboard') ? 'active' : '' ?>"><i class="fa fa-gauge"></i><span>Dashboard</span></a>
        <a href="<?= \Core\Url::route('student/cardex') ?>" class="<?= ($route==='student/cardex') ? 'active' : '' ?>"><i class="fa fa-list"></i><span>Cardex</span></a>
        <a href="<?= \Core\Url::route('student/grades') ?>" class="<?= ($route==='student/grades') ? 'active' : '' ?>"><i class="fa fa-star"></i><span>Calificaciones</span></a>
        <a href="<?= \Core\Url::route('student/schedule') ?>" class="<?= ($route==='student/schedule') ? 'active' : '' ?>"><i class="fa fa-calendar"></i><span>Horario</span></a>
        <a href="<?= \Core\Url::route('student/reticula') ?>" class="<?= ($route==='student/reticula') ? 'active' : '' ?>"><i class="fa fa-diagram-project"></i><span>Retícula</span></a>
        <a href="<?= \Core\Url::route('student/dashboard') ?>" class="<?= ($route==='student/dashboard') ? 'active' : '' ?>"><i class="fa fa-check"></i><span>Reinscripción</span></a>
      <?php endif; ?>
    </nav>
    <?php if($role): ?>
    <div class="sidebar-footer">
      <a href="<?= \Core\Url::route('logout') ?>" class="logout"><i class="fa fa-right-from-bracket"></i><span>Salir</span></a>
    </div>
    <?php endif; ?>
  </aside>
  <header class="header">
    <div class="brand">
      <div class="logo">IT</div>
      <div>
        <strong>SICEnet</strong>
        <div class="text-muted text-small">Sistema Integral de Control Escolar - ITSUR</div>
      </div>
    </div>
    <button id="sidebar-toggle-top" class="hamburger" aria-label="Alternar menú"><i class="fa fa-bars"></i></button>
  </header>
  <main class="container">
    <?php if(!empty($_SESSION['error'])): ?>
      <div class="message error"><i class="fa fa-triangle-exclamation"></i> <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>
    <?php if(!empty($_SESSION['success'])): ?>
      <div class="message success"><i class="fa fa-circle-check"></i> <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <?php include $viewFile; ?>
  </main>
  <footer class="footer">© ITSUR • SICEnet</footer>
  <?php
    $msg = $_GET['msg'] ?? null;
    if($msg){
      $type = 'success';
      $text = '';
      switch($msg){
        case 'reinscripcion_activada':
          $type = 'success';
          $text = 'Reinscripción ACTIVADA';
          break;
        case 'reinscripcion_desactivada':
          $type = 'error';
          $text = 'Reinscripción DESACTIVADA';
          break;
        case 'guardado':
          $type = 'success';
          $text = 'Cambios guardados';
          break;
        case 'actualizado':
          $type = 'success';
          $text = 'Registro actualizado';
          break;
        case 'eliminado':
          $type = 'error';
          $text = 'Registro eliminado';
          break;
      }
      if($text !== ''){
        echo '<div class="toast '.htmlspecialchars($type).'">'.htmlspecialchars($text).'</div>';
      }
    }
  ?>
  <script src="<?= \Core\Url::asset('js/app.js') ?>"></script>
</body>
</html>