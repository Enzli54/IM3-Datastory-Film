<?php
/**
 * unload.php – liest die Filme aus der Tabelle films und gibt sie als JSON aus.
 *
 * Aufrufe:
 *   /unload.php                      alle Filme
 *   /unload.php?genre=Horror         nur Filme mit diesem Genre
 *   /unload.php?year=1999            nur Filme aus diesem Jahr
 *   /unload.php?genre=Horror&year=1999
 *
 * Die Datei liefert Daten und kein HTML. Deshalb steht hier kein var_dump und
 * kein echo ausser dem JSON am Schluss.
 */

// Der Header muss vor jeder Ausgabe stehen.
header('Content-Type: application/json; charset=utf-8');

try {
    // -----------------------------------------------------------------------
    // 1. Verbinden
    // -----------------------------------------------------------------------
    // Dieselbe Verbindung wie in load.php.

    require __DIR__ . '/config.php';

    $pdo = new PDO($dsn, $username, $password, $options);

    // -----------------------------------------------------------------------
    // 2. Filter aus der URL lesen
    // -----------------------------------------------------------------------
    // ?? '' setzt einen leeren String, wenn der Parameter fehlt.

    $genre = trim($_GET['genre'] ?? '');
    $year = trim($_GET['year'] ?? '');

    // -----------------------------------------------------------------------
    // 3. Lesen
    // -----------------------------------------------------------------------
    // Kein SELECT *: Der Endpunkt liefert nur die vereinbarten Felder. AS gibt
    // den Feldern dieselben Namen wie im Transform (year, votes).

    $sql = 'SELECT
                tmdb_id,
                title,
                release_date,
                release_year AS year,
                genres,
                popularity,
                vote_count AS votes,
                vote_average
            FROM films';


    if ($genre !== '') {
        // genres ist ein Text wie "Action, Horror", deshalb LIKE statt =.
        $where[] = 'genres LIKE :genre';
        $params['genre'] = '%' . $genre . '%';
    }

    if ($year !== '') {
        $where[] = 'release_year = :year';
        $params['year'] = (int) $year;
    }

    if (count($where) > 0) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    // Eine feste Sortierung macht die Reihenfolge verlässlich.
    $sql .= ' ORDER BY release_year, title';

    $statement = $pdo->prepare($sql);
    $statement->execute($params);
    $rows = $statement->fetchAll();

    // -----------------------------------------------------------------------
    // 4. In die vereinbarte Form bringen
    // -----------------------------------------------------------------------
    // genres wird wieder zur Liste wie im Transform. DECIMAL-Spalten liefert
    // die Datenbank als Text ("7.631"), im JSON sollen es Zahlen sein.

    $films = [];
    foreach ($rows as $row) {
        $row['genres'] = explode(', ', $row['genres']);
        $row['popularity'] = (float) $row['popularity'];
        $row['vote_average'] = (float) $row['vote_average'];
        $films[] = $row;
    }

    // -----------------------------------------------------------------------
    // 5. Antworten
    // -----------------------------------------------------------------------
    // Ohne Treffer ist $films eine leere Liste und die Antwort [].

    echo json_encode($films, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
} catch (Throwable $error) {
    // Interne Meldungen gehören ins Server-Log, nicht in die öffentliche Antwort.
    http_response_code(500);
    error_log($error->getMessage());
    echo json_encode([
        'error' => 'Daten konnten nicht geladen werden.',
    ]);
}
