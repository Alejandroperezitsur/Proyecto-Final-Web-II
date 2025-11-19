<?php
$role = $_SESSION['role'] ?? '';
$csrf = $_SESSION['csrf_token'] ?? '';
$base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
ob_start();
?>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Reportes Avanzados</h3>
    <a href="<?php echo $base; ?>/dashboard" class="btn btn-outline-secondary">Volver</a>
  </div>

  <div class="card mb-4">
    <div class="card-body">
      <form method="get" action="<?php echo $base; ?>/reports" class="row g-2" id="filtersForm">
        <div class="col-md-3">
          <label class="form-label">Ciclo</label>
          <select class="form-select" name="ciclo" id="sel-ciclo">
            <option value="">Todos</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Grupo</label>
          <select class="form-select" name="grupo_id" id="sel-grupo">
            <option value="">Todos</option>
          </select>
        </div>
        <?php if ($role === 'admin'): ?>
        <div class="col-md-3">
          <label class="form-label">Profesor</label>
          <select class="form-select" name="profesor_id" id="sel-prof">
            <option value="">Todos</option>
          </select>
        </div>
        <?php endif; ?>
        <div class="col-md-3 align-self-end d-grid">
          <button class="btn btn-primary" type="submit"><i class="fa-solid fa-filter me-1"></i> Aplicar filtros</button>
        </div>
      </form>
    </div>
  </div>

  <div class="d-flex justify-content-end mb-3 gap-2">
    <form method="post" action="<?php echo $base; ?>/reports/export/csv">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
      <input type="hidden" name="ciclo" value="<?= htmlspecialchars($_GET['ciclo'] ?? '') ?>">
      <input type="hidden" name="grupo_id" value="<?= htmlspecialchars($_GET['grupo_id'] ?? '') ?>">
      <?php if ($role === 'admin'): ?>
        <input type="hidden" name="profesor_id" value="<?= htmlspecialchars($_GET['profesor_id'] ?? '') ?>">
      <?php endif; ?>
      <button class="btn btn-outline-primary"><i class="fa-solid fa-file-csv me-1"></i> Exportar CSV</button>
    </form>
    <form method="post" action="<?php echo $base; ?>/reports/export/pdf" target="_blank">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
      <input type="hidden" name="ciclo" value="<?= htmlspecialchars($_GET['ciclo'] ?? '') ?>">
      <input type="hidden" name="grupo_id" value="<?= htmlspecialchars($_GET['grupo_id'] ?? '') ?>">
      <?php if ($role === 'admin'): ?>
        <input type="hidden" name="profesor_id" value="<?= htmlspecialchars($_GET['profesor_id'] ?? '') ?>">
      <?php endif; ?>
      <button class="btn btn-outline-secondary"><i class="fa-solid fa-file-pdf me-1"></i> Exportar PDF</button>
    </form>
    <form method="post" action="<?php echo $base; ?>/reports/export/zip">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
      <input type="hidden" name="ciclo" value="<?= htmlspecialchars($_GET['ciclo'] ?? '') ?>">
      <input type="hidden" name="grupo_id" value="<?= htmlspecialchars($_GET['grupo_id'] ?? '') ?>">
      <?php if ($role === 'admin'): ?>
        <input type="hidden" name="profesor_id" value="<?= htmlspecialchars($_GET['profesor_id'] ?? '') ?>">
      <?php endif; ?>
      <button class="btn btn-outline-dark"><i class="fa-solid fa-file-zipper me-1"></i> Exportar ZIP</button>
    </form>
    <form method="post" action="<?php echo $base; ?>/reports/export/xlsx">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
      <input type="hidden" name="ciclo" value="<?= htmlspecialchars($_GET['ciclo'] ?? '') ?>">
      <input type="hidden" name="grupo_id" value="<?= htmlspecialchars($_GET['grupo_id'] ?? '') ?>">
      <?php if ($role === 'admin'): ?>
        <input type="hidden" name="profesor_id" value="<?= htmlspecialchars($_GET['profesor_id'] ?? '') ?>">
      <?php endif; ?>
      <button class="btn btn-outline-success"><i class="fa-solid fa-file-excel me-1"></i> Exportar Excel</button>
    </form>
  </div>

  <div class="row g-3">
    <div class="col-md-4">
      <div class="card h-100">
        <div class="card-body">
          <h5 class="card-title">Resumen</h5>
          <div id="summaryBox" class="text-muted">Cargando…</div>
        </div>
      </div>
    </div>
    <div class="col-md-8">
      <div class="card h-100">
        <div class="card-body">
          <h5 class="card-title">Estadísticas</h5>
          <canvas id="chartStats" height="160"></canvas>
          <hr>
          <h6 class="mt-3">Reprobados por Materia (%)</h6>
          <canvas id="chartFail" height="140"></canvas>
          <hr>
          <div class="row g-3 mt-3">
            <div class="col-md-6">
              <h6>Top 5 grupos por promedio</h6>
              <div class="table-responsive">
                <table class="table table-sm">
                  <thead><tr><th>Ciclo</th><th>Materia</th><th>Grupo</th><th class="text-end">Promedio</th></tr></thead>
                  <tbody id="tbody-top-prom"></tbody>
                </table>
              </div>
            </div>
            <div class="col-md-6">
              <h6>Top 5 grupos por % reprobados</h6>
              <div class="table-responsive">
                <table class="table table-sm">
                  <thead><tr><th>Ciclo</th><th>Materia</th><th>Grupo</th><th class="text-end">% Reprobados</th></tr></thead>
                  <tbody id="tbody-top-fail"></tbody>
                </table>
              </div>
            </div>
          </div>
          <hr>
          <div class="row g-3 mt-3">
            <div class="col-md-6">
              <h6>Top 5 alumnos por promedio</h6>
              <div class="table-responsive">
                <table class="table table-sm">
                  <thead><tr><th>Matrícula</th><th>Alumno</th><th class="text-end">Promedio</th></tr></thead>
                  <tbody id="tbody-top-alum"></tbody>
                </table>
              </div>
            </div>
            <div class="col-md-6">
              <h6>Alumnos con riesgo (final < 60)</h6>
              <div class="table-responsive">
                <table class="table table-sm">
                  <thead><tr><th>Ciclo</th><th>Materia</th><th>Grupo</th><th>Alumno</th><th class="text-end">Final</th></tr></thead>
                  <tbody id="tbody-risk"></tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const params = new URLSearchParams(window.location.search);
function updateSummary() {
  const ciclo = document.getElementById('sel-ciclo')?.value || '';
  const prof = document.getElementById('sel-prof')?.value || '';
  const qs = new URLSearchParams();
  if (ciclo) qs.set('ciclo', ciclo);
  if (prof) qs.set('profesor_id', prof);
  const resumenUrl = '<?php echo $base; ?>/reports/summary' + (qs.toString() ? ('?' + qs.toString()) : '');
  fetch(resumenUrl).then(r => r.json()).then(j => {
    const box = document.getElementById('summaryBox');
    if (!j.ok) { box.textContent = j.message || 'Error'; return; }
    const prom = Number(j.data.promedio ?? 0);
    const promCls = isNaN(prom) ? 'text-muted' : (prom >= 70 ? 'text-success' : 'text-danger');
    box.innerHTML = `
    <div class="row g-2">
      <div class="col-md-6">
        <div><strong>Promedio:</strong> <span class="${promCls}">${isNaN(prom) ? '—' : prom}</span></div>
        <div><strong>Total con final:</strong> ${j.data.total_con_final ?? 0}</div>
      </div>
      <div class="col-md-6">
        <div><strong>Aprobadas:</strong> ${j.data.aprobadas ?? 0}</div>
        <div><strong>Pendientes:</strong> ${j.data.pendientes ?? 0}</div>
      </div>
      <div class="col-12"><strong>Reprobados:</strong> ${j.data.reprobados ?? 0} (${j.data.porcentaje_reprobados ?? 0}%)</div>
    </div>`;
  });
}

function updateChart() {
  let chartUrl = '<?php echo $base; ?>/api/charts/promedios-ciclo';
  <?php if ($role === 'profesor'): ?>chartUrl = '<?php echo $base; ?>/api/charts/desempeño-grupo';<?php endif; ?>
  const qs = new URLSearchParams();
  const c = document.getElementById('sel-ciclo')?.value || '';
  const g = document.getElementById('sel-grupo')?.value || '';
  if (c) qs.set('ciclo', c);
  if (g) qs.set('grupo_id', g);
  chartUrl += (qs.toString() ? ('?' + qs.toString()) : '');
  fetch(chartUrl).then(r => r.json()).then(j => {
    if (!j.ok) return;
    const ctx = document.getElementById('chartStats');
    const isLine = (chartUrl.includes('promedios-ciclo'));
    const vals = (j.data.data || []).map(Number);
    const bgColors = vals.map(v => (isNaN(v) ? 'rgba(108,117,125,0.3)' : (v >= 70 ? 'rgba(25,135,84,0.4)' : 'rgba(220,53,69,0.4)')));
    const borderColors = vals.map(v => (isNaN(v) ? '#6c757d' : (v >= 70 ? '#198754' : '#dc3545')));
    const config = {
      type: isLine ? 'line' : 'bar',
      data: {
        labels: j.data.labels,
        datasets: [{
          label: isLine ? 'Promedio por ciclo' : 'Promedio por grupo',
          data: vals,
          borderColor: isLine ? borderColors : borderColors,
          backgroundColor: isLine ? bgColors : bgColors,
          pointBackgroundColor: isLine ? borderColors : undefined
        }]
      },
      options: { responsive: true, plugins: { legend: { display: true }, tooltip: { enabled: true } } }
    };
    new Chart(ctx, config);
  });

  // Fail chart
  let failUrl = '<?php echo $base; ?>/api/charts/reprobados';
  failUrl += (qs.toString() ? ('?' + qs.toString()) : '');
  fetch(failUrl).then(r => r.json()).then(j => {
    if (!j.ok) return;
    const ctxF = document.getElementById('chartFail');
    const vals = (j.data.data || []).map(Number);
    const bg = vals.map(v => (isNaN(v) ? 'rgba(108,117,125,0.3)' : (v >= 30 ? 'rgba(220,53,69,0.4)' : 'rgba(25,135,84,0.4)')));
    const border = vals.map(v => (isNaN(v) ? '#6c757d' : (v >= 30 ? '#dc3545' : '#198754')));
    new Chart(ctxF, {
      type: 'bar',
      data: { labels: j.data.labels, datasets: [{ label: '% Reprobados', data: vals, backgroundColor: bg, borderColor: border, borderWidth: 1 }] },
      options: { responsive: true, plugins: { legend: { display: true } } }
    });
  });

  // Tops
  const qs2 = new URLSearchParams();
  if (c) qs2.set('ciclo', c);
  const pEl = document.getElementById('sel-prof');
  const p = pEl ? (pEl.value || '') : '';
  if (p) qs2.set('profesor_id', p);
  if (g) qs2.set('grupo_id', g);
  fetch('<?php echo $base; ?>/reports/tops' + (qs2.toString() ? ('?' + qs2.toString()) : ''))
    .then(r => r.json()).then(j => {
      if (!j.ok) return;
      const prom = j.data.top_promedios || [];
      const fail = j.data.top_reprobados || [];
      const talum = j.data.top_alumnos || [];
      const riesgo = j.data.alumnos_riesgo || [];
      const tp = document.getElementById('tbody-top-prom');
      const tf = document.getElementById('tbody-top-fail');
      const ta = document.getElementById('tbody-top-alum');
      const tr = document.getElementById('tbody-risk');
      tp.innerHTML = prom.length ? prom.map(x => `<tr><td>${x.ciclo}</td><td>${x.materia}</td><td>${x.grupo}</td><td class="text-end">${Number(x.promedio||0).toFixed(2)}</td></tr>`).join('') : '<tr><td colspan="4" class="text-muted">Sin datos</td></tr>';
      tf.innerHTML = fail.length ? fail.map(x => `<tr><td>${x.ciclo}</td><td>${x.materia}</td><td>${x.grupo}</td><td class="text-end">${Number(x.porcentaje||0).toFixed(2)}%</td></tr>`).join('') : '<tr><td colspan="4" class="text-muted">Sin datos</td></tr>';
      ta.innerHTML = talum.length ? talum.map(x => `<tr><td>${x.matricula}</td><td>${x.alumno}</td><td class="text-end">${Number(x.promedio||0).toFixed(2)}</td></tr>`).join('') : '<tr><td colspan="3" class="text-muted">Sin datos</td></tr>';
      tr.innerHTML = riesgo.length ? riesgo.map(x => `<tr><td>${x.ciclo}</td><td>${x.materia}</td><td>${x.grupo}</td><td>${x.alumno}</td><td class="text-end">${Number(x.final||0).toFixed(2)}</td></tr>`).join('') : '<tr><td colspan="5" class="text-muted">Sin datos</td></tr>';
    });
}

// Cargar combos
document.addEventListener('DOMContentLoaded', async () => {
  const selCiclo = document.getElementById('sel-ciclo');
  const selGrupo = document.getElementById('sel-grupo');
  const selProf = document.getElementById('sel-prof');
  try {
    const [cyclesRes, groupsRes, profsRes] = await Promise.all([
      fetch('<?php echo $base; ?>/api/catalogs/cycles'),
      fetch('<?php echo $base; ?>' + (<?php echo json_encode($role==='admin'); ?> ? '/api/catalogs/groups_all' : '/api/catalogs/groups')),
      <?php if ($role === 'admin'): ?>fetch('<?php echo $base; ?>/api/catalogs/professors')<?php else: ?>Promise.resolve({ok:true,json:async()=>[]})<?php endif; ?>
    ]);
    const cycles = cyclesRes.ok ? await cyclesRes.json() : [];
    const groups = groupsRes.ok ? await groupsRes.json() : [];
    const profs = profsRes.ok ? await profsRes.json() : [];

    const selectedCiclo = params.get('ciclo') || '';
    const selectedGrupo = params.get('grupo_id') || '';
    const selectedProf = params.get('profesor_id') || '';

    selCiclo.innerHTML = '<option value="">Todos</option>' + cycles.map(c => `<option value="${c}">${c}</option>`).join('');
    if (selectedCiclo) selCiclo.value = selectedCiclo;

    function refreshGroupsOptions() {
      const c = selCiclo.value || '';
      const p = selProf ? (selProf.value || '') : '';
      const filtered = groups.filter(g => {
        const okC = c ? (String(g.ciclo) === c) : true;
        const okP = p ? (String(g.profesor_id || '') === String(p)) : true;
        return okC && okP;
      });
      selGrupo.innerHTML = '<option value="">Todos</option>' + filtered.map(g => `<option value="${g.id}">${g.ciclo} — ${g.materia} / ${g.nombre}</option>`).join('');
    }
    refreshGroupsOptions();
    if (selectedGrupo) selGrupo.value = selectedGrupo;

    if (selProf) {
      selProf.innerHTML = '<option value="">Todos</option>' + profs.map(p => `<option value="${p.id}">${p.nombre}${p.email?(' ('+p.email+')'):''}</option>`).join('');
      if (selectedProf) selProf.value = selectedProf;
    }

    selCiclo.addEventListener('change', () => { refreshGroupsOptions(); updateSummary(); updateChart(); });
    if (selProf) selProf.addEventListener('change', () => { refreshGroupsOptions(); updateSummary(); updateChart(); });
    selGrupo.addEventListener('change', () => { updateSummary(); updateChart(); });
    updateSummary();
    updateChart();
  } catch (e) {
    console.warn('Error cargando combos', e);
  }
});

// Export respetando selects actuales
document.querySelectorAll('form[action$="/reports/export/csv"], form[action$="/reports/export/pdf"], form[action$="/reports/export/zip"], form[action$="/reports/export/xlsx"]').forEach(f => {
  f.addEventListener('submit', (ev) => {
    const c = document.getElementById('sel-ciclo')?.value || '';
    const g = document.getElementById('sel-grupo')?.value || '';
    const pEl = document.getElementById('sel-prof');
    const p = pEl ? (pEl.value || '') : '';
    const hc = f.querySelector('input[name="ciclo"]'); if (hc) hc.value = c;
    const hg = f.querySelector('input[name="grupo_id"]'); if (hg) hg.value = g;
    const hp = f.querySelector('input[name="profesor_id"]'); if (hp) hp.value = p;
  });
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>
