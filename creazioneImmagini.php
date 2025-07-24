<?php
session_start();

if (!isset($_SESSION['busy'])) $_SESSION['busy'] = false;
if (!isset($_SESSION['count'])) $_SESSION['count'] = 0;

$imageUrl = null;-+
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$_SESSION['busy']) {
    if ($_SESSION['count'] >= 10) {
        $error = "Hai raggiunto il limite di 10 immagini generate per sessione.";
    } else {
        $prompt = trim($_POST['prompt'] ?? '');
        if ($prompt === '') {
            $error = "Inserisci un prompt.";
        } else {
            $_SESSION['busy'] = true;

            $base = 'https://image.pollinations.ai/prompt/';
            $p = rawurlencode($prompt);
            $url = "{$base}{$p}?width=512&height=512&nologo=true";

            $response = @file_get_contents($url);
            $http = $http_response_header[0] ?? '';

            if (strpos($http, '200') === false || !$response) {
                $error = "Errore HTTP: {$http}";
            } else {
                $b64 = base64_encode($response);
                $imageUrl = "data:image/png;base64,{$b64}";
                $_SESSION['count']++;
            }

            $_SESSION['busy'] = false;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8" />
    <title>Penguin AI ChatBot</title>
    <style>
        /* Stessi stili di prima (tagliati qui per brevità, ma vanno messi tutti come prima) */
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(to bottom, #a6d9f7, #e0f0ff);
            margin: 0;
            padding: 0 0 60px 0;
            color: #003366;
            user-select: none;
        }
        header {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 60px;
            background-color: #3a00ff;
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
            z-index: 1000;
            box-shadow: 0 2px 8px rgba(0,0,0,0.25);
        }
        #logo {
            height: 40px;
            width: 40px;
            border-radius: 50%;
            background: white url('https://upload.wikimedia.org/wikipedia/commons/1/12/Penguin_icon.svg') no-repeat center/contain;
            cursor: pointer;
            flex-shrink: 0;
        }
        #menuToggle {
            position: relative;
            width: 30px;
            height: 22px;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        #menuToggle span {
            display: block;
            height: 4px;
            background: white;
            border-radius: 2px;
        }
        #menu {
            position: fixed;
            top: 60px;
            right: 0;
            background: #3a00ff;
            width: 220px;
            box-shadow: -2px 0 8px rgba(0,0,0,0.25);
            border-bottom-left-radius: 10px;
            border-top-left-radius: 10px;
            display: none;
            flex-direction: column;
            padding: 10px 0;
            z-index: 1001;
        }
        #menu a {
            color: white;
            padding: 12px 20px;
            text-decoration: none;
            font-weight: 600;
            transition: background-color 0.3s;
        }
        #menu a:hover {
            background-color: #5600ff;
        }
        #menuToggle.active + #menu {
            display: flex;
        }

        main {
            padding: 100px 20px 20px 20px;
            max-width: 700px;
            margin: auto;
            text-align: center;
        }

        h2 {
            font-weight: 900;
            text-shadow: 0 0 8px #0099ff;
            margin-bottom: 6px;
        }

        #description {
            font-size: 1.15rem;
            color: #004a99;
            margin-bottom: 20px;
            line-height: 1.5em;
            max-width: 650px;
            margin-left: auto;
            margin-right: auto;
        }

        #imageGeneratorForm {
            margin-top: 30px;
        }
        #imagePrompt {
            width: 100%;
            padding: 14px 20px;
            font-size: 18px;
            border-radius: 12px;
            border: 2px solid transparent;
            background: linear-gradient(135deg, #cce6ff, #f0fbff);
            box-shadow:
                inset 0 0 8px #80bfff,
                0 4px 10px rgba(0, 153, 255, 0.3);
            outline: none;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
            margin-bottom: 12px;
        }
        #imagePrompt:focus {
            border-color: #0099ff;
            box-shadow:
                inset 0 0 12px #3399ff,
                0 6px 14px rgba(0, 153, 255, 0.5);
        }
        #generateImageBtn {
            background-color: #0099ff;
            border: none;
            color: white;
            padding: 14px 28px;
            font-size: 18px;
            border-radius: 12px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            user-select: none;
            box-shadow: 0 4px 12px rgba(0,153,255,0.6);
        }
        #generateImageBtn:hover {
            background-color: #007acc;
            box-shadow: 0 6px 16px rgba(0,122,204,0.8);
        }

        #imageResult {
            margin-top: 20px;
            max-width: 100%;
            border-radius: 10px;
            box-shadow: 0 0 15px #4fc3f7;
        }

        .error {
            color: #ff4444;
            font-weight: 700;
            margin-top: 10px;
        }

        footer {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 40px;
            background-color: #3a00ff;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            box-shadow: 0 -2px 8px rgba(0,0,0,0.25);
            user-select: none;
            font-size: 14px;
        }

        @media (max-width: 480px) {
            main {
                padding: 100px 10px 20px 10px;
            }
            #imagePrompt {
                font-size: 16px;
            }
            #generateImageBtn {
                font-size: 16px;
                padding: 12px 20px;
            }
        }
    </style>
</head>
<body>

<header>
    <a href="/" id="logo" title="Homepage Penguin AI"></a>
    <div id="menuToggle" aria-label="Menu hamburger" role="button" tabindex="0" aria-expanded="false" aria-controls="menu">
        <span></span>
        <span></span>
        <span></span>
    </div>
    <nav id="menu" role="menu" aria-hidden="true">
        <a href="/" role="menuitem" tabindex="-1">Generazione Testi</a>
        <a href="./creazioneImmagini.php" role="menuitem" tabindex="-1">Generazione Immagini</a>
    </nav>
</header>

<main>
    <h2>🐧 Penguin AI Generatore Immagini</h2>

    <p id="description">
        Scrivi una descrizione per l'immagine che vuoi generare e premi "Genera Immagine".<br>
        Penguin AI creerà un'immagine in base alla tua descrizione utilizzando il modello Gemma 3.
    </p>

    <?php if ($_SESSION['busy']): ?>
        <p>⏳ Generazione in corso...</p>
    <?php endif; ?>

    <form id="imageGeneratorForm" action="" method="POST" aria-label="Generatore di immagini" autocomplete="off">
        <input type="text" id="imagePrompt" name="prompt" placeholder="Descrivi l'immagine da generare..." required aria-required="true" />
        <br>
        <button type="submit" id="generateImageBtn" <?= $_SESSION['busy'] ? 'disabled' : '' ?>>Genera Immagine</button>
    </form>

    <?php if ($error): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <?php if ($imageUrl): ?>
        <div id="imageResult" aria-live="polite" aria-label="Risultato immagine">
            <h3>Immagine generata:</h3>
            <img src="<?= htmlspecialchars($imageUrl) ?>" alt="Immagine generata da AI" />
            <style>
                /* ...tutto il CSS precedente rimane invariato... */

#downloadBtn {
    background-color: #007acc;
    border: none;
    color: white;
    padding: 10px 22px;
    font-size: 16px;
    border-radius: 10px;
    cursor: pointer;
    margin-top: 12px;
    box-shadow: 0 4px 12px rgba(0, 122, 204, 0.8);
    transition: background-color 0.3s ease;
    user-select: none;
    display: inline-block;
    text-decoration: none;
}

#downloadBtn:hover {
    background-color: #005fa3;
}

            </style>
            <a id="downloadBtn" href="<?= htmlspecialchars($imageUrl) ?>" download="penguin_ai_image.png" aria-label="Scarica immagine generata">
    Scarica Immagine
</a>

            <p><small>Immagini generate in questa sessione: <?= $_SESSION['count'] ?>/10</small></p>
        </div>
    <?php endif; ?>
</main>

<footer>
    &copy; 2025 Penguin AI - Tutti i diritti riservati
</footer>

<script>
    const menuToggle = document.getElementById('menuToggle');
    const menu = document.getElementById('menu');

    menuToggle.addEventListener('click', () => {
        const expanded = menuToggle.getAttribute('aria-expanded') === 'true';
        menuToggle.setAttribute('aria-expanded', !expanded);
        menuToggle.classList.toggle('active');
        menu.setAttribute('aria-hidden', expanded);
    });

    menuToggle.addEventListener('keydown', e => {
        if(e.key === ' ' || e.key === 'Enter') {
            e.preventDefault();
            menuToggle.click();
        }
    });
</script>

</body>
</html>
