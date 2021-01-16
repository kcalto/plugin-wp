<?php

/**
 * Plugin Name: Kcalto
 * Description: Nutrition values for each reported recipe on the Kcalto platform.
 * Author: Team Kcalto
 * Author URI: https://kcalto.com/
 */

if (!defined('KCALTO_FILE')) {
	define('KCALTO_FILE', __FILE__);
}

if (!defined('KCALTO_SLUG')) {
	// Changing this breaks remote updates
	define('KCALTO_SLUG', 'kcalto/kcalto.php');
}

if (!defined('KCALTO_SETTINGS')) {
	define('KCALTO_SETTINGS', 'kcalto');
}

if (!defined('KCALTO_TRANSIENT')) {
	define('KCALTO_TRANSIENT', 'update_' . KCALTO_SLUG);
}

if (!defined('KCALTO_REMOTE_RELEASES_URL')) {
	define('KCALTO_REMOTE_RELEASES_URL', 'http://releases.kcalto.com/wordpress/info.json');
}


require_once(dirname(KCALTO_FILE) . '/src/kcalto.php');
