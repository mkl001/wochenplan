<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$dataDir = __DIR__ . '/../data';
if (!is_dir($dataDir)) mkdir($dataDir, 0755, true);
$file = $dataDir . '/checkins.json';

function readData($f) {
    if (!file_exists($f)) return ['entries' => []];
    $d = json_decode(file_get_contents($f), true);
    return $d ?: ['entries' => []];
}
function writeData($f, $d) {
    file_put_contents($f, json_encode($d, JSON_PRETTY_PRINT));
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true);
    $date = $body['date'] ?? '';
    $type = $body['type'] ?? '';
    if (!$date || !$type) { http_response_code(400); echo json_encode(['error' => 'date + type required']); exit; }
    $d = readData($file);
    $entry = ['date' => $date, 'type' => $type, 'done' => !empty($body['done']), 'note' => $body['note'] ?? '', 'updatedAt' => date('c')];
    $found = false;
    foreach ($d['entries'] as &$e) {
        if ($e['date'] === $date && $e['type'] === $type) { $e = $entry; $found = true; break; }
    }
    if (!$found) $d['entries'][] = $entry;
    usort($d['entries'], fn($a,$b) => strcmp($b['date'], $a['date']));
    writeData($file, $d);
    echo json_encode(['ok' => true, 'entry' => $entry]);
    exit;
}

$d = readData($file);
echo json_encode($d['entries']);
