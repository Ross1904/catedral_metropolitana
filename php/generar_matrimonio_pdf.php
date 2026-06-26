<?php
require_once '../configuracion/conexion.php'; 
require_once '../librerias/dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Error: No se especificó el documento a generar.");
}

$id_documento = $_GET['id'];

try {
    $stmt = $pdo->prepare("SELECT * FROM documentos_actas WHERE id = :id AND tipo_documento = 'Matrimonio'");
    $stmt->bindParam(':id', $id_documento);
    $stmt->execute();
    $acta = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$acta) {
        die("Error: El documento no existe o no es un acta de Matrimonio.");
    }

    $datos = json_decode($acta['datos_json'], true);
    $meses = ["01"=>"Enero", "02"=>"Febrero", "03"=>"Marzo", "04"=>"Abril", "05"=>"Mayo", "06"=>"Junio", "07"=>"Julio", "08"=>"Agosto", "09"=>"Septiembre", "10"=>"Octubre", "11"=>"Noviembre", "12"=>"Diciembre"];
    $f_sac = strtotime($acta['fecha_sacramento']);
    $dia_sac = date('d', $f_sac);
    $mes_sac = $meses[date('m', $f_sac)];
    $ano_sac = date('Y', $f_sac);

    $dia_hoy = date('d');
    $mes_hoy = $meses[date('m')];
    $ano_hoy = date('Y');

    /* DATOS DEL ESPOSO */
    $nombre_esposo = htmlspecialchars($datos['nombre_esposo'] ?? $acta['nombre_principal']);
    $estado_civil_esposo = htmlspecialchars($datos['estado_civil_esposo'] ?? 'Soltero');
    $viudo_de_esposo = htmlspecialchars($datos['viudo_de_esposo'] ?? 'N/A');
    $padre_esposo = htmlspecialchars($datos['padre_esposo'] ?? '');
    $madre_esposo = htmlspecialchars($datos['madre_esposo'] ?? '');
    $natural_de_esposo = htmlspecialchars($datos['natural_esposo'] ?? '');
    $edad_esposo = htmlspecialchars($datos['edad_esposo'] ?? '');

    /* DATOS DE LA ESPOSA */
    $nombre_esposa = htmlspecialchars($datos['nombre_esposa'] ?? '');
    $estado_civil_esposa = htmlspecialchars($datos['estado_civil_esposa'] ?? 'Soltera');
    $viuda_de_esposa = htmlspecialchars($datos['viuda_de_esposa'] ?? 'N/A');
    $padre_esposa = htmlspecialchars($datos['padre_esposa'] ?? '');
    $madre_esposa = htmlspecialchars($datos['madre_esposa'] ?? '');
    $natural_de_esposa = htmlspecialchars($datos['natural_esposa'] ?? '');
    $edad_esposa = htmlspecialchars($datos['edad_esposa'] ?? '');

    /* DATOS GENERALES */
    $testigos = htmlspecialchars($datos['testigos'] ?? '');
    $ministro = htmlspecialchars($datos['ministro'] ?? '');
    $notas_marginales = htmlspecialchars($datos['notas_marginales'] ?? '');

    $str_estado_esposo = ($estado_civil_esposo === 'Viudo') ? "Viudo de: <span class='linea' style='min-width: 150px;'>$viudo_de_esposo</span>" : "$estado_civil_esposo,";
    $str_estado_esposa = ($estado_civil_esposa === 'Viuda') ? "Viuda de: <span class='linea' style='min-width: 150px;'>$viuda_de_esposa</span>" : "$estado_civil_esposa,";

    $ruta_icono = '../recursos/img/church.png';
    $icono_base64 = '';
    if(file_exists($ruta_icono)){
        $icono_base64 = 'data:image/png;base64,' . base64_encode(file_get_contents($ruta_icono));
    }

    /* BUFFER HTML */
    ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>Certificado de Matrimonio - <?php echo $nombre_esposo; ?> y <?php echo $nombre_esposa; ?></title>
        <style>
            body { font-family: "Times New Roman", Times, serif; color: #000; margin: 25px 40px; font-size: 13px; }
            .centro { text-align: center; }
            .negrita { font-weight: bold; }
            .linea { border-bottom: 1px solid #000; display: inline-block; padding: 0 5px; }
            .cuerpo-exterior { text-align: justify; line-height: 1.6; margin-top: 15px; }
            .datos-libro { text-align: right; font-size: 13px; margin-bottom: 10px; }
            .tabla-firmas { width: 100%; margin-top: 15px; text-align: center; }
            .autenticacion { margin-top: 15px; border-top: 1px dashed #000; padding-top: 10px; font-size: 13px; line-height: 1.5; }
            h1 { font-size: 20px !important; margin: 10px 0 5px 0 !important; }
            h2 { font-size: 18px !important; margin: 0 !important; }
        </style>
    </head>
    <body>
        <div style="position: absolute; top: -10px; left: 0;">Nro. <strong><?php echo htmlspecialchars($acta['numero']); ?></strong></div>

        <div class="centro">
            <?php if($icono_base64): ?>
            <img src="<?php echo $icono_base64; ?>" style="width: 45px; margin-bottom: 10px;" />
            <?php endif; ?>
            <h2>Arquidiócesis de Ciudad Bolívar</h2>
            <h4 style="margin:0; font-size: 14px;">VENEZUELA</h4>
            
            <h1 style="text-decoration: underline;">CERTIFICADO DE MATRIMONIO</h1>
            <div style="font-size: 13px;">
                En la Parroquia: <strong>CATEDRAL</strong> &nbsp;&nbsp; Lugar: <strong>CD. BOLÍVAR</strong>, Municipio: <strong>ANG. DEL ORINOCO</strong>
            </div>
        </div>

        <div class="datos-libro">
            En el Libro: <span class="linea"><?php echo htmlspecialchars($acta['libro']); ?></span> &nbsp;
            Folio: <span class="linea"><?php echo htmlspecialchars($acta['folio']); ?></span> &nbsp;
            Nro.: <span class="linea"><?php echo htmlspecialchars($acta['numero']); ?></span>
        </div>

        <div class="cuerpo-exterior">
            Se encuentra asentada una Partida de Matrimonio que dice:<br>
            El día: <span class="linea" style="min-width: 40px; text-align:center;"><?php echo $dia_sac; ?></span> 
            del mes de: <span class="linea" style="min-width: 120px; text-align:center;"><?php echo $mes_sac; ?></span> 
            del año: <span class="linea" style="min-width: 60px; text-align:center;"><?php echo $ano_sac; ?></span><br>
            
            Contrajo Matrimonio: <span class="linea negrita" style="min-width: 450px; text-transform: uppercase;"><?php echo $nombre_esposo; ?></span><br>
            
            <?php echo $str_estado_esposo; ?> 
            hijo de <span class="linea" style="min-width: 250px;"><?php echo $padre_esposo; ?></span> 
            y de <span class="linea" style="min-width: 250px;"><?php echo $madre_esposo; ?></span><br>
            
            Natural de: <span class="linea" style="min-width: 200px;"><?php echo $natural_de_esposo; ?></span> 
            de <span class="linea" style="min-width: 40px; text-align:center;"><?php echo $edad_esposo; ?></span> años de edad.<br>
            
            con: <span class="linea negrita" style="min-width: 500px; text-transform: uppercase;"><?php echo $nombre_esposa; ?></span><br>
            
            <?php echo $str_estado_esposa; ?> 
            hija de <span class="linea" style="min-width: 250px;"><?php echo $padre_esposa; ?></span> 
            y de <span class="linea" style="min-width: 250px;"><?php echo $madre_esposa; ?></span><br>
            
            Natural de: <span class="linea" style="min-width: 200px;"><?php echo $natural_de_esposa; ?></span> 
            de <span class="linea" style="min-width: 40px; text-align:center;"><?php echo $edad_esposa; ?></span> años de edad.<br>

            Testigos: <span class="linea" style="min-width: 450px;"><?php echo $testigos; ?></span><br>
            Ministro: <span class="linea" style="min-width: 450px;"><?php echo $ministro; ?></span>
        </div>

        <div style="margin-top: 15px; font-size: 13px; line-height: 1.5;">
            <div style="margin-bottom: 25px;">Se expide el presente certificado para fines: <span class="linea" style="min-width: 380px;"></span></div>
            <div style="margin-bottom: 25px;">Notas Marginales: <span class="linea" style="min-width: 650px;"><?php echo $notas_marginales; ?></span></div>
            <div>
                El día: <span class="linea" style="width: 40px; text-align:center;"><?php echo $dia_hoy; ?></span> 
                de <span class="linea" style="width: 120px; text-align:center;"><?php echo $mes_hoy; ?></span> 
                del año: <span class="linea" style="width: 60px; text-align:center;"><?php echo $ano_hoy; ?></span>
            </div>
        </div>

        <table class="tabla-firmas">
            <tr>
                <td style="width: 50%; height: 70px; vertical-align: bottom;">
                    <div style="width: 80px; height: 80px; border: 1px dotted #ccc; margin: 0 auto; display: flex; align-items: center; justify-content: center; color: #999;">Sello</div>
                </td>
                <td style="width: 50%; vertical-align: bottom;">
                    _________________________________<br>
                    <strong>El Párroco</strong>
                </td>
            </tr>
        </table>

        <div class="autenticacion">
            <div class="centro negrita" style="margin-bottom: 10px;">Para otras Diócesis<br>AUTENTIFICACION</div>
            <div style="margin-bottom: 15px;">Yo, <span class="linea" style="min-width: 550px;"></span> Canciller de la Arquidiócesis de Cd. Bolívar</div>
            <div style="margin-bottom: 15px;">Certifico y reconozco la firma del <span class="linea" style="min-width: 450px;"></span></div>
            <div style="margin-bottom: 15px;">Que para la fecha ocupa el cargo de: <span class="linea" style="min-width: 400px;"></span></div>
            <div style="margin-bottom: 15px;">De la Parroquia: <span class="linea" style="min-width: 450px;"></span></div>

            <table style="width: 100%; text-align: center; margin-top: 10px;">
                <tr>
                    <td style="text-align: left;">
                        Fecha: <span class="linea" style="width: 40px;"></span> 
                        de <span class="linea" style="width: 100px;"></span> 
                        de 20<span class="linea" style="width: 40px;"></span>
                    </td>
                    <td>
                        _________________________________<br>Firma
                    </td>
                </tr>
            </table>
        </div>
    </body>
    </html>
    <?php


    $html = ob_get_clean();

    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true); 
    $dompdf = new Dompdf($options);

    $dompdf->loadHtml($html);
    $dompdf->setPaper('letter', 'portrait');
    $dompdf->render();
    
    $nombre_descarga = "Matrimonio_" . str_replace(' ', '_', $nombre_esposo) . "_y_" . str_replace(' ', '_', $nombre_esposa) . ".pdf";
    $dompdf->stream($nombre_descarga, array("Attachment" => false));

} catch (Exception $e) {
    if (ob_get_length()) {
        ob_end_clean();
    }
    die("Error crítico al generar el PDF de Matrimonio: " . $e->getMessage());
}
?>