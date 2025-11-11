<h3 class="section-title">CRUD: <?= htmlspecialchars($entity) ?></h3>
<div class="card">
  <form method="get" action="<?= \Core\Url::route('admin/crud') ?>">
    <input type="hidden" name="entity" value="<?= htmlspecialchars($entity) ?>" />
    <div class="row">
      <input class="input" name="q" value="<?= htmlspecialchars($q ?? '') ?>" placeholder="Buscar (nombre, matrícula/usuario)" />
      <select class="input" name="per_page">
        <?php foreach([10,20,30,50] as $opt): ?>
          <option value="<?= $opt ?>" <?= isset($per_page) && $per_page==$opt?'selected':'' ?>><?= $opt ?> por página</option>
        <?php endforeach; ?>
      </select>
      <button class="btn" type="submit"><i class="fa fa-magnifying-glass"></i> Filtrar</button>
      <?php $qv = $q ?? ''; ?>
      <a class="export-btn" href="<?= \Core\Url::route('admin/export/pdf', ['entity'=>htmlspecialchars($entity), 'q'=>$qv]) ?>"><i class="fa fa-file-pdf"></i> Exportar PDF</a>
      <a class="export-btn" href="<?= \Core\Url::route('admin/export/excel', ['entity'=>htmlspecialchars($entity), 'q'=>$qv]) ?>"><i class="fa fa-file-excel"></i> Exportar Excel</a>
    </div>
  </form>
</div>
<div class="card">
<form method="post" action="<?= \Core\Url::route('admin/crud/save') ?>" data-validate>
    <input type="hidden" name="entity" value="<?= htmlspecialchars($entity) ?>" />
    <input type="hidden" name="csrf_token" value="<?= \Core\Security::csrfToken() ?>" />
    <?php if($entity==='carreras'): ?>
      <div class="row"><input class="input" name="nombre" placeholder="Nombre de carrera" required /></div>
    <?php elseif($entity==='periodos'): ?>
      <div class="row">
        <input class="input" name="nombre" placeholder="Nombre del período (ej. 2025-1)" required />
      </div>
      <div class="row">
        <label><input type="checkbox" name="activo" value="1" /> Activar este período</label>
      </div>
    <?php elseif($entity==='materias'): ?>
      <div class="row">
        <input class="input" name="nombre" placeholder="Nombre de materia" required />
        <input class="input" type="number" name="semestre" min="1" max="9" value="1" />
        <input class="input" type="number" name="unidades" min="3" max="11" value="5" />
      </div>
      <div class="row">
        <input class="input" type="number" name="carrera_id" placeholder="ID Carrera" required />
      </div>
    <?php elseif($entity==='grupos'): ?>
      <div class="row">
        <input class="input" type="number" name="materia_id" placeholder="ID Materia" required />
        <input class="input" type="number" name="profesor_id" placeholder="ID Profesor" required />
        <input class="input" name="clave" placeholder="Clave de grupo" required />
        <input class="input" name="salon" placeholder="Salón" />
      </div>
    <?php elseif($entity==='alumnos'): ?>
      <div class="row">
        <input class="input" name="matricula" placeholder="Matrícula (9 dígitos)" maxlength="9" required />
        <input class="input" name="nombre" placeholder="Nombre" required />
        <input class="input" name="apellido" placeholder="Apellido" required />
      </div>
      <div class="row">
        <input class="input" type="number" name="carrera_id" placeholder="ID Carrera" required />
        <input class="input" type="number" name="semestre_actual" min="1" max="9" value="1" />
        <input class="input" type="password" name="password" placeholder="Contraseña (>=8)" required />
      </div>
    <?php elseif($entity==='profesores'): ?>
      <div class="row">
        <input class="input" name="usuario" placeholder="Usuario" required />
        <input class="input" name="nombre" placeholder="Nombre" required />
        <input class="input" name="apellido" placeholder="Apellido" required />
      </div>
      <div class="row">
        <input class="input" type="number" name="carrera_id" placeholder="ID Carrera" required />
        <input class="input" type="password" name="password" placeholder="Contraseña (>=8)" required />
      </div>
    <?php endif; ?>
    <input type="hidden" name="ajax" value="1" />
    <button class="btn" type="submit"><i class="fa fa-plus"></i> Crear</button>
  </form>
</div>
<div class="card mt-12">
  <table class="table">
    <thead>
      <tr>
        <?php if(!empty($rows)){ foreach(array_keys($rows[0]) as $col){ echo '<th>'.htmlspecialchars($col).'</th>'; } } ?>
        <th>Acciones</th>
      </tr>
    </thead>
    <tbody id="crud-body">
      <?php foreach($rows as $r): ?>
        <tr data-id="<?= (int)$r['id'] ?>" class="<?= ($entity==='periodos' && (int)($r['activo'] ?? 0)===1) ? 'active-period' : '' ?>">
          <?php foreach($r as $k=>$v): ?><td><?= htmlspecialchars((string)$v) ?></td><?php endforeach; ?>
          <td>
            <?php if($entity==='periodos'): ?>
              <?php if((int)($r['activo'] ?? 0)===1): ?>
                <span class="badge badge-activo">Activo</span>
              <?php else: ?>
                <span class="badge badge-inactivo">Inactivo</span>
                <form method="post" action="<?= \Core\Url::route('admin/crud/update') ?>" class="inline">
                  <input type="hidden" name="entity" value="periodos" />
                  <input type="hidden" name="id" value="<?= (int)$r['id'] ?>" />
                  <input type="hidden" name="nombre" value="<?= htmlspecialchars((string)$r['nombre']) ?>" />
                  <input type="hidden" name="activo" value="1" />
                  <input type="hidden" name="csrf_token" value="<?= \Core\Security::csrfToken() ?>" />
                  <button class="btn" type="submit"><i class="fa fa-toggle-on"></i> Activar</button>
                </form>
              <?php endif; ?>
            <?php endif; ?>
            <?php if($entity==='alumnos' || $entity==='profesores'): ?>
              <form method="post" action="<?= \Core\Url::route('admin/password/reset') ?>" class="inline js-reset-pass">
                <input type="hidden" name="entity" value="<?= htmlspecialchars($entity) ?>" />
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>" />
                <input type="hidden" name="csrf_token" value="<?= \Core\Security::csrfToken() ?>" />
                <input type="hidden" name="ajax" value="1" />
                <button class="btn warning" type="submit" title="Genera una contraseña temporal y la muestra al admin"><i class="fa fa-key"></i> Resetear contraseña</button>
              </form>
            <?php endif; ?>
            <button class="btn secondary" type="button" data-edit='<?= json_encode($r, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT) ?>' data-entity="<?= htmlspecialchars($entity) ?>"><i class="fa fa-pen"></i> Editar</button>
            <form method="post" action="<?= \Core\Url::route('admin/crud/delete') ?>" class="js-confirm-delete inline">
              <input type="hidden" name="entity" value="<?= htmlspecialchars($entity) ?>" />
              <input type="hidden" name="id" value="<?= (int)$r['id'] ?>" />
              <input type="hidden" name="csrf_token" value="<?= \Core\Security::csrfToken() ?>" />
              <input type="hidden" name="ajax" value="1" />
              <button class="btn danger" type="submit"><i class="fa fa-trash"></i> Eliminar</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<div class="mt-8 text-muted">Total: <?= (int)($total ?? count($rows)) ?> • Página <?= (int)($page ?? 1) ?></div>
<div class="row mt-8">
    <?php $p = (int)($page ?? 1); $per=(int)($per_page ?? 10); $qv = $q ?? ''; ?>
    <?php if($p>1): ?>
      <a class="btn" href="<?= \Core\Url::route('admin/crud', ['entity'=>htmlspecialchars($entity), 'page'=>$p-1, 'per_page'=>$per, 'q'=>$qv]) ?>">« Anterior</a>
    <?php endif; ?>
    <a class="btn" href="<?= \Core\Url::route('admin/crud', ['entity'=>htmlspecialchars($entity), 'page'=>$p+1, 'per_page'=>$per, 'q'=>$qv]) ?>">Siguiente »</a>
  </div>
</div>

<!-- Modal de edición -->
<div id="modal" class="modal" hidden>
  <div class="modal-content card">
    <h3>Editar <?= htmlspecialchars($entity) ?></h3>
<form method="post" action="<?= \Core\Url::route('admin/crud/update') ?>" data-validate>
      <input type="hidden" name="entity" value="<?= htmlspecialchars($entity) ?>" />
      <input type="hidden" name="id" />
      <input type="hidden" name="csrf_token" value="<?= \Core\Security::csrfToken() ?>" />
      <div id="modal-fields"></div>
<div class="row mt-12">
        <input type="hidden" name="ajax" value="1" />
        <button class="btn" type="submit"><i class="fa fa-floppy-disk"></i> Guardar cambios</button>
        <button class="btn secondary" type="button" onclick="closeModal()">Cancelar</button>
      </div>
    </form>
  </div>
</div>

<script>
function confirmDelete(form){
  return window.confirm('¿Eliminar este registro? Esta acción es irreversible.');
}
const fieldSets={
  carreras:[{name:'nombre',label:'Nombre',type:'text',required:true}],
  periodos:[{name:'nombre',label:'Nombre',type:'text',required:true},{name:'activo',label:'Activo (0/1)',type:'number'}],
  materias:[{name:'nombre',label:'Nombre',type:'text',required:true},{name:'semestre',label:'Semestre',type:'number'},{name:'unidades',label:'Unidades',type:'number'},{name:'carrera_id',label:'ID Carrera',type:'number'}],
  grupos:[{name:'carrera_id',label:'ID Carrera',type:'number'},{name:'materia_id',label:'ID Materia',type:'number'},{name:'profesor_id',label:'ID Profesor',type:'number'},{name:'clave',label:'Clave',type:'text',required:true},{name:'salon',label:'Salón',type:'text'}],
  alumnos:[{name:'matricula',label:'Matrícula',type:'text',required:true},{name:'nombre',label:'Nombre',type:'text',required:true},{name:'apellido',label:'Apellido',type:'text',required:true},{name:'carrera_id',label:'ID Carrera',type:'number',required:true},{name:'semestre_actual',label:'Semestre',type:'number'}],
  profesores:[{name:'usuario',label:'Usuario',type:'text',required:true},{name:'nombre',label:'Nombre',type:'text',required:true},{name:'apellido',label:'Apellido',type:'text',required:true},{name:'carrera_id',label:'ID Carrera',type:'number',required:true}],
};
function openEditModal(entity, data){
  const modal=document.getElementById('modal');
  modal.hidden=false;
  const fields=document.getElementById('modal-fields');
  fields.innerHTML='';
  modal.querySelector('input[name="id"]').value=data.id;
  for(const f of (fieldSets[entity]||[])){
    const div=document.createElement('div');
    div.className='row';
    const input=document.createElement('input');
    input.className='input';
    input.name=f.name; input.type=f.type||'text'; input.required=!!f.required;
    if(f.type==='checkbox'){
      input.value='1';
      input.checked = !!(data[f.name] && Number(data[f.name])===1);
    } else {
      input.value=data[f.name]??'';
    }
    const label=document.createElement('label');
    label.textContent=f.label;
    div.appendChild(label); div.appendChild(input);
    fields.appendChild(div);
  }
  if(entity==='periodos' && Number(data['activo']||0)===1){
    const note=document.createElement('div');
    note.className='text-muted';
    note.textContent='Nota: este período ya está ACTIVO.';
    fields.appendChild(note);
  }
}
function closeModal(){document.getElementById('modal').hidden=true;}
// Helpers de validación visual en el modal
function clearFieldErrors(form){
  try{
    form.querySelectorAll('.field-error').forEach(el=>el.remove());
    form.querySelectorAll('input, select, textarea').forEach(el=>{ el.style.borderColor=''; el.style.outline=''; });
  }catch{}
}
function clearModalErrorBanner(form){
  try{
    const banner = form.querySelector('.form-error-banner');
    if(banner) banner.remove();
  }catch{}
}
function mapErrorToField(entity, message){
  const msg = String(message||'').toLowerCase();
  switch(entity){
    case 'alumnos':
      if(msg.includes('matrícula')) return 'matricula';
      if(msg.includes('carrera')) return 'carrera_id';
      if(msg.includes('semestre')) return 'semestre_actual';
      break;
    case 'profesores':
      if(msg.includes('usuario')) return 'usuario';
      if(msg.includes('carrera')) return 'carrera_id';
      break;
    case 'materias':
      if(msg.includes('nombre')) return 'nombre';
      if(msg.includes('carrera')) return 'carrera_id';
      if(msg.includes('unidades')) return 'unidades';
      if(msg.includes('semestre')) return 'semestre';
      break;
    case 'grupos':
      if(msg.includes('materia')) return 'materia_id';
      if(msg.includes('profesor')) return 'profesor_id';
      if(msg.includes('clave')) return 'clave';
      if(msg.includes('salón')||msg.includes('salon')) return 'salon';
      break;
    case 'periodos':
      if(msg.includes('nombre')) return 'nombre';
      break;
    case 'carreras':
      if(msg.includes('nombre')) return 'nombre';
      break;
  }
  return '';
}
function showFieldError(form, fieldName, message){
  if(!fieldName) return;
  const input = form.querySelector(`[name="${fieldName}"]`);
  if(!input) return;
  input.style.borderColor = '#b71c1c';
  input.style.outline = '1px solid #b71c1c';
  const hint = document.createElement('div');
  hint.className = 'field-error';
  hint.textContent = String(message||'Campo inválido');
  hint.style.color = '#b71c1c';
  hint.style.fontSize = '12px';
  hint.style.marginTop = '4px';
  input.insertAdjacentElement('afterend', hint);
}
function showModalErrorBanner(form, messages){
  const banner = document.createElement('div');
  banner.className = 'form-error-banner';
  banner.style.background = '#b71c1c';
  banner.style.color = '#fff';
  banner.style.padding = '10px 12px';
  banner.style.borderRadius = '6px';
  banner.style.marginBottom = '10px';
  banner.style.display = 'block';
  const text = Array.isArray(messages) ? messages.map(m=>String(m)).join(' • ') : String(messages||'Error en el formulario');
  banner.textContent = text;
  form.prepend(banner);
}
function bindCrudEvents(){
  // Delegación de eventos para Editar (más robusto tras refrescos)
  const body = document.getElementById('crud-body');
  if(body && !body.dataset.boundEdit){
    body.addEventListener('click',(e)=>{
      const btn = e.target.closest('button[data-edit]');
      if(!btn) return;
      const entity=btn.dataset.entity;
      try{
        const data=JSON.parse(btn.getAttribute('data-edit'));
        openEditModal(entity,data);
      }catch(err){
        console.error('Error al abrir edición:', err);
        showToast('No se pudo abrir edición', 'error');
      }
    });
    body.dataset.boundEdit = '1';
  }
  document.querySelectorAll('form.js-confirm-delete').forEach(f=>{
    f.addEventListener('submit', (e)=>{
      const entity = f.querySelector('input[name="entity"]').value;
      let msg = '¿Eliminar este registro? Esta acción es irreversible.';
      if(entity==='periodos'){
        msg = '¿Eliminar este período? Si está ACTIVO o tiene inscripciones, no se eliminará.';
      }
      if(!window.confirm(msg)){
        e.preventDefault();
      }
    });
  });
  document.querySelectorAll('form.js-reset-pass').forEach(f=>{
    f.addEventListener('submit', (e)=>{
      const entity = f.querySelector('input[name="entity"]').value;
      const label = entity==='alumnos' ? 'alumno' : 'profesor';
      if(!window.confirm(`¿Resetear la contraseña de este ${label}? Se generará una contraseña temporal visible solo para el admin.`)){
        e.preventDefault();
      }
    });
  });
  // Envío AJAX y refresco parcial
  document.querySelectorAll('form').forEach(f=>{
    if(!f.querySelector('input[name="ajax"]')) return;
    f.addEventListener('submit', async (e)=>{
      e.preventDefault();
      const btn = f.querySelector('button[type="submit"]');
      if(btn){ btn.disabled = true; }
      try {
        const resp = await fetch(f.action, { method: (f.method || 'POST').toUpperCase(), body: new FormData(f) });
        const ct = resp.headers.get('content-type') || '';
        if(ct.includes('application/json')){
          const data = await resp.json();
          if(data && data.temp_password){
            showToastWithActions(`Contraseña temporal: ${data.temp_password}`, ''+data.temp_password, 'success');
            return; // no refresco necesario para reset
          }
          if(data && data.ok){
            const msgMap = {guardado:'Guardado', actualizado:'Actualizado', eliminado:'Eliminado', password_reseteada:'Contraseña reseteada'};
            const label = msgMap[data.msg] || 'Operación completada';
            showToast(label, 'success');
            await refreshCrudList();
            return;
          }
          // Si llega JSON sin ok, mostrar detalle si existe y resaltar campo
          const errMsg = (data && data.error) ? String(data.error) : 'Operación no completada';
          showToast(errMsg, 'error');
          const isEdit = !!f.closest('#modal');
          if(isEdit){
            clearFieldErrors(f);
            clearModalErrorBanner(f);
            showModalErrorBanner(f, [errMsg]);
            const entity = f.querySelector('input[name="entity"]').value || '';
            const field = mapErrorToField(entity, errMsg);
            showFieldError(f, field, errMsg);
          }
          return;
        }
        // Respuesta no JSON: refrescar y mostrar mensaje genérico
        await refreshCrudList();
        showToast('Cambios aplicados', 'info');
      } catch (err) {
        console.error(err);
        showToast('Error al procesar la solicitud', 'error');
      } finally {
        if(btn){ btn.disabled = false; }
      }
    });
  });
}
function getToastContainer(){
  let container = document.getElementById('toast-container');
  if(!container){
    container = document.createElement('div');
    container.id = 'toast-container';
    container.style.position='fixed'; container.style.bottom='20px'; container.style.right='20px';
    container.style.zIndex='9999'; container.style.display='flex'; container.style.flexDirection='column';
    container.style.gap='8px'; container.style.alignItems='flex-end';
    document.body.appendChild(container);
  }
  return container;
}

function createToastElement(message, type){
  const colors = { success:'#2e7d32', error:'#b71c1c', info:'#333', warning:'#ed6c02' };
  const icons = { success:'✓', error:'!', info:'ℹ️', warning:'⚠️' };
  const bg = colors[type] || colors.info;
  const icon = icons[type] || icons.info;
  const toast = document.createElement('div');
  toast.style.background=bg; toast.style.color='#fff'; toast.style.padding='10px 14px'; toast.style.borderRadius='8px';
  toast.style.boxShadow='0 2px 12px rgba(0,0,0,0.35)'; toast.style.opacity='0'; toast.style.transition='opacity 200ms ease-in';
  toast.style.maxWidth='80vw'; toast.style.display='flex'; toast.style.alignItems='center';
  const iconEl = document.createElement('span'); iconEl.textContent=icon; iconEl.style.marginRight='10px'; iconEl.style.fontWeight='bold';
  const msg = document.createElement('span'); msg.textContent = message; msg.style.marginRight='12px';
  toast.appendChild(iconEl); toast.appendChild(msg);
  return toast;
}

function showToast(message, type='info'){
  const container = getToastContainer();
  const toast = createToastElement(message, type);
  container.appendChild(toast);
  requestAnimationFrame(()=>{ toast.style.opacity='1'; });
  setTimeout(()=>{ toast.style.opacity='0'; setTimeout(()=>{ toast.remove(); }, 220); }, 4000);
}

function showToastWithActions(message, copyText, type='info'){
  const container = getToastContainer();
  const toast = createToastElement(message, type);
  const copyBtn = document.createElement('button');
  copyBtn.textContent='Copiar'; copyBtn.style.marginRight='8px'; copyBtn.style.background='#1976d2';
  copyBtn.style.color='#fff'; copyBtn.style.border='none'; copyBtn.style.padding='6px 10px'; copyBtn.style.borderRadius='6px';
  const closeBtn = document.createElement('button');
  closeBtn.textContent='Cerrar'; closeBtn.style.background='#555'; closeBtn.style.color='#fff'; closeBtn.style.border='none'; closeBtn.style.padding='6px 10px'; closeBtn.style.borderRadius='6px';
  toast.appendChild(copyBtn); toast.appendChild(closeBtn);
  container.appendChild(toast);
  requestAnimationFrame(()=>{ toast.style.opacity='1'; });
  copyBtn.addEventListener('click', async ()=>{
    try { await navigator.clipboard.writeText(copyText||''); copyBtn.textContent='Copiado'; copyBtn.style.background='#2e7d32'; }
    catch { copyBtn.textContent='No copiado'; copyBtn.style.background='#b71c1c'; }
  });
  const dismiss = ()=>{ toast.style.opacity='0'; setTimeout(()=>{ toast.remove(); }, 220); };
  closeBtn.addEventListener('click', dismiss);
  setTimeout(dismiss, 10000);
}
async function refreshCrudList(){
  const url = window.location.href;
  const resp = await fetch(url, { method:'GET', credentials:'same-origin' });
  const html = await resp.text();
  const dom = new DOMParser().parseFromString(html,'text/html');
  const newBody = dom.querySelector('#crud-body');
  const body = document.querySelector('#crud-body');
  if(newBody && body){ body.innerHTML = newBody.innerHTML; }
  // Actualizar total/paginación
  const newTotals = dom.querySelector('.mt-8.text-muted');
  const totals = document.querySelector('.mt-8.text-muted');
  if(newTotals && totals){ totals.innerHTML = newTotals.innerHTML; }
  // Re-bind de eventos
  bindCrudEvents();
}
// Inicializar
bindCrudEvents();
</script>