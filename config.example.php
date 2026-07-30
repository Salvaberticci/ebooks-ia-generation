<?php
define('AI_PROVIDER', 'groq');
define('GROQ_API_KEY', 'tu-api-key-de-groq-aqui');
define('GROQ_API_BASE', 'https://api.groq.com/openai/v1');
define('GROQ_MODEL', 'llama-3.3-70b-versatile');
define('HF_TOKEN', 'tu-token-de-huggingface-aqui');
define('HF_API_BASE', 'https://router.huggingface.co/hf-inference');
define('HF_MODEL_TEXT', 'meta-llama/Meta-Llama-3-70B-Instruct');
define('GEMINI_API_KEY', '');

define('MAX_CHAPTERS', 10);
define('MIN_CHAPTERS', 2);
define('DEFAULT_CHAPTERS', 5);
define('WORDS_PER_CHAPTER', 300);
define('API_TIMEOUT', 180);
define('MAX_RETRIES', 3);
define('USE_AI_IMAGES', false);
define('COVER_IMAGE_WIDTH', 1200);
define('COVER_IMAGE_HEIGHT', 1600);
define('POLLINATIONS_API_URL', 'https://image.pollinations.ai/prompt');
define('POLLINATIONS_TIMEOUT', 90);

$THEME_STYLE_PROMPTS = [
    'cyberpunk' => 'dark purple and black background with neon pink and cyan accents, cyberpunk aesthetic, futuristic',
    'dark_elegant' => 'dark elegant background with purple and blue gradients, sophisticated, premium',
    'minimalist' => 'clean white and light gray background with subtle dark elements, minimalist, zen',
    'nature' => 'dark green background with vibrant green and emerald accents, natural, organic',
    'sunset' => 'warm dark background with orange and red gradients, sunset colors, dramatic',
    'ocean' => 'deep blue background with bright cyan and azure accents, oceanic, serene',
    'corporate' => 'dark navy background with clean blue accents, professional, corporate',
    'salvatechnology' => 'deep black background with vibrant orange accents, hacker terminal aesthetic, gaming UI elements, tech noir',
];

$COLOR_PALETTES = [
    'cyberpunk' => ['name' => 'Cyberpunk', 'bg' => '#0f0c29', 'accent' => '#ff00ff', 'accent2' => '#00ffff', 'text' => '#e0e0e0', 'header' => '#ff00ff', 'page' => '#0f0c29'],
    'dark_elegant' => ['name' => 'Dark Elegant', 'bg' => '#1a1a2e', 'accent' => '#9d4edd', 'accent2' => '#0f3460', 'text' => '#d8e2e8', 'header' => '#e94560', 'page' => '#16213e'],
    'minimalist' => ['name' => 'Minimalist', 'bg' => '#ffffff', 'accent' => '#2d3436', 'accent2' => '#636e72', 'text' => '#2d3436', 'header' => '#00b894', 'page' => '#f9f9f9'],
    'nature' => ['name' => 'Nature', 'bg' => '#1b4332', 'accent' => '#95d5b2', 'accent2' => '#2d6a4f', 'text' => '#e9ecef', 'header' => '#52b788', 'page' => '#d8f3dc'],
    'sunset' => ['name' => 'Sunset', 'bg' => '#3d0000', 'accent' => '#ff6b35', 'accent2' => '#ff8c42', 'text' => '#fff5e6', 'header' => '#ff9f1c', 'page' => '#2a0505'],
    'ocean' => ['name' => 'Ocean', 'bg' => '#011627', 'accent' => '#00f5d4', 'accent2' => '#0083c7', 'text' => '#a9bcd0', 'header' => '#00b4d8', 'page' => '#012a52'],
    'corporate' => ['name' => 'Corporate', 'bg' => '#0f172a', 'accent' => '#3b82f6', 'accent2' => '#2563eb', 'text' => '#e2e8f0', 'header' => '#60a5fa', 'page' => '#1e293b'],
    'salvatechnology' => ['name' => 'Salvatechnology', 'bg' => '#121212', 'accent' => '#ff8c00', 'accent2' => '#ffb432', 'text' => '#dcdcdc', 'header' => '#ff8c00', 'page' => '#121212'],
];

// Importante: Renombra este archivo a config.php y reemplaza las API keys
