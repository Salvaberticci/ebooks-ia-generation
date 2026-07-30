<?php
if (!class_exists('RateLimitException')) {
    class RateLimitException extends Exception {}
}

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
        while ($attempts < MAX_RETRIES) {
            try {
                return self::call($payload);
            } catch (RateLimitException $e) {
                $attempts++;
                if ($attempts >= MAX_RETRIES) throw $e;
                $sleep = $attempts * 5;
                sleep($sleep);
            }
        }

        throw new Exception('No se pudo obtener respuesta de Groq');
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
                'Connection: keep-alive',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => API_TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TCP_KEEPALIVE => 1,
            CURLOPT_TCP_KEEPIDLE => 30,
            CURLOPT_TCP_KEEPINTVL => 10,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception("Error de conexion: {$error}");
        }

        if ($httpCode === 429) {
            throw new RateLimitException('Limite de velocidad alcanzado');
        }

        if ($httpCode !== 200) {
            $data = json_decode($response, true);
            $msg = $data['error']['message'] ?? 'Error desconocido';
            throw new Exception("Groq Error ({$httpCode}): {$msg}");
        }

        $data = json_decode($response, true);
        return $data['choices'][0]['message']['content'] ?? throw new Exception('Respuesta vacia de Groq');
    }

    public static function generateImagePlaceholder($chapterTitle, $tema, $theme = 'cyberpunk', $customColors = [], $imgStyle = 'geometric')
    {
        return HuggingFaceAPI::generateChapterImage($chapterTitle, $tema, $theme, $customColors, $imgStyle);
    }
}
