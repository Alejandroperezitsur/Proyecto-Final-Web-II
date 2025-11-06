<div class="card">
  <h3>Login Administrador</h3>
<form method="post" action="?route=login/admin" data-validate data-min-pass="8">
    <input type="hidden" name="csrf_token" value="<?= \Core\Security::csrfToken() ?>" />
    <div class="row">
      <div style="flex:1">
        <label>Usuario</label>
        <input class="input" type="text" name="usuario" required />
      </div>
      <div style="flex:1">
        <label>Contraseña</label>
        <input class="input" type="password" name="password" minlength="8" required />
      </div>
    </div>
    <div style="margin-top:12px">
      <button class="btn" type="submit"><i class="fa fa-right-to-bracket"></i> Ingresar</button>
    </div>
  </form>
</div>