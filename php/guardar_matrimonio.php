<?php
require_once '../configuracion/conexion.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $nombre_esposo = trim($_POST['nombre_esposo']);
    $nombre_esposa = trim($_POST['nombre_esposa']);
    $nombre_registro = $nombre_esposo . " Y " . $nombre_esposa;

    $fecha_sacramento = $_POST['fecha_matrimonio'];
    $libro = $_POST['libro_num'];
    $folio = $_POST['folio_num'];
    $numero = $_POST['acta_num'];
    $id_usuario = $_SESSION['usuario_id'] ?? 1;

    $datos_json = json_encode([
        "nombre_esposo" => $nombre_esposo,
        "estado_civil_esposo" => $_POST['estado_civil_esposo'],
        "edad_esposo" => $_POST['edad_esposo'],
        "viudo_de_esposo" => $_POST['viudo_de_esposo'],
        "natural_esposo" => $_POST['natural_esposo'],
        "padre_esposo" => $_POST['padre_esposo'],
        "madre_esposo" => $_POST['madre_esposo'],
        
        "nombre_esposa" => $nombre_esposa,
        "estado_civil_esposa" => $_POST['estado_civil_esposa'],
        "edad_esposa" => $_POST['edad_esposa'],
        "viuda_de_esposa" => $_POST['viuda_de_esposa'],
        "natural_esposa" => $_POST['natural_esposa'],
        "padre_esposa" => $_POST['padre_esposa'],
        "madre_esposa" => $_POST['madre_esposa'],
        
        "testigos" => $_POST['testigos'],
        "ministro" => $_POST['ministro'],
        "notas_marginales" => ""
    ], JSON_UNESCAPED_UNICODE);

    try {
        $sql = "INSERT INTO documentos_actas (tipo_documento, nombre_principal, fecha_sacramento, libro, folio, numero, datos_json, id_usuario_registro) 
                VALUES ('Matrimonio', :nombre, :fecha, :lib, :fol, :num, :json, :usr)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nombre' => $nombre_registro,
            ':fecha'  => $fecha_sacramento,
            ':lib'    => $libro,
            ':fol'    => $folio,
            ':num'    => $numero,
            ':json'   => $datos_json,
            ':usr'    => $id_usuario
        ]);

        $stmtLog = $pdo->prepare("INSERT INTO registro_actividad (id_usuario, modulo, accion, fecha_accion) VALUES (?, 'Archivos', ?, NOW())");
        $stmtLog->execute([$id_usuario, "Registró Matrimonio: $nombre_registro"]);

        header("Location: ../vistas/inicio.php?mensaje=guardado_exito");
        exit();

    } catch(PDOException $e) {
        die("Error al guardar Matrimonio: " . $e->getMessage());
    }
}