<?php
// informazioni.php

$sessione = $_GET['sessione'] ?? 'sessione_farlocca';
$domanda = $_GET['domanda'] ?? 'Ciao';

$ollamaPath = 'ollama';
$command = $ollamaPath . ' run gemma3 ' . escapeshellarg($domanda) . ' 2>&1';

// Esegui comando e cattura output come stringa unica
$output = shell_exec($command);

//rimuove frequenza ansi
function cleanOutput($text) {
    // Rimuove sequenze ANSI (colore, cursor movement, ecc)
    $text = preg_replace('/\e\[[\d;?]*[a-zA-Z]/', '', $text);
    // Rimuove altri caratteri di controllo non stampabili tranne newline/tab
    $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text);
    return $text;
}

$cleaned = cleanOutput($output);

$folder = __DIR__ . DIRECTORY_SEPARATOR . 'risposte';
if (!is_dir($folder)) {
    mkdir($folder, 0777, true);
}

$filename = $folder . DIRECTORY_SEPARATOR . 'risposta_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $sessione) . '.txt';

file_put_contents($filename, $cleaned);
