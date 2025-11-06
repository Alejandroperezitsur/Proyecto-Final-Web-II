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
});