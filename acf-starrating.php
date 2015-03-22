<?php

/*
Plugin Name: Advanced Custom Fields: Star Rating Field
Plugin URI: https://github.com/lienann/acf-starrating
Description: Add-on to Advanced Custom Fields plugin. Add the possibility to use "Star rating" field in ACF. Before removing the plugin files read uninstall.php!
Version: 1.0.2
Author: lienann
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/
                        
/*  Copyright 2014  liena  (email: lienann@yandex.ru)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/**
 * Constants.
 */

// Restriction on the number of stars. 
define('ACF_SRF_MIN_NUMBER_OF_STARS', 1);
define('ACF_SRF_MAX_NUMBER_OF_STARS', 20);


// Reference: https://codex.wordpress.org/Function_Reference/load_plugin_textdomain
load_plugin_textdomain( 'acf-srf', false, dirname( plugin_basename(__FILE__) ) . '/lang/' ); 

// Table name
global $wpdb;
$wpdb->acfsrf = $wpdb->prefix.'acfsrf';


/**
 * Include field type
 */

// Include field type for ACF4
function acf_srf_register_fields() {
	
    include_once('acf-starrating-v4.php');
	
}
add_action('acf/register_fields', 'acf_srf_register_fields');	


/**
 *  Include functions.
 */

require_once( dirname(__FILE__).'/functions.php' );


/**
 *
 * Plugin activation functions.
 * Creating database table to store statistics voting.
 *
 */

function acf_srf_activation() {
   
    global $wpdb;

    $charset_collate = '';
    if ( $wpdb->has_cap( 'collation' ) ) {
        if ( ! empty($wpdb->charset) ) {
            $charset_collate .= " DEFAULT CHARACTER SET $wpdb->charset";
        }
        if ( ! empty($wpdb->collate) ) {
            $charset_collate .= " COLLATE $wpdb->collate";
        }
    }

    // need drop table?
    // $wpdb->get_var("DROP TABLE $wpdb->acfsrf");
    if ( $wpdb->get_var("SHOW tables LIKE '$wpdb->acfsrf'") != $wpdb->acfsrf ) {

        $sql = "CREATE TABLE " . $wpdb->acfsrf . " (".
            "acfsrf_id  bigint(20)      NOT NULL AUTO_INCREMENT, ".
            "field_key  VARCHAR(20)     NOT NULL, ".
            "meta_id    VARCHAR(256)    NOT NULL, ".
            "rating     INT(4)          NOT NULL, ".
            "timestamp  TIMESTAMP, ".
            "user_id    INT(10)         NOT NULL default '0', ".
            "user_ip    VARCHAR(40)     NOT NULL default '0', ".
            "user_cookie VARCHAR(200)   NOT NULL default '0', ".
            "UNIQUE KEY (acfsrf_id)) $charset_collate";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
    }
    
}
register_activation_hook(__FILE__,'acf_srf_activation');


/**
 *
 * Hooking up scripts and styles. Do not use the method field_group_admin_enqueue_scripts(), 
 * because the scripts needed for the user part of the site.
 *
 */

function acf_srf_method(){    

    wp_register_script( 'acf-srf', plugins_url( '/js/jquery.rating.js', __FILE__ ), array('jquery') );
    wp_register_style( 'acf-srf', plugins_url( '/css/jquery.rating.css', __FILE__ ) ); 
    wp_enqueue_script(array(
        'acf-srf',	
    ));
    wp_enqueue_style(array(
        'acf-srf',	
    ));        
    
    // Register variable with the path to AJAX processor.
    wp_localize_script( 'acf-srf', 'srfajax', array(
        'url'   => admin_url('admin-ajax.php'), 
        'nonce' => wp_create_nonce('srfajax-nonce') )
        );

    wp_localize_script( 'acf-srf', 'objectL10n', array(
        'onevote'   => _n('vote', 'votes', '1',  'acf-srf'),
        'twovote'   => _n('vote', 'votes', '2',  'acf-srf'),
        'manyvote'  => _n('vote', 'votes', '5',  'acf-srf'),
        'yvoice'    => __('Your voice:', 'acf-srf') )       
        );

}

add_action( 'wp_enqueue_scripts', 'acf_srf_method' );
add_action( 'admin_enqueue_scripts', 'acf_srf_method' );

/*
 * Setup test cookie
 */
function acf_srf_init() {
    if ( ! headers_sent() ) {
        setcookie( 'acf-srf_test_cookie', 'Cookie check', time()+ DAY_IN_SECONDS, SITECOOKIEPATH);;
    }
}
add_action('init', 'acf_srf_init'); 

/**
 * Deleting vote statistics when deleting an object (post, user, etc.).
 */

function acf_srf_delete_userlog( $user_id ) {
    global $wpdb;
    $meta  = 'user_' . $user_id;
    $result = $wpdb->get_var( $wpdb->prepare("DELETE FROM $wpdb->acfsrf WHERE meta_id = '%s'", $meta) );       
}
add_action( 'delete_user', 'acf_srf_delete_userlog' );

function acf_srf_delete_postlog( $post_id ) {
    global $wpdb;
    $meta  = $post_id;
    $result = $wpdb->get_var( $wpdb->prepare("DELETE FROM $wpdb->acfsrf WHERE meta_id = '%s'", $meta) );       
}
add_action( 'delete_post', 'acf_srf_delete_postlog' );
add_action( 'delete_attachment', 'acf_srf_delete_postlog' );

function acf_srf_delete_termlog( $term_id, $tt_id, $taxonomy ) {
    global $wpdb;
    $meta  = $taxonomy . '_' . $term_id;
    $result = $wpdb->get_var( $wpdb->prepare("DELETE FROM $wpdb->acfsrf WHERE meta_id = '%s'", $meta) );       
}
add_action( 'delete_term', 'acf_srf_delete_termlog', 10, 3 );

/**
 * Формирование шорткода
 */
