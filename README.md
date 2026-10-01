# IM3

Genre-Datastory ist ein nicht-kommerzielles Studierendenprojekt im Modul «Interaktive Medien 3». Wir entwickeln eine interaktive Onepager-Datastory. Sie zeigt, wie sich die Beliebtheit von Filmgenres (Action, Animation, Horror, Romance, Science Fiction) auf dem US-amerikanischen Filmmarkt von 1967 bis 2025 verändert hat. Über die TMDB-API holen wir Filme nach Genre, Erscheinungsdatum und Popularität, fassen sie pro Jahr zusammen und stellen sie in interaktiven Grafiken dar. Ziel ist es, merkbare Anstiege der Genrepopularität zu erkennen und mit möglichen Auslöserfilmen zu verbinden.

## Filme in MariaDB laden

Zuerst `02_Back-End/schema.sql` in der bereits angelegten Datenbank ausführen. Dann `02_Back-End/config.example.php` als `02_Back-End/config.php` kopieren und Host, Port, Benutzername sowie Passwort eintragen. Die lokale `config.php` wird von Git ignoriert, damit Zugangsdaten nicht ins Repository gelangen.

```sh
cp 02_Back-End/config.example.php 02_Back-End/config.php
php 02_Back-End/import.php
```

Der Datenbankname ist standardmässig `uwosonis_im3film`. Der Import kann wiederholt werden: Filme mit vorhandener TMDB-ID werden aktualisiert.