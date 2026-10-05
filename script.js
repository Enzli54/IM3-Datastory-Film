let filmDaten = [];
let chart;


// Daten vom Backend laden
try {
    const response = await fetch("/02_Back-End/unload.php?type=rows");

    if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
    }

    filmDaten = await response.json();

    console.log(filmDaten);

    // Beim Start direkt die 1990er anzeigen
    updateChart(1970);

} catch (error) {
    console.error("Fetch fehlgeschlagen:", error);
}


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
                responsive:true,
                indexAxis: 'y',

                scales: {
                    x: {
                        beginAtZero: true,
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




/*
/02_Back-End/unload.php	alle einzelnen Filme
/02_Back-End/unload.php?genre=Horror&year=1999	Filme, gefiltert nach Genre und/oder Jahr
/02_Back-End/unload.php?type=rows	295 Zeilen, eine pro Genre und Jahr
/02_Back-End/unload.php?type=rows&genre=Horror	nur die 59 Zeilen dieses Genres
 */