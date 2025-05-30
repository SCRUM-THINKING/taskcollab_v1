<?php
/**
 * Archivo principal de la aplicación TaskCollab
 * 
 * Este archivo sirve como punto de entrada principal de la aplicación y maneja:
 * - La configuración de seguridad de sesiones
 * - La estructura HTML principal
 * - La carga de dependencias CSS y JavaScript
 * - La interfaz de usuario base
 */

// ===== CONFIGURACIÓN DE SEGURIDAD =====
// Previene el acceso a la cookie de sesión vía JavaScript (mitiga ataques XSS)
ini_set('session.cookie_httponly', 1);
// Solo acepta cookies para el ID de sesión, no en URLs (previene session fixation)
ini_set('session.use_only_cookies', 1);   

// Habilitar cookies seguras solo en HTTPS
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);   // Cookies solo sobre HTTPS
}

// Iniciar sesión después de la configuración de seguridad
session_start();

// Incluir configuración de la base de datos
require_once 'config/database.php';

// ===== PROTECCIÓN CONTRA CSRF =====
// Genera un token único para prevenir ataques CSRF (Cross-Site Request Forgery)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Simulación de usuario para desarrollo
// TODO: Implementar sistema de autenticación real
$_SESSION['usuario_id'] = 1;
$_SESSION['username'] = 'Demo';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskCollab - Lista de Tareas Colaborativa</title>
    
    <!-- ===== DEPENDENCIAS CSS EXTERNAS =====
         Bootstrap: Framework CSS para el diseño responsive
         Font Awesome: Biblioteca de iconos -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Estilos personalizados de la aplicación -->
    <link rel="stylesheet" href="assets/css/styles.css">
</head>

<body>
    <!-- ===== BARRA DE NAVEGACIÓN =====
         Contiene el logo, menú y información del usuario -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="#">TaskCollab</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="#">Mis Tareas</a>
                    </li>
                </ul>
                <div>
                    <span class="badge">Usuario: <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                </div>
            </div>
        </div>
    </nav>

    <!-- ===== CONTENIDO PRINCIPAL ===== -->
    <div class="container mt-4">
        <!-- Encabezado de la aplicación -->
        <div class="app-header">
            <h1 class="h3 mb-3">Lista de Tareas Colaborativa</h1>
            <p class="mb-0">Una simple aplicación para gestionar tus tareas diarias</p>
        </div>

        <!-- ===== FORMULARIO DE NUEVA TAREA =====
             Permite al usuario agregar nuevas tareas -->
        <div class="task-container mb-4">
            <form id="task-form">
                <div class="input-group">
                    <input type="text" id="task-input" class="form-control" placeholder="Añadir nueva tarea...">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Añadir
                    </button>
                </div>
            </form>
        </div>

        <!-- ===== LISTA DE TAREAS =====
             Contenedor principal para la lista de tareas y estadísticas -->
        <div class="task-container">
            <h2 class="h5 mb-3">Mis Tareas</h2>

            <!-- Panel de estadísticas -->
            <div class="task-stats mb-3">
                <div class="row text-center">
                    <div class="col">
                        <div class="bg-light p-2 rounded">
                            <div class="h4 mb-0" id="total-tasks">0</div>
                            <small>Total</small>
                        </div>
                    </div>
                    <div class="col">
                        <div class="bg-light p-2 rounded">
                            <div class="h4 mb-0" id="completed-tasks">0</div>
                            <small>Completadas</small>
                        </div>
                    </div>
                    <div class="col">
                        <div class="bg-light p-2 rounded">
                            <div class="h4 mb-0" id="pending-tasks">0</div>
                            <small>Pendientes</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lista dinámica de tareas -->
            <ul class="list-unstyled" id="task-list">
                <!-- Las tareas se cargarán dinámicamente vía JavaScript -->
            </ul>

            <!-- Estado vacío - se muestra cuando no hay tareas -->
            <div id="empty-state" class="text-center py-4">
                <i class="fas fa-clipboard-list fa-3x text-muted"></i>
                <p class="mt-2 text-muted">No hay tareas pendientes. ¡Añade una nueva tarea!</p>
            </div>
        </div>
    </div>

    <!-- ===== SCRIPTS ===== -->
    <!-- Bootstrap JS - Necesario para componentes interactivos -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    
    <!-- Configuración de seguridad y variables globales -->
    <script>
        // Variables globales para AJAX y seguridad
        const USUARIO_ID = <?php echo $_SESSION['usuario_id']; ?>;
        const CSRF_TOKEN = '<?php echo $_SESSION['csrf_token']; ?>';
        
        // Interceptor para agregar token CSRF a todas las peticiones fetch
        // Esto asegura que todas las peticiones AJAX incluyan el token CSRF
        const originalFetch = window.fetch;
        window.fetch = function() {
            let [resource, config] = arguments;
            if(config === undefined) {
                config = {};
            }
            if(config.headers === undefined) {
                config.headers = {};
            }
            config.headers['X-CSRF-Token'] = CSRF_TOKEN;
            return originalFetch(resource, config);
        };
    </script>

    <!-- Lógica principal de la aplicación -->
    <script src="assets/js/app.js"></script>

    <!-- Manejador global de errores -->
    <script>
        window.onerror = function(msg, url, line) {
            console.error('Error: ' + msg + '\nURL: ' + url + '\nLine: ' + line);
            return false;
        };
    </script>
</body>
</html> 