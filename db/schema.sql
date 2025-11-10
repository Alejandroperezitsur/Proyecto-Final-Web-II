-- SICEnet schema
CREATE TABLE IF NOT EXISTS carreras (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(120) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS alumnos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  matricula VARCHAR(9) NOT NULL UNIQUE,
  nombre VARCHAR(80) NOT NULL,
  apellido VARCHAR(80) NOT NULL,
  carrera_id INT NOT NULL,
  semestre_actual TINYINT NOT NULL DEFAULT 1,
  password_hash VARCHAR(255) NOT NULL,
  FOREIGN KEY (carrera_id) REFERENCES carreras(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS profesores (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario VARCHAR(60) NOT NULL UNIQUE,
  nombre VARCHAR(80) NOT NULL,
  apellido VARCHAR(80) NOT NULL,
  carrera_id INT NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  FOREIGN KEY (carrera_id) REFERENCES carreras(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario VARCHAR(60) NOT NULL UNIQUE,
  nombre VARCHAR(80) NOT NULL,
  password_hash VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS materias (
  id INT AUTO_INCREMENT PRIMARY KEY,
  carrera_id INT NOT NULL,
  nombre VARCHAR(120) NOT NULL,
  semestre TINYINT NOT NULL,
  unidades TINYINT NOT NULL DEFAULT 5,
  FOREIGN KEY (carrera_id) REFERENCES carreras(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS grupos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  carrera_id INT NOT NULL,
  materia_id INT NOT NULL,
  profesor_id INT NOT NULL,
  clave VARCHAR(20) NOT NULL,
  salon VARCHAR(20),
  FOREIGN KEY (carrera_id) REFERENCES carreras(id),
  FOREIGN KEY (materia_id) REFERENCES materias(id),
  FOREIGN KEY (profesor_id) REFERENCES profesores(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS periodos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(60) NOT NULL,
  activo TINYINT NOT NULL DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS horarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  grupo_id INT NOT NULL,
  dia ENUM('Lunes','Martes','Miércoles','Jueves','Viernes','Sábado') NOT NULL,
  hora_inicio TIME NOT NULL,
  hora_fin TIME NOT NULL,
  FOREIGN KEY (grupo_id) REFERENCES grupos(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS inscripciones (
  id INT AUTO_INCREMENT PRIMARY KEY,
  alumno_id INT NOT NULL,
  grupo_id INT NOT NULL,
  periodo_id INT NOT NULL,
  estatus ENUM('Aprobada','Reprobada','Cursando','Pendiente') NOT NULL DEFAULT 'Cursando',
  FOREIGN KEY (alumno_id) REFERENCES alumnos(id),
  FOREIGN KEY (grupo_id) REFERENCES grupos(id),
  FOREIGN KEY (periodo_id) REFERENCES periodos(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS calificaciones (
  id INT AUTO_INCREMENT PRIMARY KEY,
  inscripcion_id INT NOT NULL,
  unidad TINYINT NOT NULL,
  calificacion DECIMAL(5,2) NOT NULL,
  segunda_oportunidad DECIMAL(5,2) NULL,
  UNIQUE KEY uniq_insc_unidad (inscripcion_id, unidad),
  FOREIGN KEY (inscripcion_id) REFERENCES inscripciones(id)
) ENGINE=InnoDB;

-- Datos mínimos
INSERT INTO periodos (nombre, activo) VALUES ('2025-1', 1);

-- Configuraciones del sistema
CREATE TABLE IF NOT EXISTS configuraciones (
  id INT AUTO_INCREMENT PRIMARY KEY,
  clave VARCHAR(50) UNIQUE NOT NULL,
  valor VARCHAR(100) NOT NULL
);

INSERT INTO configuraciones (clave, valor) VALUES ('reinscripcion_activa', '0');