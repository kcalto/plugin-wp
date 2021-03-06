<?php

$kcalto_api_url = 'https://kcalto.com/api/recipes/';

class kcalto_Widget extends WP_Widget
{
  public function __construct()
  {
    $widget_options = array(
      'classname' => 'kcalto_Widget',
      'description' => "Wyświetla tabelę wartości odżywczych dla obsługiwanych wpisów.",
    );
    parent::__construct('kcalto_Widget', 'Kcalto Widget', $widget_options);

    $this->options = get_option(KCALTO_SETTINGS);
    $this->sourceName = get_site_url();
    $this->sourceVersion = get_bloginfo('version');
    $this->phpVersion = phpversion();
  }

  private function get_custom_canonical_url()
  {
    global $wpdb;
    global $post;

    if (is_front_page()) {
      return null;
    }

    try {
      if (array_key_exists('yoast_fix', $this->options) && $this->options['yoast_fix'] == 'on') {
        $yoast_query = $wpdb->prepare("SELECT `canonical` FROM `{$wpdb->prefix}yoast_indexable` WHERE `object_type`=\"post\" AND `object_id`=\"%d\"", $post->ID);
        $yoast_seo_canonical = $wpdb->get_results($yoast_query)[0]->canonical;
        if ($yoast_seo_canonical) {
          return $yoast_seo_canonical;
        }
      }

      if (array_key_exists('aioseo_fix', $this->options) && $this->options['aioseo_fix'] == 'on') {
        $aioseo_query = $wpdb->prepare("SELECT `canonical_url` FROM `{$wpdb->prefix}aioseo_posts` WHERE `post_id`=\"%d\"", $post->ID);
        $aioseo_seo_canonical = $wpdb->get_results($aioseo_query)[0]->canonical_url;
        if ($aioseo_seo_canonical) {
          return $aioseo_seo_canonical;
        }
      }
    } catch (Exception $e) {
      // Ignore the exception, we just want to be safe
    }

    return wp_get_canonical_url();
  }

  private function get_nutrition_table($canonical_url)
  {
    global $kcalto_api_url;

    if (!$canonical_url) {
      return null;
    }

    $url = $kcalto_api_url . preg_replace('/https?:\/\//', '', $canonical_url);
    $cache_key = KCALTO_SLUG . $canonical_url;

    $cached_nutrition_table = get_transient($cache_key);

    if ($cached_nutrition_table) {
      return $cached_nutrition_table;
    }

    $request_options = array(
      'timeout' => 5,
      'headers' => array(
        'X-KCALTO-API-KEY' => $this->options['api_key'],
        'X-KCALTO-CANONICAL-SOURCE' => $canonical_url,
        'X-KCALTO-WP-NAME' => $this->sourceName,
        'X-KCALTO-PLUGIN-VERSION' => KCALTO_CURRENT_VERSION,
        'X-KCALTO-WP-VERSION' => $this->sourceVersion,
        'X-KCALTO-PHP-VERSION' => $this->phpVersion,
      )
    );

    $response = wp_remote_get($url, $request_options);

    $response_code = wp_remote_retrieve_response_code($response);
    $nutrition_table = null;

    if (!($response_code >= 400)) {
      $response_body = wp_remote_retrieve_body($response);

      preg_match('/<div id="kcalto-nutrition">(.*)<\/div>/s', $response_body, $match);
      $nutrition_table = $match[0];
    }

    if ($nutrition_table) {
      set_transient($cache_key, $nutrition_table, 60 * 60 * 1);
    }

    return $nutrition_table;
  }

  public function widget($args, $instance)
  {
    $title = array_key_exists('title', $instance) ? apply_filters('widget_title', $instance['title']) : null;

    $canonical_url = $this->get_custom_canonical_url();
    $nutrition_table = $this->get_nutrition_table($canonical_url);

    echo $args['before_widget'] . $args['before_title'] . $title . $args['after_title']; ?>
    <?php echo $nutrition_table ?>
    <?php echo $args['after_widget'];
  }
}
