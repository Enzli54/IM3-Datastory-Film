/*
const chart = new Chart(document.querySelector('#verlauf'), {
    type: 'line', // welcher Diagrammtyp
    data: { labels: [], datasets: [] }, // was gezeigt wird
    options: verlaufOptions, // wie es aussieht
});

*/

const alleFilme = "/02_Back-End/unload.php";
const genre = "/02_Back-End/unload.php?genre";


try {
    const response = await fetch(`/02_Back-End/unload.php?type=rows`);
    if (!response.ok) throw new Error(`HTTP ${response.status}`);
    const data = await response.json();
    console.log(data);
} catch (error) {
    console.error("Fetch fehlgeschlagen:", error);
}






/*
/02_Back-End/unload.php	alle einzelnen Filme
/02_Back-End/unload.php?genre=Horror&year=1999	Filme, gefiltert nach Genre und/oder Jahr
/02_Back-End/unload.php?type=rows	295 Zeilen, eine pro Genre und Jahr
/02_Back-End/unload.php?type=rows&genre=Horror	nur die 59 Zeilen dieses Genres
 */