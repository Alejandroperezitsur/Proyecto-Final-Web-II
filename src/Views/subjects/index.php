<?php
// Expect $subjects
$csrf = $_SESSION['csrf_token'] ?? '';
ob_start();
?>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Materias</h3>
    <a href="/dashboard" class="btn btn-outline-secondary">Volver</a>
  </div>

  <div class="card mb-4">
    <div class="card-body">
      <form method="post" action="/subjects/create" class="row g-2 needs-validation" novalidate id="subjectForm">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
        <div class="col-md-4">
          <input class="form-control" name="nombre" placeholder="Nombre" required>
          <div class="invalid-feedback">Ingresa el nombre de la materia.</div>
        </div>
        <div class="col-md-3">
          <input class="form-control" name="clave" placeholder="Clave" required>
          <div class="invalid-feedback">Ingresa la clave.</div>
        </div>
        <div class="col-md-3">
          <button class="btn btn-primary" type="submit"><i class="fa-solid fa-plus me-1"></i> Agregar</button>
        </div>
      </form>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table table-striped">
      <thead><tr><th>ID</th><th>Nombre</th><th>Clave</th><th class="text-end">Acciones</th></tr></thead>
      <tbody>
        <?php foreach ($subjects as $m): ?>
        <tr>
          <td><?= htmlspecialchars($m['id']) ?></td>
          <td><?= htmlspecialchars($m['nombre']) ?></td>
          <td><?= htmlspecialchars($m['clave']) ?></td>
          <td class="text-end">
            <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#delSubj<?= (int)$m['id'] ?>">
              <i class="fa-regular fa-trash-can"></i>
            </button>
            <div class="modal fade" id="delSubj<?= (int)$m['id'] ?>" tabindex="-1" aria-hidden="true">
              <div class="modal-dialog">
                <div class="modal-content">
                  <div class="modal-header"><h5 class="modal-title">Eliminar materia</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body">¿Confirmas eliminar "<?= htmlspecialchars($m['nombre']) ?>"?</div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form method="post" action="/subjects/delete">
                      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                      <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
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
  const form = document.getElementById('subjectForm');
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