<?php
// Startup checks
$required = [
    __DIR__ . '/config.php' => 'Falta config.php — Copiá config.example.php a config.php',
    __DIR__ . '/vendor/tecnickcom/tcpdf/tcpdf.php' => 'Falta TCPDF — Ejecutá composer install en el servidor',
];
foreach ($required as $file => $msg) {
    if (!file_exists($file)) {
        die("<h2 style='color:#e94560;font-family:sans-serif;'>Error de instalacion</h2><p style='font-family:sans-serif;'>$msg</p>");
    }
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/api.php';
require_once __DIR__ . '/pdf_generator.php';

@set_time_limit(0);
header('Content-Type: text/html; charset=utf-8');

@ini_set('zlib.output_compression', '0');
@ini_set('output_buffering', '0');
@ini_set('implicit_flush', '1');
ob_implicit_flush(true);
while (ob_get_level() > 0) {
    ob_end_flush();
}

$tituloEbook = trim($_POST['titulo'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');
$tema = trim($_POST['tema'] ?? '');
$numCapitulos = min(max((int)($_POST['capitulos'] ?? DEFAULT_CHAPTERS), MIN_CHAPTERS), MAX_CHAPTERS);
$incluirImagenes = isset($_POST['imagenes']);
$idioma = $_POST['idioma'] ?? 'es';
$theme = $_POST['theme'] ?? 'cyberpunk';
$imgStyle = $_POST['img_style'] ?? 'geometric';

$customColors = [];
foreach (['bg','accent','accent2','text','header','bg_page'] as $k) {
    $v = $_POST["custom_{$k}"] ?? '';
    if ($v && preg_match('/^#[0-9a-f]{6}$/i', $v)) {
        $customColors[$k] = $v;
    }
}

$tituloPortada = $tituloEbook ?: $tema;
if (!$tema && $tituloEbook) $tema = $tituloEbook;

if (!$tema && !$tituloEbook) {
    die('<h2 style="color:#e94560;">Error: Debes proporcionar al menos un titulo o tema</h2>');
}
if (!$tema) $tema = $tituloEbook;

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Generando ebook - <?= htmlspecialchars($tema) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, sans-serif;
            background: linear-gradient(135deg, #0f0c29, #302b63, #24243e);
            min-height: 100vh;
            padding: 40px 20px;
            color: #eee;
        }
        .container { max-width: 700px; margin: 0 auto; }
        h2 { font-size: 22px; margin-bottom: 20px; color: #fff; }
        .progress-box {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 25px;
            min-height: 200px;
        }
        .log { font-family: 'Courier New', monospace; font-size: 14px; line-height: 1.8; }
        .log .step { color: #6c63ff; font-weight: bold; }
        .log .ok { color: #4caf50; }
        .log .info { color: #64b5f6; }
        .log .err { color: #e94560; }
        .result-box {
            display: none;
            margin-top: 25px;
            padding: 30px;
            background: rgba(76, 175, 80, 0.1);
            border: 1px solid rgba(76, 175, 80, 0.3);
            border-radius: 12px;
            text-align: center;
        }
        .result-box h3 { color: #4caf50; font-size: 20px; margin-bottom: 10px; }
        .result-box p { color: rgba(255,255,255,0.7); margin-bottom: 20px; }
        .btn-download {
            display: inline-block;
            padding: 14px 40px;
            background: linear-gradient(135deg, #6c63ff, #e94560);
            color: white; text-decoration: none; border-radius: 10px;
            font-weight: 700; font-size: 16px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn-download:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(108, 99, 255, 0.4);
        }
        .btn-back {
            display: inline-block; margin-top: 15px; padding: 10px 25px;
            background: rgba(255,255,255,0.1); color: rgba(255,255,255,0.8);
            text-decoration: none; border-radius: 8px; font-size: 14px;
        }
        .btn-back:hover { background: rgba(255,255,255,0.2); }
    </style>
</head>
<body>
<div class="container">
    <h2>Generando ebook: <span style="color:#6c63ff;"><?= htmlspecialchars($tema) ?></span></h2>
    <div class="progress-box">
        <div class="log" id="log">
<?php

echo "            <div><span class='step'>>></span> Iniciando generacion...</div>\n";
flush();

// ============ PASO 1: INDICE ============
try {
    $idiomaMap = ['es' => 'espanol', 'en' => 'english', 'pt' => 'portuguese'];
    $idiomaPrompt = $idiomaMap[$idioma] ?? 'espanol';

    echo "            <div><span class='step'>>> Paso 1/3:</span> Generando indice (".$numCapitulos." capitulos)...</div>\n";
    flush();

    $tocDesc = $descripcion ? " El libro trata sobre: {$descripcion}." : '';
    $tocPrompt = "Eres un escritor experto. Crea un indice detallado para un ebook titulado \"{$tituloPortada}\" sobre {$tema}.{$tocDesc} "
        . "El ebook debe tener exactamente {$numCapitulos} capitulos. "
        . "Devuelve SOLO los titulos de los capitulos, uno por linea, numerados del 1 al {$numCapitulos}. "
        . "Escribe en {$idiomaPrompt}.";

    echo "            <div><span class='muted'>> Conectando con API...</span></div>\n";
    flush();
    $tocResponse = API::generateText($tocPrompt);

    $capitulos = [];
    $lines = explode("\n", trim($tocResponse));
    foreach ($lines as $line) {
        $line = trim($line);
        if (preg_match('/^\d+[\.\)]\s*(.+)/', $line, $m)) {
            $capitulos[] = trim($m[1]);
        } elseif (!empty($line) && !str_starts_with($line, '<') && strlen($line) > 3) {
            $capitulos[] = $line;
        }
    }
    $capitulos = array_slice($capitulos, 0, $numCapitulos);

    if (count($capitulos) < 1) {
        $capitulos = [];
        for ($i = 1; $i <= $numCapitulos; $i++) {
            $capitulos[] = "{$tema} - Capitulo {$i}";
        }
    }

    echo "            <div><span class='ok'>".chr(10003)."</span> Indice: ".count($capitulos)." capitulos</div>\n";
    foreach ($capitulos as $i => $c) {
        echo "            <div><span class='info'>  " . ($i+1) . ".</span> " . htmlspecialchars($c) . "</div>\n";
    }
    flush();

} catch (Exception $e) {
    echo "            <div><span class='err'>Error: " . htmlspecialchars($e->getMessage()) . "</span></div>\n";
    flush();
    exit;
}

// ============ PASO 2: CONTENIDO ============
try {
    echo "            <div><span class='step'>>> Paso 2/3:</span> Generando contenido...</div>\n";
    flush();

    $contenido = [];
    foreach ($capitulos as $i => $titulo) {
        $tituloClean = trim($titulo);
        echo "            <div><span class='step'>>> </span> Capitulo " . ($i+1) . "/" . count($capitulos) . ": " . htmlspecialchars($tituloClean) . "</div>\n";
        flush();

        $chapDesc = $descripcion ? " El libro trata sobre: {$descripcion}." : '';
        $textPrompt = "Eres un escritor experto especializado en \"{$tema}\". "
            . "Escribe el contenido completo del capitulo titulado \"{$tituloClean}\" del ebook \"{$tituloPortada}\" sobre {$tema}.{$chapDesc} "
            . "El contenido debe ser educativo, detallado y de aproximadamente " . WORDS_PER_CHAPTER . " palabras. "
            . "Escribe en {$idiomaPrompt} con parrafos bien estructurados. "
            . "NO incluyas el titulo del capitulo en la respuesta, solo el contenido.\n\n"
            . "IMPORTANTE: Al final de tu respuesta, agrega '[IMAGE_PROMPT]' seguido de un prompt detallado de maximo 3 oraciones en {$idiomaPrompt} "
            . "para que una IA genere una imagen que represente visualmente el contenido de este capitulo. "
            . "Describe colores, composicion, estilo artistico y elementos clave.";

        echo "            <div><span class='muted'>> Conectando con API...</span></div>\n";
        flush();
        $response = API::generateText($textPrompt);

        $parts = explode('[IMAGE_PROMPT]', $response);
        $texto = trim($parts[0]);
        $imagePrompt = isset($parts[1]) ? trim($parts[1]) : '';

        // Pausa entre capitulos para evitar rate limiting
        if ($i < count($capitulos) - 1) usleep(2000000);

        $preview = mb_substr($texto, 0, 100) . '...';
        echo "            <div><span class='ok'>".chr(10003)."</span> " . htmlspecialchars($preview) . "</div>\n";
        flush();

        $imagen = null;
        if ($incluirImagenes) {
            try {
                echo "            <div><span class='info'>  --></span> Generando imagen...</div>\n";
                flush();

                $imgPromptFinal = $imagePrompt ?: "Ilustracion para capitulo '{$tituloClean}' sobre {$tema}, estilo {$imgStyle}, diseno profesional";
                $imgData = HuggingFaceAPI::generatePollinationsImage($imgPromptFinal, 768, 768);
                if ($imgData) {
                    $im = @imagecreatefromstring($imgData);
                    if ($im) {
                        ob_start(); imagepng($im); imagedestroy($im); $imagen = ob_get_clean();
                    }
                }
                if (!$imagen) {
                    $imagen = API::generateImagePlaceholder($tituloClean, $tema, $theme, $customColors, $imgStyle);
                }

                echo "            <div><span class='ok'>".chr(10003)."</span> Imagen generada</div>\n";
                flush();
            } catch (Exception $e) {
                echo "            <div><span class='err'>  ! Imagen omitida: " . htmlspecialchars($e->getMessage()) . "</span></div>\n";
                flush();
            }
        }

        $contenido[] = [
            'titulo' => $tituloClean,
            'texto' => $texto,
            'imagen' => $imagen,
        ];
    }

} catch (Exception $e) {
    echo "            <div><span class='err'>Error: " . htmlspecialchars($e->getMessage()) . "</span></div>\n";
    flush();
    exit;
}

// ============ PASO 3: PDF ============
try {
    echo "            <div><span class='step'>>> Paso 3/3:</span> Generando PDF...</div>\n";
    flush();

    $pdfFile = generarPDF($tema, $contenido, $idioma, $theme, $customColors, $tituloPortada);

    echo "            <div><span class='ok'>".chr(10003)."</span> PDF listo</div>\n";
    flush();

} catch (Exception $e) {
    echo "            <div><span class='err'>Error PDF: " . htmlspecialchars($e->getMessage()) . "</div>\n";
    flush();
    exit;
}

echo "            <div><span class='ok' style='font-size:18px;'>".chr(10004)." Ebook completado!</span></div>\n";
flush();
?>
        </div>
    </div>

    <div class="result-box" id="result">
        <h3>Ebook listo para descargar</h3>
        <p><?= count($contenido) ?> capitulos generados</p>
        <a class="btn-download" href="<?= htmlspecialchars($pdfFile) ?>" download>
            Descargar PDF
        </a><br>
        <a class="btn-back" href="index.php">Generar otro ebook</a>
    </div>
</div>

<script>document.getElementById('result').style.display = 'block';</script>
</body>
</html>
