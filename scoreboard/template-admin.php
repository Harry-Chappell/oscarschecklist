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
        
        <h3>Post Notice</h3>
        <form id="notice-form">
            <input type="text" id="notice-input" placeholder="Type your notice..." required />
            <button type="submit">Post</button>
        </form>
        
        <details id="admin-notices-section">
            <summary>Manage Current Notices (<span id="notice-count">0</span>)</summary>
            <div id="admin-notices-container">
                <button id="clear-all-notices" class="danger-btn">Clear All Notices</button>
                <ul id="admin-notices-list"></ul>
            </div>
        </details>
    </section>
    <footer>
        <div id="progress-bar-container">
            <div id="progress-bar"></div>
        </div>
    </footer>
</div>
