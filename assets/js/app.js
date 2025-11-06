document.addEventListener('DOMContentLoaded',()=>{
  const forms = document.querySelectorAll('form[data-validate]');
  forms.forEach(f=>{
    f.addEventListener('submit',(e)=>{
      const minPass = parseInt(f.dataset.minPass||'8',10);
      const pass = f.querySelector('input[type="password"]');
      if(pass && pass.value.length < minPass){
        e.preventDefault();
        alert('La contraseña debe tener al menos '+minPass+' caracteres');
      }
      const matricula = f.querySelector('input[name="matricula"]');
      if(matricula && !/^\d{9}$/.test(matricula.value.trim())){
        e.preventDefault();
        alert('La matrícula debe ser de 9 dígitos');
      }
    });
  });
});