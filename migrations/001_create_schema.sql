-- Script de creación de base de datos para Control Escolar
-- Versión: 1.0
-- Fecha: 2025-10-30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE DATABASE IF NOT EXISTS `control_escolar`;

USE `control_escolar`;

-- Tabla de usuarios
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` enum('admin','profesor') NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de alumnos
CREATE TABLE `alumnos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `matricula` varchar(20) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `apellido` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `fecha_nac` date NOT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `matricula` (`matricula`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de profesores
CREATE TABLE `profesores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `especialidad` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `fk_profesor_usuario` FOREIGN KEY (`usuario_id`) 
    REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de materias
CREATE TABLE `materias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codigo` varchar(20) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `creditos` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de grupos/clases
CREATE TABLE `grupos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `materia_id` int(11) NOT NULL,
  `profesor_id` int(11) NOT NULL,
  `aula` varchar(20) NOT NULL,
  `horario` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `materia_id` (`materia_id`),
  KEY `profesor_id` (`profesor_id`),
  CONSTRAINT `fk_grupo_materia` FOREIGN KEY (`materia_id`) 
    REFERENCES `materias` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_grupo_profesor` FOREIGN KEY (`profesor_id`) 
    REFERENCES `profesores` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de calificaciones
CREATE TABLE `calificaciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `alumno_id` int(11) NOT NULL,
  `grupo_id` int(11) NOT NULL,
  `parcial1` decimal(5,2) DEFAULT NULL,
  `parcial2` decimal(5,2) DEFAULT NULL,
  `final` decimal(5,2) DEFAULT NULL,
  `promedio` decimal(5,2) GENERATED ALWAYS AS (
    (COALESCE(parcial1, 0) + COALESCE(parcial2, 0) + COALESCE(final, 0)) / 3
  ) STORED,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `alumno_grupo` (`alumno_id`, `grupo_id`),
  KEY `grupo_id` (`grupo_id`),
  CONSTRAINT `fk_calificacion_alumno` FOREIGN KEY (`alumno_id`) 
    REFERENCES `alumnos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_calificacion_grupo` FOREIGN KEY (`grupo_id`) 
    REFERENCES `grupos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar datos de ejemplo
INSERT INTO `usuarios` (`email`, `password`, `rol`) VALUES
('admin@escuela.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('profesor1@escuela.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'profesor'),
('profesor2@escuela.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'profesor');
-- Contraseña: 'password' para todos los usuarios

INSERT INTO `profesores` (`usuario_id`, `nombre`, `email`, `especialidad`) VALUES
(2, 'Juan Pérez', 'profesor1@escuela.edu', 'Matemáticas'),
(3, 'María García', 'profesor2@escuela.edu', 'Programación');

INSERT INTO `materias` (`codigo`, `nombre`, `creditos`) VALUES
('MAT101', 'Cálculo I', 8),
('PROG201', 'Programación Orientada a Objetos', 6);

-- Índices adicionales para optimizar búsquedas comunes
ALTER TABLE `alumnos` ADD INDEX `idx_nombre_apellido` (`nombre`, `apellido`);
ALTER TABLE `calificaciones` ADD INDEX `idx_grupo_promedio` (`grupo_id`, `promedio`);