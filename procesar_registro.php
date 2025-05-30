<?php
// Datos de conexión (mantén tu configuración)
$host = 'localhost';
$usuario = 'root';
$contrasena = '';
$base_datos = 'task_collab_db';

$conn = new mysqli($host, $usuario, $contrasena, $base_datos);
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

$nombre = $_POST['nombre'];
$apellido = $_POST['apellido'];
$email = $_POST['email'];
$password = password_hash($_POST['password'], PASSWORD_DEFAULT);
$fecha_registro = date('Y-m-d H:i:s');
$ultimo_acceso = $fecha_registro;

// Verificar si el correo electrónico ya existe
$sql_verificar = "SELECT email FROM usuarios WHERE email = ?";
$stmt_verificar = $conn->prepare($sql_verificar);
$stmt_verificar->bind_param("s", $email);
$stmt_verificar->execute();
$stmt_verificar->store_result();

if ($stmt_verificar->num_rows > 0) {
    echo "Este correo electrónico ya está registrado. ¿Olvidaste tu contraseña?<br><a href='loguin.html'>Acceder a TaskCollab</a>";
    // Aquí podrías añadir un enlace a la página de recuperación de contraseña
} else {
    // El correo electrónico no existe, proceder con el registro
    $sql_insertar = "INSERT INTO usuarios (nombre, apellido, email, password, fecha_registro, ultimo_acceso)
                    VALUES (?, ?, ?, ?, ?, ?)";
    $stmt_insertar = $conn->prepare($sql_insertar);
    $stmt_insertar->bind_param("ssssss", $nombre, $apellido, $email, $password, $fecha_registro, $ultimo_acceso);

    if ($stmt_insertar->execute()) {
        echo "Registro exitoso.<br><a href='loguin.html'>Acceder a TaskCollab</a>";
        // Redirigir a la página de inicio de sesión o a otra página
        // header("Location: login.html");
        // exit();
    } else {
        echo "Error al registrar usuario: " . $stmt_insertar->error."<br><a href='registro.html'>Volver a intentar</a>";
    }
    $stmt_insertar->close();
}

$stmt_verificar->close();
$conn->close();
?>
