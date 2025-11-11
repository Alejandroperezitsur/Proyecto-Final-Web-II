# Modernización del Sistema de Control Escolar

Esta guía documenta la arquitectura paralela modernizada agregada al proyecto, manteniendo compatibilidad con XAMPP y PHP 8.x.

## Estructura Nueva (PSR-4)

- `composer.json` con autoload PSR-4 (`App\` → `src/`).
- `src/Http/Router.php`: micro-router GET/POST con middleware.
- `src/Http/Middleware/AuthMiddleware.php`: requerir login/rol.
- `src/Http/SecurityHeaders.php`: cabeceras de seguridad (CSP, XFO, ReferrerPolicy, PermissionsPolicy).
- `src/Kernel.php`: sesión endurecida y CSRF.
- `src/Controllers/`: `AuthController.php`, `DashboardController.php`, `ReportsController.php`, `GradesController.php`.
- `src/Repositories/UserRepository.php`: acceso a datos de usuarios sobre PDO.
- `src/Services/UserService.php`: autenticación y lógica de negocio básica.
- `src/Views/`: layout y dashboards por rol; carga masiva CSV.
- `public/app.php`: punto de entrada del router.
- `migrations/add_indexes.sql`: índices para rendimiento.

## Cómo Probar Rápido

1. Asegúrate que la base de datos del proyecto original funciona.
2. Abre en navegador: `http://localhost/PWBII/Control-Escolar-ITSUR/public/app.php`.
3. Inicia sesión (email+password para admin/profesor; matrícula+password para alumno).
4. Navega el dashboard por rol y prueba:
   - Admin: `Exportar CSV`.
   - Profesor: `Carga masiva de calificaciones (CSV)`.

## Composer (Opcional)

Si quieres usar autoload de Composer:

```powershell
cd C:\xampp\htdocs\PWBII\Control-Escolar-ITSUR
composer install
```

## Seguridad

- Cabeceras modernas activadas en `App\Http\SecurityHeaders`.
- CSRF para formularios y router.
- Sesiones endurecidas en `App\Kernel` (httponly, secure en HTTPS, samesite).
  - `session.cookie_samesite = Strict` para prevenir CSRF cross-site.
  - `session.cookie_secure = On` automáticamente en HTTPS.
  - CSP actualizada para permitir CDNs específicos (Bootstrap, Chart.js, FontAwesome) y limitar orígenes de `connect-src`.

## Rendimiento

- Ejecuta `migrations/add_indexes.sql` para crear índices comunes.

## Siguientes Pasos

- Conectar vistas modernas a endpoints existentes (`/public/api/...`).
  - Alumno: `/api/alumno/carga`, `/api/alumno/estadisticas` (usados en dashboard alumno).
  - Profesor: `/api/kpis/profesor` para KPIs y `/api/profesores` para gestión de grupos/calificaciones.
  - Admin: `/api/kpis/admin` para KPIs y `/api/charts/promedios-materias` para Chart.js.
  - Exportaciones: CSV `/reports/export/csv` y PDF `/reports/export/pdf` (Dompdf).

- Migrar gradualmente páginas antiguas al router (`public/app.php`).
  - Ejemplo migrado: `public/alumnos.php` → ruta `/alumnos` con `StudentsController`.
  - Nuevas pantallas CRUD: `/subjects` y `/groups` con CSRF y validaciones.
  - Gestión de profesores: `/professors` (lista/crear/eliminar) bajo rol `admin`.
  - Registro de calificaciones: `/grades` (profesor) y carga masiva `/grades/bulk`.

- Agregar PDF (Dompdf) y cache ligero de catálogos por servicio.
  - Instalar: `composer require dompdf/dompdf` (opcional).
  - PDF en `ReportsController::exportPdf()`.

## Flujo de Pruebas

1. Iniciar en `http://localhost/PWBII/Control-Escolar-ITSUR/public/app.php`.
2. Login:
   - Tras 3 intentos fallidos se solicita captcha simple.
   - Rate limit: 20 intentos/10 minutos por sesión.
3. Dashboards:
  - Admin: KPIs reales y gráfico de promedios por materia.
  - Profesor: KPI de grupos activos y alumnos por sesión.
  - Alumno: carga académica y promedio general.
4. CRUD:
  - Materias: `/subjects` crear/eliminar.
  - Grupos: `/groups` crear/eliminar.
  - Profesores: `/professors` crear/eliminar.
5. Exportaciones:
  - CSV: `/reports/export/csv` (admin)
  - PDF: `/reports/export/pdf` (admin)

## Rate Limiting

- Login: 20 intentos cada 10 minutos por sesión.
- Profesores: crear/eliminar limitado (20/10 min).
- Calificaciones: crear (30/10 min) y carga masiva (20/10 min).
- Subjects/Groups: crear/eliminar limitado (30/10 min) para evitar abuso.

## Logger

- `src/Utils/Logger.php` registra eventos en `logs/app.log` (JSON):
  - Login éxito/fallo, logout.
  - CRUD: subjects/groups/professors create/update/delete.
  - Calificaciones: upsert individual y resumen de carga masiva.
  - Campos: timestamp, usuario, rol, ruta y contexto.

## Catálogos

- Endpoints para selects dinámicos:
  - `/api/catalogs/subjects` (admin/profesor)
  - `/api/catalogs/professors` (admin)
  - `/api/catalogs/students` (admin/profesor)
  - `/api/catalogs/groups?profesor=ID` (admin/profesor)

## UX/Visual

- Modales Bootstrap para confirmaciones en CRUD de Materias y Grupos (sustituyen `confirm()`).
- Estados de validación visual (Bootstrap) en formularios: materias, grupos, profesores y calificaciones.
- Toasts contextuales (éxito, advertencia, error) integrados en `layout.php` y controlados vía `$_SESSION['flash']`/`flash_type`.
- Carga masiva CSV mejorada:
  - Barra de progreso y resumen con contadores: `procesadas`, `actualizadas`, `omitidas`.
  - Respuesta JSON si `Accept: application/json`.
  - Descarga del log del último proceso: `GET /grades/bulk-log`.

## Flujos por Rol (Ejemplo)

- Admin:
  - Login → Dashboard → CRUD Materias/Grupos/Profesores → Exportar CSV/PDF → Logout.
  - Rutas principales: `/subjects`, `/groups`, `/professors`, `/reports/export/*`.
- Profesor:
  - Login → Dashboard → Ver grupos asignados → Registrar calificaciones (`/grades`) → Carga masiva (`/grades/bulk`) → Descargar log (`/grades/bulk-log`) → Logout.
- Alumno:
  - Login → Dashboard → Ver carga académica y promedios → Logout.

## Despliegue Producción

- Apache con `mod_rewrite` habilitado; DocumentRoot apuntando a `public/` o alias:
  - `AllowOverride All` para respetar `.htaccess` (uploads).
- PHP 8.x recomendado; extensiones: `pdo_mysql`, `mbstring`, `json`.
- Permisos de carpeta `logs/` para escritura de Apache/PHP.
- HTTPS activado y cookies seguras (`secure`, `httponly`, `samesite=Strict`).
- CSP ajustada a los CDNs usados (Bootstrap, FontAwesome); limitar `connect-src` a endpoints internos.

## Seguridad adicional

- CSRF en formularios (`SubjectsController`, `GroupsController`, login).
- Middleware de autenticación por rol en router.
- Middleware de rate limit para `/login` y captcha opcional en vista.

## Fase 1 — Validaciones y CSRF (Aplicado)

- CSRF homogeneizado: todos los formularios usan `csrf_token` y los controladores validan con `hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')`.
- Validaciones servidor en `App\Services\GroupsService`:
  - Cupo entre 1–100; ciclo con regex `^\d{4}-(1|2)$`.
  - `materia_id` existente (`materias`) y `profesor_id` existente y activo (`usuarios` con `rol='profesor'` y `activo=1`).
  - Unicidad por (`materia_id`, `profesor_id`, `nombre`, `ciclo`) en `create`/`update`.
  - Helpers: `exists(table,id)`, `existsProfesorActivo(id)`, `existsGroupCombo(data, excludeId)`.
  - Mensajes claros de error vía `getLastError()` y logging JSON estructurado (`Logger::info('group_validation_failed', ctx)`).
- Seguridad: sesión endurecida en `Kernel` (`HttpOnly`, `SameSite=Strict`, `Secure` si HTTPS) y CSP aplicada en `SecurityHeaders`.
- Uploads protegidos con `.htaccess`: sin ejecución PHP, sólo imágenes, sin indexación.

## Fase 2 — Panel del Alumno (Aplicado)

- Endpoints creados (protegidos por rol `alumno` en `public/app.php`):
  - `GET /api/alumno/carga` → lista de materias/grupos inscritos con `ciclo`, `estado` (Aprobado/Reprobado/Pendiente) y `calificacion`.
  - `GET /api/alumno/estadisticas` → resumen: `promedio`, `total`, `aprobadas`, `pendientes` (acepta `?ciclo=YYYY-1|YYYY-2`).
  - `GET /api/alumno/chart` → datos para Chart.js: `{ labels: [ciclos], data: [promedios] }`.

- Validaciones aplicadas:
  - Acceso por sesión: el API usa `$_SESSION['user_id']` y no acepta IDs en query para evitar accesos cruzados.
  - Verificación de alumno activo en `StudentsService::existsStudentActive()`.
  - Validación de `ciclo` con regex `^\d{4}-(1|2)$` cuando aplica.
  - Errores con `getLastError()` y logging JSON (`Logger::info('student_*', ctx)`).

- Seguridad de acceso por rol:
  - Rutas `GET /api/alumno/*` registradas con `AuthMiddleware::requireRole('alumno')`.
  - El panel del alumno se renderiza desde `DashboardController` hacia `src/Views/dashboard/student.php`.

- Vista moderna (`src/Views/dashboard/student.php`):
  - Tarjetas de resumen (promedio general, materias cursadas, pendientes) con barra de progreso.
  - Tabla Kardex dinámica con filtro por materia/grupo y mensaje “No hay registros disponibles”.
  - Gráfica de rendimiento con Chart.js utilizando `/api/alumno/chart`.
  - Estilos Bootstrap y toasts integrados vía `src/Views/layout.php`.

- Ejemplos de respuestas JSON:
  - `GET /api/alumno/carga` → `{ "success": true, "data": [{ "materia": "Programación", "grupo": "A1", "ciclo": "2024-1", "calificacion": 85, "estado": "Aprobado" }] }`
  - `GET /api/alumno/estadisticas` → `{ "success": true, "data": { "promedio": 82.5, "total": 6, "aprobadas": 5, "pendientes": 1 } }`
- `GET /api/alumno/chart` → `{ "success": true, "data": { "labels": ["2023-2","2024-1"], "data": [78.0, 85.0] } }`

## Fase 3 — Reportes y Métricas (Aplicado)

- Controladores y endpoints:
  - `ReportsController`:
    - `POST /reports/export/csv` → exportación CSV con filtros `ciclo`, `grupo_id`, `profesor_id` (según rol).
    - `POST /reports/export/pdf` → exportación PDF (Dompdf) con los mismos filtros.
    - `GET /reports/summary` → resumen estadístico `{ ok, data: { promedio, reprobados, porcentaje_reprobados } }`.
    - `GET /reports` → vista `src/Views/reports/index.php` con filtros y botones CSRF.
  - `ChartsController`:
    - `GET /api/charts/promedios-ciclo` → `{ ok, data: { labels: [ciclos], data: [promedios] } }`.
    - `GET /api/charts/desempeño-grupo` (profesor) → `{ ok, data: { labels: [grupos], data: [promedios] } }`.
    - `GET /api/charts/reprobados` → `{ ok, data: { labels: [materias], data: [%] } }`.

- Vistas:
  - `src/Views/reports/index.php` con filtros por ciclo/grupo/profesor, botones “Exportar CSV/PDF” protegidos por CSRF, y gráfica dinámica.
  - `src/Views/dashboard/admin_stats.php` con “Promedios por ciclo” y “% Reprobados por materia”.
  - `src/Views/dashboard/prof_stats.php` con “Comparativa de promedio por grupo” del profesor.

- Seguridad y validaciones:
  - Rutas protegidas por rol: admin y profesor (según corresponda).
  - `csrf_token` obligatorio en exportaciones `POST`.
  - Validación de `ciclo` con regex `^\d{4}-(1|2)$`.
  - Acceso a datos limitado por sesión/rol: el profesor sólo consulta sus grupos.
  - Respuesta JSON uniforme: `{ ok, data, message }` en endpoints de API.

- Logging estructurado (`logs/app.log`):
  - Eventos: `report_export_csv`, `report_export_pdf`, `report_summary`, `chart_query`.
  - Contexto: filtros aplicados, tipo de gráfica, usuario/rol/sesión, resultados agregados.

- PDF:
  - Usa `dompdf/dompdf`. Instalación opcional: `composer require dompdf/dompdf`.
  - Render A4 horizontal con tabla de calificaciones.

- Ejemplos de pruebas rápidas:
  - Abrir `http://localhost/PWBII/Control-Escolar-ITSUR/public/app.php` y navegar a “Reportes”.
  - Aplicar filtro `ciclo = 2024-1` y exportar CSV/PDF.
  - Ver gráfica de “Promedios por ciclo” y “% Reprobados”.
  - Como profesor, revisar “Comparativa de promedio por grupo”.