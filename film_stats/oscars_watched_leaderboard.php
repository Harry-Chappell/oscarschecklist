<?php

/**
 * Shortcode to display a leaderboard of users by total-watched, using all_user_stats.json.
 * Renamed to oscars_watched_leaderboard. Now includes average at the bottom.
 */
function oscars_watched_leaderboard_shortcode($atts = []) {
    $atts = shortcode_atts(['top' => null], $atts);
    $top = is_numeric($atts['top']) && intval($atts['top']) > 0 ? intval($atts['top']) : null;
    $output_path = ABSPATH . 'wp-content/uploads/all_user_stats.json';

    if (!file_exists($output_path)) {
        return '<p>No leaderboard data found. Please generate it first.</p>';
    }
    $json = file_get_contents($output_path);
    $users = json_decode($json, true);
    if (!$users || !is_array($users)) {
        return '<p>Leaderboard data is invalid.</p>';
    }
    // Calculate average from all users
    $sum = 0;
    $count = 0;
    foreach ($users as $user) {
        $sum += intval($user['total-watched']);
        $count++;
    }
    // Sort by total-watched descending
    usort($users, function($a, $b) {
        return $b['total-watched'] <=> $a['total-watched'];
    });
    // Prepare user/friends logic
    $current_user_id = is_user_logged_in() ? get_current_user_id() : null;
    $current_username = null;
    $friends = [];
    // Example: get friends from user meta (replace with your logic)
    if ($current_user_id) {
        $current_user_data = get_userdata($current_user_id);
        $current_username = $current_user_data ? $current_user_data->user_login : null;
        if (function_exists('friends_get_friend_user_ids')) {
            $friends = friends_get_friend_user_ids($current_user_id);
        } else {
            $friends = [];
        }
    }
    // Build rank map and find user/friends
    $ranked_users = [];
    $user_ranks = [];
    $last_score = null;
    $rank = 0;
    $display_rank = 0;
    foreach ($users as $i => $user) {
        $rank++;
        if ($last_score !== $user['total-watched']) {
            $display_rank = $rank;
            $last_score = $user['total-watched'];
        }
        $user_ranks[$user['user_id']] = [
            'rank' => $display_rank,
            'user' => $user
        ];
        $ranked_users[] = [
            'rank' => $display_rank,
            'user' => $user
        ];
    }
    // Top X
    $top_users = $top !== null ? array_slice($ranked_users, 0, $top) : $ranked_users;
    // Collect user and friends (if not in top X)
    $user_and_friends_ids = [];
    if ($current_user_id && isset($user_ranks[$current_user_id])) {
        $user_and_friends_ids[] = $current_user_id;
    }
    foreach ($friends as $fid) {
        if (isset($user_ranks[$fid])) {
            $user_and_friends_ids[] = $fid;
        }
    }
    // Remove any already in top_users
    $added_ids = array_map(function($entry) { return $entry['user']['user_id']; }, $top_users);
    $user_and_friends_ids = array_diff($user_and_friends_ids, $added_ids);
    // Get user/friend entries in leaderboard order
    $user_and_friends_entries = [];
    foreach ($ranked_users as $entry) {
        if (in_array($entry['user']['user_id'], $user_and_friends_ids)) {
            $user_and_friends_entries[] = $entry;
        }
    }
    // Output
    $output = '<h2>Watched Leaderboard</h2><ul class="leaderboard">';
    $suffixes = function($n) {
        if ($n % 10 == 1 && $n % 100 != 11) return 'st';
        if ($n % 10 == 2 && $n % 100 != 12) return 'nd';
        if ($n % 10 == 3 && $n % 100 != 13) return 'rd';
        return 'th';
    };
    $shown_ids = [];
    foreach ($top_users as $entry) {
        $u = $entry['user'];
        $shown_ids[] = $u['user_id'];
        $username = esc_html($u['username']);
        $username = (!empty($u['public']) && !empty($user['username'])) ? esc_html($user['username']) : 'anonymous';
        $highlight = ($current_user_id && $u['user_id'] == $current_user_id) ? ' class="current-user"' : '';
        $output .= '<li' . $highlight . '><span class="rank">' . $entry['rank'] . '<sup>' . $suffixes($entry['rank']) . '</sup> </span> - ' . $username . ' - ' . intval($u['total-watched']) . '</li>';
    }
    $output .= '<li class="spacer"></li>'; // Spacer
    // Show current user and friends (in order) after top X
    foreach ($user_and_friends_entries as $entry) {
        $u = $entry['user'];
        $shown_ids[] = $u['user_id'];
        $username = esc_html($u['username']);
        $highlight = ($current_user_id && $u['user_id'] == $current_user_id) ? ' class="current-user"' : ' class="current-users-friend"';
        $output .= '<li' . $highlight . '><span class="rank">' . $entry['rank'] . '<sup>' . $suffixes($entry['rank']) . '</sup> </span> - ' . $username . ' - ' . intval($u['total-watched']) . '</li>';
    }
    $output .= '</ul>';
    if ($count > 0) {
        $avg = round($sum / $count, 1);
        $output .= '<div class="oscars-leaderboard-avg">Average: ' . $avg . '</div>';
    }
    return $output;
}
add_shortcode('oscars_watched_leaderboard', 'oscars_watched_leaderboard_shortcode');
