<?php
//if uninstall not called from WordPress exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die();
}

global $wpdb;
/* @var wpdb $wpdb */
    
//$tables = ['wp_accumed_companys', 'wp_accumed_members', 'wp_accumed_post', 'wp_accumed_qa'];  
//foreach( $tables as $t ) {
//$wpdb->query( "DROP TABLE IF EXISTS " . $t );
//}