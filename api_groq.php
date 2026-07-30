<?php
class GroqAPI
{
    public static function generateText($prompt)
    {
        $payload = [
            'model' => GROQ_MODEL,
            'messages' => [['role' => 'user', 'content' => $prompt]],
            'max_tokens' => 4096,
            'temperature' => 0.7,
        ];

        $attempts = 0;
        $maxRetries = defined('MAX_RETRIES') ? MAX_RETRIES : 3;

        $logId = uniqid();
        error_log("[Groq:{$logId}] Iniciando generateText, prompt length: " . strlen($prompt));

        while ($attempts < $maxRetries) {
            error_log("[Groq:{$logId}] Intento " . ($attempts + 1) . "/{$maxRetries}");
            $result = self::call($payload, $logId);
            if ($result === false) {
                $attempts++;
                if ($attempts >= $maxRetries) {
                    error_log("[Groq:{$logId}] Se agotaron los reintentos");
                    break;
                }
                $wait = $attempts * 5;
                error_log("[Groq:{$logId}] Esperando {$wait}s antes de reintentar");
                sleep($wait);
                continue;
            }
            error_log("[Groq:{$logId}] Respuesta exitosa, length: " . strlen($result));
            return $result;
        }

        throw new Exception('No se pudo obtener respuesta de Groq tras ' . $maxRetries . ' intentos');
    }

    private static function call($payload, $logId = '')
    {
        $url = GROQ_API_BASE . '/chat/completions';
        $json = json_encode($payload);

        error_log("[Groq:{$logId}] curl_init => {$url}");
        error_log("[Groq:{$logId}] payload size: " . strlen($json) . " bytes");
        error_log("[Groq:{$logId}] API key (primeros 8): " . substr(GROQ_API_KEY, 0, 8) . "...");

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

        error_log("[Groq:{$logId}] Antes de curl_exec...");
        $start = microtime(true);
        $response = curl_exec($ch);
        $elapsed = round(microtime(true) - $start, 2);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        error_log("[Groq:{$logId}] Despues de curl_exec: tiempo={$elapsed}s, http_code={$httpCode}, error=" . ($error ?: '(ninguno)'));
        error_log("[Groq:{$logId}] Curl info: total_time={$info['total_time']}, connect_time={$info['connect_time']}, namelookup_time={$info['namelookup_time']}, pretransfer_time={$info['pretransfer_time']}, starttransfer_time={$info['starttransfer_time']}");

        if ($error) {
            error_log("[Groq:{$logId}] Error conexion: {$error}");
            return false;
        }

        if ($httpCode === 429) {
            error_log("[Groq:{$logId}] Rate limit (429)");
            return false;
        }

        if ($httpCode !== 200) {
            $data = @json_decode($response, true);
            $msg = ($data['error']['message'] ?? 'HTTP ' . $httpCode);
            error_log("[Groq:{$logId}] Error HTTP {$httpCode}: {$msg}");
            throw new Exception("Groq Error ({$httpCode}): {$msg}");
        }

        $data = json_decode($response, true);
        $text = $data['choices'][0]['message']['content'] ?? null;
        if (!$text) {
            error_log("[Groq:{$logId}] Respuesta vacia");
            throw new Exception('Respuesta vacia de Groq');
        }

        return $text;
    }

    public static function generateImagePlaceholder($chapterTitle, $tema, $theme = 'cyberpunk', $customColors = [], $imgStyle = 'geometric')
    {
        return HuggingFaceAPI::generateChapterImage($chapterTitle, $tema, $theme, $customColors, $imgStyle);
    }
}
