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
      const tsMode = (btn.getAttribute('data-timestamp') || '').toLowerCase();
      const finalName = tsMode ? withTimestamp(filename, tsMode) : filename;
      downloadCSV(csv, finalName);
    });
  });

  document.querySelectorAll('[data-export="pdf"]').forEach(btn => {
    btn.addEventListener('click', () => {
      const target = btn.getAttribute('data-target');
      if (target) {
        // Impresión específica de tabla
        printTable(target);
      } else {
        // Impresión general de la página
        window.print();
      }
    });
  });

  // Toggle de tema oscuro/claro
  const applyTheme = (theme) => {
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem('sicenet-theme', theme);
  };

  const getSystemPref = () => {
    try { return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'; } catch { return 'dark'; }
  };
  const getCurrentTheme = () => localStorage.getItem('sicenet-theme') || getSystemPref() || 'dark';
  let currentTheme = getCurrentTheme();
  applyTheme(currentTheme);
  
  const nav = document.querySelector('nav.navbar .container-fluid');
  const existingToggle = document.getElementById('themeToggle') || document.getElementById('theme-toggle');
  
  const updateIconEl = (el, theme) => {
    if (!el) return;
    const icon = el.querySelector('i');
    if (icon) {
      icon.className = theme === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
    } else {
      el.innerHTML = theme === 'dark' ? '<i class="bi bi-sun-fill"></i>' : '<i class="bi bi-moon-fill"></i>';
    }
  };
  
  if (existingToggle) {
    updateIconEl(existingToggle, currentTheme);
    existingToggle.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      const nextTheme = getCurrentTheme() === 'dark' ? 'light' : 'dark';
      applyTheme(nextTheme);
      currentTheme = nextTheme;
      updateIconEl(existingToggle, currentTheme);
    });
  } else if (nav) {
    const btn = document.createElement('button');
    btn.className = 'btn btn-sm btn-outline-secondary ms-2';
    btn.type = 'button';
    btn.title = 'Cambiar tema';
    updateIconEl(btn, currentTheme);
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      const nextTheme = getCurrentTheme() === 'dark' ? 'light' : 'dark';
      applyTheme(nextTheme);
      currentTheme = nextTheme;
      updateIconEl(btn, currentTheme);
    });
    nav.appendChild(btn);
  }

  // Autoactivar ordenamiento en tablas con clase .table-sort
  document.querySelectorAll('table.table-sort').forEach(tbl => enableTableSort(tbl));

  // Filtro rápido: inputs con data-quick-filter-for="#selector"
  document.querySelectorAll('[data-quick-filter-for]').forEach(input => {
    const sel = input.getAttribute('data-quick-filter-for');
    const table = document.querySelector(sel);
    if (!table) return;
    input.addEventListener('input', () => {
      const term = input.value.toLowerCase();
      const rows = Array.from(table.querySelectorAll('tbody tr'));
      let visibleCount = 0;
      rows.forEach(row => {
        const txt = row.textContent.toLowerCase();
        const show = txt.includes(term);
        row.style.display = show ? '' : 'none';
        if (show) visibleCount++;
      });
      const emptyState = table.querySelector('tbody .empty-state-row');
      if (emptyState) {
        emptyState.style.display = visibleCount === 0 ? '' : 'none';
      }
    });
  });

  // Evitar doble envío: deshabilita botón submit y muestra spinner ligero
  document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', (e) => {
      const btn = form.querySelector('button[type="submit"]');
      if (btn && !btn.disabled) {
        btn.disabled = true;
        const original = btn.innerHTML;
        btn.dataset.originalText = original;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>' + original;
        // Rehabilitar si no hay navegación (por ejemplo, validación)
        setTimeout(() => {
          btn.disabled = false;
          btn.innerHTML = btn.dataset.originalText || original;
        }, 3000);
      }
    }, { passive: true });
  });
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

function withTimestamp(filename, mode = 'date') {
  try {
    const now = new Date();
    const pad = (n) => String(n).padStart(2, '0');
    const datePart = `${now.getFullYear()}-${pad(now.getMonth()+1)}-${pad(now.getDate())}`;
    const timePart = `${pad(now.getHours())}-${pad(now.getMinutes())}`;
    const ts = mode === 'full' ? `${datePart}_${timePart}` : datePart;
    const dotIndex = filename.lastIndexOf('.');
    if (dotIndex !== -1) {
      const name = filename.substring(0, dotIndex);
      const ext = filename.substring(dotIndex);
      return `${name}_${ts}${ext}`;
    }
    return `${filename}_${ts}.csv`;
  } catch {
    return filename;
  }
}

function printTable(tableSelector) {
  const table = document.querySelector(tableSelector);
  if (!table) {
    window.print();
    return;
  }
  
  // Crear ventana de impresión con solo la tabla
  const printWindow = window.open('', '_blank');
  const title = document.title || 'Exportación PDF';
  
  printWindow.document.write(`
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <title>${title}</title>
      <style>
        body { font-family: Arial, sans-serif; margin: 20px; color: #000; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f5f5f5; font-weight: bold; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        h1 { color: #333; margin-bottom: 10px; }
        .empty-state-row { font-style: italic; color: #666; }
        @media (max-width: 576px) {
          th, td { padding: 6px; font-size: 12px; }
        }
        @media print {
          body { margin: 0; }
          table { page-break-inside: auto; }
          tr { page-break-inside: avoid; page-break-after: auto; }
        }
      </style>
    </head>
    <body>
      <h1>${title}</h1>
      ${table.outerHTML}
    </body>
    </html>
  `);
  
  printWindow.document.close();
  printWindow.focus();
  
  // Esperar a que cargue y luego imprimir
  setTimeout(() => {
    printWindow.print();
    printWindow.close();
  }, 250);
}