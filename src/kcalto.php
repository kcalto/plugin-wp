<?php
require_once('widget.php');

function kcalto_hook_activation() {
  // TODO
}
register_activation_hook( __FILE__, 'kcalto_hook_activation' );

function kcalto_hook_deactivation() {
  // TODO
}
register_deactivation_hook( __FILE__, 'kcalto_hook_deactivation' );

function kcalto_widgets_init() { 
  register_widget( 'kcalto_Widget' );
}
add_action( 'widgets_init', 'kcalto_widgets_init' );

?>