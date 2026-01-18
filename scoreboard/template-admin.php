<div id="main-scoreboard-container">
    <header>
        <h1>Oscars Scoreboard Admin</h1>
    </header>
    <section class="left admin-controls">
        <h3>Interval Control</h3>
        <div id="interval-control">
            <label for="interval-input">Reload Interval (seconds):</label>
            <input type="number" id="interval-input" min="1" step="1" value="5" />
            <div id="countdown-display">Next reload in: <span id="countdown">5</span>s</div>
        </div>
        
        <h3>Submit Message</h3>
        <form id="submission-form">
            <input type="text" id="submission-input" placeholder="Type your message..." required />
            <button type="submit">Submit</button>
        </form>
    </section>
    <footer>
        <div id="progress-bar-container">
            <div id="progress-bar"></div>
        </div>
    </footer>
</div>
