<h3 class="section-title">Calificaciones actuales</h3>
<table class="table">
  <thead>
    <tr><th>Materia</th><th>Unidad</th><th>Calificación</th><th>2a Oportunidad</th></tr>
  </thead>
  <tbody>
    <?php foreach($rows as $r): ?>
      <tr>
        <td><?= htmlspecialchars($r['materia']) ?></td>
        <td><?= (int)$r['unidad'] ?></td>
        <td><?= number_format((float)$r['calificacion'],2) ?></td>
        <td><?= $r['segunda_oportunidad']!==null ? number_format((float)$r['segunda_oportunidad'],2) : '-' ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>