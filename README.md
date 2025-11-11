# Proyecto-Final-Web-II — Instrucciones para ejecutar en XAMPP

Este README explica cómo dejar el proyecto listo en un entorno XAMPP (Windows), importar la base de datos `control_escolar` y verificar las páginas principales.

Requisitos
- XAMPP instalado (Apache + MySQL)
- PHP 8.x compatible con PDO
- Acceso a la carpeta del proyecto: `C:\xampp\htdocs\PWBII\Proyecto-Final-Web-II`

Resumen rápido
1. Asegúrate de que Apache y MySQL estén corriendo desde el Panel de XAMPP.
2. Importa `migrations/control_escolar.sql` (phpMyAdmin o CLI).
3. Verifica/ajusta `config/config.php` para que use:
   - host: `127.0.0.1`
   - puerto: `3306`
   - usuario: `root`
   - contraseña: `` (vacía por defecto en XAMPP)
   - base de datos: `control_escolar`
4. Abre en el navegador: `http://localhost/Proyecto-Final-Web-II/public/`

Importar la base de datos

Opción A — phpMyAdmin (GUI)
1. Abre http://localhost/phpmyadmin/
2. Si existe una base de datos llamada `control_escolar`, puedes borrarla o sobrescribirla.
3. Ve a la pestaña "Importar" y selecciona `migrations/control_escolar.sql` desde el proyecto.
4. Haz clic en "Continuar" y espera a que termine.

Opción B — PowerShell / CLI (rápido)
Abre PowerShell y ejecuta (ajusta la ruta si instalaste XAMPP en otra carpeta):

```powershell
# Crear la base de datos (si el archivo .sql no la crea)
& 'C:\xampp\mysql\bin\mysql.exe' -u root -e "CREATE DATABASE IF NOT EXISTS control_escolar;"

# Importar el archivo (ruta desde el directorio del proyecto)
& 'C:\xampp\mysql\bin\mysql.exe' -u root control_escolar < "$PWD\migrations\control_escolar.sql"
```

Si tu MySQL tiene contraseña para `root`, cambia `-u root` por `-u root -p` y se te pedirá la contraseña.

Configurar la aplicación
1. Abre `config/config.php` y confirma los valores (host/puerto/usuario/contraseña/DB).
2. Si usas contraseña distinta para `root`, actualiza `config/config.php` o crea un usuario MySQL dedicado y dale permisos a `control_escolar`.

Probar la aplicación
1. Inicia Apache y MySQL desde el Panel de XAMPP.
2. Abre `http://localhost/Proyecto-Final-Web-II/public/`.
3. Pruebas básicas:
   - Iniciar sesión (revisa `migrations/control_escolar.sql` para ver usuarios de ejemplo). Si no ves credenciales, crea un usuario en `usuarios` (phpMyAdmin) con rol `admin`.
   - Ir a: Dashboard, Alumnos, Materias, Grupos y Calificaciones y comprobar que las páginas carguen.
   - CRUD: crear/editar/eliminar una materia y un alumno (siempre con usuario admin).
   - Como profesor: registrar calificaciones (si hay usuarios con rol `profesor`).

Notas importantes / solución de problemas
- Si ves una pantalla en blanco: activa errores en `php.ini` (display_errors = On) o revisa el log de Apache (`C:\xampp\apache\logs\error.log`).
- Si las sesiones no funcionan, verifica `session.save_path` en `php.ini` y que la carpeta exista y sea escribible por PHP.
- CSRF / tokens: la aplicación usa tokens en sesiones; si ves errores CSRF borra cookies y vuelve a iniciar sesión.
- Si algún adaptador/wrapper está incompleto, la aplicación usa fallbacks a los modelos originales; esto es intencional para mantener compatibilidad.

Credenciales de ejemplo (si están en el SQL)
- Revisa `migrations/control_escolar.sql` para encontrar usuarios de ejemplo (email, contraseña). Si no hay, crea uno así (ejemplo SQL):

```sql
INSERT INTO usuarios (email, password, nombre, rol, activo) VALUES ('admin@itsur.local', 'admin123', 'Admin', 'admin', 1);
```

Checklist de verificación rápida
- [ ] Apache y MySQL corriendo en XAMPP
- [ ] Base de datos `control_escolar` importada
- [ ] `config/config.php` apuntando a `control_escolar` con usuario correcto
- [ ] Páginas principales cargan (Login, Dashboard, Alumnos, Materias, Calificaciones)

Siguientes pasos recomendados
- Implementar los métodos faltantes en los adaptadores (`app/capas/datos/*`) para eliminar fallbacks a modelos.
- Traducir las vistas restantes y terminar la localización completa.
- Preparar plan de merge `i18n-es` → `main` con pruebas básicas y rollback.

## Gestión de usuario Admin (CLI y Web)

- Script CLI recomendado: `scripts/fix_admin_email.php`
  - Actualiza el email del primer usuario con rol `admin` y elimina cuentas placeholder (`admin@local`, `admin@local.test`).
  - Opcionalmente crea el admin si no existe.
  - Uso:
    - `php scripts/fix_admin_email.php --email=admin@itsur.edu.mx`
    - `php scripts/fix_admin_email.php --email=admin@itsur.edu.mx --create`
    - `php scripts/fix_admin_email.php --email=admin@itsur.edu.mx --create --password=admin123`
    - `php scripts/fix_admin_email.php --email=admin@itsur.edu.mx --dry-run`
  - Notas:
    - Requiere `pdo_mysql` habilitado en el `php.ini` del CLI. Verifica con `php -m`.
    - En XAMPP, usa `C:\xampp\php\php.exe` y habilita `extension=pdo_mysql` en `C:\xampp\php\php.ini`.

- Script CLI alternativo: `scripts/manage_admin.php`
  - Actualiza o crea admin con email por defecto `admin@itsur.edu.mx` y contraseña `admin123`.
  - Permite `--email=...` y `--password=...`.

- Endpoint web (solo localhost): `public/setup_admin.php`
  - Permite crear/actualizar admin vía navegador.
  - Acepta `?email=...&password=...`. Restringido a `127.0.0.1`/`::1`.

Si quieres, puedo:
- crear un script PowerShell para automatizar la importación y verificación básica, o
- implementar los métodos faltantes en los adaptadores para eliminar los fallbacks ahora.

---
Archivo añadido automáticamente por la tarea de preparación para XAMPP.
# Sistema de Control Escolar

Sistema web para la gestión escolar desarrollado en PHP, MySQL y JavaScript.

## Requisitos

- PHP 7.4 o superior
- MySQL 5.7 o superior
- Servidor web Apache/Nginx
- Extensiones PHP requeridas:
  - PDO
  - PDO_MYSQL
  - GD (para manejo de imágenes)
  - mbstring
  - json

## Instalación

1. Clonar o descargar el repositorio:
   ```bash
   git clone https://github.com/tuusuario/controlescolar.git
   cd controlescolar
   ```

2. Crear la base de datos y el usuario en MySQL:
   ```sql
   CREATE DATABASE control_escolar CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER 'control_user'@'localhost' IDENTIFIED BY 'tu_password_seguro';
   GRANT ALL PRIVILEGES ON control_escolar.* TO 'control_user'@'localhost';
   FLUSH PRIVILEGES;
   ```

3. Importar el esquema de la base de datos:
   ```bash
   mysql -u control_user -p control_escolar < migrations/001_create_schema.sql
   ```

4. Configurar el archivo de conexión a la base de datos:
   - Copiar `config/config.php.example` a `config/config.php`
   - Editar los valores de conexión:
     ```php
     'db' => [
         'host' => 'localhost',
         'name' => 'control_escolar',
         'user' => 'control_user',
         'pass' => 'tu_password_seguro'
     ]
     ```

5. Configurar el servidor web:
   - El DocumentRoot debe apuntar al directorio `public/`
   - Asegurarse que el módulo rewrite está habilitado
   - Ejemplo de configuración Apache:
     ```apache
     <VirtualHost *:80>
         ServerName controlescolar.local
         DocumentRoot "/path/to/controlescolar/public"
         <Directory "/path/to/controlescolar/public">
             AllowOverride All
             Require all granted
         </Directory>
     </VirtualHost>
     ```

6. Configurar permisos:
   ```bash
   # Crear directorio para fotos
   mkdir -p uploads/fotos
   
   # Asignar permisos (ajustar según tu servidor)
   chown -R www-data:www-data .
   chmod -R 755 .
   chmod -R 775 uploads
   ```

## Estructura del Proyecto

```
.
├── app/
│   ├── controllers/
│   │   └── api/
│   ├── models/
│   └── views/
├── config/
├── migrations/
├── public/
│   ├── assets/
│   │   ├── css/
│   │   ├── js/
│   │   └── img/
│   └── index.php
└── uploads/
    └── fotos/
```

## Flujo del Alumno (login → dashboard → Kardex)

- Inicia sesión con un usuario cuyo `rol = alumno`.
- Tras autenticación, el sistema redirige al tablero según rol; para alumnos se carga `src/Views/dashboard/student.php`.
- El panel muestra:
  - Resumen académico: promedio general, materias cursadas, materias pendientes.
  - Tabla Kardex con materia, grupo, ciclo, calificación y estado (Aprobado/Reprobado/Pendiente).
  - Gráfica de rendimiento (Chart.js) con el promedio por ciclo escolar.
  - Si no hay registros: se muestra el mensaje “No hay registros disponibles”.
- Los estilos usan Bootstrap y los toasts se integran vía `src/Views/layout.php`.

## Endpoints del Alumno (API)

- Todas las rutas requieren sesión activa y rol `alumno`.
- El API toma el `alumnoId` desde `$_SESSION['user_id']` para evitar accesos cruzados.
- Endpoints:
  - `GET /api/alumno/carga` → materias/grupos inscritos del alumno.
  - `GET /api/alumno/estadisticas` → resumen con promedio, total de materias, aprobadas y pendientes. Acepta `?ciclo=YYYY-1|YYYY-2`.
  - `GET /api/alumno/chart` → datos para Chart.js: `{ labels: [...], data: [...] }`.
- Ejemplos rápidos:
  - `http://localhost/Proyecto-Final-Web-II/public/api/alumno/carga`
  - `http://localhost/Proyecto-Final-Web-II/public/api/alumno/estadisticas?ciclo=2024-1`
  - `http://localhost/Proyecto-Final-Web-II/public/api/alumno/chart`

## Descripción de la gráfica

- La gráfica del panel del alumno usa Chart.js y muestra el promedio final por ciclo.
- Estructura esperada del JSON: `{ success: true, data: { labels: ["2023-2","2024-1"], data: [78.0, 85.0] } }`.
- El tipo de gráfica puede ser línea o barras según configuración del frontend; se estiliza con Bootstrap y respeta el CSP configurado.

## Pruebas rápidas (Alumno)

1. Inicia sesión como alumno.
2. Abre `http://localhost/Proyecto-Final-Web-II/public/dashboard.php` y verifica que ves el panel del alumno.
3. Comprueba que las tarjetas de resumen muestran valores coherentes.
4. Revisa la tabla Kardex: presencia de materias, calificaciones y estados.
5. Observa la gráfica: debe cargar con etiquetas de ciclos y promedios.
6. Llama los endpoints API listados arriba desde el navegador para validar respuestas JSON.
7. Verifica que el API no permite cambiar el `alumnoId` vía query (sólo la sesión determina el alumno).


## Flujo de Reportes (Admin/Profesor)

- Inicia sesión como `admin` o `profesor`.
- En el menú, entra a `Reportes` (`/reports`).
- Usa los filtros de `ciclo`, `grupo` y (si eres admin) `profesor`.
- Exporta con los botones:
  - `Exportar CSV` → envía `POST` con `csrf_token` y descarga `calificaciones.csv`.
  - `Exportar PDF` → envía `POST` con `csrf_token` y abre el PDF en otra pestaña.
- Se muestra un resumen con promedio general y reprobados, y una gráfica dinámica.

Notas:
- Para PDF se utiliza `dompdf/dompdf`. Instalación opcional con Composer:
  ```bash
  composer require dompdf/dompdf
  ```
- Los endpoints de exportación y reportes están protegidos por rol y validan `csrf_token`.

## Pruebas rápidas (Reportes y Estadísticas)

1. Abre `http://localhost/Proyecto-Final-Web-II/public/app.php` y navega a `Reportes`.
2. Aplica `ciclo = 2024-1` y verifica el resumen y la gráfica.
3. Exporta CSV y PDF. Valida que el CSV tenga encabezados y datos.
4. Endpoints de API de estadísticas:
   - `GET /api/charts/promedios-ciclo` → promedios por ciclo.
   - `GET /api/charts/desempeño-grupo` (profesor) → promedio por grupo del profesor autenticado.
   - `GET /api/charts/reprobados` → % de reprobados por materia.
5. Revisa que las respuestas mantengan `{ ok, data, message }` y que las rutas fallen con `403` si el rol no corresponde.


## Seguridad

### Configuración para Producción

1. Deshabilitar el modo debug en `config/config.php`:
   ```php
   'debug' => false
   ```

2. Configurar sesiones seguras:
   ```ini
   session.cookie_httponly = 1
   session.cookie_secure = 1
   session.cookie_samesite = "Strict"
   session.gc_maxlifetime = 3600
   ```

3. Permisos de archivos recomendados:
   - Archivos PHP: 644
   - Directorios: 755
   - Directorio uploads: 775

4. Configuración PHP recomendada:
   ```ini
   display_errors = Off
   log_errors = On
   error_reporting = E_ALL
   ```

### Prácticas de Seguridad Implementadas

- Consultas preparadas PDO para prevenir SQL injection
- Hash seguro de contraseñas con password_hash()
- Escape de salida HTML con htmlspecialchars()
- Protección CSRF uniforme con `csrf_token` y validación `hash_equals()`
- Validación de archivos subidos
- Regeneración de ID de sesión en login
- Headers de seguridad básicos
 - Sesiones endurecidas (`HttpOnly`, `SameSite=Strict`, `Secure` en HTTPS)
 - Validaciones de servidor en `GroupsService`: cupo (1–100), ciclo `^\d{4}-(1|2)$`, existencia de `materia_id` y `profesor_id` activo, y unicidad por (`materia_id`,`profesor_id`,`nombre`,`ciclo`).

## API REST

El sistema incluye endpoints JSON para todas las operaciones CRUD:

### Alumnos

```
GET /api/alumnos
GET /api/alumnos/{id}
POST /api/alumnos
POST /api/alumnos/{id}
POST /api/alumnos/delete/{id}
```

### Formato de Respuesta

```json
{
    "success": true|false,
    "data": {...}|[...],
    "errors": ["mensaje1", "mensaje2"],
    "pagination": {
        "currentPage": 1,
        "totalPages": 10,
        "hasNextPage": true,
        "hasPrevPage": false
    }
}
```

## Tests y Depuración

Incluye ejemplos de llamadas fetch para probar los endpoints:

```javascript
// Listar alumnos
fetch('/api/alumnos?page=1&limit=10')
    .then(r => r.json())
    .then(data => console.log(data));

// Crear alumno
fetch('/api/alumnos', {
    method: 'POST',
    body: new FormData(document.querySelector('#alumno-form'))
}).then(r => r.json());
```

## Migración a MVC

Para migrar a una arquitectura MVC más formal:

1. Implementar un router central:
   ```php
   // public/index.php
   $router = new Router();
   $router->get('/alumnos', 'AlumnosController@index');
   $router->run();
   ```

2. Usar un autoloader PSR-4:
   ```json
   {
       "autoload": {
           "psr-4": {
               "App\\": "app/"
           }
       }
   }
   ```

3. Implementar un contenedor de dependencias:
   ```php
   class Container {
       private $services = [];
       
       public function get($id) {
           return $this->services[$id]();
       }
   }
   ```

## Contribuir

1. Crear un fork del repositorio
2. Crear una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abrir un Pull Request

## Licencia

Distribuido bajo la Licencia MIT. Ver `LICENSE` para más información.

## Créditos

Desarrollado por [Tu Nombre](https://github.com/tuusuario)#   P r o y e c t o - F i n a l - W e b - I I 
 
 
# Control Escolar ITSUR (Modernizado)

Sistema de gestión académica con arquitectura modernizada, compatible con XAMPP y PHP 8.x. Incluye router con middleware, CSRF, sesiones endurecidas, catálogos dinámicos para selects, toasts contextuales, modales Bootstrap y experiencia mejorada para carga masiva de calificaciones.

## Requisitos

- PHP 8.x (pdo_mysql, mbstring, json)
- Apache (mod_rewrite habilitado)
- MySQL/MariaDB
- XAMPP recomendado en Windows

## Instalación (XAMPP)

1. Copia el proyecto en `C:\xampp\htdocs\PWBII\Control-Escolar-ITSUR`.
2. Importa la base de datos (`db/control_escolar.sql`).
3. Configura conexión en `config.php` o variables del proyecto.
4. Abre `http://localhost/PWBII/Control-Escolar-ITSUR/public/app.php`.

## Estructura del Proyecto

- `public/app.php`: punto de entrada del router.
- `src/`: controladores, servicios, vistas y utilidades.
- `src/Views/layout.php`: layout compartido con Bootstrap y toasts.
- `logs/app.log`: eventos en JSON (login, CRUD, calificaciones).
- `uploads/`: protegido por `.htaccess` (sin ejecución PHP, sólo imágenes).

## Roles y Accesos

- Admin: CRUD Materias/Grupos/Profesores; exportar CSV/PDF; dashboards.
- Profesor: CRUD Calificaciones; carga masiva CSV con resumen y descarga de log.
- Alumno: consulta de información académica y promedios.

## UX/Features Clave

- Modales Bootstrap para confirmar delete en Materias y Grupos.
- Validaciones visuales en formularios (Bootstrap validation states).
- Toasts contextuales (`success`, `warning`, `danger`) vía `$_SESSION['flash_type']`.
- Carga masiva CSV con barra de progreso y resumen (procesadas/actualizadas/omitidas) y descarga de log (`/grades/bulk-log`).
- Selects dinámicos por catálogos:
  - `/api/catalogs/subjects` (materias)
  - `/api/catalogs/professors` (profesores)
  - `/api/catalogs/students` (alumnos)
  - `/api/catalogs/groups` (por profesor actual)

## Seguridad y Rendimiento

- CSRF tokens en formularios.
- Sesiones endurecidas (httponly, secure, samesite Strict).
- Rate limiting por acción sensible (login, CRUD, bulk upload).
- Índices recomendados en `migrations/add_indexes.sql`.

## Flujo de Uso Ejemplo

1. Login como Admin → Dashboard → Crear Materia/Grupo/Profesor.
2. Login como Profesor → Dashboard → Registrar calificación o Carga masiva CSV → Descargar log.
3. Login como Alumno → Dashboard → Ver carga y promedios.

## Troubleshooting

- 403 al enviar formularios: revisa nombre del parámetro CSRF (`csrf` o `csrf_token`) y sesión.
- Upload CSV falla: valida formato y contenido (IDs enteros, calificaciones 0–100).
- Toasts no aparecen: confirma `$_SESSION['flash']` y `flash_type` antes de render.

## Créditos y Contacto

- Proyecto académico ITSUR.
- Mantenedores: Equipo PWBII.
- Contacto: soporte@itsur.edu.mx