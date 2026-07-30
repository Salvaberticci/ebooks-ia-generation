<?php
class GroqAPI
{
    private static $log = [];

    private static function log($msg)
    {
        self::$log[] = $msg;
    }

    public static function getDebugLog()
    {
        $out = self::$log;
        self::$log = [];
        return $out;
    }

    public static function generateText($prompt)
    {
        self::$log = [];
        self::log('Iniciando generateText, prompt length: ' . strlen($prompt));

        $payload = [
            'model' => GROQ_MODEL,
            'messages' => [['role' => 'user', 'content' => $prompt]],
            'max_tokens' => 4096,
            'temperature' => 0.7,
        ];

        $attempts = 0;
        $maxRetries = defined('MAX_RETRIES') ? MAX_RETRIES : 3;

        while ($attempts < $maxRetries) {
            self::log("Intento " . ($attempts + 1) . "/{$maxRetries}");
            $result = self::call($payload);
            if ($result === false) {
                $attempts++;
                if ($attempts >= $maxRetries) {
                    self::log('Se agotaron los reintentos');
                    break;
                }
                $wait = $attempts * 5;
                self::log("Esperando {$wait}s antes de reintentar");
                sleep($wait);
                continue;
            }
            self::log('Respuesta exitosa, length: ' . strlen($result));
            return $result;
        }

        throw new Exception('No se pudo obtener respuesta de Groq tras ' . $maxRetries . ' intentos');
    }

    private static function call($payload)
    {
        $url = GROQ_API_BASE . '/chat/completions';
        $json = json_encode($payload);

        self::log("curl_init => {$url}");
        self::log("payload size: " . strlen($json) . " bytes");
        self::log("API key (primeros 8): " . substr(GROQ_API_KEY, 0, 8) . "...");

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . GROQ_API_KEY,
                'Content-Type: application/json',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FRESH_CONNECT => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        ]);

        self::log('Antes de curl_exec...');
        $start = microtime(true);
        $response = curl_exec($ch);
        $elapsed = round(microtime(true) - $start, 2);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        self::log("Despues de curl_exec: tiempo={$elapsed}s, http_code={$httpCode}");
        self::log("tiempos: lookup={$info['namelookup_time']}s, connect={$info['connect_time']}s, ssl={$info['pretransfer_time']}s, transfer={$info['starttransfer_time']}s, total={$info['total_time']}s");

        if ($error) {
            self::log("Error conexion: {$error}");
            return false;
        }

        if ($httpCode === 429) {
            self::log('Rate limit (429)');
            return false;
        }

        if ($httpCode !== 200) {
            $data = @json_decode($response, true);
            $msg = ($data['error']['message'] ?? 'HTTP ' . $httpCode);
            self::log("Error HTTP {$httpCode}: {$msg}");
            throw new Exception("Groq Error ({$httpCode}): {$msg}");
        }

        $data = json_decode($response, true);
        $text = $data['choices'][0]['message']['content'] ?? null;
        if (!$text) {
            self::log('Respuesta vacia');
            throw new Exception('Respuesta vacia de Groq');
        }

        return $text;
    }

    public static function generateImagePlaceholder($chapterTitle, $tema, $theme = 'cyberpunk', $customColors = [], $imgStyle = 'geometric')
    {
        return HuggingFaceAPI::generateChapterImage($chapterTitle, $tema, $theme, $customColors, $imgStyle);
    }
}
