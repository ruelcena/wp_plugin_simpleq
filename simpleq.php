<?php
/*
Plugin Name: Simple Questionnaire
Plugin URI: https://ph.linkedin.com/in/ruel-cena-2825885
Description: A simple custom questionnaire.
Version: 1.0.0
Author: Ruel Cena
Author URI: https://ph.linkedin.com/in/ruel-cena-2825885
License: GPLv2 or later
*/

@session_start();

if ( !function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

define( 'SIMPLEQ_VERSION', '1.0.0' );
define( 'SIMPLEQ_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

register_activation_hook( __FILE__, ['SimpleQ', 'plugin_activation'] );
register_deactivation_hook( __FILE__, ['SimpleQ', 'plugin_deactivation'] );

//require_once( SIMPLEQ_PLUGIN_DIR . 'lib/vendor/autoload.php' );
//require_once( SIMPLEQ_PLUGIN_DIR . 'lib/swift_mailer/autoload.php' );
//require_once( SIMPLEQ_PLUGIN_DIR . 'class.page-templater.php' );

require_once( SIMPLEQ_PLUGIN_DIR . 'class.simpleq.php' );

//add_action( 'plugins_loaded', [ 'PageTemplater', 'get_instance' ] );

add_action( 'init', ['SimpleQ', 'init'] );
add_action( 'init', ['SimpleQ', 'post'] );

if ( !is_admin() )
add_filter('show_admin_bar', '__return_false');
  

if ( is_admin() )
{     
  require_once( SIMPLEQ_PLUGIN_DIR . 'class.questionnaire.php' );
	require_once( SIMPLEQ_PLUGIN_DIR . 'class.simpleq-admin.php' );
	add_action( 'init', ['SimpleQ_Admin', 'init'] );    
}