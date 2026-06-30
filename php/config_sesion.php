<?php
/**
 * Configuración centralizada de sesión.
 * 
 * Debe incluirse SIEMPRE antes de session_start() en cualquier archivo
 * que maneje sesiones (login.php, controlador.php, seguridad_sesion.php).
 *
 * Objetivo: evitar que la sesión persista después de cerrar el navegador.
 * Forzamos 'lifetime' = 0 (cookie de sesión pura, no persistente) y
 * desactivamos cualquier comportamiento de "recordar sesión" que algunos
 * navegadores aplican al restaurar pestañas.
 */

if (session_status() === PHP_SESSION_NONE) {
    // lifetime = 0 -> la cookie expira al cerrar el navegador (no es una fecha fija)
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}
?>
