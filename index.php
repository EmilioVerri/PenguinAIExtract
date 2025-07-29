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

    $message = strip_tags($message);
    $message = preg_replace('/[^\PC\s]/u', '', $message);

    $session_id = session_id();

    $lock_file = __DIR__ . '/risposte/lock_' . $session_id . '.lock';

    // Se esiste lock, rifiuto subito la richiesta
    if (file_exists($lock_file)) {
        echo json_encode(["reply" => "Attendi che la richiesta precedente sia terminata."]);
        exit;
    }

    // Creo il lock file per segnalare che la richiesta è in corso
    file_put_contents($lock_file, "locked");

    // Costruisco URL per chiamare script.php
    $script_url = 'http://localhost/PenguinAIChatBot/script.php?session=' . urlencode($session_id) . '&prompt=' . urlencode($message);

    // Eseguo la chiamata per avviare lo script (non mi aspetto risposta diretta)
    $call = @file_get_contents($script_url);

    $response_file = __DIR__ . '/risposte/risposta_' . $session_id . '.txt';

    $max_wait_seconds = 5;
    $waited = 0;

    while (!file_exists($response_file) && $waited < $max_wait_seconds) {
        usleep(200000); // 0.2 secondi
        $waited += 0.2;
    }

    if (!file_exists($response_file)) {
        // Elimino il lock se timeout
        unlink($lock_file);
        echo json_encode(["reply" => "Timeout nell'attesa della risposta."]);
        exit;
    }

    $response = trim(file_get_contents($response_file));

    // Elimino il file di risposta per evitare accumuli
   // unlink($response_file);

    // Elimino il lock (fine lavorazione)
   // unlink($lock_file);

    if ($response === '') {
        $response = "Nessuna risposta dal bot.";
    }

    echo json_encode(["reply" => $response]);
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
        /* Stili identici a quelli del tuo codice originale */
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
        <a href="#" title="Home">Home</a>
        <a href="#" title="Aiuto">Aiuto</a>
        <a href="#" title="Info">Info</a>
    </nav>
</header>


<main>
    <h2>Penguin AI ChatBot</h2>
    <div id="description">
        Digita il tuo messaggio e premi Invia o Invio per parlare con Penguin AI.<br />
        Attendi qualche secondo per la risposta.
    </div>

    <div id="chatbox" role="log" aria-live="polite" aria-relevant="additions"></div>

    <div id="inputArea">
        <input id="message" type="text" autocomplete="off" placeholder="Scrivi il tuo messaggio..." aria-label="Messaggio" maxlength="500" />
        <button id="sendBtn" aria-label="Invia messaggio">→</button>
    </div>
</main>

<footer>
    &copy; Penguin AI 2025
</footer>

<script>
(() => {
    const sendBtn = document.getElementById('sendBtn');
    const messageInput = document.getElementById('message');
    const chatbox = document.getElementById('chatbox');
    const menuToggle = document.getElementById('menuToggle');
    const menu = document.getElementById('menu');
    const logo = document.getElementById('logo');

    // Funzione per aggiungere messaggi al chatbox
    function addMessage(text, sender) {
        const div = document.createElement('div');
        div.classList.add('message', sender);
        const spanSender = document.createElement('span');
        spanSender.classList.add('sender');
        spanSender.textContent = sender === 'user' ? 'Tu:' : 'Penguin:';
        div.appendChild(spanSender);
        div.appendChild(document.createTextNode(text));
        chatbox.appendChild(div);
        chatbox.scrollTop = chatbox.scrollHeight;
    }

    // Funzione per inviare il messaggio al server
    async function sendMessage() {
        const message = messageInput.value.trim();
        if (!message) return;

        addMessage(message, 'user');
        messageInput.value = '';
        messageInput.disabled = true;
        sendBtn.disabled = true;

        try {
            const response = await fetch('index.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message })
            });

            if (!response.ok) {
                throw new Error(`Errore HTTP: ${response.status}`);
            }

            const data = await response.json();
            addMessage(data.reply || 'Nessuna risposta.', 'bot');

        } catch (error) {
            addMessage('Errore di rete o server: ' + error.message, 'bot');
        } finally {
            messageInput.disabled = false;
            sendBtn.disabled = false;
            messageInput.focus();
        }
    }

    // Eventi
    sendBtn.addEventListener('click', sendMessage);

    messageInput.addEventListener('keydown', e => {
        if (e.key === 'Enter' && !sendBtn.disabled) {
            e.preventDefault();
            sendMessage();
        }
    });

    menuToggle.addEventListener('click', () => {
        menuToggle.classList.toggle('active');
    });

    // Chiudi menu cliccando su link o fuori
    menu.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', () => {
            menuToggle.classList.remove('active');
        });
    });

    document.addEventListener('click', (e) => {
        if (!menu.contains(e.target) && e.target !== menuToggle) {
            menuToggle.classList.remove('active');
        }
    });

    // Logo cliccabile: torna alla home (puoi cambiare URL)
    logo.addEventListener('click', () => {
        window.location.href = '/';
    });
})();
</script>
<h1><?php echo session_id(); ?></h1>
</body>
</html>
