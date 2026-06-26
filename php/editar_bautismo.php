<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../configuracion/conexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $id_documento = $_POST['id_documento'] ?? '';
    
    if (empty($id_documento)) {
        die("Error crítico: No se recibió el identificador del acta.");
    }

    $nombre_principal = $_POST['nombre_bautizado'] ?? 'Sin Nombre';
    $fecha_sacramento = $_POST['fecha_bautismo'] ?? date('Y-m-d');
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
                WHERE id = :id AND tipo_documento = 'Bautismo'";
        
        $stmt = $pdo->prepare($sql);
        
        $stmt->bindParam(':nombre', $nombre_principal);
        $stmt->bindParam(':fecha_sac', $fecha_sacramento);
        $stmt->bindParam(':libro', $libro);
        $stmt->bindParam(':folio', $folio);
        $stmt->bindParam(':numero', $numero);
        $stmt->bindParam(':json', $datos_json);
        $stmt->bindParam(':id', $id_documento, PDO::PARAM_INT);
        
        $stmt->execute();

        /* SENSOR DE ACTIVIDAD */
        try {
            $modulo_actividad = 'Archivos';
            $detalle_actividad = 'Editó y actualizó la Partida de Bautismo de: ' . $nombre_principal;
            
            $sql_actividad = "INSERT INTO registro_actividad (id_usuario, accion, modulo, fecha_accion, visto) VALUES (:id_usr, :accion, :modulo, NOW(), 0)";
            $stmt_actividad = $pdo->prepare($sql_actividad);
            $stmt_actividad->execute([
                ':id_usr' => $id_usuario,
                ':accion' => $detalle_actividad,
                ':modulo' => $modulo_actividad
            ]);
        } catch(PDOException $e) {
        }

        header("Location: ../vistas/inicio.php?editado=exito");
        exit();

    } catch(PDOException $e) {
        
        echo "<div style='font-family: Arial; padding: 40px; background: #ffebee; border: 2px solid red; border-radius: 10px; margin: 40px;'>";
        echo "<h2 style='color: red;'>¡Alerta! Falló la actualización</h2>";
        echo "<p><strong>El error exacto es:</strong> <br><code style='background: #fff; padding: 10px; display: block; font-size: 16px; margin-top: 10px;'>" . $e->getMessage() . "</code></p>";
        echo "<a href='../vistas/inicio.php' style='padding: 10px 20px; background: red; color: white; text-decoration: none; border-radius: 5px;'>Volver a la aplicación</a>";
        echo "</div>";
        die();
    }
} else {
    header("Location: ../vistas/inicio.php");
    exit();
}
?>