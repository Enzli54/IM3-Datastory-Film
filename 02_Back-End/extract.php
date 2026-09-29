<?php
// extract.php – liest die Rohdaten und gibt sie als PHP-Array zurück.
// Kein Umbenennen, kein Filtern: das passiert erst in transform.php.

$path = __DIR__ . '/data/filme.json';

if (!file_exists($path)) {
    throw new RuntimeException("Datei nicht gefunden: $path");
}

$json = file_get_contents($path);        // Datei als Text
$rawFilms = json_decode($json, true);    // Text als PHP-Array

if (!is_array($rawFilms)) {
    throw new RuntimeException('filme.json ist kein gültiges JSON.');
}

return $rawFilms;
