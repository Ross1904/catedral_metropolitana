<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../php/seguridad_sesion.php';
require_once '../configuracion/conexion.php'; 

$idUsuario = $_SESSION['usuario_id'] ?? 0;
$nombreUsuario = htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario');

$rolSesion = $_SESSION['usuario_rol'] ?? 1;
$rolUsuario = htmlspecialchars($_SESSION['rol_nombre'] ?? 'Ciudadano');

$esAdmin = ($rolSesion == 3);
$esSecretario = ($rolSesion >= 2);

$listaActividades = [];
$eventosAgenda = [];
$donaciones = [];
$listaUsuarios = [];
$historialActividad = [];
$notificacionesNuevas = 0;
$usuarioLoginActual = '';
$preguntasActuales = ['', '', '', ''];
$conteoActividades = [];
$conteoDonaciones = [];
$historialBruto = [];

try {
    $stmtPerfil = $pdo->prepare("SELECT usuario FROM usuarios WHERE id = ?");
    $stmtPerfil->execute([$idUsuario]);
    $perfilData = $stmtPerfil->fetch(PDO::FETCH_ASSOC);
    $usuarioLoginActual = $perfilData ? $perfilData['usuario'] : '';


    $stmtPreguntas = $pdo->prepare("SELECT pregunta FROM preguntas_seguridad WHERE id_usuario = ? ORDER BY id ASC");
    $stmtPreguntas->execute([$idUsuario]);
    $preguntasGuardadas = $stmtPreguntas->fetchAll(PDO::FETCH_ASSOC);
    
    $preguntasActuales = ['', '', '', ''];
    foreach($preguntasGuardadas as $index => $row) {
        if($index < 4) $preguntasActuales[$index] = $row['pregunta'];
    }

    $stmtActividades = $pdo->query("SELECT * FROM actividades_pastorales ORDER BY categoria ASC, nombre_actividad ASC");
    $listaActividades = $stmtActividades->fetchAll(PDO::FETCH_ASSOC);

    $stmtAgenda = $pdo->query("SELECT * FROM agenda_catedral ORDER BY fecha_hora_inicio ASC");
    $eventosAgenda = $stmtAgenda->fetchAll(PDO::FETCH_ASSOC);

    $stmtDonaciones = $pdo->query("SELECT * FROM donaciones ORDER BY fecha_donacion DESC");
    $donaciones = $stmtDonaciones->fetchAll(PDO::FETCH_ASSOC);

    //MATEMÁTICAS PARA LAS GRÁFICAS
    
    $conteoActividades = ['Formación' => 0, 'Grupo Devocional' => 0, 'Reunión' => 0];
    foreach ($listaActividades as $act) {
        $cat = $act['categoria'];
        if (isset($conteoActividades[$cat])) {
            $conteoActividades[$cat]++;
        }
    }

    $conteoDonaciones = ['Monetaria' => 0, 'Suministro' => 0, 'Insumo' => 0, 'Material' => 0, 'Otros' => 0];
    foreach ($donaciones as $don) {
        $tipo = $don['tipo_donacion'];
        if (isset($conteoDonaciones[$tipo])) {
            $conteoDonaciones[$tipo]++;
        } else {
            $conteoDonaciones['Otros']++;
        }
    }

    $stmtUsuarios = $pdo->query("SELECT id, nombre, usuario, id_rol, estado FROM usuarios ORDER BY id_rol DESC, nombre ASC");
    $listaUsuarios = $stmtUsuarios->fetchAll(PDO::FETCH_ASSOC);

    //PARCHE AUTOMÁTICO DE BASE DE DATOS
    try {
        $checkCol = $pdo->query("SHOW COLUMNS FROM registro_actividad LIKE 'eliminado_por'");
        if ($checkCol->rowCount() == 0) {
            $pdo->exec("ALTER TABLE registro_actividad ADD COLUMN eliminado_por TEXT DEFAULT ''");
            $pdo->exec("ALTER TABLE registro_actividad MODIFY COLUMN visto TEXT");
            $pdo->exec("UPDATE registro_actividad SET visto = ''");
        }
    } catch(PDOException $e) { /* Silencioso */ }

    //LÓGICA DE NOTIFICACIONES PRIVADAS Y JERÁRQUICAS

    $stmtActividad = $pdo->query("
        SELECT r.*, u.nombre, u.id_rol as rol_autor 
        FROM registro_actividad r 
        JOIN usuarios u ON r.id_usuario = u.id 
        ORDER BY r.fecha_accion DESC
    ");
    $historialBruto = $stmtActividad->fetchAll(PDO::FETCH_ASSOC);

    $historialActividad = [];
    $notificacionesNuevas = 0;

    foreach($historialBruto as $hist) {
        if (!$esAdmin && $hist['rol_autor'] == 3) {
            continue;
        }

        $eliminadoPorArray = explode(',', $hist['eliminado_por'] ?? '');
        if (in_array((string)$idUsuario, $eliminadoPorArray)) {
            continue;
        }

        $vistoPorArray = explode(',', $hist['visto'] ?? '');
        $estaVisto = in_array((string)$idUsuario, $vistoPorArray);

        if (!$estaVisto) {
            $notificacionesNuevas++;
        }

        $hist['ya_lo_vio'] = $estaVisto;
        
        $historialActividad[] = $hist;

        if(count($historialActividad) >= 30) break;
    }
} catch (PDOException $e) {
    $_SESSION['alerta_tipo'] = 'error';
    $_SESSION['alerta_mensaje'] = 'Falta una tabla en la BD: ' . $e->getMessage();
}

$moduloActivoPHP = $_SESSION['modulo_activo'] ?? null;
unset($_SESSION['modulo_activo']); 
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Santo Tomás Apóstol - Panel de Control</title>
    
    <script>
        const idUsuarioActual = <?php echo json_encode($idUsuario); ?>;
        const moduloActivo = <?php echo json_encode($moduloActivoPHP); ?>;
        const esSecretarioJS = <?php echo json_encode($esSecretario); ?>;
    </script>

    <link rel="stylesheet" href="../recursos/fontawesome/css/all.min.css">
    
    <link rel="stylesheet" href="../recursos/flatpickr/flatpickr.min.css">
    
    <link rel="stylesheet" href="../recursos/css/dashboard.css?v=<?php echo time(); ?>">

    <script>
        window.history.pushState(null, null, window.location.href);
        window.onpopstate = function () {
            window.history.pushState(null, null, window.location.href);
            window.location.reload(); 
        };
        window.addEventListener('pageshow', function (event) {
            if (event.persisted || (typeof window.performance != "undefined" && window.performance.navigation.type === 2)) {
                window.location.reload(); 
            }
        });
    </script>
</head>
<body>
    <script>
        (function() {
            // Leemos el ID del usuario directamente desde PHP antes de que cargue la página
            const idUsuarioFlash = <?php echo json_encode($idUsuario ?? 'generico'); ?>;
            const claveTema = 'tema_' + idUsuarioFlash;
            const temaGuardado = localStorage.getItem(claveTema);
            
            // Si el usuario tenía el modo oscuro, pintamos el body de oscuro ANTES de que el navegador lo muestre
            if (temaGuardado === 'oscuro') {
                document.body.classList.add('oscuro');
            }
        })();
    </script>

<?php if (isset($_SESSION['alerta_mensaje'])): ?>
        <div id="toast-notificacion" class="toast-catedral toast-<?php echo $_SESSION['alerta_tipo']; ?>">
            <div class="toast-icono">
                <?php if($_SESSION['alerta_tipo'] == 'error'): ?>
                    <i class="fas fa-exclamation-circle"></i>
                <?php else: ?>
                    <i class="fas fa-check-circle"></i>
                <?php endif; ?>
            </div>
            <div class="toast-texto">
                <?php echo htmlspecialchars($_SESSION['alerta_mensaje']); ?>
            </div>
            <button class="toast-cerrar" onclick="this.parentElement.style.display='none';"><i class="fas fa-times"></i></button>
        </div>
        <?php 
            unset($_SESSION['alerta_mensaje']); 
            unset($_SESSION['alerta_tipo']); 
        ?>
    <?php endif; ?>

<div class="barra-lateral" id="barra-lateral">
    <div>
        <div class="cabecera">
            <i class="fas fa-user-circle icono-perfil"></i>
            <div class="informacion">
                <strong><?php echo $nombreUsuario; ?></strong><br>
                <span><?php echo $rolUsuario; ?></span>
            </div>
        </div>
        
        <div class="menu">
            <div class="item-menu activo" data-objetivo="mod-bienvenida">
                <i class="fas fa-home"></i><span>Inicio</span>
            </div>
            
            <?php if($esSecretario): ?>
            <div class="item-menu" data-objetivo="mod-crear-docs">
                <i class="fas fa-file-signature"></i><span>Crear Documentos</span>
            </div>
            <?php endif; ?>

            <div class="item-menu" data-objetivo="mod-calendario">
                <i class="fas fa-calendar-alt"></i><span>Agenda</span>
            </div>
            
            <div class="item-menu" data-objetivo="mod-formacion">
                <i class="fas fa-users"></i><span>Formación y Grupos</span>
            </div>
            
            <?php if($esSecretario): ?>
            <div class="item-menu" data-objetivo="mod-donaciones">
                <i class="fas fa-hand-holding-usd"></i><span>Diezmos y Ofrendas</span>
            </div>
            
            <div class="item-menu" data-objetivo="mod-archivos">
                <i class="fas fa-folder-open"></i><span>Gestión de Archivos</span>
            </div>
            <?php endif; ?>
            
            <?php if($esAdmin): ?>
                <div class="item-menu" data-objetivo="mod-gestion-usuarios">
                    <i class="fas fa-users-cog"></i><span>Gestión Usuarios</span>
                </div>
            <?php endif; ?>

            <div class="item-menu" data-objetivo="mod-perfil">
                <i class="fas fa-id-card"></i><span>Mi Perfil</span>
            </div>
        </div>
    </div>

    <div class="parte-inferior">
        <div class="item-alternar" id="btn-colapsar">
            <i class="fas fa-angle-double-left" id="icono-colapsar"></i><span>Colapsar Menú</span>
        </div>
        <div class="item-alternar">
            <i class="fas fa-moon" id="icono-tema" style="cursor: pointer;" title="Alternar Tema"></i><span>Modo Oscuro</span>
            <label class="interruptor">
                <input type="checkbox" id="alternar-tema">
            </label>
        </div>
        <a href="../php/controlador.php?logout=true" style="text-decoration: none; color: inherit;">
            <div class="item-alternar" style="color: #e63946;">
                <i class="fas fa-sign-out-alt"></i><span>Cerrar Sesión</span>
            </div>
        </a>
    </div>
</div>

<div class="contenido-principal">

    <section id="mod-bienvenida" class="modulo activo">
        
        <?php if($esAdmin): ?>
            <div class="contenedor-dashboard-admin">
                
                <div class="dashboard-cabecera">
                    <div class="dashboard-titulo-icono">
                        <i class="fas fa-church"></i>
                        <div>
                            <h2>Panel de Control - Catedral Metropolitana</h2>
                            <p>"Actuemos con valor, honestidad y compasión."</p>
                        </div>
                    </div>
                </div>

                <div class="grid-graficas">
                    <div class="tarjeta-grafica">
                        <h3><i class="fas fa-chart-bar" style="color: var(--acento-dorado);"></i> Actividades Pastorales</h3>
                        <div class="contenedor-canvas">
                            <canvas id="graficaActividades"></canvas>
                        </div>
                    </div>
                    <div class="tarjeta-grafica">
                        <h3><i class="fas fa-hand-holding-usd" style="color: var(--acento-dorado);"></i> Distribución de Donaciones</h3>
                        <div class="contenedor-canvas">
                            <canvas id="graficaDonaciones"></canvas>
                        </div>
                    </div>
                </div>

            </div>

        <?php else: ?>
            <div class="tarjeta-bienvenida" style="margin: 0 auto; max-width: 1000px;">
                <i class="fas fa-church icono-bienvenida"></i>
                <h2 class="titulo-bienvenida">Portal de la Catedral Metropolitana</h2>
                <hr class="separador-dorado">
                <blockquote class="cita-religiosa">
                    "No son nuestras habilidades las que muestran lo que realmente somos… son nuestras elecciones. Actuemos con valor, honestidad y compasión."
                </blockquote>
                <p class="instruccion-bienvenida">Que la luz guíe su labor. Seleccione una opción en el menú lateral para comenzar a trabajar en los registros parroquiales.</p>
            </div>
        <?php endif; ?>

    </section>

    <?php if($esSecretario): ?>
    <section id="mod-crear-docs" class="modulo">
            <div class="encabezado-modulo" style="margin-bottom: 30px;">
                <h2 style="color: var(--acento-dorado); font-size: 2rem; margin-bottom: 10px;">
                    <i class="fas fa-file-signature" style="color: var(--acento-dorado);"></i> Creación de Documentos
                </h2>
                <p style="opacity: 0.8;">Seleccione el tipo de certificado o partida oficial que desea generar. Los documentos generados se guardarán automáticamente en la gestión de archivos.</p>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                
                <div class="tarjeta-boton-perfil" onclick="abrirModal('modal-form-bautismo')" style="border-top: 4px solid var(--acento-dorado); text-align: center; padding: 30px 20px;">
                    <i class="fas fa-water" style="font-size: 3rem; color: var(--acento-dorado); margin-bottom: 15px;"></i>
                    <h3 style="font-size: 1.2rem; margin-bottom: 10px;">Partida de Bautismo</h3>
                    <p style="font-size: 0.85rem; opacity: 0.8;">Generar el certificado oficial del sacramento del bautismo.</p>
                </div>

                <div class="tarjeta-boton-perfil" onclick="abrirModal('modal-form-confirmacion')" style="border-top: 4px solid var(--acento-dorado); text-align: center; padding: 30px 20px;">
                    <i class="fas fa-dove" style="font-size: 3rem; color: var(--acento-dorado); margin-bottom: 15px;"></i>
                    <h3 style="font-size: 1.2rem; margin-bottom: 10px;">Confirmación</h3>
                    <p style="font-size: 0.85rem; opacity: 0.8;">Generar el certificado oficial de la Confirmación.</p>
                </div>

                <div class="tarjeta-boton-perfil" onclick="abrirModal('modal-crear-matrimonio')" style="border-top: 4px solid var(--acento-dorado); text-align: center; padding: 30px 20px;">
                    <i class="fas fa-ring" style="font-size: 3rem; color: var(--acento-dorado); margin-bottom: 15px;"></i>
                    <h3 style="font-size: 1.2rem; margin-bottom: 10px;">Acta de Matrimonio</h3>
                    <p style="font-size: 0.85rem; opacity: 0.8;">Generar el registro oficial de la unión matrimonial.</p>
                </div>

            </div>
        </section>
    <?php endif; ?>

    <section id="mod-calendario" class="modulo">
        <div class="contenedor-calendario">
            <div class="calendario-cabecera">
                <button class="boton-navegacion-calendario" id="btn-mes-anterior" title="Mes Anterior">
                    <i class="fas fa-chevron-left"></i>
                </button>
                
                <h3 id="mes-anio-display" style="transition: all 0.3s ease;">Cargando...</h3>
                
                <button class="boton-navegacion-calendario" id="btn-mes-siguiente" title="Mes Siguiente">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
            
            <div class="calendario-dias-semana">
                <div>Dom</div><div>Lun</div><div>Mar</div><div>Mié</div><div>Jue</div><div>Vie</div><div>Sáb</div>
            </div>
            
            <div class="calendario-cuadricula" id="cuadricula-dias">
                </div>
        </div>
    </section>

    <section id="mod-formacion" class="modulo">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-block-end: 20px; flex-wrap: wrap; gap: 15px;">
            <div>
                <h2>Grupos y Formación</h2>
                <p style="margin:0; opacity:0.8;">Gestión de catequesis, grupos de devoción y reuniones regulares.</p>
            </div>
            
            <?php if($esSecretario): ?>
            <button class="boton-crear-sagrado" onclick="abrirModal('modal-actividad')">
                <i class="fas fa-cross"></i> Nueva Actividad
            </button>
            <?php endif; ?>
        </div>
        
        <div class="grid-actividades" id="lista-formacion">
            <?php if (empty($listaActividades)): ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 40px; background: rgba(0,0,0,0.02); border-radius: 8px;">
                    <i class="fas fa-church" style="font-size: 3rem; color: var(--acento-dorado); margin-bottom: 15px; opacity:0.5;"></i>
                    <p>No hay actividades registradas en la parroquia aún.</p>
                </div>
            <?php else: ?>
                <?php foreach($listaActividades as $act): ?>
                <div class="tarjeta-actividad item-formacion">
                    <div class="tarjeta-actividad-cabecera">
                        <h4 class="tarjeta-actividad-titulo"><?php echo htmlspecialchars($act['nombre_actividad']); ?></h4>
                        <span class="etiqueta-categoria"><?php echo htmlspecialchars($act['categoria']); ?></span>
                    </div>
                    <div class="tarjeta-actividad-cuerpo">
                        <p><i class="fas fa-calendar-day"></i> <?php echo htmlspecialchars($act['dias_reunion']); ?></p>
                        <p><i class="fas fa-clock"></i> <?php echo date("h:i A", strtotime($act['hora_inicio'])); ?></p>
                        <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($act['lugar']); ?></p>
                    </div>
                    <?php if($esSecretario): ?>
                    <div class="tarjeta-actividad-acciones" style="display: flex; gap: 10px; justify-content: flex-end; width: 100%;">
                        
                        <button class="boton-accion-tarjeta btn-editar" 
                                onclick="abrirModalEditarActividad(
                                    <?php echo $act['id']; ?>,
                                    '<?php echo addslashes(htmlspecialchars($act['nombre_actividad'])); ?>',
                                    '<?php echo $act['categoria']; ?>',
                                    '<?php echo addslashes(htmlspecialchars($act['dias_reunion'])); ?>',
                                    '<?php echo $act['hora_inicio']; ?>',
                                    '<?php echo addslashes(htmlspecialchars($act['lugar'])); ?>'
                                )">
                            <i class="fas fa-pen"></i> Editar
                        </button>

                        <button type="button" class="boton-accion-tarjeta btn-eliminar" title="Eliminar Actividad"
                                onclick="confirmarEliminacion(<?php echo $act['id']; ?>, '<?php echo addslashes(htmlspecialchars($act['nombre_actividad'])); ?>')">
                            <i class="fas fa-trash-alt"></i>
                        </button>

                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
            <?php if(count($listaActividades) > 4): ?>
    <div style="display: flex; justify-content: center; margin-top: 25px; width: 100%; grid-column: 1 / -1;">
        <button type="button" id="btn-cargar-mas-formacion" class="boton-sagrado-secundario" style="padding: 10px 25px; border-radius: 20px;">
            <i class="fas fa-chevron-down"></i> Ver más actividades
        </button>
    </div>
    <?php endif; ?>
        </div>

        <?php if(!empty($listaActividades)): ?>
        <div style="text-align: center; margin-top: 25px; width: 100%;">
            <button type="button" id="btn-cargar-mas-formacion" class="boton-sagrado-secundario" style="display: none; padding: 10px 25px; border-radius: 20px;">
                <i class="fas fa-chevron-down"></i> Ver más actividades
            </button>
        </div>
        <?php endif; ?>
    </section>

    <?php if($esSecretario): ?>
    <section id="mod-donaciones" class="modulo">
        <?php
            $totalDinero = 0;
            foreach($donaciones as $d) {
                if($d['tipo_donacion'] === 'Monetaria') {
                    $totalDinero += (float)$d['monto'];
                }
            }
        ?>

        <div style="display: flex; justify-content: flex-start; align-items: center; flex-wrap: wrap; gap: 40px; margin-bottom: 25px;">
            
            <div style="display: flex; align-items: center; gap: 20px; flex-wrap: wrap;">
                <h2 style="margin: 0; padding: 0; border: none;"><i class="fas fa-hand-holding-usd"></i> Registro de Diezmos y Ofrendas</h2>
                
                <button class="boton-sagrado-primario" onclick="abrirModal('modal-nueva-donacion')" style="margin: 0;">
                    <i class="fas fa-plus-circle"></i> Registrar Ingreso
                </button>
            </div>

            <div class="tarjeta-estadistica" style="border-left: 5px solid #6b9071; min-width: 250px; margin: 0; padding: 10px 10px;">
                <i class="fas fa-coins icono-fondo" style="font-size: 4rem;"></i>
                <h4 style="margin-bottom: 5px;">Total Recaudado</h4>
                <p class="numero-grande" style="font-size: 1.8rem;">$ <?php echo number_format($totalDinero, 2, '.', ','); ?></p>
            </div>
            
        </div>
        
        <div class="cuadricula-tarjetas" id="contenedor-tarjetas-donaciones">
            <?php if (empty($donaciones)): ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 40px; background: rgba(0,0,0,0.02); border-radius: 8px;">
                    <i class="fas fa-hand-holding-heart" style="font-size: 3rem; color: var(--acento-dorado); margin-bottom: 15px; opacity:0.5;"></i>
                    <p>Aún no se han registrado diezmos u ofrendas.</p>
                </div>
            <?php else: ?>
                <?php foreach($donaciones as $d): 
                    $esMonetaria = ($d['tipo_donacion'] === 'Monetaria');
                    
                    $textoCabecera = $d['tipo_donacion']; 
                    $claseCabecera = 'etiqueta-otros';
                    $iconoCabecera = 'fas fa-gift';
                    $iconoMetodo = '';

                    if ($esMonetaria) {
                        $claseCabecera = 'etiqueta-monetaria';
                        $iconoCabecera = 'fas fa-money-bill-wave';
                        $iconoMetodo = 'fas fa-money-bill-wave';
                        if($d['metodo_pago'] === 'Transferencia') { $iconoMetodo = 'fas fa-exchange-alt'; }
                        elseif($d['metodo_pago'] === 'Pago Móvil') { $iconoMetodo = 'fas fa-mobile-alt'; }
                        elseif($d['metodo_pago'] === 'Tarjeta') { $iconoMetodo = 'fas fa-credit-card'; }
                    } elseif ($d['tipo_donacion'] === 'Suministro') {
                        $claseCabecera = 'etiqueta-suministro';
                        $iconoCabecera = 'fas fa-box';
                    } elseif ($d['tipo_donacion'] === 'Insumo') {
                        $claseCabecera = 'etiqueta-insumo';
                        $iconoCabecera = 'fas fa-medkit';
                    } elseif ($d['tipo_donacion'] === 'Material') {
                        $claseCabecera = 'etiqueta-material';
                        $iconoCabecera = 'fas fa-tools';
                    }
                ?>
                <div class="tarjeta-ofrenda item-donacion">
                    <div class="ofrenda-cabecera">
                        <span class="ofrenda-fecha"><i class="far fa-calendar-alt"></i> <?php echo date('d M, Y', strtotime($d['fecha_donacion'])); ?></span>
                        <span class="ofrenda-metodo <?php echo $claseCabecera; ?>"><i class="<?php echo $iconoCabecera; ?>"></i> <?php echo htmlspecialchars($textoCabecera); ?></span>
                    </div>
                    
                    <div class="ofrenda-cuerpo" style="text-align: left; padding: 10px 0;">
                        <?php if($esMonetaria): ?>
                            <h3 class="ofrenda-monto" style="text-align: center;">$ <?php echo number_format($d['monto'], 2, '.', ','); ?></h3>
                            <div class="donacion-dato-fila">
                                <span class="donacion-dato-etiqueta">Método de Pago:</span>
                                <span class="donacion-dato-valor texto-azul-adaptable" style="font-weight: bold;">
                                    <i class="<?php echo $iconoMetodo; ?>"></i> <?php echo htmlspecialchars($d['metodo_pago']); ?>
                                </span>
                            </div>

                            <div class="donacion-dato-fila">
                                <span class="donacion-dato-etiqueta">Donante:</span>
                                <span class="donacion-dato-valor nombre-donante-destacado">
                                    <i class="fas fa-user-edit" style="color: var(--acento-dorado);"></i> <?php echo htmlspecialchars($d['donante']); ?>
                                </span>
                            </div>
                            <div class="donacion-dato-fila">
                                <span class="donacion-dato-etiqueta">Referencia:</span>
                                <span class="donacion-dato-valor"><?php echo htmlspecialchars($d['referencia']); ?></span>
                            </div>
                        <?php else: ?>
                            <h3 class="ofrenda-monto" style="text-align: center; color: var(--acento-dorado); font-size: 1.5rem; margin-bottom: 15px;">
                                <i class="fas fa-box-open"></i> Aporte en Especie
                            </h3>
                            
                            <div class="donacion-dato-fila">
                                <span class="donacion-dato-etiqueta">Donante:</span>
                                <span class="donacion-dato-valor nombre-donante-destacado">
                                    <i class="fas fa-user-edit" style="color: var(--acento-dorado);"></i> <?php echo htmlspecialchars($d['donante']); ?>
                                </span>
                            </div>
                            <div class="donacion-dato-fila">
                                <span class="donacion-dato-etiqueta">Cantidad:</span>
                                <span class="donacion-dato-valor" style="font-weight: bold;"><?php echo htmlspecialchars($d['cantidad']); ?></span>
                            </div>
                            <div class="donacion-dato-fila">
                                <span class="donacion-dato-etiqueta">Descripción:</span>
                                <span class="donacion-dato-valor ofrenda-descripcion-bienes" style="margin: 0; text-align: right; line-height: 1.2;"><?php echo htmlspecialchars($d['descripcion']); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="ofrenda-acciones">
                        <button class="boton-accion-tarjeta btn-editar" title="Editar" onclick="abrirModalEditarDonacion(<?php echo $d['id']; ?>, '<?php echo $d['tipo_donacion']; ?>', <?php echo $d['monto']; ?>, '<?php echo addslashes(htmlspecialchars($d['donante'])); ?>', '<?php echo $d['metodo_pago']; ?>', '<?php echo addslashes(htmlspecialchars($d['descripcion'])); ?>', '<?php echo addslashes(htmlspecialchars($d['cantidad'])); ?>', '<?php echo addslashes(htmlspecialchars($d['referencia'])); ?>', '<?php echo $d['fecha_donacion']; ?>')"><i class="fas fa-pen"></i></button>
                        <button class="boton-accion-tarjeta btn-eliminar" title="Eliminar" onclick="confirmarEliminarDonacion(<?php echo $d['id']; ?>, '<?php echo addslashes(htmlspecialchars($d['donante'])); ?>')"><i class="fas fa-trash-alt"></i></button>
                    </div>
                </div>

        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php if(count($donaciones) > 3): ?>
<div style="text-align: center; margin-top: 25px; width: 100%;">
    <button type="button" id="btn-cargar-mas-donaciones" class="boton-sagrado-secundario" style="padding: 10px 25px; border-radius: 20px;">
        <i class="fas fa-chevron-down"></i> Ver más donaciones
    </button>
</div>
<?php endif; ?>

</section>
<?php endif; ?>
 
    <?php if($esSecretario): ?>
    <section id="mod-archivos" class="modulo">
        
        <div class="encabezado-modulo">
            <h2><i class="fas fa-folder-open" style="color: var(--acento-dorado);"></i> Gestión de Archivos</h2>
            <p style="margin: 0; opacity: 0.8;">Seleccione la categoría para explorar el historial de documentos generados.</p>
        </div>

        <div id="vista-categorias-archivos" class="grid-perfil-botones">
            <div class="tarjeta-boton-perfil" onclick="limpiarBuscadorArchivos(); mostrarTablaArchivos('Bautismo')">
                <i class="fas fa-folder icono-gigante"></i>
                <h3>Bautismos</h3>
                <p>Ver actas de bautismo generadas.</p>
            </div>

            <div class="tarjeta-boton-perfil" onclick="limpiarBuscadorArchivos(); mostrarTablaArchivos('Confirmacion')">
                <i class="fas fa-folder icono-gigante"></i>
                <h3>Confirmaciones</h3>
                <p>Ver certificados de confirmación.</p>
            </div>

            <div class="tarjeta-boton-perfil" onclick="limpiarBuscadorArchivos(); mostrarTablaArchivos('Matrimonio')">
                <i class="fas fa-folder icono-gigante"></i>
                <h3>Matrimonios</h3>
                <p>Ver actas de matrimonio generadas.</p>
            </div>
        </div>

        <div id="vista-tabla-archivos" style="display: none; margin-top: 20px;">
            
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; margin-bottom: 25px; border-bottom: 2px solid var(--acento-dorado); padding-bottom: 15px;">
            
                <div style="display: flex; align-items: center; gap: 20px;">
                    <button class="boton-sagrado-secundario" onclick="limpiarBuscadorArchivos(); volverCategoriasArchivos();" style="margin: 0; padding: 8px 20px; border-radius: 20px; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-arrow-left"></i> Regresar a Carpetas
                    </button>
                    <h3 id="titulo-tabla-archivos" style="margin: 0; border: none; padding: 0; font-size: 1.5rem; color: var(--acento-dorado);">Registros</h3>
                </div>

                <div style="display: flex; align-items: center; gap: 15px; flex-grow: 1; justify-content: flex-end;">
                    <div style="position: relative; width: 100%; max-width: 400px;">
                        <i class="fas fa-search" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--acento-dorado); font-size: 1.1rem; z-index: 10;"></i>
                        <input type="text" id="buscador-archivos" onkeyup="filtrarTablaArchivos()" class="input-estilo-catedral" placeholder="Buscar por nombre, folio, libro..." style="margin: 0; padding-left: 45px; width: 100%; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                    </div>
                    <button class="boton-sagrado-primario" onclick="toggleModoExportacion()" id="btn-modo-exportacion" style="margin: 0; padding: 10px 15px; border-radius: 6px; white-space: nowrap;">
                        <i class="fas fa-tasks"></i> Selección Múltiple
                    </button>
                </div>
                
            </div>

            <div id="panel-exportacion" style="display: none; background: rgba(198, 156, 109, 0.1); border: 1px solid var(--acento-dorado); border-radius: 8px; padding: 15px; margin-bottom: 20px; flex-wrap: wrap; gap: 15px; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <input type="checkbox" id="check-todos" onclick="toggleAllChecks(this)" style="transform: scale(1.5); cursor: pointer; margin-left: 5px;">
                    <label for="check-todos" style="font-weight: bold; cursor: pointer; color: var(--texto-principal);">Seleccionar Todos</label>
                    <span id="contador-seleccionados" style="margin-left: 15px; opacity: 0.8; font-size: 0.9rem;">0 seleccionados</span>
                </div>
                
                <div style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
                    <div style="display: flex; align-items: center; gap: 8px; background: #fff; padding: 5px 10px; border-radius: 6px; border: 1px solid #ddd;">
                        <label for="formato-lote-bautismo" style="font-size: 0.9rem; font-weight: bold; color: #555;">Formato Bautismos:</label>
                        <select id="formato-lote-bautismo" style="border: none; outline: none; background: transparent; font-size: 0.9rem; color: var(--texto-principal);">
                            <option value="nacional">Nacional</option>
                            <option value="exterior">Para el Exterior</option>
                        </select>
                    </div>

                    <button class="boton-sagrado-primario" onclick="descargarLote()" style="background: #2c3e50; border-color: #1a252f; margin: 0;">
                        <i class="fas fa-file-download"></i> Descargar Seleccionados
                    </button>
                </div>
            </div>

            <div class="contenedor-tabla-responsiva">
                <table id="tabla-registros-archivos" class="tabla-catedral" style="width: 100%;">
                    <thead>
                        <tr>
                            <th id="th-checkboxes" class="col-check" style="display: none; width: 50px; text-align: center;">
                                <i class="fas fa-check-square" style="color: var(--acento-dorado); font-size: 1.2rem;"></i>
                            </th>
                            <th>Nro. Acta</th>
                            <th>Nombre Principal</th>
                            <th>Nombre Sacramento</th>
                            <th>Fecha Sacramento</th>
                            <th>Libro/Folio</th>
                            <th style="text-align: center;">Acción</th>
                        </tr>
                    </thead>
                    <tbody id="cuerpo-tabla-archivos">
                        <?php
try {
    $stmt_archivos = $pdo->query("SELECT * FROM documentos_actas ORDER BY id DESC");
    
    if($stmt_archivos) {
        while($archivo = $stmt_archivos->fetch(PDO::FETCH_ASSOC)) {
            $tipo = htmlspecialchars($archivo['tipo_documento'] ?? '');
            $num = htmlspecialchars($archivo['numero'] ?? '-');
            $nom = htmlspecialchars($archivo['nombre_principal'] ?? '-');
            
            $fec_vista = date('d/m/Y', strtotime($archivo['fecha_sacramento'] ?? 'now'));
            $fec_input = date('Y-m-d', strtotime($archivo['fecha_sacramento'] ?? 'now'));
            
            $lib = htmlspecialchars($archivo['libro'] ?? '-');
            $fol = htmlspecialchars($archivo['folio'] ?? '-');
            $idDoc = $archivo['id'];

            $json_seguro = htmlspecialchars($archivo['datos_json'], ENT_QUOTES, 'UTF-8');
            
            // --- INICIO DEL CÓDIGO NUEVO ---

            $urlDescarga = "";
            if($tipo === 'Bautismo') {
                $urlDescarga = "../php/generar_bautismo_pdf.php?id=$idDoc&descargar=1";
            } elseif($tipo === 'Confirmacion') {
                $urlDescarga = "../php/generar_confirmacion_pdf.php?id=$idDoc&descargar=1";
            } elseif($tipo === 'Matrimonio') {
                $urlDescarga = "../php/generar_matrimonio_pdf.php?id=$idDoc&descargar=1";
            }
            echo "<tr class='fila-archivo' data-tipo='$tipo' style='display: none;'>";
            echo "<td class='col-check' style='display: none; text-align: center;'><input type='checkbox' class='check-doc' data-url='$urlDescarga' onclick='actualizarContador()' style='transform: scale(1.3); cursor: pointer;'></td>";
            echo "<td><b>$num</b></td>";
            echo "<td style='text-transform: uppercase;'>$nom</td>";
            echo "<td style='text-transform: uppercase;'>$nom</td>";
            echo "<td>$fec_vista</td>";
            echo "<td>L: $lib | F: $fol</td>";
            echo "<td style='text-align: center; white-space: nowrap;'>";
            
            //PDF (Nac / Ext)

            if($tipo === 'Bautismo') {
                echo "<a href='../php/generar_bautismo_pdf.php?id=$idDoc&formato=nacional' target='_blank' style='display: inline-flex; align-items: center; justify-content: center; background-color: #2c3e50 !important; color: #ffffff !important; padding: 6px 12px; text-decoration: none !important; border-radius: 4px; font-size: 0.85rem; margin-right: 5px; border: 1px solid #1a252f; box-shadow: 0 2px 4px rgba(0,0,0,0.1); cursor: pointer;' title='Formato Nacional'><i class='fas fa-flag' style='margin-right: 5px;'></i> Nacional</a>";
                
                echo "<a href='../php/generar_bautismo_pdf.php?id=$idDoc&formato=exterior' target='_blank' style='display: inline-flex; align-items: center; justify-content: center; background-color: #8c6b14 !important; color: #ffffff !important; padding: 6px 12px; text-decoration: none !important; border-radius: 4px; font-size: 0.85rem; margin-right: 15px; border: 1px solid #6b510f; box-shadow: 0 2px 4px rgba(0,0,0,0.1); cursor: pointer;' title='Formato para Exterior'><i class='fas fa-globe' style='margin-right: 5px;'></i> Exterior</a>";
            } else {
                $archivo_pdf = '';
                if($tipo === 'Confirmacion') { $archivo_pdf = 'generar_confirmacion_pdf.php'; }
                if($tipo === 'Matrimonio') { $archivo_pdf = 'generar_matrimonio_pdf.php'; }
                
                echo "<a href='../php/$archivo_pdf?id=$idDoc' target='_blank' style='display: inline-flex; align-items: center; justify-content: center; background-color: #8c6b14 !important; color: #ffffff !important; padding: 6px 12px; text-decoration: none !important; border-radius: 4px; font-size: 0.85rem; margin-right: 15px; border: 1px solid #6b510f; box-shadow: 0 2px 4px rgba(0,0,0,0.1); cursor: pointer;'><i class='fas fa-file-pdf' style='margin-right: 5px;'></i> Ver PDF</a>";
            }

            //ZONA DE ACCIÓN (Editar y Eliminar)

            $nom_seguro = htmlspecialchars($nom, ENT_QUOTES, 'UTF-8');

            echo "<button data-json='$json_seguro' onclick='editarDocumento(this, $idDoc, \"$tipo\", \"$nom_seguro\", \"$fec_input\", \"$lib\", \"$fol\", \"$num\")' class='boton-accion-tarjeta' style='background: #3498db; border: none; padding: 6px 10px; margin-right: 5px;' title='Editar Registro'><i class='fas fa-edit'></i></button>";

            echo "<button type='button' onclick='abrirModalEliminarActa($idDoc)' style='background: #e74c3c; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer;' title='Eliminar Registro'><i class='fas fa-trash'></i></button>";

            echo "</td>";
            echo "</tr>";
        }
    }
} catch(Exception $e) {
    echo "<tr><td colspan='5' style='color: red; text-align: center; padding: 20px;'>Error de Base de Datos: " . $e->getMessage() . "</td></tr>";
}
?>
                    </tbody>
                </table>
            </div>
            
            <style>
                body.oscuro #mensaje-sin-archivos {
                    background-color: rgba(0, 0, 0, 0.2) !important; 
                    border: 1px dashed rgba(198, 156, 109, 0.4) !important; 
                }
                body.oscuro #mensaje-sin-archivos p {
                    color: #e0e0e0 !important; 
                }
            </style>
            <div id="mensaje-sin-archivos" style="display: none; text-align: center; padding: 40px; background: rgba(0,0,0,0.03); border: 1px dashed #ccc; margin-top: 20px; border-radius: 8px;">
                <p style="color: #555; font-size: 1.1rem;"><i class="fas fa-info-circle"></i> No hay documentos guardados en esta categoría.</p>
            </div>

        </div>

        <script>

            //REGRESAR A CARPETAS (VERSIÓN ANIMADA)
            function volverCategoriasArchivos() {
                let vistaTabla = document.getElementById('vista-tabla-archivos');
                let vistaCategorias = document.getElementById('vista-categorias-archivos');

                vistaTabla.style.transition = 'opacity 0.2s ease, transform 0.2s ease';
                vistaTabla.style.opacity = '0';
                vistaTabla.style.transform = 'translateY(10px)';

                setTimeout(() => {
                    vistaTabla.style.display = 'none';
                    vistaTabla.style.transform = 'translateY(0)';
                    
                    vistaCategorias.style.display = 'grid';
                    
                    vistaCategorias.style.animation = 'none';
                    void vistaCategorias.offsetWidth;
                    vistaCategorias.style.animation = 'aparecer 0.4s ease-out forwards';
                    vistaCategorias.style.opacity = '1';
                }, 200);
            }

            //ABRIR CARPETA Y MOSTRAR TABLA
            function mostrarTablaArchivos(tipoDocumento) {
                let vistaCategorias = document.getElementById('vista-categorias-archivos');
                let vistaTabla = document.getElementById('vista-tabla-archivos');

                vistaCategorias.style.transition = 'opacity 0.2s ease, transform 0.2s ease';
                vistaCategorias.style.opacity = '0';
                vistaCategorias.style.transform = 'translateY(10px)';

                setTimeout(() => {
                    vistaCategorias.style.display = 'none';
                    vistaCategorias.style.transform = 'translateY(0)';
                    vistaCategorias.style.opacity = '1';

                    vistaTabla.style.display = 'block';
                    
                    vistaTabla.style.animation = 'none';
                    void vistaTabla.offsetWidth; 
                    vistaTabla.style.animation = 'aparecer 0.4s ease-out forwards';
                    vistaTabla.style.opacity = '1';
                    
                    let titulo = 'Registros de ' + tipoDocumento;
                    if (tipoDocumento === 'Bautismo') titulo = 'Registros de Bautismos';
                    if (tipoDocumento === 'Confirmacion') titulo = 'Registros de Confirmaciones';
                    if (tipoDocumento === 'Matrimonio') titulo = 'Registros de Matrimonios';
                    document.getElementById('titulo-tabla-archivos').innerText = titulo;
                    
                    let filas = document.querySelectorAll('.fila-archivo');
                    let hayDatos = false;
                    
                    filas.forEach(fila => {
                        if(fila.getAttribute('data-tipo') === tipoDocumento) {
                            fila.style.display = 'table-row';
                            hayDatos = true;
                        } else {
                            fila.style.display = 'none';
                        }
                    });
                    
                    const tablaCont = document.querySelector('#vista-tabla-archivos .contenedor-tabla-responsiva');
                    const msgVacio = document.getElementById('mensaje-sin-archivos');

                    if(hayDatos) {
                        tablaCont.style.display = 'block';
                        if(msgVacio) msgVacio.style.display = 'none';
                    } else {
                        tablaCont.style.display = 'none';
                        if(msgVacio) msgVacio.style.display = 'block';
                    }
                }, 200);
            }

        //AUTO-LLENADO MODAL DE EDICIÓN CONFIRMACION
        function editarDocumento(botonFormulario, id, tipo, nombre, fechaSacramento, libro, folio, numero) {
         
            let jsonSeguro = botonFormulario.getAttribute('data-json');
            let datosEspeciales = {};
            try {
                datosEspeciales = JSON.parse(jsonSeguro);
            } catch(e) { console.error("Error al leer JSON:", e); }

            if (tipo === 'Bautismo') {
                document.getElementById('edit-bautismo-id').value = id;
                document.getElementById('edit-bautismo-nombre').value = nombre;
                document.getElementById('edit-bautismo-fsac').value = fechaSacramento;
                document.getElementById('edit-bautismo-libro').value = libro;
                document.getElementById('edit-bautismo-folio').value = folio;
                document.getElementById('edit-bautismo-num').value = numero;
                document.getElementById('edit-bautismo-fnac').value = datosEspeciales.fecha_nacimiento || '';
                document.getElementById('edit-bautismo-ciudad').value = datosEspeciales.ciudad_nacimiento || datosEspeciales.lugar_nacimiento || '';
                document.getElementById('edit-bautismo-estado').value = datosEspeciales.estado_nacimiento || '';
                document.getElementById('edit-bautismo-padre').value = datosEspeciales.nombre_padre || '';
                document.getElementById('edit-bautismo-madre').value = datosEspeciales.nombre_madre || '';
                document.getElementById('edit-bautismo-padrino').value = datosEspeciales.nombre_padrino || '';
                document.getElementById('edit-bautismo-madrina').value = datosEspeciales.nombre_madrina || '';
                document.getElementById('edit-bautismo-ministro').value = datosEspeciales.ministro || '';
                
                abrirModal('modal-editar-bautismo');
                
            } else if (tipo === 'Confirmacion') {
                document.getElementById('edit-conf-id').value = id;
                document.getElementById('edit-conf-nombre').value = nombre;
                document.getElementById('edit-conf-fsac').value = fechaSacramento;
                document.getElementById('edit-conf-libro').value = libro;
                document.getElementById('edit-conf-folio').value = folio;
                document.getElementById('edit-conf-num').value = numero;
                
                document.getElementById('edit-conf-fnac').value = datosEspeciales.fecha_nacimiento || '';
                document.getElementById('edit-conf-ciudad').value = datosEspeciales.ciudad_nacimiento || '';
                document.getElementById('edit-conf-estado').value = datosEspeciales.estado_nacimiento || '';
                
                document.getElementById('edit-conf-padre').value = datosEspeciales.nombre_padre || '';
                document.getElementById('edit-conf-madre').value = datosEspeciales.nombre_madre || '';
                document.getElementById('edit-conf-padrino').value = datosEspeciales.nombre_padrino || '';
                document.getElementById('edit-conf-madrina').value = datosEspeciales.nombre_madrina || '';
                document.getElementById('edit-conf-ministro').value = datosEspeciales.ministro || '';
                
                abrirModal('modal-editar-confirmacion');
                
            } else if (tipo === 'Matrimonio') {
                // NUEVO: Llenado de Matrimonio
                document.getElementById('edit-mat-id').value = id;
                document.getElementById('edit-mat-fecha').value = fechaSacramento;
                document.getElementById('edit-mat-libro').value = libro;
                document.getElementById('edit-mat-folio').value = folio;
                document.getElementById('edit-mat-num').value = numero;

                // Datos Esposo
                document.getElementById('edit-mat-nombre-esposo').value = datosEspeciales.nombre_esposo || '';
                document.getElementById('edit-mat-edad-esposo').value = datosEspeciales.edad_esposo || '';
                document.getElementById('edit-mat-viudo-esposo').value = datosEspeciales.viudo_de_esposo || '';
                document.getElementById('edit-mat-natural-esposo').value = datosEspeciales.natural_esposo || '';
                document.getElementById('edit-mat-padre-esposo').value = datosEspeciales.padre_esposo || '';
                document.getElementById('edit-mat-madre-esposo').value = datosEspeciales.madre_esposo || '';
                
                // Setear el Falso Select del Esposo
                let estadoEsposo = datosEspeciales.estado_civil_esposo || 'Soltero';
                document.getElementById('edit-mat-estado-esposo-hidden').value = estadoEsposo;
                document.getElementById('texto-edit-estado-esposo').innerText = estadoEsposo;

                // Datos Esposa
                document.getElementById('edit-mat-nombre-esposa').value = datosEspeciales.nombre_esposa || '';
                document.getElementById('edit-mat-edad-esposa').value = datosEspeciales.edad_esposa || '';
                document.getElementById('edit-mat-viuda-esposa').value = datosEspeciales.viuda_de_esposa || '';
                document.getElementById('edit-mat-natural-esposa').value = datosEspeciales.natural_esposa || '';
                document.getElementById('edit-mat-padre-esposa').value = datosEspeciales.padre_esposa || '';
                document.getElementById('edit-mat-madre-esposa').value = datosEspeciales.madre_esposa || '';
                
                // Setear el Falso Select de la Esposa
                let estadoEsposa = datosEspeciales.estado_civil_esposa || 'Soltera';
                document.getElementById('edit-mat-estado-esposa-hidden').value = estadoEsposa;
                document.getElementById('texto-edit-estado-esposa').innerText = estadoEsposa;

                // Datos Generales
                document.getElementById('edit-mat-ministro').value = datosEspeciales.ministro || '';
                document.getElementById('edit-mat-testigos').value = datosEspeciales.testigos || '';

                abrirModal('modal-editar-matrimonio');
                
            } else {
                alert("La edición para actas de " + tipo + " estará disponible próximamente.");
            }
        }

        </script>
    </section>
    <?php endif; ?>

    <div id="modal-actividad" class="modal-catedral">
        <div class="modal-contenido">
            <div class="modal-cabecera">
                <h3>Registrar Actividad</h3>
                <button class="btn-cerrar-modal" onclick="cerrarModal('modal-actividad')"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-cuerpo">
                <form method="POST" action="../php/controlador.php" autocomplete="off">
                    <input type="hidden" name="accion" value="nueva-actividad">
                    <div class="grupo-entrada">
                        <label><i class="fas fa-edit" style="color:var(--acento-dorado);"></i> Nombre de la Actividad:</label>
                        <input type="text" name="act-nombre" class="input-estilo-catedral" required>
                    </div>
                    <div class="grupo-entrada">
                        <label><i class="fas fa-layer-group" style="color:var(--acento-dorado);"></i> Categoría:</label>
                        <div class="select-personalizado-catedral">
                            <input type="hidden" name="act-categoria" id="crear-act-categoria-hidden" required>
                            <div class="select-trigger-catedral" onclick="toggleSelectCatedral(this)">
                                <span class="select-texto">Seleccione una categoría...</span>
                                <i class="fas fa-chevron-down select-icono"></i>
                            </div>
                            <ul class="select-opciones-catedral">
                                <li onclick="seleccionarOpcionCatedral(this, 'Formación', 'Formación Pastoral', 'crear-act-categoria-hidden')">Formación Pastoral</li>
                                <li onclick="seleccionarOpcionCatedral(this, 'Grupo Devocional', 'Grupo Devocional', 'crear-act-categoria-hidden')">Grupo Devocional</li>
                                <li onclick="seleccionarOpcionCatedral(this, 'Reunión', 'Reunión General', 'crear-act-categoria-hidden')">Reunión General</li>
                            </ul>
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="grupo-entrada">
                            <label><i class="fas fa-calendar-day" style="color:var(--acento-dorado);"></i> Días:</label>
                            <input type="text" name="act-dias" class="input-estilo-catedral" required>
                        </div>
                        <div class="grupo-entrada">
                            <label><i class="fas fa-clock" style="color:var(--acento-dorado);"></i> Hora de Inicio:</label>
                            <input type="time" name="act-hora" class="input-estilo-catedral" required>
                        </div>
                    </div>
                    <div class="grupo-entrada">
                        <label><i class="fas fa-map-marker-alt" style="color:var(--acento-dorado);"></i> Lugar:</label>
                        <input type="text" name="act-lugar" value="Catedral Metropolitana" class="input-estilo-catedral" required>
                    </div>
                    <div class="contenedor-botones-modal">
                        <button type="button" class="boton-sagrado-secundario" onclick="cerrarModal('modal-actividad')">Cancelar</button>
                        <button type="submit" class="boton-sagrado-primario"><i class="fas fa-save"></i> Guardar Registro</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="modal-editar-actividad" class="modal-catedral">
        <div class="modal-contenido">
            <div class="modal-cabecera">
                <h3>Modificar Actividad</h3>
                <button class="btn-cerrar-modal" onclick="cerrarModal('modal-editar-actividad')"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-cuerpo">
                <form method="POST" action="../php/controlador.php" autocomplete="off">
                    <input type="hidden" name="accion" value="editar-actividad">
                    <input type="hidden" name="act-id" id="edit-act-id">
                    <div class="grupo-entrada">
                        <label>Nombre de la Actividad:</label>
                        <input type="text" name="act-nombre" id="edit-act-nombre" class="input-estilo-catedral" required>
                    </div>
                    <div class="grupo-entrada">
                        <label>Categoría:</label>
                        <div class="select-personalizado-catedral">
                            <input type="hidden" name="act-categoria" id="edit-act-categoria-hidden" required>
                            <div class="select-trigger-catedral" id="edit-act-categoria-trigger" onclick="toggleSelectCatedral(this)">
                                <span class="select-texto">Seleccione una categoría...</span>
                                <i class="fas fa-chevron-down select-icono"></i>
                            </div>
                            <ul class="select-opciones-catedral">
                                <li onclick="seleccionarOpcionCatedral(this, 'Formación', 'Formación Pastoral', 'edit-act-categoria-hidden')">Formación Pastoral</li>
                                <li onclick="seleccionarOpcionCatedral(this, 'Grupo Devocional', 'Grupo Devocional', 'edit-act-categoria-hidden')">Grupo Devocional</li>
                                <li onclick="seleccionarOpcionCatedral(this, 'Reunión', 'Reunión General', 'edit-act-categoria-hidden')">Reunión General</li>
                            </ul>
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="grupo-entrada">
                            <label>Días:</label>
                            <input type="text" name="act-dias" id="edit-act-dias" class="input-estilo-catedral" required>
                        </div>
                        <div class="grupo-entrada">
                            <label>Hora de Inicio:</label>
                            <input type="time" name="act-hora" id="edit-act-hora" class="input-estilo-catedral" required>
                        </div>
                    </div>
                    <div class="grupo-entrada">
                        <label>Lugar:</label>
                        <input type="text" name="act-lugar" id="edit-act-lugar" class="input-estilo-catedral" required>
                    </div>
                    <div class="contenedor-botones-modal">
                        <button type="button" class="boton-sagrado-secundario" onclick="cerrarModal('modal-editar-actividad')">Cancelar</button>
                        <button type="submit" class="boton-sagrado-primario"><i class="fas fa-sync-alt"></i> Actualizar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="modal-confirmar-eliminacion" class="modal-catedral">
        <div class="modal-contenido" style="max-width: 400px; text-align: center; border-top: 5px solid var(--acento-rojo);">
            <div class="modal-cuerpo" style="padding: 30px 20px;">
                <i class="fas fa-exclamation-triangle" style="font-size: 3.5rem; color: var(--acento-rojo); margin-bottom: 20px;"></i>
                <h3 class="titulo-advertencia">¿Eliminar Registro?</h3>
                <style>
                    body.oscuro .titulo-advertencia {
                        color: #8AB4F8 !important; 
                        text-shadow: 0px 1px 2px rgba(0,0,0,0.8) !important;
                    }
                    
                    body.oscuro #texto-peligro,
                    body.oscuro #texto-peligro small {
                        color: #ffffff !important;
                        text-shadow: 0px 1px 3px rgba(0,0,0,0.8) !important;
                    }
                    
                    body.oscuro #nombre-eliminar-display {
                        color: #E88B8B !important;
                    }
                </style>
                
                <p id="texto-peligro" style="margin-bottom: 25px; line-height: 1.5;">
                    ¿Está seguro de que desea eliminar la actividad <br>
                    <strong id="nombre-eliminar-display" style="font-size: 1.1rem; color: var(--acento-rojo);"></strong>? <br>
                    <small style="opacity: 0.8; display: block; margin-top: 10px;">Esta acción es irreversible.</small>
                </p>
                
                <form method="POST" action="../php/controlador.php" style="margin: 0;">
                    <input type="hidden" name="accion" value="eliminar-actividad">
                    <input type="hidden" name="act-id" id="input-eliminar-id">
                    
                    <div style="display: flex; gap: 10px; justify-content: center;">
                        <button type="button" class="boton-sagrado-secundario" onclick="cerrarModal('modal-confirmar-eliminacion')">Cancelar</button>
                        <button type="submit" class="boton-sagrado-primario" style="background: linear-gradient(135deg, var(--acento-rojo), #521818); box-shadow: 0 4px 10px rgba(122, 40, 40, 0.3); border-color: transparent;"><i class="fas fa-trash-alt"></i> Sí, Eliminar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php if($esAdmin): ?>
    <section id="mod-gestion-usuarios" class="modulo">
        <div class="encabezado-modulo">
            <h2><i class="fas fa-users-cog"></i> Gestión de Usuarios</h2>
            <p style="margin: 0; opacity: 0.8;">Administre los accesos y roles de las personas registradas en el sistema.</p>
        </div>

        <div class="cuadricula-tarjetas" style="grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));">
            <?php foreach($listaUsuarios as $user):
                $rolId = $user['id_rol'];
                
                if ($rolId == 3) {
                    $badgeClase = 'etiqueta-suministro';
                    $rolTexto = 'Administrador';
                    $iconoRol = 'fa-user-shield';
                    $colorBorde = 'var(--primario-oscuro)';
                } elseif ($rolId == 2) {
                    $badgeClase = 'etiqueta-insumo';
                    $rolTexto = 'Secretario';
                    $iconoRol = 'fa-user-edit';
                    $colorBorde = 'var(--acento-dorado)';
                } else {
                    $badgeClase = 'etiqueta-monetaria';
                    $rolTexto = 'Ciudadano';
                    $iconoRol = 'fa-user';
                    $colorBorde = '#6b9071';
                }
            ?>
            <div class="tarjeta-ofrenda" style="border-left: 4px solid <?php echo $colorBorde; ?>;">
                <div class="ofrenda-cabecera">
                    <span style="font-weight: bold; font-size: 1.1rem;"><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($user['usuario']); ?></span>
                    <span class="ofrenda-metodo <?php echo $badgeClase; ?>"><i class="fas <?php echo $iconoRol; ?>"></i> <?php echo $rolTexto; ?></span>
                </div>
                <div class="ofrenda-cuerpo" style="padding: 15px 0; text-align: left;">
                    <div class="donacion-dato-fila">
                        <span class="donacion-dato-etiqueta">Nombre Completo:</span>
                        <span class="donacion-dato-valor"><?php echo htmlspecialchars($user['nombre']); ?></span>
                    </div>
                </div>
                <div class="ofrenda-acciones">
                    <button class="boton-accion-tarjeta btn-editar" title="Editar Usuario" onclick="abrirModalEditarUsuarioAdmin(<?php echo $user['id']; ?>, '<?php echo addslashes(htmlspecialchars($user['nombre'])); ?>', '<?php echo addslashes(htmlspecialchars($user['usuario'])); ?>', <?php echo $user['id_rol']; ?>)"><i class="fas fa-user-edit"></i> Editar</button>
                    
                    <?php if($user['id'] != $idUsuario): ?>
                        <?php
                        $estado_usuario = $user['estado'] ?? 'Activo';
                        $color_boton = ($estado_usuario === 'Activo') ? '#8c6b14' : '#6b9071';
                        $icono_boton = ($estado_usuario === 'Activo') ? 'fa-user-slash' : 'fa-user-check';
                        $titulo_boton = ($estado_usuario === 'Activo') ? 'Suspender Usuario' : 'Restaurar Usuario';
                        $nombre_seguro = addslashes(htmlspecialchars($user['nombre']));

                        echo "<button type='button' onclick='cambiarEstadoUsuario({$user['id']}, \"$estado_usuario\", \"{$nombre_seguro}\")' style='background: $color_boton; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; margin-right: 5px;' title='$titulo_boton'><i class='fas $icono_boton'></i></button>";
                        ?>
                        <button class="boton-accion-tarjeta btn-eliminar" title="Revocar Acceso" onclick="confirmarEliminarUsuarioAdmin(<?php echo $user['id']; ?>, '<?php echo addslashes(htmlspecialchars($user['usuario'])); ?>')"><i class="fas fa-trash-alt"></i></button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <section id="mod-perfil" class="modulo">
        <div class="encabezado-modulo" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
            
            <div>
                <h2><i class="fas fa-cogs"></i> Ajustes del Sistema y Perfil</h2>
                <p style="margin: 0; opacity: 0.8;">Gestione su cuenta personal, la seguridad y el respaldo del sistema.</p>
            </div>
            
            <div style="display: flex; gap: 10px;">
                <?php if($rolSesion == 3): ?>
                    <a href="../recursos/manuales/manual_tecnico.pdf" target="_blank" class="boton-sagrado-secundario" style="text-decoration: none !important; padding: 10px 15px; border-radius: 6px; display: inline-flex; align-items: center; gap: 8px;">
                        <i class="fas fa-tools"></i> Manual Técnico
                    </a>
                    <a href="../recursos/manuales/manual_administrador.pdf" target="_blank" class="boton-sagrado-primario" style="text-decoration: none !important; padding: 10px 15px; border-radius: 6px; display: inline-flex; align-items: center; gap: 8px;">
                        <i class="fas fa-book"></i> Manual Usuario
                    </a>

                <?php elseif($rolSesion == 2): ?>
                    <a href="../recursos/manuales/manual_secretario.pdf" target="_blank" class="boton-sagrado-primario" style="text-decoration: none !important; padding: 10px 15px; border-radius: 6px; display: inline-flex; align-items: center; gap: 8px;">
                        <i class="fas fa-book"></i> Manual Usuario
                    </a>

                <?php else: ?>
                    <a href="../recursos/manuales/manual_ciudadano.pdf" target="_blank" class="boton-sagrado-primario" style="text-decoration: none !important; padding: 10px 15px; border-radius: 6px; display: inline-flex; align-items: center; gap: 8px;">
                        <i class="fas fa-book"></i> Manual Usuario
                    </a>
                <?php endif; ?>
            </div>

        </div>


        <div class="grid-perfil-botones">
            <div class="tarjeta-boton-perfil" onclick="abrirModal('modal-editar-usuario')">
                <i class="fas fa-user-edit icono-gigante"></i>
                <h3>Usuario de Acceso</h3>
                <p>Cambie su nombre de usuario.</p>
            </div>
            
            <div class="tarjeta-boton-perfil" onclick="abrirModal('modal-cambiar-clave')">
                <i class="fas fa-key icono-gigante"></i>
                <h3>Contraseña</h3>
                <p>Actualice su clave de seguridad.</p>
            </div>
            
            <div class="tarjeta-boton-perfil" onclick="resetearModalPreguntas(); abrirModal('modal-editar-preguntas')">
                <i class="fas fa-shield-alt icono-gigante"></i>
                <h3>Preguntas Secretas</h3>
                <p>Modifique sus 4 preguntas.</p>
            </div>

            <div class="tarjeta-boton-perfil danger-zone" onclick="abrirModal('modal-eliminar-cuenta')">
                <i class="fas fa-user-times icono-gigante"></i>
                <h3>Eliminar Cuenta</h3>
                <p>Borrado permanente del perfil.</p>
            </div>

            <?php if($esAdmin): ?>
            <div class="tarjeta-boton-perfil" onclick="abrirModal('modal-respaldo-db')" style="border-top: 4px solid var(--acento-dorado);">
                <i class="fas fa-cloud-download-alt icono-gigante"></i>
                <h3>Respaldo del Sistema</h3>
                <p>Despliegue el panel para Exportar o Importar la base de datos.</p>
            </div>
            <?php endif; ?>
        </div>
    </section>
</div>

    <script>
        const eventosAgendaDB = <?php echo json_encode($eventosAgenda); ?>;
    
        const graficaActNombres = <?php echo json_encode(array_keys($conteoActividades)); ?>;
        const graficaActValores = <?php echo json_encode(array_values($conteoActividades)); ?>;
        
        const graficaDonNombres = <?php echo json_encode(array_keys($conteoDonaciones)); ?>;
        const graficaDonValores = <?php echo json_encode(array_values($conteoDonaciones)); ?>;
    </script>

    <?php if($esSecretario): ?>

    <div id="panel-actividad" class="panel-lateral-derecho">
        <div class="panel-lateral-cabecera" style="display: flex; flex-direction: column; gap: 15px; padding-bottom: 15px; border-bottom: 1px solid rgba(198, 156, 109, 0.2);">
            <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                <h3 style="margin: 0;"><i class="fas fa-history"></i> Historial del Sistema</h3>
                <button class="btn-cerrar-panel" onclick="togglePanelActividad()"><i class="fas fa-times"></i></button>
            </div>
            
            <?php if(!empty($historialActividad)): ?>
            <div id="botones-accion-notificaciones" style="display: flex; gap: 10px; width: 100%;">
                <button onclick="marcarTodasVistas()" class="texto-azul-adaptable" style="flex: 1; background: rgba(198, 156, 109, 0.1); border: 1px solid var(--acento-dorado); border-radius: 6px; padding: 8px; cursor: pointer; transition: 0.3s; font-size: 0.85rem; font-weight: bold;">
                    <i class="fas fa-check-double"></i> Marcar Vistas
                </button>
                <button onclick="confirmarEliminarTodasNotif()" style="flex: 1; background: rgba(230, 57, 70, 0.1); border: 1px solid var(--acento-rojo); color: var(--acento-rojo); border-radius: 6px; padding: 8px; cursor: pointer; transition: 0.3s; font-size: 0.85rem; font-weight: bold;">
                    <i class="fas fa-trash-alt"></i> Borrar Todo
                </button>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="panel-lateral-cuerpo" id="lista-actividades-panel">
            <?php if(empty($historialActividad)): ?>
                <div style="text-align: center; padding: 30px; opacity: 0.6;">
                    <i class="fas fa-bed" style="font-size: 3rem; margin-bottom: 10px;"></i>
                    <p>El sistema está en paz. No hay actividad reciente.</p>
                </div>
            <?php else: ?>
                <?php foreach($historialActividad as $hist): 
                    // Asignar un ícono dependiendo del módulo afectado
                    $iconoHist = 'fas fa-info-circle';
                    if($hist['modulo'] == 'Donaciones') $iconoHist = 'fas fa-hand-holding-usd';
                    elseif($hist['modulo'] == 'Agenda') $iconoHist = 'far fa-calendar-alt';
                    elseif($hist['modulo'] == 'Usuarios') $iconoHist = 'fas fa-users-cog';
                ?>
                    <div class="item-actividad <?php echo !$hist['ya_lo_vio'] ? 'no-visto' : ''; ?>" data-id="<?php echo $hist['id']; ?>" onclick="toggleDetalleActividad(this)">
                        <div class="actividad-resumen">
                            <div class="actividad-icono"><i class="<?php echo $iconoHist; ?>"></i></div>
                            <div class="actividad-info-corta">
                                <span class="act-modulo">
                                    <?php echo htmlspecialchars($hist['modulo']); ?>
                                    <?php if(!$hist['ya_lo_vio']): ?><span class="punto-nuevo"></span><?php endif; ?>
                                </span>
                                <span class="act-tiempo"><?php echo date('d M, g:i A', strtotime($hist['fecha_accion'])); ?></span>
                            </div>
                            <i class="fas fa-chevron-down flecha-actividad"></i>
                        </div>
                        <div class="actividad-detalle">
                            <p><strong>Detalle:</strong> <?php echo htmlspecialchars($hist['accion']); ?></p>
                            <p><strong>Autor:</strong> <i class="fas fa-user-edit"></i> <?php echo htmlspecialchars($hist['nombre']); ?></p>
                            <p><strong>Fecha Exacta:</strong> <?php echo date('d/m/Y - h:i:s A', strtotime($hist['fecha_accion'])); ?></p>
                            
                            <div style="text-align: right; margin-top: 10px; border-top: 1px dashed rgba(198, 156, 109, 0.3); padding-top: 10px;">
                                <button class="btn-eliminar-notificacion" onclick="eliminarNotificacion(event, <?php echo $hist['id']; ?>, this)">
                                    <i class="fas fa-trash-alt"></i> Borrar Registro
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <?php if(!empty($historialActividad)): ?>
        <div style="text-align: center; padding: 15px; border-top: 1px solid rgba(198, 156, 109, 0.2);">
            <button type="button" id="btn-cargar-mas-actividad" class="boton-sagrado-secundario" style="display: none; width: 100%; border-radius: 20px;">
                <i class="fas fa-chevron-down"></i> Ver más registros
            </button>
        </div>
        <?php endif; ?>
        
    </div>
    <?php endif; ?>

    <div id="modal-agenda" class="modal-catedral">
        <div class="modal-contenido">
            <div class="modal-cabecera">
                <h3>Agendar Actividad</h3>
                <button class="btn-cerrar-modal" onclick="cerrarModal('modal-agenda')"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-cuerpo">
                <form method="POST" action="../php/controlador.php" autocomplete="off">
                    <input type="hidden" name="accion" value="nueva-agenda">
                    <input type="hidden" name="agenda-fecha" id="crear-agenda-fecha">
                    
                    <p style="text-align: center; font-weight: bold; color: var(--acento-dorado); margin-bottom: 20px;" id="texto-fecha-seleccionada"></p>

                    <div class="grupo-entrada"><label>Título (Ej. Boda de Juan y María):</label><input type="text" name="agenda-titulo" class="input-estilo-catedral" required></div>
                    
                    <div class="grupo-entrada">
                        <label>Tipo de Actividad:</label>
                        <div class="select-personalizado-catedral">
                            <input type="hidden" name="agenda-tipo" id="crear-agenda-tipo-hidden">
                            <div class="select-trigger-catedral" onclick="toggleSelectCatedral(this)">
                                <span class="select-texto">Seleccione un tipo...</span>
                                <i class="fas fa-chevron-down select-icono"></i>
                            </div>
                            <ul class="select-opciones-catedral">
                                <li onclick="seleccionarOpcionAgenda(this, 'Boda', 'Boda', 'crear-agenda-tipo-hidden', 'contenedor-otro-crear')">Boda</li>
                                <li onclick="seleccionarOpcionAgenda(this, 'Comunión', 'Comunión', 'crear-agenda-tipo-hidden', 'contenedor-otro-crear')">Comunión</li>
                                <li onclick="seleccionarOpcionAgenda(this, 'Bautizo', 'Bautizo', 'crear-agenda-tipo-hidden', 'contenedor-otro-crear')">Bautizo</li>
                                <li onclick="seleccionarOpcionAgenda(this, 'Misa Especial', 'Misa Especial', 'crear-agenda-tipo-hidden', 'contenedor-otro-crear')">Misa Especial</li>
                                <li onclick="seleccionarOpcionAgenda(this, 'Mantenimiento', 'Mantenimiento', 'crear-agenda-tipo-hidden', 'contenedor-otro-crear')">Mantenimiento</li>
                                <li onclick="seleccionarOpcionAgenda(this, 'Otro', 'Otro (Especificar)', 'crear-agenda-tipo-hidden', 'contenedor-otro-crear')">Otro</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="grupo-entrada" id="contenedor-otro-crear" style="display: none;">
                        <label>Especifique la Actividad:</label>
                        <input type="text" name="agenda-tipo-otro" id="input-otro-crear" class="input-estilo-catedral" placeholder="Ej. Retiro Espiritual">
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="grupo-entrada"><label>Hora Inicio:</label><input type="time" name="agenda-hora-inicio" class="input-estilo-catedral" required></div>
                        <div class="grupo-entrada"><label>Hora Fin:</label><input type="time" name="agenda-hora-fin" class="input-estilo-catedral" required></div>
                    </div>

                    <div class="contenedor-botones-modal">
                        <button type="button" class="boton-sagrado-secundario" onclick="cerrarModal('modal-agenda')">Cancelar</button>
                        <button type="submit" class="boton-sagrado-primario"><i class="fas fa-calendar-check"></i> Agendar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="modal-editar-agenda" class="modal-catedral">
        <div class="modal-contenido">
            <div class="modal-cabecera">
                <h3>Detalles del Evento</h3>
                <button type="button" class="btn-cerrar-modal" onclick="cerrarModal('modal-editar-agenda')"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-cuerpo">
                <form id="form-editar-agenda-bd" method="POST" action="../php/controlador.php" autocomplete="off">
                    <input type="hidden" name="accion" value="editar-agenda">
                    <input type="hidden" name="agenda-id" id="edit-agenda-id">
                    <input type="hidden" name="agenda-fecha" id="edit-agenda-fecha">
                    
                    <div class="grupo-entrada">
                        <label>Título:</label>
                        <input type="text" name="agenda-titulo" id="edit-agenda-titulo" class="input-estilo-catedral" required>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="grupo-entrada" style="grid-column: 1 / -1;">
                            <label>Tipo:</label>
                            <div class="select-personalizado-catedral">
                                <input type="hidden" name="agenda-tipo" id="edit-agenda-tipo-hidden">
                                <div class="select-trigger-catedral" id="edit-agenda-tipo-trigger" onclick="toggleSelectCatedral(this)">
                                    <span class="select-texto">Seleccione un tipo...</span>
                                    <i class="fas fa-chevron-down select-icono"></i>
                                </div>
                                <ul class="select-opciones-catedral">
                                    <li onclick="seleccionarOpcionAgenda(this, 'Boda', 'Boda', 'edit-agenda-tipo-hidden', 'contenedor-otro-editar')">Boda</li>
                                    <li onclick="seleccionarOpcionAgenda(this, 'Comunión', 'Comunión', 'edit-agenda-tipo-hidden', 'contenedor-otro-editar')">Comunión</li>
                                    <li onclick="seleccionarOpcionAgenda(this, 'Bautizo', 'Bautizo', 'edit-agenda-tipo-hidden', 'contenedor-otro-editar')">Bautizo</li>
                                    <li onclick="seleccionarOpcionAgenda(this, 'Misa Especial', 'Misa Especial', 'edit-agenda-tipo-hidden', 'contenedor-otro-editar')">Misa Especial</li>
                                    <li onclick="seleccionarOpcionAgenda(this, 'Mantenimiento', 'Mantenimiento', 'edit-agenda-tipo-hidden', 'contenedor-otro-editar')">Mantenimiento</li>
                                    <li onclick="seleccionarOpcionAgenda(this, 'Otro', 'Otro (Especificar)', 'edit-agenda-tipo-hidden', 'contenedor-otro-editar')">Otro</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="grupo-entrada" id="contenedor-otro-editar" style="display: none; grid-column: 1 / -1;">
                            <label>Especifique la Actividad:</label>
                            <input type="text" name="agenda-tipo-otro" id="input-otro-editar" class="input-estilo-catedral">
                        </div>
                        <div class="grupo-entrada">
                            <label>Estado de la Actividad:</label>
                            <div class="select-personalizado-catedral">
                                <input type="hidden" name="agenda-estado" id="edit-agenda-estado-hidden">
                                <div class="select-trigger-catedral" id="edit-agenda-estado-trigger" onclick="toggleSelectCatedral(this)">
                                    <span class="select-texto">Seleccione un estado...</span>
                                    <i class="fas fa-chevron-down select-icono"></i>
                                </div>
                                <ul class="select-opciones-catedral">
                                    <li onclick="seleccionarOpcionCatedral(this, 'Agendado', 'Agendado', 'edit-agenda-estado-hidden')">Agendado</li>
                                    <li onclick="seleccionarOpcionCatedral(this, 'Realizado', 'Realizado', 'edit-agenda-estado-hidden')">Realizado</li>
                                    <li onclick="seleccionarOpcionCatedral(this, 'Cancelado', 'Cancelado', 'edit-agenda-estado-hidden')">Cancelado</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 15px;">
                        <div class="grupo-entrada"><label>Hora Inicio:</label><input type="time" name="agenda-hora-inicio" id="edit-agenda-hora-inicio" class="input-estilo-catedral" required></div>
                        <div class="grupo-entrada"><label>Hora Fin:</label><input type="time" name="agenda-hora-fin" id="edit-agenda-hora-fin" class="input-estilo-catedral" required></div>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 25px; border-top: 1px solid rgba(198, 156, 109, 0.2); padding-top: 20px;">
                        <button type="button" class="btn-eliminar" onclick="confirmarEliminarAgenda()" style="border-radius: 6px; padding: 10px 15px;"><i class="fas fa-trash-alt"></i> Borrar</button>
                        <div style="display: flex; gap: 10px;">
                            <button type="button" class="boton-sagrado-secundario" onclick="cerrarModal('modal-editar-agenda')">Cerrar</button>
                            <button type="button" class="boton-sagrado-primario" onclick="forzarGuardadoAgenda()"><i class="fas fa-sync-alt"></i> Actualizar</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="modal-detalle-dia" class="modal-catedral">
        <div class="modal-contenido" style="max-width: 450px;">
            <div class="modal-cabecera">
                <h3 id="titulo-detalle-dia" style="color: var(--primario-oscuro);">Agenda del Día</h3>
                <button class="btn-cerrar-modal" onclick="cerrarModal('modal-detalle-dia')"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-cuerpo" style="padding: 25px;">
                
                <div id="lista-eventos-dia" style="margin-bottom: 25px;">
                    </div>
                
                <div class="contenedor-botones-modal" style="justify-content: center; border-top: none; padding-top: 0; margin-top: 0;">
                    <?php if($esSecretario): ?>
                    <button type="button" class="boton-sagrado-primario" id="btn-nuevo-evento-dia" style="width: 100%;"><i class="fas fa-plus"></i> Agendar Nueva Actividad</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div id="modal-confirmar-eliminar-agenda" class="modal-catedral">
        <div class="modal-contenido" style="max-width: 400px; text-align: center; border-top: 5px solid var(--acento-rojo);">
            <div class="modal-cuerpo" style="padding: 30px 20px;">
                <i class="fas fa-exclamation-triangle" style="font-size: 3.5rem; color: var(--acento-rojo); margin-bottom: 20px;"></i>
                
                <h3 class="titulo-advertencia">¿Eliminar Evento?</h3>
                
                <style>
                    body.oscuro #texto-peligro-agenda,
                    body.oscuro #texto-peligro-agenda small {
                        color: #ffffff !important;
                        text-shadow: 0px 1px 3px rgba(0,0,0,0.8) !important;
                    }
                    body.oscuro #nombre-eliminar-agenda-display {
                        color: #E88B8B !important;
                    }
                </style>
                
                <p id="texto-peligro-agenda" style="margin-bottom: 25px; line-height: 1.5;">
                    ¿Está seguro de que desea eliminar el evento de la agenda <br>
                    <strong id="nombre-eliminar-agenda-display" style="font-size: 1.1rem; color: var(--acento-rojo);"></strong>? <br>
                    <small style="opacity: 0.8; display: block; margin-top: 10px;">Esta acción es irreversible.</small>
                </p>

                <form method="POST" action="../php/controlador.php" style="margin: 0;">
                    <input type="hidden" name="accion" value="eliminar-agenda">
                    <input type="hidden" name="agenda-id" id="input-eliminar-agenda-id">
                    
                    <div style="display: flex; gap: 10px; justify-content: center; margin-top: 25px;">
                        <button type="button" class="boton-sagrado-secundario" onclick="cerrarModal('modal-confirmar-eliminar-agenda'); abrirModal('modal-editar-agenda');">Cancelar</button>
                        <button type="submit" class="boton-sagrado-primario" style="background: linear-gradient(135deg, var(--acento-rojo), #521818); box-shadow: 0 4px 10px rgba(122, 40, 40, 0.3); border-color: transparent;"><i class="fas fa-trash-alt"></i> Sí, Eliminar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="modal-nueva-donacion" class="modal-catedral">
        <div class="modal-contenido">
            <div class="modal-cabecera">
                <h3>Registrar Ofrenda o Donación</h3>
                <button class="btn-cerrar-modal" onclick="cerrarModal('modal-nueva-donacion')"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-cuerpo">
                <form method="POST" action="../php/controlador.php" autocomplete="off">
                    <input type="hidden" name="accion" value="nueva-donacion">
                    
                    <div class="grupo-entrada">
                        <label>Tipo de Donación:</label>
                        <div class="select-personalizado-catedral">
                            <input type="hidden" name="donacion-tipo" id="crear-donacion-tipo-hidden" value="Monetaria" required>
                            <div class="select-trigger-catedral" onclick="toggleSelectCatedral(this)">
                                <span class="select-texto" id="crear-tipo-texto">Monetaria</span>
                                <i class="fas fa-chevron-down select-icono"></i>
                            </div>
                            <ul class="select-opciones-catedral">
                                <li onclick="seleccionarOpcionDonacion(this, 'Monetaria', 'crear-donacion-tipo-hidden', 'crear-tipo-texto', 'crear')">Monetaria (Dinero)</li>
                                <li onclick="seleccionarOpcionDonacion(this, 'Suministro', 'crear-donacion-tipo-hidden', 'crear-tipo-texto', 'crear')">Suministro (Comida, Agua)</li>
                                <li onclick="seleccionarOpcionDonacion(this, 'Insumo', 'crear-donacion-tipo-hidden', 'crear-tipo-texto', 'crear')">Insumo (Medicinas, Limpieza)</li>
                                <li onclick="seleccionarOpcionDonacion(this, 'Material', 'crear-donacion-tipo-hidden', 'crear-tipo-texto', 'crear')">Material (Construcción, Equipos)</li>
                                <li onclick="seleccionarOpcionDonacion(this, 'Otros', 'crear-donacion-tipo-hidden', 'crear-tipo-texto', 'crear')">Otros</li>
                            </ul>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="grupo-entrada">
                            <label>Donante (Opcional):</label>
                            <input type="text" name="donacion-donante" id="crear-donante" class="input-estilo-catedral" placeholder="Ej. Familia Pérez" style="margin-bottom: 5px;">
                            <span class="texto-ayuda-catedral">Si se deja en blanco, se registrará como "Anónimo".</span>
                        </div>
                        <div class="grupo-entrada"><label>Fecha:</label><input type="date" name="donacion-fecha" class="input-estilo-catedral" value="<?php echo date('Y-m-d'); ?>" required></div>
                    </div>

                    <div id="seccion-monetaria-crear">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div class="grupo-entrada">
                                <label>Monto:</label>
                                <div class="contenedor-moneda-catedral">
                                    <span class="simbolo-moneda">$</span>
                                    <input type="number" step="0.01" min="0.01" name="donacion-monto" id="crear-monto" class="input-estilo-catedral input-moneda" placeholder="0.00">
                                </div>
                            </div>
                            <div class="grupo-entrada">
                                <label>Método de Pago:</label>
                                <div class="select-personalizado-catedral">
                                    <input type="hidden" name="donacion-metodo" id="crear-metodo-hidden" value="Efectivo">
                                    <div class="select-trigger-catedral" id="crear-metodo-trigger" onclick="toggleSelectCatedral(this)">
                                        <span class="select-texto">Efectivo</span>
                                        <i class="fas fa-chevron-down select-icono"></i>
                                    </div>
                                    <ul class="select-opciones-catedral">
                                        <li onclick="seleccionarMetodoPago(this, 'Efectivo', 'crear-metodo-hidden', 'crear')">Efectivo</li>
                                        <li onclick="seleccionarMetodoPago(this, 'Transferencia', 'crear-metodo-hidden', 'crear')">Transferencia</li>
                                        <li onclick="seleccionarMetodoPago(this, 'Pago Móvil', 'crear-metodo-hidden', 'crear')">Pago Móvil</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="grupo-entrada" id="contenedor-referencia-crear" style="display: none;">
                            <label>Referencia:</label>
                            <input type="text" name="donacion-referencia" id="crear-referencia" class="input-estilo-catedral">
                        </div>
                    </div>

                    <div id="seccion-bienes-crear" style="display: none;">
                        <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 15px;">
                            <div class="grupo-entrada">
                                <label>Cantidad:</label>
                                <input type="text" name="donacion-cantidad" id="crear-cantidad" class="input-estilo-catedral" placeholder="Ej. 50 kg, 3 Cajas">
                            </div>
                            <div class="grupo-entrada">
                                <label>Descripción del Bien:</label>
                                <input type="text" name="donacion-descripcion" id="crear-descripcion" class="input-estilo-catedral" placeholder="Ej. Arroz, Cemento, Sillas">
                            </div>
                        </div>
                    </div>

                    <div class="contenedor-botones-modal">
                        <button type="button" class="boton-sagrado-secundario" onclick="cerrarModal('modal-nueva-donacion')">Cancelar</button>
                        <button type="submit" class="boton-sagrado-primario"><i class="fas fa-save"></i> Guardar Registro</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="modal-editar-donacion" class="modal-catedral">
        <div class="modal-contenido">
            <div class="modal-cabecera">
                <h3>Editar Registro de Donación</h3>
                <button class="btn-cerrar-modal" onclick="cerrarModal('modal-editar-donacion')"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-cuerpo">
                <form method="POST" action="../php/controlador.php" autocomplete="off">
                    <input type="hidden" name="accion" value="editar-donacion">
                    <input type="hidden" name="donacion-id" id="edit-donacion-id">
                    
                    <div class="grupo-entrada">
                        <label>Tipo de Donación:</label>
                        <div class="select-personalizado-catedral">
                            <input type="hidden" name="donacion-tipo" id="edit-donacion-tipo-hidden" required>
                            <div class="select-trigger-catedral" onclick="toggleSelectCatedral(this)">
                                <span class="select-texto" id="edit-tipo-texto">Monetaria</span>
                                <i class="fas fa-chevron-down select-icono"></i>
                            </div>
                            <ul class="select-opciones-catedral">
                                <li onclick="seleccionarOpcionDonacion(this, 'Monetaria', 'edit-donacion-tipo-hidden', 'edit-tipo-texto', 'edit')">Monetaria (Dinero)</li>
                                <li onclick="seleccionarOpcionDonacion(this, 'Suministro', 'edit-donacion-tipo-hidden', 'edit-tipo-texto', 'edit')">Suministro (Comida, Agua)</li>
                                <li onclick="seleccionarOpcionDonacion(this, 'Insumo', 'edit-donacion-tipo-hidden', 'edit-tipo-texto', 'edit')">Insumo (Medicinas, Limpieza)</li>
                                <li onclick="seleccionarOpcionDonacion(this, 'Material', 'edit-donacion-tipo-hidden', 'edit-tipo-texto', 'edit')">Material (Construcción, Equipos)</li>
                                <li onclick="seleccionarOpcionDonacion(this, 'Otros', 'edit-donacion-tipo-hidden', 'edit-tipo-texto', 'edit')">Otros</li>
                            </ul>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="grupo-entrada">
                            <label>Donante (Opcional):</label>
                            <input type="text" name="donacion-donante" id="edit-donacion-donante" class="input-estilo-catedral" placeholder="Ej. Familia Pérez" style="margin-bottom: 5px;">
                            <span class="texto-ayuda-catedral">Si se deja en blanco, se registrará como "Anónimo".</span>
                        </div>
                        <div class="grupo-entrada"><label>Fecha:</label><input type="date" name="donacion-fecha" id="edit-donacion-fecha" class="input-estilo-catedral" required></div>
                    </div>

                    <div id="seccion-monetaria-edit">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div class="grupo-entrada">
                                <label>Monto:</label>
                                <div class="contenedor-moneda-catedral">
                                    <span class="simbolo-moneda">$</span>
                                    <input type="number" step="0.01" min="0.01" name="donacion-monto" id="edit-monto" class="input-estilo-catedral input-moneda" placeholder="0.00">
                                </div>
                            </div>
                            <div class="grupo-entrada">
                                <label>Método de Pago:</label>
                                <div class="select-personalizado-catedral">
                                    <input type="hidden" name="donacion-metodo" id="edit-metodo-hidden" value="Efectivo">
                                    <div class="select-trigger-catedral" id="edit-metodo-trigger" onclick="toggleSelectCatedral(this)">
                                        <span class="select-texto">Efectivo</span>
                                        <i class="fas fa-chevron-down select-icono"></i>
                                    </div>
                                    <ul class="select-opciones-catedral">
                                        <li onclick="seleccionarMetodoPago(this, 'Efectivo', 'edit-metodo-hidden', 'edit')">Efectivo</li>
                                        <li onclick="seleccionarMetodoPago(this, 'Transferencia', 'edit-metodo-hidden', 'edit')">Transferencia</li>
                                        <li onclick="seleccionarMetodoPago(this, 'Pago Móvil', 'edit-metodo-hidden', 'edit')">Pago Móvil</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="grupo-entrada" id="contenedor-referencia-edit">
                            <label>Referencia:</label>
                            <input type="text" name="donacion-referencia" id="edit-referencia" class="input-estilo-catedral">
                        </div>
                    </div>

                    <div id="seccion-bienes-edit" style="display: none;">
                        <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 15px;">
                            <div class="grupo-entrada"><label>Cantidad:</label><input type="text" name="donacion-cantidad" id="edit-cantidad" class="input-estilo-catedral" placeholder="Ej. 50 kg, 3 Cajas"></div>
                            <div class="grupo-entrada"><label>Descripción del Bien:</label><input type="text" name="donacion-descripcion" id="edit-descripcion" class="input-estilo-catedral" placeholder="Ej. Arroz, Cemento, Sillas"></div>
                        </div>
                    </div>

                    <div class="contenedor-botones-modal">
                        <button type="button" class="boton-sagrado-secundario" onclick="cerrarModal('modal-editar-donacion')">Cancelar</button>
                        <button type="submit" class="boton-sagrado-primario"><i class="fas fa-sync-alt"></i> Actualizar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="modal-confirmar-eliminar-donacion" class="modal-catedral">
        <div class="modal-contenido" style="max-width: 400px; text-align: center; border-top: 5px solid var(--acento-rojo);">
            <div class="modal-cuerpo" style="padding: 30px 20px;">
                <i class="fas fa-exclamation-triangle" style="font-size: 3.5rem; color: var(--acento-rojo); margin-bottom: 20px;"></i>
                <h3 class="titulo-advertencia">¿Eliminar Donación?</h3>
                <p id="texto-peligro-donacion" style="margin-bottom: 25px; line-height: 1.5;">
                    ¿Está seguro de que desea eliminar <strong id="nombre-eliminar-donacion-display" style="font-size: 1.1rem; color: var(--acento-rojo);"></strong>? <br>
                    <small style="opacity: 0.8; display: block; margin-top: 10px;">Esta acción es irreversible.</small>
                </p>
                <form method="POST" action="../php/controlador.php" style="margin: 0;">
                    <input type="hidden" name="accion" value="eliminar-donacion">
                    <input type="hidden" name="donacion-id" id="input-eliminar-donacion-id">
                    <div style="display: flex; gap: 10px; justify-content: center;">
                        <button type="button" class="boton-sagrado-secundario" onclick="cerrarModal('modal-confirmar-eliminar-donacion')">Cancelar</button>
                        <button type="submit" class="boton-sagrado-primario" style="background: linear-gradient(135deg, var(--acento-rojo), #521818); box-shadow: 0 4px 10px rgba(122, 40, 40, 0.3); border-color: transparent;"><i class="fas fa-trash-alt"></i> Sí, Eliminar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<div id="modal-editar-usuario" class="modal-catedral">
        <div class="modal-contenido" style="max-width: 400px;">
            <div class="modal-cabecera">
                <h3>Actualizar Datos de Usuario</h3>
                <button class="btn-cerrar-modal" onclick="cerrarModal('modal-editar-usuario')"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-cuerpo">
                
                <div style="background: rgba(198,156,109,0.1); padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center; border: 1px dashed rgba(198,156,109,0.4);">
                    <span class="texto-adaptable-perfil" style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px;">Usuario de Acceso Actual:</span><br>
                    <strong class="texto-fuerte-perfil" style="font-size: 1.3rem; display: block; margin-top: 5px;">
                        <i class="fas fa-user-shield" style="color: var(--acento-dorado);"></i> <?php echo htmlspecialchars($usuarioLoginActual ?? ''); ?>
                    </strong>
                </div>

                <form method="POST" action="../php/controlador.php" autocomplete="off">
                    <input type="hidden" name="accion" value="perfil-cambiar-usuario">
                    
                    <div class="grupo-entrada">
                        <label>Nombre a Mostrar (Barra Lateral):</label>
                        <input type="text" name="nuevo-nombre" class="input-estilo-catedral" value="<?php echo htmlspecialchars($_SESSION['usuario_nombre'] ?? ''); ?>" required>
                    </div>

                    <div class="grupo-entrada">
                        <label>Nuevo Usuario de Acceso (Login):</label>
                        <input type="text" name="nuevo-usuario" class="input-estilo-catedral" value="<?php echo htmlspecialchars($usuarioLoginActual ?? ''); ?>" required>
                        <span class="texto-adaptable-perfil" style="font-size: 0.75rem; display: block; margin-top: 5px; font-style: italic;">Modifique este campo solo si desea cambiar el usuario con el que inicia sesión.</span>
                    </div>
                    
                    <div class="grupo-entrada" style="margin-top: 20px; border-top: 1px dashed rgba(198,156,109,0.3); padding-top: 15px;">
                        <label style="font-weight: bold; margin-bottom: 5px; display: block;">Contraseña actual (Para confirmar):</label>
                        <div class="contenedor-clave">
                            <input type="password" name="clave-actual" id="pass-perfil-datos" class="input-estilo-catedral" style="width: 100%;" required placeholder="Ingrese su contraseña">
                            <i class="fas fa-eye icono-ver-clave" onclick="toggleclave('pass-perfil-datos', this)" title="Mostrar contraseña"></i>
                        </div>
                    </div>
                    
                    <button type="submit" class="boton-sagrado-primario" style="width: 100%;">
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div id="modal-cambiar-clave" class="modal-catedral">
        <div class="modal-contenido" style="max-width: 400px;">
            <div class="modal-cabecera">
                <h3>Cambiar Contraseña</h3>
                <button class="btn-cerrar-modal" onclick="cerrarModal('modal-cambiar-clave')"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-cuerpo">
                <form method="POST" action="../php/controlador.php" autocomplete="off">
                    <input type="hidden" name="accion" value="perfil-cambiar-clave">
                    <label style="font-weight: bold; margin-bottom: 5px; display: block;">Contraseña Actual:</label>
                    <div class="contenedor-clave">
                        <input type="password" name="clave-actual" id="pass-cambio-actual" class="input-estilo-catedral" style="width: 100%;" required>
                        <i class="fas fa-eye icono-ver-clave" onclick="toggleclave('pass-cambio-actual', this)" title="Mostrar contraseña"></i>
                    </div>

                    <label style="font-weight: bold; margin-bottom: 5px; display: block; margin-top: 15px;">Nueva Contraseña:</label>
                    <div class="contenedor-clave">
                        <input type="password" name="nueva-clave" id="pass-cambio-nueva" class="input-estilo-catedral" style="width: 100%;" required>
                        <i class="fas fa-eye icono-ver-clave" onclick="toggleclave('pass-cambio-nueva', this)" title="Mostrar contraseña"></i>
                    </div>

                    <label style="font-weight: bold; margin-bottom: 5px; display: block; margin-top: 15px;">Confirmar Nueva Contraseña:</label>
                    <div class="contenedor-clave">
                        <input type="password" name="confirmar-clave" id="pass-cambio-conf" class="input-estilo-catedral" style="width: 100%;" required>
                        <i class="fas fa-eye icono-ver-clave" onclick="toggleclave('pass-cambio-conf', this)" title="Mostrar contraseña"></i>
                    </div>
                    <button type="submit" class="boton-sagrado-primario" style="width: 100%;"><i class="fas fa-key"></i> Actualizar</button>
                </form>
            </div>
        </div>
    </div>

    <div id="modal-editar-preguntas" class="modal-catedral">
        <div class="modal-contenido" style="max-width: 450px;">
            <div class="modal-cabecera">
                <h3>Preguntas de Seguridad</h3>
                <button class="btn-cerrar-modal" onclick="cerrarModal('modal-editar-preguntas')"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-cuerpo">
                <form id="form-preguntas-seguridad" method="POST" action="../php/controlador.php" autocomplete="off">
                    <input type="hidden" name="accion" value="perfil-cambiar-preguntas">
                    
                    <div class="progreso-preguntas" style="text-align: center; margin-bottom: 20px; font-weight: bold; color: var(--acento-dorado); font-size: 1.1rem;">
                        Paso <span id="paso-actual-num">1</span> de 4
                    </div>

                    <?php 
                    $bancoPreguntas = [
                        "¿Nombre de tu primera mascota?",
                        "¿Ciudad donde naciste?",
                        "¿Nombre de tu escuela primaria?",
                        "¿Cuál es tu color favorito?",
                        "¿Nombre de tu mejor amigo de la infancia?",
                        "¿Año en que te graduaste?",
                        "¿Nombre de soltera de tu madre?"
                    ];

                    for($i=1; $i<=4; $i++): 
                        $preguntaAnterior = $preguntasActuales[$i-1];
                    ?>
                    <div class="paso-pregunta" id="paso-pregunta-<?php echo $i; ?>" style="<?php echo $i == 1 ? 'display: block;' : 'display: none;'; ?>">
                        
                        <div style="background: rgba(198,156,109,0.1); padding: 12px; border-radius: 6px; margin-bottom: 20px; border-left: 3px solid var(--acento-dorado);">
                            <span class="texto-adaptable-perfil" style="font-size: 0.8rem; text-transform: uppercase;">Pregunta actual registrada:</span><br>
                            <strong class="texto-fuerte-perfil"><?php echo $preguntaAnterior ?: 'Ninguna registrada'; ?></strong>
                        </div>

                        <div class="grupo-entrada">
                            <label>Seleccione su nueva Pregunta <?php echo $i; ?>:</label>
                            <div class="select-personalizado-catedral select-preguntas-seguridad">
                                <input type="hidden" name="preg<?php echo $i; ?>" id="edit-preg-<?php echo $i; ?>-hidden" value="<?php echo htmlspecialchars($preguntaAnterior); ?>" required>
                                
                                <div class="select-trigger-catedral gatillo-pregunta" onclick="activarMenuSeguridad(event, this, <?php echo $i; ?>)">
                                    <span class="select-texto" id="texto-preg-<?php echo $i; ?>"><?php echo $preguntaAnterior ?: 'Seleccione una pregunta...'; ?></span>
                                    <i class="fas fa-chevron-down select-icono"></i>
                                </div>
                                
                                <ul class="select-opciones-catedral">
                                    <?php foreach($bancoPreguntas as $opcion): ?>
                                        <li data-valor="<?php echo htmlspecialchars($opcion); ?>" onclick="seleccionarPreguntaSeguridad(event, this, '<?php echo $opcion; ?>', <?php echo $i; ?>)">
                                            <?php echo $opcion; ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>

                        <div class="grupo-entrada">
                            <label>Nueva Respuesta Secreta:</label>
                            <input type="text" name="resp<?php echo $i; ?>" class="input-estilo-catedral" placeholder="Escriba su respuesta..." required>
                        </div>

                        <div style="display: flex; justify-content: space-between; margin-top: 25px; border-top: 1px solid rgba(198,156,109,0.2); padding-top: 15px;">
                            <?php if($i > 1): ?>
                                <button type="button" class="boton-sagrado-secundario" onclick="navegarPregunta(<?php echo $i; ?>, <?php echo $i-1; ?>)"><i class="fas fa-arrow-left"></i> Anterior</button>
                            <?php else: ?>
                                <div></div> <?php endif; ?>

                            <?php if($i < 4): ?>
                                <button type="button" class="boton-sagrado-primario" onclick="navegarPregunta(<?php echo $i; ?>, <?php echo $i+1; ?>)">Siguiente <i class="fas fa-arrow-right"></i></button>
                            <?php else: ?>
                                <button type="button" class="boton-sagrado-primario" onclick="navegarPregunta(4, 5)">Finalizar <i class="fas fa-check"></i></button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endfor; ?>

                    <div class="paso-pregunta" id="paso-pregunta-5" style="display: none; text-align: center;">
                        <i class="fas fa-lock" style="font-size: 3rem; color: var(--acento-dorado); margin-bottom: 15px;"></i>
                        <h4 class="texto-fuerte-perfil" style="margin-bottom: 10px; font-size: 1.2rem;">Autorizar Cambios</h4>
                        <p class="texto-adaptable-perfil" style="font-size: 0.9rem; margin-bottom: 25px;">Por su seguridad, ingrese su contraseña actual para encriptar y guardar las nuevas preguntas.</p>
                        
                        <div class="grupo-entrada" style="text-align: left;">
                            <label style="font-weight: bold; margin-bottom: 5px; display: block;">Contraseña actual (Para confirmar):</label>
                            <div class="contenedor-clave">
                                <input type="password" name="clave-actual" id="pass-perfil-preguntas" class="input-estilo-catedral" style="width: 100%;" required placeholder="Ingrese su contraseña">
                                <i class="fas fa-eye icono-ver-clave" onclick="toggleclave('pass-perfil-preguntas', this)" title="Mostrar contraseña"></i>
                            </div>
                        </div>

                        <div style="display: flex; justify-content: space-between; margin-top: 25px; border-top: 1px solid rgba(198,156,109,0.2); padding-top: 15px;">
                            <button type="button" class="boton-sagrado-secundario" onclick="navegarPregunta(5, 4)"><i class="fas fa-arrow-left"></i> Volver</button>
                            <button type="submit" class="boton-sagrado-primario"><i class="fas fa-shield-alt"></i> Guardar Seguridad</button>
                        </div>
                    </div>

                </form>
            </div>
        </div>
    </div>

    <div id="modal-eliminar-cuenta" class="modal-catedral">
        <div class="modal-contenido" style="max-width: 400px; border-top: 5px solid var(--acento-rojo);">
            <div class="modal-cabecera">
                <h3 style="color: var(--acento-rojo);"><i class="fas fa-exclamation-triangle"></i> Eliminar Cuenta</h3>
                <button class="btn-cerrar-modal" onclick="cerrarModal('modal-eliminar-cuenta')"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-cuerpo" style="text-align: center;">
                <p style="margin-bottom: 20px;">Esta acción borrará su usuario permanentemente. Sus registros de actividad quedarán marcados como "Usuario Eliminado".</p>
                <form method="POST" action="../php/controlador.php" autocomplete="off">
                    <input type="hidden" name="accion" value="perfil-eliminar-cuenta">
                    <div class="grupo-entrada" style="text-align: left;">
                        <label style="font-weight: bold; margin-bottom: 5px; display: block;">Contraseña actual:</label>
                        <div class="contenedor-clave">
                            <input type="password" name="clave-actual" id="pass-perfil-eliminar" class="input-estilo-catedral" style="width: 100%;" required placeholder="Ingrese su contraseña para confirmar">
                            <i class="fas fa-eye icono-ver-clave" onclick="toggleclave('pass-perfil-eliminar', this)" title="Mostrar contraseña"></i>
                        </div>
                    </div>
                    <button type="submit" class="boton-sagrado-primario" style="background: var(--acento-rojo); width: 100%; border-color: transparent;"><i class="fas fa-user-times"></i> Sí, Eliminar mi cuenta</button>
                </form>
            </div>
        </div>
    </div>

<div id="modal-respaldo-db" class="modal-catedral">
        <div class="modal-contenido" style="max-width: 850px;">
            <div class="modal-cabecera">
                <h3><i class="fas fa-database"></i> Gestión de Base de Datos</h3>
                <button class="btn-cerrar-modal" onclick="cerrarModal('modal-respaldo-db')"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-cuerpo">
                
                <?php 
                    $archivosRespaldo = glob('../Respaldos/*.sql');
                    if($archivosRespaldo){ rsort($archivosRespaldo); } 
                ?>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 25px;">
                    
                    <div style="background: rgba(198, 156, 109, 0.15); border: 2px dashed rgba(198, 156, 109, 0.5); border-radius: 12px; padding: 25px; text-align: center; display: flex; flex-direction: column; justify-content: space-between;">
                        <div>
                            <i class="fas fa-save" style="font-size: 2.5rem; color: var(--acento-dorado); margin-bottom: 15px;"></i>
                            <h4 style="margin-bottom: 5px; font-size: 1.2rem;">Guardar Estado Actual</h4>
                            <p style="font-size: 0.9rem; opacity: 0.8; margin-bottom: 20px;">Se creará una copia de seguridad y se guardará automáticamente en la bóveda del sistema.</p>
                        </div>
                        
                        <button type="button" class="boton-sagrado-primario" onclick="abrirModal('modal-confirmar-crear-respaldo')">
    <i class="fas fa-plus"></i> Crear Nuevo Respaldo
</button>
                    </div>

                    <div style="background: rgba(230, 57, 70, 0.12); border: 2px dashed rgba(230, 57, 70, 0.5); padding: 25px; border-radius: 12px; text-align: center; display: flex; flex-direction: column; justify-content: space-between;">
                        <div>
                            <i class="fas fa-history" style="font-size: 2.5rem; color: var(--acento-rojo); margin-bottom: 15px; opacity: 0.8;"></i>
                            <h4 style="margin-bottom: 5px; font-size: 1.2rem;">Restaurar o Limpiar</h4>
                            <p style="font-size: 0.9rem; opacity: 0.9; margin-bottom: 20px; color: var(--acento-rojo); font-weight: bold;">Seleccione un respaldo para restaurarlo o eliminarlo del servidor.</p>
                        </div>
                        
                        <form method="POST" action="../php/controlador.php" style="margin: 0;" id="form-gestionar-respaldo">
                            <input type="hidden" name="accion" id="accion-respaldo" value="importar-db-servidor">
                            
                            <div style="margin-bottom: 20px; text-align: left;">
                                <label style="font-weight: bold; font-size: 0.9rem; margin-bottom: 8px; display: block;">Archivos Disponibles:</label>
                                
                                <div class="select-personalizado-catedral" style="border: 2px solid rgba(230, 57, 70, 0.3);">
                                    <input type="hidden" name="archivo-respaldo" id="input-archivo-respaldo-hidden" required>
                                    
                                    <div class="select-trigger-catedral" onclick="toggleSelectCatedral(this)">
                                        <span class="select-texto" id="texto-respaldo-seleccionado" style="font-weight: bold;">-- Elija un respaldo de la lista --</span>
                                        <i class="fas fa-chevron-down select-icono" style="color: var(--acento-rojo);"></i>
                                    </div>
                                    
                                    <ul class="select-opciones-catedral">
                                        <?php if($archivosRespaldo): ?>
                                            <?php foreach($archivosRespaldo as $ruta): 
                                                $nombreArchivo = basename($ruta);
                                                
                                                $cadenaFecha = str_replace(['catedral_respaldo_', '.sql'], '', $nombreArchivo);
                                                
                                                $fechaObj = DateTime::createFromFormat('Y-m-d_H-i-s', $cadenaFecha);
                                                
                                                $fechaBonita = $fechaObj ? $fechaObj->format('d/m/Y \a \l\a\s h:i A') : $cadenaFecha;
                                            ?>
                                                <li onclick="seleccionarOpcionCatedral(this, '<?php echo $nombreArchivo; ?>', '<?php echo $fechaBonita; ?>', 'input-archivo-respaldo-hidden')">
                                                    <i class="fas fa-file-archive" style="color: var(--acento-dorado); margin-right: 8px;"></i> <?php echo htmlspecialchars($fechaBonita); ?>
                                                </li>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <li style="color: var(--acento-rojo); cursor: not-allowed;"><i class="fas fa-exclamation-circle"></i> No hay respaldos guardados aún</li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                                </div>

                            <div style="display: flex; gap: 10px;">
                                <button type="button" class="boton-sagrado-secundario" style="flex: 1; color: var(--acento-rojo); border-color: var(--acento-rojo); padding: 12px 0;" onclick="eliminarRespaldoSeleccionado()" <?php echo empty($archivosRespaldo) ? 'disabled' : ''; ?> title="Borrar este archivo para liberar espacio">
                                    <i class="fas fa-trash-alt"></i> Eliminar
                                </button>
                                
                                <button type="submit" class="boton-sagrado-primario" style="flex: 2; background: linear-gradient(135deg, var(--acento-rojo), #d32f2f); border: none; padding: 12px 0; box-shadow: 0 4px 15px rgba(230, 57, 70, 0.4);" <?php echo empty($archivosRespaldo) ? 'disabled' : ''; ?> title="Restaurar el sistema a esta fecha">
                                    <i class="fas fa-file-import"></i> Restaurar
                                </button>
                            </div>
                        </form>
                        
                        <script>
                           function eliminarRespaldoSeleccionado() {
    // 1. Obtenemos el archivo que el usuario seleccionó en la lista
    const inputHidden = document.getElementById('input-archivo-respaldo-hidden');
    
    if (!inputHidden.value) {
        alert('Por favor, seleccione un respaldo de la lista primero.');
        return;
    }
    
    // 2. Le pasamos ese archivo exacto a la ventana de la contraseña
    document.getElementById('input-eliminar-respaldo').value = inputHidden.value;
    
    // 3. Limpiamos el campo de la contraseña por seguridad
    let inputClave = document.getElementById('clave-eliminar-respaldo');
    if (inputClave) {
        inputClave.value = '';
    }
    
    // 4. Cerramos el panel y abrimos tu advertencia roja
    cerrarModal('modal-respaldo-db');
    abrirModal('modal-confirmar-eliminar-respaldo');
}
                        </script>
                    </div>

                </div> </div>
        </div>
    </div>

    <div id="modal-confirmar-eliminar-respaldo" class="modal-catedral" style="z-index: 9999;">
    <div class="modal-contenido" style="max-width: 400px; text-align: center; border-top: 5px solid var(--acento-rojo);">
        <div class="modal-cuerpo" style="padding: 30px 20px;">
            <i class="fas fa-exclamation-triangle" style="font-size: 3.5rem; color: var(--acento-rojo); margin-bottom: 20px;"></i>
            <h3 class="titulo-advertencia" style="color: var(--texto-principal);">¿Eliminar Respaldo?</h3>
            <p style="margin-bottom: 15px; font-size: 0.95rem; opacity: 0.8;">
                Esta acción es irreversible. El archivo será borrado permanentemente del servidor.
            </p>
            
            <form method="POST" action="../php/controlador.php" style="margin: 0; text-align: left;">
                <input type="hidden" name="accion" value="eliminar-db-servidor">
                <input type="hidden" name="archivo-respaldo" id="input-eliminar-respaldo">
                
                <div class="grupo-entrada" style="margin-bottom: 20px;">
                    <label for="clave-eliminar-respaldo" style="font-size: 0.9rem; font-weight: bold; color: var(--texto-principal);">
                        <i class="fas fa-lock"></i> Contraseña de Administrador:
                    </label>
                    <div class="contenedor-clave">
                        <input type="password" name="clave-admin" id="clave-eliminar-respaldo" class="input-estilo-catedral" style="width: 100%;" required placeholder="Escriba su contraseña para confirmar...">
                        <i class="fas fa-eye icono-ver-clave" onclick="toggleclave('clave-eliminar-respaldo', this)" title="Mostrar contraseña"></i>
                    </div>
                </div>
                
                <div style="display: flex; gap: 10px; justify-content: center;">
                    <button type="button" class="boton-sagrado-secundario" onclick="cerrarModal('modal-confirmar-eliminar-respaldo')">Cancelar</button>
                    <button type="submit" class="boton-sagrado-primario" style="background: var(--acento-rojo); border-color: var(--acento-rojo);">
                        <i class="fas fa-trash"></i> Sí, Eliminar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="modal-confirmar-crear-respaldo" class="modal-catedral" style="z-index: 9999;">
    <div class="modal-contenido" style="max-width: 400px; text-align: center; border-top: 5px solid var(--acento-dorado);">
        <div class="modal-cuerpo" style="padding: 30px 20px;">
            <i class="fas fa-database" style="font-size: 3.5rem; color: var(--acento-dorado); margin-bottom: 20px;"></i>
            <h3>Crear nuevo respaldo</h3>
            <p style="margin-bottom: 15px; font-size: 0.95rem; opacity: 0.8;">
                Se creará un nuevo archivo de respaldo. Ingrese su clave para confirmar:
            </p>
            
            <form method="POST" action="../php/controlador.php" style="margin: 0;">
                <input type="hidden" name="accion" value="exportar-db-servidor">
                
                <div class="grupo-entrada" style="margin-bottom: 20px; text-align: left;">
                    <div class="contenedor-clave">
                        <input type="password" name="clave-admin" id="clave-crear-nuevo-respaldo" class="input-estilo-catedral" style="width: 100%;" required placeholder="Contraseña de administrador...">
                        <i class="fas fa-eye icono-ver-clave" onclick="toggleclave('clave-crear-nuevo-respaldo', this)" title="Mostrar contraseña"></i>
                    </div>
                </div>

                <div style="display: flex; gap: 10px; justify-content: center;">
                    <button type="button" class="boton-sagrado-secundario" onclick="cerrarModal('modal-confirmar-crear-respaldo')">Cancelar</button>
                    <button type="submit" class="boton-sagrado-primario">
                        <i class="fas fa-plus"></i> Confirmar y Crear
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

    <div id="modal-admin-editar-usuario" class="modal-catedral">
        <div class="modal-contenido">
            <div class="modal-cabecera">
                <h3><i class="fas fa-user-edit"></i> Modificar Acceso y Rol</h3>
                <button class="btn-cerrar-modal" onclick="cerrarModal('modal-admin-editar-usuario')"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-cuerpo">
                <form method="POST" action="../php/controlador.php" autocomplete="off">
                    <input type="hidden" name="accion" value="admin-editar-usuario">
                    <input type="hidden" name="usuario-id" id="admin-edit-user-id">

                    <div class="grupo-entrada">
                        <label>Nombre Completo:</label>
                        <input type="text" name="usuario-nombre" id="admin-edit-user-nombre" class="input-estilo-catedral" required>
                    </div>

                    <div class="grupo-entrada">
                        <label>Usuario de Acceso (Login):</label>
                        <input type="text" name="usuario-login" id="admin-edit-user-login" class="input-estilo-catedral" required>
                    </div>

                    <div class="grupo-entrada">
                        <label>Nivel de Permisos (Rol):</label>
                        <div class="select-personalizado-catedral">
                            <input type="hidden" name="usuario-rol" id="admin-edit-user-rol-hidden" required>
                            <div class="select-trigger-catedral" id="admin-edit-user-rol-trigger" onclick="toggleSelectCatedral(this)">
                                <span class="select-texto">Seleccione un rol...</span>
                                <i class="fas fa-chevron-down select-icono"></i>
                            </div>
                            <ul class="select-opciones-catedral">
                                <li onclick="seleccionarOpcionCatedral(this, '3', 'Administrador', 'admin-edit-user-rol-hidden')">Administrador</li>
                                <li onclick="seleccionarOpcionCatedral(this, '2', 'Secretario', 'admin-edit-user-rol-hidden')">Secretario</li>
                                <li onclick="seleccionarOpcionCatedral(this, '1', 'Ciudadano', 'admin-edit-user-rol-hidden')">Ciudadano</li>
                            </ul>
                        </div>
                    </div>

                    <div class="contenedor-botones-modal">
                        <button type="button" class="boton-sagrado-secundario" onclick="cerrarModal('modal-admin-editar-usuario')">Cancelar</button>
                        <button type="submit" class="boton-sagrado-primario"><i class="fas fa-save"></i> Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="modal-admin-eliminar-usuario" class="modal-catedral">
        <div class="modal-contenido" style="max-width: 400px; text-align: center; border-top: 5px solid var(--acento-rojo);">
            <div class="modal-cuerpo" style="padding: 30px 20px;">
                <i class="fas fa-user-slash" style="font-size: 3.5rem; color: var(--acento-rojo); margin-bottom: 20px;"></i>
                <h3 class="titulo-advertencia">¿Revocar Acceso?</h3>
                <p style="margin-bottom: 25px; line-height: 1.5;">
                    ¿Está seguro de eliminar al usuario <strong id="admin-delete-user-name" style="color: var(--acento-rojo);"></strong>? <br>
                    <small style="opacity: 0.8;">Ya no podrá iniciar sesión en el sistema.</small>
                </p>
                <form method="POST" action="../php/controlador.php" style="margin: 0;">
                    <input type="hidden" name="accion" value="admin-eliminar-usuario">
                    <input type="hidden" name="usuario-id" id="admin-delete-user-id">
                    <div style="display: flex; gap: 10px; justify-content: center;">
                        <button type="button" class="boton-sagrado-secundario" onclick="cerrarModal('modal-admin-eliminar-usuario')">Cancelar</button>
                        <button type="submit" class="boton-sagrado-primario" style="background: linear-gradient(135deg, var(--acento-rojo), #521818); border-color: transparent;"><i class="fas fa-trash-alt"></i> Sí, Eliminar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="modal-confirmar-vaciar-historial" class="modal-catedral">
        <div class="modal-contenido" style="max-width: 400px; text-align: center; border-top: 5px solid var(--acento-rojo);">
            <div class="modal-cuerpo" style="padding: 30px 20px;">
                <i class="fas fa-dumpster-fire" style="font-size: 3.5rem; color: var(--acento-rojo); margin-bottom: 20px;"></i>
                <h3 class="titulo-advertencia">¿Vaciar Historial?</h3>
                
                <style>
                    body.oscuro #texto-peligro-historial { color: #ffffff !important; }
                </style>
                
                <p id="texto-peligro-historial" style="margin-bottom: 25px; line-height: 1.5;">
                    ¿Está seguro de que desea eliminar <strong>todas las notificaciones</strong> del sistema? <br>
                    <small style="opacity: 0.8; display: block; margin-top: 10px;">Esta acción vaciará el panel y es irreversible.</small>
                </p>
                
                <div style="display: flex; gap: 10px; justify-content: center;">
                    <button type="button" class="boton-sagrado-secundario" onclick="cerrarModal('modal-confirmar-vaciar-historial')">Cancelar</button>
                    <button type="button" class="boton-sagrado-primario" onclick="eliminarTodasNotificaciones()" style="background: linear-gradient(135deg, var(--acento-rojo), #521818); box-shadow: 0 4px 10px rgba(122, 40, 40, 0.3); border-color: transparent;"><i class="fas fa-trash-alt"></i> Sí, Vaciar Panel</button>
                </div>
            </div>
        </div>
    </div>

<div id="modal-form-bautismo" class="modal-catedral">
        <div class="modal-contenido" style="max-width: 700px;">
            <div class="modal-cabecera">
                <h3><i class="fas fa-water"></i> Emitir Partida de Bautismo</h3>
                <button class="btn-cerrar-modal" onclick="cerrarModal('modal-form-bautismo')"><i class="fas fa-times"></i></button>
            </div>
            
            <div class="modal-cuerpo" style="max-height: 70vh; overflow-y: auto; padding-right: 15px;">
                <form action="../php/guardar_bautismo.php" method="POST" autocomplete="off">
                    
                    <h4 style="color: var(--acento-dorado); margin-bottom: 15px; border-bottom: 1px solid rgba(198,156,109,0.3); padding-bottom: 5px;">1. Datos del Bautizado</h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                        <div>
                            <label style="font-weight: bold; font-size: 0.9rem; margin-bottom: 5px; display: block;">Nombres y Apellidos:</label>
                            <input type="text" name="nombre_bautizado" class="input-estilo-catedral" style="width: 100%;" required placeholder="Ej: Juan Pablo Pérez">
                        </div>
                        <div>
                            <label style="font-weight: bold; font-size: 0.9rem; margin-bottom: 5px; display: block;">Fecha de Nacimiento:</label>
                            <input type="date" name="fecha_nacimiento" class="input-estilo-catedral" style="width: 100%;" required>
                        </div>
                        <div>
                            <label style="font-weight: bold; font-size: 0.9rem; margin-bottom: 5px; display: block;">Ciudad de Nacimiento:</label>
                            <input type="text" name="ciudad_nacimiento" class="input-estilo-catedral" style="width: 100%;" required placeholder="Ej: Ciudad Bolívar">
                        </div>
                        <div>
                            <label style="font-weight: bold; font-size: 0.9rem; margin-bottom: 5px; display: block;">Estado de Nacimiento:</label>
                            <input type="text" name="estado_nacimiento" class="input-estilo-catedral" style="width: 100%;" required placeholder="Ej: Bolívar">
                        </div>
                    </div>

                    <h4 style="color: var(--acento-dorado); margin-bottom: 15px; border-bottom: 1px solid rgba(198,156,109,0.3); padding-bottom: 5px;">2. Padres y Padrinos</h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                        <div>
                            <label style="font-weight: bold; font-size: 0.9rem; margin-bottom: 5px; display: block;">Nombre del Padre:</label>
                            <input type="text" name="nombre_padre" class="input-estilo-catedral" style="width: 100%;">
                        </div>
                        <div>
                            <label style="font-weight: bold; font-size: 0.9rem; margin-bottom: 5px; display: block;">Nombre de la Madre:</label>
                            <input type="text" name="nombre_madre" class="input-estilo-catedral" style="width: 100%;" required>
                        </div>
                        <div>
                            <label style="font-weight: bold; font-size: 0.9rem; margin-bottom: 5px; display: block;">Padrino:</label>
                            <input type="text" name="nombre_padrino" class="input-estilo-catedral" style="width: 100%;">
                        </div>
                        <div>
                            <label style="font-weight: bold; font-size: 0.9rem; margin-bottom: 5px; display: block;">Madrina:</label>
                            <input type="text" name="nombre_madrina" class="input-estilo-catedral" style="width: 100%;">
                        </div>
                    </div>

                    <h4 style="color: var(--acento-dorado); margin-bottom: 15px; border-bottom: 1px solid rgba(198,156,109,0.3); padding-bottom: 5px;">3. Datos del Sacramento</h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 25px;">
                        <div>
                            <label style="font-weight: bold; font-size: 0.9rem; margin-bottom: 5px; display: block;">Fecha del Bautismo:</label>
                            <input type="date" name="fecha_bautismo" class="input-estilo-catedral" style="width: 100%;" required>
                        </div>
                        <div>
                            <label style="font-weight: bold; font-size: 0.9rem; margin-bottom: 5px; display: block;">Ministro (Sacerdote):</label>
                            <input type="text" name="ministro" class="input-estilo-catedral" style="width: 100%;" required placeholder="Ej: Pbro. Carlos Gómez">
                        </div>
                        <div style="display: flex; gap: 10px;">
                            <div style="flex: 1;"><label style="font-weight: bold; font-size: 0.9rem; margin-bottom: 5px; display: block;">Libro N°:</label><input type="text" inputmode="numeric" autocomplete="off" name="libro_num" class="input-estilo-catedral" style="width: 100%;" required></div>
                            <div style="flex: 1;"><label style="font-weight: bold; font-size: 0.9rem; margin-bottom: 5px; display: block;">Folio:</label><input type="text" inputmode="numeric" autocomplete="off" name="folio_num" class="input-estilo-catedral" style="width: 100%;" required></div>
                            <div style="flex: 1;"><label style="font-weight: bold; font-size: 0.9rem; margin-bottom: 5px; display: block;">Acta N°:</label><input type="text" inputmode="numeric" autocomplete="off" name="acta_num" class="input-estilo-catedral" style="width: 100%;" required></div>
                        </div>
                    </div>

                    <div style="text-align: right; margin-top: 10px;">
                        <button type="button" class="boton-sagrado-secundario" onclick="cerrarModal('modal-form-bautismo')">Cancelar</button>
                        <button type="submit" class="boton-sagrado-primario"><i class="fas fa-file-pdf"></i> Generar Certificado</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="modal-form-confirmacion" class="modal-catedral">
        <div class="modal-contenido" style="max-width: 700px;">
            <div class="modal-cabecera">
                <h3><i class="fas fa-dove"></i> Nueva Partida de Confirmación</h3>
                <button class="btn-cerrar-modal" onclick="cerrarModal('modal-form-confirmacion')"><i class="fas fa-times"></i></button>
            </div>
            
            <div class="modal-cuerpo" style="max-height: 70vh; overflow-y: auto; padding-right: 15px;">
                <form action="../php/guardar_confirmacion.php" method="POST" autocomplete="off">
                    
                    <h4 style="color: var(--acento-dorado); margin-bottom: 15px; border-bottom: 1px solid rgba(198,156,109,0.3); padding-bottom: 5px;">1. Datos del Confirmado</h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                        <div>
                            <label style="font-weight: bold; font-size: 0.9rem; margin-bottom: 5px; display: block;">Nombres y Apellidos:</label>
                            <input type="text" name="nombre_confirmado" class="input-estilo-catedral" style="width: 100%;" required>
                        </div>
                        <div>
                            <label style="font-weight: bold; font-size: 0.9rem; margin-bottom: 5px; display: block;">Fecha de Nacimiento:</label>
                            <input type="date" name="fecha_nacimiento" class="input-estilo-catedral" style="width: 100%;" required>
                        </div>
                        <div>
                            <label style="font-weight: bold; font-size: 0.9rem; margin-bottom: 5px; display: block;">Ciudad de Nacimiento:</label>
                            <input type="text" name="ciudad_nacimiento" class="input-estilo-catedral" style="width: 100%;" required>
                        </div>
                        <div>
                            <label style="font-weight: bold; font-size: 0.9rem; margin-bottom: 5px; display: block;">Estado de Nacimiento:</label>
                            <input type="text" name="estado_nacimiento" class="input-estilo-catedral" style="width: 100%;" required>
                        </div>
                    </div>

                    <h4 style="color: var(--acento-dorado); margin-bottom: 15px; border-bottom: 1px solid rgba(198,156,109,0.3); padding-bottom: 5px;">2. Padres y Padrinos</h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                        <div>
                            <label style="font-weight: bold; font-size: 0.9rem; margin-bottom: 5px; display: block;">Nombre del Padre:</label>
                            <input type="text" name="nombre_padre" class="input-estilo-catedral" style="width: 100%;">
                        </div>
                        <div>
                            <label style="font-weight: bold; font-size: 0.9rem; margin-bottom: 5px; display: block;">Nombre de la Madre:</label>
                            <input type="text" name="nombre_madre" class="input-estilo-catedral" style="width: 100%;" required>
                        </div>
                        <div>
                            <label style="font-weight: bold; font-size: 0.9rem; margin-bottom: 5px; display: block;">Padrino:</label>
                            <input type="text" name="nombre_padrino" class="input-estilo-catedral" style="width: 100%;">
                        </div>
                        <div>
                            <label style="font-weight: bold; font-size: 0.9rem; margin-bottom: 5px; display: block;">Madrina:</label>
                            <input type="text" name="nombre_madrina" class="input-estilo-catedral" style="width: 100%;">
                        </div>
                    </div>

                    <h4 style="color: var(--acento-dorado); margin-bottom: 15px; border-bottom: 1px solid rgba(198,156,109,0.3); padding-bottom: 5px;">3. Datos del Sacramento</h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                        <div>
                            <label style="font-weight: bold; font-size: 0.9rem; margin-bottom: 5px; display: block;">Fecha de Confirmación:</label>
                            <input type="date" name="fecha_confirmacion" class="input-estilo-catedral" style="width: 100%;" required>
                        </div>
                        <div>
                            <label style="font-weight: bold; font-size: 0.9rem; margin-bottom: 5px; display: block;">Ministro (Obispo/Sacerdote):</label>
                            <input type="text" name="ministro" class="input-estilo-catedral" style="width: 100%;" required>
                        </div>
                        <div style="display: flex; gap: 10px; grid-column: span 2;">
                            <div style="flex: 1;"><label style="font-weight: bold; font-size: 0.9rem; margin-bottom: 5px; display: block;">Libro N°:</label><input type="text" inputmode="numeric" autocomplete="off" name="libro_num" class="input-estilo-catedral" style="width: 100%;" required></div>
                            <div style="flex: 1;"><label style="font-weight: bold; font-size: 0.9rem; margin-bottom: 5px; display: block;">Folio:</label><input type="text" inputmode="numeric" autocomplete="off" name="folio_num" class="input-estilo-catedral" style="width: 100%;" required></div>
                            <div style="flex: 1;"><label style="font-weight: bold; font-size: 0.9rem; margin-bottom: 5px; display: block;">Acta N°:</label><input type="text" inputmode="numeric" autocomplete="off" name="acta_num" class="input-estilo-catedral" style="width: 100%;" required></div>
                        </div>
                    </div>

                    <div style="text-align: right;">
                        <button type="button" class="boton-sagrado-secundario" onclick="cerrarModal('modal-form-confirmacion')">Cancelar</button>
                        <button type="submit" class="boton-sagrado-primario"><i class="fas fa-save"></i> Guardar Acta</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="modal-editar-confirmacion" class="modal-catedral">
        <div class="modal-contenido" style="max-width: 700px;">
            <div class="modal-cabecera">
                <h3><i class="fas fa-edit"></i> Editar Partida de Confirmación</h3>
                <button class="btn-cerrar-modal" onclick="cerrarModal('modal-editar-confirmacion')"><i class="fas fa-times"></i></button>
            </div>
            
            <div class="modal-cuerpo" style="max-height: 70vh; overflow-y: auto; padding-right: 15px;">
                <form action="../php/editar_confirmacion.php" method="POST" autocomplete="off">
                    
                    <input type="hidden" name="id_documento" id="edit-conf-id">
                    
                    <h4 style="color: var(--acento-dorado); margin-bottom: 15px; border-bottom: 1px solid rgba(198,156,109,0.3); padding-bottom: 5px;">1. Datos del Confirmado</h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                        <div>
                            <label style="font-weight: bold; font-size: 0.9rem; margin-bottom: 5px; display: block;">Nombres y Apellidos:</label>
                            <input type="text" name="nombre_confirmado" id="edit-conf-nombre" class="input-estilo-catedral" style="width: 100%;" required>
                        </div>
                        <div>
                            <label style="font-weight: bold; font-size: 0.9rem; margin-bottom: 5px; display: block;">Fecha de Nacimiento:</label>
                            <input type="date" name="fecha_nacimiento" id="edit-conf-fnac" class="input-estilo-catedral" style="width: 100%;" required>
                        </div>
                        <div>
                            <label style="font-weight: bold; font-size: 0.9rem; margin-bottom: 5px; display: block;">Ciudad de Nacimiento:</label>
                            <input type="text" name="ciudad_nacimiento" id="edit-conf-ciudad" class="input-estilo-catedral" style="width: 100%;" required>
                        </div>
                        <div>
                            <label style="font-weight: bold; font-size: 0.9rem; margin-bottom: 5px; display: block;">Estado de Nacimiento:</label>
                            <input type="text" name="estado_nacimiento" id="edit-conf-estado" class="input-estilo-catedral" style="width: 100%;" required>
                        </div>
                    </div>

                    <h4 style="color: var(--acento-dorado); margin-bottom: 15px; border-bottom: 1px solid rgba(198,156,109,0.3); padding-bottom: 5px;">2. Padres y Padrinos</h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                        <div>
                            <label style="font-weight: bold; font-size: 0.9rem; margin-bottom: 5px; display: block;">Nombre del Padre:</label>
                            <input type="text" name="nombre_padre" id="edit-conf-padre" class="input-estilo-catedral" style="width: 100%;">
                        </div>
                        <div>
                            <label style="font-weight: bold; font-size: 0.9rem; margin-bottom: 5px; display: block;">Nombre de la Madre:</label>
                            <input type="text" name="nombre_madre" id="edit-conf-madre" class="input-estilo-catedral" style="width: 100%;" required>
                        </div>
                        <div>
                            <label style="font-weight: bold; font-size: 0.9rem; margin-bottom: 5px; display: block;">Padrino:</label>
                            <input type="text" name="nombre_padrino" id="edit-conf-padrino" class="input-estilo-catedral" style="width: 100%;">
                        </div>
                        <div>
                            <label style="font-weight: bold; font-size: 0.9rem; margin-bottom: 5px; display: block;">Madrina:</label>
                            <input type="text" name="nombre_madrina" id="edit-conf-madrina" class="input-estilo-catedral" style="width: 100%;">
                        </div>
                    </div>

                    <h4 style="color: var(--acento-dorado); margin-bottom: 15px; border-bottom: 1px solid rgba(198,156,109,0.3); padding-bottom: 5px;">3. Datos del Sacramento</h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 25px;">
                        <div>
                            <label style="font-weight: bold; font-size: 0.9rem; margin-bottom: 5px; display: block;">Fecha de Confirmación:</label>
                            <input type="date" name="fecha_confirmacion" id="edit-conf-fsac" class="input-estilo-catedral" style="width: 100%;" required>
                        </div>
                        <div>
                            <label style="font-weight: bold; font-size: 0.9rem; margin-bottom: 5px; display: block;">Ministro (Obispo/Sacerdote):</label>
                            <input type="text" name="ministro" id="edit-conf-ministro" class="input-estilo-catedral" style="width: 100%;" required>
                        </div>
                        <div style="display: flex; gap: 10px; grid-column: span 2;">
                            <div style="flex: 1;"><label style="font-weight: bold; font-size: 0.9rem; margin-bottom: 5px; display: block;">Libro N°:</label><input type="text" inputmode="numeric" autocomplete="off" name="libro_num" id="edit-conf-libro" class="input-estilo-catedral" style="width: 100%;" required></div>
                            <div style="flex: 1;"><label style="font-weight: bold; font-size: 0.9rem; margin-bottom: 5px; display: block;">Folio:</label><input type="text" inputmode="numeric" autocomplete="off" name="folio_num" id="edit-conf-folio" class="input-estilo-catedral" style="width: 100%;" required></div>
                            <div style="flex: 1;"><label style="font-weight: bold; font-size: 0.9rem; margin-bottom: 5px; display: block;">Acta N°:</label><input type="text" inputmode="numeric" autocomplete="off" name="acta_num" id="edit-conf-num" class="input-estilo-catedral" style="width: 100%;" required></div>
                        </div>
                    </div>

                    <div style="text-align: right; margin-top: 10px;">
                        <button type="button" class="boton-sagrado-secundario" onclick="cerrarModal('modal-editar-confirmacion')">Cancelar</button>
                        <button type="submit" class="boton-sagrado-primario" style="background-color: #3498db; border-color: #3498db; color: #ffffff;"><i class="fas fa-save"></i> Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="modal-form-matrimonio" class="modal-catedral">
        <div class="modal-contenido" style="max-width: 500px;">
            <div class="modal-cabecera">
                <h3><i class="fas fa-ring"></i> Emitir Acta de Matrimonio</h3>
                <button class="btn-cerrar-modal" onclick="cerrarModal('modal-form-matrimonio')"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-cuerpo" style="text-align: center; padding: 40px 20px;">
                <i class="fas fa-tools" style="font-size: 3rem; color: var(--acento-dorado); margin-bottom: 15px;"></i>
                <h4>Formulario en Construcción</h4>
                <p style="opacity: 0.8; margin-bottom: 20px;">Próximamente agregaremos los campos para este documento.</p>
                <button class="boton-sagrado-secundario" onclick="cerrarModal('modal-form-matrimonio')">Cerrar</button>
            </div>
        </div>
    </div>

    <div id="modal-editar-bautismo" class="modal-catedral">
        <div class="modal-contenido" style="max-width: 700px;">
            <div class="modal-cabecera">
                <h3><i class="fas fa-edit"></i> Editar Partida de Bautismo</h3>
                <button class="btn-cerrar-modal" onclick="cerrarModal('modal-editar-bautismo')"><i class="fas fa-times"></i></button>
            </div>
            
            <div class="modal-cuerpo" style="max-height: 70vh; overflow-y: auto; padding-right: 15px;">
                <form action="../php/editar_bautismo.php" method="POST" autocomplete="off">
                    
                    <input type="hidden" name="id_documento" id="edit-bautismo-id">
                    
                    <h4 style="color: var(--acento-dorado); margin-bottom: 15px; border-bottom: 1px solid rgba(198,156,109,0.3); padding-bottom: 5px;">1. Datos del Bautizado</h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                        <div>
                            <label style="font-weight: bold; font-size: 0.9rem; margin-bottom: 5px; display: block;">Nombres y Apellidos:</label>
                            <input type="text" name="nombre_bautizado" id="edit-bautismo-nombre" class="input-estilo-catedral" style="width: 100%;" required>
                        </div>
                        <div>
                            <label style="font-weight: bold; font-size: 0.9rem; margin-bottom: 5px; display: block;">Fecha de Nacimiento:</label>
                            <input type="date" name="fecha_nacimiento" id="edit-bautismo-fnac" class="input-estilo-catedral" style="width: 100%;" required>
                        </div>
                        <div>
                            <label style="font-weight: bold; font-size: 0.9rem; margin-bottom: 5px; display: block;">Ciudad de Nacimiento:</label>
                            <input type="text" name="ciudad_nacimiento" id="edit-bautismo-ciudad" class="input-estilo-catedral" style="width: 100%;" required>
                        </div>
                        <div>
                            <label style="font-weight: bold; font-size: 0.9rem; margin-bottom: 5px; display: block;">Estado de Nacimiento:</label>
                            <input type="text" name="estado_nacimiento" id="edit-bautismo-estado" class="input-estilo-catedral" style="width: 100%;" required>
                        </div>
                    </div>

                    <h4 style="color: var(--acento-dorado); margin-bottom: 15px; border-bottom: 1px solid rgba(198,156,109,0.3); padding-bottom: 5px;">2. Padres y Padrinos</h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                        <div>
                            <label style="font-weight: bold; font-size: 0.9rem; margin-bottom: 5px; display: block;">Nombre del Padre:</label>
                            <input type="text" name="nombre_padre" id="edit-bautismo-padre" class="input-estilo-catedral" style="width: 100%;">
                        </div>
                        <div>
                            <label style="font-weight: bold; font-size: 0.9rem; margin-bottom: 5px; display: block;">Nombre de la Madre:</label>
                            <input type="text" name="nombre_madre" id="edit-bautismo-madre" class="input-estilo-catedral" style="width: 100%;" required>
                        </div>
                        <div>
                            <label style="font-weight: bold; font-size: 0.9rem; margin-bottom: 5px; display: block;">Padrino:</label>
                            <input type="text" name="nombre_padrino" id="edit-bautismo-padrino" class="input-estilo-catedral" style="width: 100%;">
                        </div>
                        <div>
                            <label style="font-weight: bold; font-size: 0.9rem; margin-bottom: 5px; display: block;">Madrina:</label>
                            <input type="text" name="nombre_madrina" id="edit-bautismo-madrina" class="input-estilo-catedral" style="width: 100%;">
                        </div>
                    </div>

                    <h4 style="color: var(--acento-dorado); margin-bottom: 15px; border-bottom: 1px solid rgba(198,156,109,0.3); padding-bottom: 5px;">3. Datos del Sacramento</h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 25px;">
                        <div>
                            <label style="font-weight: bold; font-size: 0.9rem; margin-bottom: 5px; display: block;">Fecha del Bautismo:</label>
                            <input type="date" name="fecha_bautismo" id="edit-bautismo-fsac" class="input-estilo-catedral" style="width: 100%;" required>
                        </div>
                        <div>
                            <label style="font-weight: bold; font-size: 0.9rem; margin-bottom: 5px; display: block;">Ministro (Sacerdote):</label>
                            <input type="text" name="ministro" id="edit-bautismo-ministro" class="input-estilo-catedral" style="width: 100%;" required>
                        </div>
                        <div style="display: flex; gap: 10px;">
                            <div style="flex: 1;"><label style="font-weight: bold; font-size: 0.9rem; margin-bottom: 5px; display: block;">Libro N°:</label><input type="text" inputmode="numeric" autocomplete="off" name="libro_num" id="edit-bautismo-libro" class="input-estilo-catedral" style="width: 100%;" required></div>
                            <div style="flex: 1;"><label style="font-weight: bold; font-size: 0.9rem; margin-bottom: 5px; display: block;">Folio:</label><input type="text" inputmode="numeric" autocomplete="off" name="folio_num" id="edit-bautismo-folio" class="input-estilo-catedral" style="width: 100%;" required></div>
                            <div style="flex: 1;"><label style="font-weight: bold; font-size: 0.9rem; margin-bottom: 5px; display: block;">Acta N°:</label><input type="text" inputmode="numeric" autocomplete="off" name="acta_num" id="edit-bautismo-num" class="input-estilo-catedral" style="width: 100%;" required></div>
                        </div>
                    </div>

                    <div style="text-align: right; margin-top: 10px;">
                        <button type="button" class="boton-sagrado-secundario" onclick="cerrarModal('modal-editar-bautismo')">Cancelar</button>
                        <button type="submit" class="boton-sagrado-primario" style="background: var(--acento-dorado); border-color: var(--acento-dorado);"><i class="fas fa-save"></i> Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="modal-crear-matrimonio" class="modal-catedral">
        <div class="modal-contenido" style="max-width: 850px;">
            <div class="modal-cabecera">
                <h3><i class="fas fa-ring"></i> Nueva Partida de Matrimonio</h3>
                <button class="btn-cerrar-modal" onclick="cerrarModal('modal-crear-matrimonio')"><i class="fas fa-times"></i></button>
            </div>
            
            <div class="modal-cuerpo" style="max-height: 75vh; overflow-y: auto; padding-right: 15px;">
                <form action="../php/guardar_matrimonio.php" method="POST" autocomplete="off">
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                        
                        <div>
                            <h4 style="color: var(--acento-dorado); margin-bottom: 15px; border-bottom: 1px solid rgba(198,156,109,0.3); padding-bottom: 5px;">1. Datos del Esposo</h4>
                            <div style="display: flex; flex-direction: column; gap: 12px;">
                                <div>
                                    <label style="font-weight: bold; font-size: 0.85rem; display: block; margin-bottom: 5px;">Nombres y Apellidos:</label>
                                    <input type="text" name="nombre_esposo" class="input-estilo-catedral" style="width: 100%;" required>
                                </div>
                                <div style="display: flex; gap: 10px;">
                                    <div style="flex: 2;">
                                        <label style="font-weight: bold; font-size: 0.85rem; display: block; margin-bottom: 5px;">Estado Civil:</label>
                                        
                                        <div class="select-personalizado-catedral">
                                            <input type="hidden" name="estado_civil_esposo" id="estado-esposo-hidden" value="Soltero" required>
                                            <div class="select-trigger-catedral" onclick="toggleSelectCatedral(this)">
                                                <span class="select-texto">Soltero</span>
                                                <i class="fas fa-chevron-down select-icono"></i>
                                            </div>
                                            <ul class="select-opciones-catedral">
                                                <li onclick="seleccionarOpcionCatedral(this, 'Soltero', 'Soltero', 'estado-esposo-hidden')">Soltero</li>
                                                <li onclick="seleccionarOpcionCatedral(this, 'Viudo', 'Viudo', 'estado-esposo-hidden')">Viudo</li>
                                            </ul>
                                        </div>

                                    </div>
                                    <div style="flex: 1;">
                                        <label style="font-weight: bold; font-size: 0.85rem; display: block; margin-bottom: 5px;">Edad:</label>
                                        <input type="number" name="edad_esposo" class="input-estilo-catedral" style="width: 100%;">
                                    </div>
                                </div>
                                <div>
                                    <label style="font-weight: bold; font-size: 0.85rem; display: block; margin-bottom: 5px;">Si es viudo, de quién:</label>
                                    <input type="text" name="viudo_de_esposo" class="input-estilo-catedral" style="width: 100%;" placeholder="N/A">
                                </div>
                                <div>
                                    <label style="font-weight: bold; font-size: 0.85rem; display: block; margin-bottom: 5px;">Natural de (Ciudad/Estado):</label>
                                    <input type="text" name="natural_esposo" class="input-estilo-catedral" style="width: 100%;">
                                </div>
                                <div>
                                    <label style="font-weight: bold; font-size: 0.85rem; display: block; margin-bottom: 5px;">Nombre del Padre:</label>
                                    <input type="text" name="padre_esposo" class="input-estilo-catedral" style="width: 100%;" placeholder="Padre del esposo">
                                </div>
                                <div>
                                    <label style="font-weight: bold; font-size: 0.85rem; display: block; margin-bottom: 5px;">Nombre de la Madre:</label>
                                    <input type="text" name="madre_esposo" class="input-estilo-catedral" style="width: 100%;" placeholder="Madre del esposo">
                                </div>
                            </div>
                        </div>

                        <div>
                            <h4 style="color: var(--acento-dorado); margin-bottom: 15px; border-bottom: 1px solid rgba(198,156,109,0.3); padding-bottom: 5px;">2. Datos de la Esposa</h4>
                            <div style="display: flex; flex-direction: column; gap: 12px;">
                                <div>
                                    <label style="font-weight: bold; font-size: 0.85rem; display: block; margin-bottom: 5px;">Nombres y Apellidos:</label>
                                    <input type="text" name="nombre_esposa" class="input-estilo-catedral" style="width: 100%;" required>
                                </div>
                                <div style="display: flex; gap: 10px;">
                                    <div style="flex: 2;">
                                        <label style="font-weight: bold; font-size: 0.85rem; display: block; margin-bottom: 5px;">Estado Civil:</label>
                                        
                                        <div class="select-personalizado-catedral">
                                            <input type="hidden" name="estado_civil_esposa" id="estado-esposa-hidden" value="Soltera" required>
                                            <div class="select-trigger-catedral" onclick="toggleSelectCatedral(this)">
                                                <span class="select-texto">Soltera</span>
                                                <i class="fas fa-chevron-down select-icono"></i>
                                            </div>
                                            <ul class="select-opciones-catedral">
                                                <li onclick="seleccionarOpcionCatedral(this, 'Soltera', 'Soltera', 'estado-esposa-hidden')">Soltera</li>
                                                <li onclick="seleccionarOpcionCatedral(this, 'Viuda', 'Viuda', 'estado-esposa-hidden')">Viuda</li>
                                            </ul>
                                        </div>

                                    </div>
                                    <div style="flex: 1;">
                                        <label style="font-weight: bold; font-size: 0.85rem; display: block; margin-bottom: 5px;">Edad:</label>
                                        <input type="number" name="edad_esposa" class="input-estilo-catedral" style="width: 100%;">
                                    </div>
                                </div>
                                <div>
                                    <label style="font-weight: bold; font-size: 0.85rem; display: block; margin-bottom: 5px;">Si es viuda, de quién:</label>
                                    <input type="text" name="viuda_de_esposa" class="input-estilo-catedral" style="width: 100%;" placeholder="N/A">
                                </div>
                                <div>
                                    <label style="font-weight: bold; font-size: 0.85rem; display: block; margin-bottom: 5px;">Natural de (Ciudad/Estado):</label>
                                    <input type="text" name="natural_esposa" class="input-estilo-catedral" style="width: 100%;">
                                </div>
                                <div>
                                    <label style="font-weight: bold; font-size: 0.85rem; display: block; margin-bottom: 5px;">Nombre del Padre:</label>
                                    <input type="text" name="padre_esposa" class="input-estilo-catedral" style="width: 100%;" placeholder="Padre de la esposa">
                                </div>
                                <div>
                                    <label style="font-weight: bold; font-size: 0.85rem; display: block; margin-bottom: 5px;">Nombre de la Madre:</label>
                                    <input type="text" name="madre_esposa" class="input-estilo-catedral" style="width: 100%;" placeholder="Madre de la esposa">
                                </div>
                            </div>
                        </div>
                    </div>

                    <h4 style="color: var(--acento-dorado); margin-top: 25px; margin-bottom: 15px; border-bottom: 1px solid rgba(198,156,109,0.3); padding-bottom: 5px;">3. Datos de la Celebración</h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                        <div>
                            <label style="font-weight: bold; font-size: 0.85rem; display: block; margin-bottom: 5px;">Fecha de Matrimonio:</label>
                            <input type="date" name="fecha_matrimonio" class="input-estilo-catedral" style="width: 100%;" required>
                        </div>
                        <div>
                            <label style="font-weight: bold; font-size: 0.85rem; display: block; margin-bottom: 5px;">Ministro Asistente:</label>
                            <input type="text" name="ministro" class="input-estilo-catedral" style="width: 100%;" required placeholder="Pbro. / Obispo">
                        </div>
                        <div style="grid-column: span 2;">
                            <label style="font-weight: bold; font-size: 0.85rem; display: block; margin-bottom: 5px;">Testigos:</label>
                            <input type="text" name="testigos" class="input-estilo-catedral" style="width: 100%;" placeholder="Coloque una coma despues de cada nombre">
                        </div>
                        <div style="display: flex; gap: 10px; grid-column: span 2;">
                            <div style="flex: 1;"><label style="font-weight: bold; font-size: 0.85rem; display: block; margin-bottom: 5px;">Libro N°:</label><input type="text" inputmode="numeric" pattern="[0-9]*" autocomplete="nope" name="libro_num" class="input-estilo-catedral" style="width: 100%;" required></div>
                            <div style="flex: 1;"><label style="font-weight: bold; font-size: 0.85rem; display: block; margin-bottom: 5px;">Folio:</label><input type="text" inputmode="numeric" pattern="[0-9]*" autocomplete="nope" name="folio_num" class="input-estilo-catedral" style="width: 100%;" required></div>
                            <div style="flex: 1;"><label style="font-weight: bold; font-size: 0.85rem; display: block; margin-bottom: 5px;">Acta N°:</label><input type="text" inputmode="numeric" pattern="[0-9]*" autocomplete="nope" name="acta_num" class="input-estilo-catedral" style="width: 100%;" required></div>
                        </div>
                    </div>

                    <div style="text-align: right; margin-top: 20px;">
                        <button type="button" class="boton-sagrado-secundario" onclick="cerrarModal('modal-crear-matrimonio')">Cancelar</button>
                        <button type="submit" class="boton-sagrado-primario" style="background-color: #3498db; border-color: #3498db; color: #ffffff;"><i class="fas fa-save"></i> Registrar Matrimonio</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<div id="modal-editar-matrimonio" class="modal-catedral">
        <div class="modal-contenido" style="max-width: 850px;">
            <div class="modal-cabecera">
                <h3><i class="fas fa-edit"></i> Editar Partida de Matrimonio</h3>
                <button class="btn-cerrar-modal" onclick="cerrarModal('modal-editar-matrimonio')"><i class="fas fa-times"></i></button>
            </div>
            
            <div class="modal-cuerpo" style="max-height: 75vh; overflow-y: auto; padding-right: 15px;">
                <form action="../php/editar_matrimonio.php" method="POST" autocomplete="off">
                    
                    <input type="hidden" name="id_documento" id="edit-mat-id">
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                        
                        <div>
                            <h4 style="color: var(--acento-dorado); margin-bottom: 15px; border-bottom: 1px solid rgba(198,156,109,0.3); padding-bottom: 5px;">1. Datos del Esposo</h4>
                            <div style="display: flex; flex-direction: column; gap: 12px;">
                                <div>
                                    <label style="font-weight: bold; font-size: 0.85rem; display: block; margin-bottom: 5px;">Nombres y Apellidos:</label>
                                    <input type="text" name="nombre_esposo" id="edit-mat-nombre-esposo" class="input-estilo-catedral" style="width: 100%;" required>
                                </div>
                                <div style="display: flex; gap: 10px;">
                                    <div style="flex: 2;">
                                        <label style="font-weight: bold; font-size: 0.85rem; display: block; margin-bottom: 5px;">Estado Civil:</label>
                                        
                                        <div class="select-personalizado-catedral">
                                            <input type="hidden" name="estado_civil_esposo" id="edit-mat-estado-esposo-hidden" required>
                                            <div class="select-trigger-catedral" onclick="toggleSelectCatedral(this)">
                                                <span class="select-texto" id="texto-edit-estado-esposo">Soltero</span>
                                                <i class="fas fa-chevron-down select-icono"></i>
                                            </div>
                                            <ul class="select-opciones-catedral">
                                                <li onclick="seleccionarOpcionCatedral(this, 'Soltero', 'Soltero', 'edit-mat-estado-esposo-hidden')">Soltero</li>
                                                <li onclick="seleccionarOpcionCatedral(this, 'Viudo', 'Viudo', 'edit-mat-estado-esposo-hidden')">Viudo</li>
                                            </ul>
                                        </div>
                                    </div>
                                    <div style="flex: 1;">
                                        <label style="font-weight: bold; font-size: 0.85rem; display: block; margin-bottom: 5px;">Edad:</label>
                                        <input type="number" name="edad_esposo" id="edit-mat-edad-esposo" class="input-estilo-catedral" style="width: 100%;">
                                    </div>
                                </div>
                                <div>
                                    <label style="font-weight: bold; font-size: 0.85rem; display: block; margin-bottom: 5px;">Si es viudo, de quién:</label>
                                    <input type="text" name="viudo_de_esposo" id="edit-mat-viudo-esposo" class="input-estilo-catedral" style="width: 100%;">
                                </div>
                                <div>
                                    <label style="font-weight: bold; font-size: 0.85rem; display: block; margin-bottom: 5px;">Natural de (Ciudad/Estado):</label>
                                    <input type="text" name="natural_esposo" id="edit-mat-natural-esposo" class="input-estilo-catedral" style="width: 100%;">
                                </div>
                                <div>
                                    <label style="font-weight: bold; font-size: 0.85rem; display: block; margin-bottom: 5px;">Nombre del Padre:</label>
                                    <input type="text" name="padre_esposo" id="edit-mat-padre-esposo" class="input-estilo-catedral" style="width: 100%;">
                                </div>
                                <div>
                                    <label style="font-weight: bold; font-size: 0.85rem; display: block; margin-bottom: 5px;">Nombre de la Madre:</label>
                                    <input type="text" name="madre_esposo" id="edit-mat-madre-esposo" class="input-estilo-catedral" style="width: 100%;">
                                </div>
                            </div>
                        </div>

                        <div>
                            <h4 style="color: var(--acento-dorado); margin-bottom: 15px; border-bottom: 1px solid rgba(198,156,109,0.3); padding-bottom: 5px;">2. Datos de la Esposa</h4>
                            <div style="display: flex; flex-direction: column; gap: 12px;">
                                <div>
                                    <label style="font-weight: bold; font-size: 0.85rem; display: block; margin-bottom: 5px;">Nombres y Apellidos:</label>
                                    <input type="text" name="nombre_esposa" id="edit-mat-nombre-esposa" class="input-estilo-catedral" style="width: 100%;" required>
                                </div>
                                <div style="display: flex; gap: 10px;">
                                    <div style="flex: 2;">
                                        <label style="font-weight: bold; font-size: 0.85rem; display: block; margin-bottom: 5px;">Estado Civil:</label>
                                        
                                        <div class="select-personalizado-catedral">
                                            <input type="hidden" name="estado_civil_esposa" id="edit-mat-estado-esposa-hidden" required>
                                            <div class="select-trigger-catedral" onclick="toggleSelectCatedral(this)">
                                                <span class="select-texto" id="texto-edit-estado-esposa">Soltera</span>
                                                <i class="fas fa-chevron-down select-icono"></i>
                                            </div>
                                            <ul class="select-opciones-catedral">
                                                <li onclick="seleccionarOpcionCatedral(this, 'Soltera', 'Soltera', 'edit-mat-estado-esposa-hidden')">Soltera</li>
                                                <li onclick="seleccionarOpcionCatedral(this, 'Viuda', 'Viuda', 'edit-mat-estado-esposa-hidden')">Viuda</li>
                                            </ul>
                                        </div>
                                    </div>
                                    <div style="flex: 1;">
                                        <label style="font-weight: bold; font-size: 0.85rem; display: block; margin-bottom: 5px;">Edad:</label>
                                        <input type="number" name="edad_esposa" id="edit-mat-edad-esposa" class="input-estilo-catedral" style="width: 100%;">
                                    </div>
                                </div>
                                <div>
                                    <label style="font-weight: bold; font-size: 0.85rem; display: block; margin-bottom: 5px;">Si es viuda, de quién:</label>
                                    <input type="text" name="viuda_de_esposa" id="edit-mat-viuda-esposa" class="input-estilo-catedral" style="width: 100%;">
                                </div>
                                <div>
                                    <label style="font-weight: bold; font-size: 0.85rem; display: block; margin-bottom: 5px;">Natural de (Ciudad/Estado):</label>
                                    <input type="text" name="natural_esposa" id="edit-mat-natural-esposa" class="input-estilo-catedral" style="width: 100%;">
                                </div>
                                <div>
                                    <label style="font-weight: bold; font-size: 0.85rem; display: block; margin-bottom: 5px;">Nombre del Padre:</label>
                                    <input type="text" name="padre_esposa" id="edit-mat-padre-esposa" class="input-estilo-catedral" style="width: 100%;">
                                </div>
                                <div>
                                    <label style="font-weight: bold; font-size: 0.85rem; display: block; margin-bottom: 5px;">Nombre de la Madre:</label>
                                    <input type="text" name="madre_esposa" id="edit-mat-madre-esposa" class="input-estilo-catedral" style="width: 100%;">
                                </div>
                            </div>
                        </div>
                    </div>

                    <h4 style="color: var(--acento-dorado); margin-top: 25px; margin-bottom: 15px; border-bottom: 1px solid rgba(198,156,109,0.3); padding-bottom: 5px;">3. Datos de la Celebración</h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                        <div>
                            <label style="font-weight: bold; font-size: 0.85rem; display: block; margin-bottom: 5px;">Fecha de Matrimonio:</label>
                            <input type="date" name="fecha_matrimonio" id="edit-mat-fecha" class="input-estilo-catedral" style="width: 100%;" required>
                        </div>
                        <div>
                            <label style="font-weight: bold; font-size: 0.85rem; display: block; margin-bottom: 5px;">Ministro Asistente:</label>
                            <input type="text" name="ministro" id="edit-mat-ministro" class="input-estilo-catedral" style="width: 100%;" required>
                        </div>
                        <div style="grid-column: span 2;">
                            <label style="font-weight: bold; font-size: 0.85rem; display: block; margin-bottom: 5px;">Testigos:</label>
                            <input type="text" name="testigos" id="edit-mat-testigos" class="input-estilo-catedral" style="width: 100%;">
                        </div>
                        <div style="display: flex; gap: 10px; grid-column: span 2;">
                            <div style="flex: 1;"><label style="font-weight: bold; font-size: 0.85rem; display: block; margin-bottom: 5px;">Libro N°:</label><input type="text" inputmode="numeric" pattern="[0-9]*" autocomplete="nope" name="libro_num" id="edit-mat-libro" class="input-estilo-catedral" style="width: 100%;" required></div>
                            <div style="flex: 1;"><label style="font-weight: bold; font-size: 0.85rem; display: block; margin-bottom: 5px;">Folio:</label><input type="text" inputmode="numeric" pattern="[0-9]*" autocomplete="nope" name="folio_num" id="edit-mat-folio" class="input-estilo-catedral" style="width: 100%;" required></div>
                            <div style="flex: 1;"><label style="font-weight: bold; font-size: 0.85rem; display: block; margin-bottom: 5px;">Acta N°:</label><input type="text" inputmode="numeric" pattern="[0-9]*" autocomplete="nope" name="acta_num" id="edit-mat-num" class="input-estilo-catedral" style="width: 100%;" required></div>
                        </div>
                    </div>

                    <div style="text-align: right; margin-top: 20px;">
                        <button type="button" class="boton-sagrado-secundario" onclick="cerrarModal('modal-editar-matrimonio')">Cancelar</button>
                        <button type="submit" class="boton-sagrado-primario" style="background-color: #3498db; border-color: #3498db; color: #ffffff;"><i class="fas fa-save"></i> Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<div id="modal-confirmar-eliminar-acta" class="modal-catedral">
        <div class="modal-contenido" style="max-width: 400px; text-align: center; border-top: 5px solid var(--acento-rojo);">
            <div class="modal-cuerpo" style="padding: 30px 20px;">
                <i class="fas fa-exclamation-triangle" style="font-size: 3.5rem; color: var(--acento-rojo); margin-bottom: 20px;"></i>
                
                <h3 class="titulo-advertencia" style="margin-top: 0;">¿Eliminar Acta?</h3>
                
                <style>
                    body.oscuro #texto-peligro-acta,
                    body.oscuro #texto-peligro-acta small {
                        color: #ffffff !important;
                        text-shadow: 0px 1px 3px rgba(0,0,0,0.8) !important;
                    }
                </style>
                
                <p id="texto-peligro-acta" style="margin-bottom: 25px; line-height: 1.5; color: var(--texto-claro);">
                    ¿Está seguro de que desea eliminar esta acta de forma permanente? <br>
                    <small style="opacity: 0.8; display: block; margin-top: 10px;">Esta acción es irreversible.</small>
                </p>
                
                <div style="display: flex; gap: 10px; justify-content: center;">
                    <button type="button" class="boton-sagrado-secundario" onclick="cerrarModalActa()">Cancelar</button>
                    <button type="button" class="boton-sagrado-primario" onclick="ejecutarBorradoActa()" style="background: linear-gradient(135deg, var(--acento-rojo), #521818); box-shadow: 0 4px 10px rgba(122, 40, 40, 0.3); border-color: transparent; color: white;">
                        <i class="fas fa-trash-alt"></i> Sí, Eliminar
                    </button>
                </div>
            </div>
        </div>
    </div>

<div id="modal-confirmar-estado" class="modal-catedral">
        <div class="modal-contenido" style="max-width: 450px; text-align: center; padding: 30px;">
            
            <i id="icono-estado-modal" class="fas fa-user-slash" style="font-size: 4rem; color: var(--acento-dorado); margin-bottom: 20px;"></i>
            
            <h3 style="margin-bottom: 15px; color: var(--texto-principal);">¿Confirmar Acción?</h3>
            <p style="margin-bottom: 25px; opacity: 0.8; font-size: 1.1rem; line-height: 1.5;" id="texto-estado-modal">
                ¿Está seguro que desea suspender a este usuario?
            </p>
            
            <form action="../php/controlador.php" method="POST">
                <input type="hidden" name="accion" value="admin-toggle-estado">
                <input type="hidden" name="usuario-id" id="input-estado-id">
                <input type="hidden" name="nuevo-estado" id="input-estado-nuevo">
                
                <div style="display: flex; gap: 15px; justify-content: center;">
                    <button type="button" class="boton-sagrado-secundario" onclick="cerrarModal('modal-confirmar-estado')">Cancelar</button>
                    <button type="submit" class="boton-sagrado-primario" style="background: var(--acento-dorado); border-color: var(--acento-dorado);">
                        <i class="fas fa-check"></i> Sí, Confirmar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const itemsPorPagina = 3; // Mostrará de 5 en 5
        let itemsVisibles = itemsPorPagina;
        
        // Buscamos el ID que le acabamos de poner al contenedor
        const contenedor = document.getElementById('lista-actividades-panel');
        if (!contenedor) return; 
        
        const items = contenedor.getElementsByClassName('item-actividad');
        const botonCargarMas = document.getElementById('btn-cargar-mas-actividad');
        
        if (items.length === 0 || !botonCargarMas) return;

        // Ocultar todos los que pasen de 5
        for (let i = itemsPorPagina; i < items.length; i++) {
            items[i].style.display = 'none';
        }

        // Encender el botón si hay más de 5 en total
        if (items.length > itemsPorPagina) {
            botonCargarMas.style.display = 'inline-block';
        }

        // ¿Qué pasa cuando hacemos clic?
        botonCargarMas.addEventListener('click', function() {
            let mostradosEnEsteClic = 0;
            
            for (let i = itemsVisibles; i < items.length; i++) {
                items[i].style.display = 'block'; 
                mostradosEnEsteClic++;
                itemsVisibles++;
                if (mostradosEnEsteClic === itemsPorPagina) break; 
            }

            // Ocultar botón si ya llegamos al final
            if (itemsVisibles >= items.length) {
                botonCargarMas.style.display = 'none';
            }
        });
    });
    </script>

<div id="modal-easter-egg" class="modal-catedral" style="z-index: 9999;">
        <div class="modal-contenido" style="max-width: 450px; background: #1a1a1a; border: 2px solid var(--acento-dorado); border-radius: 10px;">
            <div class="modal-cabecera" style="border-bottom: 1px dashed var(--acento-dorado); background: #0a0a0a;">
                <h3 style="color: var(--acento-dorado); font-family: 'Times New Roman', serif; font-size: 1.3rem;">
                    <i class="fas fa-gamepad" style="margin-right: 8px;"></i> Rincón del Desarrollador
                </h3>
                <button class="btn-cerrar-modal" onclick="cerrarSalaSecreta()" style="color: white;"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-cuerpo" style="text-align: center; padding: 20px; background: #1a1a1a; min-height: 480px;">
                
                <div id="menu-juegos" style="display: block; padding-top: 20px;">
                    <i class="fas fa-church" style="font-size: 3rem; color: var(--acento-dorado); margin-bottom: 15px;"></i>
                    <h2 style="color: white; margin-bottom: 20px; font-family: monospace; font-size: 1.2rem;">Arcade Parroquial</h2>
                    
                    <button onclick="prepararJuego('panes')" style="display: block; width: 85%; margin: 0 auto 10px auto; background: transparent; border: 2px solid var(--acento-dorado); color: var(--acento-dorado); padding: 12px; font-size: 1rem; cursor: pointer; border-radius: 8px; font-weight: bold;">
                        <i class="fas fa-bread-slice"></i> 1. Milagro de los Panes
                    </button>
                    
                    <button onclick="prepararJuego('flappy')" style="display: block; width: 85%; margin: 0 auto 10px auto; background: transparent; border: 2px solid #87CEEB; color: #87CEEB; padding: 12px; font-size: 1rem; cursor: pointer; border-radius: 8px; font-weight: bold;">
                        <i class="fas fa-dove"></i> 2. Vuelo del Espíritu
                    </button>

                    <button onclick="prepararJuego('doom')" style="display: block; width: 85%; margin: 0 auto; background: transparent; border: 2px solid #e74c3c; color: #e74c3c; padding: 12px; font-size: 1rem; cursor: pointer; border-radius: 8px; font-weight: bold; letter-spacing: 1px;">
                        <i class="fas fa-street-view"></i> 3. Misión 3D
                    </button>
                </div>

                <div id="pantalla-juego" style="display: none;">
                    <p id="instrucciones-juego" style="color: #ccc; font-size: 0.9rem; margin-bottom: 10px; font-style: italic; min-height: 40px;">
                        Instrucciones aquí...
                    </p>
                    
                    <canvas id="juego-canvas" width="400" height="400" style="background: #000; border: 2px solid var(--acento-dorado); box-shadow: 0 0 15px rgba(198,156,109,0.2); max-width: 100%; border-radius: 5px;"></canvas>
                    
                    <div style="margin-top: 15px; display: flex; justify-content: space-between; align-items: center;">
                        <button onclick="volverMenuJuegos()" style="background: #333; color: white; border: none; padding: 8px 15px; cursor: pointer; border-radius: 4px;">
                            <i class="fas fa-arrow-left"></i> Menú
                        </button>
                        
                        <p id="juego-score" style="color: white; margin: 0; font-size: 1.1rem; font-weight: bold;">
                            Score: 0
                        </p>
                        
                        <button onclick="iniciarPartida()" style="background: var(--acento-dorado); color: black; border: none; padding: 8px 20px; cursor: pointer; font-weight: bold; border-radius: 4px;">
                            <i class="fas fa-play"></i> Iniciar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if($esSecretario): ?>
    <button class="boton-flotante-actividad" onclick="togglePanelActividad()" title="Registro de Actividad">
        <i class="fas fa-bell"></i>
        <?php if($notificacionesNuevas > 0): ?>
            <span class="badge-notificacion"><?php echo $notificacionesNuevas; ?></span>
        <?php endif; ?>
    </button>
    <?php endif; ?>

    <script>
        window.idActaParaBorrar = null;

        function abrirModalEliminarActa(id) {
            window.idActaParaBorrar = id;
            abrirModal('modal-confirmar-eliminar-acta');
        }

        function cerrarModalActa() {
            cerrarModal('modal-confirmar-eliminar-acta');
            window.idActaParaBorrar = null;
        }

        function ejecutarBorradoActa() {
            if(window.idActaParaBorrar !== null) {
                window.location.href = '../php/eliminar_documento.php?id=' + window.idActaParaBorrar;
            }
        }



        /* FILTRO DE BÚSQUEDA */
        function filtrarTablaArchivos() {
            let filtro = document.getElementById("buscador-archivos").value.toLowerCase();
            let filas = document.querySelectorAll("#tabla-registros-archivos tbody .fila-archivo");
            let tituloActual = document.getElementById("titulo-tabla-archivos").innerText;
            let tipoActivo = "";
            if (tituloActual.includes("Bautismos")) tipoActivo = "Bautismo";
            else if (tituloActual.includes("Confirmaciones")) tipoActivo = "Confirmacion";
            else if (tituloActual.includes("Matrimonios")) tipoActivo = "Matrimonio";

            let resultadosEncontrados = false;

            filas.forEach(function(fila) {
                if (fila.getAttribute("data-tipo") === tipoActivo) {
                    
                    let textoFila = fila.textContent.toLowerCase();
                    if (textoFila.includes(filtro)) {
                        fila.style.display = "table-row";
                        resultadosEncontrados = true;
                    } else {
                        fila.style.display = "none";
                    }
                } else {
                    fila.style.display = "none";
                }
            });

            const msgVacio = document.getElementById('mensaje-sin-archivos');
            const tablaCont = document.querySelector('#vista-tabla-archivos .contenedor-tabla-responsiva');
            
            if (resultadosEncontrados) {
                tablaCont.style.display = 'block';
                if (msgVacio) msgVacio.style.display = 'none';
            } else {
                tablaCont.style.display = 'none';
                if (msgVacio) {
                    if (filtro !== "") {
                        msgVacio.innerHTML = `<p style="color: var(--texto-claro); opacity: 0.7; font-size: 1.1rem;"><i class="fas fa-search"></i> No se encontraron resultados para "<b>${filtro}</b>".</p>`;
                    } else {
                        msgVacio.innerHTML = `<p style="color: var(--texto-claro); opacity: 0.7; font-size: 1.1rem;"><i class="fas fa-info-circle"></i> No hay documentos guardados en esta categoría.</p>`;
                    }
                    msgVacio.style.display = 'block';
                }
            }
        }

        function limpiarBuscadorArchivos() {
            let inputBuscador = document.getElementById("buscador-archivos");
            if (inputBuscador) {
                inputBuscador.value = "";
                
                const msgVacio = document.getElementById('mensaje-sin-archivos');
                if (msgVacio) {
                    msgVacio.innerHTML = `<p style="color: var(--texto-claro); opacity: 0.7; font-size: 1.1rem;"><i class="fas fa-info-circle"></i> No hay documentos guardados en esta categoría.</p>`;
                }
                
                filtrarTablaArchivos();
            }
        }
    </script>

    <script src="../recursos/js/chart.min.js"></script>
    <script src="../recursos/flatpickr/flatpickr.min.js"></script>
    <script src="../recursos/flatpickr/es.js"></script>
    <script src="../recursos/js/dashboard.js?v=<?php echo time(); ?>"></script>

    <script>

        /* VER/OCULTAR CONTRASEÑA */
        function toggleclave(idInput, iconoElegido) {
            const input = document.getElementById(idInput);
            if (!input) return;
            
            if (input.type === "password") {
                input.type = "text";
                iconoElegido.classList.remove("fa-eye");
                iconoElegido.classList.add("fa-eye-slash");
            } else {
                input.type = "password";
                iconoElegido.classList.remove("fa-eye-slash");
                iconoElegido.classList.add("fa-eye");
            }
        }

        /* SUSPENDER/ACTIVAR USUARIO */
        function cambiarEstadoUsuario(id, estadoActual, nombre) {
            let nuevoEstado = (estadoActual === 'Activo') ? 'Inactivo' : 'Activo';
            let accionTxt = (estadoActual === 'Activo') ? 'suspender el acceso' : 'restaurar el acceso';
            let iconoClase = (estadoActual === 'Activo') ? 'fa-user-slash' : 'fa-user-check';
            
            document.getElementById('input-estado-id').value = id;
            document.getElementById('input-estado-nuevo').value = nuevoEstado;
            
            let textoModal = `¿Está seguro que desea ${accionTxt} del usuario <br><span style="display: inline-block; margin-top: 10px;"><strong style="color: var(--acento-dorado); font-size: 1.2rem;">«${nombre}»</strong>?</span>`;
            document.getElementById('texto-estado-modal').innerHTML = textoModal;
            
            document.getElementById('icono-estado-modal').className = `fas ${iconoClase}`;
            
            abrirModal('modal-confirmar-estado');
        }

        

         /*EASTER EGG: SALA DE ARCADE*/
        let contadorClicksPerfil = 0, temporizadorClicks = null;
        let records = {
            panes: parseInt(localStorage.getItem('record_panes')) || 0,
            flappy: parseInt(localStorage.getItem('record_flappy')) || 0,
            doom: parseInt(localStorage.getItem('record_doom')) || 1
        };

        let teclas = { ArrowLeft: false, ArrowRight: false, ArrowUp: false, ArrowDown: false, KeyW: false, KeyA: false, KeyS: false, KeyD: false };
        let musicInterval = null; 

        /*MIS SPRITES*/
        const imgVagabundoSheet = new Image();
        imgVagabundoSheet.src = '../recursos/img/vagabundo_arcade.png'; 

        const imgManos = new Image();
        imgManos.src = '../recursos/img/manos_arma.png'; 

        document.querySelector('.icono-perfil').addEventListener('click', function() {
            contadorClicksPerfil++;
            if (contadorClicksPerfil === 1) temporizadorClicks = setTimeout(() => { contadorClicksPerfil = 0; }, 1500); 
            if (contadorClicksPerfil === 3) { clearTimeout(temporizadorClicks); contadorClicksPerfil = 0; abrirSalaSecreta(); }
        });

        function abrirSalaSecreta() {
            abrirModal('modal-easter-egg'); volverMenuJuegos();
            window.addEventListener('keydown', controladorKeyDown, { passive: false });
            window.addEventListener('keyup', controladorKeyUp);
        }

        function cerrarSalaSecreta() {
            cerrarModal('modal-easter-egg'); clearInterval(gameLoop); stopMusic();
            window.removeEventListener('keydown', controladorKeyDown);
            window.removeEventListener('keyup', controladorKeyUp);
            for(let key in teclas) teclas[key] = false;
        }

        /* MOTOR DE AUDIO */
        let audioCtx = null;
        function initAudio() {
            if (!audioCtx) audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            if (audioCtx.state === 'suspended') audioCtx.resume();
        }

        function playRetroSound(type) {
            if (!audioCtx) return;
            const osc = audioCtx.createOscillator(); const gain = audioCtx.createGain();
            osc.connect(gain); gain.connect(audioCtx.destination);
            const now = audioCtx.currentTime;
            if (type === 'jump') {
                osc.type = 'sine'; osc.frequency.setValueAtTime(300, now); osc.frequency.exponentialRampToValueAtTime(600, now+0.1);
                gain.gain.setValueAtTime(0.2, now); gain.gain.exponentialRampToValueAtTime(0.01, now+0.1);
                osc.start(now); osc.stop(now+0.1);
            } else if (type === 'score') {
                osc.type = 'square'; osc.frequency.setValueAtTime(800, now); osc.frequency.setValueAtTime(1200, now + 0.05);
                gain.gain.setValueAtTime(0.05, now); gain.gain.exponentialRampToValueAtTime(0.01, now+0.1);
                osc.start(now); osc.stop(now+0.1);
            } else if (type === 'hit') {
                osc.type = 'sawtooth'; osc.frequency.setValueAtTime(150, now); osc.frequency.exponentialRampToValueAtTime(50, now + 0.2);
                gain.gain.setValueAtTime(0.2, now); gain.gain.exponentialRampToValueAtTime(0.01, now+0.2);
                osc.start(now); osc.stop(now+0.2);
            } else if (type === 'shoot') {
                osc.type = 'triangle'; osc.frequency.setValueAtTime(400, now); osc.frequency.exponentialRampToValueAtTime(100, now + 0.1);
                gain.gain.setValueAtTime(0.1, now); gain.gain.exponentialRampToValueAtTime(0.01, now+0.1);
                osc.start(now); osc.stop(now+0.1);
            }
        }

        function playMusic(tipo) {
            stopMusic();
            let tempo = tipo === 'panes' ? 140 : 180; let melodía = []; let waveType = 'triangle'; 
            if(tipo === 'panes') { melodía = [392, 440, 493, 523, 587, 523, 493, 440]; } 
            else if(tipo === 'flappy') { melodía = [523, 0, 659, 0, 783, 0, 1046, 0]; }
            else if(tipo === 'doom') { tempo = 80; waveType = 'sawtooth'; melodía = [146.83, 146.83, 164.81, 130.81, 146.83, 0, 110.00, 130.81]; } 
            else { return; }

            let step = 0;
            musicInterval = setInterval(() => {
                let freq = melodía[step % melodía.length];
                if (freq > 0) {
                    const osc = audioCtx.createOscillator(); const gain = audioCtx.createGain();
                    osc.type = waveType; osc.connect(gain); gain.connect(audioCtx.destination);
                    osc.frequency.setValueAtTime(freq, audioCtx.currentTime);
                    gain.gain.setValueAtTime(0.05, audioCtx.currentTime);
                    gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.2);
                    osc.start(); osc.stop(audioCtx.currentTime + 0.2);
                    if(tipo === 'doom' && step % 2 === 0) {
                        const dOsc = audioCtx.createOscillator(); const dGain = audioCtx.createGain();
                        dOsc.type = 'sine'; dOsc.connect(dGain); dGain.connect(audioCtx.destination);
                        dOsc.frequency.setValueAtTime(73.42, audioCtx.currentTime); 
                        dGain.gain.setValueAtTime(0.06, audioCtx.currentTime); dGain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 1.0);
                        dOsc.start(); dOsc.stop(audioCtx.currentTime + 1.0);
                    }
                }
                step++;
            }, 60000 / tempo);
        }

        function stopMusic() { if(musicInterval) clearInterval(musicInterval); }

        const canvas = document.getElementById('juego-canvas');
        const ctx = canvas.getContext('2d');
        let gameLoop, juegoActual = '', estadoJuego = 'detenido', score = 0;

        function volverMenuJuegos() {
            clearInterval(gameLoop); stopMusic(); estadoJuego = 'detenido';
            document.getElementById('menu-juegos').style.display = 'block';
            document.getElementById('pantalla-juego').style.display = 'none';
        }

        function prepararJuego(tipo) {
            juegoActual = tipo;
            document.getElementById('menu-juegos').style.display = 'none';
            document.getElementById('pantalla-juego').style.display = 'block';
            
            if(tipo === 'panes') {
                document.getElementById('instrucciones-juego').innerHTML = '<strong style="color: gold">Flechas o A/D ⬅️ ➡️</strong> atrapar.<br> <span style="font-size:0.8rem;">ESPACIO Iniciar</span>';
                canvas.style.background = 'linear-gradient(to bottom, #0f2027, #203a43, #2c5364)';
            } else if(tipo === 'flappy') {
                document.getElementById('instrucciones-juego').innerHTML = '<strong style="color: #87CEEB">ESPACIO, ⬆️ o W</strong> volar.';
                canvas.style.background = '#87CEEB';
            } else if(tipo === 'doom') {
                document.getElementById('instrucciones-juego').innerHTML = 'Flechas o WASD mover | ESPACIO disparar pan.';
                canvas.style.background = '#000';
            }
            actualizarScoreUI();
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            ctx.fillStyle = "white"; ctx.font = "18px monospace"; ctx.textAlign = "center";
            ctx.fillText("Presiona ESPACIO o INICIAR", canvas.width/2, canvas.height/2);
        }

        function actualizarScoreUI() {
            let sc = document.getElementById('juego-score');
            if(juegoActual === 'panes') sc.innerHTML = `<i class="fas fa-star" style="color: gold;"></i> ${score} &nbsp;|&nbsp; <i class="fas fa-heart" style="color: red;"></i> ${vidasPanes||3} &nbsp;|&nbsp; <i class="fas fa-trophy" style="color: gold;"></i> Máx: ${records.panes}`;
            else if(juegoActual === 'flappy') sc.innerHTML = `<i class="fas fa-dove" style="color: #87CEEB;"></i> ${score} &nbsp;|&nbsp; <i class="fas fa-trophy" style="color: gold;"></i> Máx: ${records.flappy}`;
            else if(juegoActual === 'doom') {
                sc.innerHTML = `<i class="fas fa-street-view" style="color: #e74c3c;"></i> Nv. ${nivelDoom} &nbsp;|&nbsp; <i class="fas fa-bread-slice" style="color: #2ecc71;"></i> ${score}/${metaDoom} &nbsp;|&nbsp; <i class="fas fa-trophy" style="color: gold;"></i> Máx: ${records.doom}`;
            }
        }

        function iniciarPartida() {
            initAudio(); stopMusic();
            clearInterval(gameLoop); estadoJuego = 'jugando'; score = 0;
            playMusic(juegoActual);
            if(juegoActual === 'panes') iniciarPanes();
            else if(juegoActual === 'flappy') iniciarFlappy();
            else if(juegoActual === 'doom') iniciarDoom();
        }

        function controladorKeyDown(e) {
            // Bloqueamos también las teclas W,A,S,D para que no desplacen la página
            if(["Space","ArrowUp","ArrowDown","ArrowLeft","ArrowRight","KeyW","KeyA","KeyS","KeyD"].includes(e.code)) { e.preventDefault(); }
            if(teclas[e.code] !== undefined) teclas[e.code] = true;

            if(e.code === 'Space') {
                if(estadoJuego !== 'jugando') { iniciarPartida(); } 
                else {
                    if(juegoActual === 'flappy') { palomaVel = -7; playRetroSound('jump'); }
                    if(juegoActual === 'doom' && framesDisparo === 0 && transicionDoom === 0) dispararPan();
                }
            }
            if((e.code === 'ArrowUp' || e.code === 'KeyW') && estadoJuego === 'jugando' && juegoActual === 'flappy') { palomaVel = -7; playRetroSound('jump'); }
        }

        function controladorKeyUp(e) { if(teclas[e.code] !== undefined) teclas[e.code] = false; }

        function finJuego(titulo, subtitulo) {
            stopMusic(); playRetroSound('hit');
            clearInterval(gameLoop); estadoJuego = 'gameover';
            
            if(juegoActual === 'panes' && score > records.panes) { records.panes = score; localStorage.setItem('record_panes', score); }
            if(juegoActual === 'flappy' && score > records.flappy) { records.flappy = score; localStorage.setItem('record_flappy', score); }
            if(juegoActual === 'doom' && nivelDoom > records.doom) { records.doom = nivelDoom; localStorage.setItem('record_doom', nivelDoom); }

            actualizarScoreUI();
            ctx.fillStyle = "rgba(0, 0, 0, 0.85)"; ctx.fillRect(0, 0, canvas.width, canvas.height);
            ctx.fillStyle = "gold"; ctx.font = "bold 22px Arial"; ctx.textAlign = "center";
            ctx.fillText(titulo, 200, 180);
            ctx.fillStyle = "white"; ctx.font = "18px Arial"; ctx.fillText(subtitulo, 200, 220);
            ctx.fillStyle = "#aaa"; ctx.font = "14px Arial"; ctx.fillText("ESPACIO para reintentar", 200, 260);
        }

        /* JUEGO 1: PANES*/
        let vidasPanes=3, cristoX=175, itemsPanes=[];
        function iniciarPanes() { itemsPanes=[]; vidasPanes=3; cristoX=175; actualizarScoreUI(); gameLoop=setInterval(loopPanes, 40); }
        function loopPanes() {
            if((teclas['ArrowLeft'] || teclas['KeyA']) && cristoX > 10) cristoX -= 18; 
            if((teclas['ArrowRight'] || teclas['KeyD']) && cristoX < 340) cristoX += 18;
            
            ctx.clearRect(0,0,400,400); ctx.fillStyle="rgba(255, 255, 255, 0.2)"; ctx.fillRect(50,50,2,2); ctx.fillRect(150,120,2,2);
            ctx.beginPath(); ctx.arc(cristoX+25,340,18,0,Math.PI*2); ctx.strokeStyle="gold"; ctx.lineWidth=3; ctx.stroke();
            ctx.beginPath(); ctx.arc(cristoX+25,345,12,0,Math.PI*2); ctx.fillStyle="#f1c27d"; ctx.fill();
            ctx.fillStyle="#fff"; ctx.fillRect(cristoX+10,355,30,45); ctx.strokeStyle="#f1c27d"; ctx.lineWidth=5;
            ctx.beginPath(); ctx.moveTo(cristoX+10,365); ctx.lineTo(cristoX-10,350); ctx.stroke();
            ctx.beginPath(); ctx.moveTo(cristoX+40,365); ctx.lineTo(cristoX+60,350); ctx.stroke();
            if (Math.random() < 0.03 + (score * 0.0005)) itemsPanes.push({x: Math.random()*370, y: -30, speed: 2 + Math.random()*2 + (score*0.05), tipo: ['🍞','🐟'][Math.floor(Math.random()*2)]});
            ctx.font='30px Arial';
            for(let i=0; i<itemsPanes.length; i++){
                let p=itemsPanes[i]; p.y+=p.speed; ctx.fillText(p.tipo, p.x, p.y);
                if(p.y>330 && p.y<390 && p.x+30 > cristoX-15 && p.x < cristoX+65){ score++; itemsPanes.splice(i,1); i--; playRetroSound('score'); actualizarScoreUI(); }
                else if(p.y>400){ vidasPanes--; itemsPanes.splice(i,1); i--; playRetroSound('hit'); actualizarScoreUI(); if(vidasPanes<=0) finJuego("La multitud fue alimentada.", `Atrapaste: ${score}`); }
            }
        }

        /* JUEGO 2: FLAPPY*/

        let palomaY=200, palomaVel=0, pilares=[], framesFlappy=0;
        function iniciarFlappy() { palomaY=200; palomaVel=0; pilares=[]; framesFlappy=0; actualizarScoreUI(); gameLoop=setInterval(loopFlappy, 25); }
        function loopFlappy() {
            ctx.clearRect(0,0,400,400); ctx.fillStyle="rgba(255,255,255,0.4)"; ctx.beginPath(); ctx.ellipse(80,80,40,20,0,0,2*Math.PI); ctx.fill();
            palomaVel += 0.5; palomaY += palomaVel;
            if(framesFlappy % 90 === 0) pilares.push({x: 400, top: Math.random()*230+20, hueco: 130, paso: false});
            for(let i=0; i<pilares.length; i++){
                let p=pilares[i]; p.x -= 3;
                let grad = ctx.createLinearGradient(p.x,0,p.x+40,0); grad.addColorStop(0,"#d4af37"); grad.addColorStop(1,"#aa8a29");
                ctx.fillStyle=grad; ctx.fillRect(p.x, 0, 40, p.top); ctx.fillRect(p.x, p.top+p.hueco, 40, 400);
                ctx.fillStyle="#fff"; ctx.fillRect(p.x-2, p.top-10, 44, 10); ctx.fillRect(p.x-2, p.top+p.hueco, 44, 10);
                if(50+30 > p.x && 50 < p.x+40 && (palomaY-15 < p.top || palomaY+15 > p.top+p.hueco)) finJuego("Vuelo Interrumpido", `Pilares: ${score}`);
                if(p.x+40 < 50 && !p.paso){ score++; p.paso=true; playRetroSound('score'); actualizarScoreUI(); }
                if(p.x+40 < 0){ pilares.splice(i,1); i--; }
            }
            ctx.save(); ctx.translate(65, palomaY+15); ctx.scale(-1, 1); ctx.font="35px Arial"; ctx.fillText("🕊️", 0, 0); ctx.restore();
            if(palomaY>400 || palomaY<-30) finJuego("Vuelo Interrumpido", `Pilares: ${score}`); framesFlappy++;
        }

        /*JUEGO 3: MISIÓN 3D*/

        const mapa3D = [[1,1,1,1,1,1,1,1,1,1],[1,0,0,0,0,0,1,0,0,1],[1,0,1,1,0,0,1,0,0,1],[1,0,0,0,0,0,0,0,0,1],[1,1,1,0,1,1,1,0,1,1],[1,0,0,0,0,0,0,0,0,1],[1,0,1,1,0,1,1,1,0,1],[1,0,0,0,0,0,0,0,0,1],[1,1,1,1,1,1,1,1,1,1]];
        let dPlayer={x:96, y:96, a:0.5}, npc=[], framesDisparo=0, zBuffer=new Array(400).fill(9999), nivelDoom=1, metaDoom=5, transicionDoom=0;
        
        const limiteActivosDoom = 5; 

        function generarNPCsDoom() { 
            let intentos = 0;
            let baldosasLibres = [];
            for(let r=0; r<9; r++){
                for(let c=0; c<10; c++){
                    if(mapa3D[r][c] === 0) baldosasLibres.push({x: c*64+32, y: r*64+32});
                }
            }
            baldosasLibres.sort(() => Math.random() - 0.5);
            
            while(npc.length < limiteActivosDoom && intentos < baldosasLibres.length){ 
                let pos = baldosasLibres[intentos];
                if(Math.hypot(pos.x - dPlayer.x, pos.y - dPlayer.y) > 64) {

                    /*DISTANCIAMIENTO DE VAGABUNDOS*/
                    let muyCerca = npc.some(n => Math.hypot(n.x - pos.x, n.y - pos.y) < 120);
                    if(!muyCerca) npc.push({x:pos.x, y:pos.y, fed:false, timerMisericordia: 0}); 
                } 
                intentos++;
            } 
        }

        function iniciarDoom() { 
            dPlayer={x:96, y:96, a:0.5}; framesDisparo=0; transicionDoom=0; nivelDoom=1; metaDoom=5; score=0; 
            npc = []; 
            generarNPCsDoom(); actualizarScoreUI(); gameLoop=setInterval(loopMotor3D, 50); 
        }
        
        function loopMotor3D() {
            if (transicionDoom > 0) {
                transicionDoom--; dibujarDoom(); 
                ctx.fillStyle = "rgba(15, 15, 30, 0.9)"; ctx.fillRect(0,0,400,400); 
                ctx.fillStyle = "white"; ctx.font = "bold 26px 'Times New Roman'"; ctx.textAlign = "center"; 
                ctx.fillText(`¡NIVEL ${nivelDoom} COMPLETADO!`, 200, 180);
                ctx.font = "16px Arial"; ctx.fillText(`Misericordia repartida. Preparando Nivel ${nivelDoom+1}...`, 200, 220);
                
                if (transicionDoom === 0) {
                    if(nivelDoom > records.doom) { records.doom = nivelDoom; localStorage.setItem('record_doom', nivelDoom); }
                    nivelDoom++; metaDoom+=5; score=0; 
                    npc = []; 
                    generarNPCsDoom(); actualizarScoreUI();
                }
                return; 
            }

            let paso=8, rot=0.12, nextX=dPlayer.x, nextY=dPlayer.y;
            if(teclas['ArrowLeft'] || teclas['KeyA']) dPlayer.a -= rot; 
            if(teclas['ArrowRight'] || teclas['KeyD']) dPlayer.a += rot;
            if(teclas['ArrowUp'] || teclas['KeyW']){ nextX += Math.cos(dPlayer.a)*paso; nextY += Math.sin(dPlayer.a)*paso; }
            if(teclas['ArrowDown'] || teclas['KeyS']){ nextX -= Math.cos(dPlayer.a)*paso; nextY -= Math.sin(dPlayer.a)*paso; }
            
            if(mapa3D[Math.floor(nextY/64)][Math.floor(nextX/64)]===0){ dPlayer.x=nextX; dPlayer.y=nextY; }
            if(framesDisparo>0) framesDisparo--; dibujarDoom();
        }

        function dibujarDoom() {
            ctx.fillStyle="#1a1a2e"; ctx.fillRect(0,0,400,200); ctx.fillStyle="#3e2723"; ctx.fillRect(0,200,400,200); zBuffer.fill(9999);
            const FOV = Math.PI / 3;
            for (let i=0; i<400; i+=4) {
                let rayA=dPlayer.a - Math.PI/6 + (i/400)*(Math.PI/3), c=Math.cos(rayA)*2, s=Math.sin(rayA)*2, rx=dPlayer.x, ry=dPlayer.y, dist=0, hit=false;
                for(let step=0; step<300; step++){ rx+=c; ry+=s; dist+=2; if(mapa3D[Math.floor(ry/64)]&&mapa3D[Math.floor(ry/64)][Math.floor(rx/64)]===1){ hit=true; break; } }
                if(hit){ dist*=Math.cos(dPlayer.a-rayA); zBuffer[i]=dist; zBuffer[i+1]=dist; zBuffer[i+2]=dist; zBuffer[i+3]=dist;
                let h=(64/dist)*277, cv=Math.max(20, 200-(dist/2)); ctx.fillStyle=`rgb(${cv*0.9},${cv*0.8},${cv*0.6})`; ctx.fillRect(i,200-h/2,4,h); ctx.strokeStyle="rgba(0,0,0,0.3)"; ctx.strokeRect(i,200-h/2,4,h); }
            }
            
            let npcsOrdenados = npc.slice().sort((a, b) => {
                let distA = Math.hypot(a.x - dPlayer.x, a.y - dPlayer.y);
                let distB = Math.hypot(b.x - dPlayer.x, b.y - dPlayer.y);
                return distB - distA;
            });

            /*VAGABUNDOS*/
            npcsOrdenados.forEach(n => {
                if(n.fed && n.timerMisericordia <= 0) return; 
                let dx=n.x-dPlayer.x, dy=n.y-dPlayer.y, dist=Math.sqrt(dx*dx+dy*dy), ang=Math.atan2(dy,dx)-dPlayer.a;
                while(ang<-Math.PI) ang+=Math.PI*2; while(ang>Math.PI) ang-=Math.PI*2;
                
                if(Math.abs(ang)<Math.PI/6+0.5 && dist>10){ 
                    let sX=Math.floor((ang/(Math.PI/3)+0.5)*400), sz=(64/dist)*277; 
                    
                    if (imgVagabundoSheet.complete && imgVagabundoSheet.naturalHeight !== 0) {
                        let frameWidth = imgVagabundoSheet.width / 2;
                        let aspect = frameWidth / imgVagabundoSheet.height;
                        let drawW = Math.floor(sz * aspect);
                        let sourceX = n.fed ? frameWidth : 0; 
                        let startX = Math.floor(sX - drawW/2);
                        let endX = Math.floor(sX + drawW/2);
                        
                        for(let x = startX; x < endX; x++) {
                            if(x >= 0 && x < 400 && dist < zBuffer[x]) {
                                let texX = Math.floor((x - startX) * frameWidth / drawW) + sourceX;
                                ctx.drawImage(imgVagabundoSheet, texX, 0, 1, imgVagabundoSheet.height, x, 200 - sz/2 + sz/4, 1, sz);
                            }
                        }
                    } else {
                        let limitX = Math.max(0,Math.min(399,sX));
                        if(dist < zBuffer[limitX]) {
                            ctx.font=`${sz}px Arial`; ctx.textAlign="center"; ctx.fillText(n.fed ? "😇" : "🧟", sX, 200+sz/3); 
                        }
                    }
                }
                if(n.fed && n.timerMisericordia > 0) { n.timerMisericordia--; }
            });
            
            /* ARMA FPS */
        
            if (imgManos.complete && imgManos.naturalHeight !== 0) {
                let frameWidth = imgManos.width / 3;
                let indexMarco = 2;
                
                if (framesDisparo > 10) indexMarco = 1;
                else if (framesDisparo > 0 && framesDisparo <= 10) indexMarco = 0;
                
                let scaleW = 250; let aspectArma = frameWidth / imgManos.height;
                let scaleH = scaleW / aspectArma; let drawX = 200 - scaleW/2;
                let offsetY = 50; let drawY = 400 - scaleH + offsetY; 

                ctx.drawImage(imgManos, indexMarco * frameWidth, 0, frameWidth, imgManos.height, drawX, drawY, scaleW, scaleH);
            } else {
                if(framesDisparo>0){ let esc=100-framesDisparo*5; ctx.font=`${esc}px Arial`; ctx.fillText("🍞",200,350-esc/2); } 
                else { ctx.font="60px Arial"; ctx.fillText("🤲",200,380); }
            }
        }

        function dispararPan() {
            playRetroSound('shoot'); framesDisparo = 18; let dF=zBuffer[200]||9999;
            
            npc.forEach((n, index) => {
                if(!n.fed){
                    let dx=n.x-dPlayer.x, dy=n.y-dPlayer.y, dist=Math.sqrt(dx*dx+dy*dy), ang=Math.atan2(dy,dx)-dPlayer.a;
                    while(ang<-Math.PI) ang+=Math.PI*2; while(ang>Math.PI) ang-=Math.PI*2;
                    
                    if(Math.abs(ang)<0.15 && dist<200 && dist<dF){
                        n.fed=true; n.timerMisericordia = 20; score++; playRetroSound('score'); actualizarScoreUI();
                        
                        if(score >= metaDoom) {
                            setTimeout(() => { playRetroSound('levelup'); transicionDoom = 30; }, 800);
                        } else {
                            setTimeout(() => {
                                npc = npc.filter(npcToKeep => npcToKeep !== n);
                                generarNPCsDoom();
                            }, 800); 
                        }
                    }
                }
            });
        }
    </script>
<style>
        input[type="number"]::-webkit-outer-spin-button,
        input[type="number"]::-webkit-inner-spin-button {
            -webkit-appearance: none !important;
            margin: 0 !important;
        }
    </style>

   <script>
    document.addEventListener("DOMContentLoaded", function() {
        
        // Aquí guardaremos las instrucciones para reiniciar cada módulo
        const funcionesReinicio = [];

        function aplicarCargarMas(idContenedor, claseItem, idBoton, itemsPorClic) {
            const contenedor = document.getElementById(idContenedor);
            if (!contenedor) return; 

            const boton = document.getElementById(idBoton);
            if (!boton) return;

            let itemsVisibles = itemsPorClic;

            // Esta es la magia: Una función que "enrolla" la lista de nuevo
            function resetearVista() {
                const items = contenedor.getElementsByClassName(claseItem);
                if (items.length === 0) return;
                
                itemsVisibles = itemsPorClic; // Volvemos al límite inicial
                
                // Mostrar solo la primera fila y ocultar el resto
                for (let i = 0; i < items.length; i++) {
                    if (i < itemsPorClic) {
                        items[i].style.display = ''; // Visible
                    } else {
                        items[i].style.display = 'none'; // Oculto
                    }
                }

                // Encender el botón si hay más elementos que el límite
                if (items.length > itemsPorClic) {
                    boton.style.display = 'inline-block';
                } else {
                    boton.style.display = 'none';
                }
            }

            // 1. Lo ejecutamos por primera vez al cargar la página
            resetearVista();

            // 2. Lo guardamos en nuestra lista de reinicios
            funcionesReinicio.push(resetearVista);

            // 3. Qué pasa al hacer clic en "Ver más"
            boton.addEventListener('click', function() {
                const items = contenedor.getElementsByClassName(claseItem);
                let mostradosEnEsteClic = 0;
                
                for (let i = itemsVisibles; i < items.length; i++) {
                    items[i].style.display = ''; 
                    mostradosEnEsteClic++;
                    itemsVisibles++;
                    if (mostradosEnEsteClic === itemsPorClic) break; 
                }

                if (itemsVisibles >= items.length) {
                    boton.style.display = 'none';
                }
            });
        }

        // --- APLICAMOS A NUESTROS MÓDULOS (3 tarjetas = 1 fila exacta) ---
        aplicarCargarMas('lista-formacion', 'item-formacion', 'btn-cargar-mas-formacion', 3);
        aplicarCargarMas('contenedor-tarjetas-donaciones', 'item-donacion', 'btn-cargar-mas-donaciones', 3);
        
        // (Opcional: Si quieres que el historial del panel derecho también funcione, son 5 por fila)
        aplicarCargarMas('lista-actividades-panel', 'item-actividad', 'btn-cargar-mas-actividad', 5);

        // --- EL TRUCO PARA REINICIAR AL CAMBIAR DE PESTAÑA ---
        const botonesMenuLateral = document.querySelectorAll('.item-menu');
        botonesMenuLateral.forEach(botonMenu => {
            botonMenu.addEventListener('click', function() {
                // Al hacer clic en cualquier parte del menú, reiniciamos todo en secreto
                funcionesReinicio.forEach(reset => reset());
            });
        });

    });
    </script>

    <script>
let modoExportacion = false;

function toggleModoExportacion() {
    modoExportacion = !modoExportacion;
    const panel = document.getElementById('panel-exportacion');
    const colsCheck = document.querySelectorAll('.col-check');
    const thCheck = document.getElementById('th-checkboxes'); // Atrapamos la cabecera
    const btn = document.getElementById('btn-modo-exportacion');

    if (modoExportacion) {
        panel.style.display = 'flex';
        
        // ¡LA MAGIA AQUÍ! Al dejarlo vacío, respetamos el Flexbox/Grid de tu plantilla
        colsCheck.forEach(col => col.style.display = '');
        if (thCheck) thCheck.style.display = ''; 
        
        btn.innerHTML = '<i class="fas fa-times"></i> Cancelar Selección';
        btn.style.background = 'var(--acento-rojo)';
        btn.style.borderColor = 'var(--acento-rojo)';
    } else {
        panel.style.display = 'none';
        
        // Volvemos a ocultarlos
        colsCheck.forEach(col => col.style.display = 'none');
        if (thCheck) thCheck.style.display = 'none'; 
        
        btn.innerHTML = '<i class="fas fa-tasks"></i> Selección Múltiple';
        btn.style.background = '';
        btn.style.borderColor = '';
        document.getElementById('check-todos').checked = false;
        document.querySelectorAll('.check-doc').forEach(c => c.checked = false);
        actualizarContador();
    }
}

function toggleAllChecks(master) {
    const checks = document.querySelectorAll('.check-doc');
    checks.forEach(c => {
        // Seleccionamos solo los que pertenecen a la carpeta que estás viendo (los que no están ocultos)
        if (c.closest('tr').style.display !== 'none') {
            c.checked = master.checked;
        }
    });
    actualizarContador();
}

function actualizarContador() {
    const seleccionados = document.querySelectorAll('.check-doc:checked').length;
    document.getElementById('contador-seleccionados').innerText = seleccionados + ' seleccionados';
}

async function descargarLote() {
    const checks = document.querySelectorAll('.check-doc:checked');
    if (checks.length === 0) {
        alert("Por favor, seleccione al menos un documento.");
        return;
    }

    // Leemos qué formato de bautismo eligió la persona
    const formatoBautismo = document.getElementById('formato-lote-bautismo').value;

    alert(`Se iniciará la descarga de ${checks.length} documentos.\n\nIMPORTANTE:\n1. Si el navegador muestra un aviso de "Intentando descargar múltiples archivos", dele a "Permitir".\n2. Si configuró su navegador, se guardarán automáticamente en su carpeta de Descargas.\n\nPor favor, espere a que terminen de bajar todos.`);

    for (let i = 0; i < checks.length; i++) {
        let url = checks[i].getAttribute('data-url');
        
        if(url.includes('generar_bautismo_pdf')) {
            url += '&formato=' + formatoBautismo;
        }
        
        let link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', ''); 
        link.style.display = 'none';
        
        document.body.appendChild(link);
        link.click(); 
        document.body.removeChild(link);

        await new Promise(r => setTimeout(r, 2000));
    }
    
    toggleModoExportacion();
}

// Apagar el modo exportación si el usuario se sale de la carpeta
document.addEventListener("DOMContentLoaded", function() {
    const divBotonesCarpetas = document.querySelectorAll('#vista-categorias-archivos .tarjeta-boton-perfil');
    divBotonesCarpetas.forEach(btn => {
        btn.addEventListener('click', () => {
            if(modoExportacion) toggleModoExportacion();
        });
    });
});
</script>

</body>
</html>