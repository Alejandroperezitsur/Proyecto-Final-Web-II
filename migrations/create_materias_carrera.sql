-- Create table to link materias with carreras and semesters
CREATE TABLE IF NOT EXISTS materias_carrera (
    id INT AUTO_INCREMENT PRIMARY KEY,
    materia_id INT UNSIGNED NOT NULL,
    carrera_id INT NOT NULL,
    semestre TINYINT NOT NULL,
    tipo ENUM('basica', 'especialidad', 'residencia') DEFAULT 'basica',
    creditos INT DEFAULT 5,
    FOREIGN KEY (materia_id) REFERENCES materias(id) ON DELETE CASCADE,
    FOREIGN KEY (carrera_id) REFERENCES carreras(id) ON DELETE CASCADE,
    INDEX idx_carrera_semestre (carrera_id, semestre),
    INDEX idx_materia (materia_id),
    UNIQUE KEY uk_materia_carrera_semestre (materia_id, carrera_id, semestre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
