<?php
/**
 * Plugin Name: Kcalto
 * Description: Nutrition values for each reported recipe on the Kcalto platform.
 * Plugin URI: https://github.com/kcalto/plugin-wp
 * Author: Kcalto
 * Author URI: https://kcalto.com/
 */

if ( ! defined( 'KCALTO_FILE' ) ) {
	define( 'KCALTO_FILE', __FILE__ );
}

require_once( dirname( KCALTO_FILE ) . '/src/kcalto.php' );

?>