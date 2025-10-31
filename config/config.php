<?php
return [
    'db' => [
        'host' => 'localhost',
        'name' => 'control_escolar',
        'user' => 'root',
        'pass' => ''
    ],
    'app' => [
        'name' => 'Control Escolar',
        'url' => 'http://localhost/controlescolar',
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