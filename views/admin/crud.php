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
    <tbody>
      <?php foreach($rows as $r): ?>
        <tr class="<?= ($entity==='periodos' && (int)($r['activo'] ?? 0)===1) ? 'active-period' : '' ?>">
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
            <button class="btn secondary" data-edit='<?= json_encode($r, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT) ?>' data-entity="<?= htmlspecialchars($entity) ?>"><i class="fa fa-pen"></i> Editar</button>
            <form method="post" action="<?= \Core\Url::route('admin/crud/delete') ?>" class="js-confirm-delete inline">
              <input type="hidden" name="entity" value="<?= htmlspecialchars($entity) ?>" />
              <input type="hidden" name="id" value="<?= (int)$r['id'] ?>" />
              <input type="hidden" name="csrf_token" value="<?= \Core\Security::csrfToken() ?>" />
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
document.querySelectorAll('button[data-edit]').forEach(btn=>{
  btn.addEventListener('click',()=>{
    const entity=btn.dataset.entity;
    const data=JSON.parse(btn.getAttribute('data-edit'));
    const modal=document.getElementById('modal');
    modal.hidden=false;
    const fields=document.getElementById('modal-fields');
    fields.innerHTML='';
    modal.querySelector('input[name="id"]').value=data.id;
    for(const f of fieldSets[entity]||[]){
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
    // Nota para períodos ya activos
    if(entity==='periodos' && Number(data['activo']||0)===1){
      const note=document.createElement('div');
      note.className='text-muted';
      note.textContent='Nota: este período ya está ACTIVO.';
      fields.appendChild(note);
    }
  });
});
function closeModal(){document.getElementById('modal').hidden=true;}
// Confirmación de eliminación; mensaje específico para períodos
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
</script>