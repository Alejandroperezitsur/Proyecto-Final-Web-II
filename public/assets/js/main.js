document.addEventListener('DOMContentLoaded', () => {
  // Marcar activo en el sidebar según la URL
  const links = document.querySelectorAll('.app-sidebar .menu-section a');
  const path = location.pathname.replace(/\\/g, '/');
  links.forEach(a => {
    const href = a.getAttribute('href');
    if (href && path.endsWith(href)) {
      a.classList.add('active');
    }
    // Insertar icono si existe atributo data-icon (Bootstrap Icons)
    const icon = a.getAttribute('data-icon');
    if (icon) {
      const i = document.createElement('i');
      i.className = `bi ${icon} menu-icon`;
      a.prepend(i);
    }
  });

  // Exportación CSV/PDF
  document.querySelectorAll('[data-export="csv"]').forEach(btn => {
    btn.addEventListener('click', () => {
      const target = btn.getAttribute('data-target');
      const table = document.querySelector(target);
      if (!table) return;
      const csv = tableToCSV(table);
      const filename = btn.getAttribute('data-filename') || 'export.csv';
      downloadCSV(csv, filename);
    });
  });

  document.querySelectorAll('[data-export="pdf"]').forEach(btn => {
    btn.addEventListener('click', () => {
      // Usa impresión del navegador (permitiendo guardar como PDF)
      window.print();
    });
  });

  // Toggle de tema oscuro/claro
  const applyTheme = (theme) => {
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem('sicenet-theme', theme);
  };
  let saved = localStorage.getItem('sicenet-theme') || 'dark';
  applyTheme(saved);
  const nav = document.querySelector('nav.navbar .container-fluid');
  const existingToggle = document.getElementById('themeToggle') || document.getElementById('theme-toggle');
  const updateIconEl = (el) => {
    if (!el) return;
    const icon = el.querySelector('i');
    if (icon) {
      icon.className = saved === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
    } else {
      el.innerHTML = saved === 'dark' ? '<i class="bi bi-sun-fill"></i>' : '<i class="bi bi-moon-fill"></i>';
    }
  };
  if (existingToggle) {
    updateIconEl(existingToggle);
    existingToggle.addEventListener('click', () => {
      const next = (localStorage.getItem('sicenet-theme') || 'dark') === 'dark' ? 'light' : 'dark';
      applyTheme(next);
      saved = next;
      updateIconEl(existingToggle);
    });
  } else if (nav) {
    const btn = document.createElement('button');
    btn.className = 'btn btn-sm btn-outline-secondary ms-2';
    btn.type = 'button';
    btn.title = 'Cambiar tema';
    updateIconEl(btn);
    btn.addEventListener('click', () => {
      const next = (localStorage.getItem('sicenet-theme') || 'dark') === 'dark' ? 'light' : 'dark';
      applyTheme(next);
      saved = next;
      updateIconEl(btn);
    });
    nav.appendChild(btn);
  }
});

// -------- Tabla: ordenamiento sencillo por encabezado --------
function enableTableSort(table) {
  if (!table) return;
  const headers = Array.from(table.querySelectorAll('thead th'));
  headers.forEach((th, index) => {
    th.style.cursor = 'pointer';
    th.addEventListener('click', () => {
      const tbody = table.querySelector('tbody');
      const rows = Array.from(tbody.querySelectorAll('tr'));
      const asc = th.dataset.sort !== 'asc'; // toggle
      headers.forEach(h => delete h.dataset.sort);
      th.dataset.sort = asc ? 'asc' : 'desc';
      rows.sort((a, b) => {
        const av = (a.querySelectorAll('td')[index]?.textContent || '').trim();
        const bv = (b.querySelectorAll('td')[index]?.textContent || '').trim();
        const an = parseFloat(av); const bn = parseFloat(bv);
        const cmp = (!isNaN(an) && !isNaN(bn)) ? (an - bn) : av.localeCompare(bv);
        return asc ? cmp : -cmp;
      });
      rows.forEach(r => tbody.appendChild(r));
    });
  });
}

// -------- Filtros: helpers para guardar/cargar --------
function saveFilters(elements, storageKey, label) {
  const data = {};
  elements.forEach(el => data[el.id || el.name] = (el.type === 'checkbox' ? el.checked : el.value));
  const payload = { label: label || new Date().toLocaleString(), data };
  const list = JSON.parse(localStorage.getItem(storageKey) || '[]');
  list.push(payload);
  localStorage.setItem(storageKey, JSON.stringify(list));
}

function loadFilterList(storageKey) {
  try { return JSON.parse(localStorage.getItem(storageKey) || '[]'); } catch { return []; }
}

function applyFilters(elements, record) {
  elements.forEach(el => {
    const key = el.id || el.name;
    if (record.data.hasOwnProperty(key)) {
      if (el.type === 'checkbox') el.checked = !!record.data[key];
      else el.value = record.data[key];
      el.dispatchEvent(new Event('input'));
      el.dispatchEvent(new Event('change'));
    }
  });
}

// -------- Utilidad: valores únicos por columna --------
function collectUniqueColumnValues(table, colIndex) {
  const set = new Set();
  Array.from(table.querySelectorAll('tbody tr')).forEach(row => {
    const txt = (row.querySelectorAll('td')[colIndex]?.textContent || '').trim();
    if (txt) set.add(txt);
  });
  return Array.from(set.values()).sort();
}

window.TableUtils = { enableTableSort, saveFilters, loadFilterList, applyFilters, collectUniqueColumnValues };

function tableToCSV(table) {
  const rows = Array.from(table.querySelectorAll('tr'));
  const visibleRows = rows.filter(row => {
    const style = window.getComputedStyle(row);
    return style.display !== 'none' && !row.classList.contains('d-none');
  });
  return visibleRows.map(row => {
    const cells = Array.from(row.querySelectorAll('th,td'));
    return cells.map(cell => escapeCSV(cell.textContent.trim())).join(',');
  }).join('\n');
}

function escapeCSV(text) {
  const needsQuotes = /[",\n]/.test(text);
  let escaped = text.replace(/"/g, '""');
  return needsQuotes ? `"${escaped}"` : escaped;
}

function downloadCSV(csv, filename) {
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = filename;
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  URL.revokeObjectURL(url);
}