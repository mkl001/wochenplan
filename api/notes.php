<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$dataDir = __DIR__ . '/../data';
if (!is_dir($dataDir)) mkdir($dataDir, 0755, true);
$file = $dataDir . '/notes.json';

function readData($f) {
    if (!file_exists($f)) return ['entries' => []];
    $d = json_decode(file_get_contents($f), true);
    return $d ?: ['entries' => []];
}
function writeData($f, $d) {
    file_put_contents($f, json_encode($d, JSON_PRETTY_PRINT));
}

$method = $_SERVER['REQUEST_METHOD'];

// DELETE /api/notes.php?id=123
if ($method === 'DELETE') {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $d = readData($file);
    $d['entries'] = array_values(array_filter($d['entries'], fn($e) => intval($e['id']) !== $id));
    writeData($file, $d);
    echo json_encode(['ok' => true]);
    exit;
}

if ($method === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true);
    $text = $body['text'] ?? '';
    if (!$text) { http_response_code(400); echo json_encode(['error' => 'text required']); exit; }
    $d = readData($file);
    array_unshift($d['entries'], [
        'id'        => intval(microtime(true) * 1000),
        'text'      => $text,
        'date'      => $body['date'] ?? date('Y-m-d'),
        'createdAt' => date('c'),
    ]);
    $d['entries'] = array_slice($d['entries'], 0, 500);
    writeData($file, $d);
    echo json_encode(['ok' => true]);
    exit;
}

$d = readData($file);
echo json_encode($d['entries']);
