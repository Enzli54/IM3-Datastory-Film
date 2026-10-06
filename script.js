let filmDaten = [];
let chart;        // Balkendiagramm (Jahrzehnte)
let genreChart;   // Liniendiagramm (Genres über die Jahre)

const START_JAHR = 1967;
const END_JAHR = 2025;

// Farben für die Genres (werden der Reihe nach vergeben)
const FARBEN = [
    "#e6194b", "#3cb44b", "#4363d8", "#f58231", "#911eb4",
    "#42d4f4", "#f032e6", "#9a6324", "#469990", "#800000",
    "#808000", "#000075", "#bfef45", "#fabed4", "#a9a9a9"
];


// =====================================================
// Daten EINMAL vom Backend laden – beide Charts nutzen sie
// =====================================================
try {
    const response = await fetch("/02_Back-End/unload.php?type=rows");

    if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
    }

    filmDaten = await response.json();

    console.log(filmDaten);

} catch (error) {
    console.error("Fetch fehlgeschlagen:", error);
}


// Chart 1: Beim Start direkt die 1970er anzeigen
try {
    updateChart(1970);
} catch (error) {
    console.error("Jahrzehnt-Chart fehlgeschlagen:", error);
}

// Chart 2: Genre-Verlauf + Buttons
try {
    erstelleGenreChart(filmDaten);
    erstelleGenreButtons();
} catch (error) {
    console.error("Genre-Chart fehlgeschlagen:", error);
}


// =====================================================
// CHART 1: Filme nach Genre pro Jahrzehnt (Balken)
// =====================================================

// Listener für alle Jahrzehnt-Buttons
const buttons = document.querySelectorAll("#jahrzehnte button");

buttons.forEach(button => {

    button.addEventListener("click", () => {

        const decade = Number(button.dataset.decade);

        updateChart(decade);

    });

});


// Chart für ein Jahrzehnt erzeugen / aktualisieren
function updateChart(decade) {

    // z.B. bei 1990:
    // Jahre 1990 bis 1999 auswählen
    const gefilterteDaten = filmDaten.filter(eintrag =>
        eintrag.year >= decade &&
        eintrag.year <= decade + 9
    );


    // film_count pro Genre zusammenrechnen
    const genreCounts = {};

    gefilterteDaten.forEach(eintrag => {

        if (!genreCounts[eintrag.genre]) {
            genreCounts[eintrag.genre] = 0;
        }

        genreCounts[eintrag.genre] += Number(eintrag.film_count);

    });


    // Daten für Chart.js vorbereiten
    const labels = Object.keys(genreCounts);
    const values = Object.values(genreCounts);


    // Falls bereits ein Chart existiert:
    // Daten ändern
    if (chart) {

        chart.data.labels = labels;
        chart.data.datasets[0].data = values;
        chart.data.datasets[0].label = `Filme nach Genre – ${decade}er`;

        chart.update();

        return;
    }


    // Beim ersten Mal Chart erstellen
    chart = new Chart(
        document.querySelector("#verlauf"),
        {
            type: "bar",

            data: {
                labels: labels,

                datasets: [
                    {
                        label: `Filme nach Genre – ${decade}er`,
                        data: values
                    }
                ]
            },


            options: {
                responsive: true,
                indexAxis: 'y',

                scales: {
                    x: {
                        beginAtZero: true,
                        max: 3000,
                        title: {
                            display: true,
                            text: "Anzahl Filme"
                        }
                    },

                    y: {
                        title: {
                            display: true,
                            text: "Genre"
                        }
                    }
                }
            }
        }
    );
}


// =====================================================
// CHART 2: Filme pro Jahr (1967–2025), eine Linie pro Genre
// =====================================================

function erstelleGenreChart(daten) {

    const canvas = document.querySelector("#genreVerlauf");
    if (!canvas) {
        throw new Error('Kein <canvas id="genreVerlauf"> im HTML gefunden');
    }

    // x-Achse: alle Jahre von 1967 bis 2025
    const jahre = [];
    for (let jahr = START_JAHR; jahr <= END_JAHR; jahr++) {
        jahre.push(jahr);
    }

    // Alle Genres herausfinden (alphabetisch sortiert)
    const genres = [...new Set(daten.map(eintrag => eintrag.genre))].sort();

    // Pro Genre ein Array mit einer 0 für jedes Jahr anlegen
    const counts = {};
    genres.forEach(genre => {
        counts[genre] = new Array(jahre.length).fill(0);
    });

    // film_count an der richtigen Stelle eintragen
    daten.forEach(eintrag => {
        const jahr = Number(eintrag.year);
        if (jahr < START_JAHR || jahr > END_JAHR) return;

        const index = jahr - START_JAHR;
        counts[eintrag.genre][index] += Number(eintrag.film_count);
    });

    // Ein Dataset (= eine Linie) pro Genre
    const datasets = genres.map((genre, i) => {
        const farbe = FARBEN[i % FARBEN.length];
        return {
            label: genre,
            data: counts[genre],
            borderColor: farbe,
            backgroundColor: farbe,
            borderWidth: 2,
            pointRadius: 0,
            pointHoverRadius: 4,
            tension: 0.25
        };
    });

    genreChart = new Chart(
        canvas,
        {
            type: "line",

            data: {
                labels: jahre,
                datasets: datasets
            },

            options: {
                responsive: true,

                // Tooltip zeigt alle Genres eines Jahres gleichzeitig
                interaction: {
                    mode: "index",
                    intersect: false
                },

                plugins: {
                    // Eigene Buttons ersetzen die Standard-Legende
                    legend: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: `Filme pro Jahr nach Genre (${START_JAHR}–${END_JAHR})`
                    }
                },

                scales: {
                    x: {
                        title: {
                            display: true,
                            text: "Jahr"
                        }
                    },
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: "Anzahl Filme"
                        }
                    }
                }
            }
        }
    );
}


// Für jedes Genre einen Button erzeugen
function erstelleGenreButtons() {

    const container = document.querySelector("#genreButtons");
    if (!container) {
        throw new Error('Kein <div id="genreButtons"> im HTML gefunden');
    }

    genreChart.data.datasets.forEach((dataset, index) => {

        const button = document.createElement("button");
        button.textContent = dataset.label;
        button.classList.add("genre-button", "aktiv");
        button.style.setProperty("--genre-farbe", dataset.borderColor);

        button.addEventListener("click", () => {

            const sichtbar = genreChart.isDatasetVisible(index);
            genreChart.setDatasetVisibility(index, !sichtbar);
            button.classList.toggle("aktiv", !sichtbar);

            genreChart.update();
        });

        container.appendChild(button);
    });


    // Zusätzlich: "Alle an" / "Alle aus"
    document.querySelector("#alleAn")?.addEventListener("click", () => setzeAlle(true));
    document.querySelector("#alleAus")?.addEventListener("click", () => setzeAlle(false));
}


function setzeAlle(sichtbar) {

    genreChart.data.datasets.forEach((_, index) => {
        genreChart.setDatasetVisibility(index, sichtbar);
    });

    document.querySelectorAll(".genre-button").forEach(button => {
        button.classList.toggle("aktiv", sichtbar);
    });

    genreChart.update();
}




/*
/02_Back-End/unload.php	alle einzelnen Filme
/02_Back-End/unload.php?genre=Horror&year=1999	Filme, gefiltert nach Genre und/oder Jahr
/02_Back-End/unload.php?type=rows	295 Zeilen, eine pro Genre und Jahr
/02_Back-End/unload.php?type=rows&genre=Horror	nur die 59 Zeilen dieses Genres
 */