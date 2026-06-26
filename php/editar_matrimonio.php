<?php
require_once '../configuracion/conexion.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $id_documento = $_POST['id_documento'] ?? '';
    
    if (empty($id_documento)) {
        die("Error crítico: No se recibió el identificador del acta.");
    }

    $nombre_esposo = trim($_POST['nombre_esposo'] ?? '');
    $nombre_esposa = trim($_POST['nombre_esposa'] ?? '');
    $nombre_registro = $nombre_esposo . " Y " . $nombre_esposa;

    $fecha_sacramento = $_POST['fecha_matrimonio'];
    $libro = $_POST['libro_num'];
    $folio = $_POST['folio_num'];
    $numero = $_POST['acta_num'];
    $id_usuario = $_SESSION['usuario_id'] ?? 1;

    $datos_json = json_encode([
        "nombre_esposo" => $nombre_esposo,
        "estado_civil_esposo" => $_POST['estado_civil_esposo'] ?? 'Soltero',
        "edad_esposo" => $_POST['edad_esposo'] ?? '',
        "viudo_de_esposo" => $_POST['viudo_de_esposo'] ?? '',
        "natural_esposo" => $_POST['natural_esposo'] ?? '',
        "padre_esposo" => $_POST['padre_esposo'] ?? '',
        "madre_esposo" => $_POST['madre_esposo'] ?? '',
        
        "nombre_esposa" => $nombre_esposa,
        "estado_civil_esposa" => $_POST['estado_civil_esposa'] ?? 'Soltera',
        "edad_esposa" => $_POST['edad_esposa'] ?? '',
        "viuda_de_esposa" => $_POST['viuda_de_esposa'] ?? '',
        "natural_esposa" => $_POST['natural_esposa'] ?? '',
        "padre_esposa" => $_POST['padre_esposa'] ?? '',
        "madre_esposa" => $_POST['madre_esposa'] ?? '',
        
        "testigos" => $_POST['testigos'] ?? '',
        "ministro" => $_POST['ministro'] ?? ''
    ], JSON_UNESCAPED_UNICODE);

    try {
        $sql = "UPDATE documentos_actas SET 
                nombre_principal = :nombre, 
                fecha_sacramento = :fecha_sac, 
                libro = :libro, 
                folio = :folio, 
                numero = :numero, 
                datos_json = :json 
                WHERE id = :id AND tipo_documento = 'Matrimonio'";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nombre' => $nombre_registro,
            ':fecha_sac' => $fecha_sacramento,
            ':libro' => $libro,
            ':folio' => $folio,
            ':numero' => $numero,
            ':json' => $datos_json,
            ':id' => $id_documento
        ]);
        
        /* Registro de notificaciones */
        try {
            $sql_actividad = "INSERT INTO registro_actividad (id_usuario, accion, modulo, fecha_accion, visto) VALUES (?, ?, 'Archivos', NOW(), 0)";
            $stmt_actividad = $pdo->prepare($sql_actividad);
            $stmt_actividad->execute([$id_usuario, 'Editó y actualizó la Partida de Matrimonio de: ' . $nombre_registro]);
        } catch(PDOException $e) {}

        header("Location: ../vistas/inicio.php?editado=exito");
        exit();

    } catch(PDOException $e) {
        die("Error al actualizar Matrimonio: " . $e->getMessage());
    }
} else {
    header("Location: ../vistas/inicio.php");
    exit();
}
?>