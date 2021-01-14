<?php

$kcalto_api_url = 'https://kcalto.com/api/recipes/';

class kcalto_Widget extends WP_Widget {
  public function __construct() {
    $widget_options = array( 
      'classname' => 'kcalto_Widget',
      'description' => "Kcalto's nutrition values table",
    );
    parent::__construct( 'kcalto_Widget', 'Kcalto Widget', $widget_options );
  }

  private function get_custom_canonical_url() {
    global $wpdb;
    global $post;
  
    // TODO: Query for this only if this plugin's option is enabled
    $query = $wpdb->prepare("SELECT `canonical` FROM `{$wpdb->prefix}yoast_indexable` WHERE `object_type`=\"post\" AND `object_id`=\"%d\"", $post->ID);
    $yoast_seo_canonical = $wpdb->get_results($query)[0]->canonical;
    if ( $yoast_seo_canonical ) {
      return $yoast_seo_canonical;
    }
  
    return wp_get_canonical_url();
  }

  public function widget( $args, $instance ) {
    global $kcalto_api_url;
    global $post;

    $title = apply_filters( 'widget_title', $instance[ 'title' ] );

    $canonical_url = $this->get_custom_canonical_url();

    $url = $kcalto_api_url . preg_replace('/https?:\/\//', '', $canonical_url);
    
    // TODO: Cache the request using url as key
    $response = wp_remote_get($url);
    $response_code = wp_remote_retrieve_response_code($response);
    $nutrition_table = null;
    if (!($response_code >= 400)) {
      $response_body = wp_remote_retrieve_body($response);

      $dom = new DOMDocument();
      $dom->loadHTML($response_body);
      $api_element = $dom->getElementById('kcalto-nutrition');
      $nutrition_table = $dom->saveHTML($api_element);
    }

    echo $args['before_widget'] . $args['before_title'] . $title . $args['after_title']; ?>
    <?php echo $nutrition_table ?>
    <?php echo $args['after_widget'];
  }
}

?>