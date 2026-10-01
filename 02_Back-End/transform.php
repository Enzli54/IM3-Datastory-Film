<?php
// transform.php – wendet unsere Regeln an (siehe TRANSFORM.md).
// Ergebnis: eine Zeile pro Genre und Jahr + Audit.

$rawFilms = include __DIR__ . '/extract.php';

// ---- Regeln aus unserer Datenfrage ----
const START_YEAR = 1967;
const END_YEAR = 2025;
const MIN_VOTES = 10;   // weniger Stimmen = kaum bekannter Film -> fällt weg
const GENRES = ['Action', 'Animation', 'Horror', 'Romance', 'Science Fiction'];

$audit = [
    'input_films'     => count($rawFilms),
    'duplicates'      => 0,
    'invalid_values'  => 0,
    'outside_period'  => 0,
    'too_few_votes'   => 0,
    'output_films'    => 0,
    'output_rows'     => 0,
];

$seenIds = [];
$films = [];

// ---- 1. filtern, deduplizieren, bereinigen ----
foreach ($rawFilms as $film) {
    $id = $film['id'] ?? null;

    // deduplizieren: dieselbe TMDB-ID nur einmal
    if (isset($seenIds[$id])) {
        $audit['duplicates']++;
        continue;
    }
    $seenIds[$id] = true;

    // bereinigen: Jahr und Stimmen müssen Zahlen sein, Titel und Genre vorhanden
    $year = $film['jahr'] ?? null;
    $votes = $film['vote_count'] ?? null;
    $title = trim((string) ($film['titel'] ?? ''));
    $genres = array_values(array_intersect($film['genres'] ?? [], GENRES));
    $dateString = $film['datum'] ?? null;
    $date = is_string($dateString)
        ? DateTimeImmutable::createFromFormat('!d.m.Y', $dateString)
        : false;
    $dateErrors = DateTimeImmutable::getLastErrors();

    if (!is_numeric($id)
        || !is_numeric($year)
        || !is_numeric($votes)
        || !is_numeric($film['popularity'] ?? null)
        || !is_numeric($film['vote_average'] ?? null)
        || $title === ''
        || count($genres) === 0
        || $date === false
        || ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))
        || $date->format('d.m.Y') !== $dateString
    ) {
        $audit['invalid_values']++;
        continue;
    }
    $year = (int) $year;
    $votes = (int) $votes;

    // filtern: nur unser Zeitraum
    if ($year < START_YEAR || $year > END_YEAR) {
        $audit['outside_period']++;
        continue;
    }

    // filtern: nur Filme mit genug Stimmen
    if ($votes < MIN_VOTES) {
        $audit['too_few_votes']++;
        continue;
    }

    $films[] = [
        'tmdb_id' => (int) $id,
        'title' => $title,
        'release_date' => $date->format('Y-m-d'),
        'year' => $year,
        'genres' => $genres,
        'popularity' => $film['popularity'],
        'votes' => $votes,
        'vote_average' => $film['vote_average'],
    ];
}
$audit['output_films'] = count($films);

// ---- 2. aggregieren: pro Jahr und pro Genre zählen ----
$filmsPerYear = [];   // alle Filme eines Jahres (für den Anteil)
$groups = [];         // [Jahr][Genre] => Anzahl + Top-Film

foreach ($films as $film) {
    $y = $film['year'];
    $filmsPerYear[$y] = ($filmsPerYear[$y] ?? 0) + 1;

    foreach ($film['genres'] as $genre) {
        $g = $groups[$y][$genre] ?? ['count' => 0, 'top_title' => null, 'top_votes' => -1];
        $g['count']++;
        if ($film['votes'] > $g['top_votes']) {          // meistbewerteter Film = Top-Film
            $g['top_title'] = $film['title'];
            $g['top_votes'] = $film['votes'];
        }
        $groups[$y][$genre] = $g;
    }
}

// ---- 3. Zielstruktur nach Datenvertrag bauen ----
$rows = [];
for ($y = START_YEAR; $y <= END_YEAR; $y++) {
    foreach (GENRES as $genre) {
        $g = $groups[$y][$genre] ?? null;
        $total = $filmsPerYear[$y] ?? 0;

        $rows[] = [
            'genre'          => $genre,
            'year'           => $y,
            'film_count'     => $g['count'] ?? 0,
            'share_percent'  => $total > 0 ? round(($g['count'] ?? 0) / $total * 100, 1) : 0.0,
            'top_film'       => $g['top_title'] ?? null,
            'top_film_votes' => $g['top_votes'] ?? null,
        ];
    }
}
$audit['output_rows'] = count($rows);

return [
    'audit' => $audit,
    'films' => $films,
    'rows'  => $rows,
];
