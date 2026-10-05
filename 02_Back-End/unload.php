<?php
/**
 * unload.php – liest die Tabelle films und gibt die Daten als JSON aus.
 *
 * Aufrufe:
 *   /unload.php                         alle Filme
 *   /unload.php?genre=Horror            nur Filme mit diesem Genre
 *   /unload.php?year=1999               nur Filme aus diesem Jahr
 *   /unload.php?type=rows               eine Zeile pro Genre und Jahr
 *   /unload.php?type=rows&genre=Horror  nur die Zeilen dieses Genres
 *
 * Die Datei liefert Daten und kein HTML. Deshalb steht hier kein var_dump und
 * kein echo ausser dem JSON am Schluss.
 */

// Der Header muss vor jeder Ausgabe stehen.
header('Content-Type: application/json; charset=utf-8');

// Dieselben Regeln wie in transform.php (für type=rows).
const START_YEAR = 1967;
const END_YEAR = 2025;
const GENRES = ['Action', 'Animation', 'Horror', 'Romance', 'Science Fiction'];

try {
    // -----------------------------------------------------------------------
    // 1. Verbinden
    // -----------------------------------------------------------------------
    // Dieselbe Verbindung wie in load.php.

    require __DIR__ . '/config.php';

    $pdo = new PDO($dsn, $username, $password, $options);

    // -----------------------------------------------------------------------
    // 2. Werte aus der URL lesen
    // -----------------------------------------------------------------------
    // ?? '' setzt einen leeren String, wenn der Parameter fehlt.

    $type = trim($_GET['type'] ?? '');
    $genre = trim($_GET['genre'] ?? '');
    $year = trim($_GET['year'] ?? '');

    if ($type === 'rows') {
        // -------------------------------------------------------------------
        // 3a. Zeilen pro Genre und Jahr
        // -------------------------------------------------------------------
        // Dieselbe Form wie 'rows' in index.php, aber aus der Datenbank
        // gelesen. Die Sortierung nach Stimmen sorgt dafür, dass der
        // meistbewertete Film zuerst kommt.

        $statement = $pdo->prepare(
            'SELECT title, release_year, genres, vote_count
             FROM films
             ORDER BY vote_count DESC, tmdb_id'
        );
        $statement->execute();
        $films = $statement->fetchAll();

        // aggregieren: gleiche Logik wie in transform.php
        $filmsPerYear = [];   // alle Filme eines Jahres (für den Anteil)
        $groups = [];         // [Jahr][Genre] => Anzahl + Top-Film

        foreach ($films as $film) {
            $y = (int) $film['release_year'];
            $filmsPerYear[$y] = ($filmsPerYear[$y] ?? 0) + 1;

            // genres ist ein Text wie "Action, Horror".
            foreach (explode(', ', $film['genres']) as $filmGenre) {
                $g = $groups[$y][$filmGenre] ?? ['count' => 0, 'top_title' => null, 'top_votes' => -1];
                $g['count']++;
                if ($film['vote_count'] > $g['top_votes']) {   // meistbewerteter Film = Top-Film
                    $g['top_title'] = $film['title'];
                    $g['top_votes'] = (int) $film['vote_count'];
                }
                $groups[$y][$filmGenre] = $g;
            }
        }

        // Jedes Jahr und jedes Genre bekommt eine Zeile, auch ohne Filme. Ein
        // unbekanntes Genre im Filter ergibt eine leere Liste.
        $data = [];
        for ($y = START_YEAR; $y <= END_YEAR; $y++) {
            foreach (GENRES as $rowGenre) {
                if ($genre !== '' && $rowGenre !== $genre) {
                    continue;
                }

                $g = $groups[$y][$rowGenre] ?? null;
                $total = $filmsPerYear[$y] ?? 0;

                $data[] = [
                    'genre'          => $rowGenre,
                    'year'           => $y,
                    'film_count'     => $g['count'] ?? 0,
                    'share_percent'  => $total > 0 ? round(($g['count'] ?? 0) / $total * 100, 1) : 0.0,
                    'top_film'       => $g['top_title'] ?? null,
                    'top_film_votes' => $g['top_votes'] ?? null,
                ];
            }
        }
    } else {
        // -------------------------------------------------------------------
        // 3b. Einzelne Filme
        // -------------------------------------------------------------------
        // Kein SELECT *: Der Endpunkt liefert nur die vereinbarten Felder. AS
        // gibt den Feldern dieselben Namen wie im Transform (year, votes).

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

        // Die Filter sind optional. Der Wert kommt nie direkt in den SQL-Text,
        // sondern als Platzhalter – ohne Filter bleibt $params eine leere Liste.
        $where = [];
        $params = [];

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

        // genres wird wieder zur Liste wie im Transform. DECIMAL-Spalten
        // liefert die Datenbank als Text ("7.631"), im JSON sollen es Zahlen
        // sein.
        $data = [];
        foreach ($rows as $row) {
            $row['genres'] = explode(', ', $row['genres']);
            $row['popularity'] = (float) $row['popularity'];
            $row['vote_average'] = (float) $row['vote_average'];
            $data[] = $row;
        }
    }

    // -----------------------------------------------------------------------
    // 4. Antworten
    // -----------------------------------------------------------------------
    // Ohne Treffer ist $data eine leere Liste und die Antwort [].
    // JSON_PRESERVE_ZERO_FRACTION hält 25.0 als 25.0, wie in index.php.

    echo json_encode(
        $data,
        JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION
    );
} catch (Throwable $error) {
    // Interne Meldungen gehören ins Server-Log, nicht in die öffentliche Antwort.
    http_response_code(500);
    error_log($error->getMessage());
    echo json_encode([
        'error' => 'Daten konnten nicht geladen werden.',
    ]);
}
