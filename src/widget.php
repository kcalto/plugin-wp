<?php

$kcalto_api_url = 'https://kcalto.com/api/recipes/';

class kcalto_Widget extends WP_Widget
{
  public function __construct()
  {
    $widget_options = array(
      'classname' => 'kcalto_Widget',
      'description' => "Kcalto's nutrition values table",
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

    try {
      if ($this->options['yoast_fix'] == 'on') {
        $yoast_query = $wpdb->prepare("SELECT `canonical` FROM `{$wpdb->prefix}yoast_indexable` WHERE `object_type`=\"post\" AND `object_id`=\"%d\"", $post->ID);
        $yoast_seo_canonical = $wpdb->get_results($yoast_query)[0]->canonical;
        if ($yoast_seo_canonical) {
          return $yoast_seo_canonical;
        }
      }

      if ($this->options['aioseo_fix'] == 'on') {
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

    $url = $kcalto_api_url . preg_replace('/https?:\/\//', '', $canonical_url);

    $request_options = array(
      'timeout' => 5,
      'headers' => array(
        'X-API-KEY' => $this->options['api_key'],
        'X-WP-NAME' => $this->sourceName,
        'X-WP-VERSION' => $this->sourceVersion,
        'X-PHP-VERSION' => $this->phpVersion,
      )
    );

    // TODO: Cache the request using url as key
    $response = wp_remote_get($url, $request_options);

    $response_code = wp_remote_retrieve_response_code($response);
    $nutrition_table = null;

    if (!($response_code >= 400)) {
      $response_body = wp_remote_retrieve_body($response);

      $dom = new DOMDocument();
      $dom->loadHTML($response_body);
      $api_element = $dom->getElementById('kcalto-nutrition');
      $nutrition_table = $dom->saveHTML($api_element);
    }

    return $nutrition_table;
  }

  public function widget($args, $instance)
  {

    $title = apply_filters('widget_title', $instance['title']);

    $canonical_url = $this->get_custom_canonical_url();
    $nutrition_table = $this->get_nutrition_table($canonical_url);

    echo $args['before_widget'] . $args['before_title'] . $title . $args['after_title']; ?>
    <?php echo $nutrition_table ?>
    <?php echo $args['after_widget'];
  }
}
