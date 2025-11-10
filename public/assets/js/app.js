document.addEventListener('DOMContentLoaded',()=>{
  // Sidebar toggle
  function setCollapsedByViewport(){
    if(window.innerWidth < 900){ document.body.classList.add('sidebar-collapsed'); }
  }
  setCollapsedByViewport();
  window.addEventListener('resize',()=>{ setCollapsedByViewport(); });
  const toggleBtn = document.getElementById('sidebar-toggle');
  const toggleBtnTop = document.getElementById('sidebar-toggle-top');
  function toggleSidebar(){ document.body.classList.toggle('sidebar-collapsed'); }
  if(toggleBtn) toggleBtn.addEventListener('click',toggleSidebar);
  if(toggleBtnTop) toggleBtnTop.addEventListener('click',toggleSidebar);

  const forms = document.querySelectorAll('form[data-validate]');
  forms.forEach(f=>{
    f.addEventListener('submit',(e)=>{
      const minPass = parseInt(f.dataset.minPass||'8',10);
      const pass = f.querySelector('input[type="password"]');
      if(pass && pass.value.length < minPass){
        e.preventDefault();
        showToast('La contraseña debe tener al menos '+minPass+' caracteres','error');
      }
      const matricula = f.querySelector('input[name="matricula"]');
      if(matricula && !/^\d{9}$/.test(matricula.value.trim())){
        e.preventDefault();
        showToast('La matrícula debe ser de 9 dígitos','error');
      }
      // Campos requeridos
      const required = f.querySelectorAll('[required]');
      for(const el of required){
        if(!el.value || (el.type==='number' && el.value==='')){
          e.preventDefault();
          el.style.borderColor = '#e53935';
          showToast('Completa los campos requeridos','error');
          break;
        }
      }
    });
  });

  // Auto-ocultar mensajes del servidor
  document.querySelectorAll('.message').forEach(msg=>{
    setTimeout(()=>{msg.remove();}, 4000);
  });
  // Auto-ocultar toasts renderizados por el servidor
  document.querySelectorAll('.toast').forEach(t=>{
    setTimeout(()=>{t.remove();}, 3200);
  });

  // Modal helpers (cerrar con Escape y fondo)
  const modal = document.getElementById('modal');
  if(modal){
    modal.addEventListener('click',(e)=>{ if(e.target===modal) closeModal(); });
    document.addEventListener('keydown',(e)=>{ if(e.key==='Escape') closeModal(); });
  }

  // Toast container
  function ensureToastContainer(){
    let c = document.getElementById('toast-container');
    if(!c){ c = document.createElement('div'); c.id='toast-container'; document.body.appendChild(c); }
    return c;
  }
  window.showToast = function(msg,type='success'){
    const c = ensureToastContainer();
    const t = document.createElement('div');
    t.className = 'toast '+(type||'');
    t.textContent = msg;
    c.appendChild(t);
    setTimeout(()=>{ t.remove(); }, 3500);
  }

  // Confirm delete (Admin CRUD) without inline handlers
  document.querySelectorAll('form.js-confirm-delete').forEach((form)=>{
    form.addEventListener('submit',(e)=>{
      const ok = window.confirm('¿Seguro que deseas eliminar este registro?');
      if(!ok){ e.preventDefault(); }
    });
  });

  // Charts (Professor dashboard) moved from inline script
 const accent = '#2e7d32';
  const accent2 = '#2196f3';
  const text = '#f5f5f5';
  const grid = '#2a2a2a';
  if(window.Chart){
    const pgCanvas = document.getElementById('chartPromediosGrupo');
    if(pgCanvas){
      try{
        const pg = JSON.parse(pgCanvas.getAttribute('data-pg')||'[]');
        new Chart(pgCanvas,{
          type:'bar',
          data:{labels:pg.map(d=>d.label),datasets:[{label:'Promedio',data:pg.map(d=>d.value),backgroundColor:accent2}]},
          options:{responsive:true,plugins:{legend:{labels:{color:text}}},scales:{x:{ticks:{color:text},grid:{color:grid}},y:{ticks:{color:text},grid:{color:grid}}}}
        });
      }catch(err){ console.error('Error renderizando chartPromediosGrupo', err); }
    }

    const distCanvas = document.getElementById('chartDistribucion');
    if(distCanvas){
      try{
        const dist = JSON.parse(distCanvas.getAttribute('data-dist')||'{}');
        new Chart(distCanvas,{
          type:'doughnut',
          data:{labels:['Reprobados','Aprobados','Destacados'],datasets:[{data:[dist.reprobados||0,dist.aprobados||0,dist.destacados||0],backgroundColor:['#e53935',accent,'#43a047']}]},
          options:{responsive:true,plugins:{legend:{labels:{color:text}}}}
        });
      }catch(err){ console.error('Error renderizando chartDistribucion', err); }
    }

    const pCanvas = document.getElementById('profChart');
    if(pCanvas){
      try{
        const pgeneral = JSON.parse(pCanvas.getAttribute('data-promedio')||'0');
        const pgeneral10 = pgeneral > 10 ? pgeneral/10 : pgeneral;
        const avgColor = pgeneral10 >= 8.5 ? '#43a047' : (pgeneral10 >= 7 ? '#fbc02d' : '#e53935');
        new Chart(pCanvas,{
          type:'bar',
          data:{labels:['Promedio General'],datasets:[{label:'Calificación promedio',data:[pgeneral10],backgroundColor:avgColor}]},
          options:{
            scales:{y:{beginAtZero:true,max:10,ticks:{color:text},grid:{color:grid}},x:{ticks:{color:text},grid:{color:grid}}},
            plugins:{legend:{display:false},title:{display:true,text:'Promedio general de grupos',color:text},tooltip:{callbacks:{label:(ctx)=>`Promedio: ${ctx.raw}`}}},
            animation:{duration:1200,easing:'easeOutQuart'},
            responsive:true
          }
        });
      }catch(err){ console.error('Error renderizando profChart', err); }
    }

    const teacherCanvas = document.getElementById('teacherChart');
    if(teacherCanvas){
      try{
        const gpm = JSON.parse(teacherCanvas.getAttribute('data-gpm')||'[]');
        new Chart(teacherCanvas,{
          type:'bar',
          data:{labels:gpm.map(d=>d.label),datasets:[{label:'Grupos',data:gpm.map(d=>d.value),backgroundColor:accent}]},
          options:{responsive:true,plugins:{legend:{labels:{color:text}}},scales:{x:{ticks:{color:text},grid:{color:grid}},y:{ticks:{color:text},grid:{color:grid}}}}
        });
      }catch(err){ console.error('Error renderizando teacherChart', err); }
    }
  }

  // Optional: prefetch Retícula on Student dashboard
  const route = document.body.getAttribute('data-route') || '';
  if(route === 'student/dashboard'){
    try{ fetch('?route=student/reticula', { method:'GET', cache:'no-store' }).catch(()=>{}); }catch(_){ }
  }
});