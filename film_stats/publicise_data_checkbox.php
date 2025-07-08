<?php

/**
 * Shortcode for user to toggle 'public' field in their user meta JSON.
 */
function oscars_publicise_data_checkbox_shortcode() {
    if (!is_user_logged_in()) {
        return '<p>Please log in to change your data publicity setting.</p>';
    }
    $user_id = get_current_user_id();
    $file_path = wp_upload_dir()['basedir'] . "/user_meta/user_{$user_id}.json";
    $public = false;
    if (file_exists($file_path)) {
        $data = json_decode(file_get_contents($file_path), true);
        $public = !empty($data['public']);
    }
    $checked = $public ? 'checked' : '';
    ob_start();
    ?>
    <form id="oscars-publicise-form" onsubmit="return false;">
        <input type="hidden" name="oscars_publicise_data_form_user" value="<?php echo esc_attr($user_id); ?>">
        <label>
            <input type="checkbox" name="oscars_publicise_data" value="1" <?php echo $checked; ?> onchange="oscarsTogglePublicise(this)">
            Publish my username.
        </label>
        <span id="oscars-publicise-status" style="margin-left:10px;color:green;display:none;">Saved!</span>
    </form>
    <script>
    function oscarsTogglePublicise(checkbox) {
        var form = document.getElementById('oscars-publicise-form');
        var data = new FormData(form);
        data.append('action', 'oscars_publicise_data_toggle');
        data.append('oscars_publicise_data', checkbox.checked ? '1' : '');
        var status = document.getElementById('oscars-publicise-status');
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            credentials: 'same-origin',
            body: data
        })
        .then(response => response.json())
        .then(json => {
            if (json.success) {
                status.style.display = 'inline';
                setTimeout(() => { status.style.display = 'none'; }, 1500);
            } else {
                alert('Error saving setting.');
            }
        });
    }
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('publicise_data_checkbox', 'oscars_publicise_data_checkbox_shortcode');

// AJAX handler
add_action('wp_ajax_oscars_publicise_data_toggle', function() {
    if (!is_user_logged_in()) {
        wp_send_json_error('Not logged in');
    }
    $user_id = get_current_user_id();
    $file_path = wp_upload_dir()['basedir'] . "/user_meta/user_{$user_id}.json";
    $public = !empty($_POST['oscars_publicise_data']);
    if (file_exists($file_path)) {
        $data = json_decode(file_get_contents($file_path), true);
        $data['public'] = $public;
        file_put_contents($file_path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        wp_send_json_success(['public' => $public]);
    }
    wp_send_json_error('File not found');
});