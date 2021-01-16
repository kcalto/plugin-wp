<?php
require_once('widget.php');
require_once('version.php');

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

function kcalto_admin_menu()
{
  add_options_page(
    'Kcalto',
    'Kcalto',
    'manage_options',
    KCALTO_SETTINGS,
    'kcalto_settings_page_callback'
  );
}
add_action('admin_menu', 'kcalto_admin_menu');

function kcalto_settings_page_callback()
{
  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
  }
?>
  <div class="wrap">
    <h2>Kcalto settings</h2>
    <form action="options.php" method="POST">
      <?php settings_fields(KCALTO_SETTINGS); ?>
      <div class="settings-container">
        <?php do_settings_sections(KCALTO_SETTINGS); ?>
      </div>
      <?php submit_button(); ?>
    </form>
  </div>
<?php
}

function kcalto_add_option_field($option_slug, $option_name, $type, $section =  'kcalto_default_section')
{
  add_settings_field(
    $option_slug,
    $option_name,
    'kcalto_display_field',
    KCALTO_SETTINGS,
    $section,
    array(
      'type' => $type,
      'name' => $option_slug,
    )
  );
}

function kcalto_settings_init()
{
  add_option(KCALTO_SETTINGS, array(
    'api_key' => '',
    'yoast_fix' => false,
    'aioseo_fix' => false
  ), '', 'yes');

  register_setting(KCALTO_SETTINGS, KCALTO_SETTINGS, 'kcalto_settings_validate');

  add_settings_section(
    'kcalto_default_section',
    'Common',
    'kcalto_common_section_callback',
    KCALTO_SETTINGS
  );

  add_settings_section(
    'kcalto_debug_section',
    'Debug',
    'kcalto_debug_section_callback',
    KCALTO_SETTINGS
  );

  kcalto_add_option_field(
    'api_key',
    'API key',
    'text'
  );

  kcalto_add_option_field(
    'yoast_fix',
    'Yoast debug',
    'checkbox',
    'kcalto_debug_section'
  );

  kcalto_add_option_field(
    'aioseo_fix',
    'All In One SEO debug',
    'checkbox',
    'kcalto_debug_section'
  );
}
add_action('admin_init', 'kcalto_settings_init');

function kcalto_common_section_callback()
{
  echo 'Common settings.';
}

function kcalto_debug_section_callback()
{
  echo 'Should be left unmodified at the live site.';
}

function kcalto_display_field($args)
{
  $options = get_option(KCALTO_SETTINGS);

  $name = $args['name'];
  $option_type = $args['type'];

  $option_name = KCALTO_SETTINGS . "[" . $name  . "]";
  $option_value = $options[$name];

  switch ($option_type) {
    case 'text':
      echo "<input type='" . esc_attr($option_type) . "' name='" . esc_attr($option_name) . "' value='" . $option_value . "' />";
      break;

    case 'checkbox':
      $checked = '';
      if ($option_value) {
        $checked = 'checked="checked"';
      }
      echo "<input type='" . esc_attr($option_type) . "' name='" . esc_attr($option_name) . "'" . $checked . " />";
      break;
  }
}

function kcalto_settings_validate($data)
{
  return $data;
}

function kcalto_get_cached_remote_info()
{
  // Plugin update code thanks to https://rudrastyh.com/wordpress/self-hosted-plugin-update.html
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
  if ('plugin_information' !== $action) {
    return false;
  }

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
    /*
    $res->sections = array(
      'description' => $remote->sections->description,
      'installation' => $remote->sections->installation,
      'changelog' => $remote->sections->changelog
    );
    */

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
