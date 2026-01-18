<?php
/**
 * Template Name: Scoreboard Admin
 * Description: Template for the Oscars Scoreboard Admin page
 */

// Load scoreboard-specific functions
require_once(__DIR__ . '/scoreboard/functions.php');

get_header();

require_once(__DIR__ . '/scoreboard/template-admin.php');

get_footer();
?>
