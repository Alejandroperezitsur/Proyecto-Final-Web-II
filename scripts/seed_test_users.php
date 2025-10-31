<?php
// Seed script: crea 40 alumnos y 20 profesores con datos y contraseñas únicas
// y genera un archivo HTML en public/test_users.html para verlos.

require_once __DIR__ . '/../config/db.php';

function getPdo() {
    $db = Database::getInstance();
    return $db->getConnection();
}

function randomMatricula(PDO $pdo, array $prefixes, array $existing = []) {
    // Genera una matrícula tipo [SICMQEA][8 dígitos], asegurando que no exista ya
    while (true) {
        $prefix = $prefixes[random_int(0, count($prefixes) - 1)];
        $num = str_pad((string)random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
        $mat = $prefix . $num;
        if (isset($existing[$mat])) {
            continue;
        }
        // Verificar unicidad en ambas tablas
        $stmt1 = $pdo->prepare("SELECT COUNT(*) FROM alumnos WHERE matricula = :m");
        $stmt1->execute([':m' => $mat]);
        $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE matricula = :m");
        $stmt2->execute([':m' => $mat]);
        if ($stmt1->fetchColumn() == 0 && $stmt2->fetchColumn() == 0) {
            return $mat;
        }
    }
}

function randomPassword8Digits(array $used) {
    // Contraseña de 8 dígitos, diferente para cada usuario
    while (true) {
        $pw = (string)random_int(10000000, 99999999);
        if (!isset($used[$pw])) {
            return $pw;
        }
    }
}

function pickRandom(&$arr) {
    return $arr[random_int(0, count($arr) - 1)];
}

function seedAlumnos(PDO $pdo, int $count, array $prefixes, array &$usedPw) {
    $nombres = ['Juan','María','Luis','Ana','Carlos','Lucía','Miguel','Sofía','Pedro','Valeria','Jorge','Camila','Diego','Paula','Andrés','Daniela','Hugo','Isabel','Raúl','Gabriela','Fernando','Carmen','Rubén','Irene','Manuel','Rocío','Óscar','Alicia','Iván','Sara','Emilio','Patricia','Adrián','Elena','Sergio','Marta','Nicolás','Olga','Ángel','Noelia'];
    $apellidos = ['García','Martínez','López','Hernández','González','Pérez','Rodríguez','Sánchez','Ramírez','Cruz','Flores','Rivera','Torres','Vargas','Castro','Rojas','Morales','Guerrero','Jiménez','Navarro','Vázquez','Domínguez','Díaz','Silva','Mendoza','Ortega','Ramos','Núñez','Delgado','Aguilar'];

    $created = [];

    $sql = "INSERT INTO alumnos (matricula, nombre, apellido, email, password) VALUES (:mat, :nom, :ape, :email, :pw)";
    $stmt = $pdo->prepare($sql);

    $existingMat = [];
    for ($i = 0; $i < $count; $i++) {
        $nombre = pickRandom($nombres);
        $apellido = pickRandom($apellidos);
        $matricula = randomMatricula($pdo, $prefixes, $existingMat);
        $existingMat[$matricula] = true;
        $email = strtolower($nombre . '.' . $apellido . $i . '@alumnos.test');
        $pwPlain = randomPassword8Digits($usedPw);
        $usedPw[$pwPlain] = true;
        $pwHash = password_hash($pwPlain, PASSWORD_DEFAULT);

        $stmt->execute([
            ':mat' => $matricula,
            ':nom' => $nombre,
            ':ape' => $apellido,
            ':email' => $email,
            ':pw' => $pwHash,
        ]);

        $created[] = [
            'tipo' => 'alumno',
            'matricula' => $matricula,
            'nombre' => $nombre,
            'apellido' => $apellido,
            'email' => $email,
            'password' => $pwPlain,
        ];
    }

    return $created;
}

function seedProfesores(PDO $pdo, int $count, array $prefixes, array &$usedPw) {
    $nombres = ['Alberto','Beatriz','César','Diana','Eduardo','Fátima','Gustavo','Helena','Ismael','Julia','Kevin','Lorena','Mauricio','Nadia','Octavio','Patricia','Quintín','Rafael','Sofía','Tomás','Ulises','Verónica','Wendy','Xavier','Yolanda','Zacarías'];
    $apellidos = ['Alonso','Benítez','Carrillo','Durán','Escobar','Fernández','Gómez','Herrera','Ibarra','Juárez','Kuri','León','Montoya','Nieves','Ochoa','Pacheco','Quintero','Robles','Salazar','Téllez','Uribe','Velasco','Williams','Xochihua','Yáñez','Zepeda'];

    $created = [];

    $sql = "INSERT INTO usuarios (matricula, email, password, rol, activo) VALUES (:mat, :email, :pw, 'profesor', 1)";
    $stmt = $pdo->prepare($sql);

    $existingMat = [];
    for ($i = 0; $i < $count; $i++) {
        $nombre = pickRandom($nombres);
        $apellido = pickRandom($apellidos);
        $matricula = randomMatricula($pdo, $prefixes, $existingMat);
        $existingMat[$matricula] = true;
        $local = strtolower($nombre . '.' . $apellido . $i);
        $email = $local . '@profes.test';
        $pwPlain = randomPassword8Digits($usedPw);
        $usedPw[$pwPlain] = true;
        $pwHash = password_hash($pwPlain, PASSWORD_DEFAULT);

        $stmt->execute([
            ':mat' => $matricula,
            ':email' => $email,
            ':pw' => $pwHash,
        ]);

        $created[] = [
            'tipo' => 'profesor',
            'matricula' => $matricula,
            'nombre' => $nombre, // no se almacena en DB, solo informativo
            'apellido' => $apellido, // no se almacena en DB, solo informativo
            'email' => $email,
            'password' => $pwPlain,
        ];
    }

    return $created;
}

function buildHtml(array $alumnos, array $profesores) {
    $alumnoRows = [];
    foreach ($alumnos as $a) {
        $alumnoRows[] = "            <tr>\n"
            . "                <td>" . htmlspecialchars($a['matricula']) . "</td>\n"
            . "                <td>" . htmlspecialchars($a['nombre']) . "</td>\n"
            . "                <td>" . htmlspecialchars($a['apellido']) . "</td>\n"
            . "                <td>" . htmlspecialchars($a['email']) . "</td>\n"
            . "                <td>" . htmlspecialchars($a['password']) . "</td>\n"
            . "            </tr>";
    }

    $profRows = [];
    foreach ($profesores as $p) {
        $profRows[] = "            <tr>\n"
            . "                <td>" . htmlspecialchars($p['matricula']) . "</td>\n"
            . "                <td>" . htmlspecialchars($p['email']) . "</td>\n"
            . "                <td>" . htmlspecialchars($p['password']) . "</td>\n"
            . "            </tr>";
    }

    $rowsAlumnos = implode("\n", $alumnoRows);
    $rowsProfes = implode("\n", $profRows);

    $alumnosCount = count($alumnos);
    $profCount = count($profesores);

    $html = <<<HTML
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Credenciales de prueba</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        table { width: 100%; border-collapse: collapse }
        th, td { border: 1px solid #ddd; padding: 8px }
        th { background: #f5f5f5; text-align: left }
        .container { padding: 1rem }
    </style>
    
</head>
<body>
    <div class="container">
        <h1>Usuarios de prueba</h1>
        <p>Contraseñas de 8 dígitos generadas para pruebas. No usar en producción.</p>

        <h2>Alumnos ($alumnosCount)</h2>
        <table>
            <thead>
                <tr>
                    <th>Matrícula</th>
                    <th>Nombre</th>
                    <th>Apellido</th>
                    <th>Email</th>
                    <th>Contraseña</th>
                </tr>
            </thead>
            <tbody>
$rowsAlumnos
            </tbody>
        </table>

        <h2 style="margin-top: 2rem">Profesores ($profCount)</h2>
        <table>
            <thead>
                <tr>
                    <th>Matrícula</th>
                    <th>Email</th>
                    <th>Contraseña</th>
                </tr>
            </thead>
            <tbody>
$rowsProfes
            </tbody>
        </table>
    </div>
</body>
</html>
HTML;

    return $html;
}

function main() {
    $pdo = getPdo();
    $prefixes = ['S','I','C','M','Q','E','A'];
    $usedPw = [];

    $alumnos = seedAlumnos($pdo, 40, $prefixes, $usedPw);
    $profes = seedProfesores($pdo, 20, $prefixes, $usedPw);

    $html = buildHtml($alumnos, $profes);
    $outPath = __DIR__ . '/../public/test_users.html';
    file_put_contents($outPath, $html);

    echo "Se crearon " . count($alumnos) . " alumnos y " . count($profes) . " profesores.\n";
    echo "Archivo generado: public/test_users.html\n";
}

main();

?>