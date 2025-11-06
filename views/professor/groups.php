<h3 class="section-title">Mis grupos</h3>
<table class="table">
  <thead>
    <tr><th>Clave</th><th>Materia</th><th>Salón</th><th>Acciones</th></tr>
  </thead>
  <tbody>
    <?php foreach($rows as $r): ?>
      <tr>
        <td><?= htmlspecialchars($r['clave']) ?></td>
        <td><?= htmlspecialchars($r['materia']) ?></td>
        <td><?= htmlspecialchars($r['salon']) ?></td>
        <td><a class="btn" href="<?= \Core\Url::route('professor/group', ['id'=>(int)$r['id']]) ?>"><i class="fa fa-users"></i> Alumnos</a></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>