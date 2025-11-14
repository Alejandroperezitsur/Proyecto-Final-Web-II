<?php
$csrf = $_SESSION['csrf_token'] ?? '';
// Expect $professors
?>
<?php $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/'); ob_start(); ?>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Profesores</h3>
    <a href="<?php echo $base; ?>/dashboard" class="btn btn-outline-secondary">Volver</a>
  </div>

  <div class="card mb-4">
    <div class="card-body">
      <form method="post" action="<?php echo $base; ?>/professors/create" class="row g-2 needs-validation" novalidate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
        <div class="col-md-4">
          <input class="form-control" name="nombre" placeholder="Nombre" required>
          <div class="invalid-feedback">Ingresa el nombre del profesor.</div>
        </div>
        <div class="col-md-4">
          <input class="form-control" type="email" name="email" placeholder="Email" required>
          <div class="invalid-feedback">Ingresa un correo válido.</div>
        </div>
        <div class="col-md-4">
          <button class="btn btn-primary" type="submit"><i class="fa-solid fa-user-plus me-1"></i> Agregar</button>
        </div>
      </form>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table table-striped">
      <thead><tr><th>ID</th><th>Nombre</th><th>Email</th><th>Activo</th><th class="text-end">Acciones</th></tr></thead>
      <tbody>
        <?php foreach ($professors as $p): ?>
        <tr>
          <td><?= htmlspecialchars($p['id']) ?></td>
          <td><?= htmlspecialchars($p['nombre']) ?></td>
          <td><?= htmlspecialchars($p['email']) ?></td>
          <td><?= (int)$p['activo'] === 1 ? 'Sí' : 'No' ?></td>
          <td class="text-end">
            <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#delModal<?= (int)$p['id'] ?>">
              <i class="fa-solid fa-trash"></i>
            </button>
            <div class="modal fade" id="delModal<?= (int)$p['id'] ?>" tabindex="-1" aria-hidden="true">
              <div class="modal-dialog">
                <div class="modal-content">
                  <div class="modal-header"><h5 class="modal-title">Eliminar profesor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body">¿Confirmas eliminar a <?= htmlspecialchars($p['nombre']) ?>?</div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form method="post" action="<?php echo $base; ?>/professors/delete">
                      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                      <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                      <button type="submit" class="btn btn-danger">Eliminar</button>
                    </form>
                  </div>
                </div>
              </div>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
// Bootstrap client-side validation
(() => {
  const form = document.querySelector('form[action="<?php echo $base; ?>/professors/create"]');
  form.addEventListener('submit', (event) => {
    if (!form.checkValidity()) {
      event.preventDefault();
      event.stopPropagation();
    }
    form.classList.add('was-validated');
  }, false);
})();
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>
