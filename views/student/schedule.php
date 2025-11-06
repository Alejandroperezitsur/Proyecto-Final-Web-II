<h3 class="section-title">Horario escolar</h3>
<table class="table">
  <thead>
    <tr><th>Materia</th><th>Grupo</th><th>Salón</th><th>Día</th><th>Hora</th></tr>
  </thead>
  <tbody>
    <?php foreach($rows as $r): ?>
      <tr>
        <td><?= htmlspecialchars($r['materia']) ?></td>
        <td><?= htmlspecialchars($r['grupo']) ?></td>
        <td><?= htmlspecialchars($r['salon']) ?></td>
        <td><?= htmlspecialchars($r['dia']) ?></td>
        <td><?= htmlspecialchars($r['hora_inicio']) ?> - <?= htmlspecialchars($r['hora_fin']) ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>