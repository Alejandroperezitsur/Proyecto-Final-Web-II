<?php
$csrf = $_SESSION['csrf_token'] ?? '';
$base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
ob_start();
?>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Registrar Calificaciones</h3>
    <a href="<?php echo $base; ?>/dashboard" class="btn btn-outline-secondary">Volver</a>
  </div>

  <div class="card">
    <div class="card-body">
      <form id="grade-form" method="post" action="<?php echo $base; ?>/grades/create" novalidate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Grupo</label>
            <select name="grupo_id" id="grupo_id" class="form-select" required></select>
            <div class="form-text">Selecciona el grupo asignado.</div>
          </div>
          <div class="col-md-4">
            <label class="form-label">Alumno</label>
            <select name="alumno_id" id="alumno_id" class="form-select" required></select>
            <div class="form-text">Selecciona el alumno activo.</div>
          </div>
        <div class="col-md-4">
          <label class="form-label">Parcial 1</label>
          <input type="number" min="0" max="100" name="parcial1" class="form-control" placeholder="0-100">
        </div>
        <div class="col-md-4">
          <label class="form-label">Parcial 2</label>
          <input type="number" min="0" max="100" name="parcial2" class="form-control" placeholder="0-100">
        </div>
        <div class="col-md-4">
          <label class="form-label">Final</label>
          <input type="number" min="0" max="100" name="final" class="form-control" placeholder="0-100">
        </div>
        </div>

        <div class="mt-4 d-flex gap-2">
          <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#confirmModal"><i class="fa-solid fa-floppy-disk me-1"></i> Guardar</button>
          <a href="<?php echo $base; ?>/grades/bulk" class="btn btn-outline-info"><i class="fa-solid fa-file-csv me-1"></i> Carga Masiva</a>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal de confirmación -->
  <div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Confirmar registro</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          Se registrará la calificación seleccionada. ¿Deseas continuar?
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="button" id="confirmSubmit" class="btn btn-primary">Confirmar</button>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
(async function init() {
  const grupoSel = document.getElementById('grupo_id');
  const alumnoSel = document.getElementById('alumno_id');
  // Poblar grupos del profesor actual
  const gRes = await fetch('<?php echo $base; ?>/api/catalogs/groups');
  const grupos = await gRes.json();
  grupoSel.innerHTML = grupos.map(g => `<option value="${g.id}">${g.ciclo} · ${g.materia} · ${g.nombre}</option>`).join('');
  // Poblar alumnos activos
  const aRes = await fetch('<?php echo $base; ?>/api/catalogs/students');
  const alumnos = await aRes.json();
  alumnoSel.innerHTML = alumnos.map(a => `<option value="${a.id}">${a.matricula} · ${a.nombre}</option>`).join('');
})();

// Validación cliente ligera
document.getElementById('confirmSubmit').addEventListener('click', function() {
  const form = document.getElementById('grade-form');
  const required = ['grupo_id','alumno_id'];
  for (const name of required) {
    const el = form.querySelector(`[name="${name}"]`);
    if (!el.value) { el.classList.add('is-invalid'); return; }
    else { el.classList.remove('is-invalid'); }
  }
  const numeric = ['parcial1','parcial2','final'];
  for (const name of numeric) {
    const el = form.querySelector(`[name="${name}"]`);
    if (el.value !== '' && (isNaN(el.value) || el.value < 0 || el.value > 100)) {
      el.classList.add('is-invalid'); return;
    } else { el.classList.remove('is-invalid'); }
  }
  form.submit();
});

// Umbral visual (>=70 verde, <70 rojo)
(() => {
  const inputs = ['parcial1','parcial2','final'].map(n => document.querySelector(`[name="${n}"]`));
  const paint = (el) => {
    const v = el.value === '' ? null : Number(el.value);
    el.classList.remove('is-valid','is-invalid','text-success','text-danger');
    if (v === null || isNaN(v)) return;
    if (v >= 70) { el.classList.add('is-valid','text-success'); }
    else { el.classList.add('is-invalid','text-danger'); }
  };
  inputs.forEach(el => el && el.addEventListener('input', () => paint(el)));
})();
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>
