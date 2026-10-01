<?php
// Importiert filme.json in die bereits angelegte MariaDB-Tabelle films.

if (PHP_SAPI !== 'cli') {
    throw new RuntimeException('Dieses Skript muss über die Kommandozeile gestartet werden.');
}

$configPath = __DIR__ . '/config.php';
if (!is_file($configPath)) {
    throw new RuntimeException('config.php fehlt. Erstelle sie anhand von config.example.php.');
}

$config = require $configPath;
if (!is_array($config)) {
    throw new RuntimeException('config.php muss ein Array zurückgeben.');
}

foreach (['host', 'database', 'username', 'password', 'port'] as $key) {
    if (!array_key_exists($key, $config)) {
        throw new RuntimeException("Der Datenbankeintrag '$key' fehlt in config.php.");
    }
}

if (!is_string($config['host'])
    || $config['host'] === ''
    || !is_string($config['database'])
    || $config['database'] === ''
    || !is_string($config['username'])
    || $config['username'] === ''
    || !is_string($config['password'])
    || !is_int($config['port'])
) {
    throw new RuntimeException('Die Datenbankangaben in config.php sind ungültig.');
}

$dsn = sprintf(
    'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
    $config['host'],
    $config['port'],
    $config['database']
);
$pdo = new PDO($dsn, $config['username'], $config['password'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => false,
]);

$json = file_get_contents(__DIR__ . '/data/filme.json');
if ($json === false) {
    throw new RuntimeException('filme.json konnte nicht gelesen werden.');
}

$films = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
if (!is_array($films)) {
    throw new RuntimeException('filme.json muss eine Liste von Filmen enthalten.');
}

$statement = $pdo->prepare(
    'INSERT INTO films
        (tmdb_id, title, release_date, release_year, genres, popularity, vote_count, vote_average)
     VALUES
        (:tmdb_id, :title, :release_date, :release_year, :genres, :popularity, :vote_count, :vote_average)
     ON DUPLICATE KEY UPDATE
        title = VALUES(title),
        release_date = VALUES(release_date),
        release_year = VALUES(release_year),
        genres = VALUES(genres),
        popularity = VALUES(popularity),
        vote_count = VALUES(vote_count),
        vote_average = VALUES(vote_average)'
);

$pdo->beginTransaction();

try {
    foreach ($films as $film) {
        $date = DateTimeImmutable::createFromFormat('!d.m.Y', $film['datum']);
        $dateErrors = DateTimeImmutable::getLastErrors();

        if ($date === false
            || ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))
            || $date->format('d.m.Y') !== $film['datum']
        ) {
            throw new RuntimeException('Ungültiges Datum bei TMDB-ID ' . ($film['id'] ?? 'unbekannt'));
        }

        $statement->execute([
            'tmdb_id' => $film['id'],
            'title' => $film['titel'],
            'release_date' => $date->format('Y-m-d'),
            'release_year' => $film['jahr'],
            'genres' => implode(', ', $film['genres']),
            'popularity' => $film['popularity'],
            'vote_count' => $film['vote_count'],
            'vote_average' => $film['vote_average'],
        ]);
    }

    $pdo->commit();
} catch (Throwable $exception) {
    $pdo->rollBack();
    throw $exception;
}

printf("%d Filme importiert.\n", count($films));
