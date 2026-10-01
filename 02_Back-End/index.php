<?php
// index.php – JSON-Kontrollansicht der Extract- und Transform-Ergebnisse.
header('Content-Type: application/json; charset=utf-8');

$result = require __DIR__ . '/transform.php';

echo json_encode(
    $result,
    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR
);
