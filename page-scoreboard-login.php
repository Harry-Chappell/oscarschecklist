<?php
/**
 * Template Name: Scoreboard Login
 * Description: Template for the Oscars Scoreboard Login page
 */

get_header();
?>

<?php
if (!is_user_logged_in()) {
	auth_redirect();
}

$current_user = wp_get_current_user();
$friends_data = array();
$friend_ids = array();

if (function_exists('bp_is_active') && bp_is_active('friends') && function_exists('friends_get_friend_user_ids')) {
	$friend_ids = friends_get_friend_user_ids($current_user->ID);
	if (!empty($friend_ids)) {
		foreach ($friend_ids as $friend_id) {
			$friend_user = get_userdata($friend_id);
			if (!$friend_user) {
				continue;
			}

			if (function_exists('bp_core_fetch_avatar')) {
				$avatar_url = bp_core_fetch_avatar(array(
					'item_id' => $friend_id,
					'type' => 'thumb',
					'html' => false,
				));
			} else {
				$avatar_url = get_avatar_url($friend_id);
			}

			$friends_data[] = array(
				'id' => $friend_id,
				'username' => $friend_user->user_login,
				'display_name' => $friend_user->display_name,
				'avatar' => $avatar_url,
			);
		}
	}
}


$cookie_users = array();

$current_avatar = function_exists('bp_core_fetch_avatar')
	? bp_core_fetch_avatar(array(
		'item_id' => $current_user->ID,
		'type' => 'thumb',
		'html' => false,
	))
	: get_avatar_url($current_user->ID);

$current_hash_string = $current_user->ID . $current_user->user_login . $current_user->user_firstname . $current_user->user_lastname . $current_user->user_email;
$current_hash = crc32($current_hash_string);
$current_rand_color = abs($current_hash) % 1000;

$cookie_users[] = array(
	'id' => $current_user->ID,
	'display_name' => $current_user->display_name,
	'avatar' => $current_avatar,
	'rand_color' => $current_rand_color,
	'type' => 'current',
);

if (!empty($friends_data)) {
	foreach ($friends_data as $friend) {
		$friend_user = get_userdata($friend['id']);
		if (!$friend_user) {
			continue;
		}

		$friend_hash_string = $friend_user->ID . $friend_user->user_login . $friend_user->user_firstname . $friend_user->user_lastname . $friend_user->user_email;
		$friend_hash = crc32($friend_hash_string);
		$friend_rand_color = abs($friend_hash) % 1000;

		$cookie_users[] = array(
			'id' => $friend['id'],
			'display_name' => $friend_user->display_name,
			'avatar' => $friend['avatar'],
			'rand_color' => $friend_rand_color,
			'type' => 'friend',
		);
	}
}

setcookie('oscars_scoreboard_user_ids', wp_json_encode($cookie_users), array(
	'expires' => time() + 3600,
	'path' => '/',
	'domain' => '.oscarschecklist.com',
	'secure' => is_ssl(),
	'httponly' => true,
	'samesite' => 'Lax',
));

$payload = array(
	'currentUser' => array(
		'id' => $current_user->ID,
		'username' => $current_user->user_login,
		'display_name' => $current_user->display_name,
		'avatar' => function_exists('bp_core_fetch_avatar')
			? bp_core_fetch_avatar(array(
				'item_id' => $current_user->ID,
				'type' => 'thumb',
				'html' => false,
			))
			: get_avatar_url($current_user->ID),
	),
	'friends' => $friends_data,
);
?>

<h1>One sec...</h1>
<!-- <p>Preparing your scoreboard…</p>
<p><a href="https://scoreboard.oscarschecklist.com/" class="scoreboard-back-link">Continue to Scoreboard</a></p> -->
<style>
    h1 {
        position: fixed;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
    }
</style>
<script>
	(function () {
		var payload = <?php echo wp_json_encode($payload); ?>;
		console.log('[Scoreboard Login] payload', payload);
		console.log('[Scoreboard Login] cookie set', true);
		window.location.href = 'https://scoreboard.oscarschecklist.com/';
	})();
</script>

<?php
get_footer();
?>
