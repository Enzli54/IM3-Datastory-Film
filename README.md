# IM3

Genre-Datastory ist ein nicht-kommerzielles Studierendenprojekt im Modul «Interaktive Medien 3». Wir entwickeln eine interaktive Onepager-Datastory. Sie zeigt, wie sich die Beliebtheit von Filmgenres (Action, Animation, Horror, Romance, Science Fiction) auf dem US-amerikanischen Filmmarkt von 1967 bis 2025 verändert hat. Über die TMDB-API holen wir Filme nach Genre, Erscheinungsdatum und Popularität, fassen sie pro Jahr zusammen und stellen sie in interaktiven Grafiken dar. Ziel ist es, merkbare Anstiege der Genrepopularität zu erkennen und mit möglichen Auslöserfilmen zu verbinden.

## Transformierte Daten in MariaDB laden

Die Tabelle `genre_year_stats` muss bereits in `uwosonis_im3film` existieren. `load.php` legt keine Tabellen an und ändert das Schema nicht. Es ersetzt nur die Daten in dieser Tabelle durch die transformierten Werte. Ergänze in der vorhandenen `config.php` die Datenbankverbindung. `config.php` wird von Git ignoriert.

```sh
php 02_Back-End/load.php
```

Der Load verwendet PDO, bereitet das INSERT einmal vor und führt es für jede Zeile aus `transform.php` aus. Da der historische Datenbestand vollständig vorliegt, ersetzt jeder Lauf die Tabelle `genre_year_stats` innerhalb einer Transaktion. Bei einem Fehler bleibt der vorherige Datenstand erhalten. Der Browser-Load ist über `https://DEINE-DOMAIN/02_Back-End/load.php` erreichbar und kann ohne Token ausgelöst werden; jeder Besucher kann damit den Tabelleninhalt ersetzen. `index.php` liefert weiterhin die Transform-Ergebnisse als JSON.

Der Datenbankname ist standardmässig `uwosonis_im3film`. Transform filtert Duplikate, ungültige Datensätze, Filme ausserhalb des Zeitraums 1967–2025 sowie Filme mit weniger als 10 Stimmen und aggregiert die bereinigten Filme nach Jahr und Genre.