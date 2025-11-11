document.addEventListener('DOMContentLoaded',()=>{
  const forms = document.querySelectorAll('form[data-validate]');

  function clearErrors(form){
    form.querySelectorAll('.field-error').forEach(el=>el.remove());
    form.querySelectorAll('input, select, textarea').forEach(el=>{ el.style.borderColor=''; el.style.outline=''; });
  }
  function markInvalid(input, message){
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
  function notify(message){
    if(typeof window.showToast === 'function'){
      window.showToast(String(message||'Error de validación'),'error');
    } else {
      // Fallback simple
      alert(String(message||'Error de validación'));
    }
  }
  function clearClientFormErrorBanner(form){
    try{
      const banner = form.querySelector('.form-error-banner');
      if(banner) banner.remove();
    }catch{}
  }
  function showClientFormErrorBanner(form, messages){
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

  forms.forEach(f=>{
    f.addEventListener('submit',(e)=>{
      clearErrors(f);
      const entity = (f.querySelector('input[name="entity"]')?.value||'').toLowerCase();
      const minPass = parseInt(f.dataset.minPass||'8',10);
      const pass = f.querySelector('input[type="password"]');
      const errors = [];

      // Reglas comunes
      if(pass && pass.value.length < minPass){
        errors.push({field: pass.name||'password', message: 'La contraseña debe tener al menos '+minPass+' caracteres'});
      }
      const matricula = f.querySelector('input[name="matricula"]');
      if(matricula && !/^\d{9}$/.test(matricula.value.trim())){
        errors.push({field:'matricula', message:'La matrícula debe ser de 9 dígitos'});
      }

      // Reglas por entidad (consistentes con backend)
      if(entity==='alumnos'){
        const carrera = f.querySelector('input[name="carrera_id"]');
        if(carrera && Number(carrera.value)<=0){ errors.push({field:'carrera_id', message:'Carrera inválida'}); }
        const semestre = f.querySelector('input[name="semestre_actual"]');
        if(semestre && (Number(semestre.value)<1 || Number(semestre.value)>9)){
          errors.push({field:'semestre_actual', message:'Semestre debe ser entre 1 y 9'});
        }
      } else if(entity==='profesores'){
        const usuario = f.querySelector('input[name="usuario"]');
        if(usuario && usuario.value.trim()===''){
          errors.push({field:'usuario', message:'Usuario requerido'});
        }
      } else if(entity==='materias'){
        const nombre = f.querySelector('input[name="nombre"]');
        if(nombre && nombre.value.trim()===''){ errors.push({field:'nombre', message:'Nombre requerido'}); }
        const carrera = f.querySelector('input[name="carrera_id"]');
        if(carrera && Number(carrera.value)<=0){ errors.push({field:'carrera_id', message:'Carrera inválida'}); }
        const unidades = f.querySelector('input[name="unidades"]');
        if(unidades && (Number(unidades.value)<3 || Number(unidades.value)>11)){
          errors.push({field:'unidades', message:'Unidades debe ser entre 3 y 11'});
        }
        const semestre = f.querySelector('input[name="semestre"]');
        if(semestre && (Number(semestre.value)<1 || Number(semestre.value)>9)){
          errors.push({field:'semestre', message:'Semestre debe ser entre 1 y 9'});
        }
      } else if(entity==='grupos'){
        const materia = f.querySelector('input[name="materia_id"]');
        if(materia && Number(materia.value)<=0){ errors.push({field:'materia_id', message:'Materia inválida'}); }
        const profesor = f.querySelector('input[name="profesor_id"]');
        if(profesor && Number(profesor.value)<=0){ errors.push({field:'profesor_id', message:'Profesor inválido'}); }
        const clave = f.querySelector('input[name="clave"]');
        if(clave && clave.value.trim()===''){ errors.push({field:'clave', message:'Clave requerida'}); }
      } else if(entity==='periodos'){
        const nombre = f.querySelector('input[name="nombre"]');
        if(nombre && nombre.value.trim()===''){ errors.push({field:'nombre', message:'Nombre requerido'}); }
      } else if(entity==='carreras'){
        const nombre = f.querySelector('input[name="nombre"]');
        if(nombre && nombre.value.trim()===''){ errors.push({field:'nombre', message:'Nombre requerido'}); }
      }

      if(errors.length>0){
        e.preventDefault();
        // Marcar y notificar el primer error
        errors.forEach(err=>{
          const input = f.querySelector(`[name="${err.field}"]`);
          markInvalid(input, err.message);
        });
        const isEdit = !!f.closest('#modal');
        if(isEdit){
          clearClientFormErrorBanner(f);
          showClientFormErrorBanner(f, errors.map(e=>e.message));
        }
        const summary = errors.map(e=>e.message).join('; ');
        notify(summary || 'Revisa los campos inválidos');
      }
    });
  });
});