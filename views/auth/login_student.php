<div class="card">
  <h3>Login Alumno</h3>
<form method="post" action="<?= \Core\Url::route('login/student') ?>" data-validate data-min-pass="8">
    <input type="hidden" name="csrf_token" value="<?= \Core\Security::csrfToken() ?>" />
    <div class="row">
      <div class="flex-1">
        <label>Matrícula</label>
        <input class="input" type="text" name="matricula" placeholder="XXXXXXXXX" maxlength="9" required />
      </div>
      <div class="flex-1">
        <label>Contraseña</label>
        <input class="input" type="password" name="password" minlength="8" required />
      </div>
    </div>
    <div class="mt-12">
      <button class="btn" type="submit"><i class="fa fa-right-to-bracket"></i> Ingresar</button>
    </div>
  </form>
</div>