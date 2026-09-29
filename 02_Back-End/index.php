<?php
// index.php – Kontrollansicht: zeigt das Resultat von transform.php als JSON an.
// Prüfwerkzeug für uns, nicht der spätere Endpunkt für Chart.js.
header('Content-Type: application/json; charset=utf-8');

$result = include __DIR__ . '/transform.php';

echo json_encode(
    $result,
    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR
);
