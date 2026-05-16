<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$dataDir = __DIR__ . '/../data';
if (!is_dir($dataDir)) mkdir($dataDir, 0755, true);
$file = $dataDir . '/weights.json';

function readData($f) {
    if (!file_exists($f)) return ['entries' => []];
    $d = json_decode(file_get_contents($f), true);
    return $d ?: ['entries' => []];
}
function writeData($f, $d) {
    file_put_contents($f, json_encode($d, JSON_PRETTY_PRINT));
}

$method = $_SERVER['REQUEST_METHOD'];

// DELETE /api/weights.php?date=YYYY-MM-DD
if ($method === 'DELETE') {
    $date = $_GET['date'] ?? '';
    $d = readData($file);
    $d['entries'] = array_values(array_filter($d['entries'], fn($e) => $e['date'] !== $date));
    writeData($file, $d);
    echo json_encode(['ok' => true]);
    exit;
}

// POST — add/update entry
if ($method === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true);
    $date = $body['date'] ?? '';
    $kg   = isset($body['kg']) ? floatval($body['kg']) : null;
    if (!$date || $kg === null) { http_response_code(400); echo json_encode(['error' => 'date + kg required']); exit; }
    $d = readData($file);
    $d['entries'] = array_values(array_filter($d['entries'], fn($e) => $e['date'] !== $date));
    $d['entries'][] = ['date' => $date, 'kg' => $kg, 'savedAt' => date('c')];
    usort($d['entries'], fn($a,$b) => strcmp($a['date'], $b['date']));
    writeData($file, $d);
    echo json_encode(['ok' => true]);
    exit;
}

// GET — return all entries
$d = readData($file);
echo json_encode($d['entries']);
