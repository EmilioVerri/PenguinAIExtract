<?php


if (!isset($_GET['session']) || !isset($_GET['prompt'])) {
    http_response_code(400);
    echo "Parametri mancanti.";
    exit;
}

$session_id = preg_replace('/[^a-zA-Z0-9_-]/', '_', $_GET['session']);
$prompt = $_GET['prompt'];

$info_file = __DIR__ . DIRECTORY_SEPARATOR . 'informazioni.php';

$cmd = sprintf(
    'php %s %s %s',
    escapeshellarg($info_file),
    escapeshellarg($session_id),
    escapeshellarg($prompt)
);

$_GET['sessione'] = $session_id;
$_GET['domanda'] = $prompt;

ob_start();
include $info_file;
ob_end_clean();

//echo "ok";
exit;
