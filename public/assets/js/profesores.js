// Helpers for Profesor dashboard pages
export function esc(s){ return (s ?? '').toString().replace(/[&<>"]/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[m])); }

export async function fetchJSON(url){
  const r = await fetch(url); return await r.json();
}

export async function loadProfesorStats(containerSel){
  const el = document.querySelector(containerSel); if (!el) return;
  el.innerHTML = '<div class="text-muted">Cargando estadísticas…</div>';
  try {
    const json = await fetchJSON('/api/profesores/estadisticas');
    if (!json.success) { el.innerHTML = '<div class="text-danger">Error al cargar</div>'; return; }
    const s = json.data || {};
    el.innerHTML = `
      <div class="row g-3">
        <div class="col-6 col-xl-3"><div class="card"><div class="card-body"><div class="text-muted small">Grupos activos</div><div class="h4 mb-0">${s.grupos_activos ?? 0}</div></div></div></div>
        <div class="col-6 col-xl-3"><div class="card"><div class="card-body"><div class="text-muted small">Alumnos totales</div><div class="h4 mb-0">${s.alumnos_totales ?? 0}</div></div></div></div>
        <div class="col-6 col-xl-3"><div class="card"><div class="card-body"><div class="text-muted small">Evaluaciones pendientes</div><div class="h4 mb-0">${s.evaluaciones_pendientes ?? 0}</div></div></div></div>
        <div class="col-6 col-xl-3"><div class="card"><div class="card-body"><div class="text-muted small">Promedio general</div><div class="h4 mb-0">${(s.promedio_general ?? 0).toFixed ? s.promedio_general.toFixed(2) : s.promedio_general}</div></div></div></div>
      </div>`;
  } catch (e) { el.innerHTML = '<div class="text-danger">Error de red</div>'; }
}

export async function loadActiveGroups(containerSel){
  const el = document.querySelector(containerSel); if (!el) return;
  el.innerHTML = '<div class="text-muted">Cargando grupos activos…</div>';
  try {
    const json = await fetchJSON('/api/profesores/grupos_activos');
    if (!json.success) { el.innerHTML = '<div class="text-danger">Error al cargar</div>'; return; }
    const items = (json.data || []);
    if (!items.length) { el.innerHTML = '<div class="alert alert-info">No hay grupos activos. Contacta al administrador si crees que es un error.</div>'; return; }
    el.innerHTML = items.map(g => `
      <div class="col-12 col-md-6 col-xl-4 col-xxl-3">
        <div class="card h-100">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <h5 class="card-title mb-1">${esc(g.materia)} · ${esc(g.grupo)}</h5>
                <div class="text-muted small">Alumnos: ${g.alumnos ?? 0} · Prom: ${g.promedio ?? '-'}</div>
              </div>
              <span class="badge text-bg-secondary">#${g.id}</span>
            </div>
          </div>
        </div>
      </div>`).join('');
  } catch (e) { el.innerHTML = '<div class="text-danger">Error de red</div>'; }
}