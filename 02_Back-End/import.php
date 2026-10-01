<?php
// Importiert filme.json in die bereits angelegte MariaDB-Tabelle films.

if (PHP_SAPI !== 'cli') {
    throw new RuntimeException('Dieses Skript muss über die Kommandozeile gestartet werden.');
}

$host = getenv('IM3_DB_HOST');
$username = getenv('IM3_DB_USER');
$password = getenv('IM3_DB_PASSWORD');
$database = getenv('IM3_DB_NAME') ?: 'uswosonis_im3film';

if ($host === false || $host === '' || $username === false || $username === '' || $password === false) {
    throw new RuntimeException('Bitte IM3_DB_HOST, IM3_DB_USER und IM3_DB_PASSWORD setzen.');
}

$dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $host, $database);
$pdo = new PDO($dsn, $username, $password, [
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
