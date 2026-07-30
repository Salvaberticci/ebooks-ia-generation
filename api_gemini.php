<?php
class GeminiAPI
{
    public static function generateText($prompt)
    {
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/'
            . GEMINI_MODEL . ':generateContent?key=' . GEMINI_API_KEY;

        $payload = [
            'contents' => [
                ['parts' => [['text' => $prompt]]],
            ],
            'generationConfig' => [
                'maxOutputTokens' => 4096,
                'temperature' => 0.7,
                'topP' => 0.95,
            ],
        ];

        $attempts = 0;
        while ($attempts < MAX_RETRIES) {
            try {
                return self::call($url, $payload);
            } catch (RateLimitException $e) {
                $attempts++;
                if ($attempts >= MAX_RETRIES) throw $e;
                sleep(2);
            }
        }

        throw new Exception('No se pudo obtener respuesta de Gemini');
    }

    private static function call($url, $payload)
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => API_TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception("Error de conexion: {$error}");
        }

        $data = json_decode($response, true);

        if ($httpCode === 429) {
            throw new RateLimitException('Limite de velocidad alcanzado');
        }

        if ($httpCode !== 200) {
            $msg = $data['error']['message'] ?? 'Error desconocido';
            throw new Exception("Gemini Error ({$httpCode}): {$msg}");
        }

        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

        if (!$text) {
            $reason = $data['candidates'][0]['finishReason'] ?? 'unknown';
            throw new Exception("Gemini: respuesta vacia (motivo: {$reason})");
        }

        return trim($text);
    }

    public static function generateImagePlaceholder($chapterTitle, $tema)
    {
        return HuggingFaceAPI::generateImagePlaceholder($chapterTitle, $tema);
    }
}

class RateLimitException extends Exception {}
