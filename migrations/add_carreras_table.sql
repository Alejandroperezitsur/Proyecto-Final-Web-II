-- Migration: Add carreras table
-- This table stores the different academic programs (careers) offered by the institution

CREATE TABLE IF NOT EXISTS carreras (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    clave VARCHAR(50) NOT NULL UNIQUE,
    descripcion TEXT,
    duracion_semestres INT DEFAULT 9,
    creditos_totales INT DEFAULT 240,
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_activo (activo),
    INDEX idx_clave (clave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert all 7 careers
INSERT INTO carreras (nombre, clave, descripcion, duracion_semestres, creditos_totales) VALUES
('Ingeniería en Sistemas Computacionales', 'ISC', 'Profesionista capaz de diseñar, desarrollar e implementar sistemas computacionales aplicando las metodologías y tecnologías más recientes.', 9, 240),
('Ingeniería Industrial', 'II', 'Profesionista capaz de diseñar, implementar y mejorar sistemas de producción de bienes y servicios.', 9, 240),
('Ingeniería en Gestión Empresarial', 'IGE', 'Profesionista capaz de diseñar, crear y dirigir organizaciones competitivas con visión estratégica.', 9, 240),
('Ingeniería Electrónica', 'IE', 'Profesionista capaz de diseñar, desarrollar e innovar sistemas electrónicos para la solución de problemas en el sector productivo.', 9, 240),
('Ingeniería Mecatrónica', 'IM', 'Profesionista capaz de diseñar, construir y mantener sistemas mecatrónicos innovadores.', 9, 240),
('Ingeniería en Energías Renovables', 'IER', 'Profesionista capaz de diseñar, implementar y evaluar proyectos de energía sustentable.', 9, 240),
('Contador Público', 'CP', 'Profesionista capaz de diseñar, implementar y evaluar sistemas de información financiera.', 9, 240)
ON DUPLICATE KEY UPDATE nombre=VALUES(nombre);
