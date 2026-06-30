<?php
require_once '../configuracion/conexion.php'; 
require_once '../librerias/dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Error: No se especificó el documento a generar.");
}

$id_documento = $_GET['id'];
$formato = isset($_GET['formato']) ? $_GET['formato'] : 'nacional'; 

try {
    $stmt = $pdo->prepare("SELECT * FROM documentos_actas WHERE id = :id AND tipo_documento = 'Bautismo'");
    $stmt->bindParam(':id', $id_documento);
    $stmt->execute();
    $acta = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$acta) {
        die("Error: El documento no existe.");
    }

    $datos = json_decode($acta['datos_json'], true);

    /* FORMATO DE FECHAS */
    $meses = ["01"=>"Enero", "02"=>"Febrero", "03"=>"Marzo", "04"=>"Abril", "05"=>"Mayo", "06"=>"Junio", "07"=>"Julio", "08"=>"Agosto", "09"=>"Septiembre", "10"=>"Octubre", "11"=>"Noviembre", "12"=>"Diciembre"];
    
    $f_sac = strtotime($acta['fecha_sacramento']);
    $dia_sac = date('d', $f_sac);
    $mes_sac = $meses[date('m', $f_sac)];
    $ano_sac = date('Y', $f_sac);


    $f_nac = strtotime($datos['fecha_nacimiento']);
    $dia_nac = date('d', $f_nac);
    $mes_nac = $meses[date('m', $f_nac)];
    $ano_nac = date('Y', $f_nac);


    $dia_hoy = date('d');
    $mes_hoy = $meses[date('m')];
    $ano_hoy = date('Y');

    $ciudad_nac = isset($datos['ciudad_nacimiento']) && !empty($datos['ciudad_nacimiento']) ? htmlspecialchars($datos['ciudad_nacimiento']) : (isset($datos['lugar_nacimiento']) ? htmlspecialchars($datos['lugar_nacimiento']) : '');
    $estado_nac = isset($datos['estado_nacimiento']) ? htmlspecialchars($datos['estado_nacimiento']) : '';

    $ruta_icono = '../recursos/img/church.png';
    $icono_base64 = 'data:image/png;base64,' . base64_encode(file_get_contents($ruta_icono));

    /* Estilos globales */
    $css_global = '
        <style>
            body { font-family: "Times New Roman", Times, serif; color: #000; margin: 25px 40px; }
            .centro { text-align: center; }
            .negrita { font-weight: bold; }
            .linea { border-bottom: 1px solid #000; display: inline-block; padding: 0 5px; }
            .marca-agua { position: absolute; top: 25%; left: 15%; width: 70%; opacity: 0.1; z-index: -1000; }
        </style>
    ';

    $html = '';


    /*FORMATO NACIONAL*/

    if ($formato === 'nacional') {
        $html = '
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <title>Constancia de Bautismo - ' . htmlspecialchars($acta['nombre_principal']) . '</title>
            ' . $css_global . '
            <style>
                .cuerpo-nacional { font-size: 17px; line-height: 2.2; margin-top: 20px; }
                .firma-nacional { margin-top: 60px; text-align: center; font-size: 18px; }
            </style>
        </head>
        <body>
            <div class="centro">
                <img src="' . $icono_base64 . '" style="width: 45px; margin-bottom: 5px;" />

                <h2 style="margin:0; font-size: 22px;">Arquidiócesis de Ciudad Bolívar</h2>
                <h3 style="margin:3px 0; font-size: 18px;">Parroquia Catedral</h3>
                <h4 style="margin:0; font-size: 16px;">VENEZUELA</h4>
                
                <h1 style="margin-top: 25px; text-decoration: underline; font-size: 22px;">CONSTANCIA DE BAUTISMO</h1>
            </div>

            <div class="cuerpo-nacional">
                En la Parroquia Catedral de Ciudad Bolívar, fue Bautizado (a):<br>
                <div class="centro negrita" style="font-size: 22px; text-transform: uppercase; margin: 20px 0;">
                    ' . htmlspecialchars($acta['nombre_principal']) . '
                </div>
                
                <strong>Fecha de Bautismo:</strong> <span class="linea" style="min-width: 300px;">' . $dia_sac . ' de ' . $mes_sac . ' de ' . $ano_sac . '</span><br>
                <strong>Nombre del Padre:</strong> <span class="linea" style="min-width: 320px;">' . htmlspecialchars($datos['nombre_padre']) . '</span><br>
                <strong>Nombre de la Madre:</strong> <span class="linea" style="min-width: 310px;">' . htmlspecialchars($datos['nombre_madre']) . '</span><br>
                <strong>Padrino:</strong> <span class="linea" style="min-width: 420px;">' . htmlspecialchars($datos['nombre_padrino']) . '</span><br>
                <strong>Madrina:</strong> <span class="linea" style="min-width: 415px;">' . htmlspecialchars($datos['nombre_madrina']) . '</span><br>
            </div>

            <div style="margin-top: 50px; font-size: 16px; text-align: justify; line-height: 1.5;">
                Se expide la presente Constancia como requisito para prepararse para la Primera Comunión y la Confirmación.
            </div>

            <div style="margin-top: 30px; font-size: 18px;">
                Ciudad Bolívar <span class="linea" style="width: 40px; text-align: center;">'.$dia_hoy.'</span> 
                de <span class="linea" style="width: 120px; text-align: center;">'.$mes_hoy.'</span> 
                de <span class="linea" style="width: 60px; text-align: center;">'.$ano_hoy.'</span>
            </div>

            <div class="firma-nacional">
                _________________________________________<br>
                <strong>Pbro. ANTONIO VALLADARES</strong><br>
                Párroco
            </div>
        </body>
        </html>';
    } 

    /* FORMATO EXTERIOR */

    else {
        $html = '
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <title>Certificado de Bautismo Exterior - ' . htmlspecialchars($acta['nombre_principal']) . '</title>
            ' . $css_global . '
            <style>
                body { font-size: 13px; }
                .cuerpo-exterior { text-align: justify; line-height: 1.6; margin-top: 15px; }
                .datos-libro { text-align: right; font-size: 13px; margin-bottom: 10px; }
                .tabla-firmas { width: 100%; margin-top: 15px; text-align: center; }
                .autenticacion { margin-top: 15px; border-top: 1px dashed #000; padding-top: 10px; font-size: 13px; line-height: 1.5; }
                h1 { font-size: 20px !important; margin: 10px 0 5px 0 !important; }
                h2 { font-size: 18px !important; margin: 0 !important; }
            </style>
        </head>
        <body>
            <div style="position: absolute; top: -10px; left: 0;">Nro. <strong>'. htmlspecialchars($acta['numero']) .'</strong></div>

            <div class="centro">
                <img src="' . $icono_base64 . '" style="width: 45px; margin-bottom: 10px;" />
                <h2>Arquidiócesis de Ciudad Bolívar</h2>
                <h4 style="margin:0; font-size: 14px;">VENEZUELA</h4>
                
                <h1 style="text-decoration: underline;">CERTIFICADO DE BAUTISMO</h1>
                <div style="font-size: 13px;">
                    En la Parroquia: <strong>CATEDRAL</strong> &nbsp;&nbsp; Lugar: <strong>CD. BOLÍVAR</strong>, Municipio: <strong>ANG. DEL ORINOCO</strong>
                </div>
            </div>

            <div class="datos-libro">
                Libro: <span class="linea">'. htmlspecialchars($acta['libro']) .'</span> &nbsp;
                Folio: <span class="linea">'. htmlspecialchars($acta['folio']) .'</span> &nbsp;
                Nro.: <span class="linea">'. htmlspecialchars($acta['numero']) .'</span>
            </div>

            <div class="cuerpo-exterior">
                Se encuentra asentada una Partida de BAUTISMO que dice:<br>
                El día: <span class="linea" style="min-width: 40px; text-align:center;">'. $dia_sac .'</span> 
                del mes de: <span class="linea" style="min-width: 120px; text-align:center;">'. $mes_sac .'</span> 
                del año: <span class="linea" style="min-width: 60px; text-align:center;">'. $ano_sac .'</span><br>
                
                Fue Bautizado(a) <span class="linea negrita" style="min-width: 450px; text-transform: uppercase;">'. htmlspecialchars($acta['nombre_principal']) .'</span><br>
                
                Hijo(a) de <span class="linea" style="min-width: 250px;">'. htmlspecialchars($datos['nombre_padre']) .'</span> 
                y de <span class="linea" style="min-width: 250px;">'. htmlspecialchars($datos['nombre_madre']) .'</span><br>
                
                Que nació en <span class="linea" style="min-width: 250px;">'. $ciudad_nac .'</span> 
                Estado: <span class="linea" style="min-width: 150px;">'. $estado_nac .'</span><br>
                
                El día <span class="linea" style="min-width: 40px; text-align:center;">'. $dia_nac .'</span> 
                del mes de <span class="linea" style="min-width: 120px; text-align:center;">'. $mes_nac .'</span> 
                del año: <span class="linea" style="min-width: 60px; text-align:center;">'. $ano_nac .'</span><br>

                Padrino: <span class="linea" style="min-width: 300px;">'. htmlspecialchars($datos['nombre_padrino']) .'</span><br>
                Madrina: <span class="linea" style="min-width: 300px;">'. htmlspecialchars($datos['nombre_madrina']) .'</span><br>
                Ministro: <span class="linea" style="min-width: 300px;">'. htmlspecialchars($datos['ministro']) .'</span>
            </div>

            <div style="margin-top: 15px; font-size: 13px; line-height: 1.5;">
                <div style="margin-bottom: 25px;">Se expide el presente certificado para fines: <span class="linea" style="min-width: 380px;"></span></div>
                <div style="margin-bottom: 25px;">Notas Marginales: <span class="linea" style="min-width: 650px;"></span></div>
                <div>
                    El día: <span class="linea" style="width: 40px; text-align:center;">'.$dia_hoy.'</span> 
                    de <span class="linea" style="width: 120px; text-align:center;">'.$mes_hoy.'</span> 
                    del año <span class="linea" style="width: 60px; text-align:center;">'.$ano_hoy.'</span>
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
                <div class="centro negrita" style="margin-bottom: 15px;">Para otras Diócesis<br>AUTENTIFICACION</div>
                <div style="margin-bottom: 20px;">Yo, <span class="linea" style="min-width: 550px;"></span> Canciller de la Arquidiócesis de Cd. Bolívar</div>
                <div style="margin-bottom: 20px;">Certifico y reconozco la firma del <span class="linea" style="min-width: 450px;"></span></div>
                <div style="margin-bottom: 20px;">Que para la fecha ocupa el cargo de: <span class="linea" style="min-width: 400px;"></span></div>
                <div style="margin-bottom: 20px;">De la Parroquia: <span class="linea" style="min-width: 450px;"></span></div>

                <table style="width: 100%; text-align: center; margin-top: 15px;">
                    <tr>
                        <td style="text-align: left;">
                            Fecha: <span class="linea" style="width: 40px;"></span> 
                            de <span class="linea" style="width: 100px;"></span> 
                            Año: <span class="linea" style="width: 60px;"></span>
                        </td>
                        <td>
                            _________________________________<br>Firma
                        </td>
                    </tr>
                </table>
            </div>
        </body>
        </html>';
    }

    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true); 
    $dompdf = new Dompdf($options);

    $dompdf->loadHtml($html);
    $dompdf->setPaper('letter', 'portrait');
    $dompdf->render();
    
    $nombre_descarga = "Bautismo_" . ucfirst($formato) . "_" . str_replace(' ', '_', $acta['nombre_principal']) . ".pdf";
$descargar = (isset($_GET['descargar']) && $_GET['descargar'] == '1') ? true : false;
$dompdf->stream($nombre_descarga, array("Attachment" => $descargar));
} catch(Exception $e) {
    die("Error crítico al generar el PDF: " . $e->getMessage());
}
?>