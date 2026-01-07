<?php 
function show_scoreboard_scores_shortcode($atts) {

    global $post;

    if (!$post) {
        return "No post found.";  // Return early if $post is null
    }
    $output = '';

    
    if (is_user_logged_in()) {
        // Get current user's friends
        $friend_ids = friends_get_friend_user_ids(bp_loggedin_user_id());
        array_unshift($friend_ids, bp_loggedin_user_id());

        $nomination_id = get_the_ID();
        $user_fav = get_user_meta(get_current_user_id(), 'fav_' . $nomination_id, true);
        $user_predict = get_user_meta(get_current_user_id(), 'predict_' . $nomination_id, true);
    
        // Arrays to store friends who favorited or predicted
        $friends_fav = [];
        $friends_predict = [];
    
        // Loop through each friend to check their preferences
        foreach ($friend_ids as $friend_id) {
            $friend_data = get_userdata($friend_id);
            if ($friend_data) {
                $friend_username = $friend_data->display_name;
                $friend_first_name = get_user_meta($friend_id, 'first_name', true);
                $friend_last_name = get_user_meta($friend_id, 'last_name', true);
                $friend_email = $friend_data->user_email;
                $friend_avatar = get_avatar($friend_id, 96); // 96px avatar size

                // Calculate lengths
                $id_length = strlen($friend_id);
                // Use hash-based color generation for better distribution
                // Combine user data into a string and create a numeric hash
                $hash_string = $friend_id . $friend_username . $friend_first_name . $friend_last_name . $friend_email;
                $hash = crc32($hash_string);
                
                // Convert to positive number and get last 3 digits (0-999 range)
                $randomcolornum = abs($hash) % 1000;

                // Get initials
                $initials = strtoupper(substr($friend_first_name, 0, 1) . substr($friend_last_name, 0, 1));
    
                $friends_predict[] = [
                    'id' => $friend_id,
                    'username' => $friend_username,
                    'first_name' => $friend_first_name,
                    'last_name' => $friend_last_name,
                    'email' => $friend_email,
                    'avatar' => $friend_avatar,
                    'initials' => $initials,
                    'rand_color' => $randomcolornum
                ];
            }
        }
    }


        $output .= '<ul class="friends">';



            foreach ($friends_predict as $friend) {
                $initials = strtoupper(substr($friend['first_name'], 0, 1) . substr($friend['last_name'], 0, 1));

                $output .= '<li class="friend" style="--randomcolornum:' . $friend['rand_color'] . '" data-friend-id="' . $friend['id'] . '" data-score="0">';
                $output .= '<a class="btn-hide" data-friend-id="' . $friend['id'] . '">Hide</a>';
                $output .= '<div class="friend-info">';
                $output .= '<div class="friend-photo">';
                $output .= $friend['avatar']; // Display avatar image
                $output .= '<div class="friend-initials">' . $initials . '</div>';
                $output .= '</div>';
                $output .= '<a href="' . esc_url(bp_core_get_userlink($friend['id'], false, true)) . '">' . esc_html($friend['username']) . '</a>';
                $output .= '<span class="friend-score">0</span>';
                $output .= '</div>';
                $output .= '<div class="trophies">';
                $output .= '</div>';
                $output .= '</li>';
            }



        $output .= '</ul>';
    


    return $output;

}

?>