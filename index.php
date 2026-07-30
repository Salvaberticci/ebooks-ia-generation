<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Generador de Ebooks con IA</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body {
    font-family:'Segoe UI',Tahoma,sans-serif;
    background:linear-gradient(135deg,#0f0c29,#302b63,#24243e);
    min-height:100vh; padding:30px 20px;
}
.container {
    max-width:720px; margin:0 auto;
    background:rgba(255,255,255,0.05); backdrop-filter:blur(20px);
    border:1px solid rgba(255,255,255,0.1); border-radius:20px;
    padding:40px; box-shadow:0 25px 60px rgba(0,0,0,0.5);
}
h1 { color:#fff; font-size:26px; text-align:center; margin-bottom:4px; }
.subtitle { color:rgba(255,255,255,0.45); text-align:center; margin-bottom:28px; font-size:13px; }
.form-group { margin-bottom:18px; }
label { display:block; color:rgba(255,255,255,0.8); font-size:11px; font-weight:600; margin-bottom:4px; text-transform:uppercase; letter-spacing:0.5px; }
input[type="text"], input[type="number"], select, textarea {
    width:100%; padding:10px 14px; background:rgba(255,255,255,0.07);
    border:1px solid rgba(255,255,255,0.1); border-radius:8px; color:#fff;
    font-size:14px; outline:none; transition:all 0.3s; font-family:inherit;
}
textarea { min-height:70px; resize:vertical; }
input:focus, select:focus, textarea:focus { border-color:#6c63ff; background:rgba(255,255,255,0.1); }
input::placeholder, textarea::placeholder { color:rgba(255,255,255,0.2); }
select option { background:#1a1a3e; color:#fff; }
.row { display:flex; gap:12px; }
.row .form-group { flex:1; }
.checkbox-group { display:flex; align-items:center; gap:10px; }
.checkbox-group input[type="checkbox"] { width:16px; height:16px; accent-color:#6c63ff; cursor:pointer; }
.checkbox-group label { margin:0; text-transform:none; letter-spacing:0; cursor:pointer; }

/* Theme grid */
.theme-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:8px; }
.theme-option { position:relative; cursor:pointer; border-radius:8px; overflow:hidden; border:2px solid transparent; transition:all 0.2s; padding:2px; }
.theme-option:hover { border-color:rgba(255,255,255,0.3); }
.theme-option input { position:absolute; opacity:0; }
.theme-option input:checked + .theme-card { border-color:#6c63ff; }
.theme-card { border-radius:6px; padding:8px; border:2px solid transparent; }
.theme-preview { height:36px; border-radius:4px; display:flex; align-items:flex-end; padding:4px; gap:3px; }
.theme-preview .bar { width:20%; height:10px; border-radius:2px; }
.theme-preview .bar:nth-child(1) { height:35%; }
.theme-preview .bar:nth-child(2) { height:55%; }
.theme-preview .bar:nth-child(3) { height:45%; }
.theme-preview .bar:nth-child(4) { height:70%; }
.theme-name { font-size:10px; color:rgba(255,255,255,0.6); margin-top:4px; text-align:center; font-weight:600; text-transform:uppercase; letter-spacing:0.2px; }

/* Color picker section */
.color-section { margin-top:12px; }
.color-toggle { background:rgba(255,255,255,0.05); border:1px dashed rgba(255,255,255,0.15); border-radius:8px; padding:8px 12px; cursor:pointer; color:rgba(255,255,255,0.5); font-size:12px; text-align:center; transition:all 0.2s; }
.color-toggle:hover { background:rgba(255,255,255,0.08); border-color:rgba(255,255,255,0.25); }
.color-pickers { display:none; margin-top:10px; }
.color-pickers.show { display:block; }
.color-row { display:flex; gap:12px; flex-wrap:wrap; }
.color-item { flex:1; min-width:100px; }
.color-item label { font-size:10px; text-transform:none; color:rgba(255,255,255,0.5); margin-bottom:2px; }
.color-item input[type="color"] { width:100%; height:36px; padding:2px; border-radius:6px; cursor:pointer; background:transparent; border:1px solid rgba(255,255,255,0.15); }

/* Image style buttons */
.style-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:6px; }
.style-option { position:relative; cursor:pointer; }
.style-option input { position:absolute; opacity:0; }
.style-btn {
    display:block; padding:7px; border-radius:6px; text-align:center;
    font-size:11px; font-weight:600; color:rgba(255,255,255,0.6);
    background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1);
    transition:all 0.2s; text-transform:uppercase; letter-spacing:0.3px;
}
.style-option input:checked + .style-btn { background:#6c63ff; color:#fff; border-color:#6c63ff; }
.style-option:hover .style-btn { border-color:rgba(255,255,255,0.3); }

button {
    width:100%; padding:13px;
    background:linear-gradient(135deg,#6c63ff,#e94560);
    border:none; border-radius:8px; color:#fff; font-size:15px;
    font-weight:700; cursor:pointer; transition:all 0.2s; letter-spacing:0.5px;
    margin-top:8px;
}
button:hover { transform:translateY(-2px); box-shadow:0 10px 30px rgba(108,99,255,0.4); }
.loading { display:none; text-align:center; margin-top:16px; }
.spinner { width:32px; height:32px; border:3px solid rgba(255,255,255,0.1); border-top-color:#6c63ff; border-radius:50%; animation:spin 0.8s linear infinite; margin:0 auto 6px; }
@keyframes spin { to { transform:rotate(360deg); } }
.loading p { color:rgba(255,255,255,0.45); font-size:12px; }
.info { margin-top:18px; padding:10px 14px; background:rgba(108,99,255,0.08); border:1px solid rgba(108,99,255,0.12); border-radius:8px; font-size:11px; color:rgba(255,255,255,0.4); text-align:center; line-height:1.6; }
.section-label { font-size:10px; color:rgba(255,255,255,0.35); margin:16px 0 8px; text-transform:uppercase; letter-spacing:1px; text-align:center; border-top:1px solid rgba(255,255,255,0.06); padding-top:14px; }

<?php
require_once __DIR__ . '/config.php';
foreach ($THEMES as $id => $t):
    $c1 = "{$t['cover_bg1'][0]},{$t['cover_bg1'][1]},{$t['cover_bg1'][2]}";
    $c2 = "{$t['cover_bg2'][0]},{$t['cover_bg2'][1]},{$t['cover_bg2'][2]}";
    $ac = "{$t['accent'][0]},{$t['accent'][1]},{$t['accent'][2]}";
    $a2 = "{$t['accent2'][0]},{$t['accent2'][1]},{$t['accent2'][2]}";
    $tx = "{$t['text'][0]},{$t['text'][1]},{$t['text'][2]}";
?>
.theme-preview-<?=$id?> { background:linear-gradient(135deg,rgb(<?=$c1?>),rgb(<?=$c2?>)); }
.theme-preview-<?=$id?> .bar { background:rgb(<?=$ac?>); }
.theme-preview-<?=$id?> .bar:nth-child(odd) { background:rgb(<?=$a2?>); }
.theme-card-<?=$id?> { background:rgb(<?=$c1?>); }
.theme-card-<?=$id?> .theme-name { color:rgb(<?=$tx?>); }
<?php endforeach; ?>
</style>
</head>
<body>

<div class="container">
    <h1>Generador de Ebooks con IA</h1>
    <p class="subtitle">Personaliza el contenido y el estilo visual de tu ebook</p>

    <form action="generar.php" method="POST" onsubmit="showLoading()">

        <div class="form-group">
            <label>Titulo del ebook (aparece en la portada)</label>
            <input type="text" name="titulo" required
                   placeholder="Ej: Python Desde Cero: La Guia Completa">
        </div>

        <div class="form-group">
            <label>Descripcion del contenido</label>
            <textarea name="descripcion" placeholder="Describe de que trata el ebook, que temas cubre, que va a aprender el lector. Esto ayuda a la IA a generar mejor contenido..."></textarea>
        </div>

        <div class="row">
            <div class="form-group">
                <label>Tema / Area</label>
                <input type="text" name="tema" placeholder="Ej: Programacion, Marketing, Historia..."
                       autocomplete="off">
            </div>
            <div class="form-group">
                <label>Capitulos</label>
                <input type="number" name="capitulos" min="2" max="10" value="5">
            </div>
        </div>

        <div class="row">
            <div class="form-group">
                <label>Idioma</label>
                <select name="idioma">
                    <option value="es">Espanol</option>
                    <option value="en">English</option>
                    <option value="pt">Portugues</option>
                </select>
            </div>
            <div class="form-group">
                <label>Estilo de imagenes</label>
                <select name="img_style">
                    <?php foreach ($IMAGE_STYLES as $k => $v): ?>
                    <option value="<?=$k?>"><?=$v?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label>Tema visual del ebook</label>
            <div class="theme-grid">
                <?php $first=true; foreach ($THEMES as $id=>$t): ?>
                <label class="theme-option">
                    <input type="radio" name="theme" value="<?=$id?>" <?=$first?'checked':''?>>
                    <div class="theme-card theme-card-<?=$id?>">
                        <div class="theme-preview theme-preview-<?=$id?>"><div class="bar"></div><div class="bar"></div><div class="bar"></div><div class="bar"></div></div>
                        <div class="theme-name"><?=$t['name']?></div>
                    </div>
                </label>
                <?php $first=false; endforeach; ?>
                <?php foreach ($COLOR_PALETTES as $id=>$p): ?>
                <label class="theme-option">
                    <input type="radio" name="color_palette" value="<?=$id?>" onchange="toggleColorPickers(false)">
                    <div class="theme-card color-palette-card" style="background: linear-gradient(135deg, <?=$p['bg']?>, <?=$p['accent']?>, <?=$p['accent2']?>) !important;">
                        <div class="theme-preview"><div class="bar" style="background: <?=$p['accent']?>"></div><div class="bar" style="background: <?=$p['accent2']?>"></div><div class="bar" style="background: <?=$p['header']?>"></div><div class="bar" style="background: <?=$p['text']?>"></div></div>
                    <div class="theme-name" style="color: white; mix-blend-mode: difference;"><?=$p['name']?></div>
                    </div>
                </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="color-section">
            <div class="color-toggle" onclick="toggleColorPickers(true)">+ Colores personalizados (sobrescribir paleta)</div>
            <div class="color-pickers" id="colorPickers">
                <div class="color-row">
                    <div class="color-item"><label>Fondo portada</label><input type="color" name="custom_bg" id="cp_bg"></div>
                    <div class="color-item"><label>Acento principal</label><input type="color" name="custom_accent" id="cp_accent"></div>
                    <div class="color-item"><label>Acento secundario</label><input type="color" name="custom_accent2" id="cp_accent2"></div>
                </div>
                <div class="color-row" style="margin-top:8px;">
                    <div class="color-item"><label>Color de texto</label><input type="color" name="custom_text" id="cp_text"></div>
                    <div class="color-item"><label>Titulos</label><input type="color" name="custom_header" id="cp_header"></div>
                    <div class="color-item"><label>Fondo de pagina</label><input type="color" name="custom_bg_page" id="cp_bg_page"></div>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="checkbox-group">
                <input type="checkbox" name="imagenes" id="imagenes" checked>
                <label for="imagenes">Incluir imagenes en cada capitulo</label>
            </div>
        </div>

        <button type="submit">Generar Ebook</button>
    </form>

    <div class="loading" id="loading">
        <div class="spinner"></div>
        <p>Generando ebook... esto puede tomar 1-3 minutos</p>
    </div>
    <div class="info">Usa Groq (gratis) con Llama 3.3 70B. Las imagenes se generan con IA via Pollinations.ai.</div>
</div>

<script>
function toggleColorPickers(show) {
    const cp = document.getElementById('colorPickers');
    if (show !== undefined) {
        cp.classList.toggle('show');
    } else {
        cp.style.display = cp.style.display === 'block' ? 'none' : 'block';
    }
}
function showLoading(){document.getElementById('loading').style.display='block';}
</script>
</body>
</html>
