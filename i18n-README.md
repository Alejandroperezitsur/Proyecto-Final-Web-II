i18n-es branch instructions

Este archivo contiene instrucciones para crear y trabajar en la rama `i18n-es` localmente.

1) Crear la rama local y cambiar a ella:

   git checkout -b i18n-es

2) Aplica los cambios y commitea:

   git add .
   git commit -m "i18n: iniciar wrappers en español y estructura por capas"

3) Si deseas publicar la rama remota:

   git push -u origin i18n-es

Plan de trabajo (alto nivel):
- Añadir wrappers (capa de negocio) para los modelos principales (ya añadidos: Autenticacion, Calificaciones, Grupos, Alumnos, Materias, Usuarios).
- Crear adaptadores en `app/capas/datos/` que envuelvan los modelos existentes (opcional, step inicial con placeholders).
- Traducir gradualmente las vistas a español usando los wrappers (presentación -> negocio -> datos).
- Ejecutar pruebas manuales en XAMPP antes de mergear a `main`.

Notas:
- No se cambiaron las APIs internas de `models/` ni `controllers/` para mantener compatibilidad.
- Para revertir rapidamente: borra la rama `i18n-es` local y remota si existe.

Si quieres, puedo crear un script que automatice la creación de la rama y los commits desde tu entorno local (necesitarás ejecutar los comandos en tu terminal).