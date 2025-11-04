<?php
require_once __DIR__ . '/../../app/init.php';
// Sidebar de Operaciones Académicas y navegación principal
$role = $_SESSION['user_role'] ?? null;
?>
<aside class="app-sidebar">
  <div class="brand">
    <a href="dashboard.php" class="brand-link">SICEnet · ITSUR</a>
    <div class="small text-muted">Sistema Integral de Control Escolar</div>
  </div>
  <nav class="menu">
    <?php if ($role === 'alumno'): ?>
      <div class="menu-section">
        <div class="menu-section-title">Operaciones Académicas</div>
        <ul>
          <li><a href="kardex.php" data-icon="bi-journal-text">Kardex</a></li>
          <li><a href="mi_carga.php" data-icon="bi-list-check">Mi Carga Académica</a></li>
          <li><a href="reticula.php" data-icon="bi-diagram-3">Retícula Académica</a></li>
          <li><a href="reinscripcion.php" data-icon="bi-arrow-repeat">Reinscripción</a></li>
        </ul>
      </div>
    <?php else: ?>
      <div class="menu-section">
        <div class="menu-section-title">Operaciones Académicas</div>
        <ul>
          <li><a href="kardex.php" data-icon="bi-journal-text">Kardex</a></li>
          <li><a href="mi_carga.php" data-icon="bi-list-check">Mi Carga Académica</a></li>
          <li><a href="reticula.php" data-icon="bi-diagram-3">Retícula Académica</a></li>
          <li><a href="reinscripcion.php" data-icon="bi-arrow-repeat">Reinscripción</a></li>
        </ul>
      </div>
      <div class="menu-section">
        <div class="menu-section-title">Gestión</div>
        <ul>
          <!-- Entrada 'Grupos' removida del sidebar para evitar duplicados en la navegación superior -->
          <li><a href="alumnos.php" data-icon="bi-people">Alumnos</a></li>
          <li><a href="profesores.php" data-icon="bi-person-badge">Profesores</a></li>
          <li><a href="materias.php" data-icon="bi-book">Materias</a></li>
        </ul>
      </div>
    <?php endif; ?>
    <div class="menu-section">
      <ul>
        <li><a href="logout.php" data-icon="bi-box-arrow-right">Cerrar Sesión</a></li>
      </ul>
    </div>
  </nav>
</aside>