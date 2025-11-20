-- Migración: Actualizar tabla carreras existente
-- Agregar columnas faltantes a la tabla carreras

-- Agregar columna descripcion si no existe
ALTER TABLE carreras 
ADD COLUMN IF NOT EXISTS descripcion TEXT AFTER nombre;

-- Agregar columna duracion_semestres si no existe
ALTER TABLE carreras 
ADD COLUMN IF NOT EXISTS duracion_semestres INT DEFAULT 9 AFTER descripcion;

-- Agregar columna creditos_totales si no existe
ALTER TABLE carreras 
ADD COLUMN IF NOT EXISTS creditos_totales INT DEFAULT 240 AFTER duracion_semestres;

-- Agregar columna activo si no existe
ALTER TABLE carreras 
ADD COLUMN IF NOT EXISTS activo TINYINT(1) DEFAULT 1 AFTER creditos_totales;

-- Agregar timestamps si no existen
ALTER TABLE carreras 
ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE carreras 
ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Actualizar datos existentes con descripciones por defecto
UPDATE carreras SET 
    descripcion = CASE 
        WHEN clave = 'ISC' OR clave = 'IC' THEN 'Profesionista capaz de diseñar, desarrollar e implementar sistemas computacionales aplicando las metodologías y tecnologías más recientes.'
        WHEN clave = 'II' THEN 'Profesionista capaz de diseñar, implementar y mejorar sistemas de producción de bienes y servicios.'
        WHEN clave = 'IGE' THEN 'Profesionista capaz de diseñar, crear y dirigir organizaciones competitivas con visión estratégica.'
        WHEN clave = 'IE' THEN 'Profesionista capaz de diseñar, desarrollar e innovar sistemas electrónicos para la solución de problemas en el sector productivo.'
        WHEN clave = 'IM' THEN 'Profesionista capaz de diseñar, construir y mantener sistemas mecatrónicos innovadores.'
        WHEN clave = 'IER' THEN 'Profesionista capaz de diseñar, implementar y evaluar proyectos de energía sustentable.'
        WHEN clave = 'CP' THEN 'Profesionista capaz de diseñar, implementar y evaluar sistemas de información financiera.'
        ELSE 'Descripción no disponible'
    END,
    duracion_semestres = 9,
    creditos_totales = 240,
    activo = 1
WHERE descripcion IS NULL OR descripcion = '';

-- Crear índices si no existen
CREATE INDEX IF NOT EXISTS idx_carreras_activo ON carreras(activo);
CREATE INDEX IF NOT EXISTS idx_carreras_clave ON carreras(clave);
