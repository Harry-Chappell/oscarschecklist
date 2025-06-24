<?php

/**
 * For every user, count correct/incorrect predictions and save to their JSON. Add a shortcode button for admins.
 */
function oscars_update_all_user_prediction_stats() {
    $user_meta_dir = ABSPATH . 'wp-content/uploads/user_meta/';
    if (!is_dir($user_meta_dir)) return 'User meta directory not found.';
    $count = 0;
    foreach (glob($user_meta_dir . 'user_*.json') as $file) {
        $json = file_get_contents($file);
        $data = json_decode($json, true);
        if (!is_array($data) || empty($data['predictions']) || !is_array($data['predictions'])) continue;
        $correct = 0;
        $incorrect = 0;
        foreach ($data['predictions'] as $nom_id) {
            $cat_terms = wp_get_post_terms($nom_id, 'award-categories');
            if (is_wp_error($cat_terms) || empty($cat_terms)) {
                $incorrect++;
                continue;
            }
            $is_winner = false;
            foreach ($cat_terms as $term) {
                if ($term->slug === 'winner') {
                    $is_winner = true;
                    break;
                }
            }
            if ($is_winner) {
                $correct++;
            } else {
                $incorrect++;
            }
        }
        $data['correct-predictions'] = $correct;
        $data['incorrect-predictions'] = $incorrect;
        $total = $correct + $incorrect;
        $data['correct-prediction-rate'] = $total > 0 ? round($correct / $total, 3) : null;
        file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $count++;
    }
    return "Prediction stats updated for $count users.";
}

function oscars_update_all_user_prediction_stats_button_shortcode() {
    if (!current_user_can('manage_options')) {
        return 'You do not have permission.';
    }
    $output = '';
    if (isset($_POST['oscars_update_all_user_prediction_stats'])) {
        $output .= '<div>' . oscars_update_all_user_prediction_stats() . '</div>';
    }
    $output .= '<form method="post"><button type="submit" name="oscars_update_all_user_prediction_stats">Update All User Prediction Stats</button></form>';
    return $output;
}
add_shortcode('update_all_user_prediction_stats_button', 'oscars_update_all_user_prediction_stats_button_shortcode');
