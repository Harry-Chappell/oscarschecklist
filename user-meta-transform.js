document.addEventListener("DOMContentLoaded", function () {
    const transformBtn = document.getElementById("transform-user-meta");
    const countOutput = document.getElementById("meta-count-output");
    const chartCanvas = document.getElementById("watchedChart");

    let chart;

    // Add a loading spinner to a button
    function setLoading(button, isLoading) {
        if (isLoading) {
            button.dataset.originalText = button.innerHTML;
            button.innerHTML = `<span class="spinner" style="display:inline-block;width:16px;height:16px;border:2px solid #fff;border-top-color:transparent;border-radius:50%;animation:spin 0.6s linear infinite;margin-right:8px;"></span>Loading...`;
            button.disabled = true;
        } else {
            button.innerHTML = button.dataset.originalText;
            button.disabled = false;
        }
    }

    // Fetch stats from existing JSON and generate chart
    function fetchAndRenderStats() {
        countOutput.textContent = "Loading stats...";

        fetch(userMetaAjax.ajax_url, {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams({
                action: "count_user_meta_stats",
                nonce: userMetaAjax.nonce
            })
        })
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                countOutput.textContent = `Error: ${data.data}`;
                return;
            }

            const result = data.data;
            countOutput.innerHTML = `
                Watched: <strong>${result.watchedCount}</strong><br>
                Favourites: <strong>${result.favouritesCount}</strong><br>
                Predictions: <strong>${result.predictionsCount}</strong>
            `;

            const years = Object.keys(result.watchedByYear);
            const values = Object.values(result.watchedByYear);

            if (chart) chart.destroy();

            chart = new Chart(chartCanvas, {
                type: 'bar',
                data: {
                    labels: years,
                    datasets: [{
                        label: 'Watched Films by Year',
                        data: values,
                        backgroundColor: '#c59d40',
                        borderRadius: 1,
                        barPercentage: 1.0,
                        categoryPercentage: 1.0
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: false },
                        tooltip: { enabled: true }
                    },
                    scales: {
                        x: {
                            ticks: { display: false },
                            grid: { display: false, drawBorder: false }
                        },
                        y: {
                            ticks: { display: false },
                            grid: { display: false, drawBorder: false },
                            beginAtZero: true
                        }
                    }
                }
            });
            displayWatchedFilmsByDecade(result.watched);  
        });
    }

    // Initial load of JSON stats on page load
    fetchAndRenderStats();

    // Handle refresh button click (re-transform and re-render)
    if (transformBtn) {
        transformBtn.textContent = "Refresh User Meta";
        transformBtn.addEventListener("click", function () {
            setLoading(transformBtn, true);

            fetch(userMetaAjax.ajax_url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'transform_user_meta',
                    nonce: userMetaAjax.nonce
                })
            })
            .then(response => response.json())
            .then(data => {
                setLoading(transformBtn, false);

                if (data.success) {
                    console.log("Meta refreshed. Rebuilding chart...");
                    fetchAndRenderStats();
                } else {
                    alert("Error: " + (data.data || "Unknown error"));
                }
            });
        });
    }
});

// Add basic spinner styles
const style = document.createElement("style");
style.textContent = `
@keyframes spin {
    to { transform: rotate(360deg); }
}
`;
document.head.appendChild(style);


function displayWatchedFilmsByDecade(films) {
    const container = document.getElementById("watched-by-decade");
    const filmsByDecade = {};

    // Group films by decade
    films.forEach(film => {
        const year = parseInt(film['film-year'], 10);
        const decade = Math.floor(year / 10) * 10;
        const label = `${decade}s`;

        if (!filmsByDecade[label]) filmsByDecade[label] = [];
        filmsByDecade[label].push({
            title: film['film-name'],
            year,
            id: film['film-id'],
            url: film['film-url'],
        });
    });

    // Sort and build output
    const sortedDecades = Object.keys(filmsByDecade).sort((a, b) => parseInt(b) - parseInt(a));
    let html = "<h2>Watched Films by Decade</h2>";

    sortedDecades.forEach(decade => {
        const films = filmsByDecade[decade].sort((a, b) => a.year - b.year);
        html += `<details><summary><h3>${decade} (${films.length})</h3></summary><ul>`;
        films.forEach(film => {
            html += `<li><a href="https://stage.oscarschecklist.com/films/${film.url}">${film.title}</a></li>`;
        });
        html += `</ul></details>`;
    });

    container.innerHTML = html;
}