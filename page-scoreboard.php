<?php
/**
 * Template Name: Scoreboard
 * Description: Template for the Oscars Scoreboard page
 */

// Load scoreboard-specific functions
require_once(__DIR__ . '/scoreboard/functions.php');

get_header();

require_once(__DIR__ . '/scoreboard/template.php');

get_footer();
?>
