<?php /** @var bool $reinscripcion_activa */ ?>
<div class="container">
  <div class="card">
    <h3>Configuración del sistema</h3>
    <p class="text-muted">Ajustes generales de SICEnet.</p>
    <div class="row mb-12">
      <div class="flex-1">
        <h4>Reinscripción</h4>
        <p>Estado actual: <span class="badge <?= $reinscripcion_activa ? 'badge-activo' : 'badge-inactivo' ?>"><?= $reinscripcion_activa ? 'Activa' : 'Inactiva' ?></span></p>
        <p class="text-muted">Controla si los alumnos pueden realizar su reinscripción.</p>
        <a class="btn" href="<?= \Core\Url::route('admin/toggleReinscripcion') ?>"><i class="fa fa-rotate"></i> Activar / Desactivar</a>
      </div>
    </div>
  </div>

  <div class="grid cols-3 mt-16">
    <div class="card">
      <h3>Exportaciones</h3>
      <p class="text-muted">Descarga información útil para auditorías.</p>
      <p>
        <a class="export-btn" href="<?= \Core\Url::route('admin/export/pdf', ['mode'=>'dashboard']) ?>"><i class="fa fa-file-pdf"></i> Exportar Dashboard (PDF)</a>
      </p>
      <p>
        <a class="export-btn" href="<?= \Core\Url::route('admin/export/excel', ['mode'=>'dashboard']) ?>"><i class="fa fa-file-excel"></i> Exportar Dashboard (Excel)</a>
      </p>
    </div>

    <div class="card">
      <h3>Atajos a CRUDs</h3>
      <p class="text-muted">Gestiona datos principales del sistema.</p>
      <p><a class="btn" href="<?= \Core\Url::route('admin/crud', ['entity'=>'periodos']) ?>"><i class="fa fa-calendar"></i> Períodos</a></p>
      <p><a class="btn" href="<?= \Core\Url::route('admin/crud', ['entity'=>'carreras']) ?>"><i class="fa fa-graduation-cap"></i> Carreras</a></p>
      <p><a class="btn" href="<?= \Core\Url::route('admin/crud', ['entity'=>'materias']) ?>"><i class="fa fa-book"></i> Materias</a></p>
      <p><a class="btn" href="<?= \Core\Url::route('admin/crud', ['entity'=>'grupos']) ?>"><i class="fa fa-people-group"></i> Grupos</a></p>
      <p><a class="btn" href="<?= \Core\Url::route('admin/crud', ['entity'=>'alumnos']) ?>"><i class="fa fa-user-graduate"></i> Alumnos</a></p>
      <p><a class="btn" href="<?= \Core\Url::route('admin/crud', ['entity'=>'profesores']) ?>"><i class="fa fa-chalkboard-teacher"></i> Profesores</a></p>
    </div>

    <div class="card">
      <h3>Acerca de</h3>
      <p class="text-muted">Versión y enlaces útiles.</p>
      <p class="text-small text-muted">Consulta el README para ayuda y notas.</p>
    </div>
  </div>
</div>