<h3 class="section-title">Cardex</h3>
<table class="table">
  <thead>
    <tr><th>Materia</th><th>Semestre</th><th>Final</th><th>Estatus</th></tr>
  </thead>
  <tbody>
    <?php foreach($rows as $r): ?>
      <tr>
        <td><?= htmlspecialchars($r['materia']) ?></td>
        <td><?= (int)$r['semestre'] ?></td>
        <td><?= $r['final'] !== null ? number_format((float)$r['final'],2) : '-' ?></td>
        <td>
          <?php if($r['estatus']==='Aprobada'): ?>
            <span class="badge green">Aprobada</span>
          <?php elseif($r['estatus']==='Cursando'): ?>
            <span class="badge blue">Cursando</span>
          <?php else: ?>
            <span class="badge gray">Pendiente</span>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>