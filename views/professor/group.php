<h3 class="section-title">Alumnos del grupo</h3>
<form method="post" action="<?= \Core\Url::route('professor/grades/update') ?>">
  <input type="hidden" name="csrf_token" value="<?= \Core\Security::csrfToken() ?>" />
  <input type="hidden" name="grupo_id" value="<?= (int)$grupoId ?>" />
  <label>Unidades (3 a 11):</label>
  <input class="input" type="number" name="unidades" min="3" max="11" value="5" />
<table class="table mt-12">
    <thead>
      <tr>
        <th>Matrícula</th><th>Alumno</th>
        <?php for($u=1;$u<=5;$u++): ?><th>U<?= $u ?></th><?php endfor; ?>
      </tr>
    </thead>
    <tbody>
      <?php foreach($alumnos as $a): ?>
      <tr>
        <td><?= htmlspecialchars($a['matricula']) ?></td>
        <td><?= htmlspecialchars($a['nombre'].' '.$a['apellido']) ?></td>
        <?php for($u=1;$u<=5;$u++): ?>
          <td><input class="input" type="number" step="0.01" name="grades[<?= (int)$a['inscripcion_id'] ?>][<?= $u ?>]" /></td>
        <?php endfor; ?>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <button class="btn" type="submit"><i class="fa fa-floppy-disk"></i> Guardar</button>
</form>