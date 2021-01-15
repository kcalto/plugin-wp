<?php
require_once('widget.php');
require_once('version.php');

if (!defined('KCALTO_SLUG')) {
  // Changing this breaks remote updates
  define('KCALTO_SLUG', 'kcalto/kcalto.php');
}

if (!defined('KCALTO_TRANSIENT')) {
  define('KCALTO_TRANSIENT', 'update_' . KCALTO_SLUG);
}

if (!defined('KCALTO_REMOTE_RELEASES_URL')) {
  define('KCALTO_REMOTE_RELEASES_URL', 'http://releases.kcalto.com/wordpress/info.json');
}

function kcalto_hook_activation()
{
}
register_activation_hook(KCALTO_FILE, 'kcalto_hook_activation');

function kcalto_hook_deactivation()
{
  delete_transient(KCALTO_TRANSIENT);
}
register_deactivation_hook(KCALTO_FILE, 'kcalto_hook_deactivation');

function kcalto_hook_uninstall()
{
  kcalto_hook_deactivation();
}
register_uninstall_hook(KCALTO_FILE, 'kcalto_hook_uninstall');

function kcalto_get_cached_remote_info()
{
  /*
  Plugin update code thanks to https://rudrastyh.com/wordpress/self-hosted-plugin-update.html
  */
  if (false == $remote = get_transient(KCALTO_TRANSIENT)) {

    $remote = wp_remote_get(
      KCALTO_REMOTE_RELEASES_URL,
      array(
        'timeout' => 10,
        'headers' => array(
          'Accept' => 'application/json'
        )
      )
    );

    if (is_wp_error($remote)) {
      return false;
    }

    if (isset($remote['response']['code']) && $remote['response']['code'] == 200 && !empty($remote['body'])) {
      set_transient(KCALTO_TRANSIENT, $remote, 12 * 60 * 60); // 12 hours cache
    }
  }

  return $remote;
}

function kcalto_plugin_info($res, $action, $args)
{
  // do nothing if this is not about getting plugin information
  if ('plugin_information' !== $action) {
    return false;
  }

  // do nothing if it is not our plugin
  if (KCALTO_SLUG !== $args->slug) {
    return false;
  }

  $remote = kcalto_get_cached_remote_info();

  if ($remote && isset($remote['response']['code']) && $remote['response']['code'] == 200 && !empty($remote['body'])) {

    $remote = json_decode($remote['body']);
    $res = new stdClass();

    $res->name = $remote->name;
    $res->slug = KCALTO_SLUG;
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
add_filter('plugins_api', 'kcalto_plugin_info', 10, 3);

function kcalto_site_transient_update_plugins($transient)
{
  if (empty($transient->checked)) {
    return $transient;
  }

  $res = new stdClass();

  $res->slug = KCALTO_SLUG;
  $res->plugin = KCALTO_SLUG;
  $res->version = KCALTO_CURRENT_VERSION;

  $remote = kcalto_get_cached_remote_info();
  if ($remote) {

    $remote = json_decode($remote['body']);

    // your installed plugin version should be on the line below! You can obtain it dynamically of course 
    if ($remote && version_compare(KCALTO_CURRENT_VERSION, $remote->version, '<') && version_compare($remote->requires, get_bloginfo('version'), '<')) {

      $res->new_version = $remote->version;
      $res->tested = $remote->tested;
      $res->package = $remote->download_url;

      $transient->response[$res->plugin] = $res;
    }
  } else {
    $transient->no_update[$res->plugin] = $res;
  }

  $transient->checked[$res->plugin] = KCALTO_CURRENT_VERSION;

  return $transient;
}
add_filter('site_transient_update_plugins', 'kcalto_site_transient_update_plugins');

function kcalto_plugin_row_meta($plugin_meta, $plugin_file, $plugin_data, $status)
{
  if ($plugin_file !== KCALTO_SLUG) {
    return $plugin_meta;
  }

  array_unshift($plugin_meta, 'Version ' . KCALTO_CURRENT_VERSION);

  return $plugin_meta;
}
add_filter('plugin_row_meta', 'kcalto_plugin_row_meta', 10, 4);

function kcalto_widgets_init()
{
  register_widget('kcalto_Widget');
}
add_action('widgets_init', 'kcalto_widgets_init');
