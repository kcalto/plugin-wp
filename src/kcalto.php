<?php
require_once('widget.php');
require_once('version.php');

if ( ! defined( 'KCALTO_SLUG' ) ) {
	define( 'KCALTO_SLUG', 'KCALTO' );
}

$INFO_URL = 'http://releases.kcalto.com/wordpress/info.json';

function kcalto_hook_activation() {
  // TODO
}
register_activation_hook( __FILE__, 'kcalto_hook_activation' );

function kcalto_hook_deactivation() {
  // TODO: set_transient( 'update_' . KCALTO_SLUG, false, 0 ); // this does not work
  // Maybe try delete_transient???
}
register_deactivation_hook( __FILE__, 'kcalto_hook_deactivation' );

/*
* Source: https://rudrastyh.com/wordpress/self-hosted-plugin-update.html
* $res empty at this step
* $action 'plugin_information'
* $args stdClass Object ( [slug] => woocommerce [is_ssl] => [fields] => Array ( [banners] => 1 [reviews] => 1 [downloaded] => [active_installs] => 1 ) [per_page] => 24 [locale] => en_US )
*/
function kcalto_plugin_info( $res, $action, $args ){
  global $INFO_URL;

	// do nothing if this is not about getting plugin information
	if( 'plugin_information' !== $action ) {
		return false;
	}
 
	$plugin_slug = KCALTO_SLUG; // we are going to use it in many places in this function
 
	// do nothing if it is not our plugin
	if( $plugin_slug !== $args->slug ) {
		return false;
	}
 
	// trying to get from cache first
	if( false == $remote = get_transient( 'update_' . $plugin_slug ) ) {
 
		// info.json is the file with the actual plugin information on your server
		$remote = wp_remote_get( $INFO_URL, array(
			'timeout' => 10,
			'headers' => array(
				'Accept' => 'application/json'
			) )
		);
 
		if ( ! is_wp_error( $remote ) && isset( $remote['response']['code'] ) && $remote['response']['code'] == 200 && ! empty( $remote['body'] ) ) {
			set_transient( 'update_' . $plugin_slug, $remote, 12 * 60 * 60 ); // 12 hours cache
		}
 
  }
  
	if( ! is_wp_error( $remote ) && isset( $remote['response']['code'] ) && $remote['response']['code'] == 200 && ! empty( $remote['body'] ) ) {
 
		$remote = json_decode( $remote['body'] );
    $res = new stdClass();
 
		$res->name = $remote->name;
		$res->slug = $plugin_slug;
		$res->version = $remote->version;
		$res->tested = $remote->tested;
		$res->requires = $remote->requires;
		$res->download_link = $remote->download_url;
		$res->trunk = $remote->download_url;
		$res->requires_php = $remote->requires_php;
		$res->last_updated = $remote->last_updated;
		$res->sections = array(
			'description' => $remote->sections->description,
			'installation' => $remote->sections->installation,
			'changelog' => $remote->sections->changelog
    );
    
		return $res;
 
	}
 
	return false;
 
}
add_filter('plugins_api', 'kcalto_plugin_info', 20, 3);


/*
Source: https://rudrastyh.com/wordpress/self-hosted-plugin-update.html
*/
function kcalto_push_update( $transient ){
  global $INFO_URL;
 
	if ( empty($transient->checked ) ) {
      return $transient;
  }

  $plugin_slug = KCALTO_SLUG; // we are going to use it in many places in this function

 
	// TODO: Try the cache first
	// if( false == $remote = get_transient( 'update_' . $plugin_slug ) ) {
 
		// info.json is the file with the actual plugin information on your server
		$remote = wp_remote_get( $INFO_URL, array (
			'timeout' => 10,
			'headers' => array(
				'Accept' => 'application/json'
			) )
		);
 
		if ( !is_wp_error( $remote ) && isset( $remote['response']['code'] ) && $remote['response']['code'] == 200 && !empty( $remote['body'] ) ) {
			set_transient( 'update_' . $plugin_slug, $remote, 43200 ); // 12 hours cache
		}
 
  // }
  
  $current_version = KCALTO_CURRENT_VERSION;
  $plugin = 'kcalto/kcalto.php';

	if( $remote ) {
    
    $remote = json_decode( $remote['body'] ); 

    var_dump($plugin_slug, $remote->version, $current_version);

		// your installed plugin version should be on the line below! You can obtain it dynamically of course 
		if( $remote && version_compare( $current_version, $remote->version, '<' ) && version_compare($remote->requires, get_bloginfo('version'), '<' ) ) {
      $res = new stdClass();
      
			$res->slug = $plugin_slug;
			$res->plugin = $plugin;
			$res->new_version = $remote->version;
			$res->tested = $remote->tested;
      $res->package = $remote->download_url;
      
      $transient->response[$res->plugin] = $res;
    }

  }

  $transient->checked[$plugin] = $current_version;

  return $transient;
}
add_filter('site_transient_update_plugins', 'kcalto_push_update' );

// TODO: Remote plugin options are not shown on plugin's list.

function kcalto_widgets_init() { 
  register_widget( 'kcalto_Widget' );
}
add_action( 'widgets_init', 'kcalto_widgets_init' );

?>