<div class="grid cols-3">
  <div class="card">
    <h3><i class="fa fa-user-graduate"></i> Alumno</h3>
    <p>Accede con tu matrícula de 9 dígitos y contraseña.</p>
    <a class="btn" href="<?= \Core\Url::route('login/student') ?>">Entrar</a>
  </div>
  <div class="card">
    <h3><i class="fa fa-chalkboard-teacher"></i> Profesor</h3>
    <p>Gestiona tus grupos y captura calificaciones por unidades.</p>
    <a class="btn" href="<?= \Core\Url::route('login/professor') ?>">Entrar</a>
  </div>
  <div class="card">
    <h3><i class="fa fa-user-shield"></i> Administrador</h3>
    <p>CRUD completo de entidades y estadísticas institucionales.</p>
    <a class="btn" href="<?= \Core\Url::route('login/admin') ?>">Entrar</a>
  </div>
</div>