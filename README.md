# 📚 Generador de Ebooks con IA

Sistema web en PHP que genera ebooks completos usando **Groq** (texto) y **Pollinations.ai** (imágenes), con salida en **PDF profesional via TCPDF**.

## ✨ Características

- **Contenido generado por IA** — usa Groq (Llama 3.3 70B) para crear índices, capítulos detallados y prompts de imagen contextuales
- **Imágenes con IA** — cada capítulo incluye una imagen generada por Pollinations.ai que representa visualmente el contenido del capítulo
- **Temas visuales** — 8 temas prediseñados (Cyberpunk, Dark Elegant, Minimalist, Nature, Sunset, Ocean, Corporate, Salvatechnology) con paletas de colores coordinadas
- **Paletas de colores** — 12 paletas predefinidas que se aplican al diseño del PDF, portada e imágenes
- **Colores personalizables** — opción de sobrescribir la paleta con colores propios
- **Estilos de imagen** — 6 estilos visuales para las ilustraciones (Geométrico, Abstracto, Gradientes, Partículas, Minimal, Retro)
- **PDF profesional** — portada con diseño, índice, capítulos numerados, imágenes incorporadas, márgenes profesionales
- **Multilenguaje** — genera en español, inglés o portugués
- **100% gratuito** — todas las APIs usadas tienen tier gratuito

## 🚀 Requisitos

- PHP 8.0+
- XAMPP / Apache + PHP
- Extensión curl habilitada
- Extensión gd habilitada
- Extensión mbstring habilitada
- [Composer](https://getcomposer.org/)

## 📦 Instalación

```bash
# Clonar el repositorio
git clone https://github.com/Salvaberticci/ebooks-ia-generation.git

# Entrar al directorio
cd ebooks-ia-generation

# Instalar dependencias (TCPDF)
composer install

# Configurar API keys
cp config.example.php config.php
# Editar config.php con tus API keys
```

## 🔑 APIs Necesarias

| API | Propósito | Cómo obtenerla |
|-----|-----------|----------------|
| **Groq** | Generación de texto (índice, capítulos, prompts) | [console.groq.com](https://console.groq.com) — gratis, 30 req/min |
| **Pollinations.ai** | Generación de imágenes | No requiere API key — uso gratuito ilimitado |

Editar `config.php`:

```php
define('GROQ_API_KEY', 'gsk_tu-api-key-aqui');
```

## 🖥️ Uso

1. Iniciar Apache desde XAMPP
2. Abrir en el navegador: `http://localhost/ebooks-ia-generation/`
3. Completar el formulario:
   - **Título** del ebook (aparece en la portada)
   - **Descripción** del contenido (ayuda a la IA a generar mejor)
   - **Tema** del ebook
   - **Cantidad de capítulos** (2-10)
   - **Tema visual** / **Paleta de colores**
   - **Estilo de imágenes**
4. Click en **Generar Ebook**
5. El sistema genera: índice → contenido de cada capítulo → imágenes → PDF
6. Descargar el PDF generado

## 📁 Estructura del Proyecto

```
├── index.php              # Formulario de configuración
├── generar.php            # Orquestador del proceso
├── config.php             # Configuración y API keys
├── config.example.php     # Template de configuración
├── api.php                # Fachada unificada de APIs
├── api_groq.php           # Cliente Groq
├── api_huggingface.php    # Cliente HuggingFace + Pollinations + GD
├── api_gemini.php         # Cliente Google Gemini (fallback)
├── pdf_generator.php      # Generación de PDF con TCPDF
├── composer.json          # Dependencias PHP
├── vendor/                # Dependencias instaladas
└── generados/             # PDFs generados
```

## 🎨 Temas Visuales

| Tema | Estilo |
|------|--------|
| Cyberpunk | Morado oscuro, neón rosa y cian |
| Dark Elegant | Fondo oscuro, acentos violeta y azul |
| Minimalist | Blanco/gris, líneas limpias |
| Nature | Verde esmeralda, orgánico |
| Sunset | Naranja/rojo, atardecer |
| Ocean | Azul profundo, cian y azul brillante |
| Corporate | Azul marino, profesional |
| Salvatechnology | Negro/naranja, hacker, gaming |

## 🖼️ Estilos de Imagen

- Geométrico — formas y polígonos
- Abstracto — arte abstracto fluido
- Gradientes — transiciones de color suaves
- Partículas — puntos y destellos
- Minimal — diseño simple y elegante
- Retro — estilo synthwave / ochentero

## ⚙️ Configuración Adicional

En `config.php` se pueden ajustar:

```php
define('MAX_CHAPTERS', 10);       // Máximo de capítulos
define('MIN_CHAPTERS', 2);        // Mínimo de capítulos  
define('WORDS_PER_CHAPTER', 300); // Palabras por capítulo
define('COVER_IMAGE_WIDTH', 1200); // Ancho de imagen de portada
define('COVER_IMAGE_HEIGHT', 1600); // Alto de imagen de portada
```

## 📄 Licencia

MIT
