-- Archivo simplificado de base de datos para Control Escolar
-- Hecho rapido por un programador junior: estructura mínima para pruebas
-- NOTA: contraseña en texto plano en los inserts (solo para pruebas)

CREATE DATABASE IF NOT EXISTS `control_escolar` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `control_escolar`;

-- Tabla de usuarios (muy simple)
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(100) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `rol` ENUM('admin','profesor') NOT NULL DEFAULT 'profesor',
  `activo` TINYINT NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de alumnos (simplificada)
CREATE TABLE IF NOT EXISTS `alumnos` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `matricula` VARCHAR(20) NOT NULL,
  `nombre` VARCHAR(50) NOT NULL,
  `apellido` VARCHAR(50) NOT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `fecha_nac` DATE DEFAULT NULL,
  `foto` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `matricula` (`matricula`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de calificaciones (vinculada a alumno, materia libre como texto)
CREATE TABLE IF NOT EXISTS `calificaciones` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `alumno_id` INT UNSIGNED NOT NULL,
  `materia` VARCHAR(100) NOT NULL,
  `parcial1` DECIMAL(5,2) DEFAULT NULL,
  `parcial2` DECIMAL(5,2) DEFAULT NULL,
  `final` DECIMAL(5,2) DEFAULT NULL,
  -- Usar columna generada almacenada para promedio con ROUND y COALESCE
  `promedio` DECIMAL(5,2) GENERATED ALWAYS AS (ROUND((COALESCE(`parcial1`,0)+COALESCE(`parcial2`,0)+COALESCE(`final`,0))/3,2)) STORED,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `alumno_idx` (`alumno_id`),
  CONSTRAINT `fk_cal_alumno` FOREIGN KEY (`alumno_id`) REFERENCES `alumnos`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos de ejemplo (muy simples, contraseña en texto plano para simplicidad)
-- Se eliminaron los datos de ejemplo para evitar contraseñas en texto plano

-- Fin del script simplificado

-- Ajustes para remover display width en tablas ya existentes (evita warnings 1681)
ALTER TABLE `usuarios`
  MODIFY `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  MODIFY `activo` TINYINT NOT NULL DEFAULT 1;

ALTER TABLE `alumnos`
  MODIFY `id` INT UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `calificaciones`
  MODIFY `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  MODIFY `alumno_id` INT UNSIGNED NOT NULL;
