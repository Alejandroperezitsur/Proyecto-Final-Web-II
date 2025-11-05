<?php
return [
    'db' => [
        // Ajustado para XAMPP: root sin contraseña por defecto
        'host' => '127.0.0.1:3306',
        'name' => 'control_escolar',
        'user' => 'root',
        'pass' => 'root'
    ],
    'app' => [
        'name' => 'Control Escolar',
    // URL sugerida para acceder con XAMPP (colocar carpeta del proyecto en htdocs)
    'url' => 'http://localhost/PWBII/Proyecto-Final-Web-II/public',
        'timezone' => 'America/Mexico_City',
        'charset' => 'UTF-8',
        'debug' => true // Cambiar a false en producción
    ],
    'academic' => [
        // Ventanas administrables de reinscripción (editar aquí según necesidad)
        'reinscripcion_windows' => [
            'enero' => [ 'inicio_dia' => 10, 'fin_dia' => 14, 'mes' => 'Enero' ],
            'agosto' => [ 'inicio_dia' => 10, 'fin_dia' => 14, 'mes' => 'Agosto' ],
        ],
        // Estatus mostrado por defecto para alumnos
        'estatus_alumno_default' => 'Inscrito',
        // Cupo por grupo por defecto (sin migración de esquema)
        'cupo_grupo_default' => 30
    ],
    'security' => [
        'session_timeout' => 3600, // 1 hora
        'csrf_token_name' => 'csrf_token',
        'upload_max_size' => 5242880, // 5MB
        'allowed_extensions' => ['jpg', 'jpeg', 'png']
    ],
    // Toggles de módulos visibles en el header/nav
    'modules' => [
        'dashboard' => true,
        'alumnos' => true,
        'profesores' => true,
        'materias' => true,
        'grupos' => true,
        'calificaciones' => true,
        'kardex' => true,
        'mi_carga' => true,
        'reticula' => true,
        'reinscripcion' => true,
        'monitoreo_grupos' => true
    ]
];