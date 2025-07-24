<!DOCTYPE html>
<html lang="it">
<head>
         <link rel="icon" href="./images/favicon.ico" type="image/x-icon" />
    <meta charset="UTF-8" />
    <title>Errore - Penguin AI</title>
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(to bottom, #ffd6d6, #ffecec);
            margin: 0;
            padding: 0;
            color: #660000;
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
             background: white url('./images/profilo.png') no-repeat center/contain;
            cursor: pointer;
            flex-shrink: 0;
        }
        main {
            padding: 120px 20px 60px;
            max-width: 700px;
            margin: auto;
            text-align: center;
        }
        h1 {
            font-size: 2.8rem;
            font-weight: bold;
            color: #660000;
            margin-bottom: 20px;
        }
        .icon {
            font-size: 5rem;
            margin-bottom: 20px;
        }
        .message {
            font-size: 1.2rem;
            margin-bottom: 30px;
        }
        .back-btn {
            background-color: #cc0000;
            border: none;
            color: white;
            padding: 14px 28px;
            font-size: 18px;
            border-radius: 12px;
            cursor: pointer;
            text-decoration: none;
            box-shadow: 0 4px 12px rgba(204,0,0,0.6);
            transition: background-color 0.3s ease;
        }
        .back-btn:hover {
            background-color: #990000;
            box-shadow: 0 6px 16px rgba(153,0,0,0.8);
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
            font-size: 14px;
            box-shadow: 0 -2px 8px rgba(0,0,0,0.25);
            user-select: none;
        }
    </style>
</head>
<body>

<header>
    <a href="./" id="logo" title="Homepage Penguin AI"></a>
    <div style="font-weight: bold; font-size: 18px;">Penguin AI</div>
</header>

<main>
    <div class="icon">🐧</div>
    <h1>Si è verificato un errore</h1>
    <p class="message">Qualcosa è andato storto. Riprova più tardi o contatta l'amministratore.</p>
    <a href="./" class="back-btn">Torna alla home</a>
</main>

<footer>
    &copy; 2025 Penguin AI - Tutti i diritti riservati
</footer>

</body>
</html>
