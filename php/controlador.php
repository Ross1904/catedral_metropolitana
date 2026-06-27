<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

require_once '../configuracion/conexion.php';

$mensaje = '';
$tipoMensaje = '';
$vistaActual = 'vista-login';

//LOGOUT
if (isset($_GET['logout'])) {
    $_SESSION = array();

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    session_destroy();
    
    header("Location: ../vistas/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    $accion = $_POST['accion'];

    if ($_POST['accion'] === 'marcar-notificacion-vista') {
        $idNotif = (int)$_POST['id'];
        $idUsuario = $_SESSION['usuario_id'];
        $stmt = $pdo->prepare("UPDATE registro_actividad SET visto = CONCAT(IFNULL(visto,''), ',', ?) WHERE id = ? AND FIND_IN_SET(?, IFNULL(visto,'')) = 0");
        $stmt->execute([$idUsuario, $idNotif, $idUsuario]);
        exit;
    }

    if ($_POST['accion'] === 'eliminar-notificacion') {
        $idNotif = (int)$_POST['id'];
        $idUsuario = $_SESSION['usuario_id'];
        $stmt = $pdo->prepare("UPDATE registro_actividad SET eliminado_por = CONCAT(IFNULL(eliminado_por,''), ',', ?) WHERE id = ? AND FIND_IN_SET(?, IFNULL(eliminado_por,'')) = 0");
        $stmt->execute([$idUsuario, $idNotif, $idUsuario]);
        exit;
    }

    if ($_POST['accion'] === 'marcar-todas-notificaciones-vistas') {
        $idUsuario = $_SESSION['usuario_id'];
        $stmt = $pdo->prepare("UPDATE registro_actividad SET visto = CONCAT(IFNULL(visto,''), ',', ?) WHERE FIND_IN_SET(?, IFNULL(visto,'')) = 0");
        $stmt->execute([$idUsuario, $idUsuario]);
        exit;
    }

    if ($_POST['accion'] === 'eliminar-todas-notificaciones') {
        $idUsuario = $_SESSION['usuario_id'];
        $stmt = $pdo->prepare("UPDATE registro_actividad SET eliminado_por = CONCAT(IFNULL(eliminado_por,''), ',', ?) WHERE FIND_IN_SET(?, IFNULL(eliminado_por,'')) = 0");
        $stmt->execute([$idUsuario, $idUsuario]);
        exit;
    }


    try {
        if ($accion === 'perfil-cambiar-usuario') {
            $idUsr = $_SESSION['usuario_id'];
            $nuevoNombre = trim($_POST['nuevo-nombre']);
            $nuevoUsuario = trim($_POST['nuevo-usuario']);
            $passActual = $_POST['clave-actual'];

            $stmt = $pdo->prepare("SELECT usuario, clave FROM usuarios WHERE id = ?");
            $stmt->execute([$idUsr]);
            $usrData = $stmt->fetch();

            if (!$usrData || !password_verify($passActual, $usrData['clave'])) {
                throw new Exception("La contraseña actual es incorrecta. No se guardaron los cambios.");
            }

            $stmtCheck = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = ? AND id != ?");
            $stmtCheck->execute([$nuevoUsuario, $idUsr]);
            if ($stmtCheck->rowCount() > 0) {
                throw new Exception("El nombre de usuario de acceso '$nuevoUsuario' ya está en uso por otra persona.");
            }

            $stmtUpd = $pdo->prepare("UPDATE usuarios SET nombre = ?, usuario = ? WHERE id = ?");
            $stmtUpd->execute([$nuevoNombre, $nuevoUsuario, $idUsr]);

            $_SESSION['usuario_nombre'] = $nuevoNombre;

            $pdo->query("INSERT INTO registro_actividad (id_usuario, modulo, accion) VALUES ($idUsr, 'Perfil', 'Actualizó sus datos de usuario.')");

            $_SESSION['modulo_activo'] = 'mod-perfil';
            $_SESSION['alerta_mensaje'] = "Datos de usuario actualizados con éxito.";
            $_SESSION['alerta_tipo'] = "exito";
            header("Location: ../vistas/inicio.php"); exit;
        }

        if ($accion === 'perfil-cambiar-clave') {
            $idUsr = $_SESSION['usuario_id'];
            $passActual = $_POST['clave-actual'];
            $nuevaPass = $_POST['nueva-clave'];
            $confPass = $_POST['confirmar-clave'];

            if ($nuevaPass !== $confPass) {
                throw new Exception("Las contraseñas nuevas no coinciden.");
            }

            $stmt = $pdo->prepare("SELECT clave FROM usuarios WHERE id = ?");
            $stmt->execute([$idUsr]);
            $usrData = $stmt->fetch();

            if (!$usrData || !password_verify($passActual, $usrData['clave'])) {
                throw new Exception("La contraseña actual es incorrecta.");
            }

            if (password_verify($nuevaPass, $usrData['clave'])) {
                throw new Exception("La nueva contraseña no puede ser igual a la anterior.");
            }

            $hashNuevo = password_hash($nuevaPass, PASSWORD_DEFAULT);
            $stmtUpd = $pdo->prepare("UPDATE usuarios SET clave = ? WHERE id = ?");
            $stmtUpd->execute([$hashNuevo, $idUsr]);

            $pdo->query("INSERT INTO registro_actividad (id_usuario, modulo, accion) VALUES ($idUsr, 'Perfil', 'Cambió su contraseña de acceso.')");

            $_SESSION['modulo_activo'] = 'mod-perfil';
            $_SESSION['alerta_mensaje'] = "Contraseña actualizada por seguridad.";
            $_SESSION['alerta_tipo'] = "exito";
            header("Location: ../vistas/inicio.php"); exit;
        }

        //CAMBIAR PREGUNTAS DE SEGURIDAD
        if ($accion === 'perfil-cambiar-preguntas') {
            $idUsr = $_SESSION['usuario_id'];
            $passActual = $_POST['clave-actual'];

            $stmt = $pdo->prepare("SELECT clave FROM usuarios WHERE id = ?");
            $stmt->execute([$idUsr]);
            $usrData = $stmt->fetch();
            if (!$usrData || !password_verify($passActual, $usrData['clave'])) {
                throw new Exception("La contraseña actual es incorrecta.");
            }

            $stmtDel = $pdo->prepare("DELETE FROM preguntas_seguridad WHERE id_usuario = ?");
            $stmtDel->execute([$idUsr]);

            $stmtIns = $pdo->prepare("INSERT INTO preguntas_seguridad (id_usuario, pregunta, respuesta) VALUES (?, ?, ?)");
            for ($i = 1; $i <= 4; $i++) {
                if (!empty($_POST["preg$i"]) && !empty($_POST["resp$i"])) {
                    $respEncriptada = password_hash(strtolower(trim($_POST["resp$i"])), PASSWORD_DEFAULT);
                    $stmtIns->execute([$idUsr, trim($_POST["preg$i"]), $respEncriptada]);
                }
            }

            $pdo->query("INSERT INTO registro_actividad (id_usuario, modulo, accion) VALUES ($idUsr, 'Perfil', 'Actualizó sus preguntas de seguridad.')");

            $_SESSION['modulo_activo'] = 'mod-perfil';
            $_SESSION['alerta_mensaje'] = "Preguntas de seguridad actualizadas.";
            $_SESSION['alerta_tipo'] = "exito";
            header("Location: ../vistas/inicio.php"); exit;
        }

        if ($accion === 'registro') {
            $nombre  = trim($_POST['reg-nombre']);
            $usuario = trim($_POST['reg-usuario']);
            $pass    = $_POST['reg-contrasena'];

            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = ?");
            $stmt->execute([$usuario]);
            if ($stmt->fetch()) {
                throw new Exception("El usuario '$usuario' ya está ocupado.");
            }

            try {
                $pdo->beginTransaction();

                //LOGICA NIVEL DE USUARIO
                $stmtCount = $pdo->query("SELECT COUNT(id) FROM usuarios WHERE id_rol = 3");
                $totalAdministradores = $stmtCount->fetchColumn();
                
                $rolAsignado = ($totalAdministradores == 0) ? 3 : 1;

                $passHash = password_hash($pass, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, usuario, clave, id_rol) VALUES (?, ?, ?, ?)");
                $stmt->execute([$nombre, $usuario, $passHash, $rolAsignado]);
                $userId = $pdo->lastInsertId();

                $stmtPreg = $pdo->prepare("INSERT INTO preguntas_seguridad (id_usuario, pregunta, respuesta) VALUES (?, ?, ?)");
                
                $preguntasExitosas = 0;
                for ($i = 1; $i <= 4; $i++) {
                    if (!empty($_POST["pregunta$i"]) && !empty(trim($_POST["respuesta$i"]))) {
                        $preg = $_POST["pregunta$i"];
                        $resp = password_hash(strtolower(trim($_POST["respuesta$i"])), PASSWORD_DEFAULT);
                        $stmtPreg->execute([$userId, $preg, $resp]);
                        $preguntasExitosas++;
                    }
                }

                if ($preguntasExitosas < 4) {
                    throw new Exception("Inconsistencia de datos: Faltan preguntas de seguridad.");
                }

                $pdo->commit();

                $_SESSION['flash_mensaje'] = "¡Cuenta creada! Por favor inicia sesión.";
                $_SESSION['flash_tipo'] = "exito";
                $_SESSION['flash_vista'] = "vista-login";
                
                header("Location: ../vistas/login.php");
                exit;

            } catch (Exception $e) {
                $pdo->rollBack();
                throw new Exception("Error al registrar: " . $e->getMessage());
            }
        }

        //LOGIN
        elseif ($accion === 'login') {
            $usuario = trim($_POST['usuario']);
            $clave_ingresada = $_POST['clave'];

            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE usuario = ?");
            $stmt->execute([$usuario]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($clave_ingresada, $user['clave'])) {
                

                if (isset($user['estado']) && $user['estado'] === 'Inactivo') {
                    throw new Exception("Acceso denegado. Esta cuenta ha sido suspendida por la administración.");
                }
                
                session_regenerate_id(true); 
                
                $_SESSION['usuario_id'] = $user['id'];
                $_SESSION['usuario_nombre'] = $user['nombre'];
                
                //ASIGNACION DE ROL AL ENTRAR

                $rol = $user['id_rol']; 
                $_SESSION['usuario_rol'] = $rol;
                
                if ($rol == 3) {
                    $_SESSION['rol_nombre'] = 'Administrador';
                } elseif ($rol == 2) {
                    $_SESSION['rol_nombre'] = 'Secretario';
                } else {
                    $_SESSION['rol_nombre'] = 'Ciudadano';
                }
                
                header("Location: ../vistas/inicio.php");
                exit;
            } else {
                throw new Exception("Usuario o contraseña incorrectos.");
            }
        }

        //RECUPERAR CONTRASEÑA
        elseif ($accion === 'recuperar-1') {
            $usuario = trim($_POST['rec-usuario']);
            $stmt = $pdo->prepare("SELECT u.id, p.pregunta, p.respuesta FROM usuarios u JOIN preguntas_seguridad p ON u.id = p.id_usuario WHERE u.usuario = ?");
            $stmt->execute([$usuario]);
            $resultados = $stmt->fetchAll();

            if ($resultados) {
                $_SESSION['recup_id_usuario'] = $resultados[0]['id'];
                $_SESSION['recup_data'] = $resultados; 
                
                $_SESSION['flash_mensaje'] = "Usuario encontrado. Responda sus preguntas de seguridad.";
                $_SESSION['flash_tipo'] = "info";
                $_SESSION['flash_vista'] = "vista-recuperar-2";
                
                header("Location: ../vistas/login.php");
                exit;
            } else {
                throw new Exception("El usuario no existe.");
            }
        }

        elseif ($accion === 'recuperar-2') {
            $datos = $_SESSION['recup_data'] ?? [];
            $todoCorrecto = true;
            foreach ($datos as $index => $row) {
                $respuestaUsuario = strtolower(trim($_POST["verificar-respuesta-$index"]));
                if (!password_verify($respuestaUsuario, $row['respuesta'])) {
                    $todoCorrecto = false; break;
                }
            }
            if ($todoCorrecto) {
                $_SESSION['flash_mensaje'] = "Respuestas correctas. Ingrese su nueva contraseña.";
                $_SESSION['flash_tipo'] = "exito";
                $_SESSION['flash_vista'] = "vista-recuperar-3";
                
                header("Location: ../vistas/login.php");
                exit;
            } else {
                throw new Exception("Respuestas incorrectas.");
            }
        }

        elseif ($accion === 'recuperar-3') {
            $p1 = $_POST['nueva-contrasena'];
            $p2 = $_POST['confirmar-contrasena'];
            
            if ($p1 !== $p2) throw new Exception("Las contraseñas no coinciden.");
            if ($p1 !== $p2) {
                throw new Exception("Las contraseñas no coinciden.");
            }
            
            $idUsuarioRecuperacion = $_SESSION['recup_id_usuario'];
            
            $stmtActual = $pdo->prepare("SELECT clave FROM usuarios WHERE id = ?");
            $stmtActual->execute([$idUsuarioRecuperacion]);
            $usuarioActual = $stmtActual->fetch();

            if ($usuarioActual && password_verify($p1, $usuarioActual['clave'])) {
                throw new Exception("Por seguridad, la nueva contraseña no puede ser igual a la actual.");
            }
            
            $stmt = $pdo->prepare("UPDATE usuarios SET clave = ? WHERE id = ?");
            $stmt->execute([password_hash($p1, PASSWORD_DEFAULT), $idUsuarioRecuperacion]);

            unset($_SESSION['recup_id_usuario'], $_SESSION['recup_data']);
            
            $_SESSION['flash_mensaje'] = "¡Cambio de contraseña exitoso! Ya puede iniciar sesión.";
            $_SESSION['flash_tipo'] = "exito";
            $_SESSION['flash_vista'] = "vista-login";
            
            header("Location: ../vistas/login.php");
            exit;
        }
        elseif ($accion === 'nueva-actividad') {
            if (!isset($_SESSION['usuario_id'])) {
                throw new Exception("Debe iniciar sesión para realizar esta acción.");
            }

            $nombreActividad = trim($_POST['act-nombre']);
            $categoria       = $_POST['act-categoria'];
            $diasReunion     = trim($_POST['act-dias']);
            $horaInicio      = $_POST['act-hora'];
            $lugar           = trim($_POST['act-lugar']);
            $idUsuario       = $_SESSION['usuario_id'];

            if (empty($nombreActividad) || empty($categoria) || empty($diasReunion) || empty($horaInicio)) {
                throw new Exception("Por favor, complete todos los campos obligatorios.");
            }

            $stmt = $pdo->prepare("INSERT INTO actividades_pastorales (nombre_actividad, categoria, dias_reunion, hora_inicio, lugar, id_usuario_encargado) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nombreActividad, $categoria, $diasReunion, $horaInicio, $lugar, $idUsuario]);

            $stmtBitacora = $pdo->prepare("INSERT INTO registro_actividad (id_usuario, modulo, accion) VALUES (?, 'Actividades Pastorales', ?)");
            $accionLog = "Registró nueva actividad: " . $nombreActividad;
            $stmtBitacora->execute([$idUsuario, $accionLog]);

            $mensaje = "¡Actividad pastoral registrada con éxito!";
            $tipoMensaje = "exito";
            $_SESSION['modulo_activo'] = 'mod-formacion'; 
            
            header("Location: ../vistas/inicio.php");
            exit;
        }

        elseif ($accion === 'editar-actividad') {
            if (!isset($_SESSION['usuario_id'])) {
                throw new Exception("Debe iniciar sesión para realizar esta acción.");
            }

            $idActividad     = $_POST['act-id'];
            $nombreActividad = trim($_POST['act-nombre']);
            $categoria       = $_POST['act-categoria'];
            $diasReunion     = trim($_POST['act-dias']);
            $horaInicio      = $_POST['act-hora'];
            $lugar           = trim($_POST['act-lugar']);
            $idUsuario       = $_SESSION['usuario_id'];

            if (empty($idActividad) || empty($nombreActividad) || empty($categoria)) {
                throw new Exception("Faltan datos obligatorios para editar.");
            }

            $stmt = $pdo->prepare("UPDATE actividades_pastorales SET nombre_actividad = ?, categoria = ?, dias_reunion = ?, hora_inicio = ?, lugar = ? WHERE id = ?");
            $stmt->execute([$nombreActividad, $categoria, $diasReunion, $horaInicio, $lugar, $idActividad]);

            $stmtBitacora = $pdo->prepare("INSERT INTO registro_actividad (id_usuario, modulo, accion) VALUES (?, 'Actividades Pastorales', ?)");
            $stmtBitacora->execute([$idUsuario, "Editó la actividad: " . $nombreActividad]);

            $_SESSION['modulo_activo'] = 'mod-formacion'; 
            
            header("Location: ../vistas/inicio.php");
            exit;
        }

        elseif ($accion === 'eliminar-actividad') {
            if (!isset($_SESSION['usuario_id'])) {
                throw new Exception("Debe iniciar sesión para realizar esta acción.");
            }

            $idActividad = $_POST['act-id'];

            if (empty($idActividad)) {
                throw new Exception("No se especificó la actividad a eliminar.");
            }

            $stmtNombre = $pdo->prepare("SELECT nombre_actividad FROM actividades_pastorales WHERE id = ?");
            $stmtNombre->execute([$idActividad]);
            $actividad = $stmtNombre->fetch();
            $nombreActividad = $actividad ? $actividad['nombre_actividad'] : "Actividad Desconocida";

            $stmtDelete = $pdo->prepare("DELETE FROM actividades_pastorales WHERE id = ?");
            $stmtDelete->execute([$idActividad]);

            $stmtBitacora = $pdo->prepare("INSERT INTO registro_actividad (id_usuario, modulo, accion) VALUES (?, 'Actividades Pastorales', ?)");
            $stmtBitacora->execute([$_SESSION['usuario_id'], "Eliminó la actividad: " . $nombreActividad]);

            $_SESSION['modulo_activo'] = 'mod-formacion'; 
            
            header("Location: ../vistas/inicio.php");
            exit;
        }

        /*MÓDULOS DEL CALENDARIO*/
        elseif ($accion === 'nueva-agenda') {
            $titulo = trim($_POST['agenda-titulo']);
            $tipo   = $_POST['agenda-tipo'];
            if ($tipo === 'Otro' && !empty($_POST['agenda-tipo-otro'])) {
                $tipo = trim($_POST['agenda-tipo-otro']);
            }
            
            $fechaInicio = $_POST['agenda-fecha'] . ' ' . $_POST['agenda-hora-inicio'] . ':00';
            $fechaFin    = $_POST['agenda-fecha'] . ' ' . $_POST['agenda-hora-fin'] . ':00';
            $idUsuario   = $_SESSION['usuario_id'];

            $stmtOverlap = $pdo->prepare("SELECT titulo_actividad FROM agenda_catedral WHERE estado != 'Cancelado' AND fecha_hora_inicio < ? AND fecha_hora_fin > ?");
            $stmtOverlap->execute([$fechaFin, $fechaInicio]);
            if ($stmtOverlap->rowCount() > 0) {
                $conflicto = $stmtOverlap->fetch();
                $_SESSION['modulo_activo'] = 'mod-calendario';
                $_SESSION['alerta_mensaje'] = "Error: Ya existe una actividad en ese horario («" . $conflicto['titulo_actividad'] . "»).";
                $_SESSION['alerta_tipo'] = "error";
                header("Location: ../vistas/inicio.php"); 
                exit;
            }

            $stmt = $pdo->prepare("INSERT INTO agenda_catedral (titulo_actividad, tipo_actividad, fecha_hora_inicio, fecha_hora_fin, estado, id_usuario_registro) VALUES (?, ?, ?, ?, 'Agendado', ?)");
            $stmt->execute([$titulo, $tipo, $fechaInicio, $fechaFin, $idUsuario]);

            $stmtBitacora = $pdo->prepare("INSERT INTO registro_actividad (id_usuario, modulo, accion) VALUES (?, 'Agenda', ?)");
            $stmtBitacora->execute([$idUsuario, "Agendó nuevo evento: " . $titulo]);

            // ¡AQUÍ FALTABAN LAS NOTIFICACIONES!
            $_SESSION['alerta_mensaje'] = "Evento agendado exitosamente en el calendario.";
            $_SESSION['alerta_tipo'] = "exito";
            header("Location: ../vistas/inicio.php"); exit;
        }

        elseif ($accion === 'editar-agenda') {
            $idAgenda = $_POST['agenda-id'];
            $titulo   = trim($_POST['agenda-titulo']);
            $tipo     = $_POST['agenda-tipo'];
            if ($tipo === 'Otro' && !empty($_POST['agenda-tipo-otro'])) {
                $tipo = trim($_POST['agenda-tipo-otro']);
            }
            $estado   = $_POST['agenda-estado'];
            $fechaInicio = $_POST['agenda-fecha'] . ' ' . $_POST['agenda-hora-inicio'] . ':00';
            $fechaFin    = $_POST['agenda-fecha'] . ' ' . $_POST['agenda-hora-fin'] . ':00';

            $stmtOverlap = $pdo->prepare("SELECT titulo_actividad FROM agenda_catedral WHERE id != ? AND estado != 'Cancelado' AND fecha_hora_inicio < ? AND fecha_hora_fin > ?");
            $stmtOverlap->execute([$idAgenda, $fechaFin, $fechaInicio]);
            if ($stmtOverlap->rowCount() > 0) {
                $conflicto = $stmtOverlap->fetch();
                $_SESSION['modulo_activo'] = 'mod-calendario';
                $_SESSION['alerta_mensaje'] = "Error: Conflicto de horario con «" . $conflicto['titulo_actividad'] . "».";
                $_SESSION['alerta_tipo'] = "error";
                header("Location: ../vistas/inicio.php"); 
                exit;
            }

            $stmt = $pdo->prepare("UPDATE agenda_catedral SET titulo_actividad = ?, tipo_actividad = ?, fecha_hora_inicio = ?, fecha_hora_fin = ?, estado = ? WHERE id = ?");
            $stmt->execute([$titulo, $tipo, $fechaInicio, $fechaFin, $estado, $idAgenda]);

            $stmtBitacora = $pdo->prepare("INSERT INTO registro_actividad (id_usuario, modulo, accion) VALUES (?, 'Agenda', ?)");
            $stmtBitacora->execute([$_SESSION['usuario_id'], "Actualizó evento de agenda: " . $titulo]);

            // ¡EL MENSAJE PERDIDO QUE HACÍA PARECER QUE EL BOTÓN ESTABA ROTO!
            $_SESSION['alerta_mensaje'] = "Los detalles del evento fueron actualizados correctamente.";
            $_SESSION['alerta_tipo'] = "exito";
            header("Location: ../vistas/inicio.php"); exit;
        }

        elseif ($accion === 'eliminar-agenda') {
            $idAgenda = $_POST['agenda-id'];

            $stmtNombre = $pdo->prepare("SELECT titulo_actividad FROM agenda_catedral WHERE id = ?");
            $stmtNombre->execute([$idAgenda]);
            $evento = $stmtNombre->fetch();
            $nombreEvento = $evento ? $evento['titulo_actividad'] : "Evento Desconocido";

            $stmtDelete = $pdo->prepare("DELETE FROM agenda_catedral WHERE id = ?");
            $stmtDelete->execute([$idAgenda]);

            $stmtBitacora = $pdo->prepare("INSERT INTO registro_actividad (id_usuario, modulo, accion) VALUES (?, 'Agenda', ?)");
            $stmtBitacora->execute([$_SESSION['usuario_id'], "Canceló/Eliminó el evento: " . $nombreEvento]);

            // ¡OTRA NOTIFICACIÓN FALTANTE!
            $_SESSION['alerta_mensaje'] = "El evento fue eliminado de la agenda.";
            $_SESSION['alerta_tipo'] = "exito";
            header("Location: ../vistas/inicio.php"); exit;
        }

        /*MODULO DE DONACIONES*/
        elseif ($accion === 'nueva-donacion') {
            $tipo       = $_POST['donacion-tipo'];
            $donante    = trim($_POST['donacion-donante']);
            $fecha      = $_POST['donacion-fecha'];
            $idUsuario  = $_SESSION['usuario_id'];

            if (empty($donante)) { $donante = "Anónimo"; }

            if ($tipo === 'Monetaria') {
                $monto = (float)$_POST['donacion-monto'];
                
                if ($monto <= 0) {
                    $_SESSION['modulo_activo'] = 'mod-donaciones'; 
                    $_SESSION['alerta_mensaje'] = "Operación denegada. El monto de una donación monetaria debe ser mayor a $0.00.";
                    $_SESSION['alerta_tipo'] = "error";
                    header("Location: ../vistas/inicio.php"); 
                    exit;
                }

                $metodo      = $_POST['donacion-metodo'];
                $referencia  = trim($_POST['donacion-referencia']) ?: "N/A";
                $descripcion = "Aporte Económico";
                $cantidad    = "N/A";
            } else {
                $monto       = 0.00;
                $metodo      = "N/A";
                $referencia  = "N/A";
                $descripcion = trim($_POST['donacion-descripcion']);
                $cantidad    = trim($_POST['donacion-cantidad']);
            }

            $stmt = $pdo->prepare("INSERT INTO donaciones (tipo_donacion, monto, donante, metodo_pago, descripcion, cantidad, referencia, fecha_donacion, id_usuario_registro) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$tipo, $monto, $donante, $metodo, $descripcion, $cantidad, $referencia, $fecha, $idUsuario]);

            $stmtBitacora = $pdo->prepare("INSERT INTO registro_actividad (id_usuario, modulo, accion) VALUES (?, 'Donaciones', ?)");
            $stmtBitacora->execute([$idUsuario, "Registró donación ($tipo) de " . $donante]);

            $_SESSION['modulo_activo'] = 'mod-donaciones'; 
            $_SESSION['alerta_mensaje'] = "Ofrenda registrada exitosamente.";
            $_SESSION['alerta_tipo'] = "exito";
            header("Location: ../vistas/inicio.php"); exit;
        }

        elseif ($accion === 'editar-donacion') {
            $id         = $_POST['donacion-id'];
            $tipo       = $_POST['donacion-tipo'];
            $donante    = trim($_POST['donacion-donante']);
            $fecha      = $_POST['donacion-fecha'];

            if (empty($donante)) { $donante = "Anónimo"; }

            if ($tipo === 'Monetaria') {
                $monto = (float)$_POST['donacion-monto'];
                
                if ($monto <= 0) {
                    $_SESSION['modulo_activo'] = 'mod-donaciones'; 
                    $_SESSION['alerta_mensaje'] = "Operación denegada. El monto no puede actualizarse a $0.00.";
                    $_SESSION['alerta_tipo'] = "error";
                    header("Location: ../vistas/inicio.php"); 
                    exit;
                }

                $metodo      = $_POST['donacion-metodo'];
                $referencia  = trim($_POST['donacion-referencia']) ?: "N/A";
                $descripcion = "Aporte Económico";
                $cantidad    = "N/A";
            } else {
                $monto       = 0.00;
                $metodo      = "N/A";
                $referencia  = "N/A";
                $descripcion = trim($_POST['donacion-descripcion']);
                $cantidad    = trim($_POST['donacion-cantidad']);
            }

            $stmt = $pdo->prepare("UPDATE donaciones SET tipo_donacion = ?, monto = ?, donante = ?, metodo_pago = ?, descripcion = ?, cantidad = ?, referencia = ?, fecha_donacion = ? WHERE id = ?");
            $stmt->execute([$tipo, $monto, $donante, $metodo, $descripcion, $cantidad, $referencia, $fecha, $id]);

            $stmtBitacora = $pdo->prepare("INSERT INTO registro_actividad (id_usuario, modulo, accion) VALUES (?, 'Donaciones', ?)");
            $stmtBitacora->execute([$_SESSION['usuario_id'], "Actualizó donación ID: " . $id]);

            $_SESSION['modulo_activo'] = 'mod-donaciones'; 
            $_SESSION['alerta_mensaje'] = "Registro actualizado correctamente.";
            $_SESSION['alerta_tipo'] = "exito";
            header("Location: ../vistas/inicio.php"); exit;
        }

        elseif ($accion === 'eliminar-donacion') {
            $idDonacion = $_POST['donacion-id'];

            if (empty($idDonacion)) {
                throw new Exception("No se especificó la donación a eliminar.");
            }

            $stmtNombre = $pdo->prepare("SELECT donante, tipo_donacion FROM donaciones WHERE id = ?");
            $stmtNombre->execute([$idDonacion]);
            $donacion = $stmtNombre->fetch();
            $infoDonacion = $donacion ? "donación ({$donacion['tipo_donacion']}) de {$donacion['donante']}" : "Donación Desconocida";

            $stmtDelete = $pdo->prepare("DELETE FROM donaciones WHERE id = ?");
            $stmtDelete->execute([$idDonacion]);

            $stmtBitacora = $pdo->prepare("INSERT INTO registro_actividad (id_usuario, modulo, accion) VALUES (?, 'Donaciones', ?)");
            $stmtBitacora->execute([$_SESSION['usuario_id'], "Eliminó la " . $infoDonacion]);

            $_SESSION['modulo_activo'] = 'mod-donaciones'; 
            $_SESSION['alerta_mensaje'] = "Registro de donación eliminado.";
            $_SESSION['alerta_tipo'] = "exito";
            header("Location: ../vistas/inicio.php"); exit;
        }

        if ($accion === 'perfil-eliminar-cuenta') {
            $idUsr = $_SESSION['usuario_id'];
            $passActual = $_POST['clave-actual'];

            $stmt = $pdo->prepare("SELECT clave FROM usuarios WHERE id = ?");
            $stmt->execute([$idUsr]);
            $usrData = $stmt->fetch();

            if (!$usrData || !password_verify($passActual, $usrData['clave'])) {
                throw new Exception("La contraseña actual es incorrecta. No se pudo eliminar la cuenta.");
            }

            $stmtDel = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
            $stmtDel->execute([$idUsr]);

            header("Location: controlador.php?logout=true"); exit;
        }

        if ($accion === 'exportar-db') {

            if ($_SESSION['usuario_rol'] != 3) {
                throw new Exception("Acceso denegado. Solo administradores pueden exportar la base de datos.");
            }
            
            $tablas = [];
            $query = $pdo->query('SHOW TABLES');
            while($row = $query->fetch(PDO::FETCH_NUM)){ $tablas[] = $row[0]; }
            
            $sql = "-- Respaldo Catedral Metropolitana\n-- Fecha: " . date('Y-m-d H:i:s') . "\n\nSET FOREIGN_KEY_CHECKS=0;\n\n";

            foreach($tablas as $tabla){
                $creacion = $pdo->query("SHOW CREATE TABLE `$tabla`")->fetch(PDO::FETCH_ASSOC);
                $sql .= "DROP TABLE IF EXISTS `$tabla`;\n";
                $sql .= $creacion['Create Table'] . ";\n\n";
                
                $filas = $pdo->query("SELECT * FROM `$tabla`")->fetchAll(PDO::FETCH_ASSOC);
                if (count($filas) > 0) {
                    foreach ($filas as $fila) {
                        $valores = array_map(function($value) use ($pdo) {
                            return $pdo->quote($value);
                        }, array_values($fila));
                        $sql .= "INSERT INTO `$tabla` VALUES (" . implode(', ', $valores) . ");\n";
                    }
                    $sql .= "\n";
                }
            }
            $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
            
            if (ob_get_length()) {
                ob_end_clean();
            }

            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="catedral_respaldo_' . date('Y-m-d_H-i-s') . '.sql"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . strlen($sql));
            
            echo $sql;
            exit;
        }


    if ($_POST['accion'] === 'importar-db') {
        if (isset($_FILES['archivo-sql']) && $_FILES['archivo-sql']['error'] == 0) {
            $archivoTmp = $_FILES['archivo-sql']['tmp_name'];
            $sql = file_get_contents($archivoTmp);
            
            try {
                $pdo->exec("SET FOREIGN_KEY_CHECKS=0;");

                $tablasDB = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
                foreach ($tablasDB as $tabla) {
                    $pdo->exec("DROP TABLE IF EXISTS `$tabla`");
                }

                $pdo->exec($sql);

                $pdo->exec("SET FOREIGN_KEY_CHECKS=1;");

                $stmtAct = $pdo->prepare("INSERT INTO registro_actividad (id_usuario, modulo, accion) VALUES (?, 'Sistema', 'Restauración completa de base de datos ejecutada')");
                $stmtAct->execute([$_SESSION['usuario_id']]);

                $_SESSION['alerta_tipo'] = 'exito';
                $_SESSION['alerta_mensaje'] = 'Sistema restaurado con éxito. Se ha cargado el estado exacto de la fecha del respaldo.';
                
            } catch (PDOException $e) {
                $_SESSION['alerta_tipo'] = 'error';
                $_SESSION['alerta_mensaje'] = 'Error al restaurar: ' . $e->getMessage();
            }
        } else {
            $_SESSION['alerta_tipo'] = 'error';
            $_SESSION['alerta_mensaje'] = 'Error al cargar el archivo .sql.';
        }
        
        $_SESSION['modulo_activo'] = 'mod-perfil';
        header("Location: ../vistas/inicio.php");
        exit;
    }

    /* EXPORTAR BASE DE DATOS */

    if ($_POST['accion'] === 'exportar-db-servidor') {
        try {
            // VERIFICACIÓN DE CONTRASEÑA
            $idUsr = $_SESSION['usuario_id'];
            $passActual = $_POST['clave-admin'] ?? '';
            $stmt = $pdo->prepare("SELECT clave FROM usuarios WHERE id = ?");
            $stmt->execute([$idUsr]);
            $usrData = $stmt->fetch();
            if (!$usrData || !password_verify($passActual, $usrData['clave'])) {
                throw new Exception("Contraseña incorrecta. Acción denegada.");
            }

            $directorio = '../Respaldos/';
            if (!file_exists($directorio)) {
                mkdir($directorio, 0777, true);
            }

            // LIMITE DE 10 RESPALDOS
            $archivos = glob($directorio . "*.sql");
            if (count($archivos) >= 10) {
                throw new Exception("Límite alcanzado: Ya existen 10 respaldos. Por favor, elimine uno antiguo antes de crear uno nuevo.");
            }

            $fecha = date('Y-m-d_H-i-s');
            $nombreArchivo = "catedral_respaldo_{$fecha}.sql";
            $rutaCompleta = $directorio . $nombreArchivo;

            // GENERACIÓN REAL DEL ARCHIVO SQL
            $tablas = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
            $sql = "SET FOREIGN_KEY_CHECKS=0;\n\n";
            
            foreach ($tablas as $tabla) {
                $creacion = $pdo->query("SHOW CREATE TABLE `$tabla`")->fetch(PDO::FETCH_ASSOC);
                $sql .= "DROP TABLE IF EXISTS `$tabla`;\n";
                $sql .= $creacion['Create Table'] . ";\n\n";
                
                $filas = $pdo->query("SELECT * FROM `$tabla`")->fetchAll(PDO::FETCH_ASSOC);
                foreach ($filas as $fila) {
                    $valores = array_map([$pdo, 'quote'], array_values($fila));
                    $sql .= "INSERT INTO `$tabla` VALUES (" . implode(', ', $valores) . ");\n";
                }
                $sql .= "\n";
            }
            $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

            // Guardamos el archivo físicamente en la carpeta
            file_put_contents($rutaCompleta, $sql);

            // Registramos la acción en el historial
            $stmtAct = $pdo->prepare("INSERT INTO registro_actividad (id_usuario, modulo, accion) VALUES (?, 'Sistema', 'Creó un nuevo respaldo en la bóveda del servidor')");
            $stmtAct->execute([$_SESSION['usuario_id']]);

            $_SESSION['alerta_tipo'] = 'exito';
            $_SESSION['alerta_mensaje'] = "Respaldo creado y guardado exitosamente.";

        } catch (Exception $e) {
            $_SESSION['alerta_tipo'] = 'error';
            $_SESSION['alerta_mensaje'] = $e->getMessage();
        }
        
        $_SESSION['modulo_activo'] = 'mod-perfil';
        header("Location: ../vistas/inicio.php");
        exit;
    }

    /* IMPORTAR BASE DE DATOS DESDE LA BÓVEDA */
 
    if ($_POST['accion'] === 'importar-db-servidor') {
        $archivoSeleccionado = $_POST['archivo-respaldo'] ?? '';
        $rutaCompleta = '../Respaldos/' . basename($archivoSeleccionado); 

        if (!empty($archivoSeleccionado) && file_exists($rutaCompleta)) {
            $sql = file_get_contents($rutaCompleta);
            
            try {
                $pdo->exec("SET FOREIGN_KEY_CHECKS=0;");

                $tablasDB = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
                foreach ($tablasDB as $tabla) {
                    $pdo->exec("DROP TABLE IF EXISTS `$tabla`");
                }

                $pdo->exec($sql);
                $pdo->exec("SET FOREIGN_KEY_CHECKS=1;");

                $stmtAct = $pdo->prepare("INSERT INTO registro_actividad (id_usuario, modulo, accion) VALUES (?, 'Sistema', 'Restauró el sistema desde la bóveda del servidor')");
                $stmtAct->execute([$_SESSION['usuario_id']]);

                $_SESSION['alerta_tipo'] = 'exito';
                $_SESSION['alerta_mensaje'] = 'Sistema restaurado con éxito a la fecha seleccionada.';
                
            } catch (PDOException $e) {
                $_SESSION['alerta_tipo'] = 'error';
                $_SESSION['alerta_mensaje'] = 'Error al restaurar: ' . $e->getMessage();
            }
        } else {
            $_SESSION['alerta_tipo'] = 'error';
            $_SESSION['alerta_mensaje'] = 'Operación cancelada. No se encontró el archivo seleccionado.';
        }
        
        $_SESSION['modulo_activo'] = 'mod-perfil';
        header("Location: ../vistas/inicio.php");
        exit;
    }

    /* ELIMINAR RESPALDO DE LA BÓVEDA */
  
    if ($_POST['accion'] === 'eliminar-db-servidor') {
        try {
            // VERIFICACIÓN DE CONTRASEÑA
            $idUsr = $_SESSION['usuario_id'];
            $passActual = $_POST['clave-admin'] ?? '';
            $stmt = $pdo->prepare("SELECT clave FROM usuarios WHERE id = ?");
            $stmt->execute([$idUsr]);
            $usrData = $stmt->fetch();
            if (!$usrData || !password_verify($passActual, $usrData['clave'])) {
                throw new Exception("Contraseña incorrecta. Acción de eliminación denegada.");
            }

            // LIMPIEZA DEL NOMBRE DEL ARCHIVO
            $archivoSeleccionado = trim($_POST['archivo-respaldo'] ?? '');
            
            // Validar que el nombre no llegue vacío
            if (empty($archivoSeleccionado) || $archivoSeleccionado === '.' || $archivoSeleccionado === '..') {
                throw new Exception('El nombre del archivo llegó vacío. Verifica el botón de la papelera.');
            }

            $rutaCompleta = '../Respaldos/' . basename($archivoSeleccionado);

            if (file_exists($rutaCompleta)) {
                // TRUCO PARA WINDOWS/XAMPP: Forzar permisos de lectura/escritura antes de borrar
                @chmod($rutaCompleta, 0777);
                
                if (@unlink($rutaCompleta)) {
                    $stmtAct = $pdo->prepare("INSERT INTO registro_actividad (id_usuario, modulo, accion) VALUES (?, 'Sistema', 'Eliminó un archivo de respaldo del servidor')");
                    $stmtAct->execute([$_SESSION['usuario_id']]);

                    $_SESSION['alerta_tipo'] = 'exito';
                    $_SESSION['alerta_mensaje'] = 'El archivo de respaldo fue eliminado correctamente para liberar espacio.';
                } else {
                    throw new Exception('Windows bloqueó el borrado de: ' . basename($archivoSeleccionado));
                }
            } else {
                throw new Exception('No se encontró el archivo: ' . basename($archivoSeleccionado));
            }
        } catch (Exception $e) {
            $_SESSION['alerta_tipo'] = 'error';
            $_SESSION['alerta_mensaje'] = $e->getMessage();
        }

        $_SESSION['modulo_activo'] = 'mod-perfil';
        header("Location: ../vistas/inicio.php");
        exit;
    }


      /* EDITAR USUARIO */

    if (isset($_POST['accion']) && $_POST['accion'] == 'admin-editar-usuario') {
        if ($_SESSION['usuario_rol'] != 3) exit('Acceso denegado. Solo administradores.');
        
        $id = $_POST['usuario-id'];
        $nombre = trim($_POST['usuario-nombre']);
        $usuario = trim($_POST['usuario-login']);
        $rol = $_POST['usuario-rol'];

        try {
            $stmtVerificar = $pdo->prepare("SELECT id_rol FROM usuarios WHERE id = ?");
            $stmtVerificar->execute([$id]);
            $datosDestino = $stmtVerificar->fetch();

            if ($datosDestino && $datosDestino['id_rol'] == 3 && $id != $_SESSION['usuario_id']) {
                throw new Exception("Alerta de Seguridad: No tienes permitido modificar el nombre, usuario ni permisos de otro Administrador.");
            }
 
            $stmtCheck = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = ? AND id != ?");
            $stmtCheck->execute([$usuario, $id]);
            
            if ($stmtCheck->rowCount() > 0) {
                throw new Exception("El nombre de usuario de acceso ya pertenece a otra cuenta.");
            } else {
                $stmt = $pdo->prepare("UPDATE usuarios SET nombre = ?, usuario = ?, id_rol = ? WHERE id = ?");
                $stmt->execute([$nombre, $usuario, $rol, $id]);

                if ($id == $_SESSION['usuario_id']) {
                    $_SESSION['usuario_rol'] = $rol;
                    
                    if (isset($_SESSION['usuario_nombre'])) $_SESSION['usuario_nombre'] = $nombre;
                    if (isset($_SESSION['id_rol'])) $_SESSION['id_rol'] = $rol;
                }
                $stmtAct = $pdo->prepare("INSERT INTO registro_actividad (id_usuario, modulo, accion, fecha_accion) VALUES (?, 'Usuarios', CONCAT('Modificó el perfil del usuario: ', ?), NOW())");
                $stmtAct->execute([$_SESSION['usuario_id'], $usuario]);

                $_SESSION['alerta_tipo'] = 'exito';
                $_SESSION['alerta_mensaje'] = 'Datos y rol del usuario actualizados correctamente.';
            }
        } catch (Exception $e) {
            $_SESSION['alerta_tipo'] = 'error';
            $_SESSION['alerta_mensaje'] = $e->getMessage();
        }
        $_SESSION['modulo_activo'] = 'mod-gestion-usuarios';
        header('Location: ../vistas/inicio.php');
        exit;
    }

       /* ELIMINAR USUARIO */
 
    if (isset($_POST['accion']) && $_POST['accion'] == 'admin-eliminar-usuario') {
        if ($_SESSION['usuario_rol'] != 3) exit('Acceso denegado. Solo administradores.');
        
        $id = $_POST['usuario-id'];
        
        if ($id == $_SESSION['usuario_id']) {
            $_SESSION['alerta_tipo'] = 'error';
            $_SESSION['alerta_mensaje'] = 'Protocolo de seguridad: No puede revocar su propio acceso desde aquí.';
        } else {
            try {
               
                $stmtVerificar = $pdo->prepare("SELECT id_rol FROM usuarios WHERE id = ?");
                $stmtVerificar->execute([$id]);
                $datosDestino = $stmtVerificar->fetch();

                if ($datosDestino && $datosDestino['id_rol'] == 3) {
                    throw new Exception("Alerta de Seguridad: No se puede eliminar la cuenta de otro Administrador.");
                }

                $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
                $stmt->execute([$id]);

                $stmtAct = $pdo->prepare("INSERT INTO registro_actividad (id_usuario, modulo, accion, fecha_accion) VALUES (?, 'Usuarios', 'Eliminó a un usuario del sistema', NOW())");
                $stmtAct->execute([$_SESSION['usuario_id']]);

                $_SESSION['alerta_tipo'] = 'exito';
                $_SESSION['alerta_mensaje'] = 'El acceso del usuario ha sido revocado y su cuenta eliminada.';
            } catch (Exception $e) {
                if ($e instanceof PDOException) {
                    $_SESSION['alerta_mensaje'] = 'No se puede eliminar. El usuario tiene registros o historial atados a él.';
                } else {
                    $_SESSION['alerta_mensaje'] = $e->getMessage();
                }
                $_SESSION['alerta_tipo'] = 'error';
            }
        }
        $_SESSION['modulo_activo'] = 'mod-gestion-usuarios';
        header('Location: ../vistas/inicio.php');
        exit;
    }

           /* DESHABILITAR/HABILITAR USUARIO */

        if (isset($_POST['accion']) && $_POST['accion'] == 'admin-toggle-estado') {
            if ($_SESSION['usuario_rol'] != 3) exit('Acceso denegado. Solo administradores.');
            
            $id = $_POST['usuario-id'];
            $nuevoEstado = $_POST['nuevo-estado'];
            
            if ($id == $_SESSION['usuario_id']) {
                $_SESSION['alerta_tipo'] = 'error';
                $_SESSION['alerta_mensaje'] = 'Protocolo de seguridad: No puede suspender su propia cuenta.';
            } else {
                try {
                    // BLINDAJE: Evitar que un Admin suspenda a otro Admin
                    $stmtVerificar = $pdo->prepare("SELECT id_rol FROM usuarios WHERE id = ?");
                    $stmtVerificar->execute([$id]);
                    $datosDestino = $stmtVerificar->fetch();

                    if ($datosDestino && $datosDestino['id_rol'] == 3 && $nuevoEstado === 'Inactivo') {
                        throw new Exception("Alerta de Seguridad: No tienes permitido suspender la cuenta de otro Administrador.");
                    }

                    $stmt = $pdo->prepare("UPDATE usuarios SET estado = ? WHERE id = ?");
                    $stmt->execute([$nuevoEstado, $id]);

                    $accionLog = ($nuevoEstado === 'Inactivo') ? 'Suspendió el acceso' : 'Restauró el acceso';
                    $stmtAct = $pdo->prepare("INSERT INTO registro_actividad (id_usuario, modulo, accion, fecha_accion) VALUES (?, 'Usuarios', CONCAT(?, ' de un usuario en el sistema.'), NOW())");
                    $stmtAct->execute([$_SESSION['usuario_id'], $accionLog]);

                    $_SESSION['alerta_tipo'] = 'exito';
                    $_SESSION['alerta_mensaje'] = "La cuenta del usuario ahora está $nuevoEstado.";
                } catch (Exception $e) {
                    $_SESSION['alerta_tipo'] = 'error';
                    $_SESSION['alerta_mensaje'] = $e->getMessage();
                }
            }
            $_SESSION['modulo_activo'] = 'mod-gestion-usuarios';
            header('Location: ../vistas/inicio.php');
            exit;
        }

    } catch (Exception $e) {


        if (strpos($accion, 'perfil-') === 0 || $accion === 'nueva-actividad') {
            $_SESSION['alerta_mensaje'] = $e->getMessage();
            $_SESSION['alerta_tipo'] = "error";

            if (strpos($accion, 'perfil-') === 0 || $accion === 'exportar-db' || $accion === 'importar-db') {
                $_SESSION['modulo_activo'] = 'mod-perfil';
            }

            header("Location: ../vistas/inicio.php");
            exit;
        } 

        else {
            $_SESSION['flash_mensaje'] = $e->getMessage();
            $_SESSION['flash_tipo'] = "error";
            
            if ($accion === 'registro') { $_SESSION['flash_vista'] = 'vista-registro'; }
            elseif (strpos($accion, 'recuperar') !== false) { $_SESSION['flash_vista'] = 'vista-recuperar-1'; }
            else { $_SESSION['flash_vista'] = 'vista-login'; }

            header("Location: ../vistas/login.php");
            exit;
        }
    }
} 

elseif (isset($_GET['logout'])) {
    session_start();
    session_destroy();
    header("Location: ../vistas/login.php");
    exit;
} 
else {
    header("Location: ../vistas/login.php");
    exit;
}

?>