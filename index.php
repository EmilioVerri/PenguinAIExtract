<?php
session_start();



define('QUEUE_FILE', sys_get_temp_dir() . '/gemma3_queue.json');
define('TIMEOUT', 60);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $data = json_decode(file_get_contents('php://input'), true);
   $message = trim($data['message'] ?? '');
// Prendi il messaggio dal JSON e togli spazi inutili
$message = trim($data['message'] ?? '');

// 1. Controllo tipo (deve essere stringa)
if (!is_string($message)) {
    echo json_encode(["reply" => "Input non valido."]);
    exit;
}

// 2. Limitazione lunghezza (esempio max 500 caratteri)
if (strlen($message) > 500) {
    echo json_encode(["reply" => "Messaggio troppo lungo. Massimo 500 caratteri."]);
    exit;
}

// 3. Sanitizzazione: rimuovi tag HTML e codici potenzialmente pericolosi
$message = strip_tags($message);

// 4. Ulteriore rimozione di caratteri non ASCII se vuoi
$message = preg_replace('/[^\PC\s]/u', '', $message); // rimuove caratteri non unicode stampabili

// Dopo questi controlli $message è più sicuro per l’uso interno


    

    if ($message === '') {
        echo json_encode(["reply" => "Messaggio vuoto."]);
        exit;
    }

    $session_id = session_id();

    
    if (file_exists(QUEUE_FILE)) {
        $queue = json_decode(file_get_contents(QUEUE_FILE), true);
        if (!is_array($queue)) $queue = [];
    } else {
        $queue = [];
    }

   
    $request = [
        'session_id' => $session_id,
        'message' => $message,
        'timestamp' => time()
    ];

    $queue[] = $request;
    file_put_contents(QUEUE_FILE, json_encode($queue));

    $start_wait = time();


    while (true) {
        clearstatcache();
        $queue = json_decode(file_get_contents(QUEUE_FILE), true);
        if (!is_array($queue)) $queue = [];

      
        $pos = -1;
        foreach ($queue as $i => $req) {
            if ($req['session_id'] === $session_id && $req['message'] === $message) {
                $pos = $i;
                break;
            }
        }

        if ($pos === -1) {
          
            echo json_encode(["reply" => "Errore nella gestione della coda."]);
            exit;
        }

        if ($pos === 0) {
          
            $payload = json_encode([
                "model" => "gemma3:latest",
                "prompt" => $message,
                "stream" => false
            ]);

            $ch = curl_init('http://localhost:11434/api/generate');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

            $response = curl_exec($ch);
            curl_close($ch);

            $json = json_decode($response, true);

         
            $queue = array_filter($queue, function ($req) use ($session_id, $message) {
                return !($req['session_id'] === $session_id && $req['message'] === $message);
            });
            // Reindicizza array
            $queue = array_values($queue);
            file_put_contents(QUEUE_FILE, json_encode($queue));

            if (!$json || !isset($json['response'])) {
                echo json_encode(["reply" => "Errore nella risposta.", "debug" => $response]);
            } else {
                echo json_encode(["reply" => $json['response']]);
            }
            exit;
        } else {
           
            $waited = time() - $start_wait;
            if ($waited > TIMEOUT) {
            
                $queue = array_filter($queue, function ($req) use ($session_id, $message) {
                    return !($req['session_id'] === $session_id && $req['message'] === $message);
                });
                $queue = array_values($queue);
                file_put_contents(QUEUE_FILE, json_encode($queue));

                echo json_encode(["reply" => "Timeout: troppa attesa nella coda, riprova più tardi."]);
                exit;
            }
            usleep(200000); 
        }
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
  
    <meta charset="UTF-8" />
    <title>Penguin AI ChatBot</title>
     <link rel="shortcut icon" href=".\images\favicon.ico" type="image/x-icon">
    <style>
  
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

        /* HEADER */
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
            background: white url('./images/profilo.png') no-repeat center/contain;
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

        /* CONTENUTO PRINCIPALE */
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

        /* TESTO DESCRIZIONE */
        #description {
            font-size: 1.15rem;
            color: #004a99;
            margin-bottom: 20px;
            line-height: 1.5em;
            max-width: 650px;
            margin-left: auto;
            margin-right: auto;
        }

        /* GIF PINGUINO */
        #penguinGif {
            margin: 0 auto 30px auto;
            display: block;
            width: 140px;
            height: auto;
        }

        /* CHATBOX */
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

        /* INPUT AREA */
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
            box-shadow:
                inset 0 0 8px #80bfff,
                0 4px 10px rgba(0, 153, 255, 0.3);
            outline: none;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }
        #message:focus {
            border-color: #0099ff;
            box-shadow:
                inset 0 0 12px #3399ff,
                0 6px 14px rgba(0, 153, 255, 0.5);
        }
        #sendBtn {
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
        #sendBtn:hover {
            background-color: #007acc;
            box-shadow: 0 6px 16px rgba(0,122,204,0.8);
        }

        /* LOADING */
        #loading {
            display: none;
            font-style: italic;
            color: #555;
            text-align: center;
            margin-top: 8px;
        }

        /* FOOTER */
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

        /* Responsive minori */
        @media (max-width: 480px) {
            main {
                padding: 100px 10px 20px 10px;
            }
            #message {
                font-size: 16px;
            }
            #sendBtn {
                font-size: 16px;
                padding: 12px 20px;
            }
        }
    </style>
</head>
<body>

<header>
    <a href="./" id="logo" title="Homepage Penguin AI"></a>
    <div id="menuToggle" aria-label="Menu hamburger" role="button" tabindex="0" aria-expanded="false" aria-controls="menu">
        <span></span>
        <span></span>
        <span></span>
    </div>
    <nav id="menu" role="menu" aria-hidden="true">
        <a href=".\" role="menuitem" tabindex="-1">Generazione Testi</a>
        <a href=".\creazioneImmagini.php" role="menuitem" tabindex="-1">Generazione Immagini</a>
    </nav>
</header>

<main>
    <h2>🐧 Penguin AI ChatBot</h2>

    <p id="description">
        Scrivi un messaggio nella casella qui sotto e premi "Invia".<br>
        Penguin AI risponderà in pochi secondi con un testo generato usando il modello Gemma 3.<br>
        Puoi chiedere qualsiasi cosa, da informazioni, curiosità o aiuto con testi creativi!
    </p>



    <div id="chatbox" aria-live="polite" aria-label="Chat tra utente e Penguin AI"></div>
    <div id="inputArea">
        <input type="text" id="message" placeholder="Scrivi un messaggio..." autocomplete="off" aria-label="Campo messaggio"/>
        <button id="sendBtn" aria-label="Invia messaggio">Invia</button>
    </div>
    <div id="loading" role="status" aria-live="assertive">💬 Penguin sta pensando...</div>
</main>

<footer>
    &copy; 2025 Penguin AI - Tutti i diritti riservati
</footer>

<script>
    const chatbox = document.getElementById('chatbox');
    const messageInput = document.getElementById('message');
    const sendBtn = document.getElementById('sendBtn');
    const loading = document.getElementById('loading');
    const menuToggle = document.getElementById('menuToggle');
    const menu = document.getElementById('menu');

    function appendMessage(text, sender) {
        const div = document.createElement('div');
        div.className = 'message ' + sender;

        // Escape HTML per sicurezza
        const escapedText = text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
        div.innerHTML = `<span class="sender">${sender === 'user' ? 'Tu' : 'Penguin AI'}:</span> ${escapedText}`;
        chatbox.appendChild(div);
        chatbox.scrollTop = chatbox.scrollHeight;
    }

    sendBtn.addEventListener('click', () => {
        const message = messageInput.value.trim();
        if (message === '') return;

        appendMessage(message, 'user');
        messageInput.value = '';
        loading.style.display = 'block';

        fetch('index.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ message })
        })
        .then(res => res.json())
        .then(data => {
            appendMessage(data.reply, 'bot');
        })
        .catch(err => {
            appendMessage("Errore nella comunicazione col server.", 'bot');
            console.error(err);
        })
        .finally(() => {
            loading.style.display = 'none';
        });
    });

    messageInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') sendBtn.click();
    });

   
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

    document.addEventListener('click', (e) => {
        if (!menu.contains(e.target) && !menuToggle.contains(e.target)) {
            menuToggle.setAttribute('aria-expanded', false);
            menuToggle.classList.remove('active');
            menu.setAttribute('aria-hidden', true);
        }
    });
</script>
</body>
</html>