<h3 class="section-title">Retícula (9 semestres)</h3>
<div class="grid cols-3">
  <?php for($s=1;$s<=9;$s++): ?>
  <div class="card">
    <h3>Semestre <?= $s ?></h3>
    <ul>
      <?php foreach($materias as $m): if((int)$m['semestre']!==$s) continue; $status='gray'; if(in_array($m['id'],$aprob)) $status='green'; elseif(in_array($m['id'],$cursa)) $status='blue'; ?>
        <li><span class="badge <?= $status ?>">&nbsp;</span> <?= htmlspecialchars($m['nombre']) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
  <?php endfor; ?>
</div>