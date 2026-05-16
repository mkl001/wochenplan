<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$dataDir = __DIR__ . '/../data';
if (!is_dir($dataDir)) mkdir($dataDir, 0755, true);
$file = $dataDir . '/water.json';

function readData($f) {
    if (!file_exists($f)) return [];
    $d = json_decode(file_get_contents($f), true);
    return $d ?: [];
}
function writeData($f, $d) {
    file_put_contents($f, json_encode($d, JSON_PRETTY_PRINT));
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $body  = json_decode(file_get_contents('php://input'), true);
    $date  = $body['date']  ?? '';
    $count = isset($body['count']) ? intval($body['count']) : null;
    if (!$date || $count === null) { http_response_code(400); echo json_encode(['error' => 'date + count required']); exit; }
    $d = readData($file);
    $d[$date] = $count;
    writeData($file, $d);
    echo json_encode(['ok' => true, 'date' => $date, 'count' => $count]);
    exit;
}

// GET /api/water.php?date=YYYY-MM-DD  OR  GET /api/water.php (all)
$d = readData($file);
if (isset($_GET['date'])) {
    $date = $_GET['date'];
    echo json_encode(['date' => $date, 'count' => $d[$date] ?? 0]);
} else {
    echo json_encode($d);
}
