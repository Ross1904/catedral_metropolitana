<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../configuracion/conexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $tipo_documento = 'Bautismo';
    $nombre_principal = $_POST['nombre_bautizado'] ?? 'Sin Nombre';
    $fecha_sacramento = $_POST['fecha_bautismo'] ?? date('Y-m-d');
    $libro = $_POST['libro_num'] ?? '0';
    $folio = $_POST['folio_num'] ?? '0';
    $numero = $_POST['acta_num'] ?? '0';
    
    $id_usuario = $_SESSION['usuario_id'] ?? 1; 
    $ruta_archivo = '';
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
        $sql = "INSERT INTO documentos_actas 
                (tipo_documento, nombre_principal, fecha_sacramento, libro, folio, numero, datos_json, ruta_archivo, id_usuario_registro) 
                VALUES (:tipo, :nombre, :fecha_sac, :libro, :folio, :numero, :json, :ruta, :id_usr)";
        
        $stmt = $pdo->prepare($sql);
        
        $stmt->bindParam(':tipo', $tipo_documento);
        $stmt->bindParam(':nombre', $nombre_principal);
        $stmt->bindParam(':fecha_sac', $fecha_sacramento);
        $stmt->bindParam(':libro', $libro);
        $stmt->bindParam(':folio', $folio);
        $stmt->bindParam(':numero', $numero);
        $stmt->bindParam(':json', $datos_json);
        $stmt->bindParam(':ruta', $ruta_archivo);
        $stmt->bindParam(':id_usr', $id_usuario);
        
        $stmt->execute();

        //SENSOR DE NOTIFICACIONES

        try {
            $modulo_actividad = 'Archivos';
            $detalle_actividad = 'Emitió una nueva Partida de Bautismo para: ' . $nombre_principal;
            
            $sql_actividad = "INSERT INTO registro_actividad (id_usuario, accion, modulo, fecha_accion, visto) VALUES (:id_usr, :accion, :modulo, NOW(), '')";
            $stmt_actividad = $pdo->prepare($sql_actividad);
            $stmt_actividad->execute([
                ':id_usr' => $id_usuario,
                ':accion' => $detalle_actividad,
                ':modulo' => $modulo_actividad
            ]);
        } catch(PDOException $e) {
        }

        header("Location: ../vistas/inicio.php?guardado=exito");
        exit();

    } catch(PDOException $e) {
        echo "<div style='font-family: Arial; padding: 40px; background: #ffebee; border: 2px solid red; border-radius: 10px; margin: 40px;'>";
        echo "<h2 style='color: red;'>¡Alerta! La Base de Datos rechazó el documento</h2>";
        echo "<p><strong>El error exacto es:</strong> <br><code style='background: #fff; padding: 10px; display: block; font-size: 16px; margin-top: 10px;'>" . $e->getMessage() . "</code></p>";
        echo "<p><em>Posible causa: Quizás faltó ejecutar el código SQL para actualizar la tabla 'documentos_actas' añadiendo las columnas 'nombre_principal', 'numero' y 'datos_json'.</em></p>";
        echo "<a href='../vistas/inicio.php' style='padding: 10px 20px; background: red; color: white; text-decoration: none; border-radius: 5px;'>Volver a la aplicación</a>";
        echo "</div>";
        die();
    }
} else {
    echo "No estás enviando los datos por el formulario POST.";
}
?>