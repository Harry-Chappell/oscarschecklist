<?php
/**
 * Template Name: Scoreboard
 */
get_header(); ?>

<header class="content-header">
    <h1><?php the_title(); ?></h1>
</header>
<div class="scores">
    <div class="title">
        <h2>Scores</h2>
    </div>

    <?php echo do_shortcode('[show_scoreboard_scores]'); ?>

    <div class="empty">
        <h3>Add Friends to Compete</h3>
        <p>Oscars Scoreboard is better as a competition, get the people you're watching with to create accounts and cast their predictions on the homepage, and don't forget to <a href="https://stage.oscarschecklist.com/members/">add them as friends</a> to see them here!</p>
    </div>

    <a class="btn-unhide">Unhide All</a>

</div>
<div class="categories">
    <div class="title">
        <h2>Categories</h2>
    </div>
    <?php echo do_shortcode('[show_scoreboard_nominations]'); ?>

    <div class="empty">
        <h3>Waiting for first Category to Start</h3>
        <p>When the first category starts, the nominations will appear here along with your predictions. The winner will get updated when announced, and any correct predictors will see their score go up.</p>
        <p>If you've cast your predictions and added your friends, you do not need to do anything. If you go and do these now make sure you refresh this page to see them here.</p>
        <p>You do NOT need to refresh the page during, it'll update by itself. If all users were refreshing all the time then the system would go down, ruining it for everyone.</p>
    </div>
</div>

<script>
(function () {
    let refreshInterval = 10000;
    let intervalId;
    let lastActiveCategoryId = null;

    function fetchAndUpdate() {
        fetch("https://results.oscarschecklist.com/serve-results.php")
            .then(response => response.json())
            .then(data => {
                let categoriesContainer = document.querySelector(".categories");

                if (data.aC === 0) {
                    document.querySelectorAll(".awards-category.active").forEach(category => {
                        category.classList.remove("active");
                    });
                    lastActiveCategoryId = null;
                } else if (data.aC && data.aC !== lastActiveCategoryId) {
                    let activeCategory = document.querySelector(`.awards-category[data-category-id="${data.aC}"]`);
                    if (activeCategory) {
                        activeCategory.parentElement.querySelectorAll(".awards-category").forEach(category => {
                            category.classList.remove("active");
                        });

                        activeCategory.classList.add("active");
                        lastActiveCategoryId = data.aC;

                        if (categoriesContainer) {
                            categoriesContainer.scrollTop = categoriesContainer.scrollHeight;
                        }
                    }
                }

                if (data.wI) {
                    let winningNomination = document.querySelector(`.nomination[data-nomination-id="${data.wI}"]`);
                    if (winningNomination) {
                        winningNomination.classList.add("winner");
                    }
                }

                if (data.oS) {
                    document.body.className = document.body.className
                        .split(" ")
                        .filter(cls => !cls.startsWith("state-"))
                        .join(" ");
                    document.body.classList.add(`state-${data.oS}`);
                }

                if (data.rI && data.rI * 1000 !== refreshInterval) {
                    refreshInterval = data.rI * 1000;
                    clearInterval(intervalId);
                    intervalId = setInterval(fetchAndUpdate, refreshInterval);
                }

                let friendScores = {};
                document.querySelectorAll(".nomination.winner .predictions [data-friend-id]").forEach(prediction => {
                    let friendId = prediction.getAttribute("data-friend-id");
                    friendScores[friendId] = (friendScores[friendId] || 0) + 1;
                });

                document.querySelectorAll(".friend").forEach(friend => {
                    let friendId = friend.getAttribute("data-friend-id");
                    let score = friendScores[friendId] || 0;
                    friend.setAttribute("data-score", score);
                    friend.style.setProperty("--friend-score", score);
                    let scoreSpan = friend.querySelector(".friend-score");
                    if (scoreSpan) {
                        scoreSpan.textContent = score;
                    }

                    let trophiesContainer = friend.querySelector(".trophies");
                    trophiesContainer.innerHTML = "";

                    document.querySelectorAll(".nomination.winner .predictions [data-friend-id=\"" + friendId + "\"]").forEach(prediction => {
                        let nomination = prediction.closest(".nomination");
                        if (nomination) {
                            let filmName = nomination.querySelector(".film-name p")?.textContent || "Unknown Film";
                            let nomineeName = nomination.querySelector(".nominee-name h3")?.textContent || "Unknown Nominee";
                            let category = nomination.closest(".awards-category").querySelector(".category-title h2")?.textContent || "Unknown Category";

                            let trophyDiv = document.createElement("div");
                            trophyDiv.classList.add("trophy");

                            let cntrDiv = document.createElement("div");
                            cntrDiv.classList.add("cntr");

                            let filmDiv = document.createElement("div");
                            filmDiv.classList.add("trophy-film");
                            filmDiv.textContent = filmName;

                            let nomineeDiv = document.createElement("div");
                            nomineeDiv.classList.add("trophy-nominee");
                            nomineeDiv.textContent = nomineeName;

                            let categoryDiv = document.createElement("div");
                            categoryDiv.classList.add("trophy-category");
                            categoryDiv.textContent = category;

                            cntrDiv.appendChild(categoryDiv);
                            cntrDiv.appendChild(filmDiv);
                            cntrDiv.appendChild(nomineeDiv);
                            trophyDiv.appendChild(cntrDiv);
                            trophiesContainer.appendChild(trophyDiv);
                        }
                    });
                });
            })
            .catch(error => console.error("Error fetching results:", error));
    }

    fetchAndUpdate();
    intervalId = setInterval(fetchAndUpdate, refreshInterval);
})();


document.addEventListener("DOMContentLoaded", function () {
    document.body.addEventListener("click", function (event) {
        if (event.target.classList.contains("btn-hide")) {
            let friendId = event.target.getAttribute("data-friend-id");
            
            if (friendId) {
                document.querySelectorAll(`[data-friend-id="${friendId}"]`).forEach(element => {
                    element.classList.add("hidden");
                });
            }
        }

        if (event.target.classList.contains("btn-unhide")) {
            document.querySelectorAll(".hidden").forEach(element => {
                element.classList.remove("hidden");
            });
        }
    });
});

</script>

<style>
.hidden {
    display: none !important;
}
.empty {
    margin: 100px 20px;
    order: -1;
}
.empty h3 {
    font-size: 1.2em;
}
.empty p {
    font-size: 0.9em;
}
div.scores:has(>ul>li:nth-child(2)) .empty,
.categories:has(*:is(.active, .winner)) .empty {
    display: none;
}
.categories {
    display: flex;
    flex-flow: column;
    scroll-behavior: smooth;
    gap: 25px;
    border-left: 1px solid var(--theme-palette-color-6);
    padding: 10px 20px 30px;
}
body.state-showtime .awards-category {
    display: none;
}
body.state-showtime .awards-category:has(.winner), 
body.state-showtime .awards-category.active,
body.state-finished .awards-category:has(.winner), 
body.state-finished .awards-category.active {
    display: block;
    padding: 10px;
    border-radius: 10px;
    background: var(--theme-palette-color-7);
}
.awards-category.active {
    order: 1;
    animation: activeCategory 1s linear 0s infinite alternate;
}
@keyframes activeCategory {
    from {
        border: 1px solid var(--theme-palette-color-6);
        box-shadow: 0 0 5px var(--theme-palette-color-1);
    }
    to {
        border: 1px solid var(--theme-palette-color-1);
        box-shadow: 0 0 20px var(--theme-palette-color-1);
    }
}
.awards-category .category-title {
    position: static;
    background: none !important;
    margin: 0;
}
ul.nominations-list {
    flex-flow: column;
    gap: 5px;
    margin: 0 !important;
    padding: 0;
}
ul.nominations-list > li {
    width: 100%;
    flex-flow: column wrap;
    padding: 3px;
    border-radius: 7px;
    justify-content: center;
    height: 90px;
    align-content: space-between;
    box-shadow: 0 5px 15px black;
    box-shadow: 0 5px 15px #d9d9d9;
}
.nom-info {
    display: flex;
    flex-flow: column wrap;
    justify-content: center;
    align-items: start;
    height: 100%;
}
.friends {
    order: 10;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: end;
    height: 100%;
}
.safari-desktop .friends {
    align-items: initial;
    min-width: 150px;
}
.friends .predictions {
    height: 57%;
}
.friends .favorites {
    height: 43%;
}
.friends svg {
    height: 100%;
    aspect-ratio: 0.6;
    fill: var(--theme-palette-color-5);
    opacity: 0.5;
}
.friends > div {
    display: flex;
    justify-content: flex-start;
    padding: 5px;
    gap: 10px;
}
span.friend {
    height: 100%;
    display: flex;
    position: relative;
    background: var(--theme-palette-color-6);
    background-color: hsl(calc(var(--randomcolornum) * 0.36) 90% 70%);
    border-radius: 100%;
    aspect-ratio: 1;
}
span.friend img {
    aspect-ratio: 1;
    border-radius: 100%;
    height: 100%;
    width: auto;
    z-index: 1;
}
span.friend a {
    position: absolute;
    inset: auto 50% 0 auto;
    transform: translate(50%, 100%);
    font-size: 10px;
    width: auto;
    white-space: nowrap;
    display: none;
}
span.friend:hover a {
    display: initial;
}
.friend-initials {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -43%);
    font-size: 14px;
    font-weight: bold;
    color: var(--theme-palette-color-3);
    color: black;
}





ul.nominations-list.nominee_visibility-prominent .nominees-photo {
    width: 50px;
    height: 100%;
}
ul.nominations-list.nominee_visibility-prominent .film-poster {
    width: 40px;
    margin: 0 10px 0 5px;
    object-position: bottom;
    height: 100%;
}
ul.nominations-list.nominee_visibility-prominent .film-poster img {
    object-position: bottom;
}
ul.nominations-list > li.winner {
    order: -1;
    box-shadow: 0 0 10px var(--theme-palette-color-2);
    border-color: var(--theme-palette-color-1);
}
ul.nominations-list h3 {
    margin: 0;
}
.category-title h2 {
    margin-bottom: -8px;
    font-size: 40px;
    letter-spacing: -3px;
    line-height: 1;
}

.categories, .scores {
    position: relative;
}
.categories > .title, .scores > .title {
    position: sticky;
    z-index: 2;
    inset: 0 -10px auto;
    height: 70px !important;
    display: flex;
    margin: 0;
}
.categories > .title h2, .scores > .title h2 {
    background: rgb(255 255 255 / 75%);
    padding: 10px;
    position: relative;
    bottom: 10px;
    width: 100%;
    font-size: 2em;
    align-items: end;
    display: flex;
    text-align: center;
    justify-content: center;
    backdrop-filter: blur(10px);
    font-weight: 100;
}
html[data-color-mode="dark"] .categories > .title h2, 
html[data-color-mode="dark"] .scores > .title h2 {
    background: rgb(0 0 0 / 75%);
}
html[data-color-mode="dark"] ul.nominations-list > li {
    box-shadow: 0 5px 15px black;
}
@media (prefers-color-scheme: dark) {
    html:not([data-color-mode="light"]) .categories > .title h2, 
    html:not([data-color-mode="light"]) .scores > .title h2 {
        background: rgb(0 0 0 / 75%);
    }
    html:not([data-color-mode="light"]) ul.nominations-list > li {
    box-shadow: 0 5px 15px black;
}
}


ul.nominations-list > li.winner:before {
    box-shadow: 0 0 10px var(--theme-palette-color-2);
    inset: 0 auto auto 0;
    height: 100%;
    background-position: center;
    border-radius: 5px 0 0 5px;
    content: none;
}
ul.nominations-list > li.winner .nom-info {
    /* margin-left: 30px; */
}
span.friend-score {
    font-size: 3em;
}


ul.friends {
    display: flex;
    flex-direction: column-reverse;
    list-style-type: none;
    padding: 0px 20px;
    margin: 0;
    width: 100%;
    justify-content: start;
    height: auto;
}
li.friend {
    width: 100%;
    padding: 20px 0;
    border-bottom: 1px solid var(--theme-palette-color-6);
    display: block;
    position: relative;
    order: var(--friend-score);
}
li.friend:hover {
    z-index: 2;
}
.friend-info {
    display: flex;
    align-items: center;
    justify-content: start;
    border-radius: 100%;
    height: 50px;
    width: 100%;
    position: relative;
}
.friend-photo {
    background: var(--theme-palette-color-5);
    border-radius: 100%;
    margin-right: 10px;
    background-color: hsl(calc(var(--randomcolornum) * 0.36) 90% 70%);
    position: relative;
}
.friend-info img {
    height: 100%;
    width: auto;
    border-radius: 100%;
    position: relative;
    z-index: 1;
}
.friend-photo .friend-initials {
    position: absolute;
    inset: 50% 0 0 0;
    height: 100%;
    transform: translate(0%, -45%);
    width: 50px;
    aspect-ratio: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
}
span.friend-score {
    font-size: 2em;
    position: absolute;
    inset: 0 0 0 auto;
}

.trophies {
    display: flex;
    gap: 5px;
    flex-flow: row wrap;
    padding-top: 10px;
}
.trophy {
    background: url(https://stage.oscarschecklist.com/wp-content/uploads/2025/01/trophy-solid.png);
    height: 40px;
    width: 30px;
    background-size: 100%;
    background-repeat: no-repeat;
    background-position: center;
    position: relative;
}
.trophy > div {
    display: none;
}
.trophy:hover > div {
    display: flex;
    background: var(--theme-palette-color-7);
    min-width: 100px;
    z-index: 1;
    position: absolute;
    top: 100%;
    left: 50%;
    transform: translateX(-50%);
    font-size: 12px;
    line-height: 1;
    text-align: center;
    justify-content: center;
    padding: 5px;
    border-radius: 5px;
    flex-direction: column;
    border: 1px solid var(--theme-palette-color-6);
}
.trophy:hover > div {
    left: 0;
    transform: none;
    text-align: left;
}
.trophy-category {
    border-bottom: 1px solid var(--theme-palette-color-6);
    padding: 5px;
    margin-bottom: 5px;
}
.trophy-nominee, .trophy-film {
    padding: 5px;
}


.awards-category.category-best-picture[data-category-id] {
    order: unset;
}
ul.nominations-list:not(.nominee_visibility-prominent) .film-poster {
    width: 50px;
    height: 100%;
    margin-right: 10px;
}
ul.nominations-list:not(.nominee_visibility-prominent) .nominees-photo {
    width: 40px;
    margin: 0 10px 0 5px;
    object-position: bottom;
    height: 100%;
}
ul.nominee_visibility-shown ul.nominees-photo {
    display: none;
}
ul.nominations-list .nominees-name, ul.nominations-list details.nominees {
    display: flex;
    flex-flow: row wrap;
}


ul.nominations-list.category-best-picture {
    flex-flow: row wrap;
}
ul.nominations-list.category-best-picture > li {
    width: calc((100% - 5px) / 2);
}
.category-best-picture > li:not(:hover) a.film-name {
    display: none;
}
.category-best-picture a.film-name {
    position: absolute;
    background: var(--theme-palette-color-7);
    padding: 10px 10px 7px;
    border-radius: 3px;
    border: 1px solid var(--theme-palette-color-6);
    box-shadow: 0 0 10px var(--theme-palette-color-8);
    inset: auto 10%;
    z-index: 2;
    width: 80%;
    text-align: center;
}
ul.nominations-list.category-best-picture > li:hover {
    filter: brightness(1.3);
    z-index: 1;
}
ul.nominations-list.category-best-picture > li .friends {
    width: calc(100% - 50px);
}
ul.nominations-list.category-best-picture .nom-info {
    width: auto;
}
ul.nominations-list.category-best-picture a.film-name {
    z-index: 0;
    background: none;
    border: none;
    box-shadow: none;
    width: calc(100% - 50px);
    inset: 0 0 0 auto;
    text-align: left;
    align-items: center;
    display: flex !important;
}
ul.nominations-list.category-best-picture a.film-name h3 {
    font-size: 14px;
}
ul.nominations-list.category-best-picture .friends {
    background: linear-gradient(to left, var(--theme-palette-color-7) 50%, transparent);
    z-index: 1;
}

.friend a.btn-hide,
a.btn-unhide {
    display: none;
}


@media screen and (min-width: 651px) {
    .friend:hover a.btn-hide {
        display: flex;
        position: absolute;
        z-index: 2;
        background: var(--theme-palette-color-7);
        inset: 3px auto auto 50px;
        align-items: center;
        justify-content: center;
        border-radius: 5px;
        border: 1px solid var(--theme-palette-color-6);
        cursor: pointer;
        padding: 2px 25px 0;
        /* transform: translateX(50%); */
    }

    ul:has(li.hidden) ~ a.btn-unhide {
        position: absolute;
        inset: auto 20px 0;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 10px;
        border-radius: 5px;
        background: var(--theme-palette-color-8);
        border: 1px solid var(--theme-palette-color-8);
        font-size: 10px;
        z-index: 10;
    }
    ul:has(li.hidden) ~ a.btn-unhide:hover {
        background: var(--theme-palette-color-7);
        border: 1px solid var(--theme-palette-color-6);
        cursor: pointer;
    }
}


@media screen and (max-width: 1000px) {
    .scores {
        width: 250px;
    }
    .categories {
        width: calc(100% - 250px);
    }
    li.friend {
        padding: 10px 0;
    }
    .friend-info {
        flex-flow: row wrap;
        height: auto;
    }
    .friend-photo {
        height: 30px;
    }
    .friend-photo .friend-initials {
        height: 30px;
        transform: translate(0%, -50%);
        width: 30px;
        font-size: 11px;
    }
    .friend-info a {
        width: 100%;
        order: 0;
    }
    .trophies {
        padding-top: 5px;
    }
    .trophy {
        height: 25px;
        width: 20px;
    }
    .friends .predictions {
        height: auto;
        gap: 5px;
    }
    .friends .favorites {
        height: auto;
        gap: 5px;
    }
    .friend-initials {
        font-size: 9px;
    }
    ul.nominations-list:not(.nominee_visibility-prominent) .film-poster {
        margin-right: 5px;
    }
    ul.nominations-list > li .friends svg {
        height: 25px;
    }
    ul.nominations-list > li span.friend {
        height: 25px;
    }
    .nom-info {
        max-width: 70%;
        width: 300px;
        align-content: flex-start;
    }
    .category-title h2 {
        font-size: 25px;
        letter-spacing: 0px;
    }
}

@media screen and (max-width: 650px) {
    main#main {
        align-content: flex-start;
    }
    .scores, .admin-bar .scores {
        height: 120px;
        width: 100%;
        padding: 10px 0;
        overflow: scroll;
    }
    .categories {
        height: calc(100vh - 200px);
        width: 100%;
        border-left: 0px;
        border-bottom: 1px solid var(--theme-palette-color-6);
        padding-bottom: 70px;
    }
    .admin-bar .categories {
        height: calc(100vh - 196px);
        padding-bottom: 70px;
    }

    header.content-header {
        height: 50px;
    }
    header.content-header h1 {
        font-size: 25px;
    }
    .categories > .title, .scores > .title {
        height: 40px !important;
    }
    .categories > .title h2, .scores > .title h2 {
        padding: 5px;
        font-size: 20px;
    }
    ul.friends {
        display: flex;
        flex-direction: row-reverse;
        padding: 0px 10px;
        gap: 10px;
        margin-top: -20px;
        width: auto;
        width: max-content;
        position: relative;
    }
    li.friend {
        padding: 5px 0;
        border-bottom: none;
        width: min-content;
        min-width: 75px;
    }
    .trophies {
        display: none;
    }
    span.friend-score {
        position: static;
    }
    .friend-info {
        justify-content: center;
    }
    .friend-info a {
        width: 100%;
        order: 1;
        text-align: center;
        white-space: pre;
    }
    .awards-category .category-title {
        padding-top: 5px;
    }
    .awards-category .category-title h2 {
        font-size: 20px;
    }
    ul.nominations-list > li {
        height: 65px;
    }
    ul.nominations-list.nominee_visibility-prominent .nominees-photo,
    ul.nominations-list:not(.nominee_visibility-prominent) .film-poster {
        width: 40px;
    }
    ul.nominations-list.nominee_visibility-prominent .film-poster {
        width: 30px;
    }
    a.nominee-name h3 {
        font-size: 12px;
    }
    ul.nominations-list.nominee_visibility-prominent .film-name p {
        font-size: 11px;
    }
    .friends {
        position: absolute;
        right: 0;
    }
    ul.nominations-list.category-music-original-song .nominees-name, 
    ul.nominations-list.category-music-original-song details.nominees {
        display: none;
    }
    .friend-photo .friend-initials {
        transform: translate(0%, -50%);
    }
}

</style>

<?php get_footer(); 