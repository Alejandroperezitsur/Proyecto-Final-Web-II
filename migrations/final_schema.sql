-- Migración consolidada para dejar el esquema consistente
-- Ejecutar en MySQL/MariaDB 10.3+ (soporta IF NOT EXISTS en ADD COLUMN)

-- alumnos.activo
ALTER TABLE alumnos
  ADD COLUMN IF NOT EXISTS activo TINYINT(1) NOT NULL DEFAULT 1;

-- grupos.cupo
ALTER TABLE grupos
  ADD COLUMN IF NOT EXISTS cupo INT NOT NULL DEFAULT 30;

-- Índices y unicidades
ALTER TABLE usuarios
  ADD COLUMN IF NOT EXISTS nombre VARCHAR(100) DEFAULT NULL AFTER email;
ALTER TABLE usuarios
  ADD INDEX IF NOT EXISTS idx_usuarios_rol_activo (rol, activo);

CREATE UNIQUE INDEX IF NOT EXISTS uq_usuarios_email ON usuarios (email);
CREATE UNIQUE INDEX IF NOT EXISTS uq_alumnos_matricula ON alumnos (matricula);
CREATE UNIQUE INDEX IF NOT EXISTS uq_materias_clave ON materias (clave);

-- FK calificaciones → alumnos/grupos
ALTER TABLE calificaciones
  ADD CONSTRAINT IF NOT EXISTS fk_cal_alumno
    FOREIGN KEY (alumno_id) REFERENCES alumnos(id) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT IF NOT EXISTS fk_cal_grupo
    FOREIGN KEY (grupo_id) REFERENCES grupos(id) ON DELETE CASCADE ON UPDATE CASCADE;

-- Índices de soporte para consultas comunes
CREATE INDEX IF NOT EXISTS idx_grupos_profesor ON grupos (profesor_id);
CREATE INDEX IF NOT EXISTS idx_grupos_materia ON grupos (materia_id);
CREATE INDEX IF NOT EXISTS idx_calificaciones_grupo ON calificaciones (grupo_id);
CREATE INDEX IF NOT EXISTS idx_calificaciones_alumno ON calificaciones (alumno_id);

-- Columna generada promedio (si no existe)
-- Nota: MySQL no soporta IF NOT EXISTS en ADD COLUMN GENERATED; validar antes de aplicar
-- ALTER TABLE calificaciones ADD COLUMN promedio DECIMAL(5,2) AS (
--   ROUND((COALESCE(parcial1,0) + COALESCE(parcial2,0) + COALESCE(final,0)) / 3, 2)
-- ) STORED;

-- Ajustes de charset
ALTER DATABASE control_escolar CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
ALTER TABLE usuarios CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE alumnos CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE materias CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE grupos CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE calificaciones CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
