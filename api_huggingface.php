<?php
class HuggingFaceAPI
{
    public static function generateText($prompt)
    {
        $payload = [
            'model' => HF_MODEL_TEXT,
            'messages' => [['role' => 'user', 'content' => $prompt]],
            'max_tokens' => 2048, 'temperature' => 0.7, 'top_p' => 0.95,
        ];
        $ch = curl_init(HF_API_BASE . '/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . HF_TOKEN, 'Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => API_TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => 30, CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        if ($error) throw new Exception("Error: {$error}");
        if ($httpCode !== 200) throw new Exception("API Error ({$httpCode}): " . substr($response, 0, 300));
        $data = json_decode($response, true);
        return $data['choices'][0]['message']['content'] ?? throw new Exception('Respuesta vacia');
    }

    public static function resolveTheme($themeId, $customColors = [])
    {
        global $THEMES;
        $t = $THEMES[$themeId] ?? $THEMES['cyberpunk'];
        if (!empty($customColors)) {
            $map = ['bg'=>'cover_bg1', 'accent'=>'accent', 'accent2'=>'accent2', 'text'=>'text', 'header'=>'header', 'bg_page'=>'page_bg'];
            foreach ($map as $ck => $tk) {
                if (!empty($customColors[$ck])) {
                    $c = $customColors[$ck];
                    if (preg_match('/^#([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})$/i', $c, $m)) {
                        $t[$tk] = [hexdec($m[1]), hexdec($m[2]), hexdec($m[3])];
                        if ($tk === 'cover_bg1') $t['cover_bg2'] = [min(255,$t[$tk][0]+20), min(255,$t[$tk][1]+15), min(255,$t[$tk][2]+30)];
                        if ($tk === 'text') $t['footer'] = $t['toc_dots'] = [max(0,$t[$tk][0]-30), max(0,$t[$tk][1]-30), max(0,$t[$tk][2]-30)];
                        if ($tk === 'bg_page') $t['bg'] = $t['page_bg'] = $t[$tk];
                    }
                }
            }
            if (!empty($customColors['header'])) {
                $t['title_color'] = $t['header'];
                $t['subtitle'] = $t['accent2'];
            }
        }
        return $t;
    }

    public static function generatePollinationsImage($prompt, $width = 768, $height = 768)
    {
        $params = http_build_query(['width' => $width, 'height' => $height, 'nologo' => 'true']);
        $url = POLLINATIONS_API_URL . '/' . urlencode($prompt) . '?' . $params;
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => POLLINATIONS_TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => 20, CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true, CURLOPT_TCP_KEEPALIVE => 1,
            CURLOPT_TCP_KEEPIDLE => 20, CURLOPT_TCP_KEEPINTVL => 5,
        ]);
        $res = curl_exec($ch);
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $ct = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);
        if ($http === 200 && strlen($res) > 1000 && str_contains($ct, 'image')) {
            return $res;
        }
        return null;
    }

    public static function buildImagePrompt($context, $themeId, $customColors, $imgStyle = 'geometric')
    {
        global $THEME_STYLE_PROMPTS, $IMAGE_STYLE_PROMPTS;
        $themeDesc = $THEME_STYLE_PROMPTS[$themeId] ?? 'modern design';
        $styleDesc = $IMAGE_STYLE_PROMPTS[$imgStyle] ?? 'digital art';
        if (!empty($customColors)) {
            $themeDesc .= ' with custom color palette';
        }
        return "{$context}, {$styleDesc}, {$themeDesc}, high quality digital illustration, book illustration style, clean professional design";
    }

    public static function generateCoverImage($titulo, $themeId, $customColors = [])
    {
        $prompt = self::buildImagePrompt(
            "Professional book cover illustration for a book titled '{$titulo}', beautiful cinematic composition, dramatic lighting, rich colors, abstract art concept representing the subject, no text, no letters, no words, no typography, no title text",
            $themeId, $customColors, 'abstract'
        );
        $aiImg = self::generatePollinationsImage($prompt, 768, 1024);
        if ($aiImg) {
            $im = @imagecreatefromstring($aiImg);
            if ($im) {
                ob_start(); imagepng($im); imagedestroy($im); return ob_get_clean();
            }
        }

        $t = self::resolveTheme($themeId, $customColors);
        $w = COVER_IMAGE_WIDTH; $h = COVER_IMAGE_HEIGHT;
        $img = imagecreatetruecolor($w, $h);

        for ($y = 0; $y < $h; $y++) {
            $r = (int)($t['cover_bg1'][0] + ($t['cover_bg2'][0]-$t['cover_bg1'][0])*$y/$h);
            $g = (int)($t['cover_bg1'][1] + ($t['cover_bg2'][1]-$t['cover_bg1'][1])*$y/$h);
            $b = (int)($t['cover_bg1'][2] + ($t['cover_bg2'][2]-$t['cover_bg1'][2])*$y/$h);
            imageline($img, 0, $y, $w, $y, imagecolorallocate($img, $r, $g, $b));
        }

        $accent = imagecolorallocate($img, $t['accent'][0], $t['accent'][1], $t['accent'][2]);
        $accent2 = imagecolorallocate($img, $t['accent2'][0], $t['accent2'][1], $t['accent2'][2]);
        $white = imagecolorallocate($img, 255, 255, 255);

        for ($i = 0; $i < 5; $i++) {
            $col = imagecolorallocatealpha($img, $t['accent2'][0], $t['accent2'][1], $t['accent2'][2], rand(50,100));
            imagefilledellipse($img, rand(50,$w-50), rand(50,$h-50), rand(160,500), rand(160,500), $col);
        }
        $lineCol = imagecolorallocatealpha($img, $t['accent'][0], $t['accent'][1], $t['accent'][2], 60);
        for ($i = -2; $i < 3; $i++) {
            imagesetthickness($img, 2);
            imageline($img, 0, $h/2+$i*80, $w, $h/2-200+$i*80, $lineCol);
        }
        imagefilledrectangle($img, 0, $h-120, $w, $h-115, $accent);
        imagefilledrectangle($img, 0, $h-8, $w, $h, $accent2);

        $wrapText = self::wrapForImg($titulo, 48, $w-120);
        $lines = explode("\n", $wrapText);
        $startY = $h/2 - count($lines)*40;
        foreach ($lines as $i => $line) {
            $y = $startY + $i*70;
            for ($dx=-3;$dx<=3;$dx+=2) for ($dy=-3;$dy<=3;$dy+=2) {
                $s = imagecolorallocatealpha($img,0,0,0,80);
                imagestring($img,5, $w/2-strlen($line)*9+$dx, $y+$dy, $line, $s);
            }
            imagestring($img,5, $w/2-strlen($line)*9, $y, $line, $white);
        }

        $subColor = imagecolorallocate($img, $t['accent2'][0], $t['accent2'][1], $t['accent2'][2]);
        imagestring($img,4, $w/2-strlen('Generado por IA')*7, $h-200, 'Generado por IA', $subColor);
        $d = imagecolorallocate($img, $t['subtitle'][0], $t['subtitle'][1], $t['subtitle'][2]);
        imagestring($img,3, $w/2-strlen(date('d/m/Y'))*7, $h-160, date('d/m/Y'), $d);

        ob_start(); imagepng($img); imagedestroy($img); return ob_get_clean();
    }

    public static function generateChapterImage($chapterTitle, $tema, $themeId, $customColors = [], $imgStyle = 'geometric')
    {
        $prompt = self::buildImagePrompt(
            "Illustration for chapter '{$chapterTitle}' about {$tema}, conceptual visual representation",
            $themeId, $customColors, $imgStyle
        );
        $aiImg = self::generatePollinationsImage($prompt, 768, 768);
        if ($aiImg) {
            $im = @imagecreatefromstring($aiImg);
            if ($im) {
                ob_start(); imagepng($im); imagedestroy($im); return ob_get_clean();
            }
        }

        $t = self::resolveTheme($themeId, $customColors);
        $w = 800; $h = 500;
        $img = imagecreatetruecolor($w, $h);

        for ($y = 0; $y < $h; $y++) {
            $r = (int)($t['cover_bg1'][0] + ($t['card'][0]-$t['cover_bg1'][0])*$y/$h);
            $g = (int)($t['cover_bg1'][1] + ($t['card'][1]-$t['cover_bg1'][1])*$y/$h);
            $b = (int)($t['cover_bg1'][2] + ($t['card'][2]-$t['cover_bg1'][2])*$y/$h);
            imageline($img, 0, $y, $w, $y, imagecolorallocate($img, $r, $g, $b));
        }

        $accent = imagecolorallocate($img, $t['accent'][0], $t['accent'][1], $t['accent'][2]);
        $accent2 = imagecolorallocate($img, $t['accent2'][0], $t['accent2'][1], $t['accent2'][2]);
        $white = imagecolorallocate($img, 255, 255, 255);

        // Decorations based on style
        if ($imgStyle === 'geometric') {
            for ($i = 0; $i < 5; $i++) {
                $col = imagecolorallocatealpha($img, $t['accent'][0], $t['accent'][1], $t['accent'][2], 50+$i*15);
                $size = rand(40, 120) * 2;
                $x = rand(20, $w - 20);
                $y = rand(20, $h - 20);
                imagefilledrectangle($img, $x-$size/2, $y-$size/2, $x+$size/2, $y+$size/2, $col);
                $col2 = imagecolorallocatealpha($img, $t['accent2'][0], $t['accent2'][1], $t['accent2'][2], 40);
                $s2 = $size * 0.6;
                imagerectangle($img, $x-$s2/2, $y-$s2/2, $x+$s2/2, $y+$s2/2, $col2);
            }
        } elseif ($imgStyle === 'abstract') {
            for ($i = 0; $i < 6; $i++) {
                $col = imagecolorallocatealpha($img, $t['accent'][0]+rand(-30,30), $t['accent'][1]+rand(-30,30), $t['accent'][2]+rand(-30,30), 60);
                $cx = rand(50, $w-50); $cy = rand(50, $h-50);
                imagefilledellipse($img, $cx, $cy, rand(100,300), rand(100,300), $col);
            }
        } elseif ($imgStyle === 'gradient') {
            $step = 40;
            for ($x = 0; $x <= $w; $x += $step) {
                $col = imagecolorallocatealpha($img, $t['accent'][0], $t['accent'][1], $t['accent'][2], rand(20,60));
                imageline($img, $x, 0, $x + $step, $h, $col);
            }
        } elseif ($imgStyle === 'particles') {
            for ($i = 0; $i < 80; $i++) {
                $col = imagecolorallocatealpha($img, $t['accent2'][0], $t['accent2'][1], $t['accent2'][2], rand(30,90));
                $s = rand(2, 6);
                imagefilledellipse($img, rand(10, $w-10), rand(10, $h-10), $s, $s, $col);
            }
        } elseif ($imgStyle === 'minimal') {
            $lineC = imagecolorallocatealpha($img, $t['accent2'][0], $t['accent2'][1], $t['accent2'][2], 50);
            for ($x = 0; $x <= $w; $x += 30) {
                imageline($img, $x, 0, $x, $h, $lineC);
            }
        } elseif ($imgStyle === 'retro') {
            for ($i = 0; $i < 10; $i++) {
                $col = imagecolorallocatealpha($img, $t['accent'][0], $t['accent'][1], $t['accent'][2], rand(40,80));
                $x = rand(0, $w); $y = rand(0, $h);
                imageline($img, $x, $y, $x + rand(-100,100), $y + rand(-100,100), $col);
            }
        }

        imagefilledrectangle($img, 40, $h-60, $w-40, $h-56, $accent);
        imagefilledrectangle($img, $w/2-100, $h-60, $w/2+100, $h-56, $accent2);

        $title = mb_substr($chapterTitle, 0, 60);
        $wrapText = self::wrapForImg($title, 20, $w-100);
        $tLines = explode("\n", $wrapText);
        $sY = $h/2 - count($tLines)*18;
        foreach ($tLines as $i => $line) {
            imagestring($img, 4, $w/2-strlen($line)*7, $sY+$i*30, $line, $white);
        }
        $temaC = imagecolorallocate($img, $t['accent2'][0], $t['accent2'][1], $t['accent2'][2]);
        imagestring($img, 3, $w/2-strlen($tema)*6, $h-90, $tema, $temaC);

        ob_start(); imagepng($img); imagedestroy($img); return ob_get_clean();
    }

    public static function generateImagePlaceholder($chapterTitle, $tema)
    {
        return self::generateChapterImage($chapterTitle, $tema, 'cyberpunk');
    }

    private static function wrapForImg($text, $fontSize, $maxW)
    {
        $cpl = (int)(($maxW-20)/(($fontSize/2)+2));
        if (mb_strlen($text) <= $cpl) return $text;
        $words = explode(' ', $text); $lines = ''; $cur = '';
        foreach ($words as $w) {
            $t = $cur ? "$cur $w" : $w;
            if (mb_strlen($t) > $cpl && $cur) { $lines .= "$cur\n"; $cur = $w; }
            else { $cur = $t; }
        }
        return $cur ? $lines . $cur : $lines;
    }
}
