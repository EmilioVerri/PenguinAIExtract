<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $data = json_decode(file_get_contents('php://input'), true);
    $message = trim($data['message'] ?? '');

    if (!is_string($message) || $message === '') {
        echo json_encode(["reply" => "Messaggio non valido o vuoto."]);
        exit;
    }

    if (strlen($message) > 500) {
        echo json_encode(["reply" => "Messaggio troppo lungo. Massimo 500 caratteri."]);
        exit;
    }

    // Sanitizza input
    $message = strip_tags($message);
    $message = preg_replace('/[^\PC\s]/u', '', $message);

    // Parametri OpenRouter
    $api_key = "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"; // <-- inserisci qui la tua API key!
    $model = "google/gemma-3n-e4b-it:free";

    $postData = [
        "model" => $model,
        "messages" => [
            ["role" => "user", "content" => $message]
        ]
    ];

    $ch = curl_init("https://openrouter.ai/api/v1/chat/completions");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer $api_key"
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 120);

    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        echo json_encode(["reply" => "Errore di connessione CURL: $curl_error"]);
        exit;
    }

    if ($httpcode !== 200) {
        echo json_encode(["reply" => "Errore API OpenRouter: HTTP $httpcode"]);
        exit;
    }

    $json = json_decode($response, true);

    if (!isset($json['choices'][0]['message']['content'])) {
        echo json_encode(["reply" => "Risposta API non valida o vuota."]);
        exit;
    }

    $reply = trim($json['choices'][0]['message']['content']);

    echo json_encode(["reply" => $reply]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8" />
    <title>Penguin AI ChatBot</title>
    <link rel="shortcut icon" href="./images/favicon.ico" type="image/x-icon" />
    <style>
        /* Qui la tua grafica originale, copiata pari pari */
        * { box-sizing: border-box; }
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
            top: 0; left: 0; width: 100%; height: 60px;
            background-color: #3a00ff; color: white;
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 20px; z-index: 1000;
            box-shadow: 0 2px 8px rgba(0,0,0,0.25);
        }
        #logo {
            height: 40px; width: 40px; border-radius: 50%;
            background: white url('./images/profilo.png') no-repeat center/contain;
            cursor: pointer; flex-shrink: 0;
        }
        #menuToggle {
            position: relative; width: 30px; height: 22px;
            cursor: pointer;
            display: flex; flex-direction: column; justify-content: space-between;
        }
        #menuToggle span {
            display: block; height: 4px; background: white; border-radius: 2px;
        }
        #menu {
            position: fixed; top: 60px; right: 0;
            background: #3a00ff; width: 220px;
            box-shadow: -2px 0 8px rgba(0,0,0,0.25);
            border-bottom-left-radius: 10px; border-top-left-radius: 10px;
            display: none; flex-direction: column; padding: 10px 0; z-index: 1001;
        }
        #menu a {
            color: white; padding: 12px 20px;
            text-decoration: none; font-weight: 600;
            transition: background-color 0.3s;
        }
        #menu a:hover { background-color: #5600ff; }
        #menuToggle.active + #menu { display: flex; }
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
        #chatbox {
            height: 400px;
            border: 2px solid #0099ff;
            border-radius: 10px;
            padding: 15px;
            overflow-y: auto;
            background-color: #f0f9ff;
            box-shadow: 0 0 15px #4fc3f7;
            margin-bottom: 10px;
            text-align: left;
        }
        .message {
            margin: 8px 0;
            line-height: 1.3em;
        }
        .user {
            color: #004a99;
            font-weight: 700;
        }
        .bot {
            color: #0277bd;
            font-weight: 700;
        }
        .sender {
            font-weight: 900;
            margin-right: 6px;
        }
        .bot::before {
            content: "🐧 ";
        }
        #inputArea {
            display: flex;
            gap: 10px;
        }
        #message {
            flex: 1;
            padding: 14px 20px;
            font-size: 18px;
            border-radius: 12px;
            border: 2px solid transparent;
            background: linear-gradient(135deg, #cce6ff, #f0fbff);
            box-shadow: inset 0 0 8px #80bfff, 0 4px 10px rgba(0, 120, 255, 0.25);
            transition: border-color 0.3s;
            outline-offset: 3px;
        }
        #message:focus {
            border-color: #0099ff;
            box-shadow: inset 0 0 8px #409fff, 0 0 12px #3399ff;
            outline: none;
        }
        #sendBtn {
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 50px;
            padding: 0 20px;
            cursor: pointer;
            font-weight: 900;
            font-size: 1.2rem;
            transition: background-color 0.3s;
            user-select: none;
        }
        #sendBtn:hover {
            background-color: #0056b3;
        }
        #sendBtn:disabled {
            background-color: #a0c6ff;
            cursor: not-allowed;
        }
        footer {
            text-align: center;
            font-size: 0.8rem;
            color: #003366cc;
            margin-top: 20px;
        }
    </style>
</head>
<body>
<header>
    <div id="logo" title="Torna alla home"></div>
    <div id="menuToggle" aria-label="Apri menu" role="button" tabindex="0">
        <span></span><span></span><span></span>
    </div>
    <nav id="menu" aria-label="Menu principale">
        <a href="./" title="Generazione Testi">Generazione Testi</a>
        <a href="./creazioneImmagini.php" title="Generazioni Immagini">Generazione Immagini</a>
    </nav>
</header>

<main>
    <h2>Penguin AI ChatBot</h2>
    <div id="description">
        Questo è un prototipo di chatbot che utilizza un modello AI per rispondere alle tue domande. Scrivi qualcosa e premi "Invia".
    </div>
    <div id="chatbox" aria-live="polite" aria-relevant="additions"></div>

    <form id="chatForm" autocomplete="off" aria-label="Modulo chat">
        <div id="inputArea">
            <input type="text" id="message" name="message" placeholder="Scrivi qui la tua domanda" aria-required="true" aria-describedby="helpText" />
            <button type="submit" id="sendBtn" disabled>Invia</button>
        </div>
        <small id="helpText" style="display:block; color:#004a99; margin-top:5px;">
            Usa massimo 500 caratteri. Attendi risposta prima di inviare un nuovo messaggio.
        </small>
    </form>
</main>

<footer>
     &copy; 2025 Penguin AI - Tutti i diritti riservati -&nbsp;<a href="https://emilioverri.altervista.org/" target="_blank" style="color: white;">Emilio Verri</a>
</footer>

<script>
(() => {
    const menuToggle = document.getElementById('menuToggle');
    const menu = document.getElementById('menu');
    const logo = document.getElementById('logo');
    const chatForm = document.getElementById('chatForm');
    const messageInput = document.getElementById('message');
    const sendBtn = document.getElementById('sendBtn');
    const chatbox = document.getElementById('chatbox');

    let waitingResponse = false;

    function toggleMenu() {
        menuToggle.classList.toggle('active');
        menu.style.display = menuToggle.classList.contains('active') ? 'flex' : 'none';
    }

    menuToggle.addEventListener('click', toggleMenu);
    menuToggle.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            toggleMenu();
        }
    });

    logo.addEventListener('click', () => {
        location.reload();
    });

    messageInput.addEventListener('input', () => {
        sendBtn.disabled = messageInput.value.trim() === '' || waitingResponse;
    });

    function appendMessage(sender, text) {
        const div = document.createElement('div');
        div.className = 'message ' + sender;
        div.innerHTML = `<span class="sender">${sender === 'user' ? 'Tu:' : 'Bot:'}</span>${text.replace(/\n/g, '<br>')}`;
        chatbox.appendChild(div);
        chatbox.scrollTop = chatbox.scrollHeight;
    }

    chatForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (waitingResponse) return;
        const msg = messageInput.value.trim();
        if (msg === '') return;

        appendMessage('user', msg);
        messageInput.value = '';
        sendBtn.disabled = true;
        waitingResponse = true;

        try {
            const resp = await fetch(window.location.href, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: msg })
            });
            const data = await resp.json();
            appendMessage('bot', data.reply || 'Nessuna risposta ricevuta.');
        } catch (error) {
            appendMessage('bot', 'Errore di connessione al server.');
        } finally {
            waitingResponse = false;
            sendBtn.disabled = messageInput.value.trim() === '';
            messageInput.focus();
        }
    });

    window.addEventListener('load', () => messageInput.focus());
})();
</script>
</body>
</html>
