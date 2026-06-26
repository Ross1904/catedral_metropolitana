<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['usuario_id'])) {
    header("Location: inicio.php");
    exit();
}

if (isset($_SESSION['flash_mensaje'])) {
    $mensaje = $_SESSION['flash_mensaje'];
    $tipoMensaje = $_SESSION['flash_tipo'];
    $vistaActual = $_SESSION['flash_vista'] ?? 'vista-login';
    unset($_SESSION['flash_mensaje'], $_SESSION['flash_tipo'], $_SESSION['flash_vista']);
} else {
    $mensaje = '';
    $tipoMensaje = '';
    $vistaActual = 'vista-login';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Catedral</title>
    <link rel="stylesheet" href="../recursos/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="../recursos/css/estilos.css?v=<?=time() ?>">
</head>
<body>

    <div id="notificacion">Mensaje del sistema</div>

    <div class="contenedor-principal">
        <div class="panel-lateral">
            <div style="text-align: center; margin-bottom: 30px;">
                
                <style>
                    .icono-flotante-login {
                        font-size: 4rem;
                        color: var(--acento-dorado);
                        margin-bottom: 15px;
                        animation: flotarLogin 3s ease-in-out infinite;
                        filter: drop-shadow(0 0 18px rgba(235, 206, 78, 0.87));
                    }
                    
                    @keyframes flotarLogin {
                        0% { transform: translateY(0px); }
                        50% { transform: translateY(-12px); }
                        100% { transform: translateY(0px); }
                    }
                    
                    .titulo-login-oficial {
                        color: var(--acento-dorado) !important;
                        font-size: 1.8rem;
                        font-weight: 700;
                        text-transform: uppercase;
                        letter-spacing: 1px;
                        margin-bottom: 15px;
                        
                        -webkit-text-stroke: 1px rgba(188, 152, 146, 0.56);
                        text-shadow: 0 0 15px rgba(236, 227, 178, 0.9) !important;
                    }
                    
                    .subtitulo-login-oficial {
                        color: var(--acento-dorado) !important; 
                        font-size: 0.95rem;
                        font-weight: 900; 
                        text-transform: uppercase;
                        letter-spacing: 3px;
                        opacity: 1 !important;
                        margin-bottom: 0;
                        
                        -webkit-text-stroke: 0.5px rgba(188, 152, 146, 0.56);
                        text-shadow: 0 0 10px rgba(236, 227, 178, 0.9) !important;
                    }
                </style>
                
                <i class="fas fa-church icono-flotante-login"></i>
                <h2 class="titulo-login-oficial">Santo Tomás Apóstol</h2>
                <p class="subtitulo-login-oficial">Gestión Parroquial</p>
                
            </div>
        </div>

<div class="panel-formulario">
            
            <div id="vista-login" class="vista-centrada activa">
                <h2>Iniciar Sesión</h2>
                <form action="../php/controlador.php" method="POST" autocomplete="off">
                    <input type="hidden" name="accion" value="login">

                    <div class="grupo-entrada">
                        <label for="usuario">Usuario</label>
                        <input type="text" id="usuario" name="usuario" required>
                    </div>

                    <div class="grupo-entrada">
                        <label for="pass-login">Contraseña</label>
                        <div class="contenedor-clave">
                            <input type="password" name="clave" id="pass-login" class="input-respuesta" required placeholder="Ingrese su contraseña">
                            <i class="fas fa-eye icono-ver-clave" onclick="toggleclave('pass-login', this)" title="Mostrar contraseña"></i>
                        </div>
                    </div>

                    <button type="submit" class="boton boton-primario">Ingresar</button>
                </form>
                <div class="enlaces">
                    <a href="#" onclick="mostrarVista('vista-recuperar-1'); return false;">¿Olvidó su clave?</a>
                    <a href="#" onclick="mostrarVista('vista-registro'); return false;">Registrarse</a>
                </div>
            </div>

            <div id="vista-registro" class="vista-scroll">
                <h2>Nueva Cuenta</h2>
                <form id="formulario-registro" method="POST" action="../php/controlador.php" autocomplete="off">
                    <input type="hidden" name="accion" value="registro">
                    
                    <div class="grupo-entrada">
                        <input type="text" name="reg-nombre" placeholder="Nombre Completo" required autocomplete="off">
                    </div>
                    <div class="grupo-entrada">
                        <input type="text" name="reg-usuario" id="reg-usuario" placeholder="Usuario (ej. juanperez)" required autocomplete="off">
                    </div>
                    <div class="grupo-entrada">
                        <div class="contenedor-clave">
                            <input type="password" name="reg-contrasena" id="reg-contrasena" class="input-respuesta" placeholder="Contraseña" required autocomplete="new-password">
                            <i class="fas fa-eye icono-ver-clave" onclick="toggleclave('reg-contrasena', this)" title="Mostrar contraseña"></i>
                        </div>
                    </div>

                    <div class="seccion-seguridad" style="border:none; margin-top:0; padding-top:0;">
                        <div id="area-preguntas"></div>
                    </div>

                    <div style="display:flex; gap:10px; margin-top:15px;">
                        <button type="button" class="boton boton-gris" onclick="mostrarVista('vista-login')">Cancelar</button>
                        <button type="button" id="btn-accion-registro" class="boton boton-primario">Siguiente</button>
                    </div>
                </form>
            </div>

            <div id="vista-recuperar-1" class="vista-centrada">
                <h2>Recuperar Cuenta</h2>
                <form method="POST" action="../php/controlador.php" autocomplete="off">
                    <input type="hidden" name="accion" value="recuperar-1">
                    <div class="grupo-entrada">
                        <input type="text" name="rec-usuario" placeholder="Usuario" required autocomplete="off">
                    </div>
                    <button type="submit" class="boton boton-primario">Buscar</button>
                    <button type="button" class="boton boton-gris" onclick="mostrarVista('vista-login')">Cancelar</button>
                </form>
            </div>

            <div id="vista-recuperar-2" class="vista-centrada"> <h2>Seguridad</h2>
                <p style="text-align:center; font-size:0.9rem; margin-bottom:15px; color: var(--primario-oscuro);">Responda las preguntas:</p>
                <form method="POST" action="../php/controlador.php" autocomplete="off">
                    <input type="hidden" name="accion" value="recuperar-2">
                    
                    <div id="area-verificar-preguntas" style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <?php if(isset($_SESSION['recup_data'])): 
                            foreach($_SESSION['recup_data'] as $idx => $dato): ?>
                                <div style="padding: 10px; background: rgba(26, 34, 56, 0.03); border: 1px solid rgba(26, 34, 56, 0.1); border-radius: 8px;">
                                    <label class="etiqueta-pregunta" style="border: none; padding: 0; margin-bottom: 5px; font-size: 0.75rem;"><?php echo htmlspecialchars($dato['pregunta']); ?></label>
                                    <input type="text" name="verificar-respuesta-<?php echo $idx; ?>" class="input-respuesta" style="margin-bottom: 0; padding: 8px; font-size: 0.85rem;" placeholder="Respuesta" required autocomplete="off">
                                </div>
                            <?php endforeach; 
                        endif; ?>
                    </div>

                    <div style="display:flex; gap:10px; margin-top:15px;">
                        <button type="button" class="boton boton-gris" onclick="mostrarVista('vista-login')">Cancelar</button>
                        <button type="submit" class="boton boton-secundario">Verificar</button>
                    </div>
                </form>
            </div>

            <div id="vista-recuperar-3" class="vista-centrada">
                <h2>Nueva Clave</h2>
                <form id="formulario-recuperar-3" method="POST" action="../php/controlador.php" autocomplete="off">
                    <input type="hidden" name="accion" value="recuperar-3">
                    <div class="grupo-entrada">
                        <div class="contenedor-clave">
                            <input type="password" name="nueva-contrasena" id="nueva-contrasena" class="input-respuesta" placeholder="Nueva contraseña" required autocomplete="new-password">
                            <i class="fas fa-eye icono-ver-clave" onclick="toggleclave('nueva-contrasena', this)" title="Mostrar contraseña"></i>
                        </div>
                    </div>
                    <div class="grupo-entrada">
                        <div class="contenedor-clave">
                            <input type="password" name="confirmar-contrasena" id="confirmar-contrasena" class="input-respuesta" placeholder="Repetir contraseña" required autocomplete="new-password">
                            <i class="fas fa-eye icono-ver-clave" onclick="toggleclave('confirmar-contrasena', this)" title="Mostrar contraseña"></i>
                        </div>
                    </div>
                    <button type="submit" class="boton boton-primario">Cambiar</button>
                </form>
            </div>
            
        </div>
    </div>

    <script>
        const mensajeSistema = <?php echo json_encode($mensaje); ?>;
        const tipoMensaje = <?php echo json_encode($tipoMensaje); ?>;
        const vistaInicial = <?php echo json_encode($vistaActual); ?>;
    </script>
    <script src="../recursos/js/main.js?v=3.0"></script>
</body>
</html>