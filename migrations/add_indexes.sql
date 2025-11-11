-- Índices recomendados para rendimiento
ALTER TABLE usuarios ADD INDEX idx_usuarios_email (email);
ALTER TABLE usuarios ADD INDEX idx_usuarios_rol_activo (rol, activo);
ALTER TABLE alumnos ADD INDEX idx_alumnos_matricula (matricula);
ALTER TABLE grupos ADD INDEX idx_grupos_profesor (profesor_id);
ALTER TABLE grupos ADD INDEX idx_grupos_materia_ciclo (materia_id, ciclo);
ALTER TABLE calificaciones ADD INDEX idx_calificaciones_alumno_grupo (alumno_id, grupo_id);