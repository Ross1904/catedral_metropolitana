<?php
require_once '../configuracion/conexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $id_documento = $_POST['id_documento'] ?? '';
    
    if (empty($id_documento)) {
        die("Error crítico: No se recibió el identificador del acta.");
    }

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
        "ministro" => $_POST['ministro'] ?? ''
    );
    
    $datos_json = json_encode($datos_especificos, JSON_UNESCAPED_UNICODE);

    try {
        $sql = "UPDATE documentos_actas SET 
                nombre_principal = :nombre, 
                fecha_sacramento = :fecha_sac, 
                libro = :libro, 
                folio = :folio, 
                numero = :numero, 
                datos_json = :json 
                WHERE id = :id AND tipo_documento = 'Confirmacion'";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nombre' => $nombre_principal,
            ':fecha_sac' => $fecha_sacramento,
            ':libro' => $libro,
            ':folio' => $folio,
            ':numero' => $numero,
            ':json' => $datos_json,
            ':id' => $id_documento
        ]);
        
        try {
            $sql_actividad = "INSERT INTO registro_actividad (id_usuario, accion, modulo, fecha_accion, visto) VALUES (?, ?, 'Archivos', NOW(), 0)";
            $stmt_actividad = $pdo->prepare($sql_actividad);
            $stmt_actividad->execute([$id_usuario, 'Editó y actualizó la Partida de Confirmación de: ' . $nombre_principal]);
        } catch(PDOException $e) {}

        header("Location: ../vistas/inicio.php?editado=exito");
        exit();

    } catch(PDOException $e) {
        die("Error al actualizar la Confirmación: " . $e->getMessage());
    }
} else {
    header("Location: ../vistas/inicio.php");
    exit();
}
?>