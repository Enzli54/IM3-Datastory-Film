# IM3

Genre-Datastory ist ein nicht-kommerzielles Studierendenprojekt im Modul «Interaktive Medien 3». Wir entwickeln eine interaktive Onepager-Datastory. Sie zeigt, wie sich die Beliebtheit von Filmgenres (Action, Animation, Horror, Romance, Science Fiction) auf dem US-amerikanischen Filmmarkt von 1967 bis 2025 verändert hat. Über die TMDB-API holen wir Filme nach Genre, Erscheinungsdatum und Popularität, fassen sie pro Jahr zusammen und stellen sie in interaktiven Grafiken dar. Ziel ist es, merkbare Anstiege der Genrepopularität zu erkennen und mit möglichen Auslöserfilmen zu verbinden.

## Filme in MariaDB laden

Zuerst `02_Back-End/schema.sql` in der bereits angelegten Datenbank ausführen. Danach `02_Back-End/import.php` mit PHP CLI starten. Die Zugangsdaten als Umgebungsvariablen setzen; der Datenbankname ist standardmässig `uwosonis_im3film`:

```sh
IM3_DB_HOST=localhost IM3_DB_USER=dein-benutzer IM3_DB_PASSWORD='dein-passwort' php 02_Back-End/import.php
```

Optional kann der Datenbankname mit `IM3_DB_NAME` überschrieben werden. Der Import kann wiederholt werden: Filme mit vorhandener TMDB-ID werden aktualisiert.