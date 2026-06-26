<?php
require_once '../configuracion/conexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if(isset($_GET['id'])) {
    $id = $_GET['id'];
    $id_usuario = $_SESSION['usuario_id'] ?? 1;
    
    try {

        $stmtSelect = $pdo->prepare("SELECT tipo_documento, nombre_principal FROM documentos_actas WHERE id = :id");
        $stmtSelect->bindParam(':id', $id, PDO::PARAM_INT);
        $stmtSelect->execute();
        $documento = $stmtSelect->fetch(PDO::FETCH_ASSOC);

        if($documento) {
            $nombre_doc = $documento['nombre_principal'];
            $tipo_doc = $documento['tipo_documento'];


            $stmt = $pdo->prepare("DELETE FROM documentos_actas WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            /*SENSOR DE NOTIFICACIONES*/
            try {
                $modulo_actividad = 'Archivos';
                $detalle_actividad = "Eliminó un acta de $tipo_doc perteneciente a: $nombre_doc";
                
                $sql_actividad = "INSERT INTO registro_actividad (id_usuario, accion, modulo, fecha_accion, visto) VALUES (:id_usr, :accion, :modulo, NOW(), 0)";
                $stmt_actividad = $pdo->prepare($sql_actividad);
                $stmt_actividad->execute([
                    ':id_usr' => $id_usuario,
                    ':accion' => $detalle_actividad,
                    ':modulo' => $modulo_actividad
                ]);
            } catch(PDOException $e) {
            }
        }

        header("Location: ../vistas/inicio.php?mensaje=eliminado_exito");
        exit();

    } catch(PDOException $e) {
        die("Error crítico al intentar eliminar: " . $e->getMessage());
    }
} else {
    header("Location: ../vistas/inicio.php");
    exit();
}
?>