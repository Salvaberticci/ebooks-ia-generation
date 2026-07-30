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
        while ($attempts < $maxRetries) {
            $result = self::call($payload);
            if ($result === false) {
                $attempts++;
                if ($attempts >= $maxRetries) break;
                sleep($attempts * 5);
                continue;
            }
            return $result;
        }

        throw new Exception('No se pudo obtener respuesta de Groq tras ' . $maxRetries . ' intentos');
    }

    private static function call($payload)
    {
        $ch = curl_init(GROQ_API_BASE . '/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
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

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            error_log("[Groq] Error conexion: {$error}");
            return false;
        }

        if ($httpCode === 429) {
            error_log("[Groq] Rate limit, reintentando...");
            return false;
        }

        if ($httpCode !== 200) {
            $data = @json_decode($response, true);
            $msg = ($data['error']['message'] ?? 'HTTP ' . $httpCode);
            throw new Exception("Groq Error ({$httpCode}): {$msg}");
        }

        $data = json_decode($response, true);
        $text = $data['choices'][0]['message']['content'] ?? null;
        if (!$text) {
            throw new Exception('Respuesta vacia de Groq');
        }

        return $text;
    }

    public static function generateImagePlaceholder($chapterTitle, $tema, $theme = 'cyberpunk', $customColors = [], $imgStyle = 'geometric')
    {
        return HuggingFaceAPI::generateChapterImage($chapterTitle, $tema, $theme, $customColors, $imgStyle);
    }
}
