<?php
require_once '../configuracion/conexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $tipo_documento = 'Confirmacion';
    $nombre_principal = $_POST['nombre_confirmado'] ?? 'Sin Nombre';
    $fecha_sacramento = $_POST['fecha_confirmacion'] ?? date('Y-m-d');
    $libro = $_POST['libro_num'] ?? '0';
    $folio = $_POST['folio_num'] ?? '0';
    $numero = $_POST['acta_num'] ?? '0';
    $id_usuario = $_SESSION['usuario_id'] ?? 1; 

    $datos_especificos = array(
        "fecha_nacimiento" => $_POST['fecha_nacimiento'] ?? '',
        "ciudad_nacimiento" => $_POST['ciudad_nacimiento'] ?? '',
        "estado_nacimiento" => $_POST['estado_nacimiento'] ?? '',
        "nombre_padre" => $_POST['nombre_padre'] ?? '',
        "nombre_madre" => $_POST['nombre_madre'] ?? '',
        "nombre_padrino" => $_POST['nombre_padrino'] ?? '',
        "nombre_madrina" => $_POST['nombre_madrina'] ?? '',
        "ministro" => $_POST['ministro'] ?? '',
        "notas_marginales" => $_POST['notas_marginales'] ?? ''
    );

    $datos_json = json_encode($datos_especificos, JSON_UNESCAPED_UNICODE);

    try {
        $sql = "INSERT INTO documentos_actas (tipo_documento, nombre_principal, fecha_sacramento, libro, folio, numero, datos_json, id_usuario_registro) 
                VALUES (:tipo, :nombre, :fecha_sac, :libro, :folio, :numero, :json, :id_usr)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':tipo' => $tipo_documento,
            ':nombre' => $nombre_principal,
            ':fecha_sac' => $fecha_sacramento,
            ':libro' => $libro,
            ':folio' => $folio,
            ':numero' => $numero,
            ':json' => $datos_json,
            ':id_usr' => $id_usuario
        ]);
        
        try {
            $modulo_actividad = 'Archivos';
            $detalle_actividad = 'Registró una nueva Partida de Confirmación a nombre de: ' . $nombre_principal;
            
            $sql_actividad = "INSERT INTO registro_actividad (id_usuario, accion, modulo, fecha_accion, visto) VALUES (:id_usr, :accion, :modulo, NOW(), 0)";
            $stmt_actividad = $pdo->prepare($sql_actividad);
            $stmt_actividad->execute([
                ':id_usr' => $id_usuario,
                ':accion' => $detalle_actividad,
                ':modulo' => $modulo_actividad
            ]);
        } catch(PDOException $e) {}

        header("Location: ../vistas/inicio.php?mensaje=guardado_exito");
        exit();

    } catch(PDOException $e) {
        die("Error de Base de Datos al guardar Confirmación: " . $e->getMessage());
    }
} else {
    header("Location: ../vistas/inicio.php");
    exit();
}
?>