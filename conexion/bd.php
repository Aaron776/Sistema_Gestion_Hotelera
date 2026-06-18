<?php
$env = parse_ini_file(__DIR__ . '/../.env'); // Carga las variables de entorno

$host = $env['DB_HOST'];
$dbname = $env['DB_NAME'];
$user = $env['DB_USER'];
$pass = $env['DB_PASS'];

try {
    $conexion = new PDO("pgsql:host=$host;dbname=$dbname", $user, $pass);
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Configurar zona horaria de Ecuador (Quito/Guayaquil)
    date_default_timezone_set('America/Guayaquil');
    // En PostgreSQL, se usa SET TIME ZONE en lugar de SET time_zone = ...
    $conexion->exec("SET TIME ZONE 'America/Guayaquil'");

    //echo "✅ Conexión a PostgreSQL exitosa!";
} catch (PDOException $e) {
    die("❌ Error al conectar a PostgreSQL: " . $e->getMessage());
}
