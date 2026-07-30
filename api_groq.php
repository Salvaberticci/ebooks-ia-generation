<?php
if (!class_exists('RateLimitException')) {
    class RateLimitException extends Exception {
        private $waitTime = null;
        public function __construct($message = '', $waitTime = null, $code = 0, Throwable $previous = null) {
            parent::__construct($message, $code, $previous);
            $this->waitTime = $waitTime;
        }
        public function getWaitTime() { return $this->waitTime; }
    }
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
                $wait = $e->getWaitTime() ?? ($attempts * 10);
                sleep($wait);
            }
        }

        throw new Exception('No se pudo obtener respuesta de Groq');
    }

    private static function call($payload)
    {
        $ch = curl_init(GROQ_API_BASE . '/chat/completions');
        $responseHeaders = [];
        $verbose = fopen('php://temp', 'w+');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . GROQ_API_KEY,
                'Content-Type: application/json',
                'Connection: keep-alive',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_VERBOSE => true,
            CURLOPT_STDERR => $verbose,
            CURLOPT_HEADERFUNCTION => function($curl, $header) use (&$responseHeaders) {
                $responseHeaders[] = $header;
                return strlen($header);
            },
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        rewind($verbose);
        $verboseLog = stream_get_contents($verbose);
        fclose($verbose);
        curl_close($ch);

        $keyPreview = substr(GROQ_API_KEY, 0, 8) . '...';
        $log = "Key: {$keyPreview} | DNS: " . implode(',', gethostbynamel(parse_url(GROQ_API_BASE, PHP_URL_HOST)) ?: ['?']) . " | Curl: {$error}";
        error_log("[Groq Debug] {$log}");
        error_log("[Groq Verbose] {$verboseLog}");

        if ($error) {
            throw new Exception("Error de conexion ({$log}): {$error}");
        }

        if ($httpCode === 429) {
            $retryAfter = null;
            foreach ($responseHeaders as $header) {
                if (preg_match('/Retry-After:\s*(\d+)/i', $header, $m)) {
                    $retryAfter = (int)$m[1];
                    break;
                }
            }
            throw new RateLimitException('Limite de velocidad alcanzado', $retryAfter);
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
