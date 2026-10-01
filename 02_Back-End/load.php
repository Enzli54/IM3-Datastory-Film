<?php
/**
 * load.php – schreibt die bereinigten Filme aus transform.php in die Tabelle films.
 *
 * Eine Zeile in der Tabelle = ein Film (siehe schema.sql).
 * Das Skript darf beliebig oft aufgerufen werden: Es schreibt den Stand jedes
 * Mal neu, statt Zeilen doppelt anzuhängen.
 */

// PHP dient hier als Prüfwerkzeug, nicht als Webseite. Mit diesem Header zeigt
// der Browser die Ausgabe als reinen Text.
header('Content-Type: text/plain; charset=utf-8');

// ---------------------------------------------------------------------------
// 1. Zugangsdaten und Daten laden
// ---------------------------------------------------------------------------
// config.php liegt im selben Ordner und liefert $dsn, $username, $password
// und $options. transform.php gibt ein Array mit 'audit', 'films' und 'rows'
// zurück – in die Tabelle films gehören die einzelnen Filme, also 'films'.

require __DIR__ . '/config.php';

$result = require __DIR__ . '/transform.php';
$films = $result['films'];

// ---------------------------------------------------------------------------
// 2. Verbindung aufbauen
// ---------------------------------------------------------------------------
// new PDO(...) baut die Verbindung auf. Klappt das nicht, wirft PDO eine
// Exception – der try/catch fängt sie und zeigt eine lesbare Meldung.

try {
    $pdo = new PDO($dsn, $username, $password, $options);
    echo "Verbindung steht.\n\n";
} catch (PDOException $e) {
    exit('Verbindung fehlgeschlagen: ' . $e->getMessage() . "\n");
}

// ---------------------------------------------------------------------------
// 3. Filme schreiben
// ---------------------------------------------------------------------------
// Die Namen mit Doppelpunkt sind Platzhalter. Der Wert kommt erst bei execute()
// dazu und wird deshalb nie Teil des SQL-Texts.
//
// prepare steht vor der Schleife und läuft genau einmal, execute läuft für
// jeden Film.

$insert = $pdo->prepare(
    'INSERT INTO films (tmdb_id, title, release_date, release_year, genres, popularity, vote_count, vote_average)
     VALUES (:tmdb_id, :title, :release_date, :release_year, :genres, :popularity, :vote_count, :vote_average)'
);

// Die Transaktion fasst Leeren und Füllen zusammen: Geht mittendrin etwas
// schief, bleibt der alte Stand erhalten. Ausserdem ist sie bei vielen Zeilen
// deutlich schneller als ein Speichern nach jedem einzelnen INSERT.
$pdo->beginTransaction();

try {
    // Stand neu schreiben: Unser Datensatz ist historisch und kommt jedes Mal
    // vollständig. Ohne diese Zeile würde der zweite Aufruf am Primärschlüssel
    // tmdb_id scheitern, weil jeder Film schon in der Tabelle steht.
    $pdo->exec('DELETE FROM films');

    foreach ($films as $film) {
        $insert->execute([
            'tmdb_id'      => $film['tmdb_id'],
            'title'        => $film['title'],
            'release_date' => $film['release_date'],
            'release_year' => $film['year'],
            // Die Spalte genres ist ein VARCHAR, im Transform ist es ein Array.
            'genres'       => implode(', ', $film['genres']),
            'popularity'   => $film['popularity'],
            'vote_count'   => $film['votes'],
            'vote_average' => $film['vote_average'],
        ]);
    }

    $pdo->commit();
} catch (PDOException $e) {
    $pdo->rollBack();
    exit('Laden fehlgeschlagen: ' . $e->getMessage() . "\n");
}

echo count($films) . " Filme geschrieben.\n\n";

// ---------------------------------------------------------------------------
// 4. Kontrolle: Was steht jetzt in der Tabelle?
// ---------------------------------------------------------------------------

$count = $pdo->query('SELECT COUNT(*) FROM films')->fetchColumn();
echo "Zeilen in films: $count\n\n";

echo "Audit aus dem Transform:\n";
foreach ($result['audit'] as $key => $value) {
    echo "  $key: $value\n";
}
