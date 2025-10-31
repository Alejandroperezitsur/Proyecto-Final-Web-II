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
    'url' => 'http://localhost/Proyecto-Final-Web-II/public',
        'timezone' => 'America/Mexico_City',
        'charset' => 'UTF-8',
        'debug' => true // Cambiar a false en producción
    ],
    'security' => [
        'session_timeout' => 3600, // 1 hora
        'csrf_token_name' => 'csrf_token',
        'upload_max_size' => 5242880, // 5MB
        'allowed_extensions' => ['jpg', 'jpeg', 'png']
    ]
];