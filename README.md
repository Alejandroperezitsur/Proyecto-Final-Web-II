# SICEnet

Sistema Integral de Control Escolar del Instituto Tecnológico Superior del Sur de Guanajuato.

## Tecnologías
- Backend: PHP 8+ (MVC, POO)
- Frontend: HTML5, CSS3 (tema oscuro), JavaScript
- Base de datos: MySQL (tablas normalizadas con llaves foráneas)

## Requisitos
- PHP 8+ con PDO MySQL habilitado
- MySQL Server

## Instalación
1. Cree una base de datos MySQL (el sistema intenta crear `sicenet` automáticamente si el usuario tiene permisos).
2. Configure credenciales en `config/config.php` si no usa `root` sin contraseña.
3. Inicie servidor embebido de PHP:
   ```bash
   php -S localhost:8000 -t public
   ```
4. Abra `http://localhost:8000/`.

### Autoinstalación
- En el primer inicio, el sistema:
  - Crea la base de datos (si es posible).
  - Aplica el esquema `db/schema.sql`.
  - Ejecuta el seeder `db/seed.php` para generar datos de prueba (50 alumnos, 30 profesores, materias, grupos, horarios y calificaciones).

### Accesos de prueba
- Admin: usuario `admin`, contraseña `admin1234`.
- Profesor: usuarios `prof1` … `prof30`, contraseña `prof<N>1234` (ej. `prof1`/`prof11234`).
- Alumno: matrículas `700000001` … `700000050`, contraseña `alumno<N>1234` (ej. `700000001`/`alumno11234`).

## Estructura de carpetas
- `/public` front controller y archivos públicos.
- `/core` bootstrap, router, base controller, DB y instalador.
- `/controllers` lógica por rol (Auth, Alumno, Profesor, Admin).
- `/models` (reservado para ampliar entidades; no imprescindible en este MVP).
- `/views` vistas con `layout.php` y subcarpetas por módulo.
- `/assets` estilos y scripts (tema oscuro, validaciones).
- `/db` esquema SQL y seeder PHP.

## Módulos
- Alumno: login con matrícula, dashboard, cardex, calificaciones, horario, retícula y reinscripción en periodo activo.
- Profesor: login, gestión de grupos, lista de alumnos y captura de calificaciones por unidad.
- Admin: login, dashboard de estadísticas y CRUD básico de carreras, materias, grupos, alumnos y profesores.

## Notas
- Este MVP prioriza funcionalidad y claridad; puedes extender con roles/permisos, paginación, filtros y seguridad avanzada según necesidades.

## Navegación lateral (Sidebar)
- Sidebar moderno y oscuro, visible para todos los roles.
- Ítems según rol y rutas activas:
  - Admin: `Dashboard`, `Alumnos`, `Profesores`, `Carreras`, `Materias`, `Grupos`, `Configuración`.
  - Profesor: `Dashboard`, `Mis grupos`, `Calificaciones` (lleva a captura en grupos).
  - Alumno: `Dashboard`, `Cardex`, `Calificaciones`, `Horario`, `Retícula`, `Reinscripción`.
- Estado activo: se calcula por la ruta (`?route=...`) y, para Admin, por `entity` en CRUD.
- Responsivo: se colapsa automáticamente en <900px; usa el botón hamburguesa para alternar.
- El contenido se adapta con `margin-left` dinámico, sin romper toasts, modales ni tablas.

### Badges dinámicos en Admin
- El menú del Admin muestra badges con los totales reales de registros: Alumnos, Profesores, Carreras, Materias y Grupos.

### Mejoras visuales: Toasts y Badges en Dashboard Admin
- Toasts de confirmación:
  - El `layout.php` detecta `?msg=` y renderiza un `<div class="toast">` con texto y tipo.
  - Claves soportadas: `reinscripcion_activada` (success), `reinscripcion_desactivada` (error), `guardado` (success), `actualizado` (success), `eliminado` (error).
  - Estilos en `public/assets/css/style.css` → `.toast { position: fixed; bottom/right 20px; animación fadeInOut 3s; tema oscuro }` con variantes `.toast.success` y `.toast.error`.
  - Auto-ocultación en 3s vía `<script>` inline del `layout.php`.
- Badges dinámicos en Dashboard:
  - Nuevas clases: `.badges-row` (flex horizontal), `.badge` (fondo translúcido y borde fino), `.badge-activo` (verde) y `.badge-inactivo` (rojo).
  - Card “Estado general” con 4 badges: Alumnos 👨‍🎓 (cian), Profesores 👩‍🏫 (magenta), Grupos 🧩 (violeta) y Reinscripción 🔁 (verde/rojo según estado).
  - En la tarjeta “Administración”, cada enlace muestra su total en una badge gris.
  - Variables disponibles en la vista: `stats['alumnos']`, `stats['profesores']`, `stats['grupos']`, `stats['carreras']`, `stats['materias']`, y `reinscripcion_activa`.

### Exportaciones (Admin)
- Endpoints nuevos:
  - `GET/POST /?route=admin/export/pdf` (parámetros: `entity` o `mode=dashboard`).
  - `GET/POST /?route=admin/export/excel` (parámetros: `entity` o `mode=dashboard`).
- CRUDs: en cada vista con buscador se añadieron botones "Exportar PDF" y "Exportar Excel" que respetan los mismos filtros (`q`, `entity`).
- Dashboard: botones superiores "📊 Exportar PDF" y "📈 Exportar Excel" para exportar métricas y datos de gráficas.
- PDF usa FPDF con encabezado, fecha y tabla básica; Excel usa PhpSpreadsheet (.xlsx) con hojas para resumen y totales por carrera.
- Integración de librerías:
  - Se carga `vendor/autoload.php` si existe; en su ausencia se intenta `lib/fpdf/fpdf.php` o `lib/PhpSpreadsheet/vendor/autoload.php` vía `require_once`.
  - Recomendado instalar vía Composer: `composer require setasign/fpdf phpoffice/phpspreadsheet`.
- Validaciones:
  - Exportaciones disponibles sólo para Admin (`requireRole('admin')`).
  - No interfieren con modales, filtros ni paginación; mantienen tema oscuro.
  - Los archivos se generan con datos actuales según filtros y modo seleccionado.

### Control de Reinscripción
- Base de datos:
  - Nueva tabla `configuraciones` con pares `clave`/`valor`.
  - Parámetro `reinscripcion_activa` (`0`=desactivada, `1`=activa). Se inserta por defecto en `db/schema.sql`.
- Admin:
  - Dashboard incluye tarjeta “Control de reinscripción” con estado actual (Activa/Inactiva).
  - Botón “Activar / Desactivar” apunta a `/?route=admin/toggleReinscripcion`.
  - Método `toggleReinscripcion()` alterna el estado y redirige con mensaje.
  - Método `isReinscripcionActiva()` consulta el estado actual.
- Alumno:
  - El Dashboard muestra dinámicamente si puede reinscribirse.
  - Si está activa: muestra el botón/formulario de “Reinscribirme” con confirmación.
  - Si está inactiva: muestra aviso de indisponibilidad.
- Estilos: se agregan `.status-activo` y `.status-inactivo` en el CSS, consistente con el tema oscuro.
- Los totales se calculan en `AdminController::getSidebarStats()` con consultas `COUNT(*)` y se actualizan al cargar el `layout.php`.
- Diseño: fondo cian translúcido `rgba(0,188,212,0.2)`, texto blanco, `border-radius: 12px`, `padding: 2px 6px`, `font-size: 0.75rem`.
- Compatibilidad: funciona solo para Admin, no afecta vistas de Profesor o Alumno, ni el colapso responsivo del sidebar.

## Dashboards y gráficas
- Rutas principales: `/admin/dashboard`, `/professor/dashboard`, `/student/dashboard`.
- Admin:
  - Tarjetas con: carreras, alumnos, profesores, materias, grupos y promedio global.
  - Gráficas:
    - Barras: alumnos activos por carrera.
    - Barras: profesores por carrera.
    - Dona: promedio global (promedio vs resto hasta 100).
    - Dona: distribución del sistema (alumnos, profesores, grupos, materias).
    - Líneas: actividad de reinscripciones (últimos 6 meses, dataset de ejemplo).
- Profesor:
  - Tarjetas con: grupos activos, alumnos totales, materias asignadas y promedio total.
  - Gráficas:
    - Barras: promedio general por grupo.
    - Dona: distribución de calificaciones (reprobados <70, aprobados 70–89, destacados ≥90).
    - Barras: grupos por materia (cantidad de grupos del profesor por cada materia).
- Alumno:
  - Tarjetas con: promedio actual (cursando), materias aprobadas, créditos acumulados (aprox.) y avance %.
  - Gráficas:
    - Dona: progreso en retícula (aprobadas vs pendientes).
    - Líneas: historial de promedios por semestre.
    - Dona personalizada: "Mi Progreso Académico" con tooltips y etiqueta dinámica.

### Técnica
- Datos cargados dinámicamente desde PHP con consultas reales y entregados al JS vía `json_encode()`.
- Render con Chart.js 4+ (CDN) en tema oscuro (texto blanco, acentos cian/azul) y responsive.
- Las gráficas se renderizan al cargar el dashboard sin recargar toda la página.
- Inclusión global: Chart.js se incluye en `views/layout.php` para disponibilidad en todas las vistas (se eliminaron inclusiones duplicadas en dashboards Admin/Profesor).
- Estilos de canvas: en `public/assets/css/style.css`, los `<canvas>` dentro de `.card` usan `width: 100%`, `max-height: 300px`, `border-radius: 12px`, `background: rgba(255,255,255,0.03)`, y `padding: 10px`.
  - En pantallas pequeñas (`<600px`), el `max-height` se reduce y el `padding` se ajusta.

### Datos y consultas empleadas
- Admin:
  - `stats`: totales de `alumnos`, `profesores`, `materias`, `grupos` mediante `COUNT(*)`.
  - `alumnos_por_carrera` y `profesores_por_carrera`: agregaciones por carrera.
  - Reinscripciones recientes: se presenta dataset ilustrativo (no hay timestamp en `inscripciones`).
- Profesor:
  - `promedios_por_grupo`: promedio de calificaciones por grupo.
  - `distribucion`: conteo de inscripciones por rango de promedio.
  - `grupos_por_materia`: `SELECT m.nombre, COUNT(g.id) ... WHERE g.profesor_id=? GROUP BY m.id`.
- No se modifica el flujo de login ni lógica backend más allá de consultas agregadas; CRUDs, modales y validaciones se mantienen.

## Fase 3G — Refinamiento visual y UX

- Indicadores visuales:
  - Alumno: etiqueta dinámica sobre la dona "Mi Progreso Académico".
    - "Progreso sobresaliente" si aprobadas ≥ 80%.
    - "Progreso aceptable" si 60–79%.
    - "Necesita mejorar" si < 60%.
    - Caso vacío: muestra "Sin materias inscritas" y no renderiza la gráfica.
  - Profesor: color adaptativo en barra del promedio general.
    - Verde ≥ 8.5, amarillo 7–8.4, rojo < 7 (se adapta automáticamente si el promedio está en escala 0–100 dividiendo entre 10).
    - Animación sutil al cargar (easing `easeOutQuart`).
    - Caso vacío: muestra "Sin grupos activos" y no renderiza la gráfica.
- Tooltips:
  - Se personalizan para mostrar valores exactos en las gráficas clave (dona de alumno y barra de profesor).
- Estilos generales:
  - Tarjetas con `padding` uniforme, texto base en gris claro `#ccc` y valores destacados en blanco.
  - Hover suave: fondo ligeramente más claro, sombra difusa y leve elevación.
  - Responsividad reforzada: reducción de altura de canvas en `<600px`.

### Screenshots finales

Se recomienda capturar y colocar las imágenes en `docs/screenshots/`:
- `docs/screenshots/student-dashboard.png`
- `docs/screenshots/professor-dashboard.png`

Las rutas pueden referenciarse aquí una vez agregadas al repositorio.

## Estadísticas personalizadas por usuario

### Alumno
- Tarjeta "Mi Progreso Académico" que muestra: total de materias, aprobadas, pendientes y promedio general.
- Gráfica tipo dona: aprobadas vs pendientes.

Consultas en `controllers/StudentController.php`:

```
$id = $_SESSION['user']['id'];
$totalMaterias = $db->query("SELECT COUNT(*) FROM inscripciones WHERE alumno_id=$id")->fetchColumn();
$materiasAprobadas = $db->query("SELECT COUNT(*) FROM inscripciones WHERE alumno_id=$id AND calificacion >= 70")->fetchColumn();
$promedio = $db->query("SELECT ROUND(AVG(calificacion),2) FROM inscripciones WHERE alumno_id=$id")->fetchColumn();
$materiasPendientes = $totalMaterias - $materiasAprobadas;
render('student/dashboard', compact('totalMaterias','materiasAprobadas','materiasPendientes','promedio'));
```

### Profesor
- Tarjeta "Estadísticas del profesor" que muestra: grupos activos, alumnos totales y promedio general.
- Gráfica de barras: promedio general visualizado.

Consultas en `controllers/ProfessorController.php` (ejemplo simple):

```
$id = $_SESSION['user']['id'];
$totalGrupos = $db->query("SELECT COUNT(*) FROM grupos WHERE profesor_id=$id")->fetchColumn();
$totalAlumnos = $db->query("SELECT COUNT(*) FROM inscripciones i JOIN grupos g ON i.grupo_id=g.id WHERE g.profesor_id=$id")->fetchColumn();
$promedioGeneral = $db->query("SELECT ROUND(AVG(i.calificacion),2) FROM inscripciones i JOIN grupos g ON i.grupo_id=g.id WHERE g.profesor_id=$id")->fetchColumn();
render('professor/dashboard', compact('totalGrupos','totalAlumnos','promedioGeneral'));
```

### Técnica y estilos
- Chart.js se incluye globalmente en `views/layout.php` y se utiliza en ambas vistas sin duplicaciones.
- Estilos añadidos para bloques de información:
```
.progress-info, .prof-stats { margin-bottom: 12px; line-height: 1.4; }
.progress-info p, .prof-stats p { color: #ddd; margin: 4px 0; }
```
## Fase 3H — Unificación y despliegue

### Unificación en Admin
- Tooltips personalizados en gráficas (barras, dona) mostrando valores exactos.
- Mensajes “Sin datos” cuando no hay métricas (sin carreras o promedio nulo).
- Animación uniforme en todas las gráficas (`duration: 1200`, `easing: 'easeOutQuart'`).
- Canvas y tarjetas estandarizados con el mismo estilo que Profesor y Alumno.

### UX Global
- Tarjetas `.card` con hover suave y animación de entrada sutil.
- Texto base `#ccc` para legibilidad y valores destacados en blanco.
- Etiquetas `.progress-label` unificadas (good/okay/bad) para indicadores contextuales.
- Responsividad reforzada (`<600px`) para padding y altura de canvas.
- Inclusión global de Chart.js en `views/layout.php` sin duplicadas.

### Validación completa
- Admin: `/?route=admin/dashboard`
- Profesor: `/?route=professor/dashboard`
- Alumno: `/?route=student/dashboard`
- Confirmado: tooltips correctos, casos vacíos con mensajes amigables, coherencia en pantallas pequeñas y sin errores JS.

### Preparación para despliegue local o remoto
1. Clonar repositorio
   - `git clone <repo>`
2. Configurar base de datos
   - Editar `config/config.php` con `host`, `name`, `user`, `pass`.
   - Importar `db/schema.sql` y, opcionalmente, `db/seed.php`.
3. Ejecutar servidor local
   - `php -S localhost:8000 -t public`
4. Acceder a los dashboards
   - Admin: `/?route=admin/dashboard`
   - Profesor: `/?route=professor/dashboard`
   - Alumno: `/?route=student/dashboard`

### Screenshots finales
- `docs/screenshots/admin-dashboard.png`
- `docs/screenshots/professor-dashboard.png`
- `docs/screenshots/student-dashboard.png`