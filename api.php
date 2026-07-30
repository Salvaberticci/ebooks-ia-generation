<?php
require_once __DIR__ . '/api_huggingface.php';

class API
{
    public static function generateText($prompt)
    {
        if (AI_PROVIDER === 'groq') {
            require_once __DIR__ . '/api_groq.php';
            return GroqAPI::generateText($prompt);
        }

        if (AI_PROVIDER === 'gemini') {
            require_once __DIR__ . '/api_gemini.php';
            if (empty(GEMINI_API_KEY)) {
                throw new Exception(
                    'Falta GEMINI_API_KEY en config.php. '
                    . 'Obtenela gratis en https://aistudio.google.com/app/apikey'
                );
            }
            return GeminiAPI::generateText($prompt);
        }

        if (AI_PROVIDER === 'huggingface') {
            return HuggingFaceAPI::generateText($prompt);
        }

        throw new Exception('AI_PROVIDER desconocido: ' . AI_PROVIDER);
    }

    public static function generateImagePlaceholder($chapterTitle, $tema, $theme = 'cyberpunk', $customColors = [], $imgStyle = 'geometric')
    {
        if (AI_PROVIDER === 'groq') {
            require_once __DIR__ . '/api_groq.php';
            return GroqAPI::generateImagePlaceholder($chapterTitle, $tema, $theme, $customColors, $imgStyle);
        }
        return HuggingFaceAPI::generateChapterImage($chapterTitle, $tema, $theme, $customColors, $imgStyle);
    }

    public static function generateAIImage($prompt)
    {
        throw new Exception(
            'AI image generation not configured. '
            . 'Set USE_AI_IMAGES = true in config.php and implement the provider.'
        );
    }
}
