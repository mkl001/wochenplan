<?php
header('Content-Type: application/json');
echo json_encode(['ok' => true, 'version' => '2.0.0', 'time' => date('c')]);
