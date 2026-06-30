<?php
require_once __DIR__ . '/config_sesion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../vistas/login.php");
    exit();
}

/**
 * Cierre de sesión por inactividad.
 * Si el navegador "revive" una cookie de sesión vieja (algunos navegadores
 * la restauran al reabrir pestañas), esto la invalida igualmente si pasó
 * demasiado tiempo desde la última actividad real del usuario.
 */
$tiempoMaximoInactividad = 30 * 60; // 30 minutos

if (isset($_SESSION['ultima_actividad']) && (time() - $_SESSION['ultima_actividad'] > $tiempoMaximoInactividad)) {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    header("Location: ../vistas/login.php?expirada=1");
    exit();
}

$_SESSION['ultima_actividad'] = time();
?>