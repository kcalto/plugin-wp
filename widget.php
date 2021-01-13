<?php

class kcalto_Widget extends WP_Widget {
  public function __construct() {
    $widget_options = array( 
      'classname' => 'kcalto_Widget',
      'description' => "Kcalto's nutrition values table",
    );
    parent::__construct( 'kcalto_Widget', 'Kcalto Widget', $widget_options );
  }

  public function widget( $args, $instance ) {
    $title = apply_filters( 'widget_title', $instance[ 'title' ] );
    $blog_title = get_bloginfo( 'name' );
    $tagline = get_bloginfo( 'description' );
  
    echo $args['before_widget'] . $args['before_title'] . $title . $args['after_title']; ?>
  
    <p><strong>Site Name:</strong> <?php echo $blog_title ?></p>
    <p><strong>Tagline:</strong> <?php echo $tagline ?></p>
  
    <?php echo $args['after_widget'];
  }
}

?>