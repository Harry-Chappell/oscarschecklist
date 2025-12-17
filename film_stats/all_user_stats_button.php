<?php

/**
 * Admin button/shortcode to trigger compiling all user stats JSON.
 */
function oscars_all_user_stats_button_shortcode() {
    if (!current_user_can('manage_options')) {
        return 'You do not have permission.';
    }
    $output = '';
    if (isset($_POST['oscars_all_user_stats_generate'])) {
        $output .= '<div>' . oscars_compile_all_user_stats() . '</div>';
    }
    $output .= '<form method="post"><button type="submit" name="oscars_all_user_stats_generate">Generate All User Stats JSON</button></form>';
    return $output;
}
add_shortcode('all_user_stats_button', 'oscars_all_user_stats_button_shortcode');
