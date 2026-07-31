<?php
$tcpdfPath = __DIR__ . '/vendor/tecnickcom/tcpdf/tcpdf.php';
if (!file_exists($tcpdfPath)) {
    die('<h2 style="color:#e94560;">Error: Falta TCPDF</h2><p>Ejecutá <code>composer install</code> en el servidor o subí la carpeta <code>vendor/</code>.</p>');
}
require_once $tcpdfPath;

function generarPDF($tema, $contenido, $idioma = 'es', $themeId = 'cyberpunk', $customColors = [], $tituloPortada = '')
{
    global $THEMES;
    $temaNombre = $tituloPortada ?: $tema;
    $t = HuggingFaceAPI::resolveTheme($themeId, $customColors);

    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetCreator('Generador de Ebooks IA');
    $pdf->SetAuthor('Inteligencia Artificial');
    $pdf->SetTitle($temaNombre);
    $pdf->SetMargins(25, 25, 25);
    $pdf->SetAutoPageBreak(false);
    $pdf->setImageScale(1.5);

    // ============ PORTADA UNIFICADA (imagen IA a pantalla completa) ============
    $pdf->AddPage();
    $coverW = 210; $coverH = 297;

    // 1. Generar imagen de portada IA
    $coverImg = HuggingFaceAPI::generateCoverImage($temaNombre, $themeId, $customColors);

    if ($coverImg) {
        // Imagen IA como fondo full-page (210x297 mm desde 0,0)
        $imgData = '@' . $coverImg;
        $pdf->Image($imgData, 0, 0, $coverW, $coverH, '', '', '', false, 150);
    } else {
        // Fallback: fondo degradado + formas decorativas
        $pdf->SetFillColor($t['cover_bg1'][0], $t['cover_bg1'][1], $t['cover_bg1'][2]);
        $pdf->Rect(0, 0, $coverW, $coverH, 'F');
        $alpha = 0.06;
        $pdf->SetFillColor($t['accent2'][0], $t['accent2'][1], $t['accent2'][2]);
        $pdf->Circle(180, 50, 70, 0, 360, 'F', ['width' => 0], [$alpha, $alpha, $alpha]);
        $pdf->Circle(35, 220, 55, 0, 360, 'F', ['width' => 0], [$alpha, $alpha, $alpha]);
        $pdf->SetFillColor($t['accent'][0], $t['accent'][1], $t['accent'][2]);
        $pdf->Circle(160, 200, 35, 0, 360, 'F', ['width' => 0], [$alpha * 1.2, $alpha * 1.2, $alpha * 1.2]);
    }

    // 2. Overlay oscuro semi-transparente sobre toda la página para legibilidad del texto
    // Capa superior (banda degradada fuerte)
    $pdf->SetFillColor(0, 0, 0);
    $pdf->SetAlpha(0.55);
    $pdf->Rect(0, 0, $coverW, $coverH, 'F');
    $pdf->SetAlpha(1);

    // 3. Banda de color del tema en la parte superior (delgada, estética)
    $pdf->SetFillColor($t['accent'][0], $t['accent'][1], $t['accent'][2]);
    $pdf->SetAlpha(0.9);
    $pdf->Rect(0, 0, $coverW, 4, 'F');
    $pdf->SetFillColor($t['accent2'][0], $t['accent2'][1], $t['accent2'][2]);
    $pdf->Rect(0, 4, $coverW, 1.2, 'F');
    $pdf->SetAlpha(1);

    // 4. Caja semi-transparente del tema al fondo del título
    $pdf->SetFillColor($t['accent'][0], $t['accent'][1], $t['accent'][2]);
    $pdf->SetAlpha(0.75);
    $pdf->Rect(20, 115, 170, 5, 'F');
    $pdf->SetAlpha(1);

    // 5. Etiqueta "EBOOK" (pequeña, arriba del título)
    $pdf->SetXY(20, 105);
    $pdf->SetTextColor($t['accent2'][0], $t['accent2'][1], $t['accent2'][2]);
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetAlpha(0.95);
    $letter_spacing_html = '<span style="letter-spacing:4px;font-size:9px;color:rgb('
        . $t['accent2'][0] . ',' . $t['accent2'][1] . ',' . $t['accent2'][2] . ');">E B O O K</span>';
    $pdf->writeHTML('<div style="text-align:center;">' . $letter_spacing_html . '</div>');
    $pdf->SetAlpha(1);

    // 6. Título principal grande y centrado
    $pdf->SetY(122);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('helvetica', 'B', 26);
    $pdf->writeHTML('<div style="text-align:center;color:#ffffff;line-height:1.3;padding:0 15px;">'
        . htmlspecialchars($temaNombre) . '</div>');

    // 7. Línea decorativa bajo el título
    $titleBottom = $pdf->GetY() + 4;
    $pdf->SetDrawColor($t['accent'][0], $t['accent'][1], $t['accent'][2]);
    $pdf->SetLineWidth(0.8);
    $pdf->Line(50, $titleBottom, 160, $titleBottom);
    $pdf->SetDrawColor($t['accent2'][0], $t['accent2'][1], $t['accent2'][2]);
    $pdf->SetLineWidth(0.4);
    $pdf->Line(70, $titleBottom + 2.5, 140, $titleBottom + 2.5);

    // 8. Subtítulo "Generado por Inteligencia Artificial"
    $pdf->SetY($titleBottom + 10);
    $pdf->SetTextColor(220, 220, 220);
    $pdf->SetFont('helvetica', 'I', 10);
    $pdf->Cell(0, 6, 'Generado por Inteligencia Artificial', 0, 1, 'C');

    // ============ INDICE ============
    $pdf->SetAutoPageBreak(true, 30);
    $pdf->AddPage();
    if (isset($t['bg']) && $t['bg'] !== [255, 255, 255]) {
        $pdf->SetFillColor($t['bg'][0], $t['bg'][1], $t['bg'][2]);
        $pdf->Rect(0, 0, 210, 297, 'F');
    }

    // TOC header
    $pdf->SetY(25);
    $pdf->SetTextColor($t['header'][0], $t['header'][1], $t['header'][2]);
    $pdf->SetFont('helvetica', 'B', 22);
    $pdf->Cell(0, 10, 'Indice', 0, 1, 'L');

    $pdf->SetDrawColor($t['accent'][0], $t['accent'][1], $t['accent'][2]);
    $pdf->SetLineWidth(0.8);
    $pdf->Line(25, 37, 80, 37);
    $pdf->SetDrawColor($t['accent2'][0], $t['accent2'][1], $t['accent2'][2]);
    $pdf->SetLineWidth(0.4);
    $pdf->Line(25, 38.5, 65, 38.5);
    $pdf->Ln(12);

    $pdf->SetFont('helvetica', '', 11);
    $pdf->setCellHeightRatio(1.5);
    foreach ($contenido as $i => $item) {
        $pdf->SetTextColor($t['text'][0], $t['text'][1], $t['text'][2]);
        $numTxt = sprintf('%02d.', $i + 1);
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->SetTextColor($t['accent'][0], $t['accent'][1], $t['accent'][2]);
        $pdf->Cell(12, 7, $numTxt, 0, 0, 'L');
        $pdf->SetFont('helvetica', '', 11);
        $pdf->SetTextColor($t['text'][0], $t['text'][1], $t['text'][2]);
        $pdf->MultiCell(148, 7, htmlspecialchars($item['titulo']), 0, 'L');
        if ($i < count($contenido) - 1) {
            $pdf->SetTextColor($t['toc_dots'][0], $t['toc_dots'][1], $t['toc_dots'][2]);
            $pdf->SetFont('helvetica', '', 7);
            $pdf->Cell(0, 2, str_repeat('. ', 50), 0, 1, 'L');
            $pdf->Ln(1);
        }
    }

    // ============ CAPITULOS ============
    $ch = 0;
    foreach ($contenido as $num => $item) {
        $ch++;
        $pdf->AddPage();

        // Page background
        if (isset($t['bg']) && $t['bg'] !== [255, 255, 255]) {
            $pdf->SetFillColor($t['bg'][0], $t['bg'][1], $t['bg'][2]);
            $pdf->Rect(0, 0, 210, 297, 'F');
        }

        // Top colored header bar
        $pdf->SetFillColor($t['accent'][0], $t['accent'][1], $t['accent'][2]);
        $pdf->Rect(0, 0, 210, 3, 'F');
        $pdf->SetFillColor($t['accent2'][0], $t['accent2'][1], $t['accent2'][2]);
        $pdf->Rect(0, 3, 210, 0.6, 'F');

        // Chapter badge - large outline number
        $pdf->SetFont('helvetica', 'B', 48);
        $pdf->SetTextColor($t['accent'][0], $t['accent'][1], $t['accent'][2]);
        $pdf->SetXY(25, 18);
        $pdf->Cell(20, 18, sprintf('%02d', $num + 1), 0, 0, 'L');

        // Chapter title next to number
        $pdf->SetXY(50, 20);
        $pdf->SetTextColor($t['header'][0], $t['header'][1], $t['header'][2]);
        $pdf->SetFont('helvetica', 'B', 16);
        $titulo = htmlspecialchars($item['titulo']);
        $pdf->MultiCell(135, 9, $titulo, 0, 'L');

        // Decorative separator
        $titleBottom = max($pdf->GetY(), 42);
        $pdf->SetDrawColor($t['accent2'][0], $t['accent2'][1], $t['accent2'][2]);
        $pdf->SetLineWidth(0.5);
        $pdf->Line(25, $titleBottom + 2, 100, $titleBottom + 2);
        $pdf->SetDrawColor($t['accent'][0], $t['accent'][1], $t['accent'][2]);
        $pdf->SetLineWidth(0.3);
        $pdf->Line(25, $titleBottom + 4, 70, $titleBottom + 4);

        $pdf->SetY($titleBottom + 12);

        // Body text
        $pdf->SetTextColor($t['text'][0], $t['text'][1], $t['text'][2]);
        $pdf->SetFont('helvetica', '', 10);

        $parrafos = preg_split('/\n\s*\n/', trim($item['texto']));
        $html = '<div style="text-align:justify;line-height:1.7;">';
        foreach ($parrafos as $parrafo) {
            $parrafo = trim(preg_replace('/\*{1,2}(.+?)\*{1,2}/', '$1', $parrafo));
            if (!empty($parrafo)) {
                $html .= '<p style="margin-bottom:6px;">' . nl2br(htmlspecialchars($parrafo)) . '</p>';
            }
        }
        $html .= '</div>';

        $pdf->writeHTML($html);

        // Chapter image at bottom or on next page
        if (!empty($item['imagen'])) {
            $checkY = $pdf->GetY();
            if ($checkY > 200) {
                $pdf->AddPage();
                if (isset($t['bg']) && $t['bg'] !== [255, 255, 255]) {
                    $pdf->SetFillColor($t['bg'][0], $t['bg'][1], $t['bg'][2]);
                    $pdf->Rect(0, 0, 210, 297, 'F');
                }
                $pdf->SetY(25);
            } else {
                $pdf->Ln(10);
            }

            $imgData = '@' . $item['imagen'];
            $imgW = 120;
            $x = ($pdf->getPageWidth() - $imgW) / 2;
            
            // Image border (slightly larger rect behind the image)
            $pdf->SetFillColor($t['accent2'][0], $t['accent2'][1], $t['accent2'][2]);
            $borderW = 2; // 2mm border
            $pdf->Rect($x - $borderW, $pdf->GetY() - $borderW, $imgW + ($borderW * 2), $imgW + ($borderW * 2), 'F');
            
            $pdf->Image($imgData, $x, '', $imgW, $imgW, '', '', 'C', false, 200);

            $pdf->Ln(2);
            $pdf->SetFont('helvetica', 'I', 7.5);
            $pdf->SetTextColor($t['footer'][0], $t['footer'][1], $t['footer'][2]);
            $pdf->Cell(0, 4, 'Ilustracion: ' . htmlspecialchars($item['titulo']), 0, 1, 'C');
        }
    }

    // ============ GUARDAR ============
    $filename = 'ebook_' . preg_replace('/[^a-zA-Z0-9_\-\x{4e00}-\x{9fff}]/u', '_', $temaNombre)
        . '_' . date('Ymd_His') . '.pdf';
    $filename = mb_substr($filename, 0, 200);

    $dir = __DIR__ . '/generados';
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    @file_put_contents($dir . '/index.html', '');
    @file_put_contents($dir . '/.htaccess', "Options -Indexes\n");

    $filepath = 'generados/' . $filename;
    $pdf->Output(__DIR__ . '/' . $filepath, 'F');

    return $filepath;
}
