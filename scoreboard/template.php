<div id="main-scoreboard-container">
    <header>
        <h1>Oscars Scoreboard</h1>
        <p>Under Exciting Construction!</p>
    </header>
    <section class="left">
        <div id="interval-control">
            <label for="interval-input">Reload Interval (seconds):</label>
            <input type="number" id="interval-input" min="1" step="1" value="5" />
            <div id="countdown-display">Next reload in: <span id="countdown">5</span>s</div>
        </div>
        <form id="submission-form">
            <input type="text" id="submission-input" placeholder="Type your message..." required />
            <button type="submit">Submit</button>
        </form>
    </section>
    <section class="right">
        <ul id="submissions-list"></ul>
    </section>
    <footer>
        <h2>Progress</h2>
        <div id="progress-bar-container">
            <div id="progress-bar"></div>
        </div>
    </footer>
</div>
