<?php
/**
 * Configuración de la conexión a la base de datos
 * 
 * Este archivo contiene las constantes de conexión y establece
 * la conexión PDO con la base de datos MySQL
 */

// Definición de constantes de conexión
define('DB_HOST', 'localhost');     // Host de la base de datos
define('DB_USER', 'root');         // Usuario de la base de datos
define('DB_PASS', '12345');             // Contraseña de la base de datos
define('DB_NAME', 'taskcollab');   // Nombre de la base de datos
 
try {
    // Crear conexión PDO con opciones de configuración optimizadas
    $conn = new PDO(
        "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4", // DSN con soporte UTF-8
        DB_USER,
        DB_PASS,
        array(
            // Configuración de PDO para mejor rendimiento y seguridad
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,           // Habilita excepciones para errores
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,      // Resultados como array asociativo
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"    // Asegura codificación UTF-8
        )
    );
} catch(PDOException $e) {
    // Manejo de errores: log del error real y mensaje genérico al usuario
    error_log("Error de conexión a la base de datos: " . $e->getMessage());
    die("Lo sentimos, ha ocurrido un error al conectar con la base de datos.");
}
?> 