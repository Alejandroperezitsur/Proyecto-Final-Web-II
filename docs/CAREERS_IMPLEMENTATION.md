# Correcciones al Dashboard del Administrador - Card de Carreras

## Cambios Realizados

### 1. Corrección del HTML en `admin.php`
**Archivo:** `src/Views/dashboard/admin.php`

**Problema:** El card de "Carreras" tenía un `</div>` extra que causaba que se sobrepusiera con otros elementos.

**Solución:** Se corrigió la estructura HTML eliminando el div extra y actualizando el enlace para que apunte a `/careers` en lugar de `/subjects`.

```php
// Antes (líneas 34-48):
<div class="col-md-3">
  <div class="card text-bg-secondary position-relative">
    ...
    <a href="<?php echo $base; ?>/subjects" class="stretched-link"></a>
  </div>
</div>
</div>  <!-- DIV EXTRA QUE CAUSABA EL PROBLEMA -->
<div class="col-md-3">

// Después (líneas 34-47):
<div class="col-md-3">
  <div class="card text-bg-secondary position-relative">
    ...
    <a href="<?php echo $base; ?>/careers" class="stretched-link"></a>
  </div>
</div>
<div class="col-md-3">
```

### 2. Creación de la Página de Carreras
**Archivo:** `src/Views/careers/index.php`

Se creó una nueva página completa para mostrar las carreras y sus planes de estudio con:

- **Tabs para diferentes carreras:**
  - Ingeniería en Sistemas Computacionales
  - Ingeniería Industrial
  - Ingeniería en Gestión Empresarial

- **Diagrama de materias por semestre:**
  - Organizado en columnas por semestre
  - Cards interactivos para cada materia
  - Información de créditos y códigos de materia
  - Distinción visual entre materias de especialidad y generales

- **Características visuales:**
  - Diseño responsivo con Bootstrap
  - Animaciones hover en las cards de materias
  - Colores distintivos por tipo de materia
  - Headers sticky para cada semestre

### 3. Controlador de Carreras
**Archivo:** `src/Controllers/CareersController.php`

Se creó un controlador dedicado para manejar:
- Renderizado de la vista de carreras
- API endpoint para obtener el conteo de carreras
- API endpoint para obtener el curriculum de cada carrera

### 4. Rutas Agregadas
**Archivo:** `public/app.php`

Se agregaron las siguientes rutas:
```php
$router->get('/careers', fn() => $careers->index(), [AuthMiddleware::requireRole('admin')]);
$router->get('/api/careers/count', fn() => $careers->getCareersCount(), [AuthMiddleware::requireRole('admin')]);
$router->get('/api/careers/curriculum', fn() => $careers->getCurriculum(), [AuthMiddleware::requireRole('admin')]);
```

### 5. Migración de Base de Datos
**Archivo:** `migrations/add_carreras_table.sql`

Se creó una migración para la tabla `carreras` con:
- Campos: id, nombre, clave, descripcion, duracion_semestres, creditos_totales, activo
- Datos iniciales para las 3 carreras principales
- Índices para optimizar consultas

### 6. Actualización del KPI Controller
**Archivo:** `src/Controllers/Api/KpiController.php`

Se actualizó para:
- Crear automáticamente la tabla `carreras` si no existe
- Poblar con datos iniciales de las 3 carreras
- Manejar errores gracefully si la tabla no existe

## Resultado Final

### Dashboard del Administrador
- ✅ Todos los cards están correctamente alineados
- ✅ Todos los cards tienen las mismas dimensiones
- ✅ El card de "Carreras" muestra el número correcto (3)
- ✅ Al hacer clic en el card de "Carreras" se navega a la página de carreras

### Página de Carreras
- ✅ Muestra información de cada carrera
- ✅ Diagrama visual de materias por semestre
- ✅ Materias organizadas en columnas por semestre
- ✅ Cards interactivos con información detallada
- ✅ Diseño responsivo y atractivo

## Cómo Probar

1. Iniciar sesión como administrador
2. Ir al dashboard del administrador
3. Verificar que el card de "Carreras" esté alineado correctamente
4. Hacer clic en el card de "Carreras"
5. Verificar que se muestre la página con las 3 carreras
6. Navegar entre las diferentes tabs (Sistemas, Industrial, Gestión)
7. Hacer clic en las materias para ver detalles (actualmente muestra un alert)

## Mejoras Futuras Sugeridas

1. **Integración con Base de Datos:**
   - Crear tabla `materias_carrera` para relacionar materias con carreras y semestres
   - Cargar curriculum dinámicamente desde la base de datos

2. **Funcionalidad Adicional:**
   - Modal con detalles completos de cada materia
   - Prerrequisitos y correquisitos
   - Descarga del plan de estudios en PDF
   - Visualización de retícula completa

3. **Gestión de Carreras:**
   - CRUD completo para carreras
   - Asignación de materias a carreras
   - Gestión de planes de estudio

4. **Reportes:**
   - Estadísticas por carrera
   - Alumnos inscritos por carrera
   - Tasas de aprobación por carrera
