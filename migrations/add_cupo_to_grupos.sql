-- Migración: Agregar columna cupo a tabla grupos
-- Fecha: 2024-12-31
-- Descripción: Permite configurar cupo específico por grupo en lugar de usar valor global

ALTER TABLE grupos ADD COLUMN cupo INT DEFAULT 30 NOT NULL;

-- Actualizar grupos existentes con cupo por defecto
UPDATE grupos SET cupo = 30 WHERE cupo IS NULL;

-- Comentario: La columna cupo permite a los administradores configurar
-- la capacidad máxima de estudiantes por grupo de forma individual